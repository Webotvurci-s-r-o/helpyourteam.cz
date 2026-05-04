/**
 * Checkout custom fields - Tipni Jinak
 */
jQuery(document).ready(function($) {

    // ========================================
    // Vyhledávání klubů
    // ========================================

    let searchTimeout;

    $('#tipnijinak_club_search').on('input', function() {
        const searchTerm = $(this).val();

        // Zrušíme předchozí timeout
        clearTimeout(searchTimeout);

        // Pokud je méně než 2 znaky, skryjeme výsledky
        if (searchTerm.length < 2) {
            $('.club-results').remove();
            return;
        }

        // Nastavíme nový timeout pro omezení požadavků
        searchTimeout = setTimeout(function() {
            searchClubs(searchTerm);
        }, 500);
    });

    // Funkce pro vyhledávání klubů
    function searchClubs(searchTerm) {
        $.ajax({
            type: 'POST',
            url: tipnijinak_checkout.ajax_url,
            data: {
                action: 'tipnijinak_search_clubs_checkout',
                nonce: tipnijinak_checkout.nonce,
                search_term: searchTerm
            },
            success: function(response) {
                if (response.success) {
                    displayClubResults(response.data.clubs);
                }
            }
        });
    }

    // Zobrazení výsledků vyhledávání
    function displayClubResults(clubs) {
        let resultsHTML = '<div class="club-results">';

        if (clubs.length > 0) {
            clubs.forEach(function(club) {
                resultsHTML += '<div class="club-item" data-id="' + club.id + '" data-name="' + escapeHtml(club.name) + '">' + escapeHtml(club.name) + '</div>';
            });
        } else {
            resultsHTML += '<div class="no-results">Žádné kluby nenalezeny</div>';
        }

        resultsHTML += '</div>';

        // Odstraníme předchozí výsledky
        $('.club-results').remove();

        // Přidáme nové výsledky
        $('.club-results-container').html(resultsHTML);

        // Přidáme event listenery pro výběr klubu
        $('.club-item').on('click', function() {
            selectClub($(this).data('id'), $(this).data('name'));
        });
    }

    // Výběr klubu
    function selectClub(clubId, clubName) {
        $('#tipnijinak_club_search').val(clubName);
        $('#tipnijinak_selected_club').val(clubId);
        $('#tipnijinak_club_name').val(clubName);

        // Odstraníme výsledky
        $('.club-results').remove();
    }

    // Escape HTML pro bezpečné zobrazení
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Skrytí výsledků při kliknutí mimo
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.tipnijinak-club-field').length) {
            $('.club-results').remove();
        }
    });

    // ========================================
    // Modal pro přidání nového klubu
    // ========================================

    // Otevření modalu
    $('#tipnijinak-add-new-club').on('click', function(e) {
        e.preventDefault();
        $('#tipnijinak-add-club-modal').show();
    });

    // Zavření modalu
    $('.tipnijinak-close-modal').on('click', function() {
        $('#tipnijinak-add-club-modal').hide();
    });

    // Zavření modalu kliknutím mimo
    $('#tipnijinak-add-club-modal').on('click', function(e) {
        if ($(e.target).is('#tipnijinak-add-club-modal')) {
            $(this).hide();
        }
    });

    // Zavření modalu klávesou Escape
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            $('#tipnijinak-add-club-modal').hide();
        }
    });

    // Náhled obrázku
    $('#tipnijinak_new_club_logo').on('change', function() {
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

    // Kliknutí na text file inputu
    $('.tipnijinak-modal-content .file-input-text').on('click', function() {
        $(this).closest('.file-input-wrapper').find('.file-input').click();
    });

    // Odeslání formuláře pro vytvoření klubu
    $('#tipnijinak-add-club-form').on('submit', function(e) {
        e.preventDefault();

        const clubTitle = $('#tipnijinak_new_club_title').val();
        const clubAbbr = $('#tipnijinak_new_club_abbr').val();
        const clubLeague = $('#tipnijinak_new_club_league').val();
        const clubLogoFile = $('#tipnijinak_new_club_logo')[0].files[0];

        // Validace
        if (!clubTitle) {
            alert('Prosím vyplňte název klubu.');
            return;
        }

        // Vytvoření FormData
        const formData = new FormData();
        formData.append('action', 'tipnijinak_create_club_checkout');
        formData.append('nonce', tipnijinak_checkout.nonce);
        formData.append('club_title', clubTitle);
        formData.append('club_abbr', clubAbbr);
        formData.append('club_league', clubLeague);

        if (clubLogoFile) {
            formData.append('club_logo', clubLogoFile);
        }

        // Odeslání AJAX požadavku
        $.ajax({
            type: 'POST',
            url: tipnijinak_checkout.ajax_url,
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('#tipnijinak-add-club-form button[type="submit"]').prop('disabled', true).text('Ukládání...');
            },
            success: function(response) {
                if (response.success) {
                    // Skrytí modalu
                    $('#tipnijinak-add-club-modal').hide();

                    // Nastavení vytvořeného klubu jako vybraného
                    selectClub(response.data.club_id, clubTitle);

                    // Reset formuláře
                    $('#tipnijinak-add-club-form')[0].reset();
                    $('.tipnijinak-modal-content .file-input-preview').html('');
                    $('#tipnijinak-add-club-form button[type="submit"]').prop('disabled', false).text('Přidat klub');

                    alert('Klub byl úspěšně přidán a vybrán.');
                } else {
                    alert(response.data.message || 'Došlo k chybě při vytváření klubu.');
                    $('#tipnijinak-add-club-form button[type="submit"]').prop('disabled', false).text('Přidat klub');
                }
            },
            error: function() {
                alert('Došlo k chybě při komunikaci se serverem.');
                $('#tipnijinak-add-club-form button[type="submit"]').prop('disabled', false).text('Přidat klub');
            }
        });
    });
});
