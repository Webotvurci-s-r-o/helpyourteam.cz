<?php
/**
 * Template Name: Hlavní soutěž
 *
 * @package TipniJinak
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

get_header();
?>

<div class="main">
    <?php get_template_part('template-parts/content', 'hlavni-soutez'); ?>
</div>

<?php
get_footer();