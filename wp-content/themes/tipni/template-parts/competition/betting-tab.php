<?php
/**
 * Template part for displaying competition betting tab
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
$rounds = $args['rounds'] ?? array();
$current_round = $args['current_round'] ?? null;
$body_text = $args['body_text'] ?? '30 bodů';

// DEBUG pro administrátory - diagnostika problému s taby - DOČASNĚ VYPNUTO
if (false && get_current_user_id() === 1 && isset($_GET['debug'])) {
    echo '<div style="background: #2196F3; color: #fff; padding: 10px; margin: 10px 0; border-radius: 5px; font-size: 12px;">';
    echo '<strong>DEBUG - Betting Tab Data:</strong><br>';
    echo 'Competition ID: ' . $competition_id . '<br>';
    echo 'Active tab: ' . $active_tab . '<br>';
    echo 'Rounds count: ' . count($rounds) . '<br>';
    echo 'Current round: ' . ($current_round ? 'ID=' . $current_round['id'] . ', cislo=' . $current_round['cislo_kola'] : 'NULL') . '<br>';
    echo 'Current round matches: ' . ($current_round && isset($current_round['matches']) ? count($current_round['matches']) : 'N/A') . '<br>';
    echo 'Has access: ' . ($has_access ? 'YES' : 'NO') . '<br>';
    echo '</div>';
}
?>

<!-- TAB 2: Guessing/Tipování -->
<div class="guessing <?php echo $active_tab === 'guessing' ? 'active' : ''; ?>">
    <?php if ($product_id && !$has_access) : ?>
    <div class="competition-locked">
        <p><?php esc_html_e('Pro přístup k tipování musíte zakoupit přístup.', 'tipnijinak'); ?></p>
        <a href="<?php echo esc_url(add_query_arg('tab', 'competitions', get_permalink())); ?>" class="btn btn-primary"><?php esc_html_e('Zobrazit detaily', 'tipnijinak'); ?></a>
    </div>
    <?php elseif (!empty($rounds)) : ?>
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
                    <?php 
                    // Najít předchozí a další kolo
                    $prev_round = null;
                    $next_round = null;
                    
                    if ($current_round) {
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
                    }
                    ?>
                    <?php if ($prev_round) : ?>
                    <div class="pagination-prev">
                        <a href="<?php echo esc_url(add_query_arg(['tab' => 'guessing', 'kolo' => $prev_round['id']], get_permalink())); ?>"></a>
                    </div>
                    <?php else : ?>
                    <div class="pagination-prev disabled"></div>
                    <?php endif; ?>
                    
                    <?php if ($next_round) : ?>
                    <div class="pagination-next">
                        <a href="<?php echo esc_url(add_query_arg(['tab' => 'guessing', 'kolo' => $next_round['id']], get_permalink())); ?>"></a>
                    </div>
                    <?php else : ?>
                    <div class="pagination-next disabled"></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($current_round && !empty($current_round['matches'])) : 
                // Získat povolené ligy pro tuto soutěž
                $allowed_leagues = get_field('povolene_ligy', $competition_id);
                $allowed_league_slugs = array();
                
                // Pokud jsou vybrány povolené ligy, vytvořit array slugů
                if (!empty($allowed_leagues)) {
                    foreach ($allowed_leagues as $league) {
                        // Kontrola, zda je to term objekt nebo WP_Post
                        if (is_object($league)) {
                            if (isset($league->slug)) {
                                // Je to term objekt
                                $allowed_league_slugs[] = $league->slug;
                            } elseif (isset($league->post_name)) {
                                // Je to WP_Post objekt
                                $allowed_league_slugs[] = $league->post_name;
                            }
                        } elseif (is_numeric($league)) {
                            // Je to ID termu
                            $term = get_term($league, 'liga');
                            if ($term && !is_wp_error($term)) {
                                $allowed_league_slugs[] = $term->slug;
                            }
                        }
                    }
                }
                
                // Seskupit zápasy podle ligy
                $matches_by_league = [];
                foreach ($current_round['matches'] as $match) {
                    // Použít ligu z dat zápasu nebo výchozí hodnotu
                    if (!empty($match['liga'])) {
                        $liga = $match['liga'];
                        $liga_slug = isset($match['liga_slug']) ? $match['liga_slug'] : sanitize_title($liga);
                    } else {
                        // Použít výchozí hodnotu, pokud zápas nemá přiřazenou ligu
                        $liga = __('Ostatní', 'tipnijinak');
                        $liga_slug = sanitize_title($liga);
                    }
                    
                    // Pokud jsou definovány povolené ligy, filtrovat podle nich
                    if (!empty($allowed_league_slugs) && !in_array($liga_slug, $allowed_league_slugs)) {
                        continue; // Přeskočit tuto ligu, pokud není povolena
                    }
                    
                    // Vytvořit kategorii pro ligu, pokud ještě neexistuje
                    if (!isset($matches_by_league[$liga_slug])) {
                        $matches_by_league[$liga_slug] = [
                            'name' => $liga,
                            'matches' => []
                        ];
                    }
                    
                    $matches_by_league[$liga_slug]['matches'][] = $match;
                }

                // Sort matches within each league by date/time
                foreach ($matches_by_league as &$league_data) {
                    usort($league_data['matches'], function($a, $b) {
                        // date format is DD.MM.YYYY, convert to parseable format
                        $parts_a = explode('.', $a['date']);
                        $parts_b = explode('.', $b['date']);
                        $date_a = strtotime($parts_a[2] . '-' . $parts_a[1] . '-' . $parts_a[0] . ' ' . $a['time']);
                        $date_b = strtotime($parts_b[2] . '-' . $parts_b[1] . '-' . $parts_b[0] . ' ' . $b['time']);
                        return $date_a - $date_b;
                    });
                }
                unset($league_data);

                // Debug pro adminy - DOČASNĚ VYPNUTO
                if (false && get_current_user_id() === 1 && empty($matches_by_league)) {
                    echo '<div style="background: #ff6b6b; color: #fff; padding: 15px; margin: 20px 0; border-radius: 5px;">';
                    echo '<strong>⚠️ DEBUG: Žádné zápasy v lize (pouze pro administrátory)</strong><br>';
                    echo 'Soutěž ID: ' . $competition_id . '<br>';
                    echo 'Aktuální kolo ID: ' . ($current_round ? $current_round['id'] : 'N/A') . '<br>';
                    echo 'Počet zápasů v kole: ' . (isset($current_round['matches']) ? count($current_round['matches']) : 0) . '<br>';
                    echo 'Povolené ligy: ' . (!empty($allowed_league_slugs) ? implode(', ', $allowed_league_slugs) : 'všechny') . '<br>';

                    if (!empty($current_round['matches'])) {
                        echo '<br><strong>První 3 zápasy v kole:</strong><br>';
                        foreach (array_slice($current_round['matches'], 0, 3) as $i => $m) {
                            echo sprintf('- Match ID: %d, Liga: %s, Liga slug: %s<br>',
                                $m['id'],
                                $m['liga'] ?? 'N/A',
                                $m['liga_slug'] ?? 'N/A'
                            );
                        }
                    }
                    echo '</div>';
                }

                // Získat aktivní ligu
                $active_league = isset($_GET['liga']) ? sanitize_title($_GET['liga']) : array_key_first($matches_by_league);
            ?>
            <div class="league">
                <div class="league-title">
                    <h3><?php esc_html_e('Liga', 'tipnijinak'); ?></h3>
                    <ul class="window-switcher">
                        <?php foreach ($matches_by_league as $league_slug => $league_data) : 
                            // Získat term objekt pro ligu
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
                        // Načtení tipu uživatele
                        $match_id = is_object($match['id']) && property_exists($match['id'], 'ID') ? $match['id']->ID : $match['id'];
                        $user_tip = is_user_logged_in() ? tipnijinak_get_user_tip($match_id, 0, $competition_id) : false;
                        $match_status = $match['status'] ?? 'planovany';
                        $is_match_locked = in_array($match_status, array('ukonceny', 'probihajici', 'zrusen'));
                    ?>
                    <div class="match <?php echo $is_match_locked ? 'match-locked' : ''; ?>" data-match-id="<?php echo esc_attr($match_id); ?>"
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
                                $kurzy_locked = get_field('kurzy', $match_id);
                                // Zjistit výsledek zápasu pro +/- body
                                $home_score_locked = get_field('skore_domaci', $match_id);
                                $away_score_locked = get_field('skore_hoste', $match_id);
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
                                        // Zápas ukončený - zobrazit +/- body
                                        $is_correct_locked = ($user_tip === $match_result_locked);
                                        $prefix = $is_correct_locked ? '+' : '-';
                                        echo esc_html($prefix . $points_locked . ' ' . ($points_locked === 1 ? 'bod' : ($points_locked >= 2 && $points_locked <= 4 ? 'body' : 'bodů')));
                                    } else {
                                        // Zápas ještě neskončil - zobrazit jen potenciální body
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
                                $kurzy = get_field('kurzy', $match_id);
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
                                <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="btn btn-small"><?php esc_html_e('Přihlásit se pro možnost tipovat', 'tipnijinak'); ?></a>
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
                
                // Kontrola, zda je soutěž hlavní
                $is_main_competition = get_field('je_hlavni', $competition_id);
                $min_tips = $is_main_competition ? 1 : $max_tips; // Pokud není hlavní, min = max
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
                    // Fix WP_Post object - převést na ID pokud je objekt
                    $tip_match_id = $tip['match_id'];
                    if (is_object($tip_match_id) && isset($tip_match_id->ID)) {
                        $tip_match_id = $tip_match_id->ID;
                    }
                    $match = tipnijinak_get_match_details($tip_match_id);
                    if (!$match) continue;
                ?>
                <div class="match">
                    <?php
                    // Fix WP_Post object error - ensure we get the ID as string/integer
                    $match_id = is_object($match['id']) && isset($match['id']->ID) ? $match['id']->ID : $match['id'];
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
        
            <div class="recap-bottom">
                <div class="matches-left">
                    <?php printf(__('Zbývá: %d zápasů', 'tipnijinak'), $untipped_count); ?>
                </div>

                <button type="button" class="btn btn-secondary btn-sm toggle-recap-tips">
                    <?php esc_html_e('Zobrazit tipy', 'tipnijinak'); ?>
                </button>

                <?php if (($current_round['stav_kola'] === 'otevreno' || $current_round['stav_kola'] === 'probihajici') && $untipped_count > 0) : ?>
                <div class="tips-feedback"></div>
                <div class="btn-holder">
                    <button class="btn btn-primary submit-tips">
                        <?php esc_html_e('Uložit/aktualizovat', 'tipnijinak'); ?>
                    </button>
                </div>
                <?php endif; ?>
            </div>
            
            <?php else : ?>
            <div class="login-to-tip">
                <p><?php esc_html_e('Pro možnost tipovat se musíte přihlásit.', 'tipnijinak'); ?></p>
                <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="btn btn-primary"><?php esc_html_e('Přihlásit se', 'tipnijinak'); ?></a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php else : ?>
    <div class="no-rounds-message">
        <p><?php esc_html_e('Pro tuto soutěž zatím nejsou vypsaná žádná kola.', 'tipnijinak'); ?></p>
    </div>
    <?php endif; ?>
</div>