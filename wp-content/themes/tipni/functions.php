<?php
/**
 * Tipni Jinak functions and definitions
 *
 * @package TipniJinak
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Define theme version - use timestamp for cache busting during development
define( 'TIPNIJINAK_VERSION', time() );

/**
 * Include required files
 */
require_once get_template_directory() . '/inc/template-functions.php';
// Include shop functionality
require_once get_template_directory() . '/inc/shop/shop-functions.php';
require_once get_template_directory() . '/inc/shop/woocommerce-hooks.php';
// Include admin files
if ( is_admin() ) {
    require_once get_template_directory() . '/inc/admin/class-match-import.php';
    require_once get_template_directory() . '/inc/admin/admin-columns.php';
    require_once get_template_directory() . '/inc/admin/kolo-validation.php';
    require_once get_template_directory() . '/inc/admin/match-auto-evaluate.php';
}

/**
 * Automatické odhlášení bez potvrzovací stránky
 */
function tipnijinak_logout_without_confirmation() {
    if ( isset( $_GET['action'] ) && $_GET['action'] === 'logout' && isset( $_GET['_wpnonce'] ) ) {
        // Ověření nonce
        if ( wp_verify_nonce( $_GET['_wpnonce'], 'log-out' ) ) {
            wp_logout();
            wp_safe_redirect( home_url() );
            exit;
        }
    }
}
add_action( 'init', 'tipnijinak_logout_without_confirmation' );

/**
 * Enqueue scripts and styles.
 */
function tipnijinak_scripts() {
    // Enqueue jQuery from WordPress core
    wp_enqueue_script('jquery');
    
    // Enqueue main stylesheet
    wp_enqueue_style( 'tipnijinak-style', get_stylesheet_uri(), array(), TIPNIJINAK_VERSION );
    
    // Enqueue custom stylesheet
    wp_enqueue_style( 'tipnijinak-main-style', get_template_directory_uri() . '/assets/css/style.css', array(), TIPNIJINAK_VERSION );
    
    // Enqueue competition-specific styles on single competition page
    if (is_singular('soutez')) {
        wp_enqueue_style( 'tipnijinak-single-competition', get_template_directory_uri() . '/assets/css/single-competition.css', array('tipnijinak-main-style'), TIPNIJINAK_VERSION );
    }
    
    // Enqueue custom scripts
    wp_enqueue_script( 'tipnijinak-main-js', get_template_directory_uri() . '/assets/js/main.js', array('jquery'), TIPNIJINAK_VERSION, true );
    
    // Enqueue login script only on login page
    if (is_page('prihlaseni') || is_page('prihlasit-se')) {
        wp_enqueue_script( 'tipnijinak-login-js', get_template_directory_uri() . '/assets/js/login.js', array('jquery'), TIPNIJINAK_VERSION, true );
        wp_localize_script( 'tipnijinak-login-js', 'tipnijinak_ajax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'error_text' => __('Chyba', 'tipnijinak'),
            'server_error' => __('Došlo k chybě při komunikaci se serverem. Zkuste to prosím znovu.', 'tipnijinak')
        ));
    }
    
    // Enqueue single-soutez script only on single competition page
    if (is_singular('soutez')) {
        wp_enqueue_script( 'tipnijinak-single-soutez', get_template_directory_uri() . '/assets/js/single-soutez.js', array('jquery'), TIPNIJINAK_VERSION, true );
    }
}
add_action( 'wp_enqueue_scripts', 'tipnijinak_scripts' );

/**
 * Register navigation menus
 */
function tipnijinak_register_menus() {
    register_nav_menus(
        array(
            'primary' => __( 'Primary Menu', 'tipnijinak' ),
            'footer'  => __( 'Footer Menu', 'tipnijinak' ),
            'copyright' => __( 'Copyright Menu', 'tipnijinak' ),
        )
    );
}
add_action( 'init', 'tipnijinak_register_menus' );

/**
 * Theme setup function
 */
function tipnijinak_setup() {
    /*
     * Make theme available for translation.
     */
    load_theme_textdomain( 'tipnijinak', get_template_directory() . '/languages' );

    // Add default posts and comments RSS feed links to head.
    add_theme_support( 'automatic-feed-links' );

    /*
     * Let WordPress manage the document title.
     */
    add_theme_support( 'title-tag' );

    /*
     * Enable support for Post Thumbnails on posts and pages.
     */
    add_theme_support( 'post-thumbnails' );

    // Set up the WordPress core custom logo feature.
    add_theme_support(
        'custom-logo',
        array(
            'height'      => 100,
            'width'       => 350,
            'flex-width'  => true,
            'flex-height' => true,
        )
    );
    
    // Add WooCommerce support
    add_theme_support( 'woocommerce' );
    add_theme_support( 'wc-product-gallery-zoom' );
    add_theme_support( 'wc-product-gallery-lightbox' );
    add_theme_support( 'wc-product-gallery-slider' );
}
add_action( 'after_setup_theme', 'tipnijinak_setup' );

/**
 * Register Custom Post Types
 */
function tipnijinak_register_post_types() {
    // Soutěž CPT
    $labels = array(
        'name'               => _x( 'Soutěže', 'post type general name', 'tipnijinak' ),
        'singular_name'      => _x( 'Soutěž', 'post type singular name', 'tipnijinak' ),
        'menu_name'          => _x( 'Soutěže', 'admin menu', 'tipnijinak' ),
        'name_admin_bar'     => _x( 'Soutěž', 'add new on admin bar', 'tipnijinak' ),
        'add_new'            => _x( 'Přidat novou', 'soutěž', 'tipnijinak' ),
        'add_new_item'       => __( 'Přidat novou soutěž', 'tipnijinak' ),
        'new_item'           => __( 'Nová soutěž', 'tipnijinak' ),
        'edit_item'          => __( 'Upravit soutěž', 'tipnijinak' ),
        'view_item'          => __( 'Zobrazit soutěž', 'tipnijinak' ),
        'all_items'          => __( 'Všechny soutěže', 'tipnijinak' ),
        'search_items'       => __( 'Hledat soutěže', 'tipnijinak' ),
        'parent_item_colon'  => __( 'Nadřazená soutěž:', 'tipnijinak' ),
        'not_found'          => __( 'Žádné soutěže nenalezeny.', 'tipnijinak' ),
        'not_found_in_trash' => __( 'Žádné soutěže v koši.', 'tipnijinak' )
    );

    $args = array(
        'labels'             => $labels,
        'description'        => __( 'Soutěže pro tipování.', 'tipnijinak' ),
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'soutez' ),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        'menu_icon'          => 'dashicons-awards',
    );

    register_post_type( 'soutez', $args );
    
    // Kolo CPT
    $labels = array(
        'name'               => _x( 'Kola', 'post type general name', 'tipnijinak' ),
        'singular_name'      => _x( 'Kolo', 'post type singular name', 'tipnijinak' ),
        'menu_name'          => _x( 'Kola', 'admin menu', 'tipnijinak' ),
        'name_admin_bar'     => _x( 'Kolo', 'add new on admin bar', 'tipnijinak' ),
        'add_new'            => _x( 'Přidat nové', 'kolo', 'tipnijinak' ),
        'add_new_item'       => __( 'Přidat nové kolo', 'tipnijinak' ),
        'new_item'           => __( 'Nové kolo', 'tipnijinak' ),
        'edit_item'          => __( 'Upravit kolo', 'tipnijinak' ),
        'view_item'          => __( 'Zobrazit kolo', 'tipnijinak' ),
        'all_items'          => __( 'Všechna kola', 'tipnijinak' ),
        'search_items'       => __( 'Hledat kola', 'tipnijinak' ),
        'parent_item_colon'  => __( 'Nadřazené kolo:', 'tipnijinak' ),
        'not_found'          => __( 'Žádná kola nenalezena.', 'tipnijinak' ),
        'not_found_in_trash' => __( 'Žádná kola v koši.', 'tipnijinak' )
    );

    $args = array(
        'labels'             => $labels,
        'description'        => __( 'Kola soutěže pro tipování.', 'tipnijinak' ),
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        // Změna query_var z 'kolo' na 'kolo_post' - aby nedocházelo ke konfliktu
        // s URL parametrem ?kolo=X používaným na stránce soutěže pro výběr kola
        'query_var'          => 'kolo_post',
        'rewrite'            => array( 'slug' => 'kolo' ),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array( 'title' ),
        'menu_icon'          => 'dashicons-calendar-alt',
    );

    register_post_type( 'kolo', $args );
    
    // Zápasy CPT
    $labels = array(
        'name'               => _x( 'Zápasy', 'post type general name', 'tipnijinak' ),
        'singular_name'      => _x( 'Zápas', 'post type singular name', 'tipnijinak' ),
        'menu_name'          => _x( 'Zápasy', 'admin menu', 'tipnijinak' ),
        'name_admin_bar'     => _x( 'Zápas', 'add new on admin bar', 'tipnijinak' ),
        'add_new'            => _x( 'Přidat nový', 'zápas', 'tipnijinak' ),
        'add_new_item'       => __( 'Přidat nový zápas', 'tipnijinak' ),
        'new_item'           => __( 'Nový zápas', 'tipnijinak' ),
        'edit_item'          => __( 'Upravit zápas', 'tipnijinak' ),
        'view_item'          => __( 'Zobrazit zápas', 'tipnijinak' ),
        'all_items'          => __( 'Všechny zápasy', 'tipnijinak' ),
        'search_items'       => __( 'Hledat zápasy', 'tipnijinak' ),
        'parent_item_colon'  => __( 'Nadřazený zápas:', 'tipnijinak' ),
        'not_found'          => __( 'Žádné zápasy nenalezeny.', 'tipnijinak' ),
        'not_found_in_trash' => __( 'Žádné zápasy v koši.', 'tipnijinak' )
    );

    $args = array(
        'labels'             => $labels,
        'description'        => __( 'Zápasy pro tipování.', 'tipnijinak' ),
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'zapas' ),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array( 'title', 'editor', 'thumbnail' ),
        'menu_icon'          => 'dashicons-tickets-alt',
    );

    register_post_type( 'zapas', $args );
    
    // Tým CPT
    $labels = array(
        'name'               => _x( 'Týmy', 'post type general name', 'tipnijinak' ),
        'singular_name'      => _x( 'Tým', 'post type singular name', 'tipnijinak' ),
        'menu_name'          => _x( 'Týmy', 'admin menu', 'tipnijinak' ),
        'name_admin_bar'     => _x( 'Tým', 'add new on admin bar', 'tipnijinak' ),
        'add_new'            => _x( 'Přidat nový', 'tým', 'tipnijinak' ),
        'add_new_item'       => __( 'Přidat nový tým', 'tipnijinak' ),
        'new_item'           => __( 'Nový tým', 'tipnijinak' ),
        'edit_item'          => __( 'Upravit tým', 'tipnijinak' ),
        'view_item'          => __( 'Zobrazit tým', 'tipnijinak' ),
        'all_items'          => __( 'Všechny týmy', 'tipnijinak' ),
        'search_items'       => __( 'Hledat týmy', 'tipnijinak' ),
        'parent_item_colon'  => __( 'Nadřazený tým:', 'tipnijinak' ),
        'not_found'          => __( 'Žádné týmy nenalezeny.', 'tipnijinak' ),
        'not_found_in_trash' => __( 'Žádné týmy v koši.', 'tipnijinak' )
    );

    $args = array(
        'labels'             => $labels,
        'description'        => __( 'Týmy pro zápasy.', 'tipnijinak' ),
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'tym' ),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array( 'title', 'thumbnail' ),
        'menu_icon'          => 'dashicons-groups',
    );

    register_post_type( 'tym', $args );
    
    // User Tip CPT
    $labels = array(
        'name'               => _x( 'Tipy uživatelů', 'post type general name', 'tipnijinak' ),
        'singular_name'      => _x( 'Tip uživatele', 'post type singular name', 'tipnijinak' ),
        'menu_name'          => _x( 'Tipy uživatelů', 'admin menu', 'tipnijinak' ),
        'name_admin_bar'     => _x( 'Tip uživatele', 'add new on admin bar', 'tipnijinak' ),
        'add_new'            => _x( 'Přidat nový', 'tip', 'tipnijinak' ),
        'add_new_item'       => __( 'Přidat nový tip', 'tipnijinak' ),
        'new_item'           => __( 'Nový tip', 'tipnijinak' ),
        'edit_item'          => __( 'Upravit tip', 'tipnijinak' ),
        'view_item'          => __( 'Zobrazit tip', 'tipnijinak' ),
        'all_items'          => __( 'Všechny tipy', 'tipnijinak' ),
        'search_items'       => __( 'Hledat tipy', 'tipnijinak' ),
        'parent_item_colon'  => __( 'Nadřazený tip:', 'tipnijinak' ),
        'not_found'          => __( 'Žádné tipy nenalezeny.', 'tipnijinak' ),
        'not_found_in_trash' => __( 'Žádné tipy v koši.', 'tipnijinak' )
    );

    $args = array(
        'labels'             => $labels,
        'description'        => __( 'Tipy uživatelů pro zápasy.', 'tipnijinak' ),
        'public'             => false,
        'publicly_queryable' => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => false,
        'rewrite'            => false,
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array( 'title' ),
        'menu_icon'          => 'dashicons-chart-area',
    );

    register_post_type( 'user_tip', $args );
}
add_action( 'init', 'tipnijinak_register_post_types' );

/**
 * Register taxonomies for CPT
 */
function tipnijinak_register_taxonomies() {
    // Liga taxonomy
    $labels = array(
        'name'              => _x( 'Ligy', 'taxonomy general name', 'tipnijinak' ),
        'singular_name'     => _x( 'Liga', 'taxonomy singular name', 'tipnijinak' ),
        'search_items'      => __( 'Hledat ligy', 'tipnijinak' ),
        'all_items'         => __( 'Všechny ligy', 'tipnijinak' ),
        'parent_item'       => __( 'Nadřazená liga', 'tipnijinak' ),
        'parent_item_colon' => __( 'Nadřazená liga:', 'tipnijinak' ),
        'edit_item'         => __( 'Upravit ligu', 'tipnijinak' ),
        'update_item'       => __( 'Aktualizovat ligu', 'tipnijinak' ),
        'add_new_item'      => __( 'Přidat novou ligu', 'tipnijinak' ),
        'new_item_name'     => __( 'Nový název ligy', 'tipnijinak' ),
        'menu_name'         => __( 'Ligy', 'tipnijinak' ),
    );

    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'liga' ),
    );

    register_taxonomy( 'liga', array( 'zapas', 'tym' ), $args );
    
    // Typ soutěže taxonomy
    $labels = array(
        'name'              => _x( 'Typy soutěží', 'taxonomy general name', 'tipnijinak' ),
        'singular_name'     => _x( 'Typ soutěže', 'taxonomy singular name', 'tipnijinak' ),
        'search_items'      => __( 'Hledat typy soutěží', 'tipnijinak' ),
        'all_items'         => __( 'Všechny typy soutěží', 'tipnijinak' ),
        'parent_item'       => __( 'Nadřazený typ soutěže', 'tipnijinak' ),
        'parent_item_colon' => __( 'Nadřazený typ soutěže:', 'tipnijinak' ),
        'edit_item'         => __( 'Upravit typ soutěže', 'tipnijinak' ),
        'update_item'       => __( 'Aktualizovat typ soutěže', 'tipnijinak' ),
        'add_new_item'      => __( 'Přidat nový typ soutěže', 'tipnijinak' ),
        'new_item_name'     => __( 'Nový název typu soutěže', 'tipnijinak' ),
        'menu_name'         => __( 'Typy soutěží', 'tipnijinak' ),
    );

    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'typ-souteze' ),
    );

    register_taxonomy( 'typ-souteze', array( 'soutez' ), $args );
}
add_action( 'init', 'tipnijinak_register_taxonomies' );

