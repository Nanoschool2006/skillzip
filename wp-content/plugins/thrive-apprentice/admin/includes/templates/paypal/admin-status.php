<?php

use TVA\PayPal\Apple_Pay;
use TVA\PayPal\Checkout;
use TVA\PayPal\Credentials;
use TVA\PayPal\Settings;
use TVA\PayPal\Subscription_Winddown;

$summary    = Credentials::get_capabilities_summary();
$cap_groups = $summary['groups'];
$cap_rollup = $summary['rollup'];

$email_confirmed  = Credentials::is_email_confirmed();
$receivable       = Credentials::is_payments_receivable();
$merchant_live    = Credentials::get_account_id_live();
$merchant_test    = Credentials::get_account_id();
$email_live       = Credentials::get_merchant_email( 'live' );
$email_test       = Credentials::get_merchant_email( 'test' );
$live_connected   = Credentials::is_mode_enabled( 'live' );
$test_connected   = Credentials::is_mode_enabled( 'test' );
$live_count       = Credentials::get_protected_products_count( true );
$test_count       = Credentials::get_protected_products_count( false );
$checkout_methods = Settings::get_checkout_methods();

// item key => display label, for resolving rollup pending/missing keys
$cap_labels = [];
foreach ( $cap_groups as $cap_group ) {
	foreach ( (array) ( $cap_group['items'] ?? [] ) as $cap_item ) {
		if ( ! empty( $cap_item['key'] ) ) {
			$cap_labels[ $cap_item['key'] ] = $cap_item['label'] ?: $cap_item['key'];
		}
	}
}

// PayPal statuses we have translations for; anything else renders verbatim.
$status_words = [
	'ACTIVE'   => __( 'Active', 'thrive-apprentice' ),
	'PENDING'  => __( 'Pending', 'thrive-apprentice' ),
	'DENIED'   => __( 'Denied', 'thrive-apprentice' ),
	'INACTIVE' => __( 'Inactive', 'thrive-apprentice' ),
];
?>

<p class="settings-p-main mt-15 mb-15"><?php esc_html_e( 'Control your PayPal connection below', 'thrive-apprentice' ); ?></p>

<?php /* IWT-required: shown only until the merchant confirms their PayPal email */ ?>
<div id="tva-paypal-email-warning" class="tva-paypal-email-warning"<?php echo $email_confirmed ? ' style="display:none;"' : ''; ?>>
	<?php tva_get_svg_icon( 'info-circle_light' ); ?>
	<p class="m-0">
		<strong><?php esc_html_e( 'Action Required:', 'thrive-apprentice' ); ?></strong>
		<?php esc_html_e( 'confirm your PayPal email to activate payments.', 'thrive-apprentice' ); ?>
	</p>
</div>

<?php /* IWT Onboarding #21/#22: ACDC/vaulting application needs action or was denied — check both modes */ ?>
<?php
$all_vetting_alerts = [];
foreach ( [ 'live' => $live_connected, 'test' => $test_connected ] as $va_mode => $va_connected ) {
	if ( $va_connected ) {
		foreach ( Credentials::get_vetting_alerts( $va_mode ) as $va_alert ) {
			$va_alert['mode'] = $va_mode;
			$all_vetting_alerts[] = $va_alert;
		}
	}
}
?>
<?php foreach ( $all_vetting_alerts as $vetting_alert ) : ?>
	<?php if ( $vetting_alert['phase'] === 'denied' ) : ?>
		<div class="tva-paypal-email-warning">
			<?php tva_get_svg_icon( 'forbidden' ); ?>
			<p class="m-0">
				<strong><?php esc_html_e( 'Application denied:', 'thrive-apprentice' ); ?></strong>
				<?php echo esc_html( sprintf( /* translators: %s: capability name */ __( 'Your %s application was denied by PayPal.', 'thrive-apprentice' ), $vetting_alert['label'] ) ); ?>
				<?php if ( 'test' === $vetting_alert['mode'] ) : ?><em>(<?php esc_html_e( 'Sandbox', 'thrive-apprentice' ); ?>)</em><?php endif; ?>
				<a href="https://www.paypal.com/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open PayPal', 'thrive-apprentice' ); ?></a>
			</p>
		</div>
	<?php else : ?>
		<div class="tva-paypal-rollup is-pending">
			<?php tva_get_svg_icon( 'info-circle_light' ); ?>
			<p class="m-0">
				<strong><?php esc_html_e( 'Action Required:', 'thrive-apprentice' ); ?></strong>
				<?php echo esc_html( sprintf( /* translators: %s: capability name */ __( 'Your %s application needs more information.', 'thrive-apprentice' ), $vetting_alert['label'] ) ); ?>
				<?php if ( 'test' === $vetting_alert['mode'] ) : ?><em>(<?php esc_html_e( 'Sandbox', 'thrive-apprentice' ); ?>)</em><?php endif; ?>
				<a href="https://www.paypal.com/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open PayPal', 'thrive-apprentice' ); ?></a>
			</p>
		</div>
	<?php endif; ?>
