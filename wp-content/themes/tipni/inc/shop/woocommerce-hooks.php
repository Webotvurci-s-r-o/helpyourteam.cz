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
 * Nastavení telefonu jako povinného pole v checkoutu
 */
function tipnijinak_billing_phone_required( $fields ) {
    $fields['billing_phone']['required'] = true;
    return $fields;
}
add_filter( 'woocommerce_billing_fields', 'tipnijinak_billing_phone_required' );

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

/**
 * =====================================================
 * CUSTOM CHECKOUT FIELDS - Tipni Jinak (WooCommerce Blocks)
 * =====================================================
 */

/**
 * Registrace WooCommerce Blocks integrace
 */
add_action('woocommerce_blocks_loaded', 'tipnijinak_register_checkout_blocks_integration');
function tipnijinak_register_checkout_blocks_integration() {
    if (!class_exists('Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface')) {
        return;
    }

    require_once get_template_directory() . '/inc/shop/class-tipnijinak-checkout-blocks.php';

    add_action(
        'woocommerce_blocks_checkout_block_registration',
        function($integration_registry) {
            $integration_registry->register(new Tipnijinak_Checkout_Blocks_Integration());
        }
    );
}

/**
 * Automatické přidání custom bloku do checkoutu
 * Pomocí filtru přidáme náš blok do default layoutu
 */
add_filter('__experimental_woocommerce_blocks_add_data_attributes_to_block', 'tipnijinak_add_checkout_fields_block', 10, 2);
function tipnijinak_add_checkout_fields_block($attributes, $block = null) {
    // Přidáme custom atribut pro identifikaci
    return $attributes;
}

/**
 * Render custom checkout fields block v PHP
 * Fallback pokud JS nefunguje
 */
add_action('woocommerce_review_order_before_submit', 'tipnijinak_render_checkout_fields_fallback', 10);
function tipnijinak_render_checkout_fields_fallback() {
    // Zobrazíme pouze pokud je to blocks checkout
    if (!function_exists('woocommerce_store_api_register_endpoint_data')) {
        return;
    }

    // Tento fallback se nezobrazí pokud JS funguje správně
    echo '<div id="tipnijinak-checkout-fields-container"></div>';
}

/**
 * Extend Store API - přidání custom fieldů do schématu
 */
add_action('woocommerce_blocks_loaded', 'tipnijinak_extend_store_api');
function tipnijinak_extend_store_api() {
    woocommerce_store_api_register_endpoint_data(array(
        'endpoint'        => \Automattic\WooCommerce\StoreApi\Schemas\V1\CheckoutSchema::IDENTIFIER,
        'namespace'       => 'tipnijinak',
        'data_callback'   => 'tipnijinak_data_callback',
        'schema_callback' => 'tipnijinak_schema_callback',
        'schema_type'     => ARRAY_A,
    ));
}

/**
 * Data callback pro Store API
 */
function tipnijinak_data_callback() {
    return array(
        'selected_club'        => '',
        'club_name'            => '',
        'terms_agreement'      => false,
        'marketing_agreement'  => false,
    );
}

/**
 * Schema callback pro Store API
 */
function tipnijinak_schema_callback() {
    return array(
        'selected_club' => array(
            'description' => __('ID vybraného klubu', 'tipnijinak'),
            'type'        => 'string',
            'context'     => array('view', 'edit'),
            'readonly'    => false,
        ),
        'club_name' => array(
            'description' => __('Název vybraného klubu', 'tipnijinak'),
            'type'        => 'string',
            'context'     => array('view', 'edit'),
            'readonly'    => false,
        ),
        'terms_agreement' => array(
            'description' => __('Souhlas s podmínkami', 'tipnijinak'),
            'type'        => 'boolean',
            'context'     => array('view', 'edit'),
            'readonly'    => false,
        ),
        'marketing_agreement' => array(
            'description' => __('Marketingový souhlas', 'tipnijinak'),
            'type'        => 'boolean',
            'context'     => array('view', 'edit'),
            'readonly'    => false,
        ),
    );
}

