<?php
/**
 *
 * This file is used to provide basic product info. 
 *
 * @since      2.1.0
 * @package    Bridge_Woocommerce
 * @subpackage Bridge_Woocommerce/includes
 * @author     WisdmLabs <support@wisdmlabs.com>
 */
namespace NmBridgeWoocommerce {

    use \app\wisdmlabs\edwiserBridge\EdwiserBridge;

    class Bridge_Woo_Products_API
    {


        /**
         * This method registers the webservice endpointr
         * @return [type] [description]
         */
        public function wi_get_edwiser_products_list()
        {
            register_rest_route(
                'edwiser-bridge',
                "/woo-products/",
                array(
                    // 'methods' => \WP_REST_Server::EDITABLE,
                    'methods' => 'GET',
                    'callback' => array($this, "wi_get_edwiser_products"),
                    'permission_callback' => '__return_true',
                )
            );
        }


        /**
         * callback to the endpoit which will provide the product info. 
         */
        public function wi_get_edwiser_products()
        {
            global $wpdb;
            $products = array();

            $query = 'SELECT DISTINCT `product_id` FROM `' . $wpdb->prefix . "woo_moodle_course`";
            $result = $wpdb->get_results($query, ARRAY_A);

            foreach ($result as $products_id_arr) {

                $product_id = $products_id_arr['product_id'];
                $product = wc_get_product($product_id);

                $post = get_post($product_id, ARRAY_A);

                // Get product categories.
                $terms = get_the_terms($product_id, 'product_cat');
                $categories = array();
                foreach ($terms as $term) {
                    $categories[] = array(
                        "id"   => $term->id,
                        "name" => $term->name,
                        "slug" => $term->slug
                    );
                }


                $products[] = array(
                    'id'            => $product_id,
                    'name'          => $post['post_title'],
                    'permalink'     => get_post_permalink($product_id),
                    'slug'          => $post['post_name'],
                    'date_created'  => $post['post_date'],
                    'date_modified' => $post['post_modified'],
                    // 'type'          => get_product_type($product_id),
                    'type'          => $product->get_type($product_id),
                    'status'        => $post['post_status'],
                    'description'   => $post['post_content'],
                    'price'         => $product->get_price(),
                    'regular_price' => $product->get_regular_price(),
                    'sale_price'    => $product->get_sale_price(),
                    "categories"    => $categories,
                    'image-link'    => wp_get_attachment_image_src(get_post_thumbnail_id($product_id), 'single-post-thumbnail')
                );
            }

            return $products;
        }
    }
}
