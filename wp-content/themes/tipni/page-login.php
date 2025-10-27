<?php
/**
 * Template Name: Přihlášení
 *
 * @package TipniJinak
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Pokud je uživatel již přihlášený, přesměruj ho na hlavní stránku
if (is_user_logged_in()) {
    wp_redirect(home_url());
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
                    <input type="hidden" name="redirect_to" value="<?php echo esc_url(home_url()); ?>" />
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