<?php
/**
 * Plugin Name: Lead Recorder
 * Plugin URI: https://www.leadrecorder.com/docs/wordpress-manual
 * Description: Adds your Lead Recorder tracking snippet to the &lt;head&gt; of every page. Paste the snippet from your Lead Recorder dashboard and save.
 * Version: 1.0.1
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Lead Recorder
 * Author URI: https://www.leadrecorder.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: lead-recorder
 */

// Block direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LEAD_RECORDER_OPTION', 'lead_recorder_snippet_key' );
define( 'LEAD_RECORDER_SCRIPT_HOST', 'www.leadrecorder.com' );
define( 'LEAD_RECORDER_VERSION', '1.0.1' );

/**
 * Pull a snippet key out of whatever the user pasted.
 *
 * Accepts either a full <script src="https://www.leadrecorder.com/api/script/KEY" ...>
 * tag or a bare key, and always returns just the key (or an empty string).
 * We never store or echo the pasted markup itself — only this extracted,
 * validated key — so arbitrary HTML/JS can never reach the page <head>.
 */
function lead_recorder_extract_key( $raw ) {
	$raw = trim( (string) $raw );

	if ( '' === $raw ) {
		return '';
	}

	if ( preg_match( '#api/script/([A-Za-z0-9_-]{6,128})#', $raw, $matches ) ) {
		return $matches[1];
	}

	if ( preg_match( '/^[A-Za-z0-9_-]{6,128}$/', $raw ) ) {
		return $raw;
	}

	return '';
}

/**
 * Sanitize + validate the setting before it's saved.
 */
function lead_recorder_sanitize_key( $raw ) {
	$key = lead_recorder_extract_key( $raw );

	if ( '' === trim( (string) $raw ) ) {
		// Field cleared on purpose — allow it.
		return '';
	}

	if ( '' === $key ) {
		add_settings_error(
			LEAD_RECORDER_OPTION,
			'lead_recorder_invalid_snippet',
			__( "That doesn't look like a Lead Recorder snippet. Paste the full <script> tag from your dashboard, or just the key.", 'lead-recorder' )
		);
		return get_option( LEAD_RECORDER_OPTION, '' );
	}

	return $key;
}

/**
 * Settings page under Settings > Lead Recorder.
 */
function lead_recorder_register_settings() {
	register_setting(
		'lead_recorder_settings',
		LEAD_RECORDER_OPTION,
		array(
			'type'              => 'string',
			'sanitize_callback' => 'lead_recorder_sanitize_key',
			'default'           => '',
		)
	);
}
add_action( 'admin_init', 'lead_recorder_register_settings' );

function lead_recorder_add_settings_page() {
	add_options_page(
		__( 'Lead Recorder', 'lead-recorder' ),
		__( 'Lead Recorder', 'lead-recorder' ),
		'manage_options',
		'lead-recorder',
		'lead_recorder_render_settings_page'
	);
}
add_action( 'admin_menu', 'lead_recorder_add_settings_page' );

function lead_recorder_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$key = get_option( LEAD_RECORDER_OPTION, '' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Lead Recorder', 'lead-recorder' ); ?></h1>

		<?php settings_errors( LEAD_RECORDER_OPTION ); ?>

		<p>
			<?php esc_html_e( 'Paste the tracking snippet from your Lead Recorder dashboard below. We\'ll add it to the ', 'lead-recorder' ); ?><code>&lt;head&gt;</code><?php esc_html_e( ' of every page on this site.', 'lead-recorder' ); ?>
		</p>

		<?php if ( '' !== $key ) : ?>
			<p style="color:#008a20;font-weight:600;">
				&#10003; <?php esc_html_e( 'Installed — the snippet is being added to your site.', 'lead-recorder' ); ?>
			</p>
		<?php endif; ?>

		<form action="options.php" method="post">
			<?php settings_fields( 'lead_recorder_settings' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="lead_recorder_snippet"><?php esc_html_e( 'Tracking snippet', 'lead-recorder' ); ?></label>
					</th>
					<td>
						<textarea
							id="lead_recorder_snippet"
							name="<?php echo esc_attr( LEAD_RECORDER_OPTION ); ?>"
							rows="3"
							class="large-text code"
							placeholder="<?php echo esc_attr( '<script src="https://www.leadrecorder.com/api/script/YOUR_KEY" defer></script>' ); ?>"
						><?php echo esc_textarea( $key ); ?></textarea>
						<p class="description">
							<?php esc_html_e( 'Paste the whole snippet, or just the key — either works.', 'lead-recorder' ); ?>
						</p>
					</td>
				</tr>
			</table>
			<?php submit_button( __( 'Save snippet', 'lead-recorder' ) ); ?>
		</form>
	</div>
	<?php
}

/**
 * Enqueue the tracking script in <head>.
 *
 * We rebuild the script src ourselves from the stored, validated key —
 * we never echo raw user input here.
 */
function lead_recorder_enqueue_script() {
	$key = get_option( LEAD_RECORDER_OPTION, '' );

	if ( '' === $key || ! preg_match( '/^[A-Za-z0-9_-]{6,128}$/', $key ) ) {
		return;
	}

	$src = sprintf( 'https://%s/api/script/%s', LEAD_RECORDER_SCRIPT_HOST, $key );

	wp_enqueue_script(
		'lead-recorder',
		$src,
		array(),
		LEAD_RECORDER_VERSION,
		array(
			'strategy'  => 'defer',
			'in_footer' => false,
		)
	);
}
add_action( 'wp_enqueue_scripts', 'lead_recorder_enqueue_script' );

/**
 * Remove our data if the plugin is deleted from the Plugins screen.
 */
function lead_recorder_uninstall() {
	delete_option( LEAD_RECORDER_OPTION );
}
register_uninstall_hook( __FILE__, 'lead_recorder_uninstall' );
