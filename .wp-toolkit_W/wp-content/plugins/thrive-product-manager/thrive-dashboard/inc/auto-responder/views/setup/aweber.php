<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/** @var $this Thrive_Dash_List_Connection_AWeber */

// Detect connection state and OAuth version
$is_connected    = $this->is_connected();
$oauth_version   = $this->param( 'oauth_version' );
$is_legacy_oauth = $is_connected && ( empty( $oauth_version ) || '1.0' === $oauth_version );
?>

<?php if ( $is_legacy_oauth ) : ?>
	<!-- Legacy OAuth 1.0 - Upgrade Prompt UI -->
	<div class="tvd-aweber-upgrade-container">
		<div class="tvd-aweber-upgrade-content">
			<div class="tvd-aweber-title">
				<h2><?php echo esc_html( $this->get_title() ); ?></h2>
				<span class="tvd-aweber-badge tvd-aweber-badge-upgrade">
					<svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
						<circle cx="6" cy="6" r="5.5" stroke="#ff7100"/>
						<path d="M6 3V6.5" stroke="#ff7100" stroke-linecap="round"/>
						<circle cx="6" cy="8.5" r="0.5" fill="#ff7100"/>
					</svg>
					<?php echo esc_html__( 'Upgrade available', 'thrive-dash' ); ?>
				</span>
			</div>

			<form class="tvd-aweber-form">
				<input type="hidden" name="api" value="<?php echo esc_attr( $this->get_key() ); ?>"/>
				<input type="hidden" name="oauth_version" value="<?php echo esc_attr( Thrive_Dash_List_Connection_AWeber::OAUTH_VERSION_MIDDLEMAN ); ?>"/>
				<?php echo wp_nonce_field( 'tvd_api_connect', 'tvd_api_nonce', true, false ); ?>
			</form>

			<p class="tvd-aweber-upgrade-description">
				<?php echo esc_html__( "You're using an older AWeber connection. Upgrade to the new, more secure OAuth 2.0 to unlock automatic tag syncing.", 'thrive-dash' ); ?>
			</p>

			<div class="tvd-aweber-help-link">
				<svg width="10" height="12" viewBox="0 0 10 12" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M1 1.5V10.5L9 6L1 1.5Z" stroke="#1E73BE" stroke-width="1.2" stroke-linejoin="round"/>
				</svg>
				<a href="https://help.thrivethemes.com/" target="_blank" rel="noopener noreferrer"><?php echo esc_html__( 'I need help with this', 'thrive-dash' ); ?></a>
			</div>
		</div>

		<div class="tvd-aweber-buttons">
			<button type="button" class="tvd-aweber-use-1 tvd-aweber-btn-outline tvd-waves-effect">
				<?php echo esc_html__( 'USE 1.0', 'thrive-dash' ); ?>
			</button>
			<button type="button" class="tvd-api-redirect tvd-aweber-btn-solid tvd-waves-effect tvd-waves-light">
				<?php echo esc_html__( 'UPGRADE TO 2.0', 'thrive-dash' ); ?>
			</button>
		</div>
		<script>
		jQuery(function($) {
			var $container = $('.tvd-aweber-upgrade-container').first();
			if ($container.length) {
				$container.closest('.tvd-card-content-wfooter').addClass('tvd-aweber-upgrade-parent');

				$container.on('click', '.tvd-aweber-use-1', function(e) {
					e.preventDefault();
					var $form = $container.find('form');
					$form.find('input[name="oauth_version"]').val('1.0');
					$container.find('.tvd-api-redirect').trigger('click');
				});
			}
		});
		</script>
	</div>

	<style>
	.tvd-aweber-upgrade-container {
		display: flex;
		flex-direction: column;
		gap: 20px;
		padding: 20px 16px;
	}

	/* Override parent card content padding when upgrade container is present */
	.tvd-card-content.tvd-card-content-wfooter.tvd-aweber-upgrade-parent {
		padding: 0 !important;
	}

	.tvd-aweber-upgrade-content {
		display: flex;
		flex-direction: column;
		gap: 12px;
	}

	.tvd-aweber-title {
		display: flex;
		align-items: center;
		gap: 8px;
		margin: 0;
	}

	.tvd-aweber-title h2 {
		margin: 0;
		font-family: 'Roboto', sans-serif;
		font-size: 24px;
		font-weight: 600;
		line-height: 1.5;
		color: #000;
	}

	.tvd-aweber-badge-upgrade {
		display: inline-flex;
		align-items: center;
		gap: 4px;
		background-color: #fcf9f6;
		border: 1px solid #ff7100;
		border-radius: 100px;
		padding: 4px 8px;
		font-family: 'Roboto', sans-serif;
		font-size: 12px;
		font-weight: 500;
		line-height: 1.5;
		color: #ff7100;
		white-space: nowrap;
		flex-shrink: 0;
	}

	.tvd-aweber-badge-upgrade svg {
		flex-shrink: 0;
		width: 12px;
		height: 12px;
	}

	.tvd-aweber-form {
		display: none;
	}

	.tvd-aweber-upgrade-description {
		margin: 0;
		font-family: 'Roboto', sans-serif;
		font-size: 14px;
		font-weight: 400;
		line-height: 1.5;
		color: rgba(0, 0, 0, 0.7);
		text-align: left;
	}

	.tvd-aweber-help-link {
		display: flex;
		align-items: center;
		gap: 5px;
	}

	.tvd-aweber-help-link svg {
		flex-shrink: 0;
		width: 8px;
		height: 10px;
	}

	.tvd-aweber-help-link a {
		color: #1E73BE !important;
		font-family: 'Roboto', sans-serif;
		font-size: 12px;
		text-decoration: underline !important;
		font-weight: 400;
	}

	.tvd-aweber-help-link a:hover {
		color: #155a8a !important;
	}

	.tvd-aweber-buttons {
		display: flex;
		gap: 8px;
		align-items: center;
		width: 100%;
	}

	.tvd-aweber-btn-outline {
		flex: 1 0 0;
		display: inline-flex;
		align-items: center;
		justify-content: center;
		background-color: #FFF !important;
		border: 1px solid #4bb35e !important;
		border-radius: 2px;
		padding: 10px 12px;
		font-family: 'Roboto', sans-serif;
		font-size: 14px;
		font-weight: 600;
		color: #4bb35e !important;
		text-transform: uppercase;
		cursor: pointer;
		transition: background-color 0.2s ease;
		height: 40px;
		box-sizing: border-box;
		line-height: 1.6;
	}

	.tvd-aweber-btn-outline:hover {
		background-color: #f5fbf6 !important;
		color: #4bb35e !important;
	}

	.tvd-aweber-btn-solid {
		flex: 0 0 auto;
		display: inline-flex;
		align-items: center;
		justify-content: center;
		background-color: #4bb35e !important;
		border: none !important;
		border-radius: 2px;
		padding: 10px 30px;
		font-family: 'Roboto', sans-serif;
		font-size: 14px;
		font-weight: 600;
		color: #ffffff !important;
		text-transform: uppercase;
		cursor: pointer;
		transition: background-color 0.2s ease;
		height: 40px;
		box-sizing: border-box;
		line-height: 1.6;
	}

	.tvd-aweber-btn-solid:hover {
		background-color: #43a254 !important;
		color: #ffffff !important;
	}
	</style>

