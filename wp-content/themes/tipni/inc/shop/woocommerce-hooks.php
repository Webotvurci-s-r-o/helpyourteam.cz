<?php
/**
 * WooCommerce specific hooks and functions
 *
 * @package TipniJinak
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Remove default WooCommerce wrappers
 */
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );

/**
 * Add custom wrappers
 */
function tipnijinak_woocommerce_wrapper_before() {
    echo '<div class="shop-main-content">';
}
add_action( 'woocommerce_before_main_content', 'tipnijinak_woocommerce_wrapper_before', 10 );

function tipnijinak_woocommerce_wrapper_after() {
    echo '</div>';
}
add_action( 'woocommerce_after_main_content', 'tipnijinak_woocommerce_wrapper_after', 10 );

/**
 * Remove WooCommerce styles and scripts selectively
 */
function tipnijinak_dequeue_woocommerce_styles_scripts() {
    // If not on a WooCommerce page, remove styles
    if ( ! is_woocommerce() && ! is_cart() && ! is_checkout() ) {
        wp_dequeue_style( 'woocommerce-general' );
        wp_dequeue_style( 'woocommerce-layout' );
        wp_dequeue_style( 'woocommerce-smallscreen' );
    }
}
add_action( 'wp_enqueue_scripts', 'tipnijinak_dequeue_woocommerce_styles_scripts', 99 );

/**
 * Add a test function to verify template overrides are working
 */
function tipnijinak_test_woocommerce_template_override() {
    if ( is_shop() || is_product_category() ) {
        // Comment out after confirming templates are working
        // echo '<div style="background: red; color: white; padding: 10px;">Custom WooCommerce template is active</div>';
    }
}
add_action( 'woocommerce_before_shop_loop', 'tipnijinak_test_woocommerce_template_override', 5 );

/**
 * Modify products per page on shop
 */
function tipnijinak_products_per_page( $products ) {
    return 8; // Show 8 products per page
}
add_filter( 'loop_shop_per_page', 'tipnijinak_products_per_page', 20 );

/**
 * Remove some default WooCommerce elements we don't need
 */
function tipnijinak_remove_woocommerce_elements() {
    // Remove breadcrumbs
    remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
    
    // Remove sidebar
    remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
    
    // Remove sorting dropdown
    remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
}
add_action( 'init', 'tipnijinak_remove_woocommerce_elements' );


add_action('woocommerce_product_query', 'exclude_category_shop_only');
function exclude_category_shop_only($query) {
    // Kontrola že jsme pouze na hlavní shop stránce
    if (is_shop() && !is_product_category() && !is_product_tag()) {
        
        $excluded_categories = array('soutez'); // nebo array(12, 45) pro ID
        
        $tax_query = array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'slug', // nebo 'term_id'
                'terms'    => $excluded_categories,
                'operator' => 'NOT IN'
            )
        );
        
        $query->set('tax_query', $tax_query);
    }
}