<?php
/**
 * Functions which enhance the theme by hooking into WordPress
 *
 * @package TipniJinak
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Prepare competition data for single-soutez.php template
 *
 * @param int $competition_id Competition ID (optional, defaults to current post)
 * @return array Competition data for template parts
 */
function tipnijinak_prepare_competition_data($competition_id = null) {
    if (!$competition_id) {
        // Použít get_queried_object_id() pro single post templates - spolehlivější než get_the_ID()
        // protože není ovlivněno query vars jako 'kolo' (který je registrovaný jako CPT)
        $queried_object = get_queried_object();

        if ($queried_object && isset($queried_object->ID) && $queried_object->post_type === 'soutez') {
            $competition_id = $queried_object->ID;
        } else {
            // Fallback na get_the_ID()
            $competition_id = get_the_ID();
        }
    }

    // DEBUG: Zobrazit debug info pro administrátory při problémech - DOČASNĚ VYPNUTO
    if (false && get_current_user_id() === 1 && isset($_GET['debug'])) {
        $queried_obj = get_queried_object();
        echo '<div style="background: #333; color: #fff; padding: 15px; margin: 20px; border-radius: 5px; position: fixed; top: 10px; right: 10px; z-index: 99999; max-width: 400px; font-size: 12px;">';
        echo '<strong>DEBUG - Competition Data Loading:</strong><br>';
        echo 'Competition ID: ' . $competition_id . '<br>';
        echo 'Queried object type: ' . (isset($queried_obj->post_type) ? $queried_obj->post_type : 'N/A') . '<br>';
        echo 'Queried object ID: ' . (isset($queried_obj->ID) ? $queried_obj->ID : 'N/A') . '<br>';
        echo 'get_the_ID(): ' . get_the_ID() . '<br>';
        echo '$_GET[kolo]: ' . (isset($_GET['kolo']) ? $_GET['kolo'] : 'not set') . '<br>';
        echo '$_GET[tab]: ' . (isset($_GET['tab']) ? $_GET['tab'] : 'not set') . '<br>';
        echo '</div>';
    }
    
    // Basic competition info
    $terms = wp_get_post_terms($competition_id, 'typ-souteze');
    $category = !empty($terms) ? $terms[0]->name : '';
    
    // Rounds data
    $rounds = tipnijinak_get_competition_rounds($competition_id);
    $active_round = tipnijinak_get_active_competition_round($competition_id);
    
    // Current round logic
    $current_round_id = isset($_GET['kolo']) ? intval($_GET['kolo']) : ($active_round ? $active_round['id'] : 0);
    $current_round = null;
    
    if ($current_round_id) {
        foreach ($rounds as $round) {
            if ($round['id'] == $current_round_id) {
                $current_round = $round;
                break;
            }
        }
    }
    
    if (!$current_round && $active_round) {
        $current_round = $active_round;
    } elseif (!$current_round && !empty($rounds)) {
        $current_round = $rounds[0];
    }
    
    // Points and access
    $body = get_field('pocet_bodu');
    $body_text = $body ? $body . ' bodů' : '30 bodů';
    $has_access = true; // TODO: Implement access check function later
    $product_id = get_field('woocommerce_produkt', $competition_id);
    
    // Main competition sums points across all rounds, team competition is per round
    $is_main_competition = get_field('je_hlavni', $competition_id);
    $current_round_id = $current_round ? $current_round['id'] : 0;
    $ranking_round_id = $is_main_competition ? 0 : $current_round_id;

    // User data
    $user_points = is_user_logged_in() ? tipnijinak_get_user_total_points($competition_id, 0, $ranking_round_id) : 0;
    $user_tips_count = is_user_logged_in() ? tipnijinak_get_user_tips_count($competition_id, 0, $ranking_round_id) : 0;
    $user_ranking = is_user_logged_in() ? tipnijinak_get_user_ranking($competition_id, 0, $ranking_round_id) : 0;

    // Leaderboard
    $leaderboard = tipnijinak_get_competition_leaderboard($competition_id, 10, 0, $ranking_round_id);

    // Active tab
    $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'competitions';

    return array(
        'competition_id' => $competition_id,
        'category' => $category,
        'rounds' => $rounds,
        'active_round' => $active_round,
        'current_round' => $current_round,
        'is_main_competition' => $is_main_competition,
        'body_text' => $body_text,
        'has_access' => $has_access,
        'product_id' => $product_id,
        'user_points' => $user_points,
        'user_tips_count' => $user_tips_count,
        'user_ranking' => $user_ranking,
        'leaderboard' => $leaderboard,
        'active_tab' => $active_tab
    );
}