<?php endforeach; ?>

<?php /* ---- Your PayPal connections (per-mode; merchant ID inside the Live/Sandbox card) ---- */ ?>
<div class="tva-general-settings-card mb-20">
	<div class="tva-paypal-connection-count">
		<div class="tva-paypal-connection-count-card">
			<div class="tva-paypal-connection-count-info ml-25 mt-20">
				<div class="tva-paypal-connection-count-icon"><?php tva_get_svg_icon( 'dollar-sign' ); ?></div>
				<div class="tva-paypal-connection-count-number">
					<h4><?php esc_html_e( 'Live Mode', 'thrive-apprentice' ); ?><?php if ( $live_connected ) { tva_get_svg_icon( 'check-circle-green' ); } ?></h4>
					<?php if ( $live_connected ) : ?>
						<p class="tva-paypal-merchant-line m-0"><?php esc_html_e( 'Merchant:', 'thrive-apprentice' ); ?> <code><?php echo esc_html( $merchant_live ); ?></code><?php if ( $email_live !== '' ) : ?> - <code><?php echo esc_html( $email_live ); ?></code><?php endif; ?></p>
						<p class="m-0"><span><?php echo (int) $live_count; ?></span> <?php esc_html_e( 'Thrive Apprentice Products Connected', 'thrive-apprentice' ); ?></p>
					<?php else : ?>
						<p class="m-0"><span class="tva-paypal-mode-status"><?php esc_html_e( 'Not connected', 'thrive-apprentice' ); ?></span></p>
					<?php endif; ?>
				</div>
			</div>
			<div class="tva-paypal-connection-count-notice">
				<p class="m-0"><?php esc_html_e( 'Live PayPal connections enable real money transactions to protect your Thrive Apprentice Products.', 'thrive-apprentice' ); ?></p>
			</div>
		</div>
		<div class="tva-paypal-connection-count-card">
			<div class="tva-paypal-connection-count-info ml-25 mt-20">
				<div class="tva-paypal-connection-count-icon"><?php tva_get_svg_icon( 'experiment' ); ?></div>
				<div class="tva-paypal-connection-count-number">
					<h4><?php esc_html_e( 'Sandbox Mode', 'thrive-apprentice' ); ?><?php if ( $test_connected ) { tva_get_svg_icon( 'check-circle-green' ); } ?></h4>
					<?php if ( $test_connected ) : ?>
						<p class="tva-paypal-merchant-line m-0"><?php esc_html_e( 'Merchant:', 'thrive-apprentice' ); ?> <code><?php echo esc_html( $merchant_test ); ?></code><?php if ( $email_test !== '' ) : ?> - <code><?php echo esc_html( $email_test ); ?></code><?php endif; ?></p>
						<p class="m-0"><span><?php echo (int) $test_count; ?></span> <?php esc_html_e( 'Thrive Apprentice Products Connected', 'thrive-apprentice' ); ?></p>
					<?php else : ?>
						<p class="m-0"><span class="tva-paypal-mode-status"><?php esc_html_e( 'Not connected', 'thrive-apprentice' ); ?></span></p>
					<?php endif; ?>
				</div>
			</div>
			<div class="tva-paypal-connection-count-notice">
				<p class="m-0"><?php esc_html_e( 'Sandbox PayPal connections enable test transactions. Use this for testing purposes.', 'thrive-apprentice' ); ?></p>
			</div>
		</div>
	</div>
</div>

