<?php
/**
 * Class TPM_License_Checker_And_Activation_Helper
 *
 * Responsible for checking if a product is purchased and activating licenses.
 */
if (!class_exists('TPM_License_Checker_And_Activation_Helper')) {
    class TPM_License_Checker_And_Activation_Helper extends \TPM_License_Manager
    {
        /**
         * Checks if a product tag exists in any of the licenses.
         *
         * @param string $product_tag The product tag to search for.
         * @return bool True if the product tag exists in any of the licenses, false otherwise.
         */
        public static function is_product_purchased( $product_tag )
        {
            // Get an instance of the TPM_License_Manager class
            $TPM_license_manager = self::get_instance();

            // Get TTW licenses
            $ttw_licenses = $TPM_license_manager->_get_ttw_licenses();

            // Check if the product tag exists in any of the licenses
            foreach ( $ttw_licenses as $data ) {
                if ( self::has_product_tag( $data, $product_tag ) ) {
                    return true;
                }
            }

            return false;
        }

        /**
         * Checks if a product tag exists in a specific license data.
         *
         * @param array  $license_data The data of a license to check.
         * @param string $product_tag  The product tag to search for.
         * @return bool True if the product tag exists in the license data, false otherwise.
         */
        private static function has_product_tag( $license_data, $product_tag )
        {
            if ( isset( $license_data[ 'tags' ] ) && is_array( $license_data[ 'tags' ] ) ) {
                return in_array( 'all', $license_data[ 'tags' ], false ) || in_array( $product_tag, $license_data[ 'tags' ], false );
            }
            return false;
        }

        /**
         * Activate license using plugin slug.
         *
         * @param string $plugin_tag The slug of the plugin to activate the license for.
         * @return bool True if license activation is successful, false otherwise.
         */
        public static function activate_license_using_slug( $plugin_tag ) {
            // Get the product list instance
            $productList = \TPM_Product_List::get_instance();

            // Get the product instance using plugin slug
            $product = $productList->get_product_instance( $plugin_tag );

            // Activate the license
            $product->activate();

            // Check if activation is successful
            $activationOk = false;

            if ( $product->is_licensed() ) {
                // If the product is licensed, activation is successful
                $activationOk = true;
            } else {
                // If the product is not licensed, try searching for the license and activate
                $product->search_license();
                $licensedProducts = \TPM_License_Manager::get_instance()->activate_licenses( array( $product ) );

                if ( ! empty( $licensedProducts ) ) {
                    // If any products are successfully licensed, set activation to true
                    $activationOk = true;
                }
            }

            // Clear caches after activation
            $productList->clear_cache();
            \TPM_License_Manager::get_instance()->clear_cache();

            // Return the activation status
            return $activationOk;
        }
    }
}
