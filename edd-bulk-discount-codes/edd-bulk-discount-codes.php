<?php
/**
 * Plugin Name:       EDD Bulk Discount Codes
 * Plugin URI:        https://github.com/solutions-io/edd-bulk-discount-codes
 * Description:       Generate Easy Digital Downloads discount codes in bulk from an admin page.
 * Version:           1.1.0
 * Author:            solutions.io
 * Author URI:        https://solutions.io
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       edd-bulk-codes
 * Domain Path:       /languages
 * Requires Plugins:  easy-digital-downloads
 * Requires at least: 6.0
 * Requires PHP:      7.4
 */

namespace SolutionsIO\EDDBulkCodes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const TRANSIENT_PREFIX = 'edd_bulk_codes_last_batch_';

add_action( 'admin_menu', __NAMESPACE__ . '\\register_menu' );
add_action( 'admin_post_edd_bulk_codes_csv', __NAMESPACE__ . '\\handle_csv_download' );

/**
 * Register the admin submenu page under the EDD Downloads menu.
 */
function register_menu() {
	add_submenu_page(
		'edit.php?post_type=download',
		esc_html__( 'Bulk Discount Codes', 'edd-bulk-codes' ),
		esc_html__( 'Bulk Discount Codes', 'edd-bulk-codes' ),
		'manage_options',
		'edd-bulk-codes',
		__NAMESPACE__ . '\\render_page'
	);
}

/**
 * Default form values.
 *
 * @return array<string, mixed>
 */
function get_form_defaults() {
	return [
		'campaign_name' => '',
		'count'         => 100,
		'prefix'        => '',
		'code_length'   => 10,
		'type'          => 'percent',
		'amount'        => 100,
		'max_uses'      => 1,
		'single_use'    => true,
		'expiration'    => '',
	];
}

/**
 * Parse and sanitize submitted form input.
 *
 * @param array<string, mixed> $input Raw $_POST data.
 * @return array<string, mixed>
 */
function parse_form_input( array $input ) {
	return [
		'campaign_name' => sanitize_text_field( wp_unslash( $input['campaign_name'] ?? '' ) ),
		'count'         => max( 1, min( 1000, intval( $input['count'] ?? 0 ) ) ),
		'prefix'        => strtoupper( sanitize_text_field( wp_unslash( $input['prefix'] ?? '' ) ) ),
		'code_length'   => max( 4, min( 32, intval( $input['code_length'] ?? 10 ) ) ),
		'type'          => ( ( $input['type'] ?? 'percent' ) === 'flat' ) ? 'flat' : 'percent',
		'amount'        => floatval( $input['amount'] ?? 0 ),
		'max_uses'      => max( 0, intval( $input['max_uses'] ?? 1 ) ),
		'single_use'    => ! empty( $input['single_use'] ),
		'expiration'    => sanitize_text_field( wp_unslash( $input['expiration'] ?? '' ) ),
	];
}

/**
 * Validate parsed form input.
 *
 * @param array<string, mixed> $data Parsed form data.
 * @return string[] Error messages (empty if valid).
 */
function validate( array $data ) {
	$errors = [];

	if ( $data['campaign_name'] === '' ) {
		$errors[] = esc_html__( 'Campaign name is required.', 'edd-bulk-codes' );
	}
	if ( $data['amount'] <= 0 ) {
		$errors[] = esc_html__( 'Amount must be greater than zero.', 'edd-bulk-codes' );
	}

	return $errors;
}

/**
 * Generate discount codes via edd_store_discount().
 *
 * @param array<string, mixed> $data Validated form data.
 * @return array{generated: string[], skipped: int}
 */
function generate_codes( array $data ) {
	$generated = [];
	$skipped   = 0;

	for ( $i = 0; $i < $data['count']; $i++ ) {
		$code = $data['prefix'] . strtoupper( wp_generate_password( $data['code_length'], false, false ) );

		$id = edd_store_discount( [
			'name'          => $data['campaign_name'],
			'code'          => $code,
			'type'          => $data['type'],
			'amount'        => $data['amount'],
			'max'           => $data['max_uses'],
			'is_single_use' => $data['single_use'],
			'expiration'    => $data['expiration'],
			'product_reqs'  => [],
		] );

		if ( $id ) {
			$generated[] = $code;
		} else {
			$skipped++;
		}
	}

	return [
		'generated' => $generated,
		'skipped'   => $skipped,
	];
}

