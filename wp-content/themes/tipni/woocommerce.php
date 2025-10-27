<?php
/**
 * The template for displaying WooCommerce pages
 *
 * @package TipniJinak
 */

get_header();
?>

<div class="content-wrapper">
    <?php woocommerce_content(); ?>
</div>

<?php
get_footer();