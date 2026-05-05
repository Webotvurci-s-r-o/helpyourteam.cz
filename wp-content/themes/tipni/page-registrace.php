<?php
/**
 * Template Name: Registrace
 */

get_header();
?>

<div class="content-wrapper">
    <h1>Nová registrace</h1>
    <ul class="steps">
        <li class="active" data-step="1"><span>Osobní informace</span></li>
        <li data-step="2"><span>Přihlašovací údaje</span></li>
        <li data-step="3"><span>Dokončení registrace</span></li>
        <li data-step="4"><span>Dokončení</span></li>
    </ul>

    <div class="form-holder ">
        <!-- Krok 1: Osobní informace -->
        <div class="registration-step small-container" id="step-1">
            <h3>Osobní informace</h3>
            <form class="registration step-1" id="registration-form-1">
                <?php wp_nonce_field('registrace_ajax', 'registrace_nonce'); ?>
                <input type="hidden" id="modal_nonce" name="modal_nonce" value="<?php echo wp_create_nonce('registrace_ajax'); ?>">
                <div class="label-holder">
                    <label for="name">Jméno</label>
                    <input type="text" id="name" name="name" placeholder="Vaše jméno" required>
                </div>
                <div class="label-holder">
                    <label for="surname">Příjmení</label>
                    <input type="text" id="surname" name="surname" placeholder="Vaše příjmení" required>
                </div>
                <div class="label-holder">
                    <label for="address">Adresa - ulice</label>
                    <input type="text" id="address" name="address" placeholder="Ulice">
                </div>
                <div class="column">
                    <div class="label-holder">
                        <label for="city">Adresa - Město</label>
                        <input type="text" id="city" name="city" placeholder="Město">
                    </div>
                    <div class="label-holder">
                        <label for="psc">Psč</label>
                        <input type="text" id="psc" name="psc" placeholder="Psč">
                    </div>
                </div>
                <div class="btn-holder">
                    <button class="btn btn-primary next-step" type="button" data-next="2">Pokračovat</button>
                </div>
            </form>
        </div>

        <!-- Krok 2: Přihlašovací údaje -->
        <div class="registration-step small-container" id="step-2" style="display: none;">
            <h3>Přihlašovací údaje</h3>
            <form class="registration step-2" id="registration-form-2">
                <div class="label-holder">
                    <label for="mail">Email</label>
                    <input type="email" id="mail" name="mail" placeholder="Váš e-mail" required>
                </div>
                <div class="label-holder">
                    <label for="login">Uživatelské jméno</label>
                    <input type="text" id="login" name="login" placeholder="Vaše jméno" required>
                </div>
                <div class="label-holder">
                    <label for="password">Heslo</label>
                    <input type="password" id="password" name="password" placeholder="**********" required>
                </div>
                <div class="label-holder">
                    <label for="phone">Telefon</label>
                    <input type="tel" id="phone" name="phone" placeholder="Váš telefon" required>
                </div>
                <div class="btns-holder">
                    <button class="btn btn-secondary prev-step" type="button" data-prev="1">Předchozí</button>
                    <button class="btn btn-primary next-step" type="button" data-next="3">Pokračovat</button>
                </div>
            </form>
        </div>

        <!-- Krok 3: Dokončení registrace -->
        <div class="registration-step small-container" id="step-3" style="display: none;">
            <h3>Dokončení registrace</h3>
            <form class="registration step-3" id="registration-form-3">
                <div class="label-holder">
                    <label for="club-search">Vyberte svůj oblíbený klub</label>
                    <p class="club-note">Váš oblíbený klub z nižší soutěže (3.liga max.)</p>
                    <div class="club-search-container">
                        <input type="search" id="club-search" name="club_search" placeholder="Vyhledat klub">
                        <a href="#" id="add-new-club" class="btn btn-primary btn-sm">Přidat klub</a>
                    </div>
                    <input type="hidden" id="selected-club" name="selected_club">
                </div>
                <div class="label-holder">
                    <label>
                        <input type="checkbox" name="terms_agreement" required>
                        <span>
                            Jsem starší 18 let a souhlasím s <a href="#">ochranou osobních údajů</a>,
                            <a href="#">herními plány</a>, a s <a href="#">obchodními podmínkami</a>.
                        </span>
                    </label>
                </div>
                <div class="label-holder">
                    <label>
                        <input type="checkbox" name="marketing_agreement"/>
                        <span>
                            Souhlasím se zpracováním výše uvedených osobních údajů za účelem marketingových aktivit včetně zasílání marketingových zpráv a informování o produktech a službách (např. bonusy atd.). Potvrzuji, že jsem se seznámil s celým textem <a href="#">souhlasu</a>.
                        </span>
                    </label>
                </div>
                <div class="btns-holder">
                    <button class="btn btn-secondary prev-step" type="button" data-prev="2">Předchozí</button>
                    <button class="btn btn-primary next-step" type="button" data-next="4">Zaregistrovat se</button>
                </div>
            </form>
        </div>

        <!-- Krok 4: Dokončení registrace -->
        <div class="registration-step small-container" id="step-4" style="display: none;">
            <div class="registration-success">
                <h3>Registrace úspěšně dokončena!</h3>
                <p>Tvůj účet je vytvořený a nyní se můžeš přihlásit :). K účasti v obou jedinečných soutěžích stačí nákup produktu v našem e-shopu.</p>
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
            
            <div class="customer-info">
                <h4>Údaje zákazníka</h4>
                <p><strong>Jméno:</strong> <span id="modal-customer-name"></span></p>
                <p><strong>Email:</strong> <span id="modal-customer-email"></span></p>
                <p><strong>Telefon:</strong> <span id="modal-customer-phone"></span></p>
            </div>
            
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
        </div>
    </div>

    <!-- Modal pro přidání nového klubu -->
    <div id="add-club-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h3>Přidat nový klub</h3>
            <form id="add-club-form" enctype="multipart/form-data">
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
                    <input type="text" id="club_abbr" name="club_abbr">
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
</div>

<?php get_footer(); ?>