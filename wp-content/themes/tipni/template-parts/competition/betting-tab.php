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
                        $user_tip = is_user_logged_in() ? tipnijinak_get_user_tip($match_id) : false;
                    ?>
                    <div class="match" data-match-id="<?php echo esc_attr($match_id); ?>" 
                         data-match-date="<?php echo esc_attr($match['date']); ?>" 
                         data-match-time="<?php echo esc_attr($match['time']); ?>">
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
                        
                        <?php if ($current_round['stav_kola'] === 'otevreno' || $current_round['stav_kola'] === 'probihajici') : ?>
                            <?php if (is_user_logged_in()) : ?>
                            <div class="odds">
                                <?php 
                                // Získat kurzy pro tento zápas
                                $kurzy = get_field('kurzy', $match_id);
                                // Kontrola, zda už má uživatel uložený tip
                                $has_saved_tip = !empty($user_tip);
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
                                // Zobrazení potenciálních bodů podle aktivního tipu
                                if ($user_tip) {
                                    // Získat kurz pro vybraný tip
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
                                        
                                        // Správné skloňování slova "bod/body/bodů"
                                        if ($points === 1) {
                                            echo sprintf(__('%d bod', 'tipnijinak'), $points);
                                        } elseif ($points >= 2 && $points <= 4) {
                                            echo sprintf(__('%d body', 'tipnijinak'), $points);
                                        } else {
                                            echo sprintf(__('%d bodů', 'tipnijinak'), $points);
                                        }
                                    } else {
                                        echo esc_html($body_text);
                                    }
                                } else {
                                    echo esc_html($body_text);
                                }
                                ?>
                            </div>
                            <?php else : ?>
                            <div class="login-required">
                                <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="btn btn-small"><?php esc_html_e('Přihlásit se pro tipování', 'tipnijinak'); ?></a>
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
                <p><?php esc_html_e('Pro tipování se musíte přihlásit.', 'tipnijinak'); ?></p>
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