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
	 * Single-select enforcement for taxonomy checkboxes.
	 * Converts category-style multi-checkbox into single-select.
	 * -------------------------------------------------------------- */
	$(document).on('change', '#card_bankchecklist input[type="checkbox"], #card_networkchecklist input[type="checkbox"]', function () {
		if (this.checked) {
			$(this).closest('ul').find('input[type="checkbox"]').not(this).prop('checked', false);
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
			'<option value="cash">Cash (現金)</option>' +
			'<option value="asia_miles">Asia Miles (亞洲萬里通)</option>' +
			'<option value="avios">Avios (英國航空)</option>' +
			'<option value="emirates_skywards">Emirates Skywards (阿聯酋航空)</option>' +
			'<option value="etihad_guest">Etihad Guest (阿提哈德航空)</option>' +
			'<option value="flying_blue">Flying Blue (法荷航)</option>' +
			'<option value="krisflyer">KrisFlyer (新加坡航空)</option>' +
			'<option value="qantas_ff">Qantas Frequent Flyer (澳洲航空)</option>' +
			'<option value="virgin_fc">Virgin Atlantic Flying Club (維珍航空)</option>' +
			'<option value="finnair_plus">Finnair Plus (芬蘭航空)</option>' +
			'<option value="enrich">Enrich (馬來西亞航空)</option>' +
			'<option value="infinity_mileagelands">Infinity MileageLands (長榮航空)</option>' +
			'<option value="royal_orchid_plus">Royal Orchid Plus (泰國航空)</option>' +
			'<option value="qatar_privilege">Qatar Privilege Club (卡塔爾航空)</option>' +
			'<option value="phoenix_miles">鳳凰知音 (中國國航)</option>' +
			'<option value="aeroplan">Aeroplan (加拿大航空)</option>' +
			'<option value="marriott_bonvoy">Marriott Bonvoy (萬豪)</option>' +
			'<option value="hilton_honors">Hilton Honors (希爾頓)</option>' +
			'<option value="ihg_rewards">IHG Rewards (洲際酒店)</option>' +
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
