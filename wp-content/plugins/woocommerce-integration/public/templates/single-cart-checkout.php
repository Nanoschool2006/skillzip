<?php
/**
 * Template - Single Cart Checkout.
 *
 * @since 1.1.3
 */
?>

<div class="wi-scc-wrapper">

<input type="hidden" id="wi-scc-url" value="<?php echo esc_url(get_permalink()); ?>" />

<?php
echo do_shortcode("[woocommerce_cart]");
echo do_shortcode("[woocommerce_checkout]");
?>
</div>