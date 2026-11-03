<?php
/**
 * Plugin Name: Lead Recorder
 * Plugin URI: https://www.leadrecorder.com/docs/wordpress-manual
 * Description: Adds your Lead Recorder tracking snippet to the &lt;head&gt; of every page, and records paid WooCommerce orders with the source that drove them. Paste the snippet from your Lead Recorder dashboard and save.
 * Version: 1.1.0
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
define( 'LEAD_RECORDER_STORE_KEY_OPTION', 'lead_recorder_store_key' );
define( 'LEAD_RECORDER_VERSION', '1.1.0' );

// Overridable from wp-config.php so a test store can point at a preview
// deployment. The tracking script follows it too, so the visits and the
// orders land in the same place.
if ( ! defined( 'LEAD_RECORDER_API_HOST' ) ) {
	define( 'LEAD_RECORDER_API_HOST', 'www.leadrecorder.com' );
}
define( 'LEAD_RECORDER_SCRIPT_HOST', LEAD_RECORDER_API_HOST );

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
 * The store key the dashboard generated: lrsk_ + 32 random bytes, base64url.
 */
function lead_recorder_is_valid_store_key( $key ) {
	return is_string( $key ) && 1 === preg_match( '/^lrsk_[A-Za-z0-9_-]{43}$/', $key );
}

/**
 * Sanitize the store key field. The form posts an array (`key`, `remove`)
 * so the saved key never has to be echoed back into the page: an empty
 * `key` keeps the current one.
 *
 * WordPress also runs this on plain strings (add_option() and the second
 * pass update_option() makes when the option is new), and with null when
 * the field wasn't on the form at all, e.g. WooCommerce is inactive.
 */
function lead_recorder_sanitize_store_key( $input ) {
	$current = get_option( LEAD_RECORDER_STORE_KEY_OPTION, '' );

	if ( ! is_array( $input ) ) {
		if ( is_string( $input ) && ( '' === $input || lead_recorder_is_valid_store_key( $input ) ) ) {
			return $input;
		}
		return $current;
	}

	if ( ! empty( $input['remove'] ) ) {
		return '';
	}

	$key = isset( $input['key'] ) ? trim( sanitize_text_field( (string) $input['key'] ) ) : '';

	if ( '' === $key ) {
		return $current;
	}

	if ( ! lead_recorder_is_valid_store_key( $key ) ) {
		add_settings_error(
			LEAD_RECORDER_STORE_KEY_OPTION,
			'lead_recorder_invalid_store_key',
			__( "That doesn't look like a Lead Recorder store key. It starts with lrsk_ and is shown once when you generate it in your dashboard.", 'lead-recorder' )
		);
		return $current;
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

	register_setting(
		'lead_recorder_settings',
		LEAD_RECORDER_STORE_KEY_OPTION,
		array(
			'type'              => 'string',
			'sanitize_callback' => 'lead_recorder_sanitize_store_key',
			'default'           => '',
		)
	);

	// The store key is only read when an order is sent, so keep it out of
	// the autoloaded options every page request loads. update_option() on a
	// missing option would create it autoloaded.
	if ( false === get_option( LEAD_RECORDER_STORE_KEY_OPTION, false ) ) {
		add_option( LEAD_RECORDER_STORE_KEY_OPTION, '', '', 'no' );
	}
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

	$key       = get_option( LEAD_RECORDER_OPTION, '' );
	$has_woo   = class_exists( 'WooCommerce' );
	$store_key = $has_woo ? get_option( LEAD_RECORDER_STORE_KEY_OPTION, '' ) : '';
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Lead Recorder', 'lead-recorder' ); ?></h1>

		<?php settings_errors( LEAD_RECORDER_OPTION ); ?>
		<?php settings_errors( LEAD_RECORDER_STORE_KEY_OPTION ); ?>

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
							placeholder="<?php echo esc_attr__( 'YOUR_KEY — or paste the full snippet, either works', 'lead-recorder' ); ?>"
						><?php echo esc_textarea( $key ); ?></textarea>
						<p class="description">
							<?php esc_html_e( 'Paste the whole snippet, or just the key — either works.', 'lead-recorder' ); ?>
						</p>
					</td>
				</tr>
				<?php if ( $has_woo ) : ?>
					<tr>
						<th scope="row">
							<label for="lead_recorder_store_key"><?php esc_html_e( 'Store key', 'lead-recorder' ); ?></label>
						</th>
						<td>
							<?php // Never echo the saved key back; the input always starts empty. ?>
							<input
								type="password"
								id="lead_recorder_store_key"
								name="<?php echo esc_attr( LEAD_RECORDER_STORE_KEY_OPTION ); ?>[key]"
								value=""
								class="regular-text code"
								autocomplete="off"
								spellcheck="false"
								placeholder="lrsk_…"
							/>
							<?php if ( lead_recorder_is_valid_store_key( $store_key ) ) : ?>
								<p style="color:#008a20;font-weight:600;">
									&#10003;
									<?php
									/* translators: %s: last four characters of the saved store key. */
									echo esc_html( sprintf( __( 'Saved (ending …%s). Paid orders are being sent to Lead Recorder.', 'lead-recorder' ), substr( $store_key, -4 ) ) );
									?>
								</p>
								<p>
									<label>
										<input type="checkbox" name="<?php echo esc_attr( LEAD_RECORDER_STORE_KEY_OPTION ); ?>[remove]" value="1" />
										<?php esc_html_e( 'Remove key', 'lead-recorder' ); ?>
									</label>
								</p>
							<?php endif; ?>
							<p class="description">
								<?php esc_html_e( 'From your Lead Recorder dashboard: Integrations > WooCommerce > Generate store key.', 'lead-recorder' ); ?>
								<?php if ( '' !== $store_key ) : ?>
									<?php esc_html_e( 'Leave blank to keep the current key.', 'lead-recorder' ); ?>
								<?php endif; ?>
							</p>
						</td>
					</tr>
				<?php endif; ?>
			</table>
			<?php submit_button( $has_woo ? __( 'Save settings', 'lead-recorder' ) : __( 'Save snippet', 'lead-recorder' ) ); ?>
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
	delete_option( LEAD_RECORDER_STORE_KEY_OPTION );
}
register_uninstall_hook( __FILE__, 'lead_recorder_uninstall' );

/* ─── WooCommerce purchase tracking ─────────────────────────────────────
 *
 * At checkout, a small script copies the visitor's Lead Recorder session
 * and visitor keys (from window.LeadRecorder.cartAttributes()) into the
 * order. Once the order is paid, this server sends its real total to Lead
 * Recorder, signed with the per-site store key. The snippet key is public
 * (it's in every page's HTML), so it can't authenticate an order on its
 * own; the store key is what does. No customer details are ever sent.
 */

define( 'LEAD_RECORDER_SEND_HOOK', 'lead_recorder_send_purchase' );
define( 'LEAD_RECORDER_AS_GROUP', 'lead-recorder' );

/**
 * WooCommerce is active and both keys are saved. Nothing in this section
 * runs otherwise.
 */
function lead_recorder_woo_ready() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return false;
	}

	$snippet_key = get_option( LEAD_RECORDER_OPTION, '' );

	return '' !== $snippet_key
		&& preg_match( '/^[A-Za-z0-9_-]{6,128}$/', $snippet_key )
		&& lead_recorder_is_valid_store_key( get_option( LEAD_RECORDER_STORE_KEY_OPTION, '' ) );
}

