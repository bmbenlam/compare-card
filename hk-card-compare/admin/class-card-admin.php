<?php
/**
 * Card admin edit screen: tabbed meta boxes and asset enqueuing.
 *
 * @package HK_Card_Compare
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HKCC_Card_Admin {

	/**
	 * Hook into WordPress.
	 */
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'register_meta_boxes' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'admin_notices', array( __CLASS__, 'expiry_warnings' ) );
	}

	/**
	 * Register meta boxes for the card edit screen.
	 */
	public static function register_meta_boxes() {
		add_meta_box(
			'hkcc_card_details',
			'Card Details',
			array( __CLASS__, 'render_main_meta_box' ),
			'card',
			'normal',
			'high'
		);
	}

	/**
	 * Enqueue admin CSS and JS on card edit screens.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public static function enqueue_assets( $hook_suffix ) {
		$screen = get_current_screen();
		if ( ! $screen || 'card' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_style(
			'hkcc-admin',
			HKCC_PLUGIN_URL . 'admin/css/admin.css',
			array(),
			HKCC_VERSION
		);

		wp_enqueue_script(
			'hkcc-admin',
			HKCC_PLUGIN_URL . 'admin/js/admin.js',
			array( 'jquery' ),
			HKCC_VERSION,
			true
		);

		wp_localize_script( 'hkcc-admin', 'hkccAdmin', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'hkcc_admin_nonce' ),
		) );
	}

	/**
	 * Render the main tabbed meta box.
	 *
	 * @param WP_Post $post Current post.
	 */
	public static function render_main_meta_box( $post ) {
		wp_nonce_field( 'hkcc_save_meta', 'hkcc_meta_nonce' );

		$tabs = array(
			'basic'       => 'Basic Info',
			'fees'        => 'Fees',
			'rewards'     => 'Rewards',
			'welcome'     => 'Welcome Offer',
			'benefits'    => 'Benefits',
			'eligibility' => 'Eligibility',
			'featured'    => 'Featured',
		);

		$all_fields = HKCC_Card_Meta::get_fields();
		?>
		<div class="hkcc-tabs">
			<ul class="hkcc-tab-nav">
				<?php foreach ( $tabs as $key => $label ) : ?>
					<li><a href="#hkcc-tab-<?php echo esc_attr( $key ); ?>" class="<?php echo 'basic' === $key ? 'active' : ''; ?>"><?php echo esc_html( $label ); ?></a></li>
				<?php endforeach; ?>
			</ul>

			<?php foreach ( $tabs as $tab_key => $tab_label ) : ?>
				<div id="hkcc-tab-<?php echo esc_attr( $tab_key ); ?>" class="hkcc-tab-content" style="<?php echo 'basic' !== $tab_key ? 'display:none;' : ''; ?>">
					<?php
					if ( 'rewards' === $tab_key ) {
						self::render_rewards_tab( $post, $all_fields['rewards'] );
					} elseif ( 'featured' === $tab_key ) {
						self::render_featured_tab( $post );
					} else {
						self::render_fields( $post, $all_fields[ $tab_key ] ?? array() );
					}
					?>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Render a group of standard fields with optional footnote inputs.
	 *
	 * @param WP_Post $post   Current post.
	 * @param array   $fields Field definitions.
	 */
	private static function render_fields( $post, $fields ) {
		echo '<table class="form-table hkcc-fields">';
		foreach ( $fields as $key => $def ) {
			$value      = get_post_meta( $post->ID, $key, true );
			$input_name = 'hkcc_' . $key;

			echo '<tr>';
			echo '<th><label for="' . esc_attr( $input_name ) . '">' . esc_html( $def['label'] ) . '</label></th>';
			echo '<td>';

			switch ( $def['type'] ) {
				case 'html':
					wp_editor( $value, $input_name, array(
						'textarea_rows' => 8,
						'media_buttons' => false,
					) );
					break;
				case 'date':
					printf(
						'<input type="date" id="%1$s" name="%1$s" value="%2$s" class="regular-text" />',
						esc_attr( $input_name ),
						esc_attr( $value )
					);
					break;
				case 'url':
					printf(
						'<input type="url" id="%1$s" name="%1$s" value="%2$s" class="regular-text" />',
						esc_attr( $input_name ),
						esc_attr( $value )
					);
					break;
				case 'int':
					printf(
						'<input type="number" step="1" id="%1$s" name="%1$s" value="%2$s" class="small-text" />',
						esc_attr( $input_name ),
						esc_attr( $value )
					);
					break;
				case 'float':
					printf(
						'<input type="number" step="0.01" id="%1$s" name="%1$s" value="%2$s" class="small-text" />',
						esc_attr( $input_name ),
						esc_attr( $value )
					);
					break;
				default:
					$max = $def['max'] ?? '';
					printf(
						'<input type="text" id="%1$s" name="%1$s" value="%2$s" class="regular-text" %3$s />',
						esc_attr( $input_name ),
						esc_attr( $value ),
						$max ? 'maxlength="' . intval( $max ) . '"' : ''
					);
					break;
			}

			// Footnote field for display-type fields (not sortable, not html/array/select).
			if ( ! in_array( $def['type'], array( 'select', 'html', 'array' ), true ) && ! str_ends_with( $key, '_sortable' ) ) {
				$fn_val = get_post_meta( $post->ID, $key . '_footnote', true );
				printf(
					'<div class="hkcc-footnote-field"><label>Footnote: <input type="text" name="hkcc_%s_footnote" value="%s" class="regular-text" placeholder="可選備註" /></label></div>',
					esc_attr( $key ),
					esc_attr( $fn_val )
				);
			}

			echo '</td></tr>';
		}
		echo '</table>';
	}

	/**
	 * Render the Rewards tab with conditional sections.
	 *
	 * @param WP_Post $post   Current post.
	 * @param array   $fields All rewards field definitions.
	 */
	private static function render_rewards_tab( $post, $fields ) {
		$system_id       = (int) get_post_meta( $post->ID, 'points_system_id', true );
		$reward_type_val = $system_id > 0 ? 'points' : 'cash';
		$systems         = HKCC_Points_System::get_all();

		$txn_types  = HKCC_Points_System::get_transaction_types();
		$txn_labels = HKCC_Points_System::get_transaction_labels();
		?>

		<h3>Rewards Type</h3>
		<p>
			<label><input type="radio" name="hkcc_reward_type_toggle" value="cash" <?php checked( $reward_type_val, 'cash' ); ?> /> Direct Cash Rebates</label>&nbsp;&nbsp;
			<label><input type="radio" name="hkcc_reward_type_toggle" value="points" <?php checked( $reward_type_val, 'points' ); ?> /> Points System</label>
		</p>

		<!-- Points system selection -->
		<div id="hkcc-points-section" style="<?php echo 'points' !== $reward_type_val ? 'display:none;' : ''; ?>">
			<table class="form-table hkcc-fields">
				<tr>
					<th><label for="hkcc_points_system_id">Select Points System</label></th>
					<td>
						<select id="hkcc_points_system_id" name="hkcc_points_system_id">
							<option value="0">— None —</option>
							<?php foreach ( $systems as $sys ) : ?>
								<option value="<?php echo esc_attr( $sys->id ); ?>" <?php selected( $system_id, $sys->id ); ?>>
									<?php echo esc_html( $sys->system_name ); ?>
									<?php echo $sys->system_name_en ? '(' . esc_html( $sys->system_name_en ) . ')' : ''; ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<?php foreach ( $txn_types as $txn ) :
					$key   = "{$txn}_points";
					$value = get_post_meta( $post->ID, $key, true );
					$label = $txn_labels[ $txn ] ?? $txn;
					$fn_val = get_post_meta( $post->ID, $key . '_footnote', true );
					?>
					<tr>
						<th><label for="hkcc_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?> (積分)</label></th>
						<td>
							<input type="text" id="hkcc_<?php echo esc_attr( $key ); ?>" name="hkcc_<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="HK$1 = X points" />
							<div class="hkcc-footnote-field"><label>Footnote: <input type="text" name="hkcc_<?php echo esc_attr( $key ); ?>_footnote" value="<?php echo esc_attr( $fn_val ); ?>" class="regular-text" placeholder="可選備註" /></label></div>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
		</div>

		<!-- Direct cash section -->
		<div id="hkcc-cash-section" style="<?php echo 'cash' !== $reward_type_val ? 'display:none;' : ''; ?>">
			<input type="hidden" name="hkcc_points_system_id" value="0" id="hkcc_points_system_id_cash" <?php echo 'cash' !== $reward_type_val ? 'disabled' : ''; ?> />
			<table class="form-table hkcc-fields">
				<?php foreach ( $txn_types as $txn ) :
					$d_key = "{$txn}_cash_display";
					$s_key = "{$txn}_cash_sortable";
					$d_val = get_post_meta( $post->ID, $d_key, true );
					$s_val = get_post_meta( $post->ID, $s_key, true );
					$label = $txn_labels[ $txn ] ?? $txn;
					$fn_val = get_post_meta( $post->ID, $d_key . '_footnote', true );
					?>
					<tr>
						<th><?php echo esc_html( $label ); ?> 現金回贈</th>
						<td>
							<input type="text" name="hkcc_<?php echo esc_attr( $d_key ); ?>" value="<?php echo esc_attr( $d_val ); ?>" class="regular-text" placeholder="e.g. 1%，不設上限" />
							<br />
							<label>Sortable: <input type="number" step="0.01" name="hkcc_<?php echo esc_attr( $s_key ); ?>" value="<?php echo esc_attr( $s_val ); ?>" class="small-text" /> %</label>
							<div class="hkcc-footnote-field"><label>Footnote: <input type="text" name="hkcc_<?php echo esc_attr( $d_key ); ?>_footnote" value="<?php echo esc_attr( $fn_val ); ?>" class="regular-text" placeholder="可選備註" /></label></div>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
		</div>

		<hr />

		<h3>Redemption Options</h3>
		<?php
		$redemption_types = (array) get_post_meta( $post->ID, 'redemption_types', true );
		$all_redemption   = array( 'statement_credit' => 'Statement Credit', 'points_system' => 'Points System' );
		?>
		<p>
		<?php foreach ( $all_redemption as $rval => $rlabel ) : ?>
			<label>
				<input type="checkbox" name="hkcc_redemption_types[]" value="<?php echo esc_attr( $rval ); ?>"
					<?php checked( in_array( $rval, $redemption_types, true ) ); ?> />
				<?php echo esc_html( $rlabel ); ?>
			</label>&nbsp;&nbsp;
		<?php endforeach; ?>
		</p>

		<table class="form-table hkcc-fields">
			<tr>
				<th><label for="hkcc_statement_credit_requirement">Statement Credit Requirement</label></th>
				<td><input type="text" id="hkcc_statement_credit_requirement" name="hkcc_statement_credit_requirement" value="<?php echo esc_attr( get_post_meta( $post->ID, 'statement_credit_requirement', true ) ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="hkcc_points_system_name">Points System Name</label></th>
				<td><input type="text" id="hkcc_points_system_name" name="hkcc_points_system_name" value="<?php echo esc_attr( get_post_meta( $post->ID, 'points_system_name', true ) ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="hkcc_points_redemption_fee_display">Redemption Fee (Display)</label></th>
				<td><input type="text" id="hkcc_points_redemption_fee_display" name="hkcc_points_redemption_fee_display" value="<?php echo esc_attr( get_post_meta( $post->ID, 'points_redemption_fee_display', true ) ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="hkcc_points_redemption_fee_sortable">Redemption Fee (Sortable)</label></th>
				<td><input type="number" step="1" id="hkcc_points_redemption_fee_sortable" name="hkcc_points_redemption_fee_sortable" value="<?php echo esc_attr( get_post_meta( $post->ID, 'points_redemption_fee_sortable', true ) ); ?>" class="small-text" /> HKD</td>
			</tr>
		</table>

		<h4>Transferable Airlines</h4>
		<?php
		$airlines = array(
			'Asia Miles (亞洲萬里通)',
			'Avios (英國航空)',
			'Emirates Skywards (阿聯酋航空)',
			'Etihad Guest (阿提哈德航空)',
			'Flying Blue (法荷航)',
			'KrisFlyer (新加坡航空)',
			'Qantas Frequent Flyer (澳洲航空)',
			'Virgin Atlantic Flying Club (維珍航空)',
			'Finnair Plus (芬蘭航空)',
			'Enrich (馬來西亞航空)',
			'Infinity MileageLands (長榮航空)',
			'Royal Orchid Plus (泰國航空)',
			'Qatar Privilege Club (卡塔爾航空)',
			'鳳凰知音 (中國國航)',
			'Aeroplan (加拿大航空)',
		);
		$saved_airlines = (array) get_post_meta( $post->ID, 'transferable_airlines', true );
		foreach ( $airlines as $airline ) :
			?>
			<label>
				<input type="checkbox" name="hkcc_transferable_airlines[]" value="<?php echo esc_attr( $airline ); ?>"
					<?php checked( in_array( $airline, $saved_airlines, true ) ); ?> />
				<?php echo esc_html( $airline ); ?>
			</label>&nbsp;&nbsp;
		<?php endforeach; ?>

		<h4>Transferable Hotels</h4>
		<?php
		$hotels = array(
			'Marriott Bonvoy (萬豪)',
			'Hilton Honors (希爾頓)',
			'IHG Rewards (洲際酒店)',
		);
		$saved_hotels = (array) get_post_meta( $post->ID, 'transferable_hotels', true );
		foreach ( $hotels as $hotel ) :
			?>
			<label>
				<input type="checkbox" name="hkcc_transferable_hotels[]" value="<?php echo esc_attr( $hotel ); ?>"
					<?php checked( in_array( $hotel, $saved_hotels, true ) ); ?> />
				<?php echo esc_html( $hotel ); ?>
			</label>&nbsp;&nbsp;
		<?php endforeach;
	}

	/**
	 * Render the Featured parameters tab.
	 *
	 * @param WP_Post $post Current post.
	 */
	private static function render_featured_tab( $post ) {
		$options = HKCC_Card_Meta::get_featurable_fields();
		?>
		<p>Choose 4 parameters to feature on the collapsed card view:</p>
		<table class="form-table hkcc-fields">
		<?php for ( $i = 1; $i <= 4; $i++ ) :
			$key   = "featured_param_{$i}";
			$value = get_post_meta( $post->ID, $key, true );
			?>
			<tr>
				<th><label for="hkcc_<?php echo esc_attr( $key ); ?>">Slot <?php echo $i; ?></label></th>
				<td>
					<select id="hkcc_<?php echo esc_attr( $key ); ?>" name="hkcc_<?php echo esc_attr( $key ); ?>">
						<?php foreach ( $options as $opt_key => $opt_label ) : ?>
							<option value="<?php echo esc_attr( $opt_key ); ?>" <?php selected( $value, $opt_key ); ?>><?php echo esc_html( $opt_label ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
		<?php endfor; ?>
		</table>
		<?php
	}

	/**
	 * Show admin notices for cards with welcome offers expiring within 7 days.
	 */
	public static function expiry_warnings() {
		$screen = get_current_screen();
		if ( ! $screen || 'card' !== $screen->post_type ) {
			return;
		}

		global $wpdb;
		$expiring_soon = $wpdb->get_results(
			"SELECT p.ID, p.post_title, pm.meta_value AS expiry
			 FROM {$wpdb->posts} p
			 JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
			 WHERE p.post_type = 'card'
			   AND p.post_status = 'publish'
			   AND pm.meta_key = 'welcome_offer_expiry'
			   AND pm.meta_value BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)"
		);

		if ( ! $expiring_soon ) {
			return;
		}

		echo '<div class="notice notice-warning"><p><strong>迎新優惠即將到期：</strong></p><ul>';
		foreach ( $expiring_soon as $card ) {
			printf(
				'<li><a href="%s">%s</a> — 到期日: %s</li>',
				esc_url( get_edit_post_link( $card->ID ) ),
				esc_html( $card->post_title ),
				esc_html( $card->expiry )
			);
		}
		echo '</ul></div>';
	}
}
