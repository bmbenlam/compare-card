/**
 * HK Card Compare – Public JavaScript.
 *
 * Handles:
 * - Click tracking
 * - Filter toggle (mobile accordion)
 * - AJAX filtering with debounce
 * - Miles / Cash toggle
 * - Expand / collapse card details
 */
(function () {
	'use strict';

	var debounceTimer = null;

	/* ----------------------------------------------------------------
	 * Utility: Debounce.
	 * -------------------------------------------------------------- */
	function debounce(fn, delay) {
		return function () {
			var ctx = this;
			var args = arguments;
			clearTimeout(debounceTimer);
			debounceTimer = setTimeout(function () {
				fn.apply(ctx, args);
			}, delay);
		};
	}

	/* ----------------------------------------------------------------
	 * Click tracking.
	 * -------------------------------------------------------------- */
	document.addEventListener('click', function (e) {
		var link = e.target.closest('.card-apply-link');
		if (!link) return;

		var cardId = link.getAttribute('data-card-id');
		if (!cardId) return;

		var body = new URLSearchParams();
		body.append('action', 'hkcc_track_click');
		body.append('nonce', hkccPublic.nonce);
		body.append('card_id', cardId);
		body.append('source_url', window.location.href);

		// Fire-and-forget; don't block the link navigation.
		if (navigator.sendBeacon) {
			navigator.sendBeacon(hkccPublic.ajaxUrl, body);
		} else {
			fetch(hkccPublic.ajaxUrl, { method: 'POST', body: body, keepalive: true });
		}
	});

	/* ----------------------------------------------------------------
	 * Filter toggle (mobile).
	 * -------------------------------------------------------------- */
	document.addEventListener('click', function (e) {
		var header = e.target.closest('.hkcc-filters-header');
		if (!header) return;

		var filters = header.closest('.hkcc-filters');
		var body = filters.querySelector('.hkcc-filters-body');

		if (body.style.display === 'none') {
			body.style.display = '';
			filters.classList.add('open');
		} else {
			body.style.display = 'none';
			filters.classList.remove('open');
		}
	});

	/* ----------------------------------------------------------------
	 * Clear all filters.
	 * -------------------------------------------------------------- */
	document.addEventListener('click', function (e) {
		if (!e.target.classList.contains('hkcc-clear-filters')) return;

		var wrapper = e.target.closest('.hkcc-comparison');
		wrapper.querySelectorAll('.hkcc-filter-options input[type="checkbox"]').forEach(function (cb) {
			cb.checked = false;
		});
		wrapper.querySelectorAll('.hkcc-filter-options input[type="radio"]').forEach(function (rb) {
			if (rb.value === '') rb.checked = true;
		});

		triggerFilter(wrapper);
	});

	/* ----------------------------------------------------------------
	 * Filter change → AJAX.
	 * -------------------------------------------------------------- */
	document.addEventListener('change', function (e) {
		var input = e.target;
		if (
			!input.closest('.hkcc-filter-options') &&
			!input.closest('.hkcc-rebate-toggle')
		) {
			return;
		}

		var wrapper = input.closest('.hkcc-comparison');
		if (!wrapper) return;

		debounce(function () {
			triggerFilter(wrapper);
		}, 300)();
	});

	/**
	 * Collect current filter state and request filtered cards.
	 *
	 * @param {HTMLElement} wrapper .hkcc-comparison element.
	 */
	function triggerFilter(wrapper) {
		var filters = {};

		// Banks (multi-checkbox).
		var banks = [];
		wrapper.querySelectorAll('input[name="hkcc_filter_bank"]:checked').forEach(function (cb) {
			banks.push(cb.value);
		});
		if (banks.length) filters.bank = banks;

		// Networks.
		var networks = [];
		wrapper.querySelectorAll('input[name="hkcc_filter_network"]:checked').forEach(function (cb) {
			networks.push(cb.value);
		});
		if (networks.length) filters.network = networks;

		// Annual fee.
		var annualFee = wrapper.querySelector('input[name="hkcc_filter_annual_fee"]:checked');
		if (annualFee && annualFee.value) filters.annual_fee = annualFee.value;

		// View mode.
		var viewMode = wrapper.querySelector('input[name="hkcc_view_mode"]:checked');
		var view = viewMode ? viewMode.value : wrapper.getAttribute('data-view') || 'miles';

		// Sort.
		var sort = wrapper.getAttribute('data-sort') || 'local_retail_cash_sortable';
		var order = wrapper.getAttribute('data-order') || 'desc';

		// Shortcode atts.
		var atts = wrapper.getAttribute('data-shortcode-atts') || '{}';

		// Active count.
		var activeCount = Object.keys(filters).length;
		var countEl = wrapper.querySelector('.hkcc-active-count');
		if (countEl) {
			countEl.textContent = activeCount > 0 ? '(' + activeCount + ' 個篩選)' : '';
		}

		wrapper.classList.add('hkcc-loading');

		var body = new URLSearchParams();
		body.append('action', 'hkcc_filter_cards');
		body.append('nonce', hkccPublic.nonce);
		body.append('filters', JSON.stringify(filters));
		body.append('sort', sort);
		body.append('order', order);
		body.append('view', view);
		body.append('shortcode_atts', atts);

		fetch(hkccPublic.ajaxUrl, { method: 'POST', body: body })
			.then(function (r) { return r.json(); })
			.then(function (data) {
				wrapper.classList.remove('hkcc-loading');
				if (data.success) {
					var list = wrapper.querySelector('.hkcc-card-list');
					list.innerHTML = data.data.html;

					var countSpan = wrapper.querySelector('.hkcc-count');
					if (countSpan) countSpan.textContent = data.data.count;
				}
			})
			.catch(function () {
				wrapper.classList.remove('hkcc-loading');
			});
	}

	/* ----------------------------------------------------------------
	 * Expand / Collapse card details.
	 * -------------------------------------------------------------- */
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('.hkcc-details-toggle');
		if (!btn) return;

		var card = btn.closest('.hkcc-listing-card');
		var expanded = card.querySelector('.hkcc-card-expanded');

		if (expanded.style.display === 'none') {
			expanded.style.display = '';
			btn.setAttribute('aria-expanded', 'true');
			btn.innerHTML = '收起詳情 &#9650;';
		} else {
			expanded.style.display = 'none';
			btn.setAttribute('aria-expanded', 'false');
			btn.innerHTML = '查看詳情 &#9660;';
		}
	});

})();
