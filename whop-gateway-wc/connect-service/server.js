require('dotenv').config();

const crypto = require('crypto');
const express = require('express');
const fs = require('fs');
const path = require('path');

const app = express();
app.use(express.json());

const PORT = process.env.PORT || 8787;
const BASE = (process.env.CONNECT_BASE_URL || `http://localhost:${PORT}`).replace(/\/$/, '');
const SANDBOX = process.env.WHOP_SANDBOX === 'true';
const API_BASE = SANDBOX ? 'https://sandbox-api.whop.com/api/v1' : 'https://api.whop.com/api/v1';
const OAUTH_BASE = SANDBOX ? 'https://sandbox-api.whop.com/oauth' : 'https://api.whop.com/oauth';

const STORE_FILE = path.join(__dirname, '.sessions.json');
const sessions = loadSessions();

function loadSessions() {
  try {
    if (fs.existsSync(STORE_FILE)) {
      return JSON.parse(fs.readFileSync(STORE_FILE, 'utf8'));
    }
  } catch (e) {
    console.error('Failed to load sessions', e);
  }
  return { oauth: {}, setups: {} };
}

function saveSessions() {
  fs.writeFileSync(STORE_FILE, JSON.stringify(sessions, null, 2));
}

function randomString(bytes = 32) {
  return crypto.randomBytes(bytes).toString('base64url');
}

function base64url(buffer) {
  return buffer.toString('base64').replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}

function pkceChallenge(verifier) {
  return base64url(crypto.createHash('sha256').update(verifier).digest());
}

const SESSION_TTL_MS = 15 * 60 * 1000;
const OAUTH_TTL_MS = 15 * 60 * 1000;

function purgeExpiredSessions() {
  const now = Date.now();
  for (const [key, session] of Object.entries(sessions.oauth)) {
    if (now - session.createdAt > OAUTH_TTL_MS) {
      delete sessions.oauth[key];
    }
  }
  for (const [key, setup] of Object.entries(sessions.setups)) {
    if (now - setup.createdAt > SESSION_TTL_MS) {
      delete sessions.setups[key];
    }
  }
}

function isValidReturnUrl(returnUrl, siteUrl) {
  try {
    const ret = new URL(returnUrl);
    const site = new URL(siteUrl);

    if (!['http:', 'https:'].includes(ret.protocol)) {
      return false;
    }

    if (ret.origin !== site.origin) {
      return false;
    }

    if (!ret.pathname.includes('/wp-admin/admin.php')) {
      return false;
    }

    if (ret.searchParams.get('page') !== 'whop-gateway-wc') {
      return false;
    }

    if (ret.searchParams.get('whop_connect') !== '1') {
      return false;
    }

    return true;
  } catch (e) {
    return false;
  }
}

function isValidSiteUrl(siteUrl) {
  try {
    const site = new URL(siteUrl);
    return ['http:', 'https:'].includes(site.protocol);
  } catch (e) {
    return false;
  }
}

function licenseValid(key) {
  if (!key) return false;
  const allowed = (process.env.LICENSE_KEYS || '').split(',').map((s) => s.trim()).filter(Boolean);
  if (allowed.includes('*')) return true;
  if (allowed.includes(key)) return true;
  if (process.env.STRICT_LICENSES === 'true') return false;
  return /^WGWC-(PRO|AGENCY)-/i.test(key);
}

function bridgeAuthorized(req, setupToken, siteUrl) {
  const secret = process.env.CONNECT_BRIDGE_SECRET || '';
  if (!secret) return true;
  const header = String(req.headers['x-whop-gw-bridge'] || '');
  const expected = crypto.createHmac('sha256', secret).update(`${setupToken}|${siteUrl}`).digest('hex');
  if (header.length !== expected.length) return false;
  try {
    return crypto.timingSafeEqual(Buffer.from(header), Buffer.from(expected));
  } catch (e) {
    return false;
  }
}

async function whopFetch(path, options = {}) {
  const url = `${API_BASE}${path}`;
  const res = await fetch(url, options);
  const text = await res.text();
  let data = {};
  try {
    data = text ? JSON.parse(text) : {};
  } catch (e) {
    data = { raw: text };
  }
  return { status: res.status, data };
}

async function exchangeCode(code, verifier) {
  const body = new URLSearchParams({
    grant_type: 'authorization_code',
    code,
    redirect_uri: `${BASE}/callback`,
    client_id: process.env.WHOP_CLIENT_ID,
    client_secret: process.env.WHOP_CLIENT_SECRET,
    code_verifier: verifier,
  });

  const res = await fetch(`${OAUTH_BASE}/token`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body,
  });

  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    throw new Error(data.error_description || data.error || 'Token exchange failed');
  }
  return data;
}

async function createWebhook(accessToken, companyId, webhookUrl) {
  const apiKey = process.env.WHOP_APP_API_KEY || accessToken;
  const payload = {
    url: webhookUrl,
    api_version: 'v1',
    enabled: true,
    events: ['payment.succeeded', 'payment.failed', 'refund.created'],
  };
  if (companyId) payload.resource_id = companyId;

  const { status, data } = await whopFetch('/webhooks', {
    method: 'POST',
    headers: {
      Authorization: `Bearer ${apiKey}`,
      'Content-Type': 'application/json',
      Accept: 'application/json',
    },
    body: JSON.stringify(payload),
  });

  if (status < 200 || status >= 300) {
    throw new Error(data.error?.message || data.message || `Webhook create HTTP ${status}`);
  }

  return {
    id: data.id,
    secret: data.secret || data.signing_secret || '',
  };
}