/**
 * HPOS and the block checkout are both supported: orders are only touched
 * through $order methods, never post meta functions.
 */
function lead_recorder_declare_woo_compatibility() {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
	}
}
add_action( 'before_woocommerce_init', 'lead_recorder_declare_woo_compatibility' );

/**
 * A session or visitor key as the tracker makes them, or '' for anything
 * else. These come from the browser, so they're never trusted further.
 */
function lead_recorder_clean_visit_key( $raw ) {
	if ( ! is_string( $raw ) ) {
		return '';
	}

	$value = sanitize_text_field( $raw );

	return preg_match( '/^[A-Za-z0-9_-]{1,64}$/', $value ) ? $value : '';
}

function lead_recorder_save_visit_keys( $order, $sid, $vid ) {
	$sid = lead_recorder_clean_visit_key( $sid );
	$vid = lead_recorder_clean_visit_key( $vid );

	if ( '' !== $sid ) {
		$order->update_meta_data( '_lr_sid', $sid );
	}
	if ( '' !== $vid ) {
		$order->update_meta_data( '_lr_vid', $vid );
	}
}

/**
 * Classic checkout: hidden inputs inside the checkout form, filled by the
 * script below. The script also adds them itself if a theme's checkout
 * template doesn't fire this hook.
 */
function lead_recorder_checkout_fields() {
	if ( ! lead_recorder_woo_ready() ) {
		return;
	}

	echo '<input type="hidden" name="lead_recorder_sid" id="lead_recorder_sid" value="" />' . "\n";
	echo '<input type="hidden" name="lead_recorder_vid" id="lead_recorder_vid" value="" />' . "\n";
}
add_action( 'woocommerce_after_order_notes', 'lead_recorder_checkout_fields' );