/**
 * AJAX handler pro uložení checkout dat do session
 */
add_action('wp_ajax_tipnijinak_save_checkout_data', 'tipnijinak_save_checkout_data');
add_action('wp_ajax_nopriv_tipnijinak_save_checkout_data', 'tipnijinak_save_checkout_data');
function tipnijinak_save_checkout_data() {
    check_ajax_referer('tipnijinak_checkout_ajax', 'nonce');

    $data = array(
        'username'            => isset($_POST['username']) ? sanitize_user($_POST['username']) : '',
        'password'            => isset($_POST['password']) ? $_POST['password'] : '',
        'selected_club'       => isset($_POST['selected_club']) ? sanitize_text_field($_POST['selected_club']) : '',
        'club_name'           => isset($_POST['club_name']) ? sanitize_text_field($_POST['club_name']) : '',
        'terms_agreement'     => isset($_POST['terms_agreement']) && $_POST['terms_agreement'] === 'true',
        'marketing_agreement' => isset($_POST['marketing_agreement']) && $_POST['marketing_agreement'] === 'true',
    );

    // Uložit do WooCommerce session
    if (function_exists('WC') && WC()->session) {
        WC()->session->set('tipnijinak_checkout_data', $data);
    }

    wp_send_json_success(array('message' => 'Data saved'));
}

/**
 * Validace custom fieldů při odeslání objednávky (Store API)
 */
add_action('woocommerce_store_api_checkout_update_order_from_request', 'tipnijinak_blocks_checkout_save_fields', 10, 2);
function tipnijinak_blocks_checkout_save_fields($order, $request) {
    // Zkusit získat data z extensions
    $data = $request['extensions']['tipnijinak'] ?? array();

    // Pokud nejsou v extensions, zkusit session
    if (empty($data) && function_exists('WC') && WC()->session) {
        $session_data = WC()->session->get('tipnijinak_checkout_data');
        if (!empty($session_data)) {
            $data = $session_data;
        }
    }

    // Pokud uživatel není přihlášen a máme username/password, vytvořit účet
    if (!is_user_logged_in() && !empty($data['username']) && !empty($data['password'])) {
        $user_id = tipnijinak_create_user_from_checkout($data, $order);
        if ($user_id && !is_wp_error($user_id)) {
            $order->set_customer_id($user_id);
        }
    }

    // Uložení vybraného klubu
    if (!empty($data['selected_club'])) {
        $order->update_meta_data('_tipnijinak_club_id', sanitize_text_field($data['selected_club']));
    }

    if (!empty($data['club_name'])) {
        $order->update_meta_data('_tipnijinak_club_name', sanitize_text_field($data['club_name']));
    }

    // Uložení souhlasů
    if (!empty($data['terms_agreement'])) {
        $order->update_meta_data('_tipnijinak_terms_agreement', 'yes');
    }

    if (!empty($data['marketing_agreement'])) {
        $order->update_meta_data('_tipnijinak_marketing_agreement', 'yes');
    }

    // Pokud je uživatel přihlášen, uložíme klub také k uživateli
    $user_id = $order->get_customer_id();
    if ($user_id && !empty($data['selected_club'])) {
        update_user_meta($user_id, 'club_id', intval($data['selected_club']));
        if (!empty($data['club_name'])) {
            update_user_meta($user_id, 'club_name', sanitize_text_field($data['club_name']));
        }
    }

    // Vyčistit session data
    if (function_exists('WC') && WC()->session) {
        WC()->session->set('tipnijinak_checkout_data', null);
    }
}

/**
 * Vytvoření uživatele z checkout dat
 */
