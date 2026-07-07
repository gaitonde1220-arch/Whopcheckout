(function () {
	'use strict';

	function testApi() {
		var btn = document.getElementById('whop-gw-test-api');
		var out = document.getElementById('whop-gw-test-api-result');
		if (!btn || !out || !window.whopGwAdmin) return;

		btn.addEventListener('click', function () {
			out.textContent = 'Testing…';
			var data = new FormData();
			data.append('action', 'whop_gateway_test_connection');
			data.append('nonce', whopGwAdmin.testConnectionNonce);
			fetch(whopGwAdmin.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: data })
				.then(function (r) { return r.json(); })
				.then(function (res) {
					out.textContent = res && res.data && res.data.message ? res.data.message : 'Unknown response';
				})
				.catch(function () { out.textContent = 'Request failed'; });
		});
	}

	function disconnect() {
		var btn = document.getElementById('whop-gw-disconnect');
		if (!btn || !window.whopGwAdmin) return;

		btn.addEventListener('click', function () {
			if (!confirm('Disconnect Whop from this store?')) return;
			var data = new FormData();
			data.append('action', 'whop_gw_disconnect');
			data.append('nonce', whopGwAdmin.disconnectNonce);
			fetch(whopGwAdmin.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: data })
				.then(function () { window.location.reload(); });
		});
	}

	testApi();
	disconnect();
})();
