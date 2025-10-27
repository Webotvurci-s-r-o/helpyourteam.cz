<?php
/**
 * The template for displaying the footer
 *
 * @package TipniJinak
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
?>
    </div><!-- .content-wrapper -->
    <footer id="footer">
        <?php if (function_exists('get_field') && get_field('partneri', 'option')): ?>
        <div class="partners">
            <h4><?php echo esc_html(get_field('nadpis_partneri', 'option') ?: 'Naši partneři'); ?></h4>
            <div class="partners-inner">
                <?php foreach (get_field('partneri', 'option') as $partner): ?>
                    <div class="partner">
                        <a href="<?php echo esc_url($partner['odkaz'] ?: '#'); ?>">
                            <img src="<?php echo esc_url(wp_get_attachment_image_url($partner['logo'], 'medium')); ?>" alt="<?php echo esc_attr($partner['nazev']); ?>">
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="footer-bottom">
            <h4><?php echo esc_html(get_field('nadpis_paticka', 'option') ?: 'Soutěžení'); ?></h4>
            <div class="footer-bottom__inner">
                <div class="logo-n-socials">
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="logo">
                        <?php 
                        if (function_exists('the_custom_logo') && has_custom_logo()) {
                            the_custom_logo();
                        } else {
                            echo '<img src="' . esc_url(get_template_directory_uri() . '/assets/images/logo.svg') . '" alt="' . get_bloginfo('name') . '">';
                        }
                        ?>
                    </a>
                    <?php if (function_exists('get_field') && get_field('socialni_site', 'option')): ?>
                    <div class="socials">
                        <?php foreach (get_field('socialni_site', 'option') as $social): ?>
                            <a href="<?php echo esc_url($social['odkaz']); ?>">
                                <img src="<?php echo esc_url(wp_get_attachment_image_url($social['ikona'], 'thumbnail')); ?>" alt="<?php echo esc_attr($social['nazev']); ?>">
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php
                wp_nav_menu(
                    array(
                        'theme_location' => 'footer',
                        'container'      => '',
                        'fallback_cb'    => false,
                    )
                );
                ?>
            </div>
        </div>
        <div class="copyright">
            <span>© <?php echo esc_html(get_bloginfo('name') . ' ' . date('Y')); ?></span>
            <?php
            wp_nav_menu(
                array(
                    'theme_location' => 'copyright',
                    'container'      => '',
                    'fallback_cb'    => false,
                    'items_wrap'     => '%3$s', // Výpis pouze položek menu bez obalu ul
                    'walker'         => new Copyright_Menu_Walker(),
                )
            );
            ?>
        </div>
    </footer>
</div><!-- .overall-wrapper -->

<?php wp_footer(); ?>

</body>
</html>