function tipnijinak_create_user_from_checkout($data, $order) {
    $username = sanitize_user($data['username']);
    $password = $data['password'];
    $email = $order->get_billing_email();

    // Kontrola, zda uživatel již neexistuje
    if (username_exists($username)) {
        throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException(
            'tipnijinak_username_exists',
            __('Uživatelské jméno již existuje. Zvolte prosím jiné.', 'tipnijinak'),
            400
        );
    }

    if (email_exists($email)) {
        throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException(
            'tipnijinak_email_exists',
            __('Email již existuje. Přihlaste se nebo použijte jiný email.', 'tipnijinak'),
            400
        );
    }

    // Vytvoření uživatele
    $user_data = array(
        'user_login'    => $username,
        'user_email'    => $email,
        'user_pass'     => $password,
        'first_name'    => $order->get_billing_first_name(),
        'last_name'     => $order->get_billing_last_name(),
        'display_name'  => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
        'role'          => 'subscriber',
    );

    $user_id = wp_insert_user($user_data);

    if (is_wp_error($user_id)) {
        throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException(
            'tipnijinak_user_creation_failed',
            $user_id->get_error_message(),
            400
        );
    }

    // Uložit meta data uživatele
    $billing_phone = $order->get_billing_phone();
    $billing_address = $order->get_billing_address_1();
    $billing_city = $order->get_billing_city();
    $billing_postcode = $order->get_billing_postcode();

    if ($billing_phone) {
        update_user_meta($user_id, 'phone', $billing_phone);
        update_user_meta($user_id, 'billing_phone', $billing_phone);
    }

    if ($billing_address) {
        update_user_meta($user_id, 'address', $billing_address);
        update_user_meta($user_id, 'billing_address_1', $billing_address);
    }

    if ($billing_city) {
        update_user_meta($user_id, 'city', $billing_city);
        update_user_meta($user_id, 'billing_city', $billing_city);
    }

    if ($billing_postcode) {
        update_user_meta($user_id, 'psc', $billing_postcode);
        update_user_meta($user_id, 'billing_postcode', $billing_postcode);
    }

    // WooCommerce billing data
    update_user_meta($user_id, 'billing_first_name', $order->get_billing_first_name());
    update_user_meta($user_id, 'billing_last_name', $order->get_billing_last_name());
    update_user_meta($user_id, 'billing_email', $email);
    update_user_meta($user_id, 'billing_country', 'CZ');

    // Uložit klub
    if (!empty($data['selected_club'])) {
        update_user_meta($user_id, 'club_id', intval($data['selected_club']));
    }
    if (!empty($data['club_name'])) {
        update_user_meta($user_id, 'club_name', sanitize_text_field($data['club_name']));
    }

    // Uložit souhlasy
    update_user_meta($user_id, 'terms_agreement', !empty($data['terms_agreement']) ? 'yes' : 'no');
    update_user_meta($user_id, 'marketing_agreement', !empty($data['marketing_agreement']) ? 'yes' : 'no');

    // Automatické přihlášení uživatele
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id, true, is_ssl());

    // Log pro debugging
    error_log('Tipnijinak: User created from checkout - ID: ' . $user_id . ', Username: ' . $username);

    return $user_id;
}

/**
 * Zobrazení custom fieldů v admin objednávce
 */
add_action('woocommerce_admin_order_data_after_billing_address', 'tipnijinak_checkout_display_admin_fields');
function tipnijinak_checkout_display_admin_fields($order) {
    $club_id = get_post_meta($order->get_id(), '_tipnijinak_club_id', true);
    $club_name = get_post_meta($order->get_id(), '_tipnijinak_club_name', true);
    $terms = get_post_meta($order->get_id(), '_tipnijinak_terms_agreement', true);
    $marketing = get_post_meta($order->get_id(), '_tipnijinak_marketing_agreement', true);

    echo '<div class="tipnijinak-order-fields">';
    echo '<h3>' . __('Tipni Jinak údaje', 'tipnijinak') . '</h3>';

    if ($club_name) {
        echo '<p><strong>' . __('Oblíbený klub:', 'tipnijinak') . '</strong> ' . esc_html($club_name);
        if ($club_id) {
            echo ' (ID: ' . esc_html($club_id) . ')';
        }
        echo '</p>';
    }

    echo '<p><strong>' . __('Souhlas s podmínkami (18+):', 'tipnijinak') . '</strong> ' . ($terms === 'yes' ? __('Ano', 'tipnijinak') : __('Ne', 'tipnijinak')) . '</p>';
    echo '<p><strong>' . __('Marketingový souhlas:', 'tipnijinak') . '</strong> ' . ($marketing === 'yes' ? __('Ano', 'tipnijinak') : __('Ne', 'tipnijinak')) . '</p>';

    echo '</div>';
}

