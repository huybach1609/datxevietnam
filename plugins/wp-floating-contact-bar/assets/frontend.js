(function () {
	'use strict';

	function applyDesktopBehavior() {
		var isDesktop = window.matchMedia && window.matchMedia('(min-width: 769px)').matches;
		var links = document.querySelectorAll('.wfcb-bar .wfcb-link');
		if (!links.length) return;

		links.forEach(function (a) {
			var linkUrl = a.getAttribute('data-wfcb-link-url') || '';
			var linkTarget = a.getAttribute('data-wfcb-link-target') || '_blank';
			var qrPageUrl = a.getAttribute('data-wfcb-qr-page-url') || '';
			var qrPageTarget = a.getAttribute('data-wfcb-qr-page-target') || '_self';

			if (isDesktop && qrPageUrl) {
				a.setAttribute('href', qrPageUrl);
				a.setAttribute('target', qrPageTarget);
			} else if (linkUrl) {
				a.setAttribute('href', linkUrl);
				a.setAttribute('target', linkTarget);
			} else if (qrPageUrl) {
				a.setAttribute('href', qrPageUrl);
				a.setAttribute('target', qrPageTarget);
			}
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', applyDesktopBehavior);
	} else {
		applyDesktopBehavior();
	}

	window.addEventListener('resize', function () {
		// Cheap debounce.
		window.clearTimeout(window.__wfcbResizeTimer);
		window.__wfcbResizeTimer = window.setTimeout(applyDesktopBehavior, 100);
	});
})();

