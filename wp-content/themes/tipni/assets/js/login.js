jQuery(document).ready(function($) {
    // Přidat konzolový log pro debugování
    console.log('Login JS loaded');
    
    $('#ajax-login-form').on('submit', function(e) {
        e.preventDefault();
        console.log('Form submitted');
        
        // Skrýt chybové hlášky a zobrazit loading
        $('.login-error').hide();
        $('.login-loading').show();
        
        // Získat data z formuláře
        var formData = {
            'action': 'tipnijinak_ajax_login',
            'user_login': $('#user_login').val(),
            'user_pass': $('#user_pass').val(),
            'security': $('input[name="security"]').val(), // Správný selektor pro nonce pole
            'redirect_to': $('input[name="redirect_to"]').val()
        };
        
        // Debug pro AJAX data
        console.log('AJAX URL:', tipnijinak_ajax.ajaxurl);
        console.log('Form data:', formData);
        
        // Poslat Ajax požadavek
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: tipnijinak_ajax.ajaxurl,
            data: formData,
            success: function(response) {
                console.log('AJAX response:', response);
                $('.login-loading').hide();
                
                if (response.success) {
                    // Přesměrovat na určenou URL
                  
                    window.location.href = response.data.redirect;
                } else {
                    console.log(response.data);
                    // Zobrazit chybovou hlášku včetně HTML kódu
                    $('.login-error').html(response.data.message).show();
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX error:', status, error);
                console.log('Response text:', xhr.responseText);
                console.log('HTTP status:', xhr.status);
                $('.login-loading').hide();

                // Kontrola na expirovaný nonce (WordPress vrací "-1")
                if (xhr.responseText === '-1' || xhr.status === 403) {
                    $('.login-error').html('<strong>Chyba:</strong> Stránka je zastaralá. Obnovuji stránku...').show();
                    // Automaticky reload po 2 sekundách
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    $('.login-error').html('<strong>' + tipnijinak_ajax.error_text + ':</strong> ' + tipnijinak_ajax.server_error).show();
                }
            }
        });
    });
});