/**
 * Render the admin page.
 */
function render_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	nocache_headers();

	if ( ! function_exists( 'edd_store_discount' ) ) {
		echo '<div class="wrap"><h1>' . esc_html__( 'Bulk Discount Codes', 'edd-bulk-codes' ) . '</h1>';
		echo '<div class="notice notice-error"><p>' . esc_html__( 'Easy Digital Downloads must be installed and active.', 'edd-bulk-codes' ) . '</p></div></div>';
		return;
	}

	$form      = get_form_defaults();
	$generated = [];
	$skipped   = 0;
	$errors    = [];

	$nonce = isset( $_POST['edd_bulk_codes_nonce'] )
		? sanitize_text_field( wp_unslash( $_POST['edd_bulk_codes_nonce'] ) )
		: '';

	if ( $nonce && wp_verify_nonce( $nonce, 'edd_bulk_codes_generate' ) ) {
		$form   = parse_form_input( $_POST );
		$errors = validate( $form );

		if ( empty( $errors ) ) {
			$result    = generate_codes( $form );
			$generated = $result['generated'];
			$skipped   = $result['skipped'];

			if ( ! empty( $generated ) ) {
				set_transient(
					TRANSIENT_PREFIX . get_current_user_id(),
					[
						'codes'    => $generated,
						'campaign' => $form['campaign_name'],
					],
					HOUR_IN_SECONDS
				);
			}
		}
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Bulk Discount Codes', 'edd-bulk-codes' ); ?></h1>
		<p><?php esc_html_e( 'Generate Easy Digital Downloads discount codes in bulk.', 'edd-bulk-codes' ); ?></p>

		<?php foreach ( $errors as $err ) : ?>
			<div class="notice notice-error"><p><?php echo esc_html( $err ); ?></p></div>
		<?php endforeach; ?>

		<?php if ( ! empty( $generated ) ) : ?>
			<div class="notice notice-success">
				<p>
					<?php
					printf(
						/* translators: %d: number of codes generated */
						esc_html( _n( '%d code generated.', '%d codes generated.', count( $generated ), 'edd-bulk-codes' ) ),
						(int) count( $generated )
					);
					?>
					<?php if ( $skipped > 0 ) : ?>
						<em>
							<?php
							printf(
								/* translators: %d: number of codes skipped due to duplicate collisions */
								esc_html( _n( '(%d skipped — likely duplicate collision.)', '(%d skipped — likely duplicate collisions.)', $skipped, 'edd-bulk-codes' ) ),
								(int) $skipped
							);
							?>
						</em>
					<?php endif; ?>
				</p>
			</div>

			<h2><?php esc_html_e( 'Generated codes', 'edd-bulk-codes' ); ?></h2>
			<textarea readonly rows="15" style="width:100%;font-family:monospace;font-size:13px;" onclick="this.select()"><?php echo esc_textarea( implode( "\n", $generated ) ); ?></textarea>
			<p><em><?php esc_html_e( 'Click the textarea to select all, then copy.', 'edd-bulk-codes' ); ?></em></p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:1em;">
				<input type="hidden" name="action" value="edd_bulk_codes_csv">
				<?php wp_nonce_field( 'edd_bulk_codes_csv' ); ?>
				<?php submit_button( esc_html__( 'Download as CSV', 'edd-bulk-codes' ), 'secondary', 'submit', false ); ?>
			</form>
			<hr>
		<?php endif; ?>

		<form method="post">
			<?php wp_nonce_field( 'edd_bulk_codes_generate', 'edd_bulk_codes_nonce' ); ?>
			<table class="form-table">
				<tr>
					<th><label for="campaign_name"><?php esc_html_e( 'Campaign name', 'edd-bulk-codes' ); ?></label></th>
					<td>
						<input type="text" id="campaign_name" name="campaign_name" class="regular-text" value="<?php echo esc_attr( $form['campaign_name'] ); ?>" required>
						<p class="description"><?php esc_html_e( 'Internal label shown in the EDD discounts list.', 'edd-bulk-codes' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="count"><?php esc_html_e( 'Number of codes', 'edd-bulk-codes' ); ?></label></th>
					<td><input type="number" id="count" name="count" value="<?php echo esc_attr( $form['count'] ); ?>" min="1" max="1000"></td>
				</tr>
				<tr>
					<th><label for="type"><?php esc_html_e( 'Discount type', 'edd-bulk-codes' ); ?></label></th>
					<td>
						<select id="type" name="type">
							<option value="percent" <?php selected( $form['type'], 'percent' ); ?>><?php esc_html_e( 'Percent (%)', 'edd-bulk-codes' ); ?></option>
							<option value="flat" <?php selected( $form['type'], 'flat' ); ?>><?php esc_html_e( 'Flat amount', 'edd-bulk-codes' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="amount"><?php esc_html_e( 'Amount', 'edd-bulk-codes' ); ?></label></th>
					<td><input type="number" id="amount" name="amount" value="<?php echo esc_attr( $form['amount'] ); ?>" step="0.01" min="0"></td>
				</tr>
				<tr>
					<th><label for="prefix"><?php esc_html_e( 'Code prefix (optional)', 'edd-bulk-codes' ); ?></label></th>
					<td>
						<input type="text" id="prefix" name="prefix" value="<?php echo esc_attr( $form['prefix'] ); ?>" class="regular-text" placeholder="e.g. SUMMER-">
						<p class="description"><?php esc_html_e( 'Useful for filtering this batch later.', 'edd-bulk-codes' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="code_length"><?php esc_html_e( 'Random code length', 'edd-bulk-codes' ); ?></label></th>
					<td><input type="number" id="code_length" name="code_length" value="<?php echo esc_attr( $form['code_length'] ); ?>" min="4" max="32"></td>
				</tr>
				<tr>
					<th><label for="max_uses"><?php esc_html_e( 'Max uses per code', 'edd-bulk-codes' ); ?></label></th>
					<td>
						<input type="number" id="max_uses" name="max_uses" value="<?php echo esc_attr( $form['max_uses'] ); ?>" min="0">
						<span class="description"><?php esc_html_e( '0 = unlimited', 'edd-bulk-codes' ); ?></span>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Single use per customer', 'edd-bulk-codes' ); ?></th>
					<td><label><input type="checkbox" name="single_use" value="1" <?php checked( $form['single_use'] ); ?>> <?php esc_html_e( 'Yes', 'edd-bulk-codes' ); ?></label></td>
				</tr>
				<tr>
					<th><label for="expiration"><?php esc_html_e( 'Expiration (optional)', 'edd-bulk-codes' ); ?></label></th>
					<td>
						<input type="text" id="expiration" name="expiration" value="<?php echo esc_attr( $form['expiration'] ); ?>" placeholder="YYYY-MM-DD HH:MM:SS" class="regular-text">
						<p class="description"><?php esc_html_e( 'Leave blank for no expiration.', 'edd-bulk-codes' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button( esc_html__( 'Generate Codes', 'edd-bulk-codes' ) ); ?>
		</form>
	</div>
	<?php
}

/**
 * Handle CSV download of the most recently generated batch (per user).
 */
function handle_csv_download() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to do this.', 'edd-bulk-codes' ), '', [ 'response' => 403 ] );
	}

	check_admin_referer( 'edd_bulk_codes_csv' );

	$batch = get_transient( TRANSIENT_PREFIX . get_current_user_id() );

	if ( empty( $batch['codes'] ) ) {
		wp_die( esc_html__( 'No recently generated codes are available for download.', 'edd-bulk-codes' ), '', [ 'response' => 404 ] );
	}

	$campaign = $batch['campaign'] ?? 'batch';
	$filename = 'edd-discount-codes-' . sanitize_title( $campaign ) . '-' . gmdate( 'Y-m-d-His' ) . '.csv';

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

	$out = fopen( 'php://output', 'w' );
	fputcsv( $out, [ 'code', 'campaign' ] );
	foreach ( $batch['codes'] as $code ) {
		fputcsv( $out, [ $code, $campaign ] );
	}
	fclose( $out );
	exit;
}
