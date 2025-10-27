<?php
/**
 * The template for displaying single competition
 *
 * @package TipniJinak
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

get_header();

// Prepare all competition data using helper function
$competition_data = tipnijinak_prepare_competition_data();

// DEBUG: Zobrazit obsah competition_data pro administrátory - VYPNUTO
/*if (current_user_can('administrator')) {
    echo '<div style="background: #333; color: #fff; padding: 15px; margin: 20px 0; border-radius: 5px; position: fixed; top: 10px; right: 10px; z-index: 9999; max-width: 400px; font-size: 12px; overflow: auto; max-height: 300px;">';
    echo '<strong>DEBUG - Competition Data:</strong><br>';
    echo 'Competition ID: ' . ($competition_data['competition_id'] ?? 'NULL') . '<br>';
    echo 'Category: ' . ($competition_data['category'] ?? 'NULL') . '<br>';
    echo 'Active tab: ' . ($competition_data['active_tab'] ?? 'NULL') . '<br>';
    echo 'Rounds count: ' . (isset($competition_data['rounds']) ? count($competition_data['rounds']) : 'NULL') . '<br>';
    echo 'Current round: ' . (isset($competition_data['current_round']) ? 'EXISTS' : 'NULL') . '<br>';
    echo 'Has access: ' . ($competition_data['has_access'] ? 'YES' : 'NO') . '<br>';
    echo 'Product ID: ' . ($competition_data['product_id'] ?? 'NULL') . '<br>';
    echo '</div>';
}*/

extract($competition_data); // Extract variables for backward compatibility
?>

<div class="main">
    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
        <!-- Main competition header -->
        <div class="main-competition">
            <div class="img-holder">
                <?php 
                if (has_post_thumbnail()) {
                    the_post_thumbnail('large');
                } else {
                    echo '<img src="' . esc_url(get_template_directory_uri() . '/assets/images/competition-placeholder.jpg') . '" alt="' . esc_attr(get_the_title()) . '">';
                }
                ?>
            </div>
            <div class="competition-text">
                <div class="subtitle"><?php echo esc_html($category); ?></div>
                <h1><?php the_title(); ?></h1>
                <div class="competition-description">
                <?php the_content(); ?>
                </div>
            </div>
        </div>
        
        <!-- Tab navigation -->
        <ul class="window-switcher">
            <li data="competitions" class="<?php echo $active_tab === 'competitions' ? 'active' : ''; ?>">Ceny</li>
            <li data="guessing" class="<?php echo $active_tab === 'guessing' ? 'active' : ''; ?>">Tipování</li>
            <li data="guessing-results" class="<?php echo $active_tab === 'guessing-results' ? 'active' : ''; ?>">Výsledky tipování</li>
            <li data="guessing-ranking" class="<?php echo $active_tab === 'guessing-ranking' ? 'active' : ''; ?>">Žebříček</li>
        </ul>
        
        <!-- Tab content -->
        <div class="window-switcher__windows">
            <?php 
            // DEBUG: Check template parts loading
            if (current_user_can('administrator')) {
                echo '<div style="background: red; color: white; padding: 10px;">DEBUG: Loading template parts...</div>';
                echo '<div style="background: blue; color: white; padding: 5px;">Template dir: ' . get_template_directory() . '</div>';
            }
            
            // Load template parts with competition data
            get_template_part('template-parts/competition/prizes-tab', null, $competition_data);
            get_template_part('template-parts/competition/betting-tab', null, $competition_data);
           get_template_part('template-parts/competition/results-tab', null, $competition_data);
         get_template_part('template-parts/competition/ranking-tab', null, $competition_data);
            ?>
        </div>
    </article>

    <!-- Lightbox Modal -->
    <div id="prize-lightbox" class="lightbox-overlay">
        <div class="lightbox-container">
            <span class="lightbox-close">&times;</span>
            <img id="lightbox-image" src="" alt="">
            <div class="lightbox-caption"></div>
        </div>
    </div>
</div>

<?php
// JavaScript variables for AJAX and functionality
wp_localize_script('tipnijinak-single-soutez', 'tipnijinak_vars', array(
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('tipnijinak_tips_nonce'),
    'competition_id' => intval($competition_id),
    'round_id' => $current_round ? intval($current_round['id']) : 0,
    'max_tips' => get_field('max_tipy', $competition_id) ?: 15,
    'min_tips' => get_field('je_hlavni', $competition_id) ? 1 : (get_field('max_tipy', $competition_id) ?: 15),
    'is_main_competition' => get_field('je_hlavni', $competition_id) ?: true,
    'body_text' => $body_text,
    'saved_tips_count' => $current_round ? count(tipnijinak_get_user_round_tips($current_round['id'])) : 0
));

// JavaScript translations
wp_localize_script('tipnijinak-single-soutez', 'tipnijinak_translations', array(
    'saving_tips' => __('Ukládám tipy...', 'tipnijinak'),
    'select_at_least_one' => __('Musíte vybrat alespoň jeden tip.', 'tipnijinak'),
    'save_update' => __('Uložit/aktualizovat', 'tipnijinak'),
    'error_saving_tips' => __('Došlo k chybě při ukládání tipů. Zkuste to prosím znovu.', 'tipnijinak')
));

// Enqueue JavaScript
wp_enqueue_script('tipnijinak-single-soutez');

get_footer();
?>