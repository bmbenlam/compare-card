/**
 * HK Card Compare – Public JavaScript.
 *
 * Handles:
 * - Click tracking
 * - Unified toolbar toggle (filters + sort + toggle)
 * - AJAX filtering with debounce
 * - Miles / Cash real toggle switch
 * - Sort dropdown with auto view-mode switching
 * - Feature-based filter chips
 * - Expand / collapse card details (zh-HK)
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

		if (navigator.sendBeacon) {
			navigator.sendBeacon(hkccPublic.ajaxUrl, body);
		} else {
			fetch(hkccPublic.ajaxUrl, { method: 'POST', body: body, keepalive: true });
		}
	});

	/* ----------------------------------------------------------------
	 * Unified toolbar toggle (replaces old filter toggle).
	 * -------------------------------------------------------------- */
	document.addEventListener('click', function (e) {
		var header = e.target.closest('.hkcc-toolbar-header');
		if (!header) return;

		var toolbar = header.closest('.hkcc-toolbar');
		var body = toolbar.querySelector('.hkcc-toolbar-body');

		if (body.style.display === 'none') {
			body.style.display = '';
			toolbar.classList.add('open');
		} else {
			body.style.display = 'none';
			toolbar.classList.remove('open');
		}
	});

	/* ----------------------------------------------------------------
	 * Taxonomy filter groups — collapsible toggle.
	 * -------------------------------------------------------------- */
	document.addEventListener('click', function (e) {
		var toggle = e.target.closest('.hkcc-filter-groups-toggle');
		if (!toggle) return;

		var content = toggle.nextElementSibling;
		if (!content || !content.classList.contains('hkcc-filter-groups-content')) return;

		var isOpen = content.classList.contains('open');
		if (isOpen) {
			content.classList.remove('open');
			content.style.display = 'none';
			toggle.classList.remove('open');
		} else {
			content.classList.add('open');
			content.style.display = '';
			toggle.classList.add('open');
		}
	});

	/* ----------------------------------------------------------------
	 * View toggle switch: update labels on change.
	 * -------------------------------------------------------------- */
	document.addEventListener('change', function (e) {
		if (!e.target.classList.contains('hkcc-view-toggle-input')) return;

		var wrapper = e.target.closest('.hkcc-comparison');
		if (!wrapper) return;

		updateToggleLabels(wrapper);
		triggerFilter(wrapper);
	});

	function updateToggleLabels(wrapper) {
		var input = wrapper.querySelector('.hkcc-view-toggle-input');
		if (!input) return;

		var milesLabel = wrapper.querySelector('.hkcc-toggle-miles');
		var cashLabel = wrapper.querySelector('.hkcc-toggle-cash');

		if (input.checked) {
			// checked = cash
			milesLabel && milesLabel.classList.remove('active');
			cashLabel && cashLabel.classList.add('active');
		} else {
			// unchecked = miles
			milesLabel && milesLabel.classList.add('active');
			cashLabel && cashLabel.classList.remove('active');
		}
	}

	function getViewMode(wrapper) {
		var input = wrapper.querySelector('.hkcc-view-toggle-input');
		if (!input) return wrapper.getAttribute('data-view') || 'miles';
		return input.checked ? 'cash' : 'miles';
	}

	function setViewMode(wrapper, mode) {
		var input = wrapper.querySelector('.hkcc-view-toggle-input');
		if (!input) return;
		input.checked = (mode === 'cash');
		updateToggleLabels(wrapper);
	}

	/* ----------------------------------------------------------------
	 * Clear all filters (also resets sort dropdown & view toggle).
	 * -------------------------------------------------------------- */
	document.addEventListener('click', function (e) {
		if (!e.target.classList.contains('hkcc-clear-filters')) return;

		var wrapper = e.target.closest('.hkcc-comparison');

		// Reset all filter chips (feature, bank, network).
		wrapper.querySelectorAll('.hkcc-filter-chip').forEach(function (chip) {
			chip.checked = false;
		});

		// Reset sort dropdown.
		var sortSelect = wrapper.querySelector('.hkcc-sort-select');
		if (sortSelect) {
			sortSelect.value = '|';
			wrapper.setAttribute('data-sort', '');
			wrapper.setAttribute('data-order', 'desc');
		}

		// Reset view toggle to miles.
		setViewMode(wrapper, 'miles');

		triggerFilter(wrapper);
	});

	/* ----------------------------------------------------------------
	 * Sort change → auto-switch view mode + AJAX.
	 * -------------------------------------------------------------- */
	document.addEventListener('change', function (e) {
		var input = e.target;

		if (input.closest('.hkcc-sort-bar')) {
			var wrapper = input.closest('.hkcc-comparison');
			if (!wrapper) return;
			var parts = input.value.split('|');
			var sortField = parts[0] || '';
			var sortOrder = parts[1] || 'desc';

			wrapper.setAttribute('data-sort', sortField);
			wrapper.setAttribute('data-order', sortOrder);

			// Auto-switch view mode based on sort field.
			if (sortField.indexOf('_cash_sortable') !== -1) {
				setViewMode(wrapper, 'cash');
			} else if (sortField.indexOf('_miles_sortable') !== -1) {
				setViewMode(wrapper, 'miles');
			}

			triggerFilter(wrapper);
			return;
		}

		// Handle filter chips (feature, bank, network).
		if (
			!input.classList.contains('hkcc-filter-chip') &&
			!input.classList.contains('hkcc-view-toggle-input')
		) {
			return;
		}

		var wrapper = input.closest('.hkcc-comparison');
		if (!wrapper) return;

		// Skip view toggle (handled separately above).
		if (input.classList.contains('hkcc-view-toggle-input')) return;

		debounce(function () {
			triggerFilter(wrapper);
		}, 300)();
	});

	/**
	 * Collect current filter state and request filtered cards.
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

		// Feature-based filters (chips).
		var features = [];
		wrapper.querySelectorAll('.hkcc-filter-chip:checked').forEach(function (chip) {
			features.push(chip.value);
		});
		if (features.length) filters.features = features;

		// View mode (from toggle switch).
		var view = getViewMode(wrapper);

		// Sort.
		var sort = wrapper.getAttribute('data-sort') || '';
		var order = wrapper.getAttribute('data-order') || 'desc';

		// Shortcode atts.
		var atts = wrapper.getAttribute('data-shortcode-atts') || '{}';

		// Active count.
		var activeCount = Object.keys(filters).length;
		if (sort) activeCount++;
		var countEl = wrapper.querySelector('.hkcc-active-count');
		if (countEl) {
			countEl.textContent = activeCount > 0 ? '(' + activeCount + ' 個條件)' : '';
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
	 * Expand / Collapse card details (zh-HK text).
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

	/* ----------------------------------------------------------------
	 * Single-card preview toggle — reload page with view param.
	 * -------------------------------------------------------------- */
	document.addEventListener('change', function (e) {
		if (!e.target.classList.contains('hkcc-single-view-toggle')) return;

		var mode = e.target.checked ? 'cash' : 'miles';
		var url = new URL(window.location.href);
		url.searchParams.set('view', mode);
		window.location.href = url.toString();
	});

})();
