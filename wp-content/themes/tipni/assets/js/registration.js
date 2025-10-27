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
});