/**
 * Get team logo
 * 
 * @param int $team_id ID týmu
 * @return string URL loga týmu nebo prázdný řetězec
 */
function tipnijinak_get_team_logo($team_id) {
    if (!$team_id) {
        return '';
    }
    
    $logo_id = get_field('logo_tymu', $team_id);
    
    if ($logo_id) {
        $logo_url = wp_get_attachment_image_url($logo_id, 'thumbnail');
        return $logo_url ? $logo_url : '';
    }
    
    return '';
}

/**
 * Get team name
 * 
 * @param int $team_id ID týmu
 * @return string Název týmu
 */
function tipnijinak_get_team_name($team_id) {
    if (!$team_id) {
        return '';
    }
    
    return get_the_title($team_id);
}

/**
 * Get team abbreviation
 * 
 * @param int $team_id ID týmu
 * @return string Zkratka týmu
 */
function tipnijinak_get_team_abbreviation($team_id) {
    if (!$team_id) {
        return '';
    }
    
    $abbreviation = get_field('zkratka_tymu', $team_id);
    
    // Vrátit zkratku, nebo pokud není vyplněná, celý název týmu
    if (!empty($abbreviation)) {
        return $abbreviation;
    } else {
        return get_the_title($team_id);
    }
}

/**
 * Format match time
 * 
 * @param string $date_time Datum a čas zápasu
 * @return array Formátovaný čas a datum
 */
function tipnijinak_format_match_time($date_time) {
    if (!$date_time) {
        return array(
            'time' => '',
            'date' => ''
        );
    }
    
    $timestamp = strtotime($date_time);
    
    return array(
        'time' => date_i18n('H:i', $timestamp),
        'date' => date_i18n('d.m.Y', $timestamp)
    );
}

/**
 * Get match score
 * 
 * @param int $match_id ID zápasu
 * @return string Skóre zápasu ve formátu "domácí : hosté"
 */
function tipnijinak_get_match_score($match_id) {
    if (!$match_id) {
        return '- : -';
    }

    $stav = get_field('stav_zapasu', $match_id);

    // Skóre zobrazit pouze u ukončených zápasů
    if ($stav !== 'ukonceny') {
        return '- : -';
    }

    $domaci_skore = get_field('skore_domaci', $match_id);
    $hoste_skore = get_field('skore_hoste', $match_id);

    $domaci_skore = $domaci_skore !== '' ? $domaci_skore : '0';
    $hoste_skore = $hoste_skore !== '' ? $hoste_skore : '0';

    return $domaci_skore . ' : ' . $hoste_skore;
}

/**
 * Get match teams
 * 
 * @param int $match_id ID zápasu
 * @return array Informace o týmech
 */
function tipnijinak_get_match_teams($match_id) {
    if (!$match_id) {
        return array(
            'home' => array(
                'id' => 0,
                'name' => '',
                'logo' => ''
            ),
            'away' => array(
                'id' => 0,
                'name' => '',
                'logo' => ''
            )
        );
    }
    
    $domaci_tym_id = get_field('domaci_tym', $match_id);
    $hoste_tym_id = get_field('hoste_tym', $match_id);
    
    return array(
        'home' => array(
            'id' => $domaci_tym_id ? $domaci_tym_id : 0,
            'name' => tipnijinak_get_team_name($domaci_tym_id),
            'logo' => tipnijinak_get_team_logo($domaci_tym_id),
            'abbreviation' => tipnijinak_get_team_abbreviation($domaci_tym_id)
        ),
        'away' => array(
            'id' => $hoste_tym_id ? $hoste_tym_id : 0,
            'name' => tipnijinak_get_team_name($hoste_tym_id),
            'logo' => tipnijinak_get_team_logo($hoste_tym_id),
            'abbreviation' => tipnijinak_get_team_abbreviation($hoste_tym_id)
        )
    );
}

/**
 * Get match details
 * 
 * @param int $match_id ID zápasu
 * @return array Detaily zápasu
 */
function tipnijinak_get_match_details($match_id) {
    if (!$match_id) {
        return array();
    }
    
    $teams = tipnijinak_get_match_teams($match_id);
    $datum_zapasu = get_field('datum_zapasu', $match_id);
    $match_time = tipnijinak_format_match_time($datum_zapasu);
    $score = tipnijinak_get_match_score($match_id);
    $stav = get_field('stav_zapasu', $match_id);
    
    // Získat ligu z taxonomie
    $league = '';
    $league_slug = '';
    $leagues = wp_get_post_terms($match_id, 'liga');
    if (!empty($leagues)) {
        $league = $leagues[0]->name;
        $league_slug = $leagues[0]->slug;
    }
    
    return array(
        'id' => $match_id,
        'title' => get_the_title($match_id),
        'teams' => $teams,
        'time' => $match_time['time'],
        'date' => $match_time['date'],
        'score' => $score,
        'status' => $stav,
        'liga' => $league,
        'liga_slug' => $league_slug
    );
}