<?php
/* ---- Advanced payments (ACDC / wallet vaulting) vetting notice ----
   verify_merchant() persists the per-mode status via Credentials::save_vetting_status() on each
   settings load; this template reads it with get_vetting_status(). is_vetting_blocking() is true
   when the status is set and not SUBSCRIBED/APPROVED — exactly when advanced card / vaulting
   payments are withheld at checkout — so explain why to the merchant. */
$vetting_notices = [];
foreach ( [ 'live' => $live_connected, 'test' => $test_connected ] as $vetting_mode => $vetting_mode_connected ) {
	if ( $vetting_mode_connected && Credentials::is_vetting_blocking( $vetting_mode ) ) {
		$vetting_notices[ $vetting_mode ] = Credentials::get_vetting_status( $vetting_mode );
	}
}
foreach ( $vetting_notices as $vetting_mode => $vetting_status ) :
	switch ( $vetting_status ) {
		case 'PENDING':
			$vetting_msg = __( "PayPal is reviewing your account for advanced card and wallet (vaulting) payments. These methods aren't offered at checkout until the review completes — no action is needed for now.", 'thrive-apprentice' );
			break;
		case 'NEED_MORE_DATA':
			$vetting_msg = __( 'PayPal needs more information before approving advanced card and wallet (vaulting) payments. Complete the requested details in your PayPal account to enable these methods at checkout.', 'thrive-apprentice' );
			break;
		case 'DENIED':
			$vetting_msg = __( "PayPal did not approve advanced card and wallet (vaulting) payments for your account, so these methods aren't offered at checkout. Contact PayPal for details.", 'thrive-apprentice' );
			break;
		default:
			// Status-neutral fallback: covers any future blocking status (e.g. SUSPENDED) without
			// assuming "under review", which would mislead for a non-review state.
			$vetting_msg = __( "Advanced card and wallet (vaulting) payments aren't available at checkout right now. Check your PayPal account for details.", 'thrive-apprentice' );
	}
	?>
	<div class="tva-paypal-rollup <?php echo 'DENIED' === $vetting_status ? 'is-missing' : 'is-pending'; ?> mb-20">
		<?php tva_get_svg_icon( 'info-circle_light' ); ?>
		<p class="m-0">
			<strong><?php esc_html_e( 'Advanced payments status:', 'thrive-apprentice' ); ?></strong>
			<?php echo esc_html( $vetting_msg ); ?>
			<?php if ( 'test' === $vetting_mode ) : ?>
				<em>(<?php esc_html_e( 'Sandbox', 'thrive-apprentice' ); ?>)</em>
			<?php endif; ?>
		</p>
	</div>
<?php endforeach; ?>

<?php /* ---- Account standing (no heading) ---- */ ?>
<div class="tva-paypal-standing mb-20">
	<div class="tva-paypal-standing-card <?php echo $receivable ? 'is-ok' : 'is-warn'; ?>">
		<div class="tva-paypal-standing-info">
			<span class="tva-paypal-standing-title"><?php esc_html_e( 'Payments receivable', 'thrive-apprentice' ); ?></span>
			<span class="tva-paypal-standing-sub"><?php esc_html_e( 'Merchant can receive funds', 'thrive-apprentice' ); ?></span>
		</div>
		<span class="tva-paypal-standing-value"><?php echo $receivable ? esc_html__( 'Yes', 'thrive-apprentice' ) : esc_html__( 'No', 'thrive-apprentice' ); ?></span>
	</div>
	<div class="tva-paypal-standing-card <?php echo $email_confirmed ? 'is-ok' : 'is-warn'; ?>">
		<div class="tva-paypal-standing-info">
			<span class="tva-paypal-standing-title"><?php esc_html_e( 'Primary email confirmed', 'thrive-apprentice' ); ?></span>
			<span class="tva-paypal-standing-sub"><?php esc_html_e( 'Required before going live', 'thrive-apprentice' ); ?></span>
		</div>
		<span class="tva-paypal-standing-value"><?php echo $email_confirmed ? esc_html__( 'Yes', 'thrive-apprentice' ) : esc_html__( 'Pending', 'thrive-apprentice' ); ?></span>
	</div>
</div>

