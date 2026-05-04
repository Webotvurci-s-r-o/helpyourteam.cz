<?php
/**
 * Template part for displaying competition results tab
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
$user_points = $args['user_points'] ?? 0;
$user_tips_count = $args['user_tips_count'] ?? 0;
$user_ranking = $args['user_ranking'] ?? 0;
$rounds = $args['rounds'] ?? array();
?>

<!-- TAB 3: Guessing-Results/Výsledky tipování -->
<div class="guessing-results <?php echo $active_tab === 'guessing-results' ? 'active' : ''; ?>">
    <?php if ($product_id && !$has_access) : ?>
    <div class="competition-locked">
        <p><?php esc_html_e('Pro přístup k výsledkům musíte zakoupit přístup.', 'tipnijinak'); ?></p>
        <a href="<?php echo esc_url(add_query_arg('tab', 'competitions', get_permalink())); ?>" class="btn btn-primary"><?php esc_html_e('Zobrazit detaily', 'tipnijinak'); ?></a>
    </div>
    <?php elseif (is_user_logged_in()) : ?>
    
    <!-- Celkový souhrn -->
    <div class="guessing-summary">
        <div class="guessing-summary__points"><?php printf(__('Body v aktuálním kole: %d', 'tipnijinak'), $user_points); ?></div>
        <div class="guessing-summary__tips"><?php printf(__('Tipnutých zápasů v kole: %d', 'tipnijinak'), $user_tips_count); ?></div>
        <div class="guessing-summary__ranking"><?php printf(__('Umístění v kole: %d', 'tipnijinak'), $user_ranking); ?></div>
    </div>
    
    <?php 
    // Debug - zjistit současného uživatele
    $current_user_id = get_current_user_id();
    
    // Získat všechny tipy uživatele pro tuto soutěž seskupené podle kol
    $user_all_tips = array();
    $total_earned_points = 0;
    $total_correct_tips = 0;
    $total_tips_count = 0;
    $total_evaluated_count = 0;
    
    // Projít všechna kola a získat tipy
    foreach ($rounds as $round) {
        // Použít Custom Post Type funkci pro načtení tipů
        $round_tips = function_exists('tipnijinak_get_user_round_tips_alt') ?
            tipnijinak_get_user_round_tips_alt($round['id'], $current_user_id) :
            tipnijinak_get_user_round_tips($round['id'], $current_user_id);
        
        if (!empty($round_tips)) {
            $round_data = array(
                'round_info' => $round,
                'tips' => array(),
                'points' => 0,
                'correct' => 0,
                'total' => count($round_tips)
            );
            
            foreach ($round_tips as $tip) {
                $match = tipnijinak_get_match_details($tip['match_id']);
                if (!$match) continue;

                $tip_data = array(
                    'match' => $match,
                    'user_tip' => $tip['tip'],
                    'odds' => floatval($tip['odds'] ?? 0),
                    'points' => 0,
                    'is_correct' => null
                );

                // Pokud je tip již vyhodnocený v CPT, použít uložené body
                if (!empty($tip['evaluated']) && isset($tip['points'])) {
                    $stored_points = intval($tip['points']);

                    // Určit, jestli byl tip správný nebo špatný podle bodů
                    if ($stored_points > 0) {
                        $tip_data['is_correct'] = true;
                        $tip_data['points'] = $stored_points;
                        $round_data['points'] += $stored_points;
                        $round_data['correct']++;
                        $total_earned_points += $stored_points;
                        $total_correct_tips++;
                        $total_evaluated_count++;
                    } elseif ($stored_points < 0) {
                        $tip_data['is_correct'] = false;
                        $tip_data['points'] = abs($stored_points); // Pro zobrazení používáme absolutní hodnotu
                        $round_data['points'] += $stored_points; // Přičíst zápornou hodnotu (= odečíst)
                        $total_earned_points += $stored_points;
                        $total_evaluated_count++;
                    }
                }
                // Zápas ukončený ale nevyhodnocený - necháme is_correct = null,
                // zobrazí se jako "Čeká na vyhodnocení"

                $round_data['tips'][] = $tip_data;
                $total_tips_count++;
            }
        
            $user_all_tips[] = $round_data;
        }
    }
    
    // Seřadit kola podle data, nejnovější nahoře
    usort($user_all_tips, function($a, $b) {
        return $b['round_info']['id'] - $a['round_info']['id'];
    });

    // Debug: Porovnat hodnoty z funkce vs. počítané v šabloně - DOČASNĚ VYPNUTO
    if (false && get_current_user_id() === 1) {
        $difference = $user_points - $total_earned_points;
        if ($difference != 0) {
            echo '<div style="background: #ff6b6b; color: #fff; padding: 15px; margin: 20px 0; border-radius: 5px;">';
            echo '<strong>⚠️ DEBUG: Nesrovnalost v bodech (pouze pro administrátory)</strong><br>';
            echo 'Celkový souhrn (funkce): ' . $user_points . ' bodů<br>';
            echo 'Statistiky tipování (šablona): ' . $total_earned_points . ' bodů<br>';
            echo 'Rozdíl: ' . $difference . ' bodů<br><br>';

            // Najít všechny vyhodnocené tipy pro tuto soutěž
            $all_evaluated_tips = new WP_Query(array(
                'post_type' => 'user_tip',
                'posts_per_page' => -1,
                'meta_query' => array(
                    'relation' => 'AND',
                    array('key' => 'tip_user', 'value' => $current_user_id),
                    array('key' => 'tip_competition', 'value' => $competition_id),
                    array('key' => 'tip_evaluated', 'value' => '1'),
                )
            ));

            echo 'Celkem vyhodnocených tipů v CPT: ' . $all_evaluated_tips->found_posts . '<br>';
            echo 'Celkem tipů zobrazených v šabloně: ' . $total_tips_count . '<br>';

            if ($all_evaluated_tips->have_posts()) {
                echo '<br><strong>Seznam všech vyhodnocených tipů v CPT:</strong><br>';
                while ($all_evaluated_tips->have_posts()) {
                    $all_evaluated_tips->the_post();
                    $tip_id = get_the_ID();

                    // Zkusit ACF i raw meta
                    $match_id = get_field('tip_match', $tip_id) ?: get_post_meta($tip_id, 'tip_match', true);
                    $round_id = get_field('tip_round', $tip_id) ?: get_post_meta($tip_id, 'tip_round', true);
                    $points = get_field('tip_points', $tip_id);
                    if ($points === false || $points === null) {
                        $points = get_post_meta($tip_id, 'tip_points', true);
                    }
                    $tip_value = get_field('tip_value', $tip_id) ?: get_post_meta($tip_id, 'tip_value', true);

                    // Debug raw meta
                    $all_meta = get_post_meta($tip_id);

                    echo sprintf('- Tip ID: %d, Match: %s, Round: %s, Tip: %s, Body: %s<br>',
                        $tip_id,
                        $match_id ?: 'N/A',
                        $round_id ?: 'N/A',
                        $tip_value ?: 'N/A',
                        $points !== false && $points !== null && $points !== '' ? $points : 'N/A'
                    );

                    // Ukázat RAW meta pro první tip
                    if ($tip_id == $all_evaluated_tips->posts[0]->ID) {
                        echo '<small style="opacity: 0.8;">RAW meta první tip: ' . print_r(array_map(function($v) { return $v[0]; }, $all_meta), true) . '</small><br>';
                    }
                }
                wp_reset_postdata();
            }

            echo '</div>';
        }
    }
    ?>
    
    <?php 
    // Debug informace - zobrazit pouze pokud jsou tipy prázdné - DOČASNĚ VYPNUTO
    if (false && empty($user_all_tips) && get_current_user_id() === 1) {
        echo '<div style="background: #333; color: #fff; padding: 15px; margin: 20px 0; border-radius: 5px;">';
        echo '<strong>Debug informace (pouze pro administrátory):</strong><br>';
        echo 'User ID: ' . $current_user_id . '<br>';
        echo 'Počet kol: ' . count($rounds) . '<br>';
        
        global $wpdb;
        $total_user_tips = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}tipnijinak_tips WHERE user_id = %d",
            $current_user_id
        ));
        echo 'Celkový počet tipů v DB: ' . $total_user_tips . '<br>';
        
        // Zkusit získat tipy bez ohledu na kolo
        $all_user_tips = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tipnijinak_tips WHERE user_id = %d LIMIT 5",
            $current_user_id
        ), ARRAY_A);
        
        if (!empty($all_user_tips)) {
            echo 'Ukázka tipů z DB:<br>';
            foreach ($all_user_tips as $tip) {
                echo sprintf('- Match ID: %s, Tip: %s, Round ID: %s<br>', 
                    $tip['match_id'], $tip['tip'], $tip['round_id'] ?? 'N/A');
            }
        }
        echo '</div>';
    }
    
    if (!empty($user_all_tips)) : ?>
    <!-- Souhrn výsledků tipování -->
    <div class="tips-stats">
        <h3><?php esc_html_e('Statistiky tipování', 'tipnijinak'); ?></h3>
        <div class="stats-grid">
            <div class="stat-item">
                <span class="stat-label"><?php esc_html_e('Získané body:', 'tipnijinak'); ?></span>
                <span class="stat-value"><?php echo esc_html($total_earned_points); ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-label"><?php esc_html_e('Správné tipy:', 'tipnijinak'); ?></span>
                <span class="stat-value"><?php echo esc_html($total_correct_tips); ?> / <?php echo esc_html($total_evaluated_count); ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-label"><?php esc_html_e('Úspěšnost:', 'tipnijinak'); ?></span>
                <span class="stat-value"><?php echo $total_evaluated_count > 0 ? round(($total_correct_tips / $total_evaluated_count) * 100, 1) : 0; ?>%</span>
            </div>
        </div>
    </div>
    
    <!-- Výpis tipů podle kol -->
    <div class="rounds-results">
        <?php foreach ($user_all_tips as $round_data) : ?>
        <div class="round-result">
            <div class="round-header">
                <h4><?php echo esc_html($round_data['round_info']['cislo_kola'] . '. kolo'); ?></h4>
                <div class="round-meta">
                    <span class="round-date"><?php echo esc_html($round_data['round_info']['datum_od_format'] . ' - ' . $round_data['round_info']['datum_do_format']); ?></span>
                    <span class="round-status"><?php echo esc_html($round_data['round_info']['stav_kola_text']); ?></span>
                </div>
                <div class="round-stats">
                    <span class="round-points"><?php printf(__('Body: %d', 'tipnijinak'), $round_data['points']); ?></span>
                    <span class="round-correct"><?php printf(__('Správné: %d/%d', 'tipnijinak'), $round_data['correct'], $round_data['total']); ?></span>
                </div>
            </div>
            
            <div class="matches">
                <?php foreach ($round_data['tips'] as $tip_data) : 
                    $match = $tip_data['match'];
                    $user_tip = $tip_data['user_tip'];
                    $is_correct = $tip_data['is_correct'];
                    $points = $tip_data['points'];
                ?>
                <div class="match">
                    <div class="match-time">
                        <span class="match-time__hours"><?php echo esc_html($match['time']); ?></span>
                        <span class="match-time__date"><?php echo esc_html($match['date']); ?></span>
                    </div>
                    <div class="match-info">
                        <div class="home team">
                            <div class="team-name" title="<?php echo esc_attr($match['teams']['home']['name']); ?>"><?php echo esc_html($match['teams']['home']['abbreviation']); ?></div>
                            <div class="logo-holder">
                                <?php if (!empty($match['teams']['home']['logo'])) : ?>
                                    <img src="<?php echo esc_url($match['teams']['home']['logo']); ?>" alt="<?php echo esc_attr($match['teams']['home']['name']); ?>">
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="match-score">
                            <?php echo esc_html($match['score']); ?>
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
                    
                    <?php if ($user_tip !== null) :
                        if ($is_correct !== null) {
                            $tip_class = $is_correct ? 'correct-tip' : 'wrong-tip';
                            // Pro správný tip zobrazit +body, pro špatný tip -body
                            if ($is_correct) {
                                $points_display = '+' . $points;
                            } else {
                                $points_display = '-' . $points;
                            }
                            $points_class = $is_correct ? 'points-gained' : 'points-lost';
                        ?>
                        <div class="user-match-tip <?php echo esc_attr($tip_class); ?>">
                            <?php printf(__('Váš tip: %s', 'tipnijinak'), $user_tip); ?>
                            <span class="<?php echo esc_attr($points_class); ?>"><?php echo esc_html($points_display); ?> bodů</span>
                        </div>
                        <?php } else { ?>
                        <div class="user-match-tip pending-tip">
                            <?php printf(__('Váš tip: %s', 'tipnijinak'), $user_tip); ?>
                            <span class="pending-result"><?php esc_html_e('Čeká na výsledek', 'tipnijinak'); ?></span>
                        </div>
                        <?php } ?>
                    <?php else: ?>
                    <div class="user-match-tip no-tip">
                        <?php esc_html_e('Bez tipu', 'tipnijinak'); ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php else : ?>
    <div class="no-completed-matches">
        <p><?php esc_html_e('Zatím jste neprovedli žádné tipy v této soutěži.', 'tipnijinak'); ?></p>
    </div>
    <?php endif; ?>
    <?php else : ?>
    <div class="login-to-view-results">
        <p><?php esc_html_e('Pro zobrazení výsledků se musíte přihlásit.', 'tipnijinak'); ?></p>
        <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="btn btn-primary"><?php esc_html_e('Přihlásit se', 'tipnijinak'); ?></a>
    </div>
    <?php endif; ?>
</div>