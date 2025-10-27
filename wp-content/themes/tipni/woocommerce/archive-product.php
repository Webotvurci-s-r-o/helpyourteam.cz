<?php
/**
 * The Template for displaying product archives, including the main shop page which is a post type archive
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/archive-product.php.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.4.0
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<div class="content-wrapper">
    <h1><?php woocommerce_page_title(); ?></h1>
    
    <div class="shop-content">
        <?php
        /**
         * Hook: woocommerce_before_main_content.
         *
         * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
         * @hooked woocommerce_breadcrumb - 20
         * @hooked WC_Structured_Data::generate_website_data() - 30
         */
        do_action( 'woocommerce_before_main_content' );
        ?>

        <div class="shop-filter">
            <h3>Filtrovat produkty</h3>
            <form id="filter-form" class="filter-form">
                <div class="filter-group">
                    <label for="category-filter">Kategorie</label>
                    <select id="category-filter" name="category">
                        <option value="">Všechny kategorie</option>
                        <?php
                        $categories = tipnijinak_get_product_categories();
                        foreach ($categories as $category) {
                            $selected = isset($_GET['category']) && $_GET['category'] === $category['slug'] ? 'selected' : '';
                            echo '<option value="' . esc_attr($category['slug']) . '" ' . $selected . '>' . esc_html($category['name']) . ' (' . esc_html($category['count']) . ')</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="min-price">Cena od</label>
                    <input type="number" id="min-price" name="min_price" placeholder="0" value="<?php echo isset($_GET['min_price']) ? intval($_GET['min_price']) : ''; ?>">
                </div>
                <div class="filter-group">
                    <label for="max-price">Cena do</label>
                    <input type="number" id="max-price" name="max_price" placeholder="10000" value="<?php echo isset($_GET['max_price']) ? intval($_GET['max_price']) : ''; ?>">
                </div>
                <div class="btn-holder">
                    <button type="submit" class="btn btn-primary">Filtrovat</button>
                    <button type="button" id="reset-filter" class="btn btn-secondary">Resetovat</button>
                </div>
            </form>
        </div>

        <div class="subtitle">
            Vyberte si produkt, který vám nejvíce vyhovuje. Získejte přístup k našim tipovacím soutěžím a staňte se součástí naší komunity.
        </div>
        
        <div class="products">
            <?php
            if ( woocommerce_product_loop() ) {

                /**
                 * Hook: woocommerce_before_shop_loop.
                 *
                 * @hooked woocommerce_output_all_notices - 10
                 * @hooked woocommerce_result_count - 20
                 * @hooked woocommerce_catalog_ordering - 30
                 */
                do_action( 'woocommerce_before_shop_loop' );

                // Zobrazení produktů ve stylu WooCommerce bloků
                echo '<div data-block-name="woocommerce/product-grid" class="wc-block-grid wp-block-product-grid wc-block-product-grid has-4-columns">';
                echo '<ul class="wc-block-grid__products">';
                
                // Nastavení dotazu pro produkty WooCommerce
                $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
                $args = array(
                    'post_type'      => 'product',
                    'posts_per_page' => 8,
                    'orderby'        => 'date',
                    'order'          => 'DESC',
                    'paged'          => $paged
                );
                
                // Vyloučit produkty z kategorie Soutěž (ID 45)
                $args['tax_query'] = array(
                    array(
                        'taxonomy' => 'product_cat',
                        'field'    => 'term_id',
                        'terms'    => array(45),
                        'operator' => 'NOT IN',
                    ),
                );
                
                // Filtrování podle kategorií, pokud je zvoleno
                if (isset($_GET['category']) && !empty($_GET['category'])) {
                    $args['tax_query'][] = array(
                        'taxonomy' => 'product_cat',
                        'field'    => 'slug',
                        'terms'    => sanitize_text_field($_GET['category']),
                    );
                    // Přidat relation pro kombinaci podmínek
                    $args['tax_query']['relation'] = 'AND';
                }
                
                // Filtrování podle ceny, pokud je zvoleno
                if (isset($_GET['min_price']) && isset($_GET['max_price'])) {
                    $args['meta_query'] = array(
                        array(
                            'key'     => '_price',
                            'value'   => array(sanitize_text_field($_GET['min_price']), sanitize_text_field($_GET['max_price'])),
                            'type'    => 'NUMERIC',
                            'compare' => 'BETWEEN',
                        ),
                    );
                }
                
                $products_query = new WP_Query($args);
                
                if ($products_query->have_posts()) :
                    while ($products_query->have_posts()) : $products_query->the_post();
                        global $product;
                        if ($product && $product->is_purchasable()) :
                ?>
                <li class="wc-block-grid__product">
                    <a href="<?php the_permalink(); ?>" class="wc-block-grid__product-link">
                        <div class="wc-block-grid__product-image">
                            <?php if (has_post_thumbnail()) : ?>
                                <?php the_post_thumbnail('woocommerce_thumbnail'); ?>
                            <?php else : ?>
                                <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/product-photo.jpg'); ?>" class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail" alt="<?php the_title_attribute(); ?>" loading="lazy">
                            <?php endif; ?>
                        </div>
                        <div class="wc-block-grid__product-title"><?php the_title(); ?></div>
                    </a>
                    <div class="wc-block-grid__product-price price"><?php echo $product->get_price_html(); ?></div>
                    <div class="wp-block-button wc-block-grid__product-add-to-cart">
                        <a href="?add-to-cart=<?php echo esc_attr($product->get_id()); ?>" 
                           aria-label="Přidat do košíku: &quot;<?php echo esc_attr($product->get_name()); ?>&quot;" 
                           data-quantity="1" 
                           data-product_id="<?php echo esc_attr($product->get_id()); ?>" 
                           data-product_sku="<?php echo esc_attr($product->get_sku()); ?>" 
                           data-price="<?php echo esc_attr($product->get_price()); ?>" 
                           rel="nofollow" 
                           class="wp-block-button__link add_to_cart_button ajax_add_to_cart">
                            Přidat do košíku
                        </a>
                    </div>
                </li>
                <?php 
                        endif;
                    endwhile;
                    
                    echo '</ul></div>';
                    
                    // Pagination
                    echo '<div class="shop-pagination">';
                    echo paginate_links(array(
                        'total'   => $products_query->max_num_pages,
                        'current' => $paged,
                        'prev_text' => '&laquo; Předchozí',
                        'next_text' => 'Další &raquo;',
                    ));
                    echo '</div>';
                    
                    wp_reset_postdata();
                else :
                ?>
                <div class="no-products">
                    <p>Momentálně nejsou k dispozici žádné produkty.</p>
                </div>
                <?php endif;

                woocommerce_product_loop_end();

                /**
                 * Hook: woocommerce_after_shop_loop.
                 *
                 * @hooked woocommerce_pagination - 10
                 */
                do_action( 'woocommerce_after_shop_loop' );
            } else {
                /**
                 * Hook: woocommerce_no_products_found.
                 *
                 * @hooked wc_no_products_found - 10
                 */
                do_action( 'woocommerce_no_products_found' );
            }
            ?>
        </div>

        <?php
        /**
         * Hook: woocommerce_after_main_content.
         *
         * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content)
         */
        do_action( 'woocommerce_after_main_content' );
        ?>
    </div>
</div>

<!-- Modální okno pro výběr platby -->
<div id="payment-modal" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h3>Dokončení objednávky</h3>
        
        <div class="order-summary">
            <h4>Souhrn objednávky</h4>
            <div class="product-info">
                <p><strong>Produkt:</strong> <span id="modal-product-name"></span></p>
                <p><strong>Cena:</strong> <span id="modal-product-price"></span></p>
            </div>
        </div>
        
        <?php if (is_user_logged_in()) : ?>
            <?php 
            $current_user = wp_get_current_user();
            $phone = get_user_meta($current_user->ID, 'phone', true);
            ?>
            <div class="customer-info">
                <h4>Údaje zákazníka</h4>
                <p><strong>Jméno:</strong> <span id="modal-customer-name"><?php echo esc_html($current_user->display_name); ?></span></p>
                <p><strong>Email:</strong> <span id="modal-customer-email"><?php echo esc_html($current_user->user_email); ?></span></p>
                <p><strong>Telefon:</strong> <span id="modal-customer-phone"><?php echo esc_html($phone); ?></span></p>
            </div>
        <?php else : ?>
            <div class="login-prompt">
                <p>Pro dokončení nákupu se prosím <a href="<?php echo esc_url(site_url('/login')); ?>">přihlaste</a> nebo <a href="<?php echo esc_url(site_url('/registrace')); ?>">zaregistrujte</a>.</p>
            </div>
        <?php endif; ?>
        
        <?php if (is_user_logged_in()) : ?>
            <div class="payment-methods">
                <h4>Vyberte způsob platby</h4>
                <div class="payment-options">
                    <?php
                    // Načtení dostupných platebních metod z WooCommerce
                    if (class_exists('WooCommerce')) {
                        $payment_gateways = WC()->payment_gateways->get_available_payment_gateways();
                        
                        if (!empty($payment_gateways)) {
                            $first = true;
                            foreach ($payment_gateways as $gateway) {
                                if ($gateway->enabled === 'yes') {
                                    echo '<label>';
                                    echo '<input type="radio" name="payment_method" value="' . esc_attr($gateway->id) . '"' . ($first ? ' checked' : '') . '>';
                                    echo '<span>' . esc_html($gateway->get_title()) . '</span>';
                                    echo '</label>';
                                    $first = false;
                                }
                            }
                        } else {
                            // Výchozí možnosti, pokud nejsou žádné platební brány dostupné
                            echo '<label>';
                            echo '<input type="radio" name="payment_method" value="bacs" checked>';
                            echo '<span>Bankovní převod</span>';
                            echo '</label>';
                        }
                    } else {
                        // Výchozí možnosti, pokud WooCommerce není aktivní
                        echo '<label>';
                        echo '<input type="radio" name="payment_method" value="bacs" checked>';
                        echo '<span>Bankovní převod</span>';
                        echo '</label>';
                    }
                    ?>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cancel-order">Zrušit</button>
                <button type="button" class="btn btn-primary" id="confirm-order">Potvrdit objednávku</button>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
get_footer();
?>