/**
 * Classic checkout: copy the posted keys onto the order before it's saved.
 */
function lead_recorder_classic_checkout_create_order( $order, $data ) {
	if ( ! lead_recorder_woo_ready() ) {
		return;
	}

	// phpcs:disable WordPress.Security.NonceVerification.Missing -- WooCommerce verified its checkout nonce before this hook.
	$sid = isset( $_POST['lead_recorder_sid'] ) ? wp_unslash( $_POST['lead_recorder_sid'] ) : '';  // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized in lead_recorder_clean_visit_key().
	$vid = isset( $_POST['lead_recorder_vid'] ) ? wp_unslash( $_POST['lead_recorder_vid'] ) : '';  // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized in lead_recorder_clean_visit_key().
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	lead_recorder_save_visit_keys( $order, $sid, $vid );
}
add_action( 'woocommerce_checkout_create_order', 'lead_recorder_classic_checkout_create_order', 10, 2 );

/**
 * Block checkout: accept `extensions.lead-recorder.{sid,vid}` on the Store
 * API checkout request. Both are plain nullable strings with no pattern:
 * a schema failure would reject the checkout, and a missing or odd key
 * must never stop someone paying. They're validated when saved instead.
 */
function lead_recorder_register_store_api_data() {
	if (
		! lead_recorder_woo_ready()
		|| ! function_exists( 'woocommerce_store_api_register_endpoint_data' )
		|| ! class_exists( '\Automattic\WooCommerce\StoreApi\Schemas\V1\CheckoutSchema' )
	) {
		return;
	}

	woocommerce_store_api_register_endpoint_data(
		array(
			'endpoint'        => \Automattic\WooCommerce\StoreApi\Schemas\V1\CheckoutSchema::IDENTIFIER,
			'namespace'       => 'lead-recorder',
			'schema_callback' => 'lead_recorder_store_api_schema',
			'schema_type'     => ARRAY_A,
		)
	);
}
add_action( 'woocommerce_blocks_loaded', 'lead_recorder_register_store_api_data' );

function lead_recorder_store_api_schema() {
	return array(
		'sid' => array(
			'description' => __( 'Lead Recorder session key.', 'lead-recorder' ),
			'type'        => array( 'string', 'null' ),
			'context'     => array(),
		),
		'vid' => array(
			'description' => __( 'Lead Recorder visitor key.', 'lead-recorder' ),
			'type'        => array( 'string', 'null' ),
			'context'     => array(),
		),
	);
}

/**
 * Block checkout: copy the keys onto the order. WooCommerce saves the order
 * straight after this hook.
 */
function lead_recorder_store_api_update_order( $order, $request ) {
	if ( ! lead_recorder_woo_ready() ) {
		return;
	}

	$extensions = $request['extensions'];
	$data       = is_array( $extensions ) && isset( $extensions['lead-recorder'] ) && is_array( $extensions['lead-recorder'] )
		? $extensions['lead-recorder']
		: array();

	lead_recorder_save_visit_keys(
		$order,
		isset( $data['sid'] ) ? $data['sid'] : '',
		isset( $data['vid'] ) ? $data['vid'] : ''
	);
}
add_action( 'woocommerce_store_api_checkout_update_order_from_request', 'lead_recorder_store_api_update_order', 10, 2 );

/**
 * The checkout-page script for both checkouts. It reads the keys from the
 * tracker, which loads with `defer` and can be blocked entirely, so every
 * step is optional: if the keys never turn up, the order is still placed
 * and simply shows as Unattributed. It never throws and never returns
 * false from checkout_place_order, which would stop the order.
 *
 * It's its own handle rather than inline on the tracker's handle: an
 * inline "after" script turns a deferred script back into a blocking one.
 */
function lead_recorder_enqueue_checkout_script() {
	if ( ! lead_recorder_woo_ready() || ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
		return;
	}
	if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' ) ) {
		return;
	}

	wp_register_script( 'lead-recorder-checkout', false, array(), LEAD_RECORDER_VERSION, true );
	wp_enqueue_script( 'lead-recorder-checkout' );
	wp_add_inline_script( 'lead-recorder-checkout', lead_recorder_checkout_script() );
}
add_action( 'wp_enqueue_scripts', 'lead_recorder_enqueue_checkout_script' );

