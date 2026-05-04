<?php
/**
 * Automatické vyhodnocení tipů při uložení zápasu se stavem "ukončený"
 */

function tipnijinak_auto_evaluate_on_match_save($post_id) {
    if (get_post_type($post_id) !== 'zapas') {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    $match_status = get_field('stav_zapasu', $post_id);

    if ($match_status !== 'ukonceny') {
        return;
    }

    // Zkontrolovat, že máme skóre
    $home_score = get_field('skore_domaci', $post_id);
    $away_score = get_field('skore_hoste', $post_id);

    if ($home_score === '' || $home_score === null || $away_score === '' || $away_score === null) {
        return;
    }

    // Spustit evaluaci
    $evaluated = tipnijinak_evaluate_match($post_id);

    if ($evaluated > 0) {
        set_transient('tipnijinak_match_evaluated_' . $post_id, $evaluated, 30);
    }
}
add_action('acf/save_post', 'tipnijinak_auto_evaluate_on_match_save', 20);

/**
 * Zobrazit admin notice po automatické evaluaci
 */
function tipnijinak_match_evaluated_notice() {
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'zapas') {
        return;
    }

    $post_id = isset($_GET['post']) ? intval($_GET['post']) : 0;
    if (!$post_id) {
        return;
    }

    $evaluated = get_transient('tipnijinak_match_evaluated_' . $post_id);
    if (!$evaluated) {
        return;
    }

    delete_transient('tipnijinak_match_evaluated_' . $post_id);

    echo '<div class="notice notice-success is-dismissible">';
    echo '<p><strong>Automaticky vyhodnoceno ' . intval($evaluated) . ' tipů pro tento zápas.</strong></p>';
    echo '</div>';
}
add_action('admin_notices', 'tipnijinak_match_evaluated_notice');
