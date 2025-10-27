<?php
/**
 * Template part for displaying the main competition
 *
 * @package TipniJinak
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

$main_competition = tipnijinak_get_main_competition();
if (!$main_competition) {
    return;
}

$competition_id = $main_competition->ID;
$terms = wp_get_post_terms($competition_id, 'typ-souteze');
$category = !empty($terms) ? $terms[0]->name : '';
?>

<h1><?php esc_html_e('Hlavní soutěž', 'tipnijinak'); ?></h1>
<ul class="window-switcher">
    <li data="competitions" class="active"><?php esc_html_e('Ceny', 'tipnijinak'); ?></li>
    <li data="guessing"><?php esc_html_e('Tipování', 'tipnijinak'); ?></li>
    <li data="guessing-results"><?php esc_html_e('Výsledky tipování', 'tipnijinak'); ?></li>
    <li data="guessing-ranking"><?php esc_html_e('Žebříček', 'tipnijinak'); ?></li>
</ul>

<div class="window-switcher__windows">
    <div class="competitions active">
        <div class="main-competition">
            <div class="img-holder">
                <?php 
                if (has_post_thumbnail($competition_id)) {
                    echo get_the_post_thumbnail($competition_id, 'large');
                } else {
                    echo '<img src="' . esc_url(get_template_directory_uri() . '/assets/images/competition-placeholder.jpg') . '" alt="' . esc_attr(get_the_title($competition_id)) . '">';
                }
                ?>
            </div>
            <div class="competition-text">
                <div class="subtitle"><?php echo esc_html($category); ?></div>
                <h2><?php echo esc_html(get_the_title($competition_id)); ?></h2>
                <div class="competition-description">
                    <?php echo wp_kses_post(get_the_excerpt($competition_id)); ?>
                </div>
                <div class="btn-holder">
                    <a href="<?php echo esc_url(get_permalink($competition_id)); ?>" class="btn btn-primary"><?php esc_html_e('Vstoupit', 'tipnijinak'); ?></a>
                </div>
            </div>
        </div>
        
        <?php
        // Get other competitions
        $competition_type = !empty($terms) ? $terms[0]->slug : '';
        $other_competitions = tipnijinak_get_competitions_by_type($competition_type, 3);
        
        if (!empty($other_competitions)) :
        ?>
        <div class="other-competitions">
            <?php foreach ($other_competitions as $competition) : ?>
                <div class="competition">
                    <div class="img-holder">
                        <?php 
                        if (has_post_thumbnail($competition->ID)) {
                            echo get_the_post_thumbnail($competition->ID, 'medium_large');
                        } else {
                            echo '<img src="' . esc_url(get_template_directory_uri() . '/assets/images/competition-placeholder.jpg') . '" alt="' . esc_attr(get_the_title($competition->ID)) . '">';
                        }
                        ?>
                    </div>
                    <div class="competition-text">
                        <div class="subtitle"><?php echo esc_html($category); ?></div>
                        <h3><?php echo esc_html(get_the_title($competition->ID)); ?></h3>
                        <div class="competition-description">
                            <?php echo wp_kses_post(get_the_excerpt($competition->ID)); ?>
                        </div>
                        <div class="btn-holder">
                            <a href="<?php echo esc_url(get_permalink($competition->ID)); ?>" class="btn btn-primary"><?php esc_html_e('Vstoupit', 'tipnijinak'); ?></a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Tipování tab -->
    <div class="guessing">
        <div class="two-columns">
            <div class="two-columns__column left">
                <div class="round">
                    <h3><?php esc_html_e('Kolo 1', 'tipnijinak'); ?></h3>
                    <div class="round-info">
                        <span class="duration"><?php esc_html_e('Doba trvání: od - do', 'tipnijinak'); ?></span>
                        <span class="status"><?php esc_html_e('Stav: otevřeno', 'tipnijinak'); ?></span>
                    </div>
                    <div class="pagination big">
                        <div class="pagination-prev"></div>
                        <div class="pagination-next"></div>
                    </div>
                </div>
                
                <!-- Leagues -->
                <div class="league">
                    <div class="league-title">
                        <h3><?php esc_html_e('Liga', 'tipnijinak'); ?></h3>
                        <ul class="window-switcher">
                            <?php
                            $leagues = tipnijinak_get_leagues();
                            $i = 0;
                            foreach ($leagues as $league) :
                                $active = $i === 1 ? ' class="active"' : '';
                            ?>
                                <li data="<?php echo esc_attr($league->slug); ?>"<?php echo $active; ?>><?php echo esc_html($league->name); ?></li>
                            <?php 
                                $i++;
                            endforeach; 
                            ?>
                        </ul>
                    </div>
                    
                    <?php 
                    $i = 0;
                    foreach ($leagues as $league) :
                        $active = $i === 1 ? ' active' : '';
                        $matches = tipnijinak_get_matches_by_league($league->slug, 5);
                    ?>
                    <div class="<?php echo esc_attr($league->slug . $active); ?> league-matches">
                        <?php foreach ($matches as $match) : 
                            $match_details = tipnijinak_get_match_details($match->ID);
                        ?>
                        <div class="match">
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
                            <div class="odds">
                                <div class="odds-button">1</div>
                                <div class="odds-button">0</div>
                                <div class="odds-button">2</div>
                            </div>
                            <div class="odds-points">
                                <?php 
                                $body = get_field('pocet_bodu', $competition_id);
                                echo esc_html($body ? $body . ' bodů' : '30 bodů'); 
                                ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php 
                        $i++;
                    endforeach; 
                    ?>
                </div>
            </div>
            
            <!-- Right column - Recap -->
            <div class="two-columns__column right recap">
                <div class="matches-recap">
                    <?php 
                    // Show selected matches for betting
                    // This would normally come from user session or database
                    $selected_matches = array_slice($matches, 0, 2);
                    foreach ($selected_matches as $match) : 
                        $match_details = tipnijinak_get_match_details($match->ID);
                    ?>
                    <div class="match">
                        <div class="match-info">
                            <div class="home team">
                                <div class="team-name"><?php echo esc_html($match_details['teams']['home']['name']); ?></div>
                                <div class="logo-holder">
                                    <?php if (!empty($match_details['teams']['home']['logo'])) : ?>
                                        <img src="<?php echo esc_url($match_details['teams']['home']['logo']); ?>" alt="<?php echo esc_attr($match_details['teams']['home']['name']); ?>">
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span>-</span>
                            <div class="away team">
                                <div class="team-name"><?php echo esc_html($match_details['teams']['away']['name']); ?></div>
                                <div class="logo-holder">
                                    <?php if (!empty($match_details['teams']['away']['logo'])) : ?>
                                        <img src="<?php echo esc_url($match_details['teams']['away']['logo']); ?>" alt="<?php echo esc_attr($match_details['teams']['away']['name']); ?>">
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="odd">1</div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="matches-left">
                    <?php esc_html_e('Zbývá: 13 zápasů', 'tipnijinak'); ?>
                </div>
                <div class="btn-holder">
                    <a href="#" class="btn btn-primary"><?php esc_html_e('Uložit/aktualizovat', 'tipnijinak'); ?></a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Výsledky tipování tab -->
    <div class="guessing-results">
        <div class="guessing-summary">
            <div class="guessing-summary__points"><?php esc_html_e('Počet bodů:', 'tipnijinak'); ?> 450</div>
            <div class="guessing-summary__tips"><?php esc_html_e('Počet tipnutých zápasů:', 'tipnijinak'); ?> 15</div>
            <div class="guessing-summary__ranking"><?php esc_html_e('Umístění:', 'tipnijinak'); ?> 3.</div>
        </div>
        <div class="matches">
            <?php 
            // Get competition matches to show results
            $competition_matches = tipnijinak_get_competition_matches($competition_id);
            $matches_to_show = array_slice($competition_matches, 0, 4);
            
            foreach ($matches_to_show as $match) : 
            ?>
            <div class="match">
                <div class="match-time">
                    <span class="match-time__hours"><?php echo esc_html($match['time']); ?></span>
                    <span class="match-time__date"><?php echo esc_html($match['date']); ?></span>
                </div>
                <div class="match-info">
                    <div class="home team">
                        <div class="team-name"><?php echo esc_html($match['teams']['home']['name']); ?></div>
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
                        <div class="team-name"><?php echo esc_html($match['teams']['away']['name']); ?></div>
                        <div class="logo-holder">
                            <?php if (!empty($match['teams']['away']['logo'])) : ?>
                                <img src="<?php echo esc_url($match['teams']['away']['logo']); ?>" alt="<?php echo esc_attr($match['teams']['away']['name']); ?>">
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <div class="pagination big">
                <div class="pagination-prev"></div>
                <div class="pagination-next"></div>
            </div>
        </div>
    </div>
    
    <!-- Žebříček tab -->
    <div class="guessing-ranking">
        <table>
            <thead>
                <tr>
                    <th><?php esc_html_e('Pořadí', 'tipnijinak'); ?></th>
                    <th><?php esc_html_e('Základní informace', 'tipnijinak'); ?></th>
                    <th><?php esc_html_e('Skóre', 'tipnijinak'); ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1.<img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/trophy-gold.svg'); ?>" alt="<?php esc_attr_e('Zlatá trofej', 'tipnijinak'); ?>" width="23"></td>
                    <td>@kapo12</td>
                    <td>1500</td>
                    <td>...</td>
                </tr>
                <tr>
                    <td>2.<img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/trophy-silver.svg'); ?>" alt="<?php esc_attr_e('Stříbrná trofej', 'tipnijinak'); ?>" width="23"></td>
                    <td>@kamilkral</td>
                    <td>850</td>
                    <td>...</td>
                </tr>
                <tr>
                    <td>3.<img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/trophy-bronze.svg'); ?>" alt="<?php esc_attr_e('Bronzová trofej', 'tipnijinak'); ?>" width="23"></td>
                    <td>@pegous</td>
                    <td>500</td>
                    <td>...</td>
                </tr>
                <tr>
                    <td>4.</td>
                    <td>@Zaky65</td>
                    <td>500</td>
                    <td>...</td>
                </tr>
                <tr>
                    <td>5.</td>
                    <td>@uživatel5</td>
                    <td>500</td>
                    <td>...</td>
                </tr>
            </tbody>
        </table>
        <div class="pagination big">
            <div class="pagination-prev"></div>
            <div class="pagination-next"></div>
        </div>
    </div>
</div>