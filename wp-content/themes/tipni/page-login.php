<?php
/**
 * Template Name: Přihlášení
 *
 * @package TipniJinak
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Zakázat cachování přihlašovací stránky
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Získat redirect URL z parametru nebo použít home
$redirect_to = isset($_GET['redirect_to']) ? esc_url_raw($_GET['redirect_to']) : home_url();

// Pokud je uživatel již přihlášený, přesměruj ho
if (is_user_logged_in()) {
    wp_redirect($redirect_to);
    exit;
}

get_header();
?>

<div class="content-wrapper title-middle">
    <h1><?php echo esc_html(get_the_title()); ?></h1>
    <div class="form-holder small-container">
        <form class="login" id="ajax-login-form" action="javascript:void(0);" method="post">
            <div class="label-holder">
                <label for="user_login"><?php esc_html_e('Uživatelské jméno', 'tipnijinak'); ?></label>
                <input type="text" id="user_login" name="user_login" placeholder="<?php esc_attr_e('Vaše jméno', 'tipnijinak'); ?>" required>
            </div>
            <div class="label-holder">
                <label for="user_pass"><?php esc_html_e('Heslo', 'tipnijinak'); ?></label>
                <input type="password" id="user_pass" name="user_pass" placeholder="***********" required>
            </div>
            <div class="column">
                <div class="forgot-password">
                    <?php esc_html_e('Ztratil jsi heslo?', 'tipnijinak'); ?> <a href="<?php echo esc_url(wp_lostpassword_url()); ?>"><?php esc_html_e('Zapomenuté heslo?', 'tipnijinak'); ?></a>
                </div>

                <div class="btns-holder">
                    <a class="btn btn-secondary" href="<?php echo esc_url(site_url('/registrace')); ?>">
                        <?php esc_html_e('Registrace', 'tipnijinak'); ?>
                    </a>
                    <button class="btn btn-primary" type="submit" id="login-submit"><?php esc_html_e('Přihlásit se', 'tipnijinak'); ?></button>
                    <input type="hidden" name="redirect_to" value="<?php echo esc_url($redirect_to); ?>" />
                    <?php wp_nonce_field('ajax-login-nonce', 'security'); ?>
                </div>
            </div>
            
            <div class="login-error" style="display:none; margin-top:15px; color:#ff443b; font-weight:bold; text-align:center;"></div>
            <div class="login-loading" style="display:none; text-align:center;"><?php esc_html_e('Přihlašování...', 'tipnijinak'); ?></div>
        </form>
    </div>
</div>

<!-- JavaScript kód byl přesunut do samostatného souboru /assets/js/login.js -->

<?php
get_footer();