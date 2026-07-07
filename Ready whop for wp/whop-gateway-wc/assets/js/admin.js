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

	testApi();
})();
