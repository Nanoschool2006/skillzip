<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://wisdmlabs.com
 * @since      1.0.0
 * @package    BridgeWoocommerce
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @author     WisdmLabs <support@wisdmlabs.com>
 */
namespace NmBridgeWoocommerce{

	class BridgeWoocommercePublic {
		/**
		 * The ID of this plugin.
		 *
		 * @since    1.0.0
		 *
		 * @var string The ID of this plugin.
		 */
		private $plugin_name;

		/**
		 * The version of this plugin.
		 *
		 * @since    1.0.0
		 *
		 * @var string The current version of this plugin.
		 */
		private $version;

		/**
		 * Initialize the class and set its properties.
		 *
		 * @since    1.0.0
		 *
		 * @param string $plugin_name The name of the plugin.
		 * @param string $version     The version of this plugin.
		 */
		public function __construct( $plugin_name, $version ) {
			$this->plugin_name = $plugin_name;
			$this->version     = $version;
		}

		/**
		 * Register the stylesheets for the public-facing side of the site.
		 *
		 * @since    1.0.0
		 */
		public function enqueueStyles() {

			/**
			 * This function is provided for demonstration purposes only.
			 *
			 * An instance of this class should be passed to the run() function
			 * defined in BridgeWoocommerceLoader as all of the hooks are defined
			 * in that particular class.
			 *
			 * The BridgeWoocommerceLoader will then create the relationship
			 * between the defined hooks and the functions defined in this
			 * class.
			 */

			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/bridge-woocommerce-public.css', array(), $this->version, 'all' );
		}

		/**
		 * Register the stylesheets for the public-facing side of the site.
		 *
		 * @since    1.0.0
		 */
		public function enqueueScripts() {

			// Registering scripts.
			wp_register_script( 'bridge_woo_variation_courses', BRIDGE_WOOCOMMERCE_PLUGIN_URL . 'public/js/bridge-woocommerce-variation-courses.js', array( 'jquery' ), $this->version );
			wp_enqueue_script(
				$this->plugin_name,
				plugin_dir_url( __FILE__ ) . 'js/bridge-woocommerce-public.js',
				array( 'jquery' ),
				$this->version,
				false
			);

			$setting = get_option( 'eb_general', array() );
			if ( isset( $setting['eb_my_courses_page_id'] ) ) {
				$url = get_permalink( $setting['eb_my_courses_page_id'] );
				if ( $url ) {
					wp_localize_script(
						$this->plugin_name,
						'wiPublic',
						array(
							'myCoursesUrl' => $url,
							'cancel'       => __( 'Cancel', 'woocommerce-integration' ),
							'resume'       => __( 'Resume', 'woocommerce-integration' ),
						)
					);
				}
			}
		}

		/*
		* This function is used to add associated courses shortcode on - woocommerce_single_product_summary hook
		*
		* @access public
		* @return void
		* @since 1.0.0
		*/
		public function displayProductRelatedCourses() {
			global $product;
			$setting_woo_integration = get_option( 'eb_woo_int_settings', array() );
			if ( isset( $setting_woo_integration['wi_enable_asso_courses'] ) && 'yes' === $setting_woo_integration['wi_enable_asso_courses'] ) {
				if ( ( $product->is_type( 'simple' ) || $product->is_type( 'subscription' ) ) && shortcode_exists( 'bridge_woo_display_associated_courses' ) ) {
					$product_id = get_the_ID();
					echo esc_html( do_shortcode( '[bridge_woo_display_associated_courses product_id=' . $product_id . ']' ) );
				} elseif ( $product->is_type( 'variable' ) || $product->is_type( 'variable-subscription' ) ) {
					$available_variations = $product->get_available_variations();

					$variation_settings = array();

					if ( ! empty( $available_variations ) ) {
						foreach ( $available_variations as $single_variation ) {
							$return          = '';
							$variation_id    = $single_variation['variation_id'];
							$product_options = get_post_meta( $variation_id, 'product_options', true );

							if ( ! empty( $product_options ) ) {
								if ( isset( $product_options['moodle_post_course_id'] ) && is_array( $product_options['moodle_post_course_id'] ) && ! empty( $product_options['moodle_post_course_id'] ) ) {
									$return = ' <ul class="bridge-woo-available-courses">';
									foreach ( $product_options['moodle_post_course_id'] as $single_course_id ) {
										if ( 'publish' === get_post_status( $single_course_id ) ) {
											ob_start();
											?>
											<li>
												<a href="<?php echo esc_url( get_permalink( $single_course_id ) ); ?>" target="_blank"><?php echo get_the_title( $single_course_id ); ?></a>
											</li>
											<?php
											$return .= ob_get_clean();
										}
									}
									$return .= '</ul>';
								}
							}

								$variation_settings[ $variation_id ] = apply_filters( 'bridge_woo_single_variation_html', $return, $variation_id );
						}//foreach ends

						wp_enqueue_script( 'bridge_woo_variation_courses' );
						wp_localize_script( 'bridge_woo_variation_courses', 'bridge_woo_courses', $variation_settings );

						ob_start();

						?>
							<div class="bridge-woo-courses" style="display:none;">
								<h4><?php esc_attr_e( 'Available courses', 'woocommerce-integration' ); ?></h4>
							</div>
						<?php

						$content = ob_get_clean();

						echo apply_filters( 'bridge_woo_variation_associated_courses', $content ); // @codingStandardsIgnoreLine
					}
				}
			}
		}

