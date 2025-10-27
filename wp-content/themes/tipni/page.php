<?php
/**
 * The template for displaying all pages
 *
 * @package TipniJinak
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

get_header();
?>

<div class="main">
    <?php while (have_posts()) : the_post(); ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <header class="entry-header">
                <?php the_title('<h1 class="entry-title">', '</h1>'); ?>
            </header>

            <div class="entry-content">
                <?php the_content(); ?>
                <?php
                    wp_link_pages(
                        array(
                            'before' => '<div class="page-links">' . esc_html__('Stránky:', 'tipnijinak'),
                            'after'  => '</div>',
                        )
                    );
                ?>
            </div>
        </article>
    <?php endwhile; ?>
</div>

<?php
get_footer();