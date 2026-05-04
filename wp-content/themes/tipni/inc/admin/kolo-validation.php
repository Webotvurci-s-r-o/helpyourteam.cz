<?php
/**
 * Validace kol - kontrola datumů zápasů vs. rozmezí kola
 */

/**
 * Validace zápasů v kole - datum zápasu musí spadat do rozmezí kola
 * Zápasy mimo rozmezí se automaticky odeberou z pole zapasy_kola
 */
function tipnijinak_validate_kolo_matches($post_id) {
    if (get_post_type($post_id) !== 'kolo') {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    $datum_od = get_field('datum_od', $post_id);
    $datum_do = get_field('datum_do', $post_id);
    $zapasy = get_field('zapasy_kola', $post_id);

    if (!$datum_od || !$datum_do || empty($zapasy)) {
        return;
    }

    $round_start = strtotime($datum_od);
    $round_end = strtotime($datum_do);

    if (!$round_start || !$round_end) {
        return;
    }

    $valid_matches = array();
    $removed_matches = array();

    foreach ($zapasy as $match_id) {
        $datum_zapasu = get_field('datum_zapasu', $match_id);

        if (!$datum_zapasu) {
            $valid_matches[] = $match_id;
            continue;
        }

        $match_time = strtotime($datum_zapasu);
        if ($match_time && ($match_time < $round_start || $match_time > $round_end)) {
            $removed_matches[] = array(
                'title' => get_the_title($match_id),
                'date' => $datum_zapasu,
            );
        } else {
            $valid_matches[] = $match_id;
        }
    }

    if (!empty($removed_matches)) {
        // Aktualizovat pole - ponechat jen validní zápasy
        update_field('zapasy_kola', $valid_matches, $post_id);
        set_transient('tipnijinak_kolo_date_warning_' . $post_id, $removed_matches, 30);
    }
}
add_action('acf/save_post', 'tipnijinak_validate_kolo_matches', 20);

/**
 * Zobrazit admin notice o odebraných zápasech mimo rozmezí kola
 */
function tipnijinak_kolo_date_warning_notice() {
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'kolo') {
        return;
    }

    $post_id = isset($_GET['post']) ? intval($_GET['post']) : 0;
    if (!$post_id) {
        return;
    }

    $removed_matches = get_transient('tipnijinak_kolo_date_warning_' . $post_id);
    if (empty($removed_matches)) {
        return;
    }

    delete_transient('tipnijinak_kolo_date_warning_' . $post_id);

    $datum_od = get_field('datum_od', $post_id);
    $datum_do = get_field('datum_do', $post_id);

    echo '<div class="notice notice-error is-dismissible">';
    echo '<p><strong>Následující zápasy byly odebrány z kola, protože jejich datum nespadá do rozmezí ' . esc_html($datum_od) . ' – ' . esc_html($datum_do) . ':</strong></p>';
    echo '<ul style="margin-left: 20px; list-style: disc;">';
    foreach ($removed_matches as $match) {
        echo '<li>' . esc_html($match['title']) . ' — <strong>' . esc_html($match['date']) . '</strong></li>';
    }
    echo '</ul>';
    echo '</div>';
}
add_action('admin_notices', 'tipnijinak_kolo_date_warning_notice');

/**
 * Filtrovat nabídku zápasů v relationship fieldu podle datumového rozmezí kola
 * Nabídne pouze zápasy, jejichž datum spadá do datum_od – datum_do daného kola
 */
function tipnijinak_filter_zapasy_kola_query($args, $field, $post_id) {
    // Pouze pro field zapasy_kola
    if ($field['name'] !== 'zapasy_kola') {
        return $args;
    }

    $datum_od = get_field('datum_od', $post_id);
    $datum_do = get_field('datum_do', $post_id);

    // Pokud nemáme rozmezí, nezůžovat výběr
    if (!$datum_od || !$datum_do) {
        return $args;
    }

    $round_start = date('Y-m-d H:i:s', strtotime($datum_od));
    $round_end = date('Y-m-d H:i:s', strtotime($datum_do));

    // Přidat meta_query pro filtrování podle datum_zapasu
    if (!isset($args['meta_query'])) {
        $args['meta_query'] = array();
    }

    $args['meta_query'][] = array(
        'key' => 'datum_zapasu',
        'value' => array($round_start, $round_end),
        'compare' => 'BETWEEN',
        'type' => 'DATETIME',
    );

    return $args;
}
add_filter('acf/fields/relationship/query/name=zapasy_kola', 'tipnijinak_filter_zapasy_kola_query', 10, 3);
