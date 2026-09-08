<?php

/**
 * Helper class for managing Thrive product tags.
 */
class Thrive_Product_Tags_Helper {

    /**
     * Retrieve the product tag by slug.
     *
     * @param string $slug The slug of the product.
     * @return string The corresponding product tag or an empty string if not found.
     */
    public static function get_tag_by_slug( $slug ) {
        $products = self::thrive_product_tags();

        // Return the product tag corresponding to the slug, or an empty string if not found
        return $products[ $slug ] ?? '';
    }

    /**
     * Retrieve the array of Thrive product tags.
     *
     * @return array The array of Thrive product tags with slugs as keys and tags as values.
     */
    public static function thrive_product_tags() {
        // Define the array of Thrive product tags with slugs as keys and tags as values
        return [
            'thrive_leads'          => 'tl',
            'thrive_ultimatum'      => 'tu',
            'thrive_ovation'        => 'tvo',
            'thrive_quiz_builder'   => 'tqb',
            'thrive_apprentice'     => 'tva',
            'thrive_architect'      => 'tcb',
            'thrive_comments'       => 'tcm',
            'thrive_optimize'       => 'tab',
            'thrive_automator'      => 'tap',
            'thrive_theme_builder'  => 'ttb',
        ];
    }
}

