/**
 * JavaScript for Match Import functionality
 */
(function($) {
    'use strict';

    // Elements
    var $form = $('#match-import-form');
    var $fileInput = $('#csv_file');
    var $competitionSelect = $('#competition_id');
    var $roundSelect = $('#round_id');
    var $previewButton = $('#preview-import');
    var $processButton = $('#process-import');
    var $processButtonTop = $('#process-import-top');
    var $cancelButton = $('#cancel-import');
    var $cancelButtonTop = $('#cancel-import-top');
    var $newImportButton = $('#new-import');
    var $previewContainer = $('#preview-container');
    var $previewContent = $('#preview-content');
    var $importResults = $('#import-results');
    var $resultsContent = $('#results-content');
    var $batchProgress = $('#batch-progress');
    var $progressBar = $('#import-progress-bar .progress-bar-fill');
    var $progressText = $('#progress-text');
    var $progressStatus = $('.progress-status');
    var $processedItemsList = $('#processed-items');
    var $cancelBatchButton = $('#cancel-batch');
    
    // Store temp file name
    var tempFileName = '';
    
    // Initialize
    function init() {
        // Event handlers
        $competitionSelect.on('change', onCompetitionChange);
        $(document).on('click', '#create-round', onCreateRoundClick);
        $(document).on('click', '.close, #cancel-round', closeModal);
        $(document).on('submit', '#create-round-form', onSaveRoundClick);
        $previewButton.on('click', onPreviewClick);
        $processButton.on('click', onProcessClick);
        $processButtonTop.on('click', onProcessClick); // Top import button
        $cancelButton.on('click', onCancelClick);
        $cancelButtonTop.on('click', onCancelClick); // Top cancel button
        $newImportButton.on('click', onNewImportClick);
        $cancelBatchButton.on('click', onCancelBatchClick);
        
        // Initial state - pokud je už soutěž vybrána, načteme kola
        if ($competitionSelect.val()) {
            console.log('Competition already selected, loading rounds:', $competitionSelect.val());
            onCompetitionChange.call($competitionSelect[0]);
        } else {
            $roundSelect.prop('disabled', true);
            $('#create-round').prop('disabled', true);
        }
        
        // Set default dates for modal form if it exists
        if ($('#create-round-form').length) {
            setDefaultDates();
        }
    }
    
    // Set default dates for modal form (today and tomorrow)
    function setDefaultDates() {
        var today = new Date();
        var tomorrow = new Date();
        tomorrow.setDate(today.getDate() + 1);
        
        // Format dates for datetime-local input
        var todayStr = formatDateForInput(today);
        var tomorrowStr = formatDateForInput(tomorrow);
        
        // Set default values if elements exist
        var $dateFrom = $('#round_date_from');
        var $dateTo = $('#round_date_to');
        
        if ($dateFrom.length) {
            $dateFrom.val(todayStr);
        }
        
        if ($dateTo.length) {
            $dateTo.val(tomorrowStr);
        }
    }
    
    // Format date for datetime-local input (YYYY-MM-DDThh:mm)
    function formatDateForInput(date) {
        var year = date.getFullYear();
        var month = (date.getMonth() + 1).toString().padStart(2, '0');
        var day = date.getDate().toString().padStart(2, '0');
        var hours = date.getHours().toString().padStart(2, '0');
        var minutes = date.getMinutes().toString().padStart(2, '0');
        
        return year + '-' + month + '-' + day + 'T' + hours + ':' + minutes;
    }
    
    // Competition change handler
    function onCompetitionChange() {
        var competitionId = $(this).val();
        
        if (!competitionId) {
            $roundSelect.prop('disabled', true);
            $('#create-round').prop('disabled', true);
            $roundSelect.html('<option value="">' + 'Nejprve vyberte soutěž' + '</option>');
            return;
        }
        
        // Clear and disable round select
        $roundSelect.prop('disabled', true);
        $('#create-round').prop('disabled', true);
        $roundSelect.html('<option value="">' + 'Načítání kol...' + '</option>');
        
        // Store competition ID for later use in modal
        $('#create-round-form').data('competition-id', competitionId);
        
        // Fetch rounds for selected competition
        $.ajax({
            url: matchImportVars.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_competition_rounds',
                nonce: matchImportVars.nonce,
                competition_id: competitionId
            },
            success: function(response) {
                if (response.success && response.data.rounds) {
                    populateRoundSelect(response.data.rounds);
                } else {
                    showNoRoundsMessage();
                }
            },
            error: function() {
                showNoRoundsMessage();
            },
            complete: function() {
                $roundSelect.prop('disabled', false);
                $('#create-round').prop('disabled', false);
            }
        });
    }
    
    
    // Create Round button click handler (open modal)
    function onCreateRoundClick() {
        var competitionId = $competitionSelect.val();
        
        if (!competitionId) {
            alert('Nejprve vyberte soutěž');
            return;
        }
        
        // Get next round number if possible
        var nextRoundNumber = 1;
        var $options = $roundSelect.find('option');
        
        if ($options.length > 1) {
            // Try to find the highest round number and add 1
            $options.each(function() {
                var text = $(this).text();
                var match = text.match(/Kolo (\d+)/);
                if (match && match[1]) {
                    var num = parseInt(match[1], 10);
                    if (!isNaN(num) && num >= nextRoundNumber) {
                        nextRoundNumber = num + 1;
                    }
                }
            });
        }
        
        // Reset form - ověřujeme, že formulář existuje
        var $form = $('#create-round-form');
        if ($form.length && $form[0]) {
            $form[0].reset();
        }
        
        // Set next round number and default name
        $('#round_number').val(nextRoundNumber);
        $('#round_name').val('Kolo ' + nextRoundNumber);
        
        // Set default dates
        setDefaultDates();
        
        // Show modal
        $('#create-round-modal').fadeIn(300);
    }
    
    // Close modal
    function closeModal() {
        var $modal = $('#create-round-modal');
        if ($modal.length) {
            $modal.fadeOut(200);
        }
    }
    
    // Save round click handler
    function onSaveRoundClick(e) {
        e.preventDefault();
        
        var $form = $('#create-round-form');
        if (!$form.length) {
            alert('Formulář nebyl nalezen.');
            return;
        }
        
        var competitionId = $competitionSelect.val();
        
        if (!competitionId) {
            alert('Chybí ID soutěže.');
            return;
        }
        
        // Get form data
        var roundName = $('#round_name').val();
        var roundNumber = $('#round_number').val();
        var dateFrom = $('#round_date_from').val();
        var dateTo = $('#round_date_to').val();
        var status = $('#round_status').val();
        
        if (!roundName || !roundNumber || !dateFrom || !dateTo || !status) {
            alert('Vyplňte prosím všechna povinná pole.');
            return;
        }
        
        // Show loading
        var $saveBtn = $('#save-round');
        showLoading($saveBtn);
        
        console.log('Odesílám data:', {
            action: 'create_single_round',
            nonce: matchImportVars.nonce,
            competition_id: competitionId,
            round_name: roundName,
            round_number: roundNumber,
            date_from: dateFrom,
            date_to: dateTo,
            status: status
        });
        
        // Create round via AJAX
        $.ajax({
            url: matchImportVars.ajaxurl,
            type: 'POST',
            data: {
                action: 'create_single_round',
                nonce: matchImportVars.nonce,
                competition_id: competitionId,
                round_name: roundName,
                round_number: roundNumber,
                date_from: dateFrom,
                date_to: dateTo,
                status: status
            },
            success: function(response) {
                console.log('Server response:', response);
                if (response.success && response.data.round) {
                    // Close modal
                    closeModal();
                    
                    // Add new round to select and select it
                    var newOption = new Option(response.data.round.name, response.data.round.id, true, true);
                    $roundSelect.append(newOption).trigger('change');
                    
                    // Show success message
                    alert('Kolo bylo úspěšně vytvořeno.');
                } else {
                    console.error('Round creation error:', response);
                    let errorMsg = (response.data && response.data.message) ? response.data.message : 'Neznámá chyba';
                    if (response.data && response.data.error_details) {
                        errorMsg += '\n\nDetail chyby: ' + JSON.stringify(response.data.error_details);
                    }
                    
                    // Provide more specific guidance for common issues
                    errorMsg += '\n\nPokud problém přetrvává, zkontrolujte:\n' +
                                '1. Zda je správně definovaný post type "kolo" v souboru functions.php\n' +
                                '2. Zda nemáte v systému plugin, který blokuje vytváření příspěvků\n' + 
                                '3. Zda je správně nastavená databáze a uživatelská práva';
                                
                    alert('Chyba při vytváření kola: ' + errorMsg);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', xhr.responseText, status, error);
                let errorDetail = '';
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorDetail = response.data?.message || '';
                } catch (e) {
                    errorDetail = xhr.responseText || '';
                }
                alert('Chyba při vytváření kola: ' + error + '\n\nDetail: ' + errorDetail);
            },
            complete: function() {
                if ($saveBtn.length) {
                    hideLoading($saveBtn);
                }
            }
        });
        
        // Prevent form submission (no page reload)
        return false;
    }
    
    // Populate round select
    function populateRoundSelect(rounds) {
        var options = '<option value="">' + 'Vyberte kolo' + '</option>';
        
        // Sort rounds by number
        rounds.sort(function(a, b) {
            return parseInt(a.number || 0) - parseInt(b.number || 0);
        });
        
        // Add all rounds to select
        $.each(rounds, function(index, round) {
            options += '<option value="' + round.id + '">' + round.name + '</option>';
        });
        
        // Update select with options
        $roundSelect.html(options);
        
        console.log('Loaded rounds:', rounds);
    }
    
    // Show no rounds message
    function showNoRoundsMessage() {
        $roundSelect.html('<option value="">' + 'Kola nenalezena' + '</option>');
    }
    
    // Preview button click handler
    function onPreviewClick() {
        // Validate form
        if (!validateForm()) {
            return;
        }
        
        // Create FormData object
        var formData = new FormData($form[0]);
        formData.append('action', 'preview_csv_import');
        formData.append('nonce', matchImportVars.nonce);
        
        // Show loading
        showLoading($previewButton);
        
        // Send AJAX request
        $.ajax({
            url: matchImportVars.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success && response.data.html) {
                    // Store temp file name
                    tempFileName = response.data.temp_file;
                    
                    // Show preview
                    $previewContent.html(response.data.html);
                    $form.hide();
                    $previewContainer.fadeIn();
                    
                    // Setup row selection handlers
                    setupRowSelectionHandlers();
                } else {
                    // Show error
                    alert(response.data.message || matchImportVars.i18n.previewError);
                }
            },
            error: function() {
                alert(matchImportVars.i18n.previewError);
            },
            complete: function() {
                hideLoading($previewButton);
            }
        });
    }
    
    // Setup row selection handlers
    function setupRowSelectionHandlers() {
        // Master checkbox
        $('#select-all-toggle').on('change', function() {
            var isChecked = $(this).prop('checked');
            $('.row-select-checkbox:visible').prop('checked', isChecked);
            updateRowSelectionUI();
        });
        
        // Individual row checkboxes
        $(document).on('change', '.row-select-checkbox', function() {
            updateSelectAllToggle();
            updateRowSelectionUI();
        });
        
        // Select all button
        $(document).on('click', '#select-all', function(e) {
            e.preventDefault();
            $('.row-select-checkbox:visible').prop('checked', true);
            $('#select-all-toggle').prop('checked', true);
            updateRowSelectionUI();
        });
        
        // Deselect all button
        $(document).on('click', '#deselect-all', function(e) {
            e.preventDefault();
            $('.row-select-checkbox:visible').prop('checked', false);
            $('#select-all-toggle').prop('checked', false);
            updateRowSelectionUI();
        });
        
        // Select only new matches button
        $(document).on('click', '#select-add', function(e) {
            e.preventDefault();
            $('.row-select-checkbox').prop('checked', false);
            $('tr[data-action="add"]:visible .row-select-checkbox').prop('checked', true);
            updateSelectAllToggle();
            updateRowSelectionUI();
        });
        
        // Select only updates button
        $(document).on('click', '#select-update', function(e) {
            e.preventDefault();
            $('.row-select-checkbox').prop('checked', false);
            $('tr[data-action="update"]:visible .row-select-checkbox').prop('checked', true);
            updateSelectAllToggle();
            updateRowSelectionUI();
        });
        
        // Select with result button
        $(document).on('click', '#select-with-result', function(e) {
            e.preventDefault();
            
            // Uncheck all checkboxes first
            $('.row-select-checkbox').prop('checked', false);
            
            // Check only rows with results
            $('tr[data-has-result="true"] .row-select-checkbox').prop('checked', true);
            
            updateSelectAllToggle();
            updateRowSelectionUI();
        });
        
        // Select without result button
        $(document).on('click', '#select-without-result', function(e) {
            e.preventDefault();
            
            // Uncheck all checkboxes first
            $('.row-select-checkbox').prop('checked', false);
            
            // Check only rows without results
            $('tr[data-has-result="false"] .row-select-checkbox').prop('checked', true);
            
            updateSelectAllToggle();
            updateRowSelectionUI();
        });
        
        // Make rows clickable to toggle checkbox
        $(document).on('click', 'tr[data-index]', function(e) {
            // Skip if clicked on checkbox or link
            if ($(e.target).is('input[type="checkbox"], a, button') || $(e.target).parents('a, button').length) {
                return;
            }
            
            // Skip if row is hidden by filter
            if ($(this).hasClass('hidden-by-filter')) {
                return;
            }
            
            var $checkbox = $(this).find('.row-select-checkbox');
            $checkbox.prop('checked', !$checkbox.prop('checked'));
            updateSelectAllToggle();
            updateRowSelectionUI();
        });
        
        // Initialize row selection UI
        updateRowSelectionUI();
    }
    
    // Update row counts
    function updateRowCounts() {
        var total = $('tr[data-index]').length;
        var selected = $('.row-select-checkbox:checked').length;
        
        $('.selected-count').text(selected + ' / ' + total);
    }
    
    // Update the "select all" checkbox based on individual checkboxes
    function updateSelectAllToggle() {
        var allCheckboxes = $('.row-select-checkbox');
        var checkedCheckboxes = $('.row-select-checkbox:checked');
        var allChecked = checkedCheckboxes.length === allCheckboxes.length && allCheckboxes.length > 0;
        $('#select-all-toggle').prop('checked', allChecked);
    }
    
    // Update row selection UI (highlight selected rows, etc.)
    function updateRowSelectionUI() {
        $('.row-select-checkbox').each(function() {
            var $row = $(this).closest('tr');
            if ($(this).prop('checked')) {
                $row.addClass('selected-row');
            } else {
                $row.removeClass('selected-row');
            }
        });
        
        // Update row counts based on visibility
        updateRowCounts();
    }
    
    // Process import button click handler
    function onProcessClick() {
        // Confirm import
        if (!confirm(matchImportVars.i18n.confirmImport)) {
            return;
        }
        
        // Get selected rows
        var selectedRows = [];
        $('.row-select-checkbox:checked').each(function() {
            selectedRows.push($(this).data('index'));
        });
        
        // Check if any rows are selected
        if (selectedRows.length === 0) {
            alert('Vyberte alespoň jeden zápas k importu.');
            return;
        }
        
        // Create data object
        var data = {
            action: 'process_csv_import',
            nonce: matchImportVars.nonce,
            temp_file: tempFileName,
            selected_rows: selectedRows
        };
        
        // Show loading on both buttons
        showLoading($processButton);
        showLoading($processButtonTop);
        
        // Send AJAX request
        $.ajax({
            url: matchImportVars.ajaxurl,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    if (response.data.batch_mode) {
                        // Start batch processing
                        $previewContainer.hide();
                        resetProgressBar();
                        $progressStatus.text(response.data.message);
                        $batchProgress.fadeIn();
                        processBatch(response.data.batch_file);
                    } else if (response.data.html) {
                        // Show results (legacy mode)
                        $resultsContent.html(response.data.html);
                        $previewContainer.hide();
                        $importResults.fadeIn();
                    }
                } else {
                    // Show error
                    alert(response.data.message || matchImportVars.i18n.importError);
                    $previewContainer.hide();
                    $form.fadeIn();
                }
            },
            error: function() {
                alert(matchImportVars.i18n.importError);
                $previewContainer.hide();
                $form.fadeIn();
            },
            complete: function() {
                hideLoading($processButton);
                hideLoading($processButtonTop);
            }
        });
    }
    
    // Cancel button click handler
    function onCancelClick() {
        $previewContainer.hide();
        $form.fadeIn();
        tempFileName = '';
    }
    
    // New import button click handler
    function onNewImportClick() {
        // Reset form
        $form[0].reset();
        $roundSelect.prop('disabled', true);
        $roundSelect.html('<option value="">' + 'Nejprve vyberte soutěž' + '</option>');
        
        // Reset UI
        $importResults.hide();
        $form.fadeIn();
    }
    
    // Validate form
    function validateForm() {
        // Check file input
        if (!$fileInput[0].files || !$fileInput[0].files[0]) {
            alert('Vyberte CSV soubor k importu.');
            $fileInput.focus();
            return false;
        }
        
        // Check file extension
        var fileName = $fileInput[0].files[0].name;
        var fileExt = fileName.split('.').pop().toLowerCase();
        
        if (fileExt !== 'csv') {
            alert('Vybraný soubor musí být ve formátu CSV.');
            $fileInput.focus();
            return false;
        }
        
        return true;
    }
    
    // Show loading indicator
    function showLoading($button) {
        $button.prop('disabled', true);
        $button.after('<span class="import-loading">Zpracovávám...</span>');
    }
    
    // Hide loading indicator
    function hideLoading($button) {
        $button.prop('disabled', false);
        $button.next('.import-loading').remove();
    }
    
    // Reset progress bar
    function resetProgressBar() {
        $progressBar.width('0%');
        $progressText.text('0%');
        $processedItemsList.empty();
    }
    
    // Process batch
    function processBatch(batchFile) {
        $.ajax({
            url: matchImportVars.ajaxurl,
            type: 'POST',
            data: {
                action: 'process_batch_import',
                nonce: matchImportVars.nonce,
                batch_file: batchFile
            },
            success: function(response) {
                if (response.success) {
                    if (response.data.is_completed) {
                        // Process completed
                        updateProgressBar(100);
                        $progressStatus.text('Import dokončen!');
                        
                        // Show results
                        setTimeout(function() {
                            $resultsContent.html(response.data.html);
                            $batchProgress.hide();
                            $importResults.fadeIn();
                            tempFileName = '';
                        }, 1000);
                    } else {
                        // Update progress
                        updateProgressBar(response.data.percent);
                        $progressStatus.text(response.data.message);
                        
                        // Add items to processed list
                        if (response.data.items_processed && response.data.items_processed.length > 0) {
                            response.data.items_processed.forEach(function(item) {
                                $processedItemsList.prepend($('<li class="success">').text('✓ ' + item));
                                
                                // Limit list length to avoid performance issues
                                if ($processedItemsList.children().length > 50) {
                                    $processedItemsList.children().last().remove();
                                }
                            });
                        }
                        
                        // Continue with next batch
                        setTimeout(function() {
                            processBatch(response.data.batch_file);
                        }, 500); // Small delay to prevent overwhelming the server
                    }
                } else {
                    // Error during batch processing
                    var errorItem = $('<li class="error">').text('❌ Chyba: ' + response.data.message);
                    $processedItemsList.prepend(errorItem);
                    $progressStatus.text('Chyba při zpracování dávky.');
                    
                    // Show cancel button
                    $cancelBatchButton.show();
                }
            },
            error: function(xhr, status, error) {
                // AJAX error
                var errorItem = $('<li class="error">').text('❌ AJAX Chyba: ' + error);
                $processedItemsList.prepend(errorItem);
                $progressStatus.text('Chyba při komunikaci se serverem.');
                
                // Try again after a delay - server might be overloaded
                setTimeout(function() {
                    processBatch(batchFile);
                }, 5000); // 5 second delay before retry
            }
        });
    }
    
    // Update progress bar
    function updateProgressBar(percent) {
        $progressBar.css('width', percent + '%');
        $progressText.text(percent + '%');
    }
    
    // Cancel batch processing
    function onCancelBatchClick() {
        if (confirm('Opravdu chcete zrušit probíhající import?')) {
            $batchProgress.hide();
            $form.fadeIn();
            tempFileName = '';
        }
    }
    
    // Initialize when document is ready
    $(document).ready(init);

})(jQuery);