		public function groupedProductDisplayAssociatedCourses( $product ) {
			$product_options = get_post_meta( $product->get_id(  ), 'product_options', true );

			if ( isset( $product_options['moodle_post_course_id'] ) && is_array( $product_options['moodle_post_course_id'] ) && ! empty( $product_options['moodle_post_course_id'] ) ) {
				ob_start(  );
				?>
				<td>
					<div class="wi-asso-courses-wrapper">
				<h7><?php esc_attr_e( 'Courses', 'woocommerce-integration' ); ?></h7>
				<ul class="bridge-woo-available-courses">
					<?php
						\NmBridgeWoocommerce\wi_get_associated_courses( $product_options['moodle_post_course_id'] );
					?>
				</ul>
			</div>
				</td>
				<?php
				echo ob_get_clean(); // @codingStandardsIgnoreLine
			}
		}

		/*
		* This function is used to send associated courses list in WooCommerce Emails
		*
		* @access public
		* @return void
		* @since 1.0.0
		*/

		public function sendAssociatedCoursesInEmail( $order, $sent_to_admin, $plain_text ) {
			if ( empty( $sent_to_admin ) ) {
				$sent_to_admin = '';
			}
			if ( empty( $plain_text ) ) {
				$plain_text = '';
			}

			$allowed_order_status = apply_filters( 'bridge_woo_email_allowed_order_status', array( 'wc-processing', 'wc-completed', 'wc-on-hold' ) );

			if ( in_array( $order->get_status(), $allowed_order_status, true ) ) {
				// Including required files.
				include_once BRIDGE_WOOCOMMERCE_PLUGIN_DIR . 'includes/class-bridge-woo-functions.php';

				require_once WP_PLUGIN_DIR . '/edwiser-bridge/includes/class-eb.php';

				require_once WP_PLUGIN_DIR . '/edwiser-bridge/public/class-eb-template-loader.php';

				$edwiser_bridge = new \app\wisdmlabs\edwiserBridge\EdwiserBridge();

				$plugin_tpl_loader = new \app\wisdmlabs\edwiserBridge\EbTemplateLoader( $edwiser_bridge->getPluginName(), $edwiser_bridge->getVersion() );

				ob_start();

				$plugin_tpl_loader->wpGetTemplate(
					'emails/associated-courses-order-email.php',
					array(
						'order' => $order,
					),
					'',
					BRIDGE_WOOCOMMERCE_PLUGIN_DIR . 'public/templates/'
				);
				$email_content = ob_get_clean();

				echo $email_content; // @codingStandardsIgnoreLine
			}
		}

		/*
		* This function is used to set Enable registration on the "Checkout" page and Disable guest checkout - woocommerce_after_checkout_billing_form hook
		*
		* @access public
		* @return void
		* @since 1.1.3
		*/

