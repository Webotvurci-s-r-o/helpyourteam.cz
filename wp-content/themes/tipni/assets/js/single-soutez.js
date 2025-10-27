// JavaScript pro interakci s tlačítky tipů a AJAX ukládání
document.addEventListener('DOMContentLoaded', function() {
    // Definice proměnných na začátku pro správný scope
    const matchesRecap = document.querySelector('.matches-recap');
    const matchesLeftElement = document.querySelector('.matches-left');
    
    // Funkce pro aktualizaci počtu zbývajících zápasů
    function updateRemainingMatches() {
        if (!matchesRecap || !matchesLeftElement) return;
        
        const maxTips = tipnijinak_vars.max_tips || 15; // Maximální počet tipů
        const savedTipsCount = tipnijinak_vars.saved_tips_count || 0; // Počet již uložených tipů z databáze
        
        // Získat počet nových tipů v rekapitulaci (pouze ty, které ještě nejsou uložené)
        const newTipsInRecap = matchesRecap.querySelectorAll('.match').length;
        
        // Celkový počet tipů = uložené + nové
        // Pokud máme nějaké tipy v rekapitulaci, pravděpodobně se jedná o již uložené tipy načtené z databáze
        // nebo o nové tipy přidané uživatelem
        const totalTips = Math.max(savedTipsCount, newTipsInRecap);
        
        // Vypočítat zbývající zápasy
        const remainingMatches = Math.max(0, maxTips - totalTips);
        
        // Aktualizovat text
        matchesLeftElement.textContent = `Zbývá: ${remainingMatches} zápasů`;
    }
    
    // Funkce pro aktualizaci rekapitulace tipů v sidebaru
    function updateMatchesRecap(matchId, tipValue) {
        if (!matchesRecap) return;
        
        // Hledat zápas v rekapitulaci
        let matchFound = false;
        const matchElements = matchesRecap.querySelectorAll('.match');
        
        // Zkontrolovat, zda zápas již existuje v rekapitulaci
        matchElements.forEach(function(matchElement) {
            const matchInfo = matchElement.querySelector('.match-info');
            if (matchInfo && matchInfo.getAttribute('data-match-id') === matchId) {
                // Aktualizovat hodnotu tipu
                const oddElement = matchElement.querySelector('.odd');
                if (oddElement) {
                    oddElement.textContent = tipValue;
                }
                matchFound = true;
            }
        });
        
        // Pokud zápas nebyl nalezen v rekapitulaci, přidat ho
        if (!matchFound) {
            // Najít data zápasu v hlavním seznamu
            const originalMatch = document.querySelector(`.match[data-match-id="${matchId}"]`);
            if (originalMatch) {
                // Získat data týmů
                const homeTeamName = originalMatch.querySelector('.home.team .team-name').getAttribute('title');
                const homeTeamAbbr = originalMatch.querySelector('.home.team .team-name').textContent;
                const homeTeamLogo = originalMatch.querySelector('.home.team .logo-holder img')?.src || '';
                
                const awayTeamName = originalMatch.querySelector('.away.team .team-name').getAttribute('title');
                const awayTeamAbbr = originalMatch.querySelector('.away.team .team-name').textContent;
                const awayTeamLogo = originalMatch.querySelector('.away.team .logo-holder img')?.src || '';
                
                // Vytvořit nový element zápasu pro rekapitulaci
                const newMatchElement = document.createElement('div');
                newMatchElement.className = 'match';
                
                let logoHomeHtml = '';
                if (homeTeamLogo) {
                    logoHomeHtml = `<img src="${homeTeamLogo}" alt="${homeTeamName}">`;
                }
                
                let logoAwayHtml = '';
                if (awayTeamLogo) {
                    logoAwayHtml = `<img src="${awayTeamLogo}" alt="${awayTeamName}">`;
                }
                
                newMatchElement.innerHTML = `
                    <div class="match-info" data-match-id="${matchId}">
                        <div class="home team">
                            <div class="team-name" title="${homeTeamName}">${homeTeamAbbr}</div>
                            <div class="logo-holder">${logoHomeHtml}</div>
                        </div>
                        <span>-</span>
                        <div class="away team">
                            <div class="team-name" title="${awayTeamName}">${awayTeamAbbr}</div>
                            <div class="logo-holder">${logoAwayHtml}</div>
                        </div>
                    </div>
                    <div class="odd">${tipValue}</div>
                `;
                
                // Odstranit zprávu "Zatím jste neprovedli žádné tipy", pokud existuje
                const noTipsYet = matchesRecap.querySelector('.no-tips-yet');
                if (noTipsYet) {
                    noTipsYet.remove();
                }
                
                // Přidat nový element do rekapitulace
                matchesRecap.appendChild(newMatchElement);
                
                // Aktualizovat počet zbývajících zápasů
                updateRemainingMatches();
            }
        }
    }
    
    // Inicializovat počítadlo zbývajících zápasů při načtení stránky
    if (matchesRecap && matchesLeftElement) {
        updateRemainingMatches();
    }
    
    // Přepínání mezi hlavními záložkami
    const mainTabSwitchers = document.querySelectorAll('ul.window-switcher:not(.league-title .window-switcher) li');
    const tabWindows = document.querySelectorAll('.window-switcher__windows > div');
    
    mainTabSwitchers.forEach(function(tab) {
        tab.addEventListener('click', function() {
            const dataAttr = this.getAttribute('data');
            if (!dataAttr) return;
            
            // Odstranit aktivní třídu ze všech hlavních záložek
            mainTabSwitchers.forEach(function(t) {
                t.classList.remove('active');
            });
            
            // Odstranit aktivní třídu ze všech oken
            tabWindows.forEach(function(window) {
                window.classList.remove('active');
            });
            
            // Přidat aktivní třídu aktuální záložce
            this.classList.add('active');
            
            // Přidat aktivní třídu odpovídajícímu oknu
            const targetWindow = document.querySelector('.' + dataAttr);
            if (targetWindow) {
                targetWindow.classList.add('active');
            }
            
            // Aktualizovat URL s parametrem tab
            const url = new URL(window.location.href);
            url.searchParams.set('tab', dataAttr);
            history.pushState({}, '', url);
        });
    });
    
    // Přepínání mezi ligami
    const leagueSwitchers = document.querySelectorAll('.league-title .window-switcher li');
    
    if (leagueSwitchers.length > 0) {
        const leagueWindows = document.querySelectorAll('.league-matches');
        
        leagueSwitchers.forEach(function(leagueTab) {
            leagueTab.addEventListener('click', function() {
                const leagueData = this.getAttribute('data');
                if (!leagueData) return;
                
                // Odstranit aktivní třídu ze všech záložek lig
                leagueSwitchers.forEach(function(lt) {
                    lt.classList.remove('active');
                });
                
                // Odstranit aktivní třídu ze všech oken lig
                leagueWindows.forEach(function(lw) {
                    lw.classList.remove('active');
                });
                
                // Přidat aktivní třídu aktuální záložce ligy
                this.classList.add('active');
                
                // Přidat aktivní třídu odpovídajícímu oknu ligy
                const targetLeagueWindow = document.querySelector('.' + leagueData + '.league-matches');
                if (targetLeagueWindow) {
                    targetLeagueWindow.classList.add('active');
                }
                
                // Aktualizovat URL s parametrem liga
                const url = new URL(window.location.href);
                url.searchParams.set('liga', leagueData);
                history.pushState({}, '', url);
            });
        });
    }
    
    // Inicializovat event listenery při načtení stránky
    initRoundNavigation();
    initOddsButtons();
    initLeagueSwitchers();
    
    // AJAX navigace mezi koly
    function initRoundNavigation() {
        const roundPagination = document.querySelector('.round .pagination');
        if (!roundPagination) return;

        // Zachytit kliknutí na navigační tlačítka
        roundPagination.addEventListener('click', function(e) {
            const link = e.target.closest('a');
            if (!link) return;
            
            e.preventDefault();
            
            // Získat URL a extrakt parametru kolo
            const url = new URL(link.href);
            const roundId = url.searchParams.get('kolo');
            
            if (!roundId) return;
            
            // Zakázat navigaci během načítání
            const prevBtn = roundPagination.querySelector('.pagination-prev');
            const nextBtn = roundPagination.querySelector('.pagination-next');
            if (prevBtn) prevBtn.style.pointerEvents = 'none';
            if (nextBtn) nextBtn.style.pointerEvents = 'none';
            
            // Zobrazit indikátor načítání
            const guessingContent = document.querySelector('.guessing .two-columns');
            if (guessingContent) {
                guessingContent.style.opacity = '0.5';
            }
            
            // AJAX požadavek pro načtení dat kola
            const formData = new FormData();
            formData.append('action', 'tipnijinak_get_round_content');
            formData.append('round_id', roundId);
            formData.append('competition_id', tipnijinak_vars.competition_id);
            formData.append('nonce', tipnijinak_vars.nonce);
            
            fetch(tipnijinak_vars.ajax_url, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.html) {
                    // Nahradit obsah
                    const guessingTab = document.querySelector('.guessing');
                    if (guessingTab) {
                        guessingTab.innerHTML = data.data.html;
                        
                        // Aktualizovat URL bez reloadu
                        url.searchParams.set('tab', 'guessing');
                        history.pushState({}, '', url);
                        
                        // Aktualizovat round_id a saved_tips_count v tipnijinak_vars
                        tipnijinak_vars.round_id = parseInt(roundId);
                        // Resetovat počet uložených tipů pro nové kolo
                        tipnijinak_vars.saved_tips_count = data.data.saved_tips_count || 0;
                        
                        // Reinicializovat event listenery
                        initRoundNavigation();
                        initOddsButtons();
                        initLeagueSwitchers();
                        initSubmitButton();
                        
                        // Aktualizovat počítadlo zbývajících zápasů
                        updateRemainingMatches();
                    }
                } else {
                    console.error('Error loading round content:', data.data?.message || 'Unknown error');
                }
            })
            .catch(error => {
                console.error('AJAX error:', error);
            })
            .finally(() => {
                // Obnovit navigaci
                if (prevBtn) prevBtn.style.pointerEvents = '';
                if (nextBtn) nextBtn.style.pointerEvents = '';
                if (guessingContent) {
                    guessingContent.style.opacity = '';
                }
            });
        });
    }
    
    // Funkce pro kontrolu, zda je zápas již začal nebo skončil
    function isMatchExpired(matchElement) {
        const matchDate = matchElement.getAttribute('data-match-date');
        const matchTime = matchElement.getAttribute('data-match-time');
        
        if (!matchDate || !matchTime) {
            return false; // Pokud nemáme data, povolit tipování
        }
        
        // Převést datum a čas zápasu na timestamp
        // Formát: dd.mm.yyyy a hh:mm
        const dateParts = matchDate.split('.');
        const timeParts = matchTime.split(':');
        
        if (dateParts.length !== 3 || timeParts.length !== 2) {
            return false; // Neplatný formát, povolit tipování
        }
        
        const day = parseInt(dateParts[0]);
        const month = parseInt(dateParts[1]) - 1; // Měsíce jsou 0-indexované
        const year = parseInt(dateParts[2]);
        const hours = parseInt(timeParts[0]);
        const minutes = parseInt(timeParts[1]);
        
        const matchDateTime = new Date(year, month, day, hours, minutes);
        const now = new Date();
        
        return now >= matchDateTime; // Vrátit true, pokud je aktuální čas >= času zápasu
    }
    
    // Funkce pro inicializaci odds tlačítek (extrahovaná pro znovupoužití)
    function initOddsButtons() {
        const oddsButtons = document.querySelectorAll('.odds-button');
        
        // Nejprve zkontrolovat všechny zápasy a zakázat tlačítka pro expirované zápasy
        document.querySelectorAll('.match').forEach(function(matchElement) {
            if (isMatchExpired(matchElement)) {
                const buttons = matchElement.querySelectorAll('.odds-button');
                buttons.forEach(function(btn) {
                    btn.setAttribute('data-expired', 'true');
                });
                
     
            }
        });
        
        oddsButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                // Zkontrolovat, zda je tlačítko expirované
                if (this.getAttribute('data-expired') === 'true') {
                    const feedbackEl = document.querySelector('.tips-feedback');
                    if (feedbackEl) {
                        feedbackEl.textContent = 'Zápas již začal, tipování již není možné.';
                        feedbackEl.classList.remove('success');
                        feedbackEl.classList.add('error');
                        feedbackEl.style.display = 'block';
                        
                        // Skrýt zprávu po 3 sekundách
                        setTimeout(() => {
                            feedbackEl.style.display = 'none';
                        }, 3000);
                    }
                    return; // Zastavit další zpracování
                }
                
                // Kontrola, zda už má uživatel uložený tip (má tlačítko třídu active)
                const matchElement = this.closest('.match');
                const hasSavedTip = matchElement.querySelector('.odds-button.active[data-saved="true"]');
                
                if (hasSavedTip) {
                    // Pokud už má uložený tip, zobrazit upozornění
                    const feedbackEl = document.querySelector('.tips-feedback');
                    if (feedbackEl) {
                        feedbackEl.textContent = 'Tip pro tento zápas již byl uložen a nelze jej změnit.';
                        feedbackEl.classList.remove('success');
                        feedbackEl.classList.add('error');
                        feedbackEl.style.display = 'block';
                        
                        // Skrýt zprávu po 3 sekundách
                        setTimeout(() => {
                            feedbackEl.style.display = 'none';
                        }, 3000);
                    }
                    return; // Zastavit další zpracování
                }
                
                // Získat ID zápasu a hodnotu tipu
                const matchId = matchElement?.getAttribute('data-match-id');
                const tipValue = this.getAttribute('data-value');
                
                // Kontrola, zda je tlačítko již aktivní
                const isAlreadyActive = this.classList.contains('active');
                
                // Zrušit označení ostatních tlačítek ve stejné skupině
                const parent = this.parentElement;
                parent.querySelectorAll('.odds-button').forEach(function(btn) {
                    btn.classList.remove('active', 'selected');
                });
                
                // Pokud tlačítko nebylo aktivní, označit ho
                if (!isAlreadyActive) {
                    this.classList.add('active');
                    this.classList.add('selected');
                } else {
                    // Pokud bylo aktivní, odstranit ho z rekapitulace
                    if (matchId && matchesRecap) {
                        const matchInRecap = matchesRecap.querySelector(`.match-info[data-match-id="${matchId}"]`);
                        if (matchInRecap) {
                            matchInRecap.closest('.match').remove();
                            
                            // Pokud není žádný tip, zobrazit zprávu
                            if (matchesRecap.querySelectorAll('.match').length === 0) {
                                const noTipsDiv = document.createElement('div');
                                noTipsDiv.className = 'no-tips-yet';
                                noTipsDiv.innerHTML = '<p>Zatím jste neprovedli žádné tipy.</p>';
                                matchesRecap.appendChild(noTipsDiv);
                            }
                            
                            // Aktualizovat počet zbývajících zápasů
                            updateRemainingMatches();
                        }
                    }
                    return; // Ukončit zpracování
                }
                
                // Aktualizovat zobrazení bodů podle vybraného tipu
                const oddsPointsElement = matchElement.querySelector('.odds-points');
                const odds = parseFloat(this.getAttribute('data-odds'));
                
                if (oddsPointsElement && !isNaN(odds)) {
                    // Získat body podle kurzu
                    fetch(`${tipnijinak_vars.ajax_url}?action=tipnijinak_get_points_by_odds&odds=${odds}`, {
                        method: 'GET',
                        credentials: 'same-origin'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const points = data.data;
                            let pointsText;
                            
                            // Správné skloňování slova "bod/body/bodů"
                            if (points === 1) {
                                pointsText = `${points} bod`;
                            } else if (points >= 2 && points <= 4) {
                                pointsText = `${points} body`;
                            } else {
                                pointsText = `${points} bodů`;
                            }
                            
                            oddsPointsElement.textContent = pointsText;
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching points:', error);
                    });
                } else if (oddsPointsElement) {
                    // Pokud není vybraný žádný tip, vrátit výchozí text
                    oddsPointsElement.textContent = tipnijinak_vars.body_text || '30 bodů';
                }
                
                // Pokud jsou k dispozici ID zápasu a hodnota tipu, aktualizovat rekapitulaci
                if (matchId && tipValue) {
                    updateMatchesRecap(matchId, tipValue);
                }
            });
        });
    }
    
    // Funkce pro inicializaci přepínání lig
    function initLeagueSwitchers() {
        const leagueSwitchers = document.querySelectorAll('.league-title .window-switcher li');
        
        if (leagueSwitchers.length > 0) {
            const leagueWindows = document.querySelectorAll('.league-matches');
            
            leagueSwitchers.forEach(function(leagueTab) {
                leagueTab.addEventListener('click', function() {
                    const leagueData = this.getAttribute('data');
                    if (!leagueData) return;
                    
                    // Odstranit aktivní třídu ze všech záložek lig
                    leagueSwitchers.forEach(function(lt) {
                        lt.classList.remove('active');
                    });
                    
                    // Odstranit aktivní třídu ze všech oken lig
                    leagueWindows.forEach(function(lw) {
                        lw.classList.remove('active');
                    });
                    
                    // Přidat aktivní třídu aktuální záložce ligy
                    this.classList.add('active');
                    
                    // Přidat aktivní třídu odpovídajícímu oknu ligy
                    const targetLeagueWindow = document.querySelector('.league-' + leagueData + '.league-matches');
                    if (targetLeagueWindow) {
                        targetLeagueWindow.classList.add('active');
                    }
                    
                    // Aktualizovat URL s parametrem liga
                    const url = new URL(window.location.href);
                    url.searchParams.set('liga', leagueData);
                    history.pushState({}, '', url);
                });
            });
        }
    }
    
    // Funkce pro inicializaci submit tlačítka
    function initSubmitButton() {
        const submitButton = document.querySelector('.submit-tips');
        const feedbackEl = document.querySelector('.tips-feedback');
        
        if (submitButton) {
        submitButton.addEventListener('click', function() {
            // Zakázat tlačítko během zpracování
            submitButton.disabled = true;
            submitButton.textContent = tipnijinak_translations.saving_tips;
            
            // Skrýt předchozí zpětnou vazbu
            if (feedbackEl) {
                feedbackEl.classList.remove('success', 'error');
                feedbackEl.style.display = 'none';
                feedbackEl.textContent = '';
            }
            
            const tips = [];
            let hasSelectedTips = false;
            
            // Nasbírat všechny tipy
            document.querySelectorAll('.match').forEach(function(match) {
                const matchId = match.getAttribute('data-match-id');
                if (!matchId) return;
                
                // Hledáme buď .active nebo .selected třídu pro zpětnou kompatibilitu
                const selectedTip = match.querySelector('.odds-button.active') || match.querySelector('.odds-button.selected');
                
                if (selectedTip) {
                    hasSelectedTips = true;
                    const tip = {
                        match_id: matchId,
                        value: selectedTip.getAttribute('data-value')
                    };
                    tips.push(tip);
                }
            });
            
            // Kontrola minimálního počtu tipů
            const minTips = tipnijinak_vars.min_tips || 1;
            const maxTips = tipnijinak_vars.max_tips || 15;
            const isMainCompetition = tipnijinak_vars.is_main_competition;
            const selectedTipsCount = tips.length;
            
            if (!hasSelectedTips) {
                // Zobrazit chybovou zprávu, pokud nejsou vybrány žádné tipy
                if (feedbackEl) {
                    feedbackEl.textContent = tipnijinak_translations.select_at_least_one;
                    feedbackEl.classList.add('error');
                    feedbackEl.style.display = 'block';
                }
                
                // Povolit tlačítko
                submitButton.disabled = false;
                submitButton.textContent = tipnijinak_translations.save_update;
                return;
            }
            
            // Pro nehlávní soutěže kontrolovat přesný počet tipů
            if (!isMainCompetition && selectedTipsCount !== minTips) {
                if (feedbackEl) {
                    feedbackEl.textContent = `Musíte odtipovat přesně ${minTips} zápasů. Aktuálně máte ${selectedTipsCount} tipů.`;
                    feedbackEl.classList.add('error');
                    feedbackEl.style.display = 'block';
                }
                
                // Povolit tlačítko
                submitButton.disabled = false;
                submitButton.textContent = tipnijinak_translations.save_update;
                return;
            }
            
            // Pro hlavní soutěže kontrolovat minimální počet
            if (isMainCompetition && selectedTipsCount < minTips) {
                if (feedbackEl) {
                    feedbackEl.textContent = `Musíte vybrat alespoň ${minTips} tip. Aktuálně máte ${selectedTipsCount} tipů.`;
                    feedbackEl.classList.add('error');
                    feedbackEl.style.display = 'block';
                }
                
                // Povolit tlačítko
                submitButton.disabled = false;
                submitButton.textContent = tipnijinak_translations.save_update;
                return;
            }
            
            // Odeslání požadavku na server
            const roundId = tipnijinak_vars.round_id;
            const competitionId = tipnijinak_vars.competition_id;
            
            const formData = new FormData();
            formData.append('action', 'tipnijinak_save_tips');
            formData.append('security', tipnijinak_vars.nonce);
            formData.append('tips', JSON.stringify(tips));
            formData.append('round_id', roundId);
            formData.append('competition_id', competitionId);
            
            fetch(tipnijinak_vars.ajax_url, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                // Zpracování odpovědi
                if (data.success) {
                    if (feedbackEl) {
                        feedbackEl.textContent = data.data.message;
                        feedbackEl.classList.add('success');
                        feedbackEl.style.display = 'block';
                    }
                    
                    // Označit všechny tipy jako uložené
                    tips.forEach(function(tip) {
                        const matchElement = document.querySelector(`.match[data-match-id="${tip.match_id}"]`);
                        if (matchElement) {
                            const activeButton = matchElement.querySelector('.odds-button.active');
                            if (activeButton) {
                                activeButton.setAttribute('data-saved', 'true');
                            }
                            // Označit všechna tlačítka v tomto zápase jako uložená
                            matchElement.querySelectorAll('.odds-button').forEach(function(btn) {
                                btn.setAttribute('data-saved', 'true');
                            });
                        }
                    });
                    
                    // Aktualizovat rekapitulaci tipů - načíst stránku po 2 sekundách
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    if (feedbackEl) {
                        feedbackEl.textContent = data.data.message;
                        feedbackEl.classList.add('error');
                        feedbackEl.style.display = 'block';
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (feedbackEl) {
                    feedbackEl.textContent = tipnijinak_translations.error_saving_tips;
                    feedbackEl.classList.add('error');
                    feedbackEl.style.display = 'block';
                }
            })
            .finally(() => {
                // Povolit tlačítko
                submitButton.disabled = false;
                submitButton.textContent = tipnijinak_translations.save_update;
            });
        });
    }
    }
    
    // Zavolat inicializaci submit tlačítka
    initSubmitButton();
    
    // Inicializovat lightbox pro obrázky cen
    initPrizeLightbox();
});

