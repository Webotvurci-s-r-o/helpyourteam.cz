<?php
/**
 * Custom Walker pro copyright menu
 *
 * @package TipniJinak
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Class Copyright_Menu_Walker
 * 
 * Vlastní walker pro menu copyrightu, který vypisuje pouze odkazy bez obalujícího ul/li
 */
class Copyright_Menu_Walker extends Walker_Nav_Menu {
    /**
     * Začátek prvku menu
     */
    public function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
        $output .= '<a href="' . esc_url( $item->url ) . '">' . esc_html( $item->title ) . '</a>';
    }

    /**
     * Konec prvku menu - nepřidáváme žádný výstup
     */
    public function end_el( &$output, $item, $depth = 0, $args = array() ) {
        // Nepřidáváme žádný výstup
    }

    /**
     * Začátek sub menu - nepřidáváme žádný výstup
     */
    public function start_lvl( &$output, $depth = 0, $args = array() ) {
        // Nepřidáváme žádný výstup
    }

    /**
     * Konec sub menu - nepřidáváme žádný výstup
     */
    public function end_lvl( &$output, $depth = 0, $args = array() ) {
        // Nepřidáváme žádný výstup
    }
}