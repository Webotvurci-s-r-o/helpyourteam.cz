<?php
/**
 * Template Name: Obchod
 */

get_header();
?>

<div class="content-wrapper">
    <h1>Obchod</h1>
    
    <div class="shop-content">
        <div class="subtitle">
            Vyberte si produkt, který vám nejvíce vyhovuje. Získejte přístup k našim tipovacím soutěžím a staňte se součástí naší komunity.
        </div>
        
        <div class="products">
            <?php
            // Nastavení dotazu pro produkty WooCommerce
            $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
            $args = array(
                'post_type'      => 'product',
                'posts_per_page' => 8,
                'orderby'        => 'date',
                'order'          => 'DESC',
                'paged'          => $paged
            );
            
            // Filtrování podle kategorií, pokud je zvoleno
            if (isset($_GET['category']) && !empty($_GET['category'])) {
                $args['tax_query'] = array(
                    array(
                        'taxonomy' => 'product_cat',
                        'field'    => 'slug',
                        'terms'    => sanitize_text_field($_GET['category']),
                    ),
                );
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
            <div class="product" data-product-id="<?php echo esc_attr($product->get_id()); ?>" data-product-price="<?php echo esc_attr($product->get_price()); ?>">
                <div class="img-holder">
                    <?php if (has_post_thumbnail()) : ?>
                        <?php the_post_thumbnail('medium'); ?>
                    <?php else : ?>
                        <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/product-photo.jpg'); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy">
                    <?php endif; ?>
                </div>
                <div class="product-text">
                    <h3 class="product-title"><?php the_title(); ?></h3>
                    <h4 class="product-price"><?php echo $product->get_price_html(); ?></h4>
                    <div class="product-description">
                        <?php echo wp_trim_words(get_the_excerpt(), 15, '...'); ?>
                    </div>
                    <div class="btn-holder">
                        <button type="button" class="btn btn-primary buy-product" data-product-id="<?php echo esc_attr($product->get_id()); ?>">Koupit</button>
                        <a href="<?php the_permalink(); ?>" class="btn btn-secondary">Detail</a>
                    </div>
                </div>
            </div>
            <?php 
                    endif;
                endwhile;
                
                // Pagination
                echo '<div class="pagination">';
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
            <?php endif; ?>
        </div>
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

<?php get_footer(); ?>