<?php else : ?>
	<!-- New Connection or OAuth 2.0 Middleman - Default UI -->
	<h2 class="tvd-card-title tvd-aweber-title">
		<?php echo esc_html( $this->get_title() ); ?>
		<span class="tvd-aweber-badge">
			<svg width="9" height="12" viewBox="0 0 9 12" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M3.83184 0.19357C4.23072 -0.0645235 4.77037 -0.0645235 5.16924 0.19357L5.54465 0.404738C5.56811 0.428201 5.61504 0.428201 5.6385 0.428201H6.06084C6.55356 0.404738 7.02282 0.662832 7.23399 1.10863L7.44516 1.48404C7.46862 1.5075 7.49208 1.53097 7.51555 1.55443L7.89096 1.74213C8.33676 1.97677 8.59485 2.44603 8.57139 2.91529V3.36109C8.57139 3.38455 8.57139 3.43148 8.59485 3.45494L8.80602 3.80689C9.06411 4.22922 9.06411 4.74541 8.80602 5.16775L8.59485 5.54316C8.57139 5.56662 8.57139 5.61354 8.57139 5.63701V6.05934C8.59485 6.55207 8.33676 6.99787 7.89096 7.2325L7.51555 7.44367C7.49208 7.46713 7.46862 7.49059 7.44516 7.51405L7.25745 7.88946C7.02282 8.3118 6.55356 8.59336 6.0843 8.56989H5.6385C5.61504 8.56989 5.56811 8.56989 5.54465 8.59336L5.1927 8.80452C4.77037 9.06262 4.25418 9.06262 3.83184 8.80452L3.45643 8.59336C3.43297 8.56989 3.38605 8.56989 3.36258 8.56989H2.94025C2.44752 8.59336 2.00172 8.3118 1.76709 7.88946L1.55592 7.51405C1.53246 7.49059 1.509 7.46713 1.48554 7.44367L1.11013 7.2325C0.68779 6.99787 0.406233 6.55207 0.429696 6.05934V5.63701C0.429696 5.61354 0.429696 5.56662 0.406233 5.54316L0.195065 5.16775C-0.0630287 4.76887 -0.0630287 4.22922 0.195065 3.83035L0.406233 3.45494C0.429696 3.43148 0.429696 3.38455 0.429696 3.36109V2.93875C0.406233 2.44603 0.68779 1.97677 1.11013 1.74213L1.48554 1.55443C1.509 1.53097 1.53246 1.5075 1.55592 1.48404L1.76709 1.10863C2.00172 0.662832 2.44752 0.404738 2.94025 0.428201H3.36258C3.38605 0.428201 3.43297 0.428201 3.45643 0.404738L3.83184 0.19357ZM4.58266 1.15556C4.53574 1.10863 4.46535 1.10863 4.41842 1.15556L4.04301 1.36672C3.83184 1.5075 3.57375 1.55443 3.33912 1.55443H2.91678C2.84639 1.55443 2.776 1.57789 2.75254 1.64828L2.54137 2.02369C2.42406 2.23486 2.23635 2.42256 2.02519 2.53988L1.64978 2.75105C1.57939 2.77451 1.55592 2.8449 1.55592 2.89183V3.33762C1.57939 3.57226 1.509 3.83035 1.36822 4.04152L1.15705 4.41693C1.11013 4.46385 1.11013 4.53424 1.15705 4.58117L1.36822 4.95658C1.509 5.16775 1.55592 5.42584 1.55592 5.66047V6.08281C1.55592 6.1532 1.57939 6.22358 1.64978 6.24705L2.02519 6.45822C2.23635 6.57553 2.42406 6.76324 2.54137 6.9744L2.75254 7.34981C2.776 7.4202 2.84639 7.44367 2.91678 7.44367H3.33912C3.57375 7.4202 3.83184 7.49059 4.04301 7.63137L4.41842 7.84254C4.46535 7.88946 4.53574 7.88946 4.58266 7.84254L4.95807 7.63137C5.16924 7.49059 5.42733 7.44367 5.66196 7.44367H6.10776C6.15469 7.44367 6.22508 7.4202 6.24854 7.34981L6.45971 6.9744C6.57702 6.76324 6.76473 6.57553 6.9759 6.45822L7.35131 6.24705C7.4217 6.22358 7.44516 6.1532 7.44516 6.08281V5.66047C7.44516 5.42584 7.49208 5.16775 7.63286 4.95658L7.84403 4.58117C7.89096 4.53424 7.89096 4.46385 7.84403 4.41693L7.63286 4.04152C7.49208 3.83035 7.44516 3.57226 7.44516 3.33762V2.89183C7.44516 2.8449 7.4217 2.77451 7.35131 2.75105L6.9759 2.53988C6.76473 2.42256 6.57702 2.23486 6.45971 2.02369L6.24854 1.64828C6.22508 1.57789 6.15469 1.55443 6.10776 1.55443H5.66196C5.42733 1.55443 5.16924 1.5075 4.95807 1.36672L4.58266 1.15556ZM3.01064 4.48732C3.01064 3.97113 3.29219 3.4784 3.76145 3.19685C4.20725 2.93875 4.79383 2.93875 5.26309 3.19685C5.70889 3.4784 6.01391 3.97113 6.01391 4.48732C6.01391 5.02697 5.70889 5.51969 5.26309 5.80125C4.79383 6.05934 4.20725 6.05934 3.76145 5.80125C3.29219 5.51969 3.01064 5.02697 3.01064 4.48732ZM0.0308237 10.3531L0.99281 8.05371L1.11013 8.26487C1.46207 8.92184 2.18943 9.34418 2.94025 9.32071H3.2218L3.43297 9.46149C3.62068 9.55534 3.83184 9.6492 4.04301 9.69612L3.15141 11.7843C3.10449 11.9017 2.98717 11.9955 2.84639 11.9955C2.70562 12.019 2.56484 11.9486 2.49445 11.8313L1.74363 10.6816L0.429696 10.8693C0.288918 10.8927 0.148139 10.8458 0.0777498 10.7285C-0.0161025 10.6346 -0.0161025 10.4939 0.0308237 10.3531ZM5.84967 11.7843L4.98153 9.69612C5.16924 9.6492 5.38041 9.57881 5.56811 9.46149L5.80274 9.32071H6.06084C6.81166 9.34418 7.53901 8.92184 7.91442 8.26487L8.00827 8.05371L8.97026 10.3531C9.01719 10.4939 9.01719 10.6346 8.92333 10.7285C8.85294 10.8458 8.71217 10.8927 8.57139 10.8693L7.25745 10.6816L6.50664 11.8313C6.43625 11.9486 6.29547 12.019 6.15469 11.9955C6.01391 11.9955 5.8966 11.9017 5.84967 11.7843Z" fill="#794BB3"/>
			</svg>
			<?php echo esc_html__( 'Advanced', 'thrive-dash' ); ?>
		</span>
	</h2>

	<div class="tvd-row">
		<form class="tvd-col tvd-s12">
			<input type="hidden" name="api" value="<?php echo esc_attr( $this->get_key() ); ?>"/>
			<input type="hidden" name="oauth_version" value="<?php echo esc_attr( Thrive_Dash_List_Connection_AWeber::DEFAULT_OAUTH_VERSION ); ?>"/>
			<?php echo wp_nonce_field( 'tvd_api_connect', 'tvd_api_nonce', true, false ); ?>

			<div class="tvd-col tvd-s12 tvd-no-padding tvd-aweber-description-wrapper">
				<p class="tvd-aweber-description">
					<?php
					printf(
						// translators: %s: OAuth 2.0 wrapped in strong tags
						esc_html__( 'Securely connect your AWeber account using %s and activate automatic tag syncing.', 'thrive-dash' ),
						'<strong>' . esc_html__( 'OAuth 2.0', 'thrive-dash' ) . '</strong>'
					);
					?>
				</p>
			</div>

			<?php $this->display_video_link(); ?>
		</form>
	</div>

	<div class="tvd-card-action">
		<div class="tvd-row tvd-no-margin">
			<div class="tvd-col tvd-s12 tvd-m6">
				<a class="tvd-api-cancel tvd-btn-flat tvd-btn-flat-secondary tvd-btn-flat-dark tvd-full-btn tvd-waves-effect">
					<?php echo esc_html__( 'Cancel', 'thrive-dash' ); ?>
				</a>
			</div>
			<div class="tvd-col tvd-s12 tvd-m6">
				<a class="tvd-api-redirect tvd-waves-effect tvd-waves-light tvd-btn tvd-btn-green tvd-full-btn">
					<?php echo esc_html__( 'Connect', 'thrive-dash' ); ?>
				</a>
			</div>
		</div>
	</div>

	<style>
	.tvd-aweber-title {
		display: flex;
		align-items: center;
		gap: 8px;
	}

	.tvd-aweber-badge {
		display: inline-flex;
		align-items: center;
		gap: 4px;
		background-color: #f3edf7;
		border: 1px solid #794bb3;
		border-radius: 100px;
		padding: 4px 8px;
		font-family: 'Roboto', sans-serif;
		font-size: 12px;
		font-weight: 500;
		line-height: 1.5;
		color: #794bb3;
	}

	.tvd-aweber-badge svg {
		flex-shrink: 0;
	}

	.tvd-aweber-description-wrapper {
		margin-bottom: 12px;
	}

	.tvd-aweber-description {
		margin: 0;
		font-size: 14px;
		line-height: 1.5;
		color: rgba(0, 0, 0, 0.7);
		text-align: left;
	}
	</style>
<?php endif; ?>
