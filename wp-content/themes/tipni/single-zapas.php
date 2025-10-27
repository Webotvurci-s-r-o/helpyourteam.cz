<?php
/**
 * The template for displaying single match
 *
 * @package TipniJinak
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

get_header();

$match_id = get_the_ID();
$match_details = tipnijinak_get_match_details($match_id);
$liga_terms = wp_get_post_terms($match_id, 'liga');
$liga = !empty($liga_terms) ? $liga_terms[0]->name : '';
?>

<div class="main">
    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
        <h1><?php esc_html_e('Zápas', 'tipnijinak'); ?></h1>
        
        <div class="match-detail box profile-box">
            <div class="match match-large">
                <div class="match-time">
                    <span class="match-time__hours"><?php echo esc_html($match_details['time']); ?></span>
                    <span class="match-time__date"><?php echo esc_html($match_details['date']); ?></span>
                </div>
                <div class="match-info">
                    <div class="home team">
                        <div class="team-name"><?php echo esc_html($match_details['teams']['home']['name']); ?></div>
                        <div class="logo-holder">
                            <?php if (!empty($match_details['teams']['home']['logo'])) : ?>
                                <img src="<?php echo esc_url($match_details['teams']['home']['logo']); ?>" alt="<?php echo esc_attr($match_details['teams']['home']['name']); ?>">
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="match-score">
                        <?php echo esc_html($match_details['score']); ?>
                    </div>
                    <div class="away team">
                        <div class="team-name"><?php echo esc_html($match_details['teams']['away']['name']); ?></div>
                        <div class="logo-holder">
                            <?php if (!empty($match_details['teams']['away']['logo'])) : ?>
                                <img src="<?php echo esc_url($match_details['teams']['away']['logo']); ?>" alt="<?php echo esc_attr($match_details['teams']['away']['name']); ?>">
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="match-meta">
                <div class="table-info">
                    <div class="table-row">
                        <div class="table-row__name"><?php esc_html_e('Liga:', 'tipnijinak'); ?></div>
                        <div class="table-row__value"><?php echo esc_html($liga); ?></div>
                    </div>
                    <div class="table-row">
                        <div class="table-row__name"><?php esc_html_e('Stav zápasu:', 'tipnijinak'); ?></div>
                        <div class="table-row__value">
                            <?php 
                            $stav = $match_details['status'];
                            $stavy = array(
                                'planovany' => esc_html__('Plánovaný', 'tipnijinak'),
                                'probihajici' => esc_html__('Probíhající', 'tipnijinak'),
                                'ukonceny' => esc_html__('Ukončený', 'tipnijinak'),
                                'zrusen' => esc_html__('Zrušený', 'tipnijinak')
                            );
                            echo isset($stavy[$stav]) ? $stavy[$stav] : esc_html__('Neznámý', 'tipnijinak');
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="entry-content">
                <?php the_content(); ?>
            </div>
            
            <?php if ($stav === 'planovany' || $stav === 'probihajici') : ?>
            <div class="betting-options">
                <h3><?php esc_html_e('Tipnout výsledek', 'tipnijinak'); ?></h3>
                <div class="odds">
                    <div class="odds-button">1</div>
                    <div class="odds-button">0</div>
                    <div class="odds-button">2</div>
                </div>
                <div class="btn-holder">
                    <a href="#" class="btn btn-primary"><?php esc_html_e('Potvrdit tip', 'tipnijinak'); ?></a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </article>
</div>

<?php
get_footer();