<?php /* ---- Default options ---- */ ?>
<div class="tva-general-settings-card mb-20">
	<h5 class="mb-20"><?php esc_html_e( 'Default options', 'thrive-apprentice' ); ?></h5>
	<?php /* Pay Later messaging only matters when PayPal offers the installments capability to the connected account, so hide the toggle otherwise (mode: live if connected, else test — matches the capabilities grid below). */ ?>
	<?php if ( Checkout::is_pay_later_capability_available() ) : ?>
	<div class="tvd-switch">
		<label class="tva-slide-checkbox">
			<span class="settings-checkbox-label"><?php esc_html_e( 'Show Pay Later messaging on product pages', 'thrive-apprentice' ); ?></span>
			<input type="checkbox" class="click" data-fn="changeSetting" data-setting="<?php echo esc_attr( Settings::SHOW_PAY_LATER_MESSAGING ); ?>" <?php checked( Settings::is_enabled( Settings::SHOW_PAY_LATER_MESSAGING ) ); ?>>
			<span class="lever"></span>
		</label>
	</div>
	<hr>
	<?php endif; ?>
	<div class="tvd-switch">
		<label class="tva-slide-checkbox">
			<span class="settings-checkbox-label"><?php esc_html_e( 'Automatically set “display buy button” when connecting a PayPal product', 'thrive-apprentice' ); ?></span>
			<input type="checkbox" class="click" data-fn="changeSetting" data-setting="<?php echo esc_attr( Settings::AUTO_DISPLAY_BUY_BUTTON ); ?>" <?php checked( Settings::is_enabled( Settings::AUTO_DISPLAY_BUY_BUTTON ) ); ?>>
			<span class="lever"></span>
		</label>
	</div>
	<hr>
	<div class="tvd-switch">
		<label class="tva-slide-checkbox">
			<span class="settings-checkbox-label"><?php esc_html_e( 'Enable FraudNet protection at checkout', 'thrive-apprentice' ); ?></span>
			<input type="checkbox" class="click" data-fn="changeSetting" data-setting="<?php echo esc_attr( Settings::FRAUDNET_ENABLED ); ?>" <?php checked( Settings::is_enabled( Settings::FRAUDNET_ENABLED ) ); ?>>
			<span class="lever"></span>
		</label>
	</div>
	<hr>
	<div class="tvd-switch">
		<label class="tva-slide-checkbox">
			<span class="settings-checkbox-label">
				<?php esc_html_e( 'Require 3D Secure authentication on every card payment', 'thrive-apprentice' ); ?>
				<span class="tva-paypal-method-hint"><?php esc_html_e( 'Applies to card payments taken on this site. When off, 3D Secure runs only where regionally required. Subscriptions are approved on PayPal, which applies its own 3D Secure rules.', 'thrive-apprentice' ); ?></span>
			</span>
			<input type="checkbox" class="click" data-fn="changeSetting" data-setting="<?php echo esc_attr( Settings::SCA_ALWAYS ); ?>" <?php checked( Settings::is_enabled( Settings::SCA_ALWAYS ) ); ?>>
			<span class="lever"></span>
		</label>
	</div>
</div>

