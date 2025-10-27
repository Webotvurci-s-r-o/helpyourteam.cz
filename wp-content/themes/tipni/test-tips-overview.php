<?php
/**
 * Template Name: Test Tips Overview
 * Description: Testovací stránka pro zobrazení přehledu tipů
 */

// Kontrola, zda je uživatel admin
if (!current_user_can('manage_options')) {
    wp_die('Nemáte oprávnění k zobrazení této stránky.');
}

get_header();

// Získat všechny soutěže
$competitions = get_posts(array(
    'post_type' => 'soutez',
    'posts_per_page' => -1,
    'post_status' => 'publish'
));

// Získat ID vybrané soutěže z URL
$selected_competition = isset($_GET['competition']) ? intval($_GET['competition']) : 0;

?>

<div class="wrap" style="max-width: 1200px; margin: 40px auto; padding: 20px; ">
    <h1>Přehled tipů - Testovací stránka</h1>
    
    <!-- Výběr soutěže -->
    <form method="get" style="margin-bottom: 30px;">
        <input type="hidden" name="page_id" value="<?php echo get_the_ID(); ?>">
        <label for="competition">Vyberte soutěž:</label>
        <select name="competition" id="competition" onchange="this.form.submit()">
            <option value="">-- Vyberte soutěž --</option>
            <?php foreach ($competitions as $competition) : ?>
                <option value="<?php echo $competition->ID; ?>" <?php selected($selected_competition, $competition->ID); ?>>
                    <?php echo esc_html($competition->post_title); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if ($selected_competition) : 
        // Kontrola, zda se používá databázový systém nebo CPT
        $use_db = defined('TIPNIJINAK_USE_DB_TIPS') && TIPNIJINAK_USE_DB_TIPS;

        var_dump($use_db);
        
        if ($use_db) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'tipnijinak_tips';
            
            // Získat všechny tipy pro vybranou soutěž
            $tips = $wpdb->get_results($wpdb->prepare(
                "SELECT t.*, u.display_name as user_name 
                FROM $table_name t 
                LEFT JOIN {$wpdb->users} u ON t.user_id = u.ID 
                WHERE t.competition_id = %d 
                ORDER BY t.user_id, t.match_id",
                $selected_competition
            ));
            
            // Statistiky uživatelů
            $user_stats = $wpdb->get_results($wpdb->prepare(
                "SELECT 
                    t.user_id,
                    u.display_name as user_name,
                    COUNT(*) as total_tips,
                    COUNT(DISTINCT t.round_id) as rounds_tipped,
                    COUNT(DISTINCT t.match_id) as matches_tipped
                FROM $table_name t 
                LEFT JOIN {$wpdb->users} u ON t.user_id = u.ID 
                WHERE t.competition_id = %d 
                GROUP BY t.user_id 
                ORDER BY total_tips DESC",
                $selected_competition
            ));
            
        } else {
            // CPT systém
            $tips_query = new WP_Query(array(
                'post_type' => 'user_tip',
                'posts_per_page' => -1,
                'meta_query' => array(
                    array(
                        'key' => 'tip_competition',
                        'value' => $selected_competition,
                        'compare' => '='
                    )
                )
            ));

         
            
            $tips = array();
            $user_stats_raw = array();
            var_dump($tips_query->have_posts());
            if ($tips_query->have_posts()) {
                while ($tips_query->the_post()) {
                    $tip = new stdClass();
                    $tip->user_id = get_field('tip_user');
                    $tip->match_id = get_field('tip_match');
                    $tip->round_id = get_field('tip_round');
                    $tip->tip = get_field('tip_value');
                    $tip->points = get_field('tip_points');
                    $tip->evaluated = get_field('tip_evaluated');
                    
                    $user = get_userdata($tip->user_id);
                    $tip->user_name = $user ? $user->display_name : 'Neznámý uživatel';
                    
                    $tips[] = $tip;
     
                    // Sbírat statistiky
                    if (!isset($user_stats_raw[$tip->user_id])) {
                        $user_stats_raw[$tip->user_id] = array(
                            'user_id' => $tip->user_id,
                            'user_name' => $tip->user_name,
                            'total_tips' => 0,
                            'rounds' => array(),
                            'matches' => array()
                        );
                    }
                    
                    $user_stats_raw[$tip->user_id]['total_tips']++;
                    $user_stats_raw[$tip->user_id]['rounds'][$tip->round_id] = true;
                    $user_stats_raw[$tip->user_id]['matches'][$tip->match_id] = true;
                }
                wp_reset_postdata();
            }
            
            // Převést statistiky do formátu pro zobrazení
            $user_stats = array();
            var_dump($user_stats_raw);
            foreach ($user_stats_raw as $user_id => $stats) {
                $stat = new stdClass();
                $stat->user_id = $user_id;
                $stat->user_name = $stats['user_name'];
                $stat->total_tips = $stats['total_tips'];
                $stat->rounds_tipped = count($stats['rounds']);
                $stat->matches_tipped = count($stats['matches']);
                $user_stats[] = $stat;
            }
            
            // Seřadit podle počtu tipů
            usort($user_stats, function($a, $b) {
                return $b->total_tips - $a->total_tips;
            });
        }
        
        // Získat informace o zápasech
        $match_info = array();
        $unique_matches = array_unique(array_column($tips, 'match_id'));
        
        foreach ($unique_matches as $match_id) {
            $match = get_post($match_id);
            if ($match) {
                $home_team_id = get_field('tym_domaci', $match_id);
                $away_team_id = get_field('tym_hoste', $match_id);
                
                $match_info[$match_id] = array(
                    'home' => $home_team_id ? get_the_title($home_team_id) : 'Neznámý tým',
                    'away' => $away_team_id ? get_the_title($away_team_id) : 'Neznámý tým',
                    'date' => get_field('datum_zapasu', $match_id),
                    'time' => get_field('cas_zapasu', $match_id),
                    'score' => get_field('skore', $match_id)
                );
            }
        }
    ?>
    
    <!-- Statistiky uživatelů -->
    <h2>Statistiky uživatelů</h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Uživatel</th>
                <th>Počet tipů</th>
                <th>Počet kol</th>
                <th>Počet zápasů</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($user_stats as $stat) : ?>
            <tr>
                <td><?php echo esc_html($stat->user_name); ?></td>
                <td><?php echo $stat->total_tips; ?></td>
                <td><?php echo $stat->rounds_tipped; ?></td>
                <td><?php echo $stat->matches_tipped; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <!-- Přehled všech tipů -->
    <h2 style="margin-top: 40px;">Přehled všech tipů</h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Uživatel</th>
                <th>Zápas</th>
                <th>Datum a čas</th>
                <th>Tip</th>
                <th>Výsledek</th>
                <th>Body</th>
                <th>Vyhodnoceno</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tips as $tip) : 
                $match_data = isset($match_info[$tip->match_id]) ? $match_info[$tip->match_id] : null;
                $tip_text = $tip->tip === '1' ? 'Domácí' : ($tip->tip === '0' ? 'Remíza' : 'Hosté');
            ?>
            <tr>
                <td><?php echo esc_html($tip->user_name); ?></td>
                <td>
                    <?php if ($match_data) : ?>
                        <?php echo esc_html($match_data['home'] . ' vs ' . $match_data['away']); ?>
                    <?php else : ?>
                        Zápas ID: <?php echo $tip->match_id; ?>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($match_data && $match_data['date']) : ?>
                        <?php echo esc_html($match_data['date'] . ' ' . $match_data['time']); ?>
                    <?php else : ?>
                        -
                    <?php endif; ?>
                </td>
                <td><?php echo esc_html($tip_text); ?></td>
                <td>
                    <?php if ($match_data && $match_data['score']) : ?>
                        <?php echo esc_html($match_data['score']); ?>
                    <?php else : ?>
                        -
                    <?php endif; ?>
                </td>
                <td>
                    <?php 
                    if (isset($tip->points)) {
                        echo $tip->points;
                    } else {
                        echo '-';
                    }
                    ?>
                </td>
                <td>
                    <?php 
                    if (isset($tip->evaluated)) {
                        echo $tip->evaluated ? '✅' : '❌';
                    } else {
                        echo '-';
                    }
                    ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <?php else : ?>
    <p>Vyberte soutěž pro zobrazení přehledu tipů.</p>
    <?php endif; ?>
    
    <style>
        .wrap {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        table {
            margin-top: 20px;
        }
        th {
            font-weight: 600;
        }
        select {
            padding: 5px 10px;
            font-size: 14px;
        }
    </style>
</div>

<?php
get_footer();
?>