		public function configureWooCommerceCheckout( $checkout ) {
			// Unnecessary var.
			unset( $checkout );

			if ( ! \WC_Checkout::instance()->enable_signup || \WC_Checkout::instance()->enable_guest_checkout ) {
				foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
					unset( $cart_item_key );
					$_product = $values['data'];

					$product_id = ( isset( $_product->variation_id ) ? $_product->variation_id : $_product->id );

					$product_options = get_post_meta( $product_id, 'product_options', true );

					if ( ! empty( $product_options ) && isset( $product_options['moodle_post_course_id'] ) && ! empty( $product_options['moodle_post_course_id'] ) ) {
							// Add condition to make it work on checkout which have courses in the cart.
							\WC_Checkout::instance()->enable_signup         = true;
							\WC_Checkout::instance()->enable_guest_checkout = false;
							break;
					}
				}
			}
		}
		public function addWoocomerceOrdersToUserAccountPage( $user_orders ) {
			if ( ! is_user_logged_in() || is_admin() ) {
				return;
			}
			global $post;
			$content = $post->post_content;
			if ( ! has_shortcode( $content, 'eb_user_account' ) ) {
				return;
			}

			$wc_order_statuses = wc_get_order_statuses();

			$customer_orders = get_posts(
				array(
					'numberposts' => -1,
					'meta_key'    => '_customer_user',
					'meta_value'  => get_current_user_id(),
					'post_type'   => wc_get_order_types(),
					'post_status' => is_array( $wc_order_statuses ) ? array_keys( $wc_order_statuses ) : array(),
				)
			);

			$woo_orders  = $this->getUsersWoocommerceOrders( $customer_orders );
			$user_orders = array_merge( $user_orders, $woo_orders );

			return $user_orders;
		}

		public function getUsersWoocommerceOrders( $customer_orders ) {
			$courseAssociatedOrders = array();
			foreach ( $customer_orders as $key => $orderObject ) {
				$formattedOrderData = array();
				// if Order Belongs to subscription order shop_subscription do not include.
				if ( 'shop_subscription' === $orderObject->post_type ) {
					$formattedOrderData = [];
					continue;
				}

				$formattedOrderData['order_id']    = $orderObject->ID;
				$formattedOrderData['eb_order_id'] = $orderObject->ID;

				// Get WC order Object.
				$order                             = wc_get_order( $orderObject->ID );
				$formattedOrderData['status']      = $order->get_status();
				$formattedOrderData['amount_paid'] = $order->get_formatted_order_total();

				// Get WC order Object data.
				$orderData                           = $order->get_data();
				$formattedOrderData['billing_email'] = $orderData['billing']['email'];
				$formattedOrderData['currency']      = $orderData['currency'];
				$formattedOrderData['date']          = $orderData['date_created']->date( 'Y-m-d' );

				// $productItem is WC_Order_Item_Product Object.
				$orderedItems = $order->get_items();
				$formattedOrderData['ordered_item'] = array();
				foreach ( $orderedItems as $key => $productItem ) {
					// Get variation id.

					$productId=$productItem->get_product_id();

					$variationId = $productItem->get_variation_id();
					if ( $variationId != 0 ) {
						$productId = $variationId;
					}

					$productOptions = get_post_meta( $productId, 'product_options', true );

					// if the product is not associated with any moodle course.
					if ( false === $productOptions || empty( $productOptions ) ) {
						continue;
					} elseif ( ! empty( $productOptions['moodle_post_course_id'] ) ) {
						//array merge for courses from different product
						$formattedOrderData['ordered_item'] = array_merge( $formattedOrderData['ordered_item'], $productOptions['moodle_post_course_id'] );
					}
				}
				$courseAssociatedOrders[] = $formattedOrderData;
				$formattedOrderData       = [];
			}
			return $courseAssociatedOrders;
		}


		public function thankYouOrderReceivedText( $msg, $order ) {
			if ( ! empty( $order ) ) {
				$order_manager = new BridgeWoocommerceOrderManager( $this->plugin_name, $this->version );
				$courses       = (array) $order_manager->_getMoodleCourseIdsForOrder( $order );
				$setting       = get_option( 'eb_general', array() );
				$url           = isset( $setting['eb_my_courses_page_id'] ) ? get_permalink( $setting['eb_my_courses_page_id'] ) : null;
				// Get the setting to check if redirection is enabled or not.
				$setting_woo_integration = get_option( 'eb_woo_int_settings', array() );

				if ( count( $courses ) && $url && $setting_woo_integration['wi_enable_redirect'] === 'yes' ) {
					ob_start();
					?>
					<br />
					<span id="wi-thanq-wrapper">
						<span class="msg">
						<?php
						printf(
							__( 'You will be redirected to %s within next %s seconds.', 'woocommerce-integration' ),
							'<a href="' . esc_url( $url ) . '">' . __( 'My Courses Page', 'woocommerce-integration' ) . '</a>',
							'<span id="wi-countdown">10</span>'
						);
						?>
						</span>
						<button id="wi-cancel-redirect" data-wi-auto-redirect="on"><?php esc_attr_e( 'Cancel', 'woocommerce-integration' ); ?></button>
					</span>
					<?php
					$msg .= ob_get_clean(  );
				}
				return $msg;
			}
		}