/**
 * Register ACF fields if ACF exists
 */
function tipnijinak_register_acf_fields() {
    if (!function_exists('acf_add_local_field_group')) {
        return; // ACF is not active
    }

    // Zde budeme registrovat pole pro ACF
    // Příklad polí pro zápasy
    acf_add_local_field_group(array(
        'key' => 'group_zapas',
        'title' => 'Informace o zápasu',
        'fields' => array(
            array(
                'key' => 'field_domaci_tym',
                'label' => 'Domácí tým',
                'name' => 'domaci_tym',
                'type' => 'post_object',
                'instructions' => '',
                'required' => 1,
                'conditional_logic' => 0,
                'post_type' => array(
                    0 => 'tym',
                ),
                'return_format' => 'id',
            ),
            array(
                'key' => 'field_hoste_tym',
                'label' => 'Hostující tým',
                'name' => 'hoste_tym',
                'type' => 'post_object',
                'instructions' => '',
                'required' => 1,
                'conditional_logic' => 0,
                'post_type' => array(
                    0 => 'tym',
                ),
                'return_format' => 'id',
            ),
            array(
                'key' => 'field_datum_zapasu',
                'label' => 'Datum zápasu',
                'name' => 'datum_zapasu',
                'type' => 'date_time_picker',
                'instructions' => '',
                'required' => 1,
                'conditional_logic' => 0,
                'display_format' => 'd.m.Y H:i',
                'return_format' => 'd.m.Y H:i',
                'first_day' => 1,
            ),
            array(
                'key' => 'field_skore_domaci',
                'label' => 'Skóre domácí',
                'name' => 'skore_domaci',
                'type' => 'number',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'default_value' => 0,
                'min' => 0,
                'max' => '',
                'step' => 1,
            ),
            array(
                'key' => 'field_skore_hoste',
                'label' => 'Skóre hosté',
                'name' => 'skore_hoste',
                'type' => 'number',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'default_value' => 0,
                'min' => 0,
                'max' => '',
                'step' => 1,
            ),
            array(
                'key' => 'field_stav_zapasu',
                'label' => 'Stav zápasu',
                'name' => 'stav_zapasu',
                'type' => 'select',
                'instructions' => '',
                'required' => 1,
                'conditional_logic' => 0,
                'choices' => array(
                    'planovany' => 'Plánovaný',
                    'probihajici' => 'Probíhající',
                    'ukonceny' => 'Ukončený',
                    'zrusen' => 'Zrušený',
                ),
                'default_value' => 'planovany',
                'return_format' => 'value',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'zapas',
                ),
            ),
        ),
    ));
    
    // Pole pro soutěže
    acf_add_local_field_group(array(
        'key' => 'group_soutez',
        'title' => 'Informace o soutěži',
        'fields' => array(
            array(
                'key' => 'field_je_hlavni',
                'label' => 'Hlavní soutěž',
                'name' => 'je_hlavni',
                'type' => 'true_false',
                'instructions' => 'Zaškrtněte, pokud se jedná o hlavní soutěž.',
                'required' => 0,
                'conditional_logic' => 0,
                'ui' => 1,
                'default_value' => 0,
            ),
            array(
                'key' => 'field_pocet_bodu',
                'label' => 'Počet bodů',
                'name' => 'pocet_bodu',
                'type' => 'number',
                'instructions' => 'Počet bodů, které hráč získá za správný tip',
                'required' => 1,
                'conditional_logic' => 0,
                'default_value' => 30,
                'min' => 0,
                'max' => '',
                'step' => 1,
            ),
            array(
                'key' => 'field_zapasy_souteze',
                'label' => 'Zápasy soutěže',
                'name' => 'zapasy_souteze',
                'type' => 'relationship',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'post_type' => array(
                    0 => 'zapas',
                ),
                'filters' => array(
                    0 => 'search',
                ),
                'return_format' => 'id',
                'min' => '',
                'max' => '',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'soutez',
                ),
            ),
        ),
    ));
    
    // Pole pro týmy
    acf_add_local_field_group(array(
        'key' => 'group_tym',
        'title' => 'Informace o týmu',
        'fields' => array(
            array(
                'key' => 'field_logo_tymu',
                'label' => 'Logo týmu',
                'name' => 'logo_tymu',
                'type' => 'image',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'return_format' => 'id',
                'preview_size' => 'medium',
                'library' => 'all',
                'min_width' => '',
                'min_height' => '',
                'min_size' => '',
                'max_width' => '',
                'max_height' => '',
                'max_size' => '',
                'mime_types' => '',
            ),
            array(
                'key' => 'field_zkratka_tymu',
                'label' => 'Zkratka týmu',
                'name' => 'zkratka_tymu',
                'type' => 'text',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'default_value' => '',
                'maxlength' => 3,
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'tym',
                ),
            ),
        ),
    ));
}
add_action('acf/init', 'tipnijinak_register_acf_fields');

/**
 * Include template parts
 */
//require get_template_directory() . '/inc/template-functions.php';
require get_template_directory() . '/inc/class-copyright-menu-walker.php';

/**
 * Registrace a zpracování pro registrační formulář
 */
function tipnijinak_register_registration_scripts() {
    if (is_page_template('page-registrace.php')) {
        wp_enqueue_script('registration-script', get_template_directory_uri() . '/assets/js/registration.js', array('jquery'), TIPNIJINAK_VERSION, true);
        
        // Předání AJAX URL do skriptu
        wp_localize_script('registration-script', 'ajax_object', array(
            'ajax_url' => admin_url('admin-ajax.php'),
        ));
    }
}
add_action('wp_enqueue_scripts', 'tipnijinak_register_registration_scripts');

/**
 * AJAX handler pro zpracování registrace
 */
function tipnijinak_process_registration() {
    // Ověření nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'registrace_ajax')) {
        wp_send_json_error(array('message' => __('Neplatný bezpečnostní token.', 'tipnijinak')));
    }
    
    // Získání dat z AJAX požadavku
    $registration_data = isset($_POST['registration_data']) ? $_POST['registration_data'] : array();
    
    // Kontrola povinných údajů
    $required_fields = array('name', 'surname', 'mail', 'login', 'password', 'phone', 'terms_agreement');
    
    foreach ($required_fields as $field) {
        if (!isset($registration_data[$field]) || empty($registration_data[$field])) {
            wp_send_json_error(array('message' => __('Všechna povinná pole musí být vyplněna.', 'tipnijinak')));
        }
    }
    
    // Kontrola, zda uživatel už neexistuje
    if (username_exists($registration_data['login'])) {
        wp_send_json_error(array('message' => __('Uživatelské jméno již existuje. Zvolte prosím jiné.', 'tipnijinak')));
    }
    
    if (email_exists($registration_data['mail'])) {
        wp_send_json_error(array('message' => __('Email již existuje. Zvolte prosím jiný.', 'tipnijinak')));
    }
    
    // Vytvoření nového uživatele
    $user_data = array(
        'user_login'    => sanitize_user($registration_data['login']),
        'user_email'    => sanitize_email($registration_data['mail']),
        'user_pass'     => $registration_data['password'],
        'first_name'    => sanitize_text_field($registration_data['name']),
        'last_name'     => sanitize_text_field($registration_data['surname']),
        'display_name'  => sanitize_text_field($registration_data['name'] . ' ' . $registration_data['surname']),
        'role'          => 'subscriber',
    );
    
    $user_id = wp_insert_user($user_data);
    
    if (is_wp_error($user_id)) {
        wp_send_json_error(array('message' => $user_id->get_error_message()));
    }
    
    // Uložení dalších údajů jako user meta
    update_user_meta($user_id, 'address', sanitize_text_field($registration_data['address']));
    update_user_meta($user_id, 'city', sanitize_text_field($registration_data['city']));
    update_user_meta($user_id, 'psc', sanitize_text_field($registration_data['psc']));
    update_user_meta($user_id, 'phone', sanitize_text_field($registration_data['phone']));
    
    // Uložení fakturačních údajů do WooCommerce meta
    if (class_exists('WooCommerce')) {
        // Fakturační adresa
        update_user_meta($user_id, 'billing_first_name', sanitize_text_field($registration_data['name']));
        update_user_meta($user_id, 'billing_last_name', sanitize_text_field($registration_data['surname']));
        update_user_meta($user_id, 'billing_email', sanitize_email($registration_data['mail']));
        update_user_meta($user_id, 'billing_phone', sanitize_text_field($registration_data['phone']));
        
        if (isset($registration_data['address']) && !empty($registration_data['address'])) {
            update_user_meta($user_id, 'billing_address_1', sanitize_text_field($registration_data['address']));
        }
        
        if (isset($registration_data['city']) && !empty($registration_data['city'])) {
            update_user_meta($user_id, 'billing_city', sanitize_text_field($registration_data['city']));
        }
        
        if (isset($registration_data['psc']) && !empty($registration_data['psc'])) {
            update_user_meta($user_id, 'billing_postcode', sanitize_text_field($registration_data['psc']));
        }
        
        // Výchozí země (ČR)
        update_user_meta($user_id, 'billing_country', 'CZ');
        
        // Doručovací adresa (stejná jako fakturační)
        update_user_meta($user_id, 'shipping_first_name', sanitize_text_field($registration_data['name']));
        update_user_meta($user_id, 'shipping_last_name', sanitize_text_field($registration_data['surname']));
        
        if (isset($registration_data['address']) && !empty($registration_data['address'])) {
            update_user_meta($user_id, 'shipping_address_1', sanitize_text_field($registration_data['address']));
        }
        
        if (isset($registration_data['city']) && !empty($registration_data['city'])) {
            update_user_meta($user_id, 'shipping_city', sanitize_text_field($registration_data['city']));
        }
        
        if (isset($registration_data['psc']) && !empty($registration_data['psc'])) {
            update_user_meta($user_id, 'shipping_postcode', sanitize_text_field($registration_data['psc']));
        }
        
        update_user_meta($user_id, 'shipping_country', 'CZ');
    }
    
    // Uložení výběru klubu
    if (isset($registration_data['selected_club']) && !empty($registration_data['selected_club'])) {
        update_user_meta($user_id, 'club_id', intval($registration_data['selected_club']));
    }
    
    // Uložení názvu klubu
    if (isset($registration_data['club_name']) && !empty($registration_data['club_name'])) {
        update_user_meta($user_id, 'club_name', sanitize_text_field($registration_data['club_name']));
    }
    
    // Uložení textu vyhledávání klubu (pro případ, že klub nebyl vybrán ze seznamu)
    if (isset($registration_data['club_search']) && !empty($registration_data['club_search'])) {
        update_user_meta($user_id, 'club_search', sanitize_text_field($registration_data['club_search']));
    }
    
    // Uložení souhlasů
    if (isset($registration_data['terms_agreement'])) {
        $terms_agreement = ($registration_data['terms_agreement'] === true || $registration_data['terms_agreement'] === 'true') ? 'yes' : 'no';
        update_user_meta($user_id, 'terms_agreement', $terms_agreement);
    }
    
    if (isset($registration_data['marketing_agreement'])) {
        $marketing_agreement = ($registration_data['marketing_agreement'] === true || $registration_data['marketing_agreement'] === 'true') ? 'yes' : 'no';
        update_user_meta($user_id, 'marketing_agreement', $marketing_agreement);
    }
    
    // Automatické přihlášení uživatele
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id, true, is_ssl());

    // Odeslání úspěšné odpovědi - nyní nepřesměrováváme, zobrazíme čtvrtý krok s produkty
    wp_send_json_success(array(
        'message'      => __('Registrace byla úspěšná.', 'tipnijinak'),
        'user_id'      => $user_id,
    ));
}
add_action('wp_ajax_nopriv_process_registration', 'tipnijinak_process_registration');
add_action('wp_ajax_process_registration', 'tipnijinak_process_registration');

/**
 * AJAX handler pro vyhledávání klubů
 */
function tipnijinak_search_clubs() {
    // Ověření nonce
    if (!isset($_POST['nonce']) || (!wp_verify_nonce($_POST['nonce'], 'ajax_nonce') && !wp_verify_nonce($_POST['nonce'], 'registrace_ajax'))) {
        wp_send_json_error(array('message' => __('Neplatný bezpečnostní token.', 'tipnijinak')));
    }
    
    // Získání vyhledávacího výrazu
    $search_term = isset($_POST['search_term']) ? sanitize_text_field($_POST['search_term']) : '';
    
    if (empty($search_term)) {
        wp_send_json_error(array('message' => __('Nebyl zadán hledaný výraz.', 'tipnijinak')));
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
        'post_type' => 'tym',
        'posts_per_page' => 10,
        'post_status' => 'publish',
        's' => $search_term,
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
            $club_id = get_the_ID();
            $logo_id = get_field('logo_tymu', $club_id);
            $logo_url = '';
            
            // Pokud máme ID obrázku, získáme URL
            if (!empty($logo_id) && is_numeric($logo_id)) {
                $logo_url = wp_get_attachment_url($logo_id);
            }
            
            $clubs[] = array(
                'id' => $club_id,
                'name' => get_the_title(),
                'logo' => $logo_url,
            );
        }
        wp_reset_postdata();
    }
    
    // Odeslání výsledků
    wp_send_json_success(array(
        'clubs' => $clubs,
    ));
}
add_action('wp_ajax_nopriv_search_clubs', 'tipnijinak_search_clubs');
add_action('wp_ajax_search_clubs', 'tipnijinak_search_clubs');

/**
 * AJAX handler pro vytvoření nového klubu
 */
function tipnijinak_create_new_club() {
    // Ověření nonce - akceptuje 'ajax_nonce' (profil) i 'registrace_ajax' (registrace)
    $nonce_valid = false;
    if (isset($_POST['nonce'])) {
        if (wp_verify_nonce($_POST['nonce'], 'ajax_nonce') || wp_verify_nonce($_POST['nonce'], 'registrace_ajax')) {
            $nonce_valid = true;
        }
    }

    if (!$nonce_valid) {
        wp_send_json_error(array('message' => __('Neplatný bezpečnostní token.', 'tipnijinak')));
    }

    // Získání dat z formuláře
    $club_title = isset($_POST['club_title']) ? sanitize_text_field($_POST['club_title']) : '';
    $club_abbr = isset($_POST['club_abbr']) ? sanitize_text_field($_POST['club_abbr']) : '';
    $club_league = isset($_POST['club_league']) ? intval($_POST['club_league']) : 0;
    
    // Validace
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
            $logo_id = get_field('logo_tymu', get_the_ID());
            $logo_url = (!empty($logo_id) && is_numeric($logo_id)) ? wp_get_attachment_url($logo_id) : '';
            $suggestions[] = array(
                'id' => get_the_ID(),
                'name' => get_the_title(),
                'logo' => $logo_url,
            );
        }
        wp_reset_postdata();
        wp_send_json_error(array(
            'message' => __('Našli jsme podobné kluby. Vyberte existující klub nebo pokračujte v přidání nového.', 'tipnijinak'),
            'similar_clubs' => $suggestions,
        ));
    }

    // Force create = pending (admin schválí), jinak publish (žádná shoda nalezena)
    $new_status = $force_create ? 'pending' : 'publish';
    $club_id = wp_insert_post(array(
        'post_title'    => $club_title,
        'post_status'   => $new_status,
        'post_type'     => 'tym',
    ));
    
    // Kontrola, zda se post vytvořil
    if (is_wp_error($club_id)) {
        wp_send_json_error(array('message' => $club_id->get_error_message()));
    }
    
    // Zpracování nahraného loga klubu
    if (!empty($_FILES['club_logo']) && !empty($_FILES['club_logo']['name'])) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        // Nahrání souboru
        $attachment_id = media_handle_upload('club_logo', $club_id);
        
        if (!is_wp_error($attachment_id)) {
            // Uložit ID média do ACF pole logo_tymu
            update_field('logo_tymu', $attachment_id, $club_id);
        }
    }
    
    if (!empty($club_abbr)) {
        update_field('zkratka_tymu', $club_abbr, $club_id);
    }
    
    // Přidání taxonomie liga
    if (!empty($club_league)) {
        wp_set_object_terms($club_id, (int)$club_league, 'liga');
    }
    
    // Odeslání úspěšné odpovědi
    wp_send_json_success(array(
        'message' => __('Klub byl úspěšně vytvořen.', 'tipnijinak'),
        'club_id' => $club_id,
    ));
}
add_action('wp_ajax_create_new_club', 'tipnijinak_create_new_club');
add_action('wp_ajax_nopriv_create_new_club', 'tipnijinak_create_new_club');

