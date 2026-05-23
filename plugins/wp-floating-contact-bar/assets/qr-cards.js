(function () {
	'use strict';

	function copyText(text) {
		if (!text) return Promise.reject(new Error('No text to copy'));

		if (navigator.clipboard && window.isSecureContext) {
			return navigator.clipboard.writeText(text);
		}

		// Fallback for non-secure contexts / older browsers.
		return new Promise(function (resolve, reject) {
			try {
				var ta = document.createElement('textarea');
				ta.value = text;
				ta.setAttribute('readonly', '');
				ta.style.position = 'fixed';
				ta.style.top = '-1000px';
				ta.style.left = '-1000px';
				document.body.appendChild(ta);
				ta.select();
				var ok = document.execCommand('copy');
				document.body.removeChild(ta);
				if (ok) resolve();
				else reject(new Error('Copy command failed'));
			} catch (e) {
				reject(e);
			}
		});
	}

	function initCopyHints(wrapper) {
		var hints = wrapper.querySelectorAll('.scan-hint[data-wfcb-copy-text]');
		if (!hints.length) return;

		function bindHint(hint) {
			var busy = false;
			var restoreTimer = null;
			var originalText = hint.textContent;

			function showCopied() {
				var copiedLabel = hint.getAttribute('data-wfcb-copied-label') || 'Copied';
				hint.classList.add('is-copied');
				hint.textContent = copiedLabel;
				hint.setAttribute('aria-live', 'polite');

				clearTimeout(restoreTimer);
				restoreTimer = setTimeout(function () {
					hint.classList.remove('is-copied');
					hint.textContent = originalText;
					hint.removeAttribute('aria-live');
				}, 1200);
			}

			function onActivate() {
				if (busy) return;
				busy = true;
				var text = hint.getAttribute('data-wfcb-copy-text') || '';

				copyText(text)
					.then(showCopied)
					.catch(function () {
						// If copy fails, at least keep it focusable/clickable without breaking.
					})
					.finally(function () {
						busy = false;
					});
			}

			hint.addEventListener('click', function (e) {
				e.preventDefault();
				onActivate();
			});

			hint.addEventListener('keydown', function (e) {
				var key = e.key || e.code;
				if (key === 'Enter' || key === ' ' || key === 'Spacebar') {
					e.preventDefault();
					onActivate();
				}
			});
		}

		hints.forEach ? hints.forEach(bindHint) : Array.prototype.forEach.call(hints, bindHint);
	}

	function initWrapper(wrapper) {
		var track = wrapper.querySelector('[data-wfcb-qr-track]');
		var dotsEl = wrapper.querySelector('[data-wfcb-qr-dots]');
		if (!track || !dotsEl) return;

		initCopyHints(wrapper);

		var cards = Array.prototype.slice.call(wrapper.querySelectorAll('.card'));
		if (!cards.length) return;

		function scrollToCard(idx) {
			var card = cards[idx];
			if (!card) return;

			var trackRect = track.getBoundingClientRect();
			var cardRect = card.getBoundingClientRect();
			var offset =
				cardRect.left -
				trackRect.left +
				track.scrollLeft -
				(trackRect.width - card.offsetWidth) / 2;

			track.scrollTo({ left: offset, behavior: 'smooth' });
		}

		// Build dots
		dotsEl.innerHTML = '';
		cards.forEach(function (_card, i) {
			var d = document.createElement('div');
			d.className = 'dot' + (i === 0 ? ' active' : '');
			d.addEventListener('click', function () {
				scrollToCard(i);
			});
			dotsEl.appendChild(d);
		});

		var allDots = Array.prototype.slice.call(dotsEl.querySelectorAll('.dot'));

		function setActiveDot(idx) {
			allDots.forEach(function (d, i) {
				if (i === idx) d.classList.add('active');
				else d.classList.remove('active');
			});
		}

		// Active dot + visible card on scroll
		var raf = null;
		track.addEventListener('scroll', function () {
			if (raf) cancelAnimationFrame(raf);
			raf = requestAnimationFrame(function () {
				var trackRect = track.getBoundingClientRect();
				var centerX = trackRect.left + trackRect.width / 2;

				var bestIdx = 0;
				var bestDist = Infinity;

				cards.forEach(function (card, idx) {
					var rect = card.getBoundingClientRect();
					var cardCenter = rect.left + rect.width / 2;
					var dist = Math.abs(cardCenter - centerX);
					card.classList.toggle('visible', dist < rect.width * 0.25);
					if (dist < bestDist) {
						bestDist = dist;
						bestIdx = idx;
					}
				});

				setActiveDot(bestIdx);
			});
		});

		// On load: scroll to hash if present
		function scrollToHashIfNeeded() {
			var hash = (location.hash || '').replace('#', '');
			if (!hash) return;
			var idx = cards.findIndex ? cards.findIndex(function (c) { return c.id === hash; }) : -1;
			if (idx < 0) {
				for (var i = 0; i < cards.length; i++) {
					if (cards[i].id === hash) {
						idx = i;
						break;
					}
				}
			}
			if (idx >= 0) scrollToCard(idx);
		}

		window.addEventListener('load', scrollToHashIfNeeded);
		window.addEventListener('hashchange', scrollToHashIfNeeded);
	}

	function initAll() {
		var wrappers = document.querySelectorAll('.wfcb-qr-cards');
		if (!wrappers.length) return;
		wrappers.forEach ? wrappers.forEach(initWrapper) : Array.prototype.forEach.call(wrappers, initWrapper);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initAll);
	} else {
		initAll();
	}
})();