		public function productPageAfterAddToCart() {
			global $product;
			if ( 'simple' === $product->get_type() ) {
				$args = array( 'product' => $product );
				echo self::getBuyNowButton( $args ); // @codingStandardsIgnoreLine
			}
		}

		public function shopPageAfterAddToCart() {
			global $product;
			if ( $product->get_type() == 'simple' ) {
				$args = array( 'product' => $product );
				echo '<br />' . self::getBuyNowButton( $args ); // @codingStandardsIgnoreLine
			}
		}

		public static function getBuyNowButton( $args ) {
			$args = wp_parse_args(
				$args,
				array(
					'product' => null,
					'class'   => 'button',
				)
			);

			extract( $args );

			$eb_general = get_option( 'eb_woo_int_settings', array() );
			if ( isset( $eb_general['wi_buy_now_text'] ) && ! empty( $eb_general['wi_buy_now_text'] ) ) {
				$buy_now_text = $eb_general['wi_buy_now_text'];
			} else {
				$buy_now_text = __( 'Buy Now', 'woocommerce-integration' );
			}

			$html = '';

			if ( null === $product || ! $product->is_purchasable() ) {
				return;
			}

			$link   = self::getProductAddToCartLink( $product, 1 );
			$_id    = 'wi_buy_now_' . $product->get_id();
			$_class = 'wi_btn_buy_now button wi_buy_now_' . $product->get_type();
			if ( is_product() ) {
				$_class .= ' wi_product';
			}
			$_attrs = 'data-product_type="' . $product->get_type() . '" data-product_id="' . $product->get_id() . '"';
			$html  .= '<a href="' . $link . '" id="' . $_id . '" ' . $_attrs . '  class="' . $_class . '">';
			$html  .= $buy_now_text;
			$html  .= '</a>';
			return $html;
		}

		public static function getProductAddToCartLink( $product, $qty = 1 ) {
			if ( 'simple' === $product->get_type() ) {
				$link = $product->add_to_cart_url();
				$link = add_query_arg( 'quantity', $qty, $link );
				$link = add_query_arg( 'wi_buy_now', true, $link );
				return $link;
			}
		}

		public function buyNowRedirect( $url ) {
			if ( isset( $_REQUEST['wi_buy_now'] ) && 1 === (int)$_REQUEST['wi_buy_now'] ) {
				$eb_general = get_option( 'eb_woo_int_settings', array() );
				if ( isset( $eb_general['wi_scc_page_id'] ) ) {
					$scc_url = get_permalink( $eb_general['wi_scc_page_id'] );
					if ( $scc_url ) {
						$url = $scc_url;
					}
				}
			}

			return $url;
		}

		/**
		 * On update cart by default page redirect to the default checkout page.
		 * If page is one click checkout page then function redirects to the edwiser bridge selected one click checkout page.
		 *
		 * @since  2.16
		 * @param  string $is_scc Boolean Provider Slug/Type.
		 * @return string
		 */
		public function eb_get_one_click_checkout_url( $default_cart_url  ) {
			global $post;

			if ( isset( $post  ) && is_object( $post  ) && isset( $post->post_content  ) && isset( $post->post_content  ) && strpos( $post->post_content, '[bridge_woo_single_cart_checkout]'  ) !== false  ) {
				$eb_general = get_option( 'eb_woo_int_settings', array());
				if ( isset( $eb_general['wi_scc_page_id'] ) ) {
					$scc_page_id = (int) $eb_general['wi_scc_page_id'];
					$default_cart_url = get_page_link( $scc_page_id );
				}
			}

			return $default_cart_url;
		}


		/**
		 * Woocommmerce loads checkout js and css only on the woocommerce checkout page.
		 * Below is the function to load woocommerce js and css on our one click checkout page. 
		 *
		 * @since  2.16
		 * @param  string $is_scc Boolean Provider Slug/Type.
		 * @return string
		 */
		public function isSingleCartCheckout( $is_scc ) {
			global $post;

			$eb_general = get_option( 'eb_woo_int_settings', array() );
			if ( isset( $eb_general['wi_scc_page_id'] ) ) {
				$scc_page_id = (int) $eb_general['wi_scc_page_id'];
				if ( is_page( $scc_page_id ) ) {
					$is_scc = true;
				}
			}

			if ( isset( $post ) && is_object( $post ) && isset( $post->post_content ) && strpos( $post->post_content, '[woocommerce_cart]' ) !== false ) {
				$is_scc = false;
			}

			return $is_scc;
		}