/**
 * Načtení JS skriptu pro stránku profilu
 */
function tipnijinak_profile_scripts() {
    if (is_page_template('page-profil.php')) {
        // Načtení jQuery UI Autocomplete
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-autocomplete');
        
        // Načtení jQuery UI CSS a vlastních stylů pro profil
        wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
        wp_enqueue_style('profile-css', get_template_directory_uri() . '/assets/css/profile.css', array(), TIPNIJINAK_VERSION);
        
        // Načtení vlastního skriptu
        wp_enqueue_script('profile-script', get_template_directory_uri() . '/assets/js/profile.js', array('jquery', 'jquery-ui-autocomplete'), TIPNIJINAK_VERSION, true);
        
        // Předání AJAX URL a nonce do skriptu
        wp_localize_script('profile-script', 'ajax_object', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('ajax_nonce'),
        ));
    }
}
add_action('wp_enqueue_scripts', 'tipnijinak_profile_scripts');

/**
 * AJAX handler pro aktualizaci uživatelského profilu
 */
function tipnijinak_update_user_profile() {
    // Ověření nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ajax_nonce')) {
        wp_send_json_error(array('message' => __('Neplatný bezpečnostní token.', 'tipnijinak')));
    }
    
    // Kontrola, zda je uživatel přihlášen
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => __('Musíte být přihlášeni pro úpravu profilu.', 'tipnijinak')));
    }
    
    $user_id = get_current_user_id();
    $field = isset($_POST['field']) ? sanitize_text_field($_POST['field']) : '';
    
    if (empty($field)) {
        wp_send_json_error(array('message' => __('Nebyl zadán žádný parametr k aktualizaci.', 'tipnijinak')));
    }
    
    // Aktualizace různých polí podle typu
    switch ($field) {
        case 'first_name':
            $value = isset($_POST['value']) ? sanitize_text_field($_POST['value']) : '';
            update_user_meta($user_id, 'first_name', $value);
            break;
            
        case 'last_name':
            $value = isset($_POST['value']) ? sanitize_text_field($_POST['value']) : '';
            update_user_meta($user_id, 'last_name', $value);
            break;
            
        case 'email':
            $value = isset($_POST['value']) ? sanitize_email($_POST['value']) : '';
            
            // Kontrola, zda email již neexistuje
            if (email_exists($value) && email_exists($value) !== $user_id) {
                wp_send_json_error(array('message' => __('Tento email je již používán jiným uživatelem.', 'tipnijinak')));
            }
            
            // Aktualizace emailu
            $user_data = array(
                'ID'         => $user_id,
                'user_email' => $value,
            );
            
            $result = wp_update_user($user_data);
            
            if (is_wp_error($result)) {
                wp_send_json_error(array('message' => $result->get_error_message()));
            }
            break;
            
        case 'phone':
            $value = isset($_POST['value']) ? sanitize_text_field($_POST['value']) : '';
            update_user_meta($user_id, 'phone', $value);
            
            if (class_exists('WooCommerce')) {
                update_user_meta($user_id, 'billing_phone', $value);
            }
            break;
            
        case 'address':
            $address = isset($_POST['address']) ? sanitize_text_field($_POST['address']) : '';
            $city = isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '';
            $psc = isset($_POST['psc']) ? sanitize_text_field($_POST['psc']) : '';
            
            update_user_meta($user_id, 'address', $address);
            update_user_meta($user_id, 'city', $city);
            update_user_meta($user_id, 'psc', $psc);
            
            if (class_exists('WooCommerce')) {
                update_user_meta($user_id, 'billing_address_1', $address);
                update_user_meta($user_id, 'billing_city', $city);
                update_user_meta($user_id, 'billing_postcode', $psc);
                update_user_meta($user_id, 'shipping_address_1', $address);
                update_user_meta($user_id, 'shipping_city', $city);
                update_user_meta($user_id, 'shipping_postcode', $psc);
            }
            break;
            
        case 'terms_agreement':
        case 'marketing_agreement':
            $value = isset($_POST['value']) ? sanitize_text_field($_POST['value']) : 'no';
            update_user_meta($user_id, $field, $value);
            break;
            
        default:
            wp_send_json_error(array('message' => __('Neznámý parametr k aktualizaci.', 'tipnijinak')));
            break;
    }
    
    wp_send_json_success(array('message' => __('Profil byl úspěšně aktualizován.', 'tipnijinak')));
}
add_action('wp_ajax_update_user_profile', 'tipnijinak_update_user_profile');

/**
 * AJAX handler pro aktualizaci klubu uživatele
 */
function tipnijinak_update_user_club() {
    // Ověření nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ajax_nonce')) {
        wp_send_json_error(array('message' => __('Neplatný bezpečnostní token.', 'tipnijinak')));
    }
    
    // Kontrola, zda je uživatel přihlášen
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => __('Musíte být přihlášeni pro úpravu profilu.', 'tipnijinak')));
    }
    
    $user_id = get_current_user_id();
    $club_id = isset($_POST['club_id']) ? intval($_POST['club_id']) : 0;
    $club_name = isset($_POST['club_name']) ? sanitize_text_field($_POST['club_name']) : '';
    
    if (empty($club_name)) {
        wp_send_json_error(array('message' => __('Nebyl vybrán žádný klub.', 'tipnijinak')));
    }
    
    // Aktualizace klubu
    update_user_meta($user_id, 'club_id', $club_id);
    update_user_meta($user_id, 'club_name', $club_name);
    update_user_meta($user_id, 'club_search', $club_name);
    
    wp_send_json_success(array('message' => __('Klub byl úspěšně aktualizován.', 'tipnijinak')));
}
add_action('wp_ajax_update_user_club', 'tipnijinak_update_user_club');

/**
 * AJAX handler pro vytvoření objednávky WooCommerce
 */
function tipnijinak_create_woo_order() {
    // Ověření, zda je WooCommerce aktivní
    if (!class_exists('WooCommerce')) {
        wp_send_json_error(array('message' => __('WooCommerce není aktivní.', 'tipnijinak')));
    }

    // Ověření nonce     -- dořešit 
  /*  if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'registrace_ajax')) {
        wp_send_json_error(array('message' => __('Neplatný bezpečnostní token.', 'tipnijinak')));
    }
    */
    // Získání dat z požadavku
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : 'bacs';
    $customer_data = isset($_POST['customer_data']) ? $_POST['customer_data'] : array();
    
    // Kontrola, zda produkt existuje
    if (!$product_id || !wc_get_product($product_id)) {
        wp_send_json_error(array('message' => __('Vybraný produkt neexistuje.', 'tipnijinak')));
    }
    
    // Kontrola, zda je uživatel přihlášen
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => __('Pro vytvoření objednávky musíte být přihlášeni.', 'tipnijinak')));
    }
    
    $current_user = wp_get_current_user();
    
    // Vytvoření objednávky
    try {
        // Vytvoření instance objednávky
        $order = wc_create_order(array(
            'customer_id' => $current_user->ID,
        ));
        
        // Přidání produktu do objednávky
        $order->add_product(wc_get_product($product_id), 1);
        
        // Nastavení fakturační adresy
        $order->set_billing_first_name($customer_data['name']);
        $order->set_billing_last_name($customer_data['surname']);
        $order->set_billing_email($customer_data['mail']);
        $order->set_billing_phone($customer_data['phone']);
        
        if (isset($customer_data['address'])) {
            $order->set_billing_address_1($customer_data['address']);
        }
        
        if (isset($customer_data['city'])) {
            $order->set_billing_city($customer_data['city']);
        }
        
        if (isset($customer_data['psc'])) {
            $order->set_billing_postcode($customer_data['psc']);
        }
        
        // Nastavení země (výchozí ČR)
        $order->set_billing_country('CZ');
        
        // Nastavení doručovací adresy stejné jako fakturační
        $order->set_shipping_first_name($customer_data['name']);
        $order->set_shipping_last_name($customer_data['surname']);
        
        if (isset($customer_data['address'])) {
            $order->set_shipping_address_1($customer_data['address']);
        }
        
        if (isset($customer_data['city'])) {
            $order->set_shipping_city($customer_data['city']);
        }
        
        if (isset($customer_data['psc'])) {
            $order->set_shipping_postcode($customer_data['psc']);
        }
        
        $order->set_shipping_country('CZ');
        
        // Nastavení způsobu platby
        $order->set_payment_method($payment_method);
        
        // Výpočet celkové částky
        $order->calculate_totals();
        
        // Přidání poznámky
        $order->add_order_note(__('Objednávka vytvořena přes registrační formulář.', 'tipnijinak'));
        
        // Uložení objednávky
        $order->save();
        
        // Určení URL pro přesměrování
        if ($payment_method === 'card') {
            // Pro platbu kartou přesměrujeme na stránku s platební bránou
            $redirect_url = $order->get_checkout_payment_url();
        } else {
            // Pro ostatní platby na stránku s potvrzením objednávky
            $redirect_url = $order->get_checkout_order_received_url();
        }
        
        wp_send_json_success(array(
            'message' => __('Objednávka byla úspěšně vytvořena.', 'tipnijinak'),
            'order_id' => $order->get_id(),
            'redirect_url' => $redirect_url,
        ));
        
    } catch (Exception $e) {
        wp_send_json_error(array('message' => $e->getMessage()));
    }
}
add_action('wp_ajax_create_woo_order', 'tipnijinak_create_woo_order');
add_action('wp_ajax_nopriv_create_woo_order', 'tipnijinak_create_woo_order');

/**
 * Add ACF JSON save point
 */
function tipnijinak_acf_json_save_point($path) {
    return get_template_directory() . '/acf-json';
}
add_filter('acf/settings/save_json', 'tipnijinak_acf_json_save_point');

/**
 * Add ACF JSON load point
 */
function tipnijinak_acf_json_load_point($paths) {
    $paths[] = get_template_directory() . '/acf-json';
    return $paths;
}
add_filter('acf/settings/load_json', 'tipnijinak_acf_json_load_point');

/**
 * Přesměrování standardního WordPress přihlašování na vlastní stránku
 */
function tipnijinak_login_page_redirect() {
    // Přesměrování wp-login.php na vlastní stránku
    $login_page = home_url('/prihlasit-se/');
    
    // Nezachytávat odhlášení a resetování hesla
    global $pagenow;
    if ('wp-login.php' === $pagenow && !isset($_GET['action']) && !isset($_GET['loggedout'])) {
        wp_redirect($login_page);
        exit;
    }
}
add_action('init', 'tipnijinak_login_page_redirect');

/**
 * AJAX handler pro přihlášení uživatele
 */
function tipnijinak_ajax_login() {
    // První kontrola - ověřit nonce
    check_ajax_referer('ajax-login-nonce', 'security');
    
    $info = array();
    $info['user_login'] = sanitize_user($_POST['user_login']);
    $info['user_password'] = $_POST['user_pass'];
    $info['remember'] = isset($_POST['remember']) ? true : false;
    
    // Zkusit přihlásit uživatele
    $user_signon = wp_signon($info, is_ssl());
    
    if (is_wp_error($user_signon)) {
        // Neúspěšné přihlášení - vrátit chybovou hlášku
        // Vrátit zprávu přesně tak, jak ji vrací WordPress, aby zůstaly zachované všechny odkazy a HTML formátování
        wp_send_json_error(array(
            'success' => false,
            'message' => $user_signon->get_error_message()
        ));
    } else {
        // Úspěšné přihlášení
        wp_set_current_user($user_signon->ID);
        wp_set_auth_cookie($user_signon->ID, $info['remember']);
        
        // Určit URL pro přesměrování
        $redirect_to = isset($_POST['redirect_to']) ? $_POST['redirect_to'] : home_url();
        
        wp_send_json_success(array(
            'success' => true,
            'message' => __('Přihlášení proběhlo úspěšně, přesměrováváme...', 'tipnijinak'),
            'redirect' => $redirect_to
        ));
        // wp_send_json_success() již obsahuje wp_die() uvnitř
    }

    // Ukončit zpracování pro případ neúspěchu
    wp_die();
}
// Registrovat AJAX handler pro nepřihlášené i přihlášené uživatele
add_action('wp_ajax_nopriv_tipnijinak_ajax_login', 'tipnijinak_ajax_login');
add_action('wp_ajax_tipnijinak_ajax_login', 'tipnijinak_ajax_login');

/**
 * Vytvoření databázové tabulky pro ukládání tipů uživatelů
 */
function tipnijinak_create_tips_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $table_name_tips = $wpdb->prefix . 'tipnijinak_tips';
    $table_name_points = $wpdb->prefix . 'tipnijinak_points';
    
    $tips_sql = "CREATE TABLE $table_name_tips (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        match_id bigint(20) NOT NULL,
        round_id bigint(20) NOT NULL,
        competition_id bigint(20) NOT NULL,
        tip varchar(1) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY user_match (user_id, match_id)
    ) $charset_collate;";
    
    $points_sql = "CREATE TABLE $table_name_points (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        match_id bigint(20) NOT NULL,
        round_id bigint(20) NOT NULL,
        competition_id bigint(20) NOT NULL,
        points int(11) NOT NULL DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY user_match (user_id, match_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($tips_sql);
    dbDelta($points_sql);
}
register_activation_hook(__FILE__, 'tipnijinak_create_tips_tables');

/**
 * AJAX handler pro ukládání tipů uživatelů
 */
