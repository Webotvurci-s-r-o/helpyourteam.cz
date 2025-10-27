jQuery(document).ready(function($) {
    // Funkce pro otevření modálního okna
    function openModal() {
        $('#payment-modal').show();
    }

    // Funkce pro zavření modálního okna
    function closeModal() {
        $('#payment-modal').hide();
    }

    // Zavření modálního okna při kliknutí na křížek
    $('.close-modal').on('click', function() {
        closeModal();
    });

    // Zavření modálního okna při kliknutí mimo obsah
    $(window).on('click', function(event) {
        if ($(event.target).is('#payment-modal')) {
            closeModal();
        }
    });

    // Zavření modálního okna při kliknutí na tlačítko zrušit
    $('#cancel-order').on('click', function() {
        closeModal();
    });

    // Zpracování kliknutí na tlačítko "Koupit"
    $('.buy-product').on('click', function() {
        // Kontrola, zda je uživatel přihlášen
        var isLoggedIn = $('body').hasClass('logged-in');
        
        if (!isLoggedIn) {
            // Pokud není přihlášen, zobrazíme modální okno s výzvou k přihlášení
            openModal();
            return;
        }
        
        var productId = $(this).data('product-id');
        var $product = $(this).closest('.product');
        var productName = $product.find('.product-title').text();
        var productPrice = $product.find('.product-price').text();
        
        // Naplnění modálního okna daty
        $('#modal-product-name').text(productName);
        $('#modal-product-price').text(productPrice);
        
        // Uložení ID produktu pro pozdější použití
        $('#payment-modal').data('product-id', productId);
        
        // Otevření modálního okna
        openModal();
    });

    // Zpracování kliknutí na tlačítko "Potvrdit objednávku"
    $('#confirm-order').on('click', function() {
        var productId = $('#payment-modal').data('product-id');
        var paymentMethod = $('input[name="payment_method"]:checked').val();

        // Zobrazení indikátoru načítání
        $(this).prop('disabled', true).text('Zpracovávám...');
        
        // AJAX požadavek pro vytvoření objednávky
        $.ajax({
            url: wc_add_to_cart_params.ajax_url,
            type: 'POST',
            data: {
                action: 'create_wc_order',
                product_id: productId,
                payment_method: paymentMethod,
                security: wc_add_to_cart_params.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Přesměrování na stránku pokladny nebo děkovnou stránku
                    window.location.href = response.redirect;
                } else {
                    // Zobrazení chyby
                    alert(response.message || 'Nastala chyba při zpracování objednávky.');
                    $('#confirm-order').prop('disabled', false).text('Potvrdit objednávku');
                }
            },
            error: function() {
                alert('Nastala chyba při komunikaci se serverem.');
                $('#confirm-order').prop('disabled', false).text('Potvrdit objednávku');
            }
        });
    });

    // Filtrování produktů
    $('#filter-form').on('submit', function(e) {
        e.preventDefault();
        
        var category = $('#category-filter').val();
        var minPrice = $('#min-price').val();
        var maxPrice = $('#max-price').val();
        
        // Sestavení URL s parametry filtru
        var url = window.location.href.split('?')[0];
        var params = [];
        
        if (category) {
            params.push('category=' + category);
        }
        
        if (minPrice) {
            params.push('min_price=' + minPrice);
        }
        
        if (maxPrice) {
            params.push('max_price=' + maxPrice);
        }
        
        // Přesměrování na URL s parametry filtru
        if (params.length > 0) {
            window.location.href = url + '?' + params.join('&');
        } else {
            window.location.href = url;
        }
    });

    // Reset filtru
    $('#reset-filter').on('click', function() {
        window.location.href = window.location.href.split('?')[0];
    });
});