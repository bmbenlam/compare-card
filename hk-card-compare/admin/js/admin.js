/**
 * HK Card Compare – Admin JavaScript.
 */
(function ($) {
	'use strict';

	/* ----------------------------------------------------------------
	 * Tab switching.
	 * -------------------------------------------------------------- */
	$(document).on('click', '.hkcc-tab-nav a', function (e) {
		e.preventDefault();
		var target = $(this).attr('href');

		$('.hkcc-tab-nav a').removeClass('active');
		$(this).addClass('active');

		$('.hkcc-tab-content').hide();
		$(target).show();
	});

	/* ----------------------------------------------------------------
	 * Rewards type toggle (cash vs points).
	 * -------------------------------------------------------------- */
	$(document).on('change', 'input[name="hkcc_reward_type_toggle"]', function () {
		var val = $(this).val();

		if (val === 'points') {
			$('#hkcc-points-section').show();
			$('#hkcc-cash-section').hide();
			$('#hkcc_points_system_id_cash').prop('disabled', true);
			$('#hkcc_points_system_id').prop('disabled', false);
		} else {
			$('#hkcc-points-section').hide();
			$('#hkcc-cash-section').show();
			$('#hkcc_points_system_id_cash').prop('disabled', false);
			$('#hkcc_points_system_id').prop('disabled', true);
		}
	});

	/* ----------------------------------------------------------------
	 * Points Systems page – add / remove conversion rows.
	 * -------------------------------------------------------------- */
	$(document).on('click', '#hkcc-add-conversion', function () {
		var row =
			'<tr>' +
			'<td><select name="conv_reward_type[]">' +
			'<option value="">— Select —</option>' +
			'<option value="cash">Cash</option>' +
			'<option value="asia_miles">Asia Miles</option>' +
			'<option value="ba_avios">BA Avios</option>' +
			'<option value="marriott_bonvoy">Marriott Bonvoy</option>' +
			'<option value="hilton_honor">Hilton Honors</option>' +
			'</select></td>' +
			'<td><input type="number" name="conv_points_required[]" class="small-text" /></td>' +
			'<td><input type="number" step="0.01" name="conv_reward_value[]" class="small-text" /></td>' +
			'<td><input type="text" name="conv_reward_currency[]" value="HKD" class="small-text" /></td>' +
			'<td><button type="button" class="button hkcc-remove-row">&times;</button></td>' +
			'</tr>';

		$('#hkcc-conversions tbody').append(row);
	});

	$(document).on('click', '.hkcc-remove-row', function () {
		$(this).closest('tr').remove();
	});

})(jQuery);