function tipnijinak_save_user_tips() {
    // Ověření, zda je uživatel přihlášen
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => __('Pro tipování musíte být přihlášen.', 'tipnijinak')));
    }
    
    // Ověření nonce
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'tipnijinak_tips_nonce')) {
        wp_send_json_error(array('message' => __('Neplatný bezpečnostní token.', 'tipnijinak')));
    }
    
    $user_id = get_current_user_id();
    $tips = isset($_POST['tips']) ? json_decode(stripslashes($_POST['tips']), true) : array();
    $round_id = isset($_POST['round_id']) ? intval($_POST['round_id']) : 0;
    $competition_id = isset($_POST['competition_id']) ? intval($_POST['competition_id']) : 0;
    
    if (empty($tips) || !$round_id || !$competition_id) {
        wp_send_json_error(array('message' => __('Neplatná data.', 'tipnijinak')));
    }
    
    // Ověření přístupu uživatele k soutěži (pokud existuje propojená funkce)
    if (function_exists('tipnijinak_user_has_access_to_competition') && !tipnijinak_user_has_access_to_competition($competition_id)) {
        wp_send_json_error(array('message' => __('Nemáte přístup k této soutěži.', 'tipnijinak')));
    }
    
    // Ověření, zda je kolo otevřené pro tipování
    $round_status = get_field('stav_kola', $round_id);
    if ($round_status !== 'otevreno' && $round_status !== 'probihajici') {
        wp_send_json_error(array('message' => __('Kolo není otevřené pro tipování.', 'tipnijinak')));
    }
    
    $success_count = 0;
    $errors = array();
    
    // Podpora původního systému ukládání tipů do databáze
    if (defined('TIPNIJINAK_USE_DB_TIPS') && TIPNIJINAK_USE_DB_TIPS) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tipnijinak_tips';
        
        foreach ($tips as $tip) {
            $match_id = isset($tip['match_id']) ? intval($tip['match_id']) : 0;
            $value = isset($tip['value']) ? sanitize_text_field($tip['value']) : '';
            
            if (!$match_id || !in_array($value, array('1', '0', '2'))) {
                $errors[] = sprintf(__('Neplatný tip pro zápas ID %d.', 'tipnijinak'), $match_id);
                continue;
            }
            
            // Ověření, že zápas patří do daného kola
            $match_round = false;
            $zapasy_kola = get_field('zapasy_kola', $round_id);
            if (is_array($zapasy_kola) && in_array($match_id, $zapasy_kola)) {
                $match_round = true;
            }
            
            if (!$match_round) {
                $errors[] = sprintf(__('Zápas ID %d nepatří do vybraného kola.', 'tipnijinak'), $match_id);
                continue;
            }
            
            // Kontrola, zda už uživatel má tip pro tento zápas
            $existing_tip = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT tip FROM $table_name WHERE user_id = %d AND match_id = %d",
                    $user_id,
                    $match_id
                )
            );
            
            if ($existing_tip !== null) {
                $errors[] = sprintf(__('Tip pro zápas ID %d již byl uložen a nelze jej změnit.', 'tipnijinak'), $match_id);
                continue;
            }
            
            // Ukládání tipu do databáze
            $result = $wpdb->insert(
                $table_name,
                array(
                    'user_id' => $user_id,
                    'match_id' => $match_id,
                    'round_id' => $round_id,
                    'competition_id' => $competition_id,
                    'tip' => $value
                ),
                array(
                    '%d', // user_id
                    '%d', // match_id
                    '%d', // round_id
                    '%d', // competition_id
                    '%s'  // tip
                )
            );
            
            if ($result) {
                $success_count++;
            } else {
                $errors[] = sprintf(__('Chyba při ukládání tipu pro zápas ID %d.', 'tipnijinak'), $match_id);
            }
        }
    } else {
        // Použít nový systém ukládání tipů jako CPT
        foreach ($tips as $tip) {
            $match_id = isset($tip['match_id']) ? intval($tip['match_id']) : 0;
            $value = isset($tip['value']) ? sanitize_text_field($tip['value']) : '';
            
            if (!$match_id || !in_array($value, array('1', '0', '2'))) {
                $errors[] = sprintf(__('Neplatný tip pro zápas ID %d.', 'tipnijinak'), $match_id);
                continue;
            }
            
            // Ověření, že zápas patří do daného kola
            $match_round = false;
            $zapasy_kola = get_field('zapasy_kola', $round_id);
            if (is_array($zapasy_kola) && in_array($match_id, $zapasy_kola)) {
                $match_round = true;
            }
            
            if (!$match_round) {
                $errors[] = sprintf(__('Zápas ID %d nepatří do vybraného kola.', 'tipnijinak'), $match_id);
                continue;
            }

            // Ověření, že zápas je otevřený pro tipování (plánovaný)
            $match_status = get_field('stav_zapasu', $match_id);
            if (in_array($match_status, array('ukonceny', 'probihajici', 'zrusen'))) {
                $errors[] = sprintf(__('Na zápas ID %d již nelze tipovat.', 'tipnijinak'), $match_id);
                continue;
            }

            // Zkontrolovat, zda pro tohoto uživatele, zápas a soutěž už existuje tip
            $existing_tip_args = array(
                'post_type' => 'user_tip',
                'posts_per_page' => 1,
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => 'tip_user',
                        'value' => $user_id,
                    ),
                    array(
                        'key' => 'tip_match',
                        'value' => $match_id,
                    ),
                    array(
                        'key' => 'tip_competition',
                        'value' => $competition_id,
                    )
                )
            );
            
            $existing_tip_query = new WP_Query($existing_tip_args);
            $existing_tip_id = 0;
            
            if ($existing_tip_query->have_posts()) {
                $existing_tip_query->the_post();
                $existing_tip_id = get_the_ID();
                wp_reset_postdata();
            }
            
            if ($existing_tip_id) {
                // Tip už existuje, nelze jej změnit
                $errors[] = sprintf(__('Tip pro zápas ID %d již byl uložen a nelze jej změnit.', 'tipnijinak'), $match_id);
                continue;
            } else {
                // Vytvoření nového tipu
                $tip_title = sprintf(__('Tip uživatele %s pro zápas %d', 'tipnijinak'), get_userdata($user_id)->display_name, $match_id);
                
                $tip_post = array(
                    'post_title' => $tip_title,
                    'post_status' => 'publish',
                    'post_type' => 'user_tip',
                );
                
                $tip_id = wp_insert_post($tip_post);
                
                if (!is_wp_error($tip_id)) {
                    update_field('tip_user', $user_id, $tip_id);
                    update_field('tip_match', $match_id, $tip_id);
                    update_field('tip_competition', $competition_id, $tip_id);
                    update_field('tip_round', $round_id, $tip_id);
                    update_field('tip_value', $value, $tip_id);
                    update_field('tip_points', 0, $tip_id); // Počáteční počet bodů je 0
                    update_field('tip_evaluated', false, $tip_id); // Tip zatím není vyhodnocen
                    
                    $success_count++;
                } else {
                    $errors[] = sprintf(__('Chyba při ukládání tipu pro zápas ID %d.', 'tipnijinak'), $match_id);
                }
            }
        }
    }
    
    if ($success_count > 0) {
        $response = array(
            'message' => sprintf(__('Úspěšně uloženo %d tipů.', 'tipnijinak'), $success_count),
            'success_count' => $success_count
        );
        
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
        
        wp_send_json_success($response);
    } else {
        wp_send_json_error(array(
            'message' => __('Nepodařilo se uložit žádné tipy.', 'tipnijinak'),
            'errors' => $errors
        ));
    }
}
add_action('wp_ajax_tipnijinak_save_tips', 'tipnijinak_save_user_tips');

/**
 * Získá uživatelský tip pro daný zápas
 * 
 * @param int $match_id ID zápasu
 * @param int $user_id ID uživatele (výchozí: aktuálně přihlášený uživatel)
 * @param int $competition_id ID soutěže (volitelné - pokud zadáno, filtruje tipy podle soutěže)
 * @return string|bool Tip uživatele (1, 0, 2) nebo false pokud není nalezen
 */
function tipnijinak_get_user_tip($match_id, $user_id = 0, $competition_id = 0) {
    if (!$match_id) {
        return false;
    }
    
    if (!$user_id && is_user_logged_in()) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return false;
    }
    
    // Podpora původního systému ukládání tipů do databáze
    if (defined('TIPNIJINAK_USE_DB_TIPS') && TIPNIJINAK_USE_DB_TIPS) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tipnijinak_tips';
        
        $tip = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT tip FROM $table_name WHERE user_id = %d AND match_id = %d",
                $user_id,
                $match_id
            )
        );
        
        return $tip !== null ? $tip : false;
    }

    // Použít nový systém ukládání tipů jako CPT
    $meta_query = array(
        'relation' => 'AND',
        array(
            'key' => 'tip_user',
            'value' => $user_id,
        ),
        array(
            'key' => 'tip_match',
            'value' => $match_id,
        )
    );

    // Filtrovat podle soutěže, pokud je zadána
    if ($competition_id) {
        $meta_query[] = array(
            'key' => 'tip_competition',
            'value' => $competition_id,
        );
    }

    $args = array(
        'post_type' => 'user_tip',
        'posts_per_page' => 1,
        'meta_query' => $meta_query
    );

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        $query->the_post();
        $tip_value = get_field('tip_value');
        wp_reset_postdata();
        return $tip_value;
    }
    
    return false;
}

/**
 * Vyhodnotí zápas a přidělí body uživatelům
 * 
 * @param int $match_id ID zápasu
 * @return int Počet vyhodnocených tipů
 */
function tipnijinak_evaluate_match($match_id) {
    if (!$match_id) {
        return 0;
    }
    
    // Získání údajů o zápasu
    $match_status = get_field('stav_zapasu', $match_id);
    if ($match_status !== 'ukonceny') {
        return 0; // Zápas ještě není ukončen
    }
    
    $home_score = intval(get_field('skore_domaci', $match_id));
    $away_score = intval(get_field('skore_hoste', $match_id));
    
    // Určení výsledku zápasu (1 - domácí vyhráli, 0 - remíza, 2 - hosté vyhráli)
    $result = '0'; // výchozí hodnota - remíza
    if ($home_score > $away_score) {
        $result = '1'; // domácí vyhráli
    } elseif ($home_score < $away_score) {
        $result = '2'; // hosté vyhráli
    }
    
    $evaluated_count = 0;
    
    // Podpora původního systému ukládání tipů do databáze
    if (defined('TIPNIJINAK_USE_DB_TIPS') && TIPNIJINAK_USE_DB_TIPS) {
        global $wpdb;
        $tips_table = $wpdb->prefix . 'tipnijinak_tips';
        $points_table = $wpdb->prefix . 'tipnijinak_points';
        
        // Načtení všech tipů pro tento zápas
        $tips = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT user_id, match_id, round_id, competition_id, tip FROM $tips_table WHERE match_id = %d",
                $match_id
            )
        );
        
        if (empty($tips)) {
            return 0; // Žádné tipy k vyhodnocení
        }
        
        foreach ($tips as $tip) {
            // Získání kurzu pro vybraný tip uživatele
            $tip_value = $tip->tip;
            $match_kurz = 0;
            
            if ($tip_value === '1') {
                $match_kurz = floatval(get_post_meta($match_id, 'kurz_1', true));
            } elseif ($tip_value === '0') {
                $match_kurz = floatval(get_post_meta($match_id, 'kurz_0', true));
            } elseif ($tip_value === '2') {
                $match_kurz = floatval(get_post_meta($match_id, 'kurz_2', true));
            }
            
            // Pokud není kurz nalezen, použijeme kurzy z polí ACF
            if (!$match_kurz) {
                $kurzy = get_field('kurzy', $match_id);
                if ($tip_value === '1' && isset($kurzy['kurz_domaci'])) {
                    $match_kurz = floatval($kurzy['kurz_domaci']);
                } elseif ($tip_value === '0' && isset($kurzy['kurz_remiza'])) {
                    $match_kurz = floatval($kurzy['kurz_remiza']);
                } elseif ($tip_value === '2' && isset($kurzy['kurz_hoste'])) {
                    $match_kurz = floatval($kurzy['kurz_hoste']);
                }
            }
            
            // Určení počtu bodů na základě kurzové hladiny
            $points_per_match = tipnijinak_get_points_by_odds($match_kurz);
            
            // Přidělení bodů - při správném tipu přičteme, při špatném odečteme
            $earned_points = 0;
            if ($tip->tip === $result) {
                $earned_points = $points_per_match;
            } else {
                $earned_points = -$points_per_match;
            }
            
            // Uložení bodů do databáze
            $wpdb->replace(
                $points_table,
                array(
                    'user_id' => $tip->user_id,
                    'match_id' => $tip->match_id,
                    'round_id' => $tip->round_id,
                    'competition_id' => $tip->competition_id,
                    'points' => $earned_points
                ),
                array(
                    '%d', // user_id
                    '%d', // match_id
                    '%d', // round_id
                    '%d', // competition_id
                    '%d'  // points
                )
            );
            
            $evaluated_count++;
        }
    } else {
        // Použít nový systém ukládání tipů jako CPT
        $args = array(
            'post_type' => 'user_tip',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => 'tip_match',
                    'value' => $match_id,
                ),
            )
        );
        
        $tips_query = new WP_Query($args);
        
        if (!$tips_query->have_posts()) {
            return 0; // Žádné tipy k vyhodnocení
        }
        
        while ($tips_query->have_posts()) {
            $tips_query->the_post();
            
            $tip_id = get_the_ID();
            $competition_id = get_field('tip_competition', $tip_id);
            $tip_value = get_field('tip_value', $tip_id);
            
            // Ujistíme se, že competition_id je integer
            if (is_object($competition_id) && isset($competition_id->ID)) {
                $competition_id = $competition_id->ID;
            } elseif (is_array($competition_id) && isset($competition_id['ID'])) {
                $competition_id = $competition_id['ID'];
            } else {
                $competition_id = intval($competition_id);
            }
            
            // Přeskočíme neplatné competition_id
            if ($competition_id <= 0) {
                continue;
            }
            
            // Získání kurzu pro vybraný tip uživatele
            $match_kurz = 0;
            
            if ($tip_value === '1') {
                $match_kurz = floatval(get_post_meta($match_id, 'kurz_1', true));
            } elseif ($tip_value === '0') {
                $match_kurz = floatval(get_post_meta($match_id, 'kurz_0', true));
            } elseif ($tip_value === '2') {
                $match_kurz = floatval(get_post_meta($match_id, 'kurz_2', true));
            }
            
            // Pokud není kurz nalezen, použijeme kurzy z polí ACF
            if (!$match_kurz) {
                $kurzy = get_field('kurzy', $match_id);
                if ($tip_value === '1' && isset($kurzy['kurz_domaci'])) {
                    $match_kurz = floatval($kurzy['kurz_domaci']);
                } elseif ($tip_value === '0' && isset($kurzy['kurz_remiza'])) {
                    $match_kurz = floatval($kurzy['kurz_remiza']);
                } elseif ($tip_value === '2' && isset($kurzy['kurz_hoste'])) {
                    $match_kurz = floatval($kurzy['kurz_hoste']);
                }
            }
            
            // Určení počtu bodů na základě kurzové hladiny
            $points_per_match = tipnijinak_get_points_by_odds($match_kurz);
            
            // Přidělení bodů - při správném tipu přičteme, při špatném odečteme
            $earned_points = 0;
            if ($tip_value === $result) {
                $earned_points = $points_per_match;
            } else {
                $earned_points = -$points_per_match;
            }
            
            // Uložení bodů do CPT
            update_field('tip_points', $earned_points, $tip_id);
            update_field('tip_evaluated', true, $tip_id);
            
            $evaluated_count++;
        }
        
        wp_reset_postdata();
    }
    
    return $evaluated_count;
}

/**
 * Získá body uživatele za zápas
 * 
 * @param int $match_id ID zápasu
 * @param int $user_id ID uživatele (výchozí: aktuálně přihlášený uživatel)
 * @return int Počet bodů
 */
