<?php
/**
 * Template part for displaying competition prizes tab
 *
 * @package TipniJinak
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

$competition_id = $args['competition_id'] ?? get_the_ID();
$product_id = $args['product_id'] ?? null;
$has_access = $args['has_access'] ?? true;
$active_tab = $args['active_tab'] ?? '';
?>

<!-- TAB 1: Competitions/Ceny -->
<div class="competitions <?php echo $active_tab === 'competitions' ? 'active' : ''; ?>">
    <?php if ($product_id && !$has_access) : ?>
    <div class="competition-locked">
        <div class="lock-message">
            <h2><?php esc_html_e('Tato soutěž je dostupná pouze po zakoupení', 'tipnijinak'); ?></h2>
            <p><?php esc_html_e('Pro přístup k tipování v této soutěži musíte zakoupit přístup.', 'tipnijinak'); ?></p>
            
            <?php if (is_user_logged_in()) : ?>
                <?php 
                // Získání informací o produktu
                $product = wc_get_product($product_id);
                if ($product) :
                ?>
                    <div class="product-purchase-info">
                        <h3><?php echo esc_html($product->get_name()); ?></h3>
                        <p class="product-price"><?php echo wp_kses_post($product->get_price_html()); ?></p>
                        <p class="product-description"><?php echo wp_kses_post($product->get_short_description()); ?></p>
                        <div class="product-actions">
                            <a href="<?php echo esc_url(get_permalink($product_id)); ?>" class="btn btn-secondary"><?php esc_html_e('Zobrazit detail', 'tipnijinak'); ?></a>
                            <a href="<?php echo esc_url(add_query_arg('add-to-cart', $product_id, wc_get_cart_url())); ?>" class="btn btn-primary"><?php esc_html_e('Zakoupit přístup', 'tipnijinak'); ?></a>
                        </div>
                    </div>
                <?php endif; ?>
            <?php else : ?>
                <p><?php esc_html_e('Pro zakoupení přístupu se musíte nejprve přihlásit nebo registrovat.', 'tipnijinak'); ?></p>
                <div class="auth-actions">
                    <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="btn btn-secondary"><?php esc_html_e('Přihlásit se', 'tipnijinak'); ?></a>
                    <a href="<?php echo esc_url(site_url('/registrace/')); ?>" class="btn btn-primary"><?php esc_html_e('Registrovat', 'tipnijinak'); ?></a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php else : ?>
    <!-- Obsah záložky Ceny -->
    <div class="main-competition">
        <div class="competition-description">
            <?php 
            $prizes_group = get_field('ceny_souteze');
            if (!empty($prizes_group)) : 
                $section_title = !empty($prizes_group['nadpis_sekce']) ? $prizes_group['nadpis_sekce'] : 'O co hrajeme';
                $section_description = !empty($prizes_group['popis_sekce']) ? $prizes_group['popis_sekce'] : '';
                $prizes = !empty($prizes_group['ceny']) ? $prizes_group['ceny'] : array();
            ?>
                <h2><?php echo esc_html($section_title); ?></h2>
                <?php if (!empty($section_description)) : ?>
                    <p><?php echo wp_kses_post($section_description); ?></p>
                <?php endif; ?>
                
                <?php if (!empty($prizes)) : ?>
                    <div class="prizes-list">
                        <?php foreach ($prizes as $prize) : ?>
                            <div class="prize-item">
                                <?php if (!empty($prize['obrazek'])) : ?>
                                    <div class="prize-image">
                                        <img src="<?php echo esc_url($prize['obrazek']['sizes']['medium'] ?? $prize['obrazek']['url']); ?>" 
                                             alt="<?php echo esc_attr($prize['nazev']); ?>"
                                             class="prize-image-clickable"
                                             data-lightbox-src="<?php echo esc_url($prize['obrazek']['url']); ?>"
                                             data-lightbox-caption="<?php echo esc_attr($prize['nazev']); ?>">
                                    </div>
                                <?php endif; ?>
                                <div class="prize-content">
                                    <h3><?php echo esc_html($prize['nazev']); ?></h3>
                                    <?php if (!empty($prize['popis'])) : ?>
                                        <p><?php echo wp_kses_post($prize['popis']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <p><?php esc_html_e('Informace o cenách v této soutěži budou upřesněny.', 'tipnijinak'); ?></p>
                <?php endif; ?>
            <?php else : ?>
                <h2><?php esc_html_e('O co hrajeme', 'tipnijinak'); ?></h2>
                <p><?php esc_html_e('Informace o cenách v této soutěži budou upřesněny.', 'tipnijinak'); ?></p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Další podobné soutěže -->
    <?php
    $related_competitions = get_posts(array(
        'post_type' => 'soutez',
        'posts_per_page' => 3,
        'post__not_in' => array($competition_id),
        'meta_query' => array(
            array(
                'key' => 'aktivni',
                'value' => '1',
                'compare' => '='
            )
        )
    ));
    
    if (!empty($related_competitions)) : 
    ?>
    <div class="other-competitions">
        <h2><?php esc_html_e('Další soutěže', 'tipnijinak'); ?></h2>
        <div class="competitions-grid">
            <?php foreach ($related_competitions as $related) : ?>
            <div class="competition">
                <div class="img-holder">
                    <?php 
                    if (has_post_thumbnail($related->ID)) {
                        echo get_the_post_thumbnail($related->ID, 'medium');
                    } else {
                        echo '<img src="' . esc_url(get_template_directory_uri() . '/assets/images/competition-placeholder.jpg') . '" alt="' . esc_attr($related->post_title) . '">';
                    }
                    ?>
                </div>
                <div class="competition-text">
                    <?php 
                    $rel_terms = wp_get_post_terms($related->ID, 'typ-souteze');
                    $rel_category = !empty($rel_terms) ? $rel_terms[0]->name : '';
                    ?>
                    <div class="subtitle"><?php echo esc_html($rel_category); ?></div>
                    <h3><?php echo esc_html($related->post_title); ?></h3>
                    <div class="competition-description">
                        <?php echo wp_trim_words(get_the_excerpt($related->ID), 20); ?>
                    </div>
                    <div class="btn-holder">
                        <a href="<?php echo esc_url(get_permalink($related->ID)); ?>" class="btn btn-primary"><?php esc_html_e('Vstoupit', 'tipnijinak'); ?></a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>