<?php
/**
 * WooCommerce Blocks Checkout Integration
 *
 * @package TipniJinak
 */

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

defined('ABSPATH') || exit;

/**
 * Class Tipnijinak_Checkout_Blocks_Integration
 */
class Tipnijinak_Checkout_Blocks_Integration implements IntegrationInterface {

    /**
     * Název integrace
     *
     * @return string
     */
    public function get_name() {
        return 'tipnijinak-checkout';
    }

    /**
     * Inicializace integrace
     */
    public function initialize() {
        error_log('Tipnijinak Checkout Blocks - Initialize called');
        $this->register_block_frontend_scripts();
        $this->register_block_editor_scripts();
    }

    /**
     * Registrace frontend scriptů
     */
    private function register_block_frontend_scripts() {
        $script_path = '/assets/js/checkout-blocks.js';
        $script_url  = get_template_directory_uri() . $script_path;
        $script_file = get_template_directory() . $script_path;

        error_log('Registering script: ' . $script_url);
        error_log('Script file exists: ' . (file_exists($script_file) ? 'yes' : 'no'));

        wp_register_script(
            'tipnijinak-checkout-blocks-frontend',
            $script_url,
            array(
                'wc-blocks-checkout',
                'wc-blocks-data-store',
                'wp-element',
                'wp-i18n',
                'wp-components',
            ),
            file_exists($script_file) ? filemtime($script_file) : '1.0.0',
            true
        );

        // FORCE ENQUEUE - pro testování
        add_action('wp_enqueue_scripts', function() {
            if (is_checkout()) {
                error_log('Force enqueueing tipnijinak-checkout-blocks-frontend on checkout');
                wp_enqueue_script('tipnijinak-checkout-blocks-frontend');
            }
        });

        // Lokalizace pro JS
        wp_localize_script('tipnijinak-checkout-blocks-frontend', 'tipnijinakCheckout', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('tipnijinak_checkout_ajax'),
            'i18n'    => array(
                'additionalInfo'      => __('Další údaje', 'tipnijinak'),
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
            ),
            'privacyPolicyUrl'    => get_privacy_policy_url(),
            'termsConditionsUrl'  => get_permalink(wc_terms_and_conditions_page_id()),
            'leagues'             => tipnijinak_get_leagues_for_js(),
        ));
    }

    /**
     * Registrace editor scriptů
     */
    private function register_block_editor_scripts() {
        // Pro editor nepotřebujeme speciální scripty
    }

    /**
     * Script handles pro frontend
     *
     * @return array
     */
    public function get_script_handles() {
        return array('tipnijinak-checkout-blocks-frontend');
    }

    /**
     * Script handles pro editor
     *
     * @return array
     */
    public function get_editor_script_handles() {
        return array();
    }

    /**
     * Data pro script
     *
     * @return array
     */
    public function get_script_data() {
        return array();
    }
}

/**
 * Helper funkce pro získání lig pro JS
 */
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