function tipnijinak_get_user_points_for_match($match_id, $user_id = 0) {
    if (!$match_id) {
        return 0;
    }
    
    if (!$user_id && is_user_logged_in()) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return 0;
    }
    
    // Podpora původního systému ukládání tipů do databáze
    if (defined('TIPNIJINAK_USE_DB_TIPS') && TIPNIJINAK_USE_DB_TIPS) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tipnijinak_points';
        
        $points = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT points FROM $table_name WHERE user_id = %d AND match_id = %d",
                $user_id,
                $match_id
            )
        );
        
        return $points !== null ? intval($points) : 0;
    }
    
    // Použít nový systém ukládání tipů jako CPT
    $args = array(
        'post_type' => 'user_tip',
        'posts_per_page' => 1,
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => 'tip_user',
                'value' => $user_id,
            ),
            array(
                'key' => 'tip_match',
                'value' => $match_id,
            )
        )
    );
    
    $query = new WP_Query($args);
    
    if ($query->have_posts()) {
        $query->the_post();
        $points = get_field('tip_points');
        wp_reset_postdata();
        return $points !== null ? intval($points) : 0;
    }
    
    return 0;
}

/**
 * Získá počet tipů uživatele v soutěži
 *
 * @param int $competition_id ID soutěže
 * @param int $user_id ID uživatele (výchozí: aktuálně přihlášený uživatel)
 * @return int Počet tipů
 */
function tipnijinak_get_user_tips_count($competition_id, $user_id = 0, $round_id = 0) {
    if (!$competition_id) {
        return 0;
    }

    if (!$user_id && is_user_logged_in()) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return 0;
    }

    // Podpora původního systému ukládání tipů do databáze
    if (defined('TIPNIJINAK_USE_DB_TIPS') && TIPNIJINAK_USE_DB_TIPS) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tipnijinak_tips';

        // Kontrola, zda tabulka existuje
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        if (!$table_exists) {
            return 0;
        }

        $sql = "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND competition_id = %d";
        $params = array($user_id, $competition_id);

        if ($round_id) {
            $sql .= " AND round_id = %d";
            $params[] = $round_id;
        }

        $count = $wpdb->get_var($wpdb->prepare($sql, $params));

        return $count !== null ? intval($count) : 0;
    }

    // Použít nový systém ukládání tipů jako CPT
    $args = array(
        'post_type' => 'user_tip',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => 'tip_user',
                'value' => $user_id,
            ),
            array(
                'key' => 'tip_competition',
                'value' => $competition_id,
            )
        )
    );

    if ($round_id) {
        $args['meta_query'][] = array(
            'key' => 'tip_round',
            'value' => $round_id,
        );
    }

    $query = new WP_Query($args);
    return $query->found_posts;
}

/**
 * Získá celkové body uživatele v soutěži
 * 
 * @param int $competition_id ID soutěže
 * @param int $user_id ID uživatele (výchozí: aktuálně přihlášený uživatel)
 * @return int Celkový počet bodů
 */
function tipnijinak_get_user_total_points($competition_id, $user_id = 0, $round_id = 0) {
    if (!$competition_id) {
        return 0;
    }

    if (!$user_id && is_user_logged_in()) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return 0;
    }

    // Použít nový systém ukládání tipů jako CPT (přeskočit databázovou verzi)
    $args = array(
        'post_type' => 'user_tip',
        'posts_per_page' => -1,
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => 'tip_user',
                'value' => $user_id,
            ),
            array(
                'key' => 'tip_competition',
                'value' => $competition_id,
            ),
            array(
                'key' => 'tip_evaluated',
                'value' => '1',
            )
        )
    );

    if ($round_id) {
        $args['meta_query'][] = array(
            'key' => 'tip_round',
            'value' => $round_id,
        );
    }
    
    $query = new WP_Query($args);
    $total_points = 0;
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $points = get_field('tip_points');
            $total_points += intval($points);
        }
        wp_reset_postdata();
    }
    
    return $total_points;
    
    // Podpora původního systému ukládání tipů do databáze (nepoužívá se)
    if (false && defined('TIPNIJINAK_USE_DB_TIPS') && TIPNIJINAK_USE_DB_TIPS) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tipnijinak_points';
        
        // Kontrola, zda tabulka existuje
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        if (!$table_exists) {
            // Zkusíme tabulku vytvořit
            tipnijinak_create_tables_manually();
            // Znovu zkontrolujeme
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
            if (!$table_exists) {
                return 0;
            }
        }
        
        $points = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(points) FROM $table_name WHERE user_id = %d AND competition_id = %d",
                $user_id,
                $competition_id
            )
        );
        
        return $points !== null ? intval($points) : 0;
    }
    
    // Použít nový systém ukládání tipů jako CPT
    $args = array(
        'post_type' => 'user_tip',
        'posts_per_page' => -1,
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => 'tip_user',
                'value' => $user_id,
            ),
            array(
                'key' => 'tip_competition',
                'value' => $competition_id,
            ),
            array(
                'key' => 'tip_evaluated',
                'value' => '1',
            )
        )
    );
    
    $query = new WP_Query($args);
    $total_points = 0;
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $points = get_field('tip_points');
            $total_points += intval($points);
        }
        wp_reset_postdata();
    }
    
    return $total_points;
}

/**
 * Získá žebříček uživatelů v soutěži
 * 
 * @param int $competition_id ID soutěže
 * @param int $limit Maximální počet záznamů
 * @param int $offset Offset pro stránkování
 * @return array Seznam uživatelů s jejich body
 */
function tipnijinak_get_competition_leaderboard($competition_id, $limit = 10, $offset = 0, $round_id = 0) {
    if (!$competition_id) {
        return array();
    }

    // Podpora původního systému ukládání tipů do databáze
    if (defined('TIPNIJINAK_USE_DB_TIPS') && TIPNIJINAK_USE_DB_TIPS) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tipnijinak_points';

        // Kontrola, zda tabulka existuje
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        if (!$table_exists) {
            tipnijinak_create_tables_manually();
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
            if (!$table_exists) {
                return array();
            }
        }

        $sql = "SELECT user_id, SUM(points) as total_points FROM $table_name WHERE competition_id = %d";
        $params = array($competition_id);

        if ($round_id) {
            $sql .= " AND round_id = %d";
            $params[] = $round_id;
        }

        $sql .= " GROUP BY user_id ORDER BY total_points DESC, user_id ASC LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;

        $leaderboard = $wpdb->get_results($wpdb->prepare($sql, $params));

        $result = array();

        foreach ($leaderboard as $entry) {
            $user = get_userdata($entry->user_id);
            if ($user) {
                $result[] = array(
                    'user_id' => $entry->user_id,
                    'name' => $user->user_login,
                    'points' => intval($entry->total_points)
                );
            }
        }

        return $result;
    }

    // Použít nový systém ukládání tipů jako CPT
    $users_with_tips_args = array(
        'post_type' => 'user_tip',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_query' => array(
            array(
                'key' => 'tip_competition',
                'value' => $competition_id,
            ),
            array(
                'key' => 'tip_evaluated',
                'value' => '1',
            )
        )
    );

    if ($round_id) {
        $users_with_tips_args['meta_query'][] = array(
            'key' => 'tip_round',
            'value' => $round_id,
        );
    }
    
    $user_tips_query = new WP_Query($users_with_tips_args);
    
    if (!$user_tips_query->have_posts()) {
        return array();
    }
    
    $tip_ids = $user_tips_query->posts;
    $users_points = array();
    
    // Pro každý tip získáme ID uživatele a body
    foreach ($tip_ids as $tip_id) {
        $user_id = get_field('tip_user', $tip_id);
        $points = intval(get_field('tip_points', $tip_id));
        
        // Ujistíme se, že user_id je integer
        if (is_object($user_id) && isset($user_id->ID)) {
            $user_id = $user_id->ID;
        } elseif (is_array($user_id) && isset($user_id['ID'])) {
            $user_id = $user_id['ID'];
        } else {
            $user_id = intval($user_id);
        }
        
        // Přeskočíme neplatné user_id
        if ($user_id <= 0) {
            continue;
        }
        
        if (!isset($users_points[$user_id])) {
            $users_points[$user_id] = 0;
        }
        
        $users_points[$user_id] += $points;
    }
    
    // Seřadit uživatele podle bodů sestupně
    arsort($users_points);
    
    // Omezit výsledky podle limitu a offsetu
    $users_points = array_slice($users_points, $offset, $limit, true);
    
    $result = array();
    
    // Vytvořit finální pole s informacemi o uživatelích
    foreach ($users_points as $user_id => $points) {
        $user = get_userdata($user_id);
        if ($user) {
            $result[] = array(
                'user_id' => $user_id,
                'name' => $user->user_login,
                'points' => $points
            );
        }
    }

    return $result;
}

/**
 * Přepínač pro použití původního systému ukládání tipů v databázi
 * Ponecháno kvůli kompatibilitě se staršími daty
 * Pro přepnutí odkomentujte řádek níže
 */
// define('TIPNIJINAK_USE_DB_TIPS', true);

/**
 * Funkce pro určení počtu bodů podle kurzové hladiny
 * 
 * @param float $odds Kurz zápasu
 * @return int Počet bodů podle kurzové hladiny
 */
function tipnijinak_get_points_by_odds($odds) {
    // Výchozí počet bodů, pokud nejsou nastaveny kurzové hladiny
    $default_points = 1;
    
    // Pokud není validní kurz, vrátíme výchozí hodnotu
    if (!$odds || $odds < 1) {
        return $default_points;
    }
    
    // Získání kurzových hladin z options
    $odds_levels = get_field('kurzove_hladiny', 'option');
    
    // Pokud nejsou nastaveny kurzové hladiny, vrátíme výchozí hodnotu
    if (!$odds_levels || !is_array($odds_levels) || empty($odds_levels)) {
        return $default_points;
    }
    
    // Projdeme všechny kurzové hladiny a určíme příslušnou hladinu
    foreach ($odds_levels as $level) {
        $odds_from = isset($level['kurz_od']) ? floatval($level['kurz_od']) : 0;
        $odds_to = isset($level['kurz_do']) && !empty($level['kurz_do']) ? floatval($level['kurz_do']) : PHP_FLOAT_MAX;
        $points = isset($level['body']) ? intval($level['body']) : $default_points;
        
        // Pokud je kurz v dané hladině, vrátíme odpovídající počet bodů
        if ($odds >= $odds_from && $odds <= $odds_to) {
            return $points;
        }
    }
    
    // Pokud nenajdeme odpovídající hladinu, vrátíme výchozí hodnotu
    return $default_points;
}

/**
 * Zobrazí náhled bodového hodnocení v options stránce
 * 
 * @param array $field ACF pole
 * @return array Upravené ACF pole
 */
function tipnijinak_odds_levels_preview($field) {
    $odds_levels = get_field('kurzove_hladiny', 'option');
    
    if (!$odds_levels || !is_array($odds_levels) || empty($odds_levels)) {
        $field['message'] = '<p>' . __('Zatím nebyly nastaveny žádné kurzové hladiny. Přidejte je výše.', 'tipnijinak') . '</p>';
        return $field;
    }
    
    $html = '<div class="odds-levels-preview">';
    $html .= '<h3>' . __('Přehled bodového hodnocení', 'tipnijinak') . '</h3>';
    $html .= '<table class="wp-list-table widefat fixed striped">';
    $html .= '<thead><tr>';
    $html .= '<th>' . __('Kurzová hladina', 'tipnijinak') . '</th>';
    $html .= '<th>' . __('Počet bodů', 'tipnijinak') . '</th>';
    $html .= '<th>' . __('Popis', 'tipnijinak') . '</th>';
    $html .= '</tr></thead>';
    $html .= '<tbody>';
    
    // Seřazení kurzových hladin podle kurz_od
    usort($odds_levels, function($a, $b) {
        $a_val = isset($a['kurz_od']) ? floatval($a['kurz_od']) : 0;
        $b_val = isset($b['kurz_od']) ? floatval($b['kurz_od']) : 0;
        return $a_val <=> $b_val;
    });
    
    foreach ($odds_levels as $level) {
        $odds_from = isset($level['kurz_od']) ? floatval($level['kurz_od']) : 0;
        $odds_to = isset($level['kurz_do']) && !empty($level['kurz_do']) ? floatval($level['kurz_do']) : null;
        $points = isset($level['body']) ? intval($level['body']) : 1;
        $description = isset($level['popis']) ? esc_html($level['popis']) : '';
        
        $html .= '<tr>';
        
        // Zobrazení kurzové hladiny
        if ($odds_to) {
            $html .= '<td>' . sprintf(__('%s - %s', 'tipnijinak'), number_format($odds_from, 2), number_format($odds_to, 2)) . '</td>';
        } else {
            $html .= '<td>' . sprintf(__('%s a více', 'tipnijinak'), number_format($odds_from, 2)) . '</td>';
        }
        
        // Zobrazení bodů
        $html .= '<td>' . $points . '</td>';
        
        // Zobrazení popisu
        $html .= '<td>' . $description . '</td>';
        
        $html .= '</tr>';
    }
    
    $html .= '</tbody></table>';
    $html .= '<p class="description">' . __('Při správném tipu se body přičtou, při špatném tipu se odečtou.', 'tipnijinak') . '</p>';
    $html .= '</div>';
    
    $field['message'] = $html;
    return $field;
}

/**
 * ACF filtry pro vlastní funkcionalitu
 */
add_filter('acf/prepare_field/key=field_kurz_preview', 'tipnijinak_odds_levels_preview');

/**
 * AJAX handler pro získání bodů podle kurzu
 */
function tipnijinak_ajax_get_points_by_odds() {
    // Získání kurzu z parametru
    $odds = isset($_GET['odds']) ? floatval($_GET['odds']) : 0;
    
    if ($odds <= 0) {
        wp_send_json_error('Neplatný kurz');
        return;
    }
    
    // Získání bodů podle kurzu
    $points = tipnijinak_get_points_by_odds($odds);
    
    wp_send_json_success($points);
}
add_action('wp_ajax_tipnijinak_get_points_by_odds', 'tipnijinak_ajax_get_points_by_odds');
add_action('wp_ajax_nopriv_tipnijinak_get_points_by_odds', 'tipnijinak_ajax_get_points_by_odds');

/**
 * AJAX handler pro načtení obsahu kola
 */