<?php /* ---- Capabilities: data-driven groups from the Product API's capabilities_summary ---- */ ?>
<div class="tva-general-settings-card mb-20 tva-paypal-capabilities-card">
	<h5 class="mb-5"><?php esc_html_e( 'Capabilities', 'thrive-apprentice' ); ?></h5>
	<p class="tva-paypal-methods-note"><?php esc_html_e( 'Review which approved payment methods and account capabilities are available for checkout and subscription workflows.', 'thrive-apprentice' ); ?></p>

	<?php if ( empty( $cap_groups ) ) : ?>
		<p class="tva-paypal-cap-empty"><?php esc_html_e( 'No capability data yet — refresh the page to fetch the latest status from PayPal.', 'thrive-apprentice' ); ?></p>
	<?php else : ?>

		<?php if ( ! empty( $cap_rollup['all_ready'] ) ) : ?>
			<div class="tva-paypal-rollup is-ready">
				<?php tva_get_svg_icon( 'check-circle-green' ); ?>
				<p class="m-0"><?php esc_html_e( 'All payment methods ready', 'thrive-apprentice' ); ?></p>
			</div>
		<?php endif; ?>

		<?php
		// Drop methods we deliberately don't expose (NON_TOGGLEABLE_METHODS) and ones not offered
		// at checkout yet (UNSUPPORTED_METHODS) so the rollup never names a method that has no
		// corresponding row in the groups below.
		$rollup_pending = array_diff( (array) ( $cap_rollup['pending'] ?? [] ), Settings::NON_TOGGLEABLE_METHODS, Settings::UNSUPPORTED_METHODS );
		$rollup_missing = array_diff( (array) ( $cap_rollup['missing'] ?? [] ), Settings::NON_TOGGLEABLE_METHODS, Settings::UNSUPPORTED_METHODS );
		?>

		<?php if ( ! empty( $rollup_pending ) ) :
			$pending_labels = array_map( static function ( $key ) use ( $cap_labels ) {
				// Keys missing from groups have no label — prettify the raw key.
				return $cap_labels[ $key ] ?? ucwords( str_replace( '_', ' ', $key ) );
			}, $rollup_pending );
			?>
			<div class="tva-paypal-rollup is-pending">
				<?php tva_get_svg_icon( 'info-circle_light' ); ?>
				<p class="m-0"><strong><?php esc_html_e( 'Pending PayPal review:', 'thrive-apprentice' ); ?></strong> <?php echo esc_html( implode( ', ', $pending_labels ) ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $rollup_missing ) ) :
			$missing_labels = array_map( static function ( $key ) use ( $cap_labels ) {
				// Keys missing from groups have no label — prettify the raw key.
				return $cap_labels[ $key ] ?? ucwords( str_replace( '_', ' ', $key ) );
			}, $rollup_missing );
			?>
			<div class="tva-paypal-rollup is-warning">
				<?php tva_get_svg_icon( 'info-circle_light' ); ?>
				<p class="m-0"><strong><?php esc_html_e( 'Not granted by PayPal:', 'thrive-apprentice' ); ?></strong> <?php echo esc_html( implode( ', ', $missing_labels ) ); ?></p>
			</div>
		<?php endif; ?>

		<?php foreach ( $cap_groups as $cap_group ) :
			// Drop methods we deliberately don't expose: Pay Later funding rendered only on
			// PayPal's hosted checkout (NON_TOGGLEABLE_METHODS) and methods not offered at
			// checkout yet (UNSUPPORTED_METHODS — apms/fastlane). Skip a group left empty after
			// filtering so we don't render a stray heading.
			$cap_items = array_filter( (array) ( $cap_group['items'] ?? [] ), static function ( $item ) {
				$key = $item['key'] ?? '';
				return ! in_array( $key, Settings::NON_TOGGLEABLE_METHODS, true )
					&& ! in_array( $key, Settings::UNSUPPORTED_METHODS, true );
			} );
			if ( empty( $cap_items ) ) {
				continue; // skips to the next group in the outer foreach: / endforeach below
			}
			?>
			<h6 class="tva-paypal-cap-subhead"><?php echo esc_html( $cap_group['label'] ?: $cap_group['key'] ); ?></h6>
			<div class="tva-paypal-method-list tva-paypal-capabilities">
				<?php foreach ( $cap_items as $cap_item ) :
					$cap_key    = $cap_item['key'];
					$cap_label  = $cap_item['label'] ?: $cap_key;
					$cap_status = (string) ( $cap_item['status'] ?? '' );

					if ( ! empty( $cap_item['renderable'] ) && $cap_status === 'ACTIVE' ) :
						// Buyer-facing and ACTIVE — a display-preference toggle. The
						// contract guarantees renderable implies ACTIVE; the status
						// check makes a contract violation degrade to a read-only row.
						$is_locked = ( $cap_key === Settings::LOCKED_ON_METHOD );
						$checked   = $is_locked || ! empty( $checkout_methods[ $cap_key ] );
						?>
						<div class="tvd-switch tva-paypal-method-row<?php echo $is_locked ? ' is-disabled' : ''; ?>">
							<label class="tva-slide-checkbox">
								<span class="settings-checkbox-label">
									<?php echo esc_html( $cap_label ); ?>
									<?php if ( $is_locked ) : ?>
										<span class="tva-paypal-method-hint"><?php esc_html_e( 'Required', 'thrive-apprentice' ); ?></span>
									<?php endif; ?>
									<?php if ( ! empty( $cap_item['extra']['domain_registration_required'] ) ) : ?>
										<?php /* PayPal reports this flag as always-true; show the real state from our own record. The error state forces "required" even if a stale record exists, so the UI never claims success while serving is broken. */ ?>
										<?php if ( Apple_Pay::is_domain_registered() && '' === Apple_Pay::get_error() ) : ?>
											<span class="tva-paypal-method-hint"><?php esc_html_e( 'Domain registered', 'thrive-apprentice' ); ?></span>
										<?php else : ?>
											<span class="tva-paypal-method-hint"><?php esc_html_e( 'Domain registration required', 'thrive-apprentice' ); ?></span>
											<button type="button" class="tva-btn tva-btn-blue-empty click save-with-loader tva-paypal-reverify" data-fn="reverifyAppleDomain">
												<img width="15px" alt="" data-state="saving" src="<?php echo esc_url( TVA_Const::plugin_url( 'admin/img/loading-spinner.gif' ) ); ?>"/>
												<span data-state="save"><?php esc_html_e( 'Re-verify', 'thrive-apprentice' ); ?></span>
												<span data-state="saving"><?php esc_html_e( 'Re-verifying', 'thrive-apprentice' ); ?></span>
											</button>
										<?php endif; ?>
									<?php endif; ?>
								</span>
								<input type="checkbox" class="click" data-fn="toggleCheckoutMethod" data-method="<?php echo esc_attr( $cap_key ); ?>" <?php checked( $checked ); ?> <?php disabled( $is_locked ); ?>>
								<span class="lever"></span>
							</label>
						</div>
					<?php else :
						// Not independently renderable — read-only status row.
						$vetting_phase = ! empty( $cap_item['vetting'] ) ? Credentials::vetting_phase( $cap_item['vetting'] ) : '';
						$phase_words   = [
							'in_review'  => __( 'In review', 'thrive-apprentice' ),
							'needs_info' => __( 'Needs info', 'thrive-apprentice' ),
							'denied'     => __( 'Denied', 'thrive-apprentice' ),
						];
						if ( isset( $phase_words[ $vetting_phase ] ) ) {
							$status_word  = $phase_words[ $vetting_phase ];
							$status_class = $vetting_phase === 'denied' ? 'denied' : 'pending';
						} else {
							$status_word  = $status_words[ $cap_status ] ?? ucwords( strtolower( str_replace( '_', ' ', $cap_status ) ) );
							$status_class = in_array( $cap_status, [ 'ACTIVE', 'PENDING', 'DENIED' ], true ) ? strtolower( $cap_status ) : 'inactive';
						}
						?>
						<div class="tva-paypal-capability <?php echo $cap_status === 'ACTIVE' ? 'is-active' : 'is-inactive'; ?>">
							<span class="tva-paypal-capability-label"><?php echo esc_html( $cap_label ); ?></span>
							<span class="tva-paypal-capability-status <?php echo esc_attr( $status_class ); ?>">
								<?php if ( $cap_status === 'ACTIVE' ) { tva_get_svg_icon( 'check-circle-green' ); } ?>
								<?php echo esc_html( $status_word ); ?>
							</span>
						</div>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		<?php endforeach; ?>

	<?php endif; ?>
</div>

<?php /* ---- Disconnect + wind-down note ---- */ ?>
<div class="tva-paypal-disconnect click" data-fn="confirmDisconnect">
	<?php tva_get_svg_icon( 'forbidden' ); ?>
	<p class="m-0"><?php esc_html_e( 'Disconnect PayPal', 'thrive-apprentice' ); ?></p>
</div>
<?php $active_subscriptions = Subscription_Winddown::count_active(); ?>
<div class="tva-paypal-winddown">
	<?php tva_get_svg_icon( 'info-circle_light' ); ?>
	<p class="m-0">
		<strong>
			<?php
			echo esc_html(
				$active_subscriptions > 0
					? sprintf(
						/* translators: %d: number of active subscriptions. */
						_n( 'Active subscription (%d):', 'Active subscriptions (%d):', $active_subscriptions, 'thrive-apprentice' ),
						$active_subscriptions
					)
					: __( 'Active subscriptions:', 'thrive-apprentice' )
			);
			?>
		</strong>
		<?php esc_html_e( 'disconnecting cancels each active subscription at the end of its current billing period.', 'thrive-apprentice' ); ?>
	</p>
</div>