/**
 * Přidání custom fieldů do emailů
 */
add_action('woocommerce_email_after_order_table', 'tipnijinak_checkout_email_fields', 10, 4);
function tipnijinak_checkout_email_fields($order, $sent_to_admin, $plain_text, $email) {
    $club_name = get_post_meta($order->get_id(), '_tipnijinak_club_name', true);

    if ($club_name) {
        if ($plain_text) {
            echo "\n" . __('Oblíbený klub:', 'tipnijinak') . ' ' . $club_name . "\n";
        } else {
            echo '<p><strong>' . __('Oblíbený klub:', 'tipnijinak') . '</strong> ' . esc_html($club_name) . '</p>';
        }
    }
}

/**
 * Helper funkce pro získání lig pro JS
 */
if (!function_exists('tipnijinak_get_leagues_for_js')) {
    function tipnijinak_get_leagues_for_js() {
        $leagues = get_terms(array(
            'taxonomy'   => 'liga',
            'child_of'   => 46,
            'hide_empty' => false,
        ));

        if (is_wp_error($leagues) || empty($leagues)) {
            return array();
        }

        $result = array();
        foreach ($leagues as $league) {
            $result[] = array(
                'id'   => $league->term_id,
                'name' => $league->name,
            );
        }

        return $result;
    }
}

/**
 * Enqueue checkout styles a scripts pro Blocks checkout
 */