		/**
		 * Add input checkbox and other input fields for purchase for someone else.
		 *
		 * @since  2.2.1
		 */
		public function wi_add_purchase_for_someone_else_input_fields( $checkout ) {

			// get products from cart.
			$cart_items = WC()->cart->get_cart();
			// get cart meta data.

			$products   = array();
			foreach ( $cart_items as $cart_item ) {
				if ( isset( $cart_item['wdm_edwiser_self_enroll'] ) && 'on' == $cart_item['wdm_edwiser_self_enroll'] ) {
					return;
				}
				$products[] = $cart_item['data'];
			}

			foreach ( $products as $product ) {
				$product_id      = $product->get_id();
				$product_options = get_post_meta( $product_id, 'product_options', true );
				$group_purchase  = 'off';
				$list_of_courses = array();
				if (check_value_set($product_options, 'moodle_post_course_id')) {
					$line_item_course_ids = $product_options['moodle_post_course_id'];

					if (! empty($list_of_courses)) {
						$list_of_courses = array_unique(array_merge($list_of_courses, $line_item_course_ids), SORT_REGULAR);
					} else {
						$list_of_courses = $line_item_course_ids;
					}
				} else {
					return;
				}
			}
			if ( empty( $list_of_courses ) ) {
				return;
			}

			// add checkbox for purchase for someone else.
			?>
			<h3 id="purchase-for-someone-else">
				<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
					<input id="purchase-for-someone-else-checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" type="checkbox" name="purchase_for_someone_else" value="1" style="border: 1px solid rgb(0, 0, 0);">
					<span><?php _e( 'Purchase this product for someone else?', 'woocommerce-integration') ?></span>
				</label>
			</h3>
			<p class="form-row form-row-wide eb-purchase-for-someone-else" id="order_comments_field">
				<label for="order_comments" class=""><?php _e( 'First Name', 'woocommerce-integration' ); ?><abbr class="required" title="required">*</abbr></label>
				<input type="text" class="input-text" name="recipient_first_name" id="recipient_first_name" placeholder="" value="" />
			</p>
			<p class="form-row form-row-wide eb-purchase-for-someone-else" id="order_comments_field">
				<label for="order_comments" class=""><?php _e( 'Last Name', 'woocommerce-integration' ); ?><abbr class="required" title="required">*</abbr></label>
				<input type="text" class="input-text" name="recipient_last_name" id="recipient_last_name" placeholder="" value="" />
			</p>
			<p class="form-row form-row-wide eb-purchase-for-someone-else" id="order_comments_field">
				<label for="order_comments" class=""><?php _e( 'Email', 'woocommerce-integration' ); ?><abbr class="required" title="required">*</abbr></label>
				<input type="text" class="input-text" name="recipient_email" id="recipient_email" placeholder="" value="" />
			</p>
			<?php
		}

		/**
		 * Validate input fields for purchase for someone else.
		 * 
		 * @since  2.2.1
		 */
		public function wi_validate_purchase_for_someone_else_input_fields() {
			$eb_general = get_option('eb_woo_int_settings');
			$purchase_for_someone_else_enabled = isset($eb_general['wi_enable_purchase_for_someone_else']) && $eb_general['wi_enable_purchase_for_someone_else'] === 'yes' ? true : false;
			$is_for_someone_else = isset( $_POST['purchase_for_someone_else'] ) && $_POST['purchase_for_someone_else'] === '1' ? true : false;
			if ( $purchase_for_someone_else_enabled && $is_for_someone_else ) {
				if ( ( isset( $_POST['recipient_first_name'] ) && empty( $_POST['recipient_first_name'] ) ) || ! isset( $_POST['recipient_first_name'] ) ) {
				    wc_add_notice( '<b>' . esc_html__( 'First Name', 'woocommerce-integration' ) . '</b>' . esc_html__( ' is a required field.', 'woocommerce-integration' ), 'error' );
				}
				if ( ( isset( $_POST['recipient_last_name'] ) && empty( $_POST['recipient_last_name'] ) ) || ! isset( $_POST['recipient_last_name'] ) ) {
				    wc_add_notice( '<b>' . esc_html__( 'Last Name', 'woocommerce-integration' ) . '</b>' . esc_html__( ' is a required field.', 'woocommerce-integration' ), 'error' );
				}
				if ( ( isset( $_POST['recipient_email'] ) && empty( $_POST['recipient_email'] ) ) || ! isset( $_POST['recipient_email'] ) ) {
				    wc_add_notice( '<b>' . esc_html__( 'Email', 'woocommerce-integration' ) . '</b>' . esc_html__( ' is a required field.', 'woocommerce-integration' ), 'error' );
				}
			}
		}
	}
}