function tipnijinak_get_round_content() {
    // Ověření nonce - dočasně vypnuto pro debug
    /*if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tipnijinak_tips_nonce')) {
        wp_send_json_error(array('message' => __('Neplatný bezpečnostní token.', 'tipnijinak')));
    }*/
    
    $round_id = isset($_POST['round_id']) ? intval($_POST['round_id']) : 0;
    $competition_id = isset($_POST['competition_id']) ? intval($_POST['competition_id']) : 0;
    
    if (!$round_id || !$competition_id) {
        wp_send_json_error(array('message' => __('Chybějící parametry.', 'tipnijinak')));
    }
    
    // Získat data soutěže a kola
    $rounds = tipnijinak_get_competition_rounds($competition_id);
    $current_round = null;
    
    // Najít požadované kolo
    foreach ($rounds as $round) {
        if ($round['id'] == $round_id) {
            $current_round = $round;
            break;
        }
    }
    
    if (!$current_round) {
        wp_send_json_error(array('message' => __('Kolo nebylo nalezeno.', 'tipnijinak')));
    }
    
    // Kontrola přístupu
    $has_access = tipnijinak_user_has_access_to_competition($competition_id);
    $product_id = get_field('woocommerce_produkt', $competition_id);
    
    // Pokud nemá přístup, vrátit locked message
    if ($product_id && !$has_access) {
        ob_start();
        ?>
        <div class="competition-locked">
            <p><?php esc_html_e('Pro přístup k tipování musíte zakoupit přístup.', 'tipnijinak'); ?></p>
            <a href="<?php echo esc_url(add_query_arg('tab', 'competitions', get_permalink($competition_id))); ?>" class="btn btn-primary"><?php esc_html_e('Zobrazit detaily', 'tipnijinak'); ?></a>
        </div>
        <?php
        $html = ob_get_clean();
        wp_send_json_success(array('html' => $html));
    }
    
    // Najít předchozí a další kolo
    $prev_round = null;
    $next_round = null;
    
    foreach ($rounds as $i => $round) {
        if ($round['id'] == $current_round['id']) {
            if ($i > 0) {
                $prev_round = $rounds[$i - 1];
            }
            if ($i < count($rounds) - 1) {
                $next_round = $rounds[$i + 1];
            }
            break;
        }
    }
    
    // Počet bodů za správný tip
    $body = get_field('pocet_bodu', $competition_id);
    $body_text = $body ? $body . ' bodů' : '30 bodů';
    
    // Generovat HTML obsah
    ob_start();
    ?>
    <div class="two-columns">
        <div class="two-columns__column left">
            <!-- Navigace mezi koly -->
            <div class="round">
                <h3><?php echo esc_html($current_round ? $current_round['cislo_kola'] . '. kolo' : __('Kolo', 'tipnijinak')); ?></h3>
                <div class="round-info">
                    <?php if ($current_round) : ?>
                    <span class="duration"><?php printf(__('Doba trvání: %s - %s', 'tipnijinak'), 
                        esc_html($current_round['datum_od_format']), 
                        esc_html($current_round['datum_do_format'])); ?></span>
                    <span class="status"><?php printf(__('Stav: %s', 'tipnijinak'), 
                        esc_html($current_round['stav_kola_text'])); ?></span>
                    <?php endif; ?>
                </div>
                <div class="pagination big">
                    <?php if ($prev_round) : ?>
                    <div class="pagination-prev">
                        <a href="<?php echo esc_url(add_query_arg(['tab' => 'guessing', 'kolo' => $prev_round['id']], get_permalink($competition_id))); ?>"></a>
                    </div>
                    <?php else : ?>
                    <div class="pagination-prev disabled"></div>
                    <?php endif; ?>
                    
                    <?php if ($next_round) : ?>
                    <div class="pagination-next">
                        <a href="<?php echo esc_url(add_query_arg(['tab' => 'guessing', 'kolo' => $next_round['id']], get_permalink($competition_id))); ?>"></a>
                    </div>
                    <?php else : ?>
                    <div class="pagination-next disabled"></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($current_round && !empty($current_round['matches'])) : 
                // Seskupit zápasy podle ligy
                $matches_by_league = [];
                foreach ($current_round['matches'] as $match) {
                    if (!empty($match['liga'])) {
                        $liga = $match['liga'];
                        $liga_slug = isset($match['liga_slug']) ? $match['liga_slug'] : sanitize_title($liga);
                    } else {
                        $liga = __('Ostatní', 'tipnijinak');
                        $liga_slug = sanitize_title($liga);
                    }
                    
                    if (!isset($matches_by_league[$liga_slug])) {
                        $matches_by_league[$liga_slug] = [
                            'name' => $liga,
                            'matches' => []
                        ];
                    }
                    
                    $matches_by_league[$liga_slug]['matches'][] = $match;
                }
                
                $active_league = isset($_GET['liga']) ? sanitize_title($_GET['liga']) : array_key_first($matches_by_league);
            ?>
            <div class="league">
                <div class="league-title">
                    <h3><?php esc_html_e('Liga', 'tipnijinak'); ?></h3>
                    <ul class="window-switcher">
                        <?php foreach ($matches_by_league as $league_slug => $league_data) :
                            // Získat term objekt pro ligu a její obrázek
                            $league_term = get_term_by('slug', $league_slug, 'liga');
                            $league_image = null;

                            if ($league_term && !is_wp_error($league_term)) {
                                $image_field = get_field('obrazek_ligy', 'liga_' . $league_term->term_id);
                                if ($image_field && isset($image_field['url'])) {
                                    $league_image = $image_field['url'];
                                }
                            }
                        ?>
                        <li data="league-<?php echo esc_attr($league_slug); ?>"
                            class="<?php echo $league_slug === $active_league ? 'active' : ''; ?>">
                            <?php if ($league_image) : ?>
                                <img src="<?php echo esc_url($league_image); ?>" alt="<?php echo esc_attr($league_data['name']); ?>" class="league-icon">
                            <?php endif; ?>
                            <?php echo esc_html($league_data['name']); ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <?php foreach ($matches_by_league as $league_slug => $league_data) : ?>
                <div class="league-<?php echo esc_attr($league_slug); ?> <?php echo $league_slug === $active_league ? 'active' : ''; ?> league-matches">
                    <?php foreach ($league_data['matches'] as $match) :
                        $user_tip = is_user_logged_in() ? tipnijinak_get_user_tip($match['id'], 0, $competition_id) : false;
                        $match_status = $match['status'] ?? 'planovany';
                        $is_match_locked = in_array($match_status, array('ukonceny', 'probihajici', 'zrusen'));
                    ?>
                    <div class="match <?php echo $is_match_locked ? 'match-locked' : ''; ?>" data-match-id="<?php echo esc_attr($match['id']); ?>"
                         data-match-date="<?php echo esc_attr($match['date']); ?>"
                         data-match-time="<?php echo esc_attr($match['time']); ?>">
                        <div class="match-time">
                            <span class="match-time__hours"><?php echo esc_html($match['time']); ?></span>
                            <span class="match-time__date"><?php echo esc_html($match['date']); ?></span>
                        </div>
                        <div class="match-info">
                            <div class="match-teams">
                                <div class="home team">
                                    <div class="team-name" title="<?php echo esc_attr($match['teams']['home']['name']); ?>"><?php echo esc_html($match['teams']['home']['abbreviation']); ?></div>
                                    <div class="logo-holder">
                                        <?php if (!empty($match['teams']['home']['logo'])) : ?>
                                            <img src="<?php echo esc_url($match['teams']['home']['logo']); ?>" alt="<?php echo esc_attr($match['teams']['home']['name']); ?>">
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="away team">
                                    <div class="team-name" title="<?php echo esc_attr($match['teams']['away']['name']); ?>"><?php echo esc_html($match['teams']['away']['abbreviation']); ?></div>
                                    <div class="logo-holder">
                                        <?php if (!empty($match['teams']['away']['logo'])) : ?>
                                            <img src="<?php echo esc_url($match['teams']['away']['logo']); ?>" alt="<?php echo esc_attr($match['teams']['away']['name']); ?>">
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="match-score">
                                <?php
                                $score_parts = explode(':', $match['score']);
                                $home_score_display = isset($score_parts[0]) ? trim($score_parts[0]) : '-';
                                $away_score_display = isset($score_parts[1]) ? trim($score_parts[1]) : '-';
                                ?>
                                <span class="score-home"><?php echo esc_html($home_score_display); ?></span>
                                <span class="score-away"><?php echo esc_html($away_score_display); ?></span>
                            </div>
                        </div>

                        <?php if ($is_match_locked) : ?>
                            <?php if ($user_tip !== false) :
                                $kurzy_locked = get_field('kurzy', $match['id']);
                                $home_score_locked = get_field('skore_domaci', $match['id']);
                                $away_score_locked = get_field('skore_hoste', $match['id']);
                                $match_result_locked = null;
                                if ($match_status === 'ukonceny' && $home_score_locked !== '' && $home_score_locked !== null && $away_score_locked !== '' && $away_score_locked !== null) {
                                    $home_score_locked = intval($home_score_locked);
                                    $away_score_locked = intval($away_score_locked);
                                    if ($home_score_locked > $away_score_locked) {
                                        $match_result_locked = '1';
                                    } elseif ($home_score_locked < $away_score_locked) {
                                        $match_result_locked = '2';
                                    } else {
                                        $match_result_locked = '0';
                                    }
                                }
                            ?>
                            <div class="odds">
                                <div class="odds-button <?php echo ($user_tip === '1') ? 'active' : ''; ?>" data-saved="true">1</div>
                                <div class="odds-button <?php echo ($user_tip === '0') ? 'active' : ''; ?>" data-saved="true">0</div>
                                <div class="odds-button <?php echo ($user_tip === '2') ? 'active' : ''; ?>" data-saved="true">2</div>
                            </div>
                            <div class="odds-points">
                                <?php
                                $selected_odds_locked = 0;
                                if ($user_tip === '1' && !empty($kurzy_locked['kurz_domaci'])) {
                                    $selected_odds_locked = floatval($kurzy_locked['kurz_domaci']);
                                } elseif ($user_tip === '0' && !empty($kurzy_locked['kurz_remiza'])) {
                                    $selected_odds_locked = floatval($kurzy_locked['kurz_remiza']);
                                } elseif ($user_tip === '2' && !empty($kurzy_locked['kurz_hoste'])) {
                                    $selected_odds_locked = floatval($kurzy_locked['kurz_hoste']);
                                }
                                if ($selected_odds_locked > 0) {
                                    $points_locked = tipnijinak_get_points_by_odds($selected_odds_locked);
                                    if ($match_result_locked !== null) {
                                        $is_correct_locked = ($user_tip === $match_result_locked);
                                        $prefix = $is_correct_locked ? '+' : '-';
                                        echo esc_html($prefix . $points_locked . ' ' . ($points_locked === 1 ? 'bod' : ($points_locked >= 2 && $points_locked <= 4 ? 'body' : 'bodů')));
                                    } else {
                                        if ($points_locked === 1) {
                                            echo sprintf(__('%d bod', 'tipnijinak'), $points_locked);
                                        } elseif ($points_locked >= 2 && $points_locked <= 4) {
                                            echo sprintf(__('%d body', 'tipnijinak'), $points_locked);
                                        } else {
                                            echo sprintf(__('%d bodů', 'tipnijinak'), $points_locked);
                                        }
                                    }
                                }
                                ?>
                            </div>
                            <?php endif; ?>
                        <?php elseif ($current_round['stav_kola'] === 'otevreno' || $current_round['stav_kola'] === 'probihajici') : ?>
                            <?php if (is_user_logged_in()) : ?>
                            <div class="odds">
                                <?php
                                // Získat kurzy pro tento zápas
                                $kurzy = get_field('kurzy', $match['id']);
                                // Kontrola, zda už má uživatel uložený tip
                                $has_saved_tip = ($user_tip !== false);
                                ?>
                                <div class="odds-button <?php echo ($user_tip === '1') ? 'active' : ''; ?>" data-value="1"
                                    <?php if ($has_saved_tip) : ?>data-saved="true"<?php endif; ?>
                                    <?php if (!empty($kurzy['kurz_domaci'])) : ?>
                                    data-odds="<?php echo esc_attr($kurzy['kurz_domaci']); ?>"
                                    <?php endif; ?>>
                                    1
                                </div>
                                <div class="odds-button <?php echo ($user_tip === '0') ? 'active' : ''; ?>" data-value="0"
                                    <?php if ($has_saved_tip) : ?>data-saved="true"<?php endif; ?>
                                    <?php if (!empty($kurzy['kurz_remiza'])) : ?>
                                    data-odds="<?php echo esc_attr($kurzy['kurz_remiza']); ?>"
                                    <?php endif; ?>>
                                    0
                                </div>
                                <div class="odds-button <?php echo ($user_tip === '2') ? 'active' : ''; ?>" data-value="2"
                                    <?php if ($has_saved_tip) : ?>data-saved="true"<?php endif; ?>
                                    <?php if (!empty($kurzy['kurz_hoste'])) : ?>
                                    data-odds="<?php echo esc_attr($kurzy['kurz_hoste']); ?>"
                                    <?php endif; ?>>
                                    2
                                </div>
                            </div>
                            <div class="odds-points">
                                <?php
                                if ($user_tip !== false) {
                                    $selected_odds = 0;
                                    if ($user_tip === '1' && !empty($kurzy['kurz_domaci'])) {
                                        $selected_odds = floatval($kurzy['kurz_domaci']);
                                    } elseif ($user_tip === '0' && !empty($kurzy['kurz_remiza'])) {
                                        $selected_odds = floatval($kurzy['kurz_remiza']);
                                    } elseif ($user_tip === '2' && !empty($kurzy['kurz_hoste'])) {
                                        $selected_odds = floatval($kurzy['kurz_hoste']);
                                    }
                                    if ($selected_odds > 0) {
                                        $points = tipnijinak_get_points_by_odds($selected_odds);
                                        if ($points === 1) {
                                            echo sprintf(__('%d bod', 'tipnijinak'), $points);
                                        } elseif ($points >= 2 && $points <= 4) {
                                            echo sprintf(__('%d body', 'tipnijinak'), $points);
                                        } else {
                                            echo sprintf(__('%d bodů', 'tipnijinak'), $points);
                                        }
                                    }
                                }
                                ?>
                            </div>
                            <?php else : ?>
                            <div class="login-required">
                                <a href="<?php echo esc_url(wp_login_url(get_permalink($competition_id))); ?>" class="btn btn-small"><?php esc_html_e('Přihlásit se pro možnost tipovat', 'tipnijinak'); ?></a>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else : ?>
            <div class="no-matches-message">
                <p><?php esc_html_e('V tomto kole nejsou žádné zápasy.', 'tipnijinak'); ?></p>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="two-columns__column right recap">
            <?php if (is_user_logged_in() && $current_round) : 
                // Získat tipy uživatele pro aktuální kolo z Custom Post Type
                $user_tips = function_exists('tipnijinak_get_user_round_tips_alt') ? 
                    tipnijinak_get_user_round_tips_alt($current_round['id']) : 
                    tipnijinak_get_user_round_tips($current_round['id']);
                
                // Získat maximální počet tipů z ACF pole
                $max_tips = get_field('max_tipy', $competition_id);
                $max_tips = $max_tips ? intval($max_tips) : 15; // Výchozí hodnota 15
                $untipped_count = 0;
                
                if (!empty($current_round['matches'])) {
                    // Počet zbývajících tipů = maximální počet - počet již tipnutých
                    $current_tips_count = count($user_tips);
                    $untipped_count = max(0, $max_tips - $current_tips_count);
                }
            ?>
            <div class="matches-recap">
                <?php if (!empty($user_tips)) : 
                    foreach ($user_tips as $tip) : 
                    $match = tipnijinak_get_match_details($tip['match_id']);
                    if (!$match) continue;
                ?>
                <div class="match">
                    <?php 
                    // Fix WP_Post object error - ensure we get the ID as string/integer
                    $match_id = is_object($match['id']) && property_exists($match['id'], 'ID') ? $match['id']->ID : $match['id'];
                    ?>
                    <div class="match-info" data-match-id="<?php echo esc_attr($match_id); ?>">
                        <div class="home team">
                            <div class="team-name" title="<?php echo esc_attr($match['teams']['home']['name']); ?>"><?php echo esc_html($match['teams']['home']['abbreviation']); ?></div>
                            <div class="logo-holder">
                                <?php if (!empty($match['teams']['home']['logo'])) : ?>
                                    <img src="<?php echo esc_url($match['teams']['home']['logo']); ?>" alt="<?php echo esc_attr($match['teams']['home']['name']); ?>">
                                <?php endif; ?>
                            </div>
                        </div>
                        <span>-</span>
                        <div class="away team">
                            <div class="team-name" title="<?php echo esc_attr($match['teams']['away']['name']); ?>"><?php echo esc_html($match['teams']['away']['abbreviation']); ?></div>
                            <div class="logo-holder">
                                <?php if (!empty($match['teams']['away']['logo'])) : ?>
                                    <img src="<?php echo esc_url($match['teams']['away']['logo']); ?>" alt="<?php echo esc_attr($match['teams']['away']['name']); ?>">
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="odd">
                        <?php echo esc_html($tip['tip']); ?>
                    </div>
                </div>
                <?php endforeach; 
                else : ?>
                <div class="no-tips-yet">
                    <p><?php esc_html_e('Zatím jste neprovedli žádné tipy.', 'tipnijinak'); ?></p>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="matches-left">
                <?php printf(__('Zbývá: %d zápasů', 'tipnijinak'), $untipped_count); ?>
            </div>
            
            <?php if (($current_round['stav_kola'] === 'otevreno' || $current_round['stav_kola'] === 'probihajici')) : ?>
            <div class="tips-feedback"></div>
            <div class="btn-holder">
                <button class="btn btn-primary submit-tips">
                    <?php esc_html_e('Uložit/aktualizovat', 'tipnijinak'); ?>
                </button>
            </div>
            <?php endif; ?>
            
            <?php else : ?>
            <div class="login-to-tip">
                <p><?php esc_html_e('Pro možnost tipovat se musíte přihlásit.', 'tipnijinak'); ?></p>
                <a href="<?php echo esc_url(wp_login_url(get_permalink($competition_id))); ?>" class="btn btn-primary"><?php esc_html_e('Přihlásit se', 'tipnijinak'); ?></a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
    $html = ob_get_clean();
    
    // Kontrola, zda je soutěž hlavní
    $is_main_competition = get_field('je_hlavni', $competition_id);
    $min_tips = $is_main_competition ? 1 : $max_tips; // Pokud není hlavní, min = max
    
    // Spočítat počet uložených tipů pro JavaScript
    $saved_tips_count = 0;
    if (is_user_logged_in() && $current_round) {
        $user_tips_for_count = function_exists('tipnijinak_get_user_round_tips_alt') ? 
            tipnijinak_get_user_round_tips_alt($current_round['id']) : 
            tipnijinak_get_user_round_tips($current_round['id']);
        $saved_tips_count = count($user_tips_for_count);
    }
    
    // Přidat JavaScript pro aktualizaci proměnných
    $html .= '<script>';
    $html .= 'if (typeof tipnijinak_vars !== "undefined") {';
    $html .= 'tipnijinak_vars.max_tips = ' . intval($max_tips) . ';';
    $html .= 'tipnijinak_vars.min_tips = ' . intval($min_tips) . ';';
    $html .= 'tipnijinak_vars.is_main_competition = ' . ($is_main_competition ? 'true' : 'false') . ';';
    $html .= 'tipnijinak_vars.saved_tips_count = ' . intval($saved_tips_count) . ';';
    $html .= '}';
    $html .= '</script>';
    
    wp_send_json_success(array('html' => $html, 'saved_tips_count' => $saved_tips_count));
}
add_action('wp_ajax_tipnijinak_get_round_content', 'tipnijinak_get_round_content');
add_action('wp_ajax_nopriv_tipnijinak_get_round_content', 'tipnijinak_get_round_content');

