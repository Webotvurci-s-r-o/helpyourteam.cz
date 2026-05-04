<?php
/**
 * Template Name: Profil
 */

// Kontrola, zda je uživatel přihlášen, pokud ne, přesměrování na stránku s přihlášením
if (!is_user_logged_in()) {
    wp_redirect(home_url('/prihlasit-se/'));
    exit;
}

// Získání aktuálního uživatele a jeho meta údajů
$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Základní informace
$username = $current_user->user_login;
$first_name = get_user_meta($user_id, 'first_name', true);
$last_name = get_user_meta($user_id, 'last_name', true);
$email = $current_user->user_email;
$phone = get_user_meta($user_id, 'phone', true);
$address = get_user_meta($user_id, 'address', true);
$city = get_user_meta($user_id, 'city', true);
$psc = get_user_meta($user_id, 'psc', true);

// Informace o klubu
$club_id = get_user_meta($user_id, 'club_id', true);
$club_name = get_user_meta($user_id, 'club_name', true);
$club_search = get_user_meta($user_id, 'club_search', true);

// Souhlas s podmínkami
$terms_agreement = get_user_meta($user_id, 'terms_agreement', true);
$marketing_agreement = get_user_meta($user_id, 'marketing_agreement', true);

// Platební historie - získáme WooCommerce objednávky uživatele
$orders = array();
if (class_exists('WooCommerce')) {
    $customer_orders = wc_get_orders(array(
        'customer' => $user_id,
        'limit' => 10,
        'orderby' => 'date',
        'order' => 'DESC',
    ));
    
    if (!empty($customer_orders)) {
        foreach ($customer_orders as $order) {
            $orders[] = array(
                'id' => $order->get_id(),
                'date' => $order->get_date_created()->date_i18n(get_option('date_format')),
                'total' => $order->get_formatted_order_total(),
                'status' => $order->get_status(),
                'url' => $order->get_view_order_url(),
            );
        }
    }
}

get_header();
?>