app.get('/health', (_req, res) => {
  res.json({ ok: true, service: 'whop-gateway-connect' });
});

app.get('/start', (req, res) => {
  purgeExpiredSessions();

  const siteUrl = String(req.query.site_url || '');
  const returnUrl = String(req.query.return_url || '');
  const licenseKey = String(req.query.license_key || '');
  const wpState = String(req.query.state || '');

  if (!siteUrl || !returnUrl) {
    return res.status(400).send('Missing site_url or return_url');
  }

  if (!isValidSiteUrl(siteUrl)) {
    return res.status(400).send('Invalid site_url');
  }

  if (!isValidReturnUrl(returnUrl, siteUrl)) {
    return res.status(400).send('Invalid return_url');
  }

  if (!wpState) {
    return res.status(400).send('Missing state (WordPress session nonce).');
  }

  if (!licenseValid(licenseKey)) {
    return res.status(403).send('Invalid or missing license key. Pro/Agency required.');
  }

  if (!process.env.WHOP_CLIENT_ID || !process.env.WHOP_CLIENT_SECRET) {
    return res.status(500).send('Connect service not configured. Set WHOP_CLIENT_ID and WHOP_CLIENT_SECRET.');
  }

  const state = randomString(16);
  const verifier = randomString(32);
  const webhookUrl = new URL('/?wc-api=whop_webhook', siteUrl).toString();

  sessions.oauth[state] = {
    verifier,
    returnUrl,
    siteUrl,
    webhookUrl,
    licenseKey,
    wpState,
    createdAt: Date.now(),
  };
  saveSessions();

  const params = new URLSearchParams({
    response_type: 'code',
    client_id: process.env.WHOP_CLIENT_ID,
    redirect_uri: `${BASE}/callback`,
    scope: 'openid profile email',
    state,
    code_challenge: pkceChallenge(verifier),
    code_challenge_method: 'S256',
  });

  res.redirect(`${OAUTH_BASE}/authorize?${params.toString()}`);
});

app.get('/callback', async (req, res) => {
  purgeExpiredSessions();

  const { code, state, error } = req.query;

  if (error) {
    return res.status(400).send(`OAuth error: ${error}`);
  }

  const session = sessions.oauth[state];
  if (!session) {
    return res.status(400).send('Invalid or expired OAuth session.');
  }

  if (Date.now() - session.createdAt > OAUTH_TTL_MS) {
    delete sessions.oauth[state];
    saveSessions();
    return res.status(400).send('OAuth session expired. Start again from your WordPress admin.');
  }

  delete sessions.oauth[state];

  try {
    const tokens = await exchangeCode(String(code), session.verifier);
    const accessToken = tokens.access_token;
    const refreshToken = tokens.refresh_token || '';

    let companyId = tokens.company_id || '';
    if (!companyId) {
      const me = await whopFetch('/me', {
        headers: { Authorization: `Bearer ${accessToken}`, Accept: 'application/json' },
      });
      companyId = me.data?.company_id || me.data?.id || '';
    }

    let webhook = { id: '', secret: '' };
    try {
      webhook = await createWebhook(accessToken, companyId, session.webhookUrl);
    } catch (webhookErr) {
      console.error('Webhook creation failed:', webhookErr.message);
    }

    const setupToken = randomString(24);
    sessions.setups[setupToken] = {
      company_id: companyId,
      access_token: accessToken,
      refresh_token: refreshToken,
      webhook_secret: webhook.secret,
      webhook_id: webhook.id,
      siteUrl: session.siteUrl,
      createdAt: Date.now(),
    };
    saveSessions();

    const redirect = new URL(session.returnUrl);
    redirect.searchParams.set('setup_token', setupToken);
    redirect.searchParams.set('connect_nonce', session.wpState);

    res.redirect(redirect.toString());
  } catch (err) {
    console.error(err);
    res.status(500).send(err.message || 'Connect failed');
  }
});

app.post('/exchange', (req, res) => {
  purgeExpiredSessions();

  const { setup_token: setupToken, site_url: siteUrl } = req.body || {};

  if (!setupToken || !siteUrl) {
    return res.status(400).json({ success: false, error: 'Missing setup_token or site_url' });
  }

  const data = sessions.setups[setupToken];
  if (!data) {
    return res.status(404).json({ success: false, error: 'Invalid or expired setup token' });
  }

  if (Date.now() - data.createdAt > 15 * 60 * 1000) {
    delete sessions.setups[setupToken];
    saveSessions();
    return res.status(410).json({ success: false, error: 'Setup token expired' });
  }

  if (data.siteUrl.replace(/\/$/, '') !== String(siteUrl).replace(/\/$/, '')) {
    return res.status(403).json({ success: false, error: 'Site URL mismatch' });
  }

  if (!bridgeAuthorized(req, String(setupToken), String(siteUrl))) {
    return res.status(403).json({ success: false, error: 'Bridge authorization failed' });
  }

  delete sessions.setups[setupToken];
  saveSessions();

  res.json({
    success: true,
    company_id: data.company_id,
    access_token: data.access_token,
    refresh_token: data.refresh_token,
    webhook_secret: data.webhook_secret,
    webhook_id: data.webhook_id,
  });
});

app.listen(PORT, () => {
  console.log(`Whop Checkout Connect listening on ${BASE} (port ${PORT})`);
});
