/**
 * HK Card Compare – Admin JavaScript.
 *
 * Handles:
 * - Tab navigation
 * - Rewards type toggle (cash / points)
 * - Single-select taxonomy enforcement
 * - Media uploader for card face image
 */
(function ($) {
	'use strict';

	/* ----------------------------------------------------------------
	 * Tab navigation.
	 * -------------------------------------------------------------- */
	$(document).on('click', '.hkcc-tab-nav a', function (e) {
		e.preventDefault();

		var $this = $(this);
		var target = $this.attr('href');

		$this.closest('.hkcc-tab-nav').find('a').removeClass('active');
		$this.addClass('active');

		$this.closest('.hkcc-tabs').find('.hkcc-tab-content').hide();
		$(target).show();
	});

	/* ----------------------------------------------------------------
	 * Rewards type toggle.
	 * -------------------------------------------------------------- */
	$(document).on('change', 'input[name="hkcc_reward_type_toggle"]', function () {
		if (this.value === 'points') {
			$('#hkcc-points-section').show().find(':input').prop('disabled', false);
			$('#hkcc-cash-section').hide().find(':input').prop('disabled', true);
		} else {
			$('#hkcc-points-section').hide().find(':input').prop('disabled', true);
			$('#hkcc-cash-section').show().find(':input').prop('disabled', false);
		}
	});

	/* ----------------------------------------------------------------
	 * Single-select taxonomy enforcement.
	 * Convert checkboxes to radio-like behaviour.
	 * -------------------------------------------------------------- */
	$(document).on('change', '#card_bankchecklist input[type="checkbox"], #card_networkchecklist input[type="checkbox"]', function () {
		if (this.checked) {
			$(this).closest('ul').find('input[type="checkbox"]').not(this).prop('checked', false);
		}
	});

	/* ----------------------------------------------------------------
	 * Media uploader for card face image.
	 * -------------------------------------------------------------- */
	$(document).on('click', '.hkcc-upload-image', function (e) {
		e.preventDefault();
		var $field = $(this).closest('.hkcc-image-field');
		var $input = $field.find('input[type="hidden"]');
		var $preview = $field.find('.hkcc-image-preview');
		var $removeBtn = $field.find('.hkcc-remove-image');

		var frame = wp.media({
			title: 'Select Card Face Image',
			button: { text: 'Use this image' },
			multiple: false,
			library: { type: 'image' }
		});

		frame.on('select', function () {
			var attachment = frame.state().get('selection').first().toJSON();
			var url = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
			$input.val(attachment.id);
			$preview.html('<img src="' + url + '" style="max-width:200px;display:block;margin-bottom:8px;" />');
			$removeBtn.show();
		});

		frame.open();
	});

	$(document).on('click', '.hkcc-remove-image', function (e) {
		e.preventDefault();
		var $field = $(this).closest('.hkcc-image-field');
		$field.find('input[type="hidden"]').val('');
		$field.find('.hkcc-image-preview').html('');
		$(this).hide();
	});

})(jQuery);