/**
 * Synchronizace bilaterálního vztahu mezi soutěžemi a koly
 * Při změně jedné strany vztahu se automaticky aktualizuje druhá strana
 */
function tipnijinak_sync_soutez_kola_relationship($post_id) {
    // Zabránit nekonečné smyčce
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Zabránit spuštění během hromadné editace
    if (defined('DOING_AJAX') && DOING_AJAX) {
        return;
    }
    
    $post_type = get_post_type($post_id);
    
    if ($post_type == 'soutez') {
        // Při uložení soutěže aktualizovat vztah v kolech
        $kola = get_field('kola_souteze', $post_id);
        
        if ($kola) {
            // Nejprve odebrat tuto soutěž ze všech kol, která ji měla přiřazenou
            $existing_kola = get_posts(array(
                'post_type' => 'kolo',
                'meta_query' => array(
                    array(
                        'key' => 'souteze_kola',
                        'value' => '"' . $post_id . '"',
                        'compare' => 'LIKE'
                    )
                ),
                'posts_per_page' => -1,
                'fields' => 'ids'
            ));
            
            foreach ($existing_kola as $kolo_id) {
                if (!in_array($kolo_id, $kola)) {
                    // Toto kolo už není přiřazeno k této soutěži, odstranit vztah
                    $current_souteze = get_field('souteze_kola', $kolo_id);
                    if ($current_souteze && in_array($post_id, $current_souteze)) {
                        $current_souteze = array_diff($current_souteze, array($post_id));
                        update_field('souteze_kola', array_values($current_souteze), $kolo_id);
                    }
                }
            }
            
            // Nastavit vztah v nově přiřazených kolech
            foreach ($kola as $kolo_id) {
                $current_souteze = get_field('souteze_kola', $kolo_id);
                if (!$current_souteze) {
                    $current_souteze = array();
                }
                
                // Přidat tuto soutěž, pokud tam ještě není
                if (!in_array($post_id, $current_souteze)) {
                    $current_souteze[] = $post_id;
                    update_field('souteze_kola', $current_souteze, $kolo_id);
                }
            }
        } else {
            // Pokud nejsou vybrána žádná kola, odstranit vztah ze všech kol
            $existing_kola = get_posts(array(
                'post_type' => 'kolo',
                'meta_query' => array(
                    array(
                        'key' => 'souteze_kola',
                        'value' => '"' . $post_id . '"',
                        'compare' => 'LIKE'
                    )
                ),
                'posts_per_page' => -1,
                'fields' => 'ids'
            ));
            
            foreach ($existing_kola as $kolo_id) {
                $current_souteze = get_field('souteze_kola', $kolo_id);
                if ($current_souteze && in_array($post_id, $current_souteze)) {
                    $current_souteze = array_diff($current_souteze, array($post_id));
                    update_field('souteze_kola', array_values($current_souteze), $kolo_id);
                }
            }
        }
    } elseif ($post_type == 'kolo') {
        // Při uložení kola aktualizovat vztah ve soutěžích
        $souteze_ids = get_field('souteze_kola', $post_id);
        
        // Nejprve najít všechny soutěže, které měly toto kolo přiřazené
        $existing_souteze = get_posts(array(
            'post_type' => 'soutez',
            'meta_query' => array(
                array(
                    'key' => 'kola_souteze',
                    'value' => '"' . $post_id . '"',
                    'compare' => 'LIKE'
                )
            ),
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
        
        if ($souteze_ids && !empty($souteze_ids)) {
            // Odebrat kolo ze soutěží, které už nejsou vybrané
            foreach ($existing_souteze as $soutez_id) {
                if (!in_array($soutez_id, $souteze_ids)) {
                    $kola = get_field('kola_souteze', $soutez_id);
                    if ($kola && in_array($post_id, $kola)) {
                        $kola = array_diff($kola, array($post_id));
                        update_field('kola_souteze', array_values($kola), $soutez_id);
                    }
                }
            }
            
            // Přidat kolo do nově vybraných soutěží
            foreach ($souteze_ids as $soutez_id) {
                $current_kola = get_field('kola_souteze', $soutez_id);
                if (!$current_kola) {
                    $current_kola = array();
                }
                
                // Přidat toto kolo, pokud tam ještě není
                if (!in_array($post_id, $current_kola)) {
                    $current_kola[] = $post_id;
                    update_field('kola_souteze', $current_kola, $soutez_id);
                }
            }
        } else {
            // Pokud kolo nemá přiřazené žádné soutěže, odstranit ho ze všech soutěží
            foreach ($existing_souteze as $soutez_id) {
                $kola = get_field('kola_souteze', $soutez_id);
                if ($kola && in_array($post_id, $kola)) {
                    $kola = array_diff($kola, array($post_id));
                    update_field('kola_souteze', array_values($kola), $soutez_id);
                }
            }
        }
    }
}
add_action('acf/save_post', 'tipnijinak_sync_soutez_kola_relationship');

/**
 * Manuální vytvoření tabulek pro ukládání tipů a bodů
 * Tato funkce může být volána po aktivaci šablony, pokud tabulky neexistují
 */
function tipnijinak_create_tables_manually() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    $table_name_tips = $wpdb->prefix . 'tipnijinak_tips';
    $table_name_points = $wpdb->prefix . 'tipnijinak_points';
    
    // Nejprve zkontrolujeme, zda tabulky už existují
    $tips_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name_tips'") === $table_name_tips;
    $points_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name_points'") === $table_name_points;
    
    if (!$tips_table_exists) {
        $tips_sql = "CREATE TABLE $table_name_tips (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            match_id bigint(20) NOT NULL,
            round_id bigint(20) NOT NULL,
            competition_id bigint(20) NOT NULL,
            tip varchar(1) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY user_match (user_id, match_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($tips_sql);
    }
    
    if (!$points_table_exists) {
        $points_sql = "CREATE TABLE $table_name_points (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            match_id bigint(20) NOT NULL,
            round_id bigint(20) NOT NULL,
            competition_id bigint(20) NOT NULL,
            points int(11) NOT NULL DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY user_match (user_id, match_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($points_sql);
    }
    
    return array(
        'tips_table' => ($tips_table_exists ? 'exists' : 'created'),
        'points_table' => ($points_table_exists ? 'exists' : 'created')
    );
}

/**
 * AJAX handler pro manuální vytvoření tabulek
 */
function tipnijinak_ajax_create_tables() {
    // Ověření, zda je uživatel administrátorem
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Nemáte dostatečná oprávnění pro tuto akci.', 'tipnijinak')));
    }
    
    $result = tipnijinak_create_tables_manually();
    wp_send_json_success($result);
}
add_action('wp_ajax_tipnijinak_create_tables', 'tipnijinak_ajax_create_tables');

/**
 * Enqueue admin scripts pro options page kurzových hladin
 */
function tipnijinak_odds_levels_admin_scripts($hook) {
    // Pouze na stránce kurzových hladin
    if ('toplevel_page_kurzove-hladiny' !== $hook) {
        return;
    }
    
    wp_enqueue_style('tipnijinak-kurzove-hladiny', get_template_directory_uri() . '/inc/admin/css/kurzove-hladiny.css', array(), TIPNIJINAK_VERSION);
}
add_action('admin_enqueue_scripts', 'tipnijinak_odds_levels_admin_scripts');

/**
 * Přidání admin menu pro správu databázových tabulek
 */
function tipnijinak_add_admin_menu() {
    add_management_page(
        __('Tipni Jinak - Správa databáze', 'tipnijinak'),
        __('Tipni Jinak DB', 'tipnijinak'),
        'manage_options',
        'tipnijinak-db-manager',
        'tipnijinak_render_db_manager'
    );
}
add_action('admin_menu', 'tipnijinak_add_admin_menu');

/**
 * Vykreslení stránky pro správu databázových tabulek
 */
function tipnijinak_render_db_manager() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Tipni Jinak - Správa databáze', 'tipnijinak'); ?></h1>
        
        <div class="card">
            <h2><?php echo esc_html__('Stav databázových tabulek', 'tipnijinak'); ?></h2>
            
            <?php
            global $wpdb;
            $table_name_tips = $wpdb->prefix . 'tipnijinak_tips';
            $table_name_points = $wpdb->prefix . 'tipnijinak_points';
            
            $tips_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name_tips'") === $table_name_tips;
            $points_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name_points'") === $table_name_points;
            ?>
            
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Tabulka', 'tipnijinak'); ?></th>
                        <th><?php echo esc_html__('Stav', 'tipnijinak'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo esc_html($table_name_tips); ?></td>
                        <td>
                            <?php if ($tips_table_exists) : ?>
                                <span class="dashicons dashicons-yes" style="color: green;"></span> <?php echo esc_html__('Existuje', 'tipnijinak'); ?>
                            <?php else : ?>
                                <span class="dashicons dashicons-no" style="color: red;"></span> <?php echo esc_html__('Neexistuje', 'tipnijinak'); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><?php echo esc_html($table_name_points); ?></td>
                        <td>
                            <?php if ($points_table_exists) : ?>
                                <span class="dashicons dashicons-yes" style="color: green;"></span> <?php echo esc_html__('Existuje', 'tipnijinak'); ?>
                            <?php else : ?>
                                <span class="dashicons dashicons-no" style="color: red;"></span> <?php echo esc_html__('Neexistuje', 'tipnijinak'); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <p class="submit">
                <button id="tipnijinak-create-tables" class="button button-primary"><?php echo esc_html__('Vytvořit chybějící tabulky', 'tipnijinak'); ?></button>
                <span id="tipnijinak-create-tables-spinner" class="spinner" style="float: none; margin-top: 0;"></span>
                <span id="tipnijinak-create-tables-message"></span>
            </p>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#tipnijinak-create-tables').on('click', function() {
                var button = $(this);
                var spinner = $('#tipnijinak-create-tables-spinner');
                var message = $('#tipnijinak-create-tables-message');
                
                button.prop('disabled', true);
                spinner.addClass('is-active');
                message.html('');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'tipnijinak_create_tables',
                    },
                    success: function(response) {
                        if (response.success) {
                            var result = response.data;
                            message.html('<div class="notice notice-success inline"><p>' + 
                                'Tabulky zpracovány: ' + 
                                'Tips: ' + result.tips_table + ', ' + 
                                'Points: ' + result.points_table + '</p></div>');
                            
                            // Aktualizovat stránku po 2 sekundách
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            message.html('<div class="notice notice-error inline"><p>Chyba: ' + response.data.message + '</p></div>');
                        }
                    },
                    error: function() {
                        message.html('<div class="notice notice-error inline"><p>Došlo k chybě při komunikaci se serverem.</p></div>');
                    },
                    complete: function() {
                        button.prop('disabled', false);
                        spinner.removeClass('is-active');
                    }
                });
            });
        });
        </script>
    </div>
    <?php
}

/**
 * WooCommerce cart fragments for AJAX cart update
 */
function tipnijinak_add_to_cart_fragment( $fragments ) {
    ob_start();
    ?>
    <span class="cart-count"><?php echo WC()->cart->get_cart_contents_count(); ?></span>
    <?php
    $fragments['.cart-count'] = ob_get_clean();
    
    return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'tipnijinak_add_to_cart_fragment' );

/**
 * Add options page when ACF is active
 */
if (function_exists('acf_add_options_page')) {
    acf_add_options_page(array(
        'page_title'    => 'Nastavení šablony',
        'menu_title'    => 'Nastavení šablony',
        'menu_slug'     => 'nastaveni-sablony',
        'capability'    => 'edit_posts',
        'redirect'      => false
    ));
    
    acf_add_options_page(array(
        'page_title'    => 'Kurzové hladiny',
        'menu_title'    => 'Kurzové hladiny',
        'menu_slug'     => 'kurzove-hladiny',
        'capability'    => 'manage_options',
        'redirect'      => false,
        'icon_url'      => 'dashicons-chart-line'
    ));
    
    
    // Zakomentováno - místo toho používáme vlastní admin stránku
    // acf_add_options_sub_page(array(
    //     'page_title'    => 'Vyhodnocení tipů',
    //     'menu_title'    => 'Vyhodnocení tipů',
    //     'parent_slug'   => 'nastaveni-sablony',
    // ));
}

/**
 * Přidání admin stránky pro vyhodnocení tipů
 */