<div class="content-wrapper">
    <div class="profile">
        <div class="profile-left">
            <div class="profile-box box basic-info">
                <p><strong>Základní informace</strong></p>
                <div class="table-info table-basic-info">
                    <div class="table-row">
                        <span class="table-row__name">Uživatelské jméno</span>
                        <span class="table-row__value"><?php echo esc_html($username); ?></span>
                        <div class="btn-holder"></div>
                    </div>
                    <div class="table-row">
                        <span class="table-row__name">Jméno</span>
                        <span class="table-row__value"><?php echo esc_html($first_name); ?></span>
                        <div class="btn-holder"><a href="#" class="btn btn-secondary edit-profile" data-field="first_name">Změnit</a></div>
                    </div>
                    <div class="table-row">
                        <span class="table-row__name">Příjmení</span>
                        <span class="table-row__value"><?php echo esc_html($last_name); ?></span>
                        <div class="btn-holder"><a href="#" class="btn btn-secondary edit-profile" data-field="last_name">Změnit</a></div>
                    </div>
                    <div class="table-row">
                        <span class="table-row__name">Email</span>
                        <span class="table-row__value"><?php echo esc_html($email); ?></span>
                        <div class="btn-holder"><a href="#" class="btn btn-secondary edit-profile" data-field="email">Změnit</a></div>
                    </div>
                    <div class="table-row">
                        <span class="table-row__name">Telefon</span>
                        <span class="table-row__value"><?php echo esc_html($phone); ?></span>
                        <div class="btn-holder"><a href="#" class="btn btn-secondary edit-profile" data-field="phone">Změnit</a></div>
                    </div>
                    <div class="table-row">
                        <span class="table-row__name">Adresa</span>
                        <span class="table-row__value">
                            <?php
                            $address_parts = array();
                            if (!empty($address)) $address_parts[] = $address;
                            if (!empty($city)) $address_parts[] = $city;
                            if (!empty($psc)) $address_parts[] = $psc;
                            
                            echo !empty($address_parts) ? esc_html(implode(', ', $address_parts)) : '...';
                            ?>
                        </span>
                        <div class="btn-holder"><a href="#" class="btn btn-secondary edit-profile" data-field="address">Změnit</a></div>
                    </div>
                </div>
            </div>
            <div class="profile-box box gdpr">
                <p><strong>Ochrana osobních údajů</strong></p>
                <div class="table-info table-gdpr">
                    <div class="table-row">
                        <span class="table-row__name">Podmínky a pravidla</span>
                        <span class="table-row__value">
                            <?php if ($terms_agreement === 'yes') : ?>
                                <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/checkmark.svg'); ?>" alt="">Potvrzeno
                            <?php else : ?>
                                <span class="not-confirmed">Nepotvrzeno</span>
                            <?php endif; ?>
                        </span>
                        <div class="btn-holder"><a href="#" class="btn btn-secondary edit-profile" data-field="terms_agreement">Změnit</a></div>
                    </div>
                    <div class="table-row">
                        <span class="table-row__name">Marketingové akce</span>
                        <span class="table-row__value">
                            <?php if ($marketing_agreement === 'yes') : ?>
                                <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/checkmark.svg'); ?>" alt="">Potvrzeno
                            <?php else : ?>
                                <span class="not-confirmed">Nepotvrzeno</span>
                            <?php endif; ?>
                        </span>
                        <div class="btn-holder"><a href="#" class="btn btn-secondary edit-profile" data-field="marketing_agreement">Změnit</a></div>
                    </div>
                </div>
            </div>
            <div class="profile-box box matches " style="display:none !important">
                <p><strong>Historie soutěží</strong></p>
                <?php
                // Zde by byla logika pro získání historie soutěží uživatele
                // Pro demonstraci použijeme statická data
                $matches = array(
                    array(
                        'home_team' => 'Sparta Praha',
                        'home_logo' => get_template_directory_uri() . '/assets/images/sparta-logo.png',
                        'away_team' => 'Slavia Praha',
                        'away_logo' => get_template_directory_uri() . '/assets/images/sparta-logo.png',
                        'score' => '2 : 1',
                        'url' => '#'
                    ),
                    array(
                        'home_team' => 'Viktoria Plzeň',
                        'home_logo' => get_template_directory_uri() . '/assets/images/sparta-logo.png',
                        'away_team' => 'Baník Ostrava',
                        'away_logo' => get_template_directory_uri() . '/assets/images/sparta-logo.png',
                        'score' => '0 : 0',
                        'url' => '#'
                    ),
                );
                ?>
                <div class="table-info table-competition">
                    <?php if (!empty($matches)) : ?>
                        <?php foreach ($matches as $match) : ?>
                            <div class="table-row">
                                <div class="table-row__match">
                                    <div class="home team">
                                        <div class="team-name"><?php echo esc_html($match['home_team']); ?></div>
                                        <div class="logo-holder">
                                            <img src="<?php echo esc_url($match['home_logo']); ?>" alt="">
                                        </div>
                                    </div>
                                    <div class="match-score">
                                        <?php echo esc_html($match['score']); ?>
                                    </div>
                                    <div class="away team">
                                        <div class="team-name"><?php echo esc_html($match['away_team']); ?></div>
                                        <div class="logo-holder">
                                            <img src="<?php echo esc_url($match['away_logo']); ?>" alt="">
                                        </div>
                                    </div>
                                </div>
                                <div class="btn-holder"><a href="<?php echo esc_url($match['url']); ?>" class="btn btn-primary">Detail</a></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div class="table-row">
                            <span>Zatím nemáte žádné soutěže.</span>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if (!empty($matches)) : ?>
                <div class="pagination">
                    <div class="pagination-prev"></div>
                    <div class="pagination-next"></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="profile-right">
            <div class="box profile-box club">
                <p><strong>KLUB</strong></p>
                <p class="club-note">Váš oblíbený klub z nižší soutěže (3.liga max.)</p>
                <div class="club-search-container">
                    <input type="search" id="profile-club-search" placeholder="Vyhledat klub" value="<?php echo esc_attr($club_name); ?>">
                    <a href="#" id="add-new-club" class="btn btn-primary btn-sm">Přidat klub</a>
                    <?php wp_nonce_field('ajax_nonce', 'search_nonce'); ?>
                </div>
                <input type="hidden" id="profile-club-id" value="<?php echo esc_attr($club_id); ?>">
                <?php if (empty($club_name)) : ?>
                    <p>Není zvolen preferovaný klub...</p>
                <?php else : ?>
                    <div class="favorite-club">
                        <p>Váš oblíbený klub:</p>
                        <div class="club-info">
                            <?php 
                            $club_logo_url = '';
                            if (!empty($club_id)) {
                                $logo_id = get_field('logo_tymu', $club_id);
                                if (!empty($logo_id) && is_numeric($logo_id)) {
                                    $club_logo_url = wp_get_attachment_url($logo_id);
                                }
                            }
                            ?>
                            <?php if (!empty($club_logo_url)) : ?>
                                <div class="club-logo">
                                    <img src="<?php echo esc_url($club_logo_url); ?>" alt="<?php echo esc_attr($club_name); ?>">
                                </div>
                            <?php endif; ?>
                            <strong><?php echo esc_html($club_name); ?></strong>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Modal pro přidání nového klubu -->
            <div id="add-club-modal" class="modal" style="display: none;">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2>Přidat nový klub</h2>
                    <form id="add-club-form" enctype="multipart/form-data">
                        <?php wp_nonce_field('ajax_nonce', 'club_nonce'); ?>
                        <div class="form-group">
                            <label for="club_title">Název klubu</label>
                            <input type="text" id="club_title" name="club_title" required>
                        </div>
                        <div class="form-group">
                            <label for="club_logo">Logo klubu</label>
                            <div class="file-input-wrapper">
                                <input type="file" id="club_logo" name="club_logo" accept="image/*" class="file-input">
                                <div class="file-input-custom">
                                    <span class="file-input-text">Vybrat soubor</span>
                                    <div class="file-input-preview"></div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="club_abbr">Zkratka klubu</label>
                            <input type="text" id="club_abbr" name="club_abbr"  >
                        </div>
                        <div class="form-group">
                            <label for="club_league">Liga</label>
                            <select id="club_league" name="club_league">
                                <?php
                                $ligy = get_terms(array(
                                    'taxonomy' => 'liga',
                                    'child_of' => 46,
                                    'hide_empty' => false,
                                ));
                                
                                if (!empty($ligy) && !is_wp_error($ligy)) {
                                    foreach ($ligy as $liga) {
                                        echo '<option value="' . esc_attr($liga->term_id) . '">' . esc_html($liga->name) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Přidat klub</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="profile-box box payments">
                <p><strong>Historie plateb</strong></p>
                <?php if (!empty($orders)) : ?>
                <table>
                    <thead>
                    <tr>
                        <th>Datum</th>
                        <th>Částka</th>
                        <th>Uhrazeno</th>
                        <th>Detail</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($orders as $order) : ?>
                        <tr>
                            <td><?php echo esc_html($order['date']); ?></td>
                            <td><?php echo wp_kses_post($order['total']); ?></td>
                            <td>
                                <?php if ($order['status'] === 'completed' || $order['status'] === 'processing') : ?>
                                    <span class="paid">️</span>
                                <?php else : ?>
                                    <span class="not-paid"></span>
                                <?php endif; ?>
                            </td>
                            <td class="icon">
                                <a href="<?php echo esc_url($order['url']); ?>">
                                    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/leave.svg'); ?>" alt="">
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="pagination">
                    <div class="pagination-prev"></div>
                    <div class="pagination-next"></div>
                </div>
                <?php else : ?>
                <p>Zatím nemáte žádné platby.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>