add_action('wp_enqueue_scripts', 'tipnijinak_checkout_enqueue_assets', 100);
function tipnijinak_checkout_enqueue_assets() {
    if (is_checkout()) {
        error_log('Tipnijinak: Enqueueing checkout assets');

        // CSS
        wp_enqueue_style(
            'tipnijinak-checkout-blocks',
            get_template_directory_uri() . '/assets/css/checkout-blocks.css',
            array(),
            filemtime(get_template_directory() . '/assets/css/checkout-blocks.css')
        );

        // JavaScript - FORCE ENQUEUE
        $script_path = get_template_directory() . '/assets/js/checkout-blocks.js';
        if (file_exists($script_path)) {
            wp_enqueue_script(
                'tipnijinak-checkout-blocks-frontend',
                get_template_directory_uri() . '/assets/js/checkout-blocks.js',
                array('wc-blocks-checkout', 'wp-element', 'wp-components'),
                filemtime($script_path),
                true
            );

            // Získat info o klubu přihlášeného uživatele
            $user_club_id = '';
            $user_club_name = '';
            if (is_user_logged_in()) {
                $user_id = get_current_user_id();
                $user_club_id = get_user_meta($user_id, 'club_id', true);
                $user_club_name = get_user_meta($user_id, 'club_name', true);
            }

            // Lokalizace pro JS
            wp_localize_script('tipnijinak-checkout-blocks-frontend', 'tipnijinakCheckout', array(
                'ajaxUrl'          => admin_url('admin-ajax.php'),
                'nonce'            => wp_create_nonce('tipnijinak_checkout_ajax'),
                'isUserLoggedIn'   => is_user_logged_in(),
                'userHasClub'      => !empty($user_club_id),
                'userClubName'     => $user_club_name,
                'loginUrl'         => add_query_arg('redirect_to', urlencode(wc_get_checkout_url()), home_url('/prihlasit-se/')),
                'i18n'             => array(
                    'additionalInfo'      => __('Další údaje', 'tipnijinak'),
                    'accountInfo'         => __('Přihlášení nebo registrace', 'tipnijinak'),
                    'createAccount'       => __('Vytvořit účet', 'tipnijinak'),
                    'orLogin'             => __('nebo se', 'tipnijinak'),
                    'loginLink'           => __('přihlaste', 'tipnijinak'),
                    'alreadyHaveAccount'  => __('Máte již účet?', 'tipnijinak'),
                    'username'            => __('Uživatelské jméno', 'tipnijinak'),
                    'usernamePlaceholder' => __('Vaše uživatelské jméno', 'tipnijinak'),
                    'password'            => __('Heslo', 'tipnijinak'),
                    'passwordPlaceholder' => __('Zadejte heslo (min. 8 znaků)', 'tipnijinak'),
                    'selectClub'          => __('Vyberte svůj oblíbený klub', 'tipnijinak'),
                    'clubNote'            => __('Váš oblíbený klub z nižší soutěže (3.liga max.)', 'tipnijinak'),
                    'searchClub'          => __('Vyhledat klub', 'tipnijinak'),
                    'addNewClub'          => __('Přidat klub', 'tipnijinak'),
                    'noClubsFound'        => __('Žádné kluby nenalezeny', 'tipnijinak'),
                    'termsAgreement'      => __('Jsem starší 18 let a souhlasím s', 'tipnijinak'),
                    'privacyPolicy'       => __('ochranou osobních údajů', 'tipnijinak'),
                    'gamePlans'           => __('herními plány', 'tipnijinak'),
                    'termsConditions'     => __('obchodními podmínkami', 'tipnijinak'),
                    'marketingAgreement'  => __('Souhlasím se zpracováním výše uvedených osobních údajů za účelem marketingových aktivit včetně zasílání marketingových zpráv a informování o produktech a službách.', 'tipnijinak'),
                    'clubTitle'           => __('Název klubu', 'tipnijinak'),
                    'clubLogo'            => __('Logo klubu', 'tipnijinak'),
                    'clubAbbr'            => __('Zkratka klubu', 'tipnijinak'),
                    'league'              => __('Liga', 'tipnijinak'),
                    'selectFile'          => __('Vybrat soubor', 'tipnijinak'),
                    'saving'              => __('Ukládání...', 'tipnijinak'),
                    'clubAdded'           => __('Klub byl úspěšně přidán.', 'tipnijinak'),
                    'errorCreatingClub'   => __('Nepodařilo se vytvořit klub.', 'tipnijinak'),
                    'usernameRequired'    => __('Uživatelské jméno je povinné.', 'tipnijinak'),
                    'passwordRequired'    => __('Heslo je povinné.', 'tipnijinak'),
                    'passwordTooShort'    => __('Heslo musí mít alespoň 8 znaků.', 'tipnijinak'),
                    'termsRequired'       => __('Musíte souhlasit s podmínkami.', 'tipnijinak'),
                ),
                'privacyPolicyUrl'    => get_privacy_policy_url(),
                'termsConditionsUrl'  => get_permalink(wc_terms_and_conditions_page_id()),
                'gamePlansUrl'        => home_url('/herni-plany/'),
                'leagues'             => tipnijinak_get_leagues_for_js(),
            ));

            error_log('Tipnijinak: Script enqueued - ' . get_template_directory_uri() . '/assets/js/checkout-blocks.js');
        } else {
            error_log('Tipnijinak: Script file NOT found - ' . $script_path);
        }
    }
}

/**
 * AJAX handler pro vyhledávání klubů na checkoutu
 */