function lead_recorder_checkout_script() {
	return <<<'JS'
(function () {
	function keys() {
		var out = { sid: null, vid: null };
		try {
			var lr = window.LeadRecorder;
			var attrs = lr && typeof lr.cartAttributes === "function" ? lr.cartAttributes() : [];
			for (var i = 0; i < attrs.length; i++) {
				if (attrs[i].key === "_lr_sid") out.sid = attrs[i].value || null;
				if (attrs[i].key === "_lr_vid") out.vid = attrs[i].value || null;
			}
		} catch (e) {}
		return out;
	}

	// Classic checkout.
	function fillClassic() {
		try {
			var form = document.querySelector("form.checkout");
			if (!form) return;
			var k = keys();
			[["lead_recorder_sid", k.sid], ["lead_recorder_vid", k.vid]].forEach(function (pair) {
				var input = form.querySelector('input[name="' + pair[0] + '"]');
				if (!input) {
					input = document.createElement("input");
					input.type = "hidden";
					input.name = pair[0];
					form.appendChild(input);
				}
				if (pair[1]) input.value = pair[1];
			});
		} catch (e) {}
	}

	// Block checkout. Older WooCommerce versions only had the internal name.
	var blockDone = false;
	function fillBlock() {
		if (blockDone) return;
		try {
			var k = keys();
			if (!k.sid && !k.vid) return;
			if (!window.wp || !wp.data || typeof wp.data.dispatch !== "function") return;
			var store = wp.data.dispatch("wc/store/checkout");
			if (!store) return;
			var data = { sid: k.sid, vid: k.vid };
			if (typeof store.setExtensionData === "function") {
				store.setExtensionData("lead-recorder", data);
			} else if (typeof store.__internalSetExtensionData === "function") {
				store.__internalSetExtensionData("lead-recorder", data);
			} else {
				return;
			}
			blockDone = true;
		} catch (e) {}
	}

	function fillAll() {
		fillClassic();
		fillBlock();
	}

	// The tracker and the checkout block can both arrive late, so try on
	// load and then a few more times.
	var tries = 0;
	function retry() {
		fillAll();
		if (++tries < 20 && !blockDone) setTimeout(retry, 500);
	}

	if (document.readyState === "loading") {
		document.addEventListener("DOMContentLoaded", retry);
	} else {
		retry();
	}
	window.addEventListener("load", fillAll);

	if (window.jQuery) {
		window.jQuery(function ($) {
			$("form.checkout").on("checkout_place_order", function () {
				fillClassic();
				return true;
			});
		});
	}
})();
JS;
}

/**
 * Paid-order hooks. Card payments fire woocommerce_payment_complete; cash
 * on delivery, bank transfer and cheque orders count once they're marked
 * Processing or Completed. All three can fire for one order, so the queue
 * step is idempotent.
 */
function lead_recorder_maybe_queue_purchase( $order_id ) {
	if ( ! lead_recorder_woo_ready() || ! function_exists( 'as_enqueue_async_action' ) ) {
		return;
	}

	$order = wc_get_order( $order_id );
	if ( ! $order || $order->get_meta( '_lr_sent_at' ) ) {
		return;
	}

	// Only orders a shopper placed through the online checkout, classic or
	// block. Leaves out wp-admin orders, subscription renewals and imports,
	// the same limits as Shopify purchase tracking.
	if ( ! in_array( $order->get_created_via(), array( 'checkout', 'store-api' ), true ) ) {
		return;
	}

	$args = array( (int) $order->get_id() );

	$pending = function_exists( 'as_has_scheduled_action' )
		? as_has_scheduled_action( LEAD_RECORDER_SEND_HOOK, $args, LEAD_RECORDER_AS_GROUP )
		: false !== as_next_scheduled_action( LEAD_RECORDER_SEND_HOOK, $args, LEAD_RECORDER_AS_GROUP );
	if ( $pending ) {
		return;
	}

	// A fresh status change gets a fresh set of retries, e.g. after an
	// earlier attempt gave up while the store key was wrong.
	if ( $order->get_meta( '_lr_send_attempts' ) ) {
		$order->delete_meta_data( '_lr_send_attempts' );
		$order->save();
	}

	// Never inside the checkout request: a slow or failing Lead Recorder
	// must not slow down or break a purchase.
	as_enqueue_async_action( LEAD_RECORDER_SEND_HOOK, $args, LEAD_RECORDER_AS_GROUP );
}
add_action( 'woocommerce_payment_complete', 'lead_recorder_maybe_queue_purchase' );
add_action( 'woocommerce_order_status_processing', 'lead_recorder_maybe_queue_purchase' );
add_action( 'woocommerce_order_status_completed', 'lead_recorder_maybe_queue_purchase' );

/**
 * The body we send. Order total, currency, date, id, the wp-admin link and
 * the anonymous visit keys; deliberately no name, email, address or items.
 */
