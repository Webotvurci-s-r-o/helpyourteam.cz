<?php
/**
 * Template part for displaying competition ranking tab
 *
 * @package TipniJinak
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

$leaderboard = $args['leaderboard'] ?? array();
?>

<!-- TAB 4: Guessing-Ranking/Žebříček -->
<div class="guessing-ranking <?php echo $args['active_tab'] === 'guessing-ranking' ? 'active' : ''; ?>">
    <?php if (!empty($leaderboard)) : ?>
    <table>
        <thead>
        <tr>
            <th><?php esc_html_e('Pořadí', 'tipnijinak'); ?></th>
            <th><?php esc_html_e('Základní informace', 'tipnijinak'); ?></th>
            <th><?php esc_html_e('Skóre', 'tipnijinak'); ?></th>
            <th><?php esc_html_e('Ceny', 'tipnijinak'); ?></th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php 
        // Získat ceny pro žebříček
        $prizes_group = get_field('ceny_souteze');
        $prizes = (!empty($prizes_group) && !empty($prizes_group['ceny'])) ? $prizes_group['ceny'] : array();
        
        foreach ($leaderboard as $position => $user_data) : 
            $trophy = '';
            if ($position === 0) {
                $trophy = '<img src="' . esc_url(get_template_directory_uri() . '/assets/images/trophy-gold.svg') . '" alt="" width="23">';
            } elseif ($position === 1) {
                $trophy = '<img src="' . esc_url(get_template_directory_uri() . '/assets/images/trophy-silver.svg') . '" alt="" width="23">';
            } elseif ($position === 2) {
                $trophy = '<img src="' . esc_url(get_template_directory_uri() . '/assets/images/trophy-bronze.svg') . '" alt="" width="23">';
            }
            
            // Získat logo oblíbeného klubu
            $club_logo_url = '';
            $club_id = get_user_meta($user_data['user_id'], 'club_id', true);
     
            if (!empty($club_id)) {
                $logo_id = get_field('logo_tymu', $club_id);
         
                if (!empty($logo_id) && is_numeric($logo_id)) {
                    $club_logo_url = wp_get_attachment_url($logo_id);
                }
            }
            
            // Získat cenu pro tuto pozici
            $prize_for_position = isset($prizes[$position]) ? $prizes[$position] : null;
        ?>
        <tr <?php echo (is_user_logged_in() && $user_data['user_id'] == get_current_user_id()) ? 'class="current-user"' : ''; ?>>
            <td><?php echo esc_html($position + 1) . '.' . $trophy; ?></td>
            <td>
                <?php echo esc_html($user_data['name']); ?>
                <?php if (!empty($club_logo_url)) : ?>
                    <img src="<?php echo esc_url($club_logo_url); ?>" alt="" class="user-club-logo" style="width: 20px; height: 20px; margin-left: 10px; vertical-align: middle; object-fit: contain;">
                <?php endif; ?>
            </td>
            <td><?php echo esc_html($user_data['points']); ?></td>
            <td>
                <?php if ($prize_for_position) : ?>
                    <div class="prize-info">
                        <?php if (!empty($prize_for_position['obrazek'])) : ?>
                            <img src="<?php echo esc_url($prize_for_position['obrazek']['sizes']['thumbnail'] ?? $prize_for_position['obrazek']['url']); ?>" alt="<?php echo esc_attr($prize_for_position['nazev']); ?>" style="width: 30px; height: 30px; object-fit: contain; margin-right: 8px; vertical-align: middle;">
                        <?php endif; ?>
                        <span><?php echo esc_html($prize_for_position['nazev']); ?></span>
                    </div>
                <?php else : ?>
                    <span class="no-prize">-</span>
                <?php endif; ?>
            </td>
            <td>...</td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    
    <?php if (count($leaderboard) > 10) : ?>
    <div class="pagination big">
        <div class="pagination-prev"></div>
        <div class="pagination-next"></div>
    </div>
    <?php endif; ?>
    <?php else : ?>
    <div class="no-leaderboard-data">
        <p><?php esc_html_e('Zatím nejsou k dispozici žádné údaje pro žebříček.', 'tipnijinak'); ?></p>
    </div>
    <?php endif; ?>
</div>