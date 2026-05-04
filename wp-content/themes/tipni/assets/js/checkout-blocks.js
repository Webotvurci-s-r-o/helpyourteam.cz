/**
 * Tipni Jinak - WooCommerce Blocks Checkout Integration
 * Version 7.0 - Login/Register + Club selection (conditional)
 *             - Přesunuto NAD kontaktní informace
 *             - Odstraněn nadpis "Další údaje"
 */
(function() {
    'use strict';

    console.log('[TIPNIJINAK] Checkout script loaded v7');

    let retryCount = 0;
    const maxRetries = 20;

    // Wait for checkout to be ready
    const waitForCheckout = () => {
        retryCount++;
        const contactFields = document.querySelector('fieldset#contact-fields, fieldset.wc-block-checkout__contact-fields');

        if (!contactFields) {
            if (retryCount >= maxRetries) {
                console.error('[TIPNIJINAK] Max retries reached. Contact fields not found.');
                return;
            }
            console.log('[TIPNIJINAK] Waiting for contact fields block... (attempt ' + retryCount + ')');
            setTimeout(waitForCheckout, 300);
            return;
        }

        console.log('[TIPNIJINAK] Contact fields found, initializing...');
        setTimeout(initFields, 100);
    };

    const initFields = () => {
        const config = window.tipnijinakCheckout || {};
        const i18n = config.i18n || {};
        const isLoggedIn = config.isUserLoggedIn || false;
        const userHasClub = config.userHasClub || false;

        console.log('[TIPNIJINAK] Config:', { isLoggedIn, userHasClub, clubName: config.userClubName });

        // If user is logged in AND has club, nothing to show
        if (isLoggedIn && userHasClub) {
            console.log('[TIPNIJINAK] User logged in with club, nothing to display');
            return;
        }

        // Check if already rendered
        if (document.querySelector('.tipnijinak-checkout-fields')) {
            console.log('[TIPNIJINAK] Already rendered, skipping');
            return;
        }

        // Find contact block
        let contactBlock = document.querySelector('fieldset#contact-fields');
        if (!contactBlock) {
            contactBlock = document.querySelector('fieldset.wc-block-checkout__contact-fields');
        }
        if (!contactBlock) {
            console.error('[TIPNIJINAK] Contact fields block not found in initFields!');
            return;
        }

        console.log('[TIPNIJINAK] Found contact block:', contactBlock.id);

        // Create leagues options
        const leaguesOptions = (config.leagues || []).map(l =>
            `<option value="${l.id}">${l.name}</option>`
        ).join('');

        // Build HTML based on user state
        let accountSectionHTML = '';
        let clubSectionHTML = '';

        // Account section - only for non-logged-in users
        if (!isLoggedIn) {
            accountSectionHTML = `
                <div class="tipnijinak-account-section">
                    <h4>${i18n.accountInfo || 'Přihlášení nebo registrace'}</h4>
                    <p class="tipnijinak-account-desc">
                        Pro účast v tipovacích soutěžích potřebujete účet.
                        <a href="${config.loginUrl || '#'}" class="tipnijinak-login-link">${i18n.alreadyHaveAccount || 'Máte již účet?'} <strong>${i18n.loginLink || 'Přihlaste se'}</strong></a>
                    </p>

                    <div class="tipnijinak-form-row">
                        <div class="tipnijinak-form-group">
                            <label for="tipnijinak-username">${i18n.username || 'Uživatelské jméno'}</label>
                            <input type="text" id="tipnijinak-username" name="tipnijinak_username" placeholder="${i18n.usernamePlaceholder || 'Vaše uživatelské jméno'}" required>
                            <span class="tipnijinak-field-error" id="tipnijinak-username-error"></span>
                        </div>
                    </div>

                    <div class="tipnijinak-form-row">
                        <div class="tipnijinak-form-group">
                            <label for="tipnijinak-password">${i18n.password || 'Heslo'}</label>
                            <input type="password" id="tipnijinak-password" name="tipnijinak_password" placeholder="${i18n.passwordPlaceholder || 'Zadejte heslo (min. 8 znaků)'}" required>
                            <span class="tipnijinak-field-error" id="tipnijinak-password-error"></span>
                        </div>
                    </div>
                </div>
            `;
        }

        // Club section - for non-logged-in OR logged-in without club
        if (!isLoggedIn || !userHasClub) {
            clubSectionHTML = `
                <div class="tipnijinak-club-field">
                    <label>${i18n.selectClub || 'Vyberte svůj oblíbený klub'}</label>
                    <p class="club-note">${i18n.clubNote || 'Váš oblíbený klub z nižší soutěže (3.liga max.)'}</p>
                    <div class="club-search-container">
                        <input type="search" id="tipnijinak-club-search" placeholder="${i18n.searchClub || 'Vyhledat klub'}">
                        <a href="#" id="tipnijinak-add-club-btn" class="btn btn-primary btn-sm">${i18n.addNewClub || 'Přidat klub'}</a>
                    </div>
                    <div class="club-results" id="tipnijinak-club-results" style="display: none;"></div>
                    <input type="hidden" id="tipnijinak-selected-club" name="tipnijinak_selected_club" value="">
                    <input type="hidden" id="tipnijinak-club-name" name="tipnijinak_club_name" value="">
                </div>
            `;
        }

        // Build complete HTML (bez nadpisu "Další údaje")
        const fieldsHTML = `
            <div class="tipnijinak-checkout-fields">
                ${accountSectionHTML}
                ${clubSectionHTML}
            </div>

            <!-- Modal pro přidání nového klubu -->
            <div id="tipnijinak-add-club-modal" class="modal" style="display: none;">
                <div class="modal-content">
                    <span class="close-modal" id="tipnijinak-close-modal">&times;</span>
                    <h3>${i18n.addNewClub || 'Přidat nový klub'}</h3>
                    <form id="tipnijinak-add-club-form" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="tipnijinak-club-title">${i18n.clubTitle || 'Název klubu'}</label>
                            <input type="text" id="tipnijinak-club-title" name="club_title" required>
                        </div>
                        <div class="form-group">
                            <label for="tipnijinak-club-logo">${i18n.clubLogo || 'Logo klubu'}</label>
                            <div class="file-input-wrapper">
                                <input type="file" id="tipnijinak-club-logo" name="club_logo" accept="image/*" class="file-input">
                                <div class="file-input-custom">
                                    <span class="file-input-text">${i18n.selectFile || 'Vybrat soubor'}</span>
                                    <div class="file-input-preview"></div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="tipnijinak-club-abbr">${i18n.clubAbbr || 'Zkratka klubu'}</label>
                            <input type="text" id="tipnijinak-club-abbr" name="club_abbr">
                        </div>
                        <div class="form-group">
                            <label for="tipnijinak-club-league">${i18n.league || 'Liga'}</label>
                            <select id="tipnijinak-club-league" name="club_league">
                                ${leaguesOptions}
                            </select>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">${i18n.addNewClub || 'Přidat klub'}</button>
                        </div>
                    </form>
                </div>
            </div>
        `;

        // Insert BEFORE contact block (nad kontaktní informace)
        const wrapper = document.createElement('div');
        wrapper.innerHTML = fieldsHTML;
        contactBlock.before(wrapper);

        console.log('[TIPNIJINAK] Fields inserted into DOM');

        // Setup events
        setupEventListeners(config, i18n, isLoggedIn);
        setupFormValidation(config, i18n, isLoggedIn);
    };

    const setupEventListeners = (config, i18n, isLoggedIn) => {
        let searchTimeout = null;

        // Club search
        const searchInput = document.getElementById('tipnijinak-club-search');
        const resultsDiv = document.getElementById('tipnijinak-club-results');
        const selectedClubInput = document.getElementById('tipnijinak-selected-club');
        const clubNameInput = document.getElementById('tipnijinak-club-name');

        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                const term = e.target.value;

                if (term.length < 2) {
                    resultsDiv.style.display = 'none';
                    return;
                }

                searchTimeout = setTimeout(() => {
                    fetch(config.ajaxUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({
                            action: 'tipnijinak_search_clubs_checkout',
                            nonce: config.nonce,
                            search_term: term,
                        }),
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success && data.data.clubs.length > 0) {
                            resultsDiv.innerHTML = data.data.clubs.map(club =>
                                `<div class="club-item" data-id="${club.id}" data-name="${club.name}">${club.name}</div>`
                            ).join('');
                            resultsDiv.style.display = 'block';

                            resultsDiv.querySelectorAll('.club-item').forEach(item => {
                                item.addEventListener('click', () => {
                                    selectedClubInput.value = item.dataset.id;
                                    clubNameInput.value = item.dataset.name;
                                    searchInput.value = item.dataset.name;
                                    resultsDiv.style.display = 'none';
                                    console.log('[TIPNIJINAK] Club selected:', item.dataset.name);
                                });
                            });
                        } else {
                            resultsDiv.innerHTML = `<div class="no-results">${i18n.noClubsFound || 'Žádné kluby nenalezeny'}</div>`;
                            resultsDiv.style.display = 'block';
                        }
                    })
                    .catch(err => console.error('[TIPNIJINAK] Search error:', err));
                }, 500);
            });
        }

        // Add club modal
        const addClubBtn = document.getElementById('tipnijinak-add-club-btn');
        const modal = document.getElementById('tipnijinak-add-club-modal');
        const closeModalBtn = document.getElementById('tipnijinak-close-modal');
        const addClubForm = document.getElementById('tipnijinak-add-club-form');
        const fileInput = document.getElementById('tipnijinak-club-logo');
        const fileInputText = modal ? modal.querySelector('.file-input-text') : null;
        const fileInputPreview = modal ? modal.querySelector('.file-input-preview') : null;

        if (addClubBtn && modal) {
            addClubBtn.addEventListener('click', (e) => {
                e.preventDefault();
                modal.style.display = 'block';
            });

            closeModalBtn?.addEventListener('click', () => modal.style.display = 'none');

            modal.addEventListener('click', (e) => {
                if (e.target === modal) modal.style.display = 'none';
            });

            if (fileInput && fileInputPreview) {
                fileInput.addEventListener('change', function() {
                    const file = this.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            fileInputPreview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-width: 100px; max-height: 100px;">`;
                        };
                        reader.readAsDataURL(file);
                    } else {
                        fileInputPreview.innerHTML = '';
                    }
                });

                fileInputText?.addEventListener('click', () => fileInput.click());
            }

            addClubForm?.addEventListener('submit', (e) => {
                e.preventDefault();

                const clubTitle = document.getElementById('tipnijinak-club-title').value;
                const clubAbbr = document.getElementById('tipnijinak-club-abbr').value;
                const clubLeague = document.getElementById('tipnijinak-club-league').value;
                const clubLogoFile = document.getElementById('tipnijinak-club-logo').files[0];

                if (!clubTitle) {
                    alert('Prosím vyplňte název klubu.');
                    return;
                }

                const formData = new FormData();
                formData.append('action', 'tipnijinak_create_club_checkout');
                formData.append('nonce', config.nonce);
                formData.append('club_title', clubTitle);
                formData.append('club_abbr', clubAbbr);
                formData.append('club_league', clubLeague);
                if (clubLogoFile) formData.append('club_logo', clubLogoFile);

                const submitBtn = addClubForm.querySelector('button[type="submit"]');
                const originalText = submitBtn.textContent;
                submitBtn.disabled = true;
                submitBtn.textContent = i18n.saving || 'Ukládání...';

                fetch(config.ajaxUrl, { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(data => {
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;

                        if (data.success) {
                            selectedClubInput.value = data.data.club_id;
                            clubNameInput.value = clubTitle;
                            searchInput.value = clubTitle;
                            modal.style.display = 'none';
                            addClubForm.reset();
                            fileInputPreview && (fileInputPreview.innerHTML = '');
                            alert(i18n.clubAdded || 'Klub byl úspěšně přidán.');
                        } else {
                            alert(data.data?.message || 'Chyba při vytváření klubu.');
                        }
                    })
                    .catch(err => {
                        console.error('[TIPNIJINAK] Create club error:', err);
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                        alert('Chyba při komunikaci se serverem.');
                    });
            });
        }

        console.log('[TIPNIJINAK] Event listeners set up');
    };

    const setupFormValidation = (config, i18n, isLoggedIn) => {
        // Validation function
        const validateFields = () => {
            let isValid = true;

            // Clear previous errors
            document.querySelectorAll('.tipnijinak-field-error').forEach(el => el.textContent = '');
            document.querySelectorAll('.tipnijinak-form-group input').forEach(el => el.classList.remove('has-error'));

            // Validate username/password for non-logged-in users
            if (!isLoggedIn) {
                const usernameInput = document.getElementById('tipnijinak-username');
                const passwordInput = document.getElementById('tipnijinak-password');

                if (usernameInput && !usernameInput.value.trim()) {
                    document.getElementById('tipnijinak-username-error').textContent = i18n.usernameRequired || 'Uživatelské jméno je povinné.';
                    usernameInput.classList.add('has-error');
                    isValid = false;
                }

                if (passwordInput) {
                    if (!passwordInput.value) {
                        document.getElementById('tipnijinak-password-error').textContent = i18n.passwordRequired || 'Heslo je povinné.';
                        passwordInput.classList.add('has-error');
                        isValid = false;
                    } else if (passwordInput.value.length < 8) {
                        document.getElementById('tipnijinak-password-error').textContent = i18n.passwordTooShort || 'Heslo musí mít alespoň 8 znaků.';
                        passwordInput.classList.add('has-error');
                        isValid = false;
                    }
                }
            }

            return isValid;
        };

        // Save data function
        const saveDataToSession = () => {
            const checkoutData = {
                action: 'tipnijinak_save_checkout_data',
                nonce: config.nonce,
                selected_club: document.getElementById('tipnijinak-selected-club')?.value || '',
                club_name: document.getElementById('tipnijinak-club-name')?.value || '',
                terms_agreement: 'true',
                marketing_agreement: 'false',
            };

            if (!isLoggedIn) {
                checkoutData.username = document.getElementById('tipnijinak-username')?.value || '';
                checkoutData.password = document.getElementById('tipnijinak-password')?.value || '';
            }

            const xhr = new XMLHttpRequest();
            xhr.open('POST', config.ajaxUrl, false);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.send(new URLSearchParams(checkoutData).toString());
            console.log('[TIPNIJINAK] Data saved to session');
        };

        // Watch for place order button (may be rendered later by React)
        const attachValidation = () => {
            const placeOrderBtn = document.querySelector('.wc-block-components-checkout-place-order-button');

            if (!placeOrderBtn) {
                setTimeout(attachValidation, 500);
                return;
            }

            // Prevent duplicate listeners
            if (placeOrderBtn.dataset.tipnijinakValidation) return;
            placeOrderBtn.dataset.tipnijinakValidation = 'true';

            placeOrderBtn.addEventListener('click', (e) => {
                console.log('[TIPNIJINAK] Place order clicked, validating...');

                if (!validateFields()) {
                    console.log('[TIPNIJINAK] Validation failed');
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();

                    const firstError = document.querySelector('.tipnijinak-field-error:not(:empty)');
                    if (firstError) {
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    return false;
                }

                console.log('[TIPNIJINAK] Validation passed, saving data...');
                saveDataToSession();
            }, true);

            console.log('[TIPNIJINAK] Validation attached to place order button');
        };

        attachValidation();

        // Also use MutationObserver for dynamic button changes
        const observer = new MutationObserver(() => {
            const btn = document.querySelector('.wc-block-components-checkout-place-order-button');
            if (btn && !btn.dataset.tipnijinakValidation) {
                attachValidation();
            }
        });

        const checkoutForm = document.querySelector('.wc-block-checkout__form');
        if (checkoutForm) {
            observer.observe(checkoutForm, { childList: true, subtree: true });
        }

        console.log('[TIPNIJINAK] Form validation set up');
    };

    // Initialize
    const startInit = () => {
        waitForCheckout();

        // MutationObserver fallback
        const observer = new MutationObserver((mutations, obs) => {
            const contactFields = document.querySelector('fieldset#contact-fields, fieldset.wc-block-checkout__contact-fields');
            if (contactFields && !document.querySelector('.tipnijinak-checkout-fields')) {
                console.log('[TIPNIJINAK] MutationObserver triggered init');
                obs.disconnect();
                initFields();
            }
        });

        observer.observe(document.body, { childList: true, subtree: true });
        setTimeout(() => observer.disconnect(), 10000);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', startInit);
    } else {
        startInit();
    }

    console.log('[TIPNIJINAK] Initialization scheduled v7');

})();
