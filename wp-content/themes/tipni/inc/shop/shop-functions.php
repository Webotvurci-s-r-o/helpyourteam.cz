<?php
/**
 * Shop functionality for the Tipni Jinak theme
 *
 * @package TipniJinak
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Enqueue shop scripts and styles.
 */
function tipnijinak_shop_scripts() {
    if (is_page('obchod')) {
        // Enqueue shop specific stylesheet
        wp_enqueue_style('tipnijinak-shop-style', get_template_directory_uri() . '/assets/css/shop.css', array(), TIPNIJINAK_VERSION);
        
        // Enqueue shop specific script
        wp_enqueue_script('tipnijinak-shop-js', get_template_directory_uri() . '/assets/js/shop.js', array('jquery'), TIPNIJINAK_VERSION, true);
        
        // Pass AJAX URL and nonce to JavaScript
        wp_localize_script('tipnijinak-shop-js', 'tipnijinak_shop', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tipnijinak_shop_nonce'),
            'checkout_url' => wc_get_checkout_url(),
            'is_user_logged_in' => is_user_logged_in(),
            'login_url' => site_url('/login'),
            'i18n' => array(
                'error_message' => __('Nastala chyba při zpracování požadavku.', 'tipnijinak'),
                'add_to_cart_success' => __('Produkt byl přidán do košíku', 'tipnijinak'),
                'add_to_cart_error' => __('Produkt nemohl být přidán do košíku', 'tipnijinak'),
                'loading' => __('Zpracovávám...', 'tipnijinak'),
            )
        ));
    }
}
add_action('wp_enqueue_scripts', 'tipnijinak_shop_scripts');

/**
 * AJAX handler for creating WooCommerce order
 */
function tipnijinak_create_wc_order() {
    // Check nonce for security
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'tipnijinak_shop_nonce')) {
        wp_send_json_error(array('message' => __('Bezpečnostní ověření selhalo.', 'tipnijinak')));
    }

    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => __('Pro vytvoření objednávky se musíte přihlásit.', 'tipnijinak')));
    }

    // Check for product ID
    if (!isset($_POST['product_id']) || empty($_POST['product_id'])) {
        wp_send_json_error(array('message' => __('Nebyl zadán produkt.', 'tipnijinak')));
    }

    $product_id = absint($_POST['product_id']);
    $payment_method = sanitize_text_field($_POST['payment_method']);

    // Add to cart
    $cart_item_key = WC()->cart->add_to_cart($product_id, 1);
    
    if (!$cart_item_key) {
        wp_send_json_error(array('message' => __('Produkt nemohl být přidán do košíku.', 'tipnijinak')));
    }

    // Create order
    $order = wc_create_order();
    
    // Get customer details
    $current_user = wp_get_current_user();
    
    // Set customer
    $order->set_customer_id(get_current_user_id());
    
    // Add products to order from cart
    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        $product = $cart_item['data'];
        $product_id = $cart_item['product_id'];
        $quantity = $cart_item['quantity'];
        
        $order->add_product($product, $quantity);
    }
    
    // Set payment method
    $order->set_payment_method($payment_method);
    
    // Calculate totals
    $order->calculate_totals();
    
    // Save order
    $order->save();
    
    // Empty cart
    WC()->cart->empty_cart();
    
    // Generate payment URL based on payment method
    $pay_url = $order->get_checkout_payment_url();
    
    // Return success and redirect URL
    wp_send_json_success(array(
        'redirect' => $pay_url,
        'order_id' => $order->get_id()
    ));
}
add_action('wp_ajax_create_wc_order', 'tipnijinak_create_wc_order');
add_action('wp_ajax_nopriv_create_wc_order', 'tipnijinak_create_wc_order');

/**
 * Get WooCommerce product categories for shop filter
 */
function tipnijinak_get_product_categories() {
    $terms = get_terms(array(
        'taxonomy' => 'product_cat',
        'hide_empty' => true,
    ));
    
    $categories = array();
    
    if (!empty($terms) && !is_wp_error($terms)) {
        foreach ($terms as $term) {
            $categories[] = array(
                'id' => $term->term_id,
                'slug' => $term->slug,
                'name' => $term->name,
                'count' => $term->count
            );
        }
    }
    
    return $categories;
}