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
$rounds = $args['rounds'] ?? array();
$current_round = $args['current_round'] ?? null;
$competition_id = $args['competition_id'] ?? get_the_ID();
$is_main_competition = $args['is_main_competition'] ?? false;
?>

<!-- TAB 4: Guessing-Ranking/Žebříček -->
<div class="guessing-ranking <?php echo $args['active_tab'] === 'guessing-ranking' ? 'active' : ''; ?>">

    <?php if (!empty($rounds) && $current_round && !$is_main_competition) : ?>
    <!-- Navigace mezi koly -->
    <div class="round">
        <h3><?php echo esc_html($current_round['cislo_kola'] . '. kolo'); ?></h3>
        <div class="round-info">
            <span class="duration"><?php printf(__('Doba trvání: %s - %s', 'tipnijinak'),
                esc_html($current_round['datum_od_format']),
                esc_html($current_round['datum_do_format'])); ?></span>
            <span class="status"><?php printf(__('Stav: %s', 'tipnijinak'),
                esc_html($current_round['stav_kola_text'])); ?></span>
        </div>
        <div class="pagination big">
            <?php
            $prev_round = null;
            $next_round = null;

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
            ?>
            <?php if ($prev_round) : ?>
            <div class="pagination-prev">
                <a href="<?php echo esc_url(add_query_arg(['tab' => 'guessing-ranking', 'kolo' => $prev_round['id']], get_permalink())); ?>"></a>
            </div>
            <?php else : ?>
            <div class="pagination-prev disabled"></div>
            <?php endif; ?>

            <?php if ($next_round) : ?>
            <div class="pagination-next">
                <a href="<?php echo esc_url(add_query_arg(['tab' => 'guessing-ranking', 'kolo' => $next_round['id']], get_permalink())); ?>"></a>
            </div>
            <?php else : ?>
            <div class="pagination-next disabled"></div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

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
        // Získat ceny pro žebříček - nejdříve z kola, pak fallback na soutěž
        $prizes = array();
        if ($current_round) {
            $round_prizes = get_field('ceny_kola', $current_round['id']);
            if (!empty($round_prizes)) {
                $prizes = $round_prizes;
            }
        }
        if (empty($prizes)) {
            $prizes_group = get_field('ceny_souteze', $competition_id);
            $prizes = (!empty($prizes_group) && !empty($prizes_group['ceny'])) ? $prizes_group['ceny'] : array();
        }

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
    <?php else : ?>
    <div class="no-leaderboard-data">
        <p><?php echo $is_main_competition
            ? esc_html__('Zatím nejsou k dispozici žádné údaje pro žebříček.', 'tipnijinak')
            : esc_html__('Zatím nejsou k dispozici žádné údaje pro žebříček v tomto kole.', 'tipnijinak'); ?></p>
    </div>
    <?php endif; ?>
</div>