// Funkce pro inicializaci lightbox pro obrázky cen
function initPrizeLightbox() {
    const lightboxOverlay = document.getElementById('prize-lightbox');
    const lightboxImage = document.getElementById('lightbox-image');
    const lightboxCaption = document.querySelector('.lightbox-caption');
    const lightboxClose = document.querySelector('.lightbox-close');
    
    if (!lightboxOverlay || !lightboxImage || !lightboxCaption || !lightboxClose) {
        return;
    }
    
    // Přidat event listener pro všechny klikatelné obrázky cen
    document.querySelectorAll('.prize-image-clickable').forEach(function(image) {
        image.addEventListener('click', function() {
            const src = this.getAttribute('data-lightbox-src');
            const caption = this.getAttribute('data-lightbox-caption');
            
            if (src) {
                lightboxImage.src = src;
                lightboxImage.alt = caption || '';
                lightboxCaption.textContent = caption || '';
                lightboxOverlay.classList.add('active');
                document.body.style.overflow = 'hidden'; // Zabránit scrollování na pozadí
            }
        });
    });
    
    // Zavřít lightbox při kliknutí na křížek
    lightboxClose.addEventListener('click', function() {
        closeLightbox();
    });
    
    // Zavřít lightbox při kliknutí na pozadí
    lightboxOverlay.addEventListener('click', function(e) {
        if (e.target === lightboxOverlay) {
            closeLightbox();
        }
    });
    
    // Zavřít lightbox při stisknutí ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && lightboxOverlay.classList.contains('active')) {
            closeLightbox();
        }
    });
    
    // Funkce pro zavření lightbox
    function closeLightbox() {
        lightboxOverlay.classList.remove('active');
        document.body.style.overflow = ''; // Obnovit scrollování
        lightboxImage.src = '';
        lightboxCaption.textContent = '';
    }
}