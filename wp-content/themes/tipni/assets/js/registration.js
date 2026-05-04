jQuery(document).ready(function($) {
    // Registrační data
    let registrationData = {};

    // Přepínání mezi kroky registrace
    $('.next-step').on('click', function(e) {
        e.preventDefault();
        
        const currentStep = parseInt($(this).attr('data-next')) - 1;
        const nextStep = parseInt($(this).attr('data-next'));
        const currentForm = $(`#registration-form-${currentStep}`);
        
        // Validace formuláře
        if (!validateForm(currentForm)) {
            return false;
        }
        
        // Uložení dat z aktuálního kroku
        saveStepData(currentStep);
        
        // Zobrazení dalšího kroku
        $('.registration-step').hide();
        $(`#step-${nextStep}`).show();
        
        // Aktualizace stavů kroků
        updateSteps(nextStep);
        
        // Pokud je to poslední krok, odešleme data na server
        if (nextStep === 4) {
            submitRegistration();
        }
        
        return false;
    });

    // Návrat na předchozí krok
    $('.prev-step').on('click', function(e) {
        e.preventDefault();
        
        const prevStep = parseInt($(this).attr('data-prev'));
        
        // Zobrazení předchozího kroku
        $('.registration-step').hide();
        $(`#step-${prevStep}`).show();
        
        // Aktualizace stavů kroků
        updateSteps(prevStep);
        
        return false;
    });

    // Validace formuláře
    function validateForm(form) {
        let isValid = true;
        
        // Zkontrolujeme všechna povinná pole
        form.find('input[required]').each(function() {
            if (!$(this).val()) {
                $(this).addClass('error');
                isValid = false;
            } else {
                $(this).removeClass('error');
            }
        });
        
        // Zkontrolujeme validitu emailu v kroku 2
        if (form.attr('id') === 'registration-form-2') {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const email = $('#mail').val();
            
            if (email && !emailRegex.test(email)) {
                $('#mail').addClass('error');
                isValid = false;
            }
        }
        
        return isValid;
    }

    // Uložení dat z kroku
    function saveStepData(step) {
        const form = $(`#registration-form-${step}`);
        
        form.find('input, select').each(function() {
            const name = $(this).attr('name');
            const value = $(this).val();
            
            if (name) {
                if ($(this).attr('type') === 'checkbox') {
                    registrationData[name] = $(this).prop('checked');
                } else {
                    registrationData[name] = value;
                }
            }
        });
        
        // Uložíme data do sessionStorage
        sessionStorage.setItem('registrationData', JSON.stringify(registrationData));
    }
    
    // Oprava klikání na checkboxy - přidání event handleru
    $(document).on('click', 'label:has(input[type="checkbox"]) span', function(e) {
        // Zastavíme propagaci eventu, pokud uživatel klikl na odkaz v labelu
        if ($(e.target).is('a')) {
            return;
        }
        
        // Přepneme stav checkboxu
        const checkbox = $(this).closest('label').find('input[type="checkbox"]');
        checkbox.prop('checked', !checkbox.prop('checked'));
        
        // Vyvoláme změnu pro aktualizaci validace
        checkbox.trigger('change');
        
        // Zabránění propagaci na další elementy
        e.preventDefault();
    });

    // Aktualizace stavů kroků v navigaci
    function updateSteps(activeStep) {
        $('.steps li').removeClass('active completed');
        
        $('.steps li').each(function() {
            const step = parseInt($(this).attr('data-step'));
            
            if (step < activeStep) {
                $(this).addClass('completed');
            } else if (step === activeStep) {
                $(this).addClass('active');
            }
        });
    }

    // Odeslání registrace na server
    function submitRegistration() {
        $.ajax({
            type: 'POST',
            url: ajax_object.ajax_url,
            data: {
                action: 'process_registration',
                nonce: $('#modal_nonce').val(),
                registration_data: registrationData
            },
            success: function(response) {
                if (response.success) {
                    // Úspěšná registrace - zobrazí se produktový krok
                    // Nyní zůstáváme na stránce, produktový krok se zobrazí automaticky
                } else {
                    // Chyba - zobrazení chybové zprávy
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('Došlo k chybě při zpracování registrace. Zkuste to prosím znovu.');
            }
        });
    }
    
    // Obsluha tlačítek pro nákup produktu
    $(document).on('click', '.buy-product', function() {
        const productId = $(this).data('product-id');
        const productElement = $(this).closest('.product');
        const productName = productElement.find('.product-title').text();
        const productPrice = productElement.find('.product-price').text();
        
        // Naplnění modálního okna údaji o produktu a zákazníkovi
        $('#modal-product-name').text(productName);
        $('#modal-product-price').text(productPrice);
        
        // Naplnění údajů o zákazníkovi z registračních dat
        $('#modal-customer-name').text(registrationData.name + ' ' + registrationData.surname);
        $('#modal-customer-email').text(registrationData.mail);
        $('#modal-customer-phone').text(registrationData.phone);
        
        // Uložení ID produktu do skrytého pole
        $('#payment-modal').data('product-id', productId);
        
        // Zobrazení modálního okna
        $('#payment-modal').show();
    });
    
    // Zavření modálního okna
    $('.close-modal, #cancel-order').on('click', function() {
        $('#payment-modal').hide();
    });
    
    // Zavření modálního okna kliknutím mimo obsah
    $(window).on('click', function(event) {
        if ($(event.target).is('#payment-modal')) {
            $('#payment-modal').hide();
        }
    });
    
    // Potvrzení objednávky
    $('#confirm-order').on('click', function() {
        const productId = $('#payment-modal').data('product-id');
        const paymentMethod = $('input[name="payment_method"]:checked').val();
        
        // Vytvoření objednávky
        $.ajax({
            type: 'POST',
            url: ajax_object.ajax_url,
            data: {
                action: 'create_woo_order',
                nonce: $('#modal_nonce').val(),
                product_id: productId,
                payment_method: paymentMethod,
                customer_data: registrationData
            },
            beforeSend: function() {
                // Zobrazení načítacího stavu
                $('#confirm-order').prop('disabled', true).text('Zpracování...');
            },
            success: function(response) {
                if (response.success) {
                    // Úspěšné vytvoření objednávky - přesměrování na stránku pro dokončení objednávky
                    window.location.href = response.data.redirect_url;
                } else {
                    // Chyba při vytvoření objednávky
                    alert(response.data.message);
                    $('#confirm-order').prop('disabled', false).text('Potvrdit objednávku');
                }
            },
            error: function() {
                alert('Došlo k chybě při zpracování objednávky. Zkuste to prosím znovu.');
                $('#confirm-order').prop('disabled', false).text('Potvrdit objednávku');
            }
        });
    });

    // Vyhledávání klubů
    let searchTimeout;
    
    $('#club-search').on('input', function() {
        const searchTerm = $(this).val();
        
        // Zrušíme předchozí timeout pro omezení počtu požadavků
        clearTimeout(searchTimeout);
        
        // Nastavíme nový timeout
        searchTimeout = setTimeout(function() {
            if (searchTerm.length > 2) {
                searchClubs(searchTerm);
            }
        }, 500);
    });

    // Funkce pro vyhledávání klubů
    function searchClubs(searchTerm) {
        $.ajax({
            type: 'POST',
            url: ajax_object.ajax_url,
            data: {
                action: 'search_clubs',
                nonce: $('#modal_nonce').val(),
                search_term: searchTerm
            },
            success: function(response) {
                if (response.success) {
                    displayClubResults(response.data.clubs);
                }
            }
        });
    }

    // Zobrazení výsledků vyhledávání klubů
    function displayClubResults(clubs) {
        let resultsHTML = '<div class="club-results">';
        
        if (clubs.length > 0) {
            clubs.forEach(function(club) {
                resultsHTML += `<div class="club-item" data-id="${club.id}" data-name="${club.name}">${club.name}</div>`;
            });
        } else {
            resultsHTML += '<div class="no-results">Žádné kluby nenalezeny</div>';
        }
        
        resultsHTML += '</div>';
        
        // Odstraníme předchozí výsledky
        $('.club-results').remove();
        
        // Přidáme nové výsledky
        $('#club-search').after(resultsHTML);
        
        // Přidáme event listenery pro výběr klubu
        $('.club-item').on('click', function() {
            const clubId = $(this).attr('data-id');
            const clubName = $(this).attr('data-name');
            
            $('#club-search').val(clubName);
            $('#selected-club').val(clubId);
            
            // Uložení výběru klubu do registračních dat
            registrationData['club_search'] = clubName;
            registrationData['selected_club'] = clubId;
            registrationData['club_name'] = clubName;
            
            // Aktualizace sessionStorage
            sessionStorage.setItem('registrationData', JSON.stringify(registrationData));
            
            $('.club-results').remove();
        });
    }

    // Načtení uložených dat při opětovném načtení stránky
    function loadSavedData() {
        const savedData = sessionStorage.getItem('registrationData');
        
        if (savedData) {
            registrationData = JSON.parse(savedData);
            
            // Předvyplnění formulářů
            for (const key in registrationData) {
                const input = $(`[name="${key}"]`);
                
                if (input.length > 0) {
                    if (input.attr('type') === 'checkbox') {
                        input.prop('checked', registrationData[key]);
                    } else {
                        input.val(registrationData[key]);
                    }
                }
            }
        }
    }

    // Načteme uložená data při načtení stránky
    loadSavedData();

    // ========================================
    // Přidání nového klubu - Modal funkcionalita
    // ========================================

    // Otevření modálního okna pro přidání nového klubu
    $('#add-new-club').on('click', function(e) {
        e.preventDefault();
        $('#add-club-modal').show();
    });

    // Zavření modálního okna pro přidání klubu
    $('#add-club-modal .close-modal').on('click', function() {
        $('#add-club-modal').hide();
    });

    // Zavření modálního okna kliknutím mimo obsah
    $('#add-club-modal').on('click', function(e) {
        if ($(e.target).is('#add-club-modal')) {
            $('#add-club-modal').hide();
        }
    });

    // Obsluha custom file input - náhled obrázku
    $('.file-input').on('change', function() {
        const file = this.files[0];
        const preview = $(this).closest('.file-input-wrapper').find('.file-input-preview');

        if (file) {
            const reader = new FileReader();

            reader.onload = function(e) {
                preview.html('<img src="' + e.target.result + '" alt="Preview">');
            };

            reader.readAsDataURL(file);
        } else {
            preview.html('');
        }
    });

    // Kliknutí na custom file input
    $('.file-input-text').on('click', function() {
        $(this).closest('.file-input-wrapper').find('.file-input').click();
    });

    // Zpracování formuláře pro přidání nového klubu
    $('#add-club-form').on('submit', function(e) {
        e.preventDefault();

        const clubTitle = $('#club_title').val();
        const clubAbbr = $('#club_abbr').val();
        const clubLeague = $('#club_league').val();
        const clubLogoFile = $('#club_logo')[0].files[0];

        // Validace
        if (!clubTitle) {
            alert('Prosím vyplňte název klubu.');
            return;
        }

        // Vytvoření FormData pro odeslání souboru
        const formData = new FormData();
        formData.append('action', 'create_new_club');
        formData.append('nonce', $('#modal_nonce').val());
        formData.append('club_title', clubTitle);
        formData.append('club_abbr', clubAbbr);
        formData.append('club_league', clubLeague);

        if (clubLogoFile) {
            formData.append('club_logo', clubLogoFile);
        }

        // Odeslání AJAX požadavku pro vytvoření nového klubu
        $.ajax({
            type: 'POST',
            url: ajax_object.ajax_url,
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('#add-club-form button[type="submit"]').prop('disabled', true).text('Ukládání...');
            },
            success: function(response) {
                if (response.success) {
                    // Skrytí modálu
                    $('#add-club-modal').hide();

                    // Nastavení vytvořeného klubu jako vybraného
                    $('#club-search').val(clubTitle);
                    $('#selected-club').val(response.data.club_id);

                    // Uložení do registračních dat
                    registrationData['club_search'] = clubTitle;
                    registrationData['selected_club'] = response.data.club_id;
                    registrationData['club_name'] = clubTitle;

                    // Aktualizace sessionStorage
                    sessionStorage.setItem('registrationData', JSON.stringify(registrationData));

                    // Reset formuláře
                    $('#add-club-form')[0].reset();
                    $('.file-input-preview').html('');
                    $('#add-club-form button[type="submit"]').prop('disabled', false).text('Přidat klub');

                    alert('Klub byl úspěšně přidán a vybrán.');
                } else if (response.data && response.data.similar_clubs) {
                    // Found similar clubs - show suggestions
                    var html = '<div class="similar-clubs-warning">';
                    html += '<p><strong>' + response.data.message + '</strong></p>';
                    html += '<ul class="similar-clubs-list">';
                    response.data.similar_clubs.forEach(function(club) {
                        html += '<li>';
                        if (club.logo) {
                            html += '<img src="' + club.logo + '" alt="" style="width:20px;height:20px;object-fit:contain;margin-right:8px;vertical-align:middle;">';
                        }
                        html += '<span>' + club.name + '</span> ';
                        html += '<button type="button" class="btn btn-primary btn-sm select-existing-club" data-id="' + club.id + '" data-name="' + club.name + '">Vybrat</button>';
                        html += '</li>';
                    });
                    html += '</ul>';
                    html += '<button type="button" class="btn btn-secondary btn-sm force-create-club">Přesto přidat nový klub</button>';
                    html += '</div>';
                    $('#add-club-form .similar-clubs-warning').remove();
                    $('#add-club-form').prepend(html);
                    $('#add-club-form button[type="submit"]').prop('disabled', false).text('Přidat klub');

                    // Select existing club handler
                    $('.select-existing-club').on('click', function(e) {
                        e.preventDefault();
                        var id = $(this).data('id');
                        var name = $(this).data('name');
                        $('#add-club-modal').hide();
                        $('.similar-clubs-warning').remove();
                        $('#club-search').val(name);
                        $('#selected-club').val(id);
                        registrationData['club_search'] = name;
                        registrationData['selected_club'] = id;
                        registrationData['club_name'] = name;
                        sessionStorage.setItem('registrationData', JSON.stringify(registrationData));
                        $('#add-club-form')[0].reset();
                    });

                    // Force create handler
                    $('.force-create-club').on('click', function() {
                        $('.similar-clubs-warning').remove();
                        formData.append('force_create', '1');
                        $.ajax({
                            type: 'POST',
                            url: ajax_object.ajax_url,
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function(resp) {
                                if (resp.success) {
                                    $('#add-club-modal').hide();
                                    $('#club-search').val(clubTitle);
                                    $('#selected-club').val(resp.data.club_id);
                                    registrationData['club_search'] = clubTitle;
                                    registrationData['selected_club'] = resp.data.club_id;
                                    registrationData['club_name'] = clubTitle;
                                    sessionStorage.setItem('registrationData', JSON.stringify(registrationData));
                                    $('#add-club-form')[0].reset();
                                    alert('Klub byl přidán ke schválení.');
                                }
                            }
                        });
                    });
                } else {
                    alert(response.data.message || 'Došlo k chybě při vytváření klubu.');
                    $('#add-club-form button[type="submit"]').prop('disabled', false).text('Přidat klub');
                }
            },
            error: function() {
                alert('Došlo k chybě při komunikaci se serverem.');
                $('#add-club-form button[type="submit"]').prop('disabled', false).text('Přidat klub');
            }
        });
    });
});