function tipnijinak_add_evaluate_tips_menu() {
    add_submenu_page(
        'nastaveni-sablony',
        'Vyhodnocení tipů',
        'Vyhodnocení tipů',
        'manage_options',
        'tipnijinak-vyhodnoceni-tipu',
        'tipnijinak_evaluate_tips_page_content'
    );
}
add_action('admin_menu', 'tipnijinak_add_evaluate_tips_menu', 100);

/**
 * Zobrazení stránky pro vyhodnocení tipů
 */
function tipnijinak_evaluate_tips_page_content() {
    
    // Zpracování formuláře pro vyhodnocení zápasů
    if (isset($_POST['tipnijinak_evaluate_matches']) && current_user_can('manage_options')) {
        // Kontrola nonce
        check_admin_referer('tipnijinak_evaluate_matches_nonce');
        
        $match_ids = isset($_POST['match_ids']) ? $_POST['match_ids'] : array();
        $processed_count = 0;
        $evaluated_count = 0;
        
        if (!empty($match_ids)) {
            foreach ($match_ids as $match_id) {
                $match_id = intval($match_id);
                if ($match_id > 0) {
                    $processed_count++;
                    $evaluated_count += tipnijinak_evaluate_match($match_id);
                }
            }
            
            add_settings_error(
                'tipnijinak_evaluate_matches',
                'tipnijinak_evaluated',
                sprintf(
                    __('Zpracováno %d zápasů. Vyhodnoceno %d tipů.', 'tipnijinak'),
                    $processed_count,
                    $evaluated_count
                ),
                'success'
            );
        } else {
            add_settings_error(
                'tipnijinak_evaluate_matches',
                'tipnijinak_no_matches',
                __('Nebyly vybrány žádné zápasy k vyhodnocení.', 'tipnijinak'),
                'error'
            );
        }
    }
    
    // Zobrazení zpráv
    settings_errors('tipnijinak_evaluate_matches');
    
    // Výběr kola a zápasů
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Vyhodnocení tipů', 'tipnijinak'); ?></h1>
        
        <div class="tipnijinak-admin-block">
            <h2><?php esc_html_e('Vyhodnocení ukončených zápasů', 'tipnijinak'); ?></h2>
            <p><?php esc_html_e('Zde můžete vyhodnotit tipy pro ukončené zápasy a přidělit body uživatelům.', 'tipnijinak'); ?></p>
            
            <?php
            // Získání všech ukončených zápasů, které ještě nebyly vyhodnoceny
            $args = array(
                'post_type' => 'zapas',
                'posts_per_page' => -1,
                'meta_query' => array(
                    array(
                        'key' => 'stav_zapasu',
                        'value' => 'ukonceny',
                        'compare' => '=',
                    ),
                ),
            );
            
            $ended_matches = get_posts($args);
            
            if (!empty($ended_matches)) {
                ?>
                <form method="post" action="">
                    <?php wp_nonce_field('tipnijinak_evaluate_matches_nonce'); ?>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th style="width: 30px;"><input type="checkbox" id="select-all-matches"></th>
                                <th><?php esc_html_e('Zápas', 'tipnijinak'); ?></th>
                                <th><?php esc_html_e('Datum', 'tipnijinak'); ?></th>
                                <th><?php esc_html_e('Skóre', 'tipnijinak'); ?></th>
                                <th><?php esc_html_e('Stav', 'tipnijinak'); ?></th>
                                <th><?php esc_html_e('Počet tipů', 'tipnijinak'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ended_matches as $match) {
                                $match_details = tipnijinak_get_match_details($match->ID);
                                
                                // Počet tipů pro tento zápas
                                global $wpdb;
                                $tips_table = $wpdb->prefix . 'tipnijinak_tips';
                                $tips_count = $wpdb->get_var(
                                    $wpdb->prepare(
                                        "SELECT COUNT(*) FROM $tips_table WHERE match_id = %d",
                                        $match->ID
                                    )
                                );
                                
                                ?>
                                <tr>
                                    <td><input type="checkbox" name="match_ids[]" value="<?php echo esc_attr($match->ID); ?>" class="match-checkbox"></td>
                                    <td>
                                        <strong>
                                            <?php 
                                            echo esc_html($match_details['teams']['home']['name'] . ' vs ' . $match_details['teams']['away']['name']);
                                            ?>
                                        </strong>
                                    </td>
                                    <td><?php echo esc_html($match_details['date'] . ' ' . $match_details['time']); ?></td>
                                    <td><?php echo esc_html($match_details['score']); ?></td>
                                    <td><?php echo esc_html($match_details['status']); ?></td>
                                    <td><?php echo esc_html($tips_count); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" name="tipnijinak_evaluate_matches" class="button button-primary" value="<?php esc_attr_e('Vyhodnotit vybrané zápasy', 'tipnijinak'); ?>">
                    </p>
                </form>
                
                <script>
                    // Skript pro zaškrtnutí všech checkboxů
                    document.addEventListener('DOMContentLoaded', function() {
                        const selectAllCheckbox = document.getElementById('select-all-matches');
                        const matchCheckboxes = document.querySelectorAll('.match-checkbox');
                        
                        if (selectAllCheckbox && matchCheckboxes.length > 0) {
                            selectAllCheckbox.addEventListener('change', function() {
                                const isChecked = this.checked;
                                matchCheckboxes.forEach(function(checkbox) {
                                    checkbox.checked = isChecked;
                                });
                            });
                        }
                    });
                </script>
                <?php
            } else {
                echo '<p>' . esc_html__('Nejsou dostupné žádné ukončené zápasy k vyhodnocení.', 'tipnijinak') . '</p>';
            }
            ?>
        </div>
    </div>
    <?php
}
// Již není potřeba - používáme vlastní admin stránku

// Metoda 1: PHP způsob (vložit do functions.php)
function add_login_status_body_class($classes) {
    if (is_user_logged_in()) {
        $classes[] = 'is-user-logged-in';
    } else {
        $classes[] = 'is-user-not-logged-in';
    }
    return $classes;
}
add_filter('body_class', 'add_login_status_body_class');

/**
 * Získá počet tipů uživatele v soutěži
 * 
 * @param int $competition_id ID soutěže
 * @param int $user_id ID uživatele (výchozí: aktuálně přihlášený uživatel)
 * @return int Počet tipů
 */
/*function tipnijinak_get_user_tips_count($competition_id, $user_id = 0) {
    if (!$competition_id) {
        return 0;
    }
    
    if (!$user_id && is_user_logged_in()) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return 0;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'tipnijinak_tips';
    
    $count = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND competition_id = %d",
            $user_id,
            $competition_id
        )
    );
    
    return $count !== null ? intval($count) : 0;
}
*/

/**
 * Získá tipy uživatele pro konkrétní kolo
 * 
 * @param int $round_id ID kola
 * @param int $user_id ID uživatele (výchozí: aktuálně přihlášený uživatel)
 * @return array Seznam tipů
 */
function tipnijinak_get_user_round_tips($round_id, $user_id = 0) {
    if (!$round_id) {
        return array();
    }
    
    if (!$user_id && is_user_logged_in()) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return array();
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'tipnijinak_tips';
    
    $tips = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT match_id, tip FROM $table_name WHERE user_id = %d AND round_id = %d",
            $user_id,
            $round_id
        ),
        ARRAY_A
    );

    // Přidat odds = 0 pro kompatibilitu s ostatními funkcemi
    if ($tips) {
        foreach ($tips as &$tip) {
            $tip['odds'] = 0;
        }
    }

    return $tips ? $tips : array();
}

/**
 * Získá tipy uživatele pro konkrétní kolo z Custom Post Type
 * 
 * @param int $round_id ID kola
 * @param int $user_id ID uživatele (výchozí: aktuálně přihlášený uživatel)
 * @return array Seznam tipů
 */
function tipnijinak_get_user_round_tips_alt($round_id, $user_id = 0) {
    if (!$round_id) {
        return array();
    }
    
    if (!$user_id && is_user_logged_in()) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return array();
    }
    
    // Načíst tipy z Custom Post Type user_tip
    $args = array(
        'post_type' => 'user_tip',
        'posts_per_page' => -1,
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => 'tip_user',
                'value' => $user_id,
            ),
            array(
                'key' => 'tip_round',
                'value' => $round_id,
            ),
        ),
    );
    
    $query = new WP_Query($args);
    $tips = array();
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();

            // Získat match_id a převést na int pokud je WP_Post objekt
            $match_field = get_field('tip_match', $post_id);
            $match_id = $match_field;
            if (is_object($match_field) && isset($match_field->ID)) {
                $match_id = $match_field->ID;
            } elseif (is_array($match_field) && isset($match_field['ID'])) {
                $match_id = $match_field['ID'];
            }

            $tips[] = array(
                'match_id' => $match_id,
                'tip' => get_field('tip_value', $post_id),
                'odds' => get_field('tip_odds', $post_id) ?: 0,
                'points' => get_field('tip_points', $post_id) ?: 0,
                'evaluated' => get_field('tip_evaluated', $post_id) ?: false,
            );
        }
        wp_reset_postdata();
    }
    
    return $tips;
}

/**
 * Získá umístění uživatele v žebříčku soutěže
 * 
 * @param int $competition_id ID soutěže
 * @param int $user_id ID uživatele (výchozí: aktuálně přihlášený uživatel)
 * @return int Umístění v žebříčku (pozice + 1)
 */
function tipnijinak_get_user_ranking($competition_id, $user_id = 0, $round_id = 0) {
    if (!$competition_id) {
        return 0;
    }

    if (!$user_id && is_user_logged_in()) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return 0;
    }

    // Použít CPT systém - získat všechny vyhodnocené tipy pro soutěž
    $users_with_tips_args = array(
        'post_type' => 'user_tip',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_query' => array(
            array(
                'key' => 'tip_competition',
                'value' => $competition_id,
            ),
            array(
                'key' => 'tip_evaluated',
                'value' => '1',
            )
        )
    );

    if ($round_id) {
        $users_with_tips_args['meta_query'][] = array(
            'key' => 'tip_round',
            'value' => $round_id,
        );
    }

    $user_tips_query = new WP_Query($users_with_tips_args);

    if (!$user_tips_query->have_posts()) {
        return 0;
    }

    $tip_ids = $user_tips_query->posts;
    $users_points = array();

    // Pro každý tip získáme ID uživatele a body
    foreach ($tip_ids as $tip_id) {
        $tip_user_id = get_field('tip_user', $tip_id);
        $points = intval(get_field('tip_points', $tip_id));

        // Ujistíme se, že user_id je integer
        if (is_object($tip_user_id) && isset($tip_user_id->ID)) {
            $tip_user_id = $tip_user_id->ID;
        } elseif (is_array($tip_user_id) && isset($tip_user_id['ID'])) {
            $tip_user_id = $tip_user_id['ID'];
        } else {
            $tip_user_id = intval($tip_user_id);
        }

        // Přeskočíme neplatné user_id
        if ($tip_user_id <= 0) {
            continue;
        }

        if (!isset($users_points[$tip_user_id])) {
            $users_points[$tip_user_id] = 0;
        }

        $users_points[$tip_user_id] += $points;
    }

    if (empty($users_points)) {
        return 0;
    }

    // Seřadit uživatele podle bodů sestupně
    arsort($users_points);

    // Najít pozici uživatele v žebříčku
    $position = 1;
    foreach ($users_points as $uid => $total_points) {
        if ($uid == $user_id) {
            return $position;
        }
        $position++;
    }

    return 0; // Uživatel není v žebříčku
}

/**
 * Povolení nahrávání SVG souborů
 */
function tipnijinak_allow_svg_upload($mimes) {
    $mimes['svg'] = 'image/svg+xml';
    $mimes['svgz'] = 'image/svg+xml';
    return $mimes;
}
add_filter('upload_mimes', 'tipnijinak_allow_svg_upload');

/**
 * Oprava MIME typu pro SVG soubory
 */
function tipnijinak_fix_svg_mime_type($data, $file, $filename, $mimes) {
    $ext = isset($data['ext']) ? $data['ext'] : '';
    
    if (strlen($ext) < 1) {
        $exploded = explode('.', $filename);
        $ext = strtolower(end($exploded));
    }
    
    if ($ext === 'svg') {
        $data['type'] = 'image/svg+xml';
        $data['ext'] = 'svg';
    } elseif ($ext === 'svgz') {
        $data['type'] = 'image/svg+xml';
        $data['ext'] = 'svgz';
    }
    
    return $data;
}
add_filter('wp_check_filetype_and_ext', 'tipnijinak_fix_svg_mime_type', 10, 4);

/**
 * Přidání podpory pro SVG v Media Library
 */
function tipnijinak_svg_thumbs($response, $attachment, $meta) {
    if ($response['type'] === 'image' && $response['subtype'] === 'svg+xml' && class_exists('SimpleXMLElement')) {
        try {
            $path = get_attached_file($attachment->ID);
            if (@file_exists($path)) {
                $svg = new SimpleXMLElement(@file_get_contents($path));
                $src = $response['url'];
                $width = (int) $svg['width'];
                $height = (int) $svg['height'];
                
                // SVG nemají velikosti
                $response['sizes'] = array();
                $response['sizes']['full'] = array(
                    'url' => $src,
                    'width' => $width,
                    'height' => $height,
                    'orientation' => $width > $height ? 'landscape' : 'portrait'
                );
            }
        } catch (Exception $e) {}
    }
    
    return $response;
}
add_filter('wp_prepare_attachment_for_js', 'tipnijinak_svg_thumbs', 10, 3);

/**
 * Automatické generování titulu zápasu při uložení
 * Formát: {Domácí tým} – {Hosté} | {datum} {čas}
 *
 * Používá acf/save_post hook, který se spouští PO uložení ACF polí,
 * takže funguje i při prvním vytvoření postu (ne jen při updatu).
 */
function tipnijinak_auto_generate_match_title($post_id) {
    // Kontrola post type
    if (get_post_type($post_id) !== 'zapas') {
        return;
    }

    // Nekontrolovat při autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Nekontrolovat revize
    if (wp_is_post_revision($post_id)) {
        return;
    }

    // Získat ACF data
    $domaci_tym_id = get_field('domaci_tym', $post_id);
    $hoste_tym_id = get_field('hoste_tym', $post_id);
    $datum_zapasu = get_field('datum_zapasu', $post_id);

    // Pokud nemáme oba týmy, neměnit titul
    if (!$domaci_tym_id || !$hoste_tym_id) {
        return;
    }

    // Získat názvy týmů
    $domaci_nazev = get_the_title($domaci_tym_id);
    $hoste_nazev = get_the_title($hoste_tym_id);

    // Vytvořit základní titul
    $new_title = $domaci_nazev . ' – ' . $hoste_nazev;

    // Přidat datum pokud existuje
    if ($datum_zapasu) {
        $timestamp = strtotime($datum_zapasu);
        if ($timestamp) {
            $date_formatted = date('d.m.Y H:i', $timestamp);
            $new_title .= ' | ' . $date_formatted;
        }
    }

    // Zkontrolovat jestli se titul skutečně změnil
    $current_title = get_the_title($post_id);
    if ($current_title === $new_title) {
        return;
    }

    // Odstranit hook aby nedošlo k nekonečné smyčce
    remove_action('acf/save_post', 'tipnijinak_auto_generate_match_title', 20);

    // Aktualizovat titul
    wp_update_post(array(
        'ID' => $post_id,
        'post_title' => $new_title
    ));

    // Znovu přidat hook
    add_action('acf/save_post', 'tipnijinak_auto_generate_match_title', 20);
}
add_action('acf/save_post', 'tipnijinak_auto_generate_match_title', 20);