function lead_recorder_purchase_body( $order ) {
	$created  = $order->get_date_created();
	$currency = $order->get_currency();
	$value    = (float) $order->get_total();
	// The store's base currency total is only known when the buyer paid in
	// it. Multi-currency plugins don't share a common conversion API.
	$is_base = $currency === get_woocommerce_currency();
	$sid     = (string) $order->get_meta( '_lr_sid' );
	$vid     = (string) $order->get_meta( '_lr_vid' );

	return array(
		'snippetKey'    => get_option( LEAD_RECORDER_OPTION, '' ),
		'orderId'       => (string) $order->get_id(),
		'createdAt'     => $created ? $created->format( DATE_ATOM ) : gmdate( DATE_ATOM ),
		'value'         => $value,
		'currency'      => $currency,
		'shopValue'     => $is_base ? $value : null,
		'shopCurrency'  => $is_base ? $currency : null,
		'sessionKey'    => '' !== $sid ? $sid : null,
		'visitorKey'    => '' !== $vid ? $vid : null,
		'adminOrderUrl' => $order->get_edit_order_url(),
	);
}

/**
 * Action Scheduler job: send one paid order. Lead Recorder ignores
 * duplicates, so a rare double send is harmless.
 */
function lead_recorder_send_purchase( $order_id ) {
	if ( ! lead_recorder_woo_ready() ) {
		return;
	}

	$order = wc_get_order( $order_id );
	if ( ! $order || $order->get_meta( '_lr_sent_at' ) ) {
		return;
	}

	$json      = wp_json_encode( lead_recorder_purchase_body( $order ) );
	$timestamp = (string) time();
	$signature = 'sha256=' . hash_hmac( 'sha256', $timestamp . '.' . $json, get_option( LEAD_RECORDER_STORE_KEY_OPTION, '' ) );

	$response = wp_remote_post(
		sprintf( 'https://%s/api/woocommerce/purchase', LEAD_RECORDER_API_HOST ),
		array(
			'timeout' => 10,
			'headers' => array(
				'Content-Type'   => 'application/json',
				'X-LR-Timestamp' => $timestamp,
				'X-LR-Signature' => $signature,
			),
			// The exact string that was signed; the server verifies these bytes.
			'body'    => $json,
		)
	);

	$status = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );

	if ( $status >= 200 && $status < 300 ) {
		$order->update_meta_data( '_lr_sent_at', gmdate( DATE_ATOM ) );
		$order->delete_meta_data( '_lr_send_error' );
		$order->delete_meta_data( '_lr_send_attempts' );
		$order->save();
		return;
	}

	$error = is_wp_error( $response )
		? $response->get_error_message()
		: sprintf( 'HTTP %d', $status );

	// A 4xx (bad key, unknown snippet, invalid order) will be refused the
	// same way every time, so retrying won't help. 429 means try later.
	if ( $status >= 400 && $status < 500 && 429 !== $status ) {
		$order->update_meta_data( '_lr_send_error', $error );
		$order->save();
		return;
	}

	// 429, 5xx or no response: retry at 5, 30 and 120 minutes, then give up.
	$delays   = array( 5, 30, 120 );
	$attempts = (int) $order->get_meta( '_lr_send_attempts' ) + 1;
	$order->update_meta_data( '_lr_send_attempts', $attempts );

	if ( isset( $delays[ $attempts - 1 ] ) && function_exists( 'as_schedule_single_action' ) ) {
		$order->save();
		as_schedule_single_action(
			time() + $delays[ $attempts - 1 ] * MINUTE_IN_SECONDS,
			LEAD_RECORDER_SEND_HOOK,
			array( (int) $order_id ),
			LEAD_RECORDER_AS_GROUP
		);
		return;
	}

	$order->update_meta_data( '_lr_send_error', $error );
	$order->save();
}
add_action( LEAD_RECORDER_SEND_HOOK, 'lead_recorder_send_purchase' );

/**
 * Suggested text for the site's privacy policy (Settings > Privacy).
 */
function lead_recorder_privacy_policy_content() {
	if ( ! function_exists( 'wp_add_privacy_policy_content' ) || ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	wp_add_privacy_policy_content(
		__( 'Lead Recorder', 'lead-recorder' ),
		wp_kses_post(
			wpautop(
				__( 'We use Lead Recorder to see which ads, searches and websites lead to sales. For each paid order, this store sends Lead Recorder the order total, currency, order date, order ID, a link to the order in our admin area and anonymous visit IDs. No customer details are sent: no name, email address, postal address or products.', 'lead-recorder' )
			)
		)
	);
}
add_action( 'admin_init', 'lead_recorder_privacy_policy_content' );
