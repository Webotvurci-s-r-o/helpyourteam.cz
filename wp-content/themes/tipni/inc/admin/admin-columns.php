<?php
/**
 * Custom admin columns pro CPT
 */

/**
 * Přidat sloupec "Soutěž" do admin tabulky pro post type Kolo
 */
function tipnijinak_kolo_admin_columns($columns) {
    $new_columns = array();
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if ($key === 'title') {
            $new_columns['soutez'] = __('Soutěž', 'tipnijinak');
        }
    }
    return $new_columns;
}
add_filter('manage_kolo_posts_columns', 'tipnijinak_kolo_admin_columns');

function tipnijinak_kolo_admin_column_content($column, $post_id) {
    if ($column === 'soutez') {
        $souteze = get_field('souteze_kola', $post_id);
        if (!empty($souteze) && is_array($souteze)) {
            $links = array();
            foreach ($souteze as $soutez_id) {
                $title = get_the_title($soutez_id);
                $edit_link = get_edit_post_link($soutez_id);
                $links[] = '<a href="' . esc_url($edit_link) . '">' . esc_html($title) . '</a>';
            }
            echo implode(', ', $links);
        } else {
            echo '—';
        }
    }
}
add_action('manage_kolo_posts_custom_column', 'tipnijinak_kolo_admin_column_content', 10, 2);
