jQuery(document).ready(function($) {
    // Editace profilových údajů
    $('.edit-profile').on('click', function(e) {
        e.preventDefault();
        
        const field = $(this).data('field');
        const $row = $(this).closest('.table-row');
        const $valueContainer = $row.find('.table-row__value');
        const currentValue = $valueContainer.text().trim();
        
        // Vytvořit a zobrazit formulář pro úpravu
        if (field === 'terms_agreement' || field === 'marketing_agreement') {
            // Checkbox pro souhlas
            const isChecked = $valueContainer.find('img').length > 0;
            const $form = $(`
                <form class="edit-field-form">
                    <input type="hidden" name="field" value="${field}">
                    <label>
                        <input type="checkbox" name="value" ${isChecked ? 'checked' : ''}>
                        <span>Souhlasím</span>
                    </label>
                    <div class="btn-holder">
                        <button type="button" class="btn btn-secondary cancel-edit">Zrušit</button>
                        <button type="submit" class="btn btn-primary">Uložit</button>
                    </div>
                </form>
            `);
            
            $valueContainer.html($form);
        } else if (field === 'address') {
            // Adresní pole - rozdělit na jednotlivé části
            const addressParts = currentValue.split(',').map(part => part.trim());
            const address = addressParts[0] || '';
            const city = addressParts[1] || '';
            const psc = addressParts[2] || '';
            
            const $form = $(`
                <form class="edit-field-form">
                    <input type="hidden" name="field" value="${field}">
                    <div class="address-fields">
                        <input type="text" name="address" placeholder="Ulice" value="${address}">
                        <input type="text" name="city" placeholder="Město" value="${city}">
                        <input type="text" name="psc" placeholder="PSČ" value="${psc}">
                    </div>
                    <div class="btn-holder">
                        <button type="button" class="btn btn-secondary cancel-edit">Zrušit</button>
                        <button type="submit" class="btn btn-primary">Uložit</button>
                    </div>
                </form>
            `);
            
            $valueContainer.html($form);
        } else {
            // Standardní textové pole
            const $form = $(`
                <form class="edit-field-form">
                    <input type="hidden" name="field" value="${field}">
                    <input type="${field === 'email' ? 'email' : 'text'}" name="value" value="${currentValue}">
                    <div class="btn-holder">
                        <button type="button" class="btn btn-secondary cancel-edit">Zrušit</button>
                        <button type="submit" class="btn btn-primary">Uložit</button>
                    </div>
                </form>
            `);
            
            $valueContainer.html($form);
        }
        
        // Skrýt tlačítko pro editaci
        $(this).hide();
    });
    
    // Zrušení editace
    $(document).on('click', '.cancel-edit', function() {
        const $row = $(this).closest('.table-row');
        
        // Obnovit původní zobrazení
        location.reload();
    });
    
    // Odeslání formuláře pro úpravu
    $(document).on('submit', '.edit-field-form', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $row = $form.closest('.table-row');
        const field = $form.find('input[name="field"]').val();
        
        let data = {
            action: 'update_user_profile',
            field: field,
            nonce: ajax_object.nonce
        };
        
        // Získání hodnoty
        if (field === 'address') {
            // Adresní pole
            data.address = $form.find('input[name="address"]').val();
            data.city = $form.find('input[name="city"]').val();
            data.psc = $form.find('input[name="psc"]').val();
        } else if (field === 'terms_agreement' || field === 'marketing_agreement') {
            // Checkbox
            data.value = $form.find('input[name="value"]').prop('checked') ? 'yes' : 'no';
        } else {
            // Standardní pole
            data.value = $form.find('input[name="value"]').val();
        }
        
        // Odeslání AJAX požadavku
        $.ajax({
            type: 'POST',
            url: ajax_object.ajax_url,
            data: data,
            beforeSend: function() {
                $form.find('button[type="submit"]').prop('disabled', true).text('Ukládání...');
            },
            success: function(response) {
                if (response.success) {
                    // Aktualizace zobrazení
                    location.reload();
                } else {
                    alert(response.data.message || 'Došlo k chybě při ukládání.');
                    $form.find('button[type="submit"]').prop('disabled', false).text('Uložit');
                }
            },
            error: function() {
                alert('Došlo k chybě při komunikaci se serverem.');
                $form.find('button[type="submit"]').prop('disabled', false).text('Uložit');
            }
        });
    });
    
    // Vyhledávání klubů
    let searchTimeout;
    let clubResults;
    
    // Vyhledávání klubů
    $('#profile-club-search').on('input', function() {
        const searchTerm = $(this).val();
        
        // Zrušíme předchozí timeout pro omezení počtu požadavků
        clearTimeout(searchTimeout);
        
        // Odstraníme předchozí výsledky
        $('.club-results').remove();
        
        // Pokud je pole prázdné, skončíme
        if (searchTerm.length < 3) {
            return;
        }
        
        // Nastavíme nový timeout
        searchTimeout = setTimeout(function() {
            // Odeslání AJAX požadavku pro vyhledání klubů
            $.ajax({
                type: 'POST',
                url: ajax_object.ajax_url,
                data: {
                    action: 'search_clubs',
                    nonce: $('#search_nonce').val(),
                    search_term: searchTerm
                },
                success: function(response) {
                    if (response.success && response.data.clubs.length > 0) {
                        displayClubResults(response.data.clubs);
                    }
                }
            });
        }, 500);
    });
    
    // Zobrazení výsledků vyhledávání klubů
    function displayClubResults(clubs) {
        let resultsHTML = '<div class="club-results">';
        
        clubs.forEach(function(club) {
            let clubHtml = '<div class="club-item" data-id="' + club.id + '" data-name="' + club.name + '">';
            
            if (club.logo) {
                clubHtml += '<img src="' + club.logo + '" class="club-logo-small" alt="' + club.name + '">';
            }
            
            clubHtml += club.name + '</div>';
            
            resultsHTML += clubHtml;
        });
        
        resultsHTML += '</div>';
        
        // Přidáme výsledky pod vyhledávací pole
        $('#profile-club-search').after(resultsHTML);
        
        // Přidáme event listenery pro výběr klubu
        $('.club-item').on('click', function() {
            const clubId = $(this).attr('data-id');
            const clubName = $(this).attr('data-name');
            
            // Nastavíme vybraný klub
            $('#profile-club-search').val(clubName);
            $('#profile-club-id').val(clubId);
            
            // Odstraníme výsledky vyhledávání
            $('.club-results').remove();
            
            // Uložíme vybraný klub
            saveSelectedClub(clubId, clubName);
        });
    };
    
    // Otevření modálního okna pro přidání nového klubu
    $('#add-new-club').on('click', function(e) {
        e.preventDefault();
        $('#add-club-modal').show();
    });
    
    // Zavření modálního okna
    $('.modal .close, .modal').on('click', function(e) {
        if (e.target === this) {
            $('#add-club-modal').hide();
        }
    });
    
    // Zastavení propagace kliknutí uvnitř modálního okna
    $('.modal-content').on('click', function(e) {
        e.stopPropagation();
    });
    
    // Obsluha custom file input
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
        formData.append('nonce', $('#club_nonce').val());
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

                    // Nastavení vytvořeného klubu jako oblíbeného
                    saveSelectedClub(response.data.club_id, clubTitle);
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
                        saveSelectedClub(id, name);
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
                                    saveSelectedClub(resp.data.club_id, clubTitle);
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
    
    // Uložení vybraného klubu
    function saveSelectedClub(clubId, clubName) {
        $.ajax({
            type: 'POST',
            url: ajax_object.ajax_url,
            data: {
                action: 'update_user_club',
                nonce: $('#search_nonce').val(),
                club_id: clubId,
                club_name: clubName
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || 'Došlo k chybě při ukládání klubu.');
                }
            },
            error: function() {
                alert('Došlo k chybě při komunikaci se serverem.');
            }
        });
    }
});