add_action('wp_ajax_tipnijinak_search_clubs_checkout', 'tipnijinak_search_clubs_checkout');
add_action('wp_ajax_nopriv_tipnijinak_search_clubs_checkout', 'tipnijinak_search_clubs_checkout');
function tipnijinak_search_clubs_checkout() {
    check_ajax_referer('tipnijinak_checkout_ajax', 'nonce');

    $search_term = isset($_POST['search_term']) ? sanitize_text_field($_POST['search_term']) : '';

    if (strlen($search_term) < 2) {
        wp_send_json_error(array('message' => __('Zadejte alespoň 2 znaky.', 'tipnijinak')));
    }

    // Získání child kategorií ligy s ID 46 (Oblíbená liga)
    $child_leagues = get_terms(array(
        'taxonomy' => 'liga',
        'child_of' => 46,
        'fields' => 'ids',
        'hide_empty' => false,
    ));

    // Vyhledání klubů (týmů) - pouze z child kategorií Oblíbené ligy
    $args = array(
        'post_type'      => 'tym',
        'posts_per_page' => 10,
        's'              => $search_term,
        'post_status'    => 'publish',
    );

    // Filtrovat pouze týmy přiřazené k child ligám pod ID 46
    if (!empty($child_leagues) && !is_wp_error($child_leagues)) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'liga',
                'field' => 'term_id',
                'terms' => $child_leagues,
                'operator' => 'IN',
            ),
        );
    } else {
        // Pokud neexistují child ligy, nevrátit žádné výsledky
        $args['post__in'] = array(0);
    }

    $clubs_query = new WP_Query($args);
    $clubs = array();

    if ($clubs_query->have_posts()) {
        while ($clubs_query->have_posts()) {
            $clubs_query->the_post();
            $clubs[] = array(
                'id'   => get_the_ID(),
                'name' => get_the_title(),
            );
        }
        wp_reset_postdata();
    }

    wp_send_json_success(array('clubs' => $clubs));
}

/**
 * AJAX handler pro vytvoření nového klubu na checkoutu
 */
add_action('wp_ajax_tipnijinak_create_club_checkout', 'tipnijinak_create_club_checkout');
add_action('wp_ajax_nopriv_tipnijinak_create_club_checkout', 'tipnijinak_create_club_checkout');
function tipnijinak_create_club_checkout() {
    check_ajax_referer('tipnijinak_checkout_ajax', 'nonce');

    $club_title = isset($_POST['club_title']) ? sanitize_text_field($_POST['club_title']) : '';
    $club_abbr = isset($_POST['club_abbr']) ? sanitize_text_field($_POST['club_abbr']) : '';
    $club_league = isset($_POST['club_league']) ? intval($_POST['club_league']) : 0;

    if (empty($club_title)) {
        wp_send_json_error(array('message' => __('Název klubu je povinný.', 'tipnijinak')));
    }

    // Kontrola, zda klub s podobným názvem už existuje (částečná shoda)
    $force_create = isset($_POST['force_create']) && $_POST['force_create'] === '1';
    $existing = new WP_Query(array(
        'post_type' => 'tym',
        's' => $club_title,
        'post_status' => 'publish',
        'posts_per_page' => 5,
    ));
    if (!$force_create && $existing->have_posts()) {
        $suggestions = array();
        while ($existing->have_posts()) {
            $existing->the_post();
            $suggestions[] = array(
                'id' => get_the_ID(),
                'name' => get_the_title(),
            );
        }
        wp_reset_postdata();
        wp_send_json_error(array(
            'message' => __('Našli jsme podobné kluby. Vyberte existující klub nebo pokračujte v přidání nového.', 'tipnijinak'),
            'similar_clubs' => $suggestions,
        ));
    }

    // Vytvoření nového klubu - jako pending, admin musí schválit
    $club_id = wp_insert_post(array(
        'post_type'   => 'tym',
        'post_title'  => $club_title,
        'post_status' => 'pending',
    ));

    if (is_wp_error($club_id)) {
        wp_send_json_error(array('message' => __('Nepodařilo se vytvořit klub.', 'tipnijinak')));
    }

    // Uložení zkratky
    if (!empty($club_abbr)) {
        update_post_meta($club_id, 'zkratka', $club_abbr);
    }

    // Přiřazení ligy
    if ($club_league > 0) {
        wp_set_object_terms($club_id, $club_league, 'liga');
    }

    // Upload loga pokud bylo odesláno
    if (!empty($_FILES['club_logo']['name'])) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $attachment_id = media_handle_upload('club_logo', $club_id);

        if (!is_wp_error($attachment_id)) {
            set_post_thumbnail($club_id, $attachment_id);
        }
    }

    wp_send_json_success(array(
        'club_id' => $club_id,
        'message' => __('Klub byl úspěšně vytvořen.', 'tipnijinak'),
    ));
}