/**
 * Get main competition
 * 
 * @return WP_Post|bool Hlavní soutěž nebo false
 */
function tipnijinak_get_main_competition() {
    $args = array(
        'post_type' => 'soutez',
        'posts_per_page' => 1,
        'meta_key' => 'je_hlavni',
        'meta_value' => 1
    );
    
    $query = new WP_Query($args);
    
    if ($query->have_posts()) {
        return $query->posts[0];
    }
    
    return false;
}

/**
 * Get competitions by type
 * 
 * @param string $type_slug Slug typu soutěže
 * @param int $limit Počet soutěží
 * @return array Soutěže daného typu
 */
function tipnijinak_get_competitions_by_type($type_slug, $limit = -1) {
    $args = array(
        'post_type' => 'soutez',
        'posts_per_page' => $limit,
        'tax_query' => array(
            array(
                'taxonomy' => 'typ-souteze',
                'field' => 'slug',
                'terms' => $type_slug
            )
        ),
        'meta_query' => array(
            array(
                'key' => 'je_hlavni',
                'value' => 0,
                'compare' => '='
            )
        )
    );
    
    $query = new WP_Query($args);
    
    return $query->posts;
}

/**
 * Get matches by league
 * 
 * @param string $league_slug Slug ligy
 * @param int $limit Počet zápasů
 * @return array Zápasy dané ligy
 */
function tipnijinak_get_matches_by_league($league_slug, $limit = 10) {
    $args = array(
        'post_type' => 'zapas',
        'posts_per_page' => $limit,
        'tax_query' => array(
            array(
                'taxonomy' => 'liga',
                'field' => 'slug',
                'terms' => $league_slug
            )
        ),
        'meta_key' => 'datum_zapasu',
        'orderby' => 'meta_value',
        'order' => 'ASC'
    );
    
    $query = new WP_Query($args);
    
    return $query->posts;
}

/**
 * Get leagues
 * 
 * @return array Ligy
 */
function tipnijinak_get_leagues() {
    $leagues = get_terms(array(
        'taxonomy' => 'liga',
        'hide_empty' => false
    ));
    
    return $leagues;
}

/**
 * Get competition types
 * 
 * @return array Typy soutěží
 */
function tipnijinak_get_competition_types() {
    $types = get_terms(array(
        'taxonomy' => 'typ-souteze',
        'hide_empty' => false
    ));
    
    return $types;
}

/**
 * Get matches for competition
 * 
 * @param int $competition_id ID soutěže
 * @return array Zápasy pro danou soutěž
 */
function tipnijinak_get_competition_matches($competition_id) {
    if (!$competition_id) {
        return array();
    }
    
    $match_ids = get_field('zapasy_souteze', $competition_id);
    
    if (!$match_ids || !is_array($match_ids)) {
        return array();
    }
    
    $matches = array();
    
    foreach ($match_ids as $match_id) {
        $matches[] = tipnijinak_get_match_details($match_id);
    }
    
    return $matches;
}

/**
 * Get round details
 * 
 * @param int $round_id ID kola
 * @return array Detaily kola
 */
function tipnijinak_get_round_details($round_id) {
    if (!$round_id) {
        return array();
    }
    
    $cislo_kola = get_field('cislo_kola', $round_id);
    $datum_od = get_field('datum_od', $round_id);
    $datum_do = get_field('datum_do', $round_id);
    $stav_kola = get_field('stav_kola', $round_id);
    $zapasy_kola = get_field('zapasy_kola', $round_id);
    
    // Formátovat datum od a do
    $datum_od_format = '';
    $datum_do_format = '';
    
    if ($datum_od) {
        $timestamp_od = strtotime($datum_od);
        $datum_od_format = date_i18n('d.m.Y H:i', $timestamp_od);
    }
    
    if ($datum_do) {
        $timestamp_do = strtotime($datum_do);
        $datum_do_format = date_i18n('d.m.Y H:i', $timestamp_do);
    }
    
    // Definice stavů kola s překlady
    $stavy = array(
        'planovano' => __('Plánováno', 'tipnijinak'),
        'otevreno' => __('Otevřeno', 'tipnijinak'),
        'probihajici' => __('Probíhající', 'tipnijinak'),
        'uzavreno' => __('Uzavřeno', 'tipnijinak')
    );
    
    // Příprava zápasů kola
    $matches = array();
    if (is_array($zapasy_kola) && !empty($zapasy_kola)) {
        foreach ($zapasy_kola as $match_id) {
            if (get_post_status($match_id) !== 'publish') {
                continue;
            }
            $matches[] = tipnijinak_get_match_details($match_id);
        }
    }
    
    return array(
        'id' => $round_id,
        'title' => get_the_title($round_id),
        'cislo_kola' => $cislo_kola ? $cislo_kola : 1,
        'datum_od' => $datum_od,
        'datum_od_format' => $datum_od_format,
        'datum_do' => $datum_do,
        'datum_do_format' => $datum_do_format,
        'stav_kola' => $stav_kola,
        'stav_kola_text' => isset($stavy[$stav_kola]) ? $stavy[$stav_kola] : __('Neznámý', 'tipnijinak'),
        'matches' => $matches
    );
}

