<?php
/**
 * Points Systems admin submenu page (CRUD interface).
 *
 * @package HK_Card_Compare
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HKCC_Points_Admin {

	/**
	 * Hook into WordPress.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_form' ) );
	}

	/**
	 * Add the Points Systems submenu under Cards.
	 */
	public static function register_menu() {
		add_submenu_page(
			'edit.php?post_type=card',
			'Points Systems',
			'Points Systems',
			'edit_posts',
			'card-points-systems',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Handle form submissions (add / edit / delete systems, custom txn types).
	 */
	public static function handle_form() {
		if ( ! isset( $_POST['hkcc_points_action'] ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( 'Unauthorized' );
		}
		check_admin_referer( 'hkcc_points_manage' );

		$action = sanitize_text_field( $_POST['hkcc_points_action'] );

		if ( 'create' === $action || 'update' === $action ) {
			$name    = sanitize_text_field( $_POST['system_name'] ?? '' );
			$name_en = sanitize_text_field( $_POST['system_name_en'] ?? '' );

			if ( 'create' === $action ) {
				$system_id = HKCC_Points_System::create( $name, $name_en );
			} else {
				$system_id = absint( $_POST['system_id'] ?? 0 );
				HKCC_Points_System::update( $system_id, $name, $name_en );
			}

			// Save conversion rows.
			if ( $system_id && ! empty( $_POST['conv_reward_type'] ) ) {
				$conversions = array();
				$types   = (array) $_POST['conv_reward_type'];
				$points  = (array) $_POST['conv_points_required'];
				$values  = (array) $_POST['conv_reward_value'];
				$currencies = (array) $_POST['conv_reward_currency'];

				foreach ( $types as $i => $type ) {
					if ( empty( $type ) ) {
						continue;
					}
					$conversions[] = array(
						'reward_type'     => sanitize_text_field( $type ),
						'points_required' => absint( $points[ $i ] ?? 0 ),
						'reward_value'    => floatval( $values[ $i ] ?? 0 ),
						'reward_currency' => sanitize_text_field( $currencies[ $i ] ?? 'HKD' ),
					);
				}
				HKCC_Points_System::save_conversions( $system_id, $conversions );
			}

			wp_safe_redirect( admin_url( 'edit.php?post_type=card&page=card-points-systems&msg=saved' ) );
			exit;
		}

		if ( 'delete' === $action ) {
			$system_id = absint( $_POST['system_id'] ?? 0 );
			if ( $system_id ) {
				HKCC_Points_System::delete( $system_id );
			}
			wp_safe_redirect( admin_url( 'edit.php?post_type=card&page=card-points-systems&msg=deleted' ) );
			exit;
		}

		// Custom transaction type management.
		if ( 'add_custom_txn' === $action ) {
			$slug  = sanitize_key( $_POST['custom_txn_slug'] ?? '' );
			$label = sanitize_text_field( $_POST['custom_txn_label'] ?? '' );

			if ( $slug && $label ) {
				$custom = get_option( 'hkcc_custom_txn_types', array() );
				if ( ! is_array( $custom ) ) {
					$custom = array();
				}
				$custom[] = array( 'slug' => $slug, 'label' => $label );
				update_option( 'hkcc_custom_txn_types', $custom );
			}

			wp_safe_redirect( admin_url( 'edit.php?post_type=card&page=card-points-systems&msg=saved' ) );
			exit;
		}

		if ( 'delete_custom_txn' === $action ) {
			$index = intval( $_POST['custom_txn_index'] ?? -1 );
			$custom = get_option( 'hkcc_custom_txn_types', array() );

			if ( is_array( $custom ) && isset( $custom[ $index ] ) ) {
				array_splice( $custom, $index, 1 );
				update_option( 'hkcc_custom_txn_types', $custom );
			}

			wp_safe_redirect( admin_url( 'edit.php?post_type=card&page=card-points-systems&msg=deleted' ) );
			exit;
		}
	}

	/**
	 * Render the Points Systems admin page.
	 */
	public static function render_page() {
		$systems = HKCC_Points_System::get_all();
		$editing = null;
		$editing_conversions = array();

		if ( isset( $_GET['edit'] ) ) {
			$editing = HKCC_Points_System::get( absint( $_GET['edit'] ) );
			if ( $editing ) {
				$editing_conversions = HKCC_Points_System::get_conversions( $editing->id );
			}
		}

		?>
		<div class="wrap">
			<h1>Points Systems</h1>

			<?php if ( isset( $_GET['msg'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p>
					<?php echo 'deleted' === $_GET['msg'] ? 'System deleted.' : 'System saved.'; ?>
				</p></div>
			<?php endif; ?>

			<!-- Add / Edit form -->
			<h2><?php echo $editing ? 'Edit System' : 'Add New System'; ?></h2>
			<form method="post">
				<?php wp_nonce_field( 'hkcc_points_manage' ); ?>
				<input type="hidden" name="hkcc_points_action" value="<?php echo $editing ? 'update' : 'create'; ?>" />
				<?php if ( $editing ) : ?>
					<input type="hidden" name="system_id" value="<?php echo esc_attr( $editing->id ); ?>" />
				<?php endif; ?>

				<table class="form-table">
					<tr>
						<th><label for="system_name">System Name (Chinese)</label></th>
						<td><input type="text" id="system_name" name="system_name" value="<?php echo esc_attr( $editing->system_name ?? '' ); ?>" class="regular-text" required /></td>
					</tr>
					<tr>
						<th><label for="system_name_en">System Name (English)</label></th>
						<td><input type="text" id="system_name_en" name="system_name_en" value="<?php echo esc_attr( $editing->system_name_en ?? '' ); ?>" class="regular-text" /></td>
					</tr>
				</table>

				<h3>Conversion Rates</h3>
				<table class="widefat hkcc-conversions-table" id="hkcc-conversions">
					<thead>
						<tr>
							<th>Reward Type</th>
							<th>Points Required</th>
							<th>Reward Value</th>
							<th>Currency</th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<?php
						$rows = ! empty( $editing_conversions ) ? $editing_conversions : array( (object) array(
							'reward_type'     => '',
							'points_required' => '',
							'reward_value'    => '',
							'reward_currency' => 'HKD',
						) );
						foreach ( $rows as $idx => $row ) :
							?>
							<tr>
								<td>
									<select name="conv_reward_type[]">
										<option value="">— Select —</option>
										<?php
										$reward_options = array(
											'cash'                 => 'Cash (現金)',
											'asia_miles'           => 'Asia Miles (亞洲萬里通)',
											'avios'                => 'Avios (英國航空)',
											'emirates_skywards'    => 'Emirates Skywards (阿聯酋航空)',
											'etihad_guest'         => 'Etihad Guest (阿提哈德航空)',
											'flying_blue'          => 'Flying Blue (法荷航)',
											'krisflyer'            => 'KrisFlyer (新加坡航空)',
											'qantas_ff'            => 'Qantas Frequent Flyer (澳洲航空)',
											'virgin_fc'            => 'Virgin Atlantic Flying Club (維珍航空)',
											'finnair_plus'         => 'Finnair Plus (芬蘭航空)',
											'enrich'               => 'Enrich (馬來西亞航空)',
											'infinity_mileagelands'=> 'Infinity MileageLands (長榮航空)',
											'royal_orchid_plus'    => 'Royal Orchid Plus (泰國航空)',
											'qatar_privilege'      => 'Qatar Privilege Club (卡塔爾航空)',
											'phoenix_miles'        => '鳳凰知音 (中國國航)',
											'aeroplan'             => 'Aeroplan (加拿大航空)',
											'marriott_bonvoy'      => 'Marriott Bonvoy (萬豪)',
											'hilton_honors'        => 'Hilton Honors (希爾頓)',
											'ihg_rewards'          => 'IHG Rewards (洲際酒店)',
										);
										foreach ( $reward_options as $rk => $rl ) :
											?>
											<option value="<?php echo esc_attr( $rk ); ?>" <?php selected( $row->reward_type, $rk ); ?>><?php echo esc_html( $rl ); ?></option>
										<?php endforeach; ?>
									</select>
								</td>
								<td><input type="number" name="conv_points_required[]" value="<?php echo esc_attr( $row->points_required ); ?>" class="small-text" /></td>
								<td><input type="number" step="0.01" name="conv_reward_value[]" value="<?php echo esc_attr( $row->reward_value ); ?>" class="small-text" /></td>
								<td><input type="text" name="conv_reward_currency[]" value="<?php echo esc_attr( $row->reward_currency ); ?>" class="small-text" /></td>
								<td><button type="button" class="button hkcc-remove-row">&times;</button></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<p><button type="button" class="button" id="hkcc-add-conversion">+ Add Conversion</button></p>

				<p class="submit">
					<input type="submit" class="button-primary" value="<?php echo $editing ? 'Update System' : 'Save System'; ?>" />
					<?php if ( $editing ) : ?>
						<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=card&page=card-points-systems' ) ); ?>" class="button">Cancel</a>
					<?php endif; ?>
				</p>
			</form>

			<hr />

			<!-- Existing systems list -->
			<h2>Existing Systems</h2>
			<?php if ( empty( $systems ) ) : ?>
				<p>No points systems found.</p>
			<?php else : ?>
				<table class="widefat striped">
					<thead>
						<tr>
							<th>Name</th>
							<th>English Name</th>
							<th>Conversions</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $systems as $sys ) :
							$convs = HKCC_Points_System::get_conversions( $sys->id );
							?>
							<tr>
								<td><?php echo esc_html( $sys->system_name ); ?></td>
								<td><?php echo esc_html( $sys->system_name_en ); ?></td>
								<td>
									<?php foreach ( $convs as $c ) :
										printf(
											'%s: %s pts = %s %s<br>',
											esc_html( $c->reward_type ),
											esc_html( number_format( $c->points_required ) ),
											esc_html( number_format( $c->reward_value, 2 ) ),
											esc_html( $c->reward_currency )
										);
									endforeach; ?>
								</td>
								<td>
									<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=card&page=card-points-systems&edit=' . $sys->id ) ); ?>" class="button button-small">Edit</a>
									<form method="post" style="display:inline;">
										<?php wp_nonce_field( 'hkcc_points_manage' ); ?>
										<input type="hidden" name="hkcc_points_action" value="delete" />
										<input type="hidden" name="system_id" value="<?php echo esc_attr( $sys->id ); ?>" />
										<button type="submit" class="button button-small button-link-delete" onclick="return confirm('Delete this system and all its conversions?');">&times;</button>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<hr />

			<!-- Custom Transaction Types -->
			<h2>Custom Transaction Types</h2>
			<p>Add custom reward categories that will appear in card reward fields globally.</p>

			<?php
			$custom_types = get_option( 'hkcc_custom_txn_types', array() );
			if ( ! empty( $custom_types ) && is_array( $custom_types ) ) : ?>
				<table class="widefat striped hkcc-custom-txn-table">
					<thead>
						<tr><th>Slug</th><th>Label</th><th></th></tr>
					</thead>
					<tbody>
						<?php foreach ( $custom_types as $i => $type ) : ?>
							<tr>
								<td><code><?php echo esc_html( $type['slug'] ); ?></code></td>
								<td><?php echo esc_html( $type['label'] ); ?></td>
								<td>
									<form method="post" style="display:inline;">
										<?php wp_nonce_field( 'hkcc_points_manage' ); ?>
										<input type="hidden" name="hkcc_points_action" value="delete_custom_txn" />
										<input type="hidden" name="custom_txn_index" value="<?php echo esc_attr( $i ); ?>" />
										<button type="submit" class="button button-small button-link-delete" onclick="return confirm('Delete this custom type?');">&times;</button>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<form method="post">
				<?php wp_nonce_field( 'hkcc_points_manage' ); ?>
				<input type="hidden" name="hkcc_points_action" value="add_custom_txn" />
				<table class="form-table">
					<tr>
						<th><label for="custom_txn_slug">Slug (English, lowercase)</label></th>
						<td><input type="text" id="custom_txn_slug" name="custom_txn_slug" class="regular-text" pattern="[a-z_]+" required /></td>
					</tr>
					<tr>
						<th><label for="custom_txn_label">Label (Chinese)</label></th>
						<td><input type="text" id="custom_txn_label" name="custom_txn_label" class="regular-text" required /></td>
					</tr>
				</table>
				<p class="submit"><input type="submit" class="button-secondary" value="Add Custom Type" /></p>
			</form>
		</div>
		<?php
	}
}