/**
 * Get rounds for competition
 * 
 * @param int $competition_id ID soutěže
 * @return array Kola pro danou soutěž
 */
function tipnijinak_get_competition_rounds($competition_id) {
    if (!$competition_id) {
        return array();
    }
    
    $round_ids = get_field('kola_souteze', $competition_id);
    
    if (!$round_ids || !is_array($round_ids)) {
        return array();
    }
    
    $rounds = array();
    
    foreach ($round_ids as $round_id) {
        if (get_post_status($round_id) !== 'publish') {
            continue;
        }
        $rounds[] = tipnijinak_get_round_details($round_id);
    }
    
    // Seřadit kola podle čísla kola vzestupně
    usort($rounds, function($a, $b) {
        return $a['cislo_kola'] - $b['cislo_kola'];
    });
    
    return $rounds;
}

/**
 * Get active competition round
 * 
 * @param int $competition_id ID soutěže
 * @return array|bool Aktivní kolo nebo false pokud není žádné aktivní
 */
function tipnijinak_get_active_competition_round($competition_id) {
    if (!$competition_id) {
        return false;
    }
    
    $rounds = tipnijinak_get_competition_rounds($competition_id);
    
    // Nejprve hledáme kolo se stavem "probihajici"
    foreach ($rounds as $round) {
        if ($round['stav_kola'] === 'probihajici') {
            return $round;
        }
    }
    
    // Pokud není žádné probíhající, hledáme otevřené
    foreach ($rounds as $round) {
        if ($round['stav_kola'] === 'otevreno') {
            return $round;
        }
    }
    
    // Pokud není ani otevřené, vrátíme první neplánované
    foreach ($rounds as $round) {
        if ($round['stav_kola'] !== 'planovano') {
            return $round;
        }
    }
    
    // Pokud jsou všechna plánovaná, vrátíme první
    if (!empty($rounds)) {
        return $rounds[0];
    }
    
    return false;
}

/**
 * Zkontroluje, zda má uživatel zakoupený produkt spojený se soutěží
 *
 * @param int $competition_id ID soutěže
 * @param int $user_id ID uživatele (výchozí: aktuální přihlášený uživatel)
 * @return bool True pokud uživatel zakoupil produkt nebo soutěž nemá propojený produkt, jinak false
 */
function tipnijinak_user_has_access_to_competition($competition_id, $user_id = 0) {
    if (!$competition_id) {
        return false;
    }
    
    // Získáme ID produktu WooCommerce spojený s touto soutěží
    $product_id = get_field('woocommerce_produkt', $competition_id);
    
    // Pokud soutěž nemá propojený produkt, má k ní každý přístup
    if (!$product_id) {
        return true;
    }
    
    // Pokud není uživatel přihlášen, nemá přístup
    if (!is_user_logged_in() && $user_id === 0) {
        return false;
    }
    
    // Pokud není zadáno ID uživatele, použijeme aktuálního přihlášeného uživatele
    if ($user_id === 0) {
        $user_id = get_current_user_id();
    }
    
    // Pokud WooCommerce není aktivní, nemůžeme ověřit nákup
    if (!class_exists('WooCommerce')) {
        return false;
    }
    
    // Kontrola, zda uživatel zakoupil produkt
    $customer_orders = wc_get_orders(array(
        'customer_id' => $user_id,
        'status' => array('wc-completed', 'wc-processing'),
        'limit' => -1,
    ));
    
    foreach ($customer_orders as $order) {
        foreach ($order->get_items() as $item) {
            if ($item->get_product_id() == $product_id) {
                return true;
            }
        }
    }
    
    return false;
}

/**
 * Is mobile device
 * 
 * @return bool True pokud je zařízení mobilní, jinak false
 */
function tipnijinak_is_mobile() {
    $useragent = $_SERVER['HTTP_USER_AGENT'];
    
    if(preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i',$useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($useragent,0,4))) {
        return true;
    }
    
    return false;
}