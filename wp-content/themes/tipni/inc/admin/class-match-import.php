<?php
/**
 * Match Import Class
 *
 * Handles the import of matches and results from CSV files
 *
 * @package TipniJinak
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Match Import class.
 */
class Tipnijinak_Match_Import {

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_ajax_preview_csv_import', array( $this, 'ajax_preview_csv_import' ) );
        add_action( 'wp_ajax_process_csv_import', array( $this, 'ajax_process_csv_import' ) );
        add_action( 'wp_ajax_process_batch_import', array( $this, 'ajax_process_batch_import' ) );
        add_action( 'wp_ajax_get_competition_rounds', array( $this, 'ajax_get_competition_rounds' ) );
        add_action( 'wp_ajax_create_single_round', array( $this, 'ajax_create_single_round' ) );
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=zapas',
            __( 'Import zápasů', 'tipnijinak' ),
            __( 'Import zápasů', 'tipnijinak' ),
            'manage_options',
            'match-import',
            array( $this, 'render_admin_page' )
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting( 'match-import', 'match_import_options' );
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts( $hook ) {
        if ( 'zapas_page_match-import' !== $hook ) {
            return;
        }

        wp_enqueue_style( 'match-import-css', get_template_directory_uri() . '/inc/admin/css/match-import.css', array(), '1.0.0' );
        wp_enqueue_script( 'match-import-js', get_template_directory_uri() . '/inc/admin/js/match-import.js', array( 'jquery' ), '1.0.0', true );
        
        wp_localize_script( 'match-import-js', 'matchImportVars', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'match-import-nonce' ),
            'i18n' => array(
                'previewError' => __( 'Chyba při načítání náhledu. Zkontrolujte formát CSV souboru.', 'tipnijinak' ),
                'importError' => __( 'Chyba při importu. Zkuste to znovu.', 'tipnijinak' ),
                'confirmImport' => __( 'Opravdu chcete importovat tyto zápasy a výsledky?', 'tipnijinak' ),
            )
        ) );
    }

    /**
     * Render admin page
     */
    public function render_admin_page() {
        ?>
        <div class="wrap match-import-wrap">
            <h1><?php echo esc_html__( 'Import zápasů a výsledků', 'tipnijinak' ); ?></h1>
            
            <div class="match-import-container">
                <div class="match-import-intro">
                    <p><?php echo esc_html__( 'Zde můžete importovat zápasy a výsledky z CSV souboru.', 'tipnijinak' ); ?></p>
                    <p><?php echo esc_html__( 'CSV soubor musí být ve formátu:', 'tipnijinak' ); ?></p>
                    <pre>Liga,Zápas,Link,Výsledek_1,Výsledek_2,1,0,2,Datum</pre>
                    <p><?php echo esc_html__( 'Kde:', 'tipnijinak' ); ?></p>
                    <ul>
                        <li><strong>Liga</strong> - <?php echo esc_html__( 'identifikátor ligy (např. liga-mistru, 1-cesko)', 'tipnijinak' ); ?></li>
                        <li><strong>Zápas</strong> - <?php echo esc_html__( 'název zápasu (např. "AC Milan - Feyenoord")', 'tipnijinak' ); ?></li>
                        <li><strong>Link</strong> - <?php echo esc_html__( 'odkaz na zápas', 'tipnijinak' ); ?></li>
                        <li><strong>Výsledek_1</strong> - <?php echo esc_html__( 'počet gólů domácího týmu (-1 pro zatím neznámý výsledek)', 'tipnijinak' ); ?></li>
                        <li><strong>Výsledek_2</strong> - <?php echo esc_html__( 'počet gólů hostujícího týmu (-1 pro zatím neznámý výsledek)', 'tipnijinak' ); ?></li>
                        <li><strong>1</strong> - <?php echo esc_html__( 'kurz na výhru domácích', 'tipnijinak' ); ?></li>
                        <li><strong>0</strong> - <?php echo esc_html__( 'kurz na remízu', 'tipnijinak' ); ?></li>
                        <li><strong>2</strong> - <?php echo esc_html__( 'kurz na výhru hostů', 'tipnijinak' ); ?></li>
                        <li><strong>Datum</strong> - <?php echo esc_html__( 'datum a čas zápasu ve formátu "DD.MM. HH:MM"', 'tipnijinak' ); ?></li>
                    </ul>
                </div>
                
                <div class="match-import-form">
                    <form id="match-import-form" method="post" enctype="multipart/form-data">
                        <?php wp_nonce_field( 'match-import-action', 'match_import_nonce' ); ?>
                        
                        <div class="form-field">
                            <label for="csv_file"><?php echo esc_html__( 'CSV soubor', 'tipnijinak' ); ?></label>
                            <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
                        </div>
                        
                        <div class="form-field">
                            <label for="import_mode"><?php echo esc_html__( 'Režim importu', 'tipnijinak' ); ?></label>
                            <select name="import_mode" id="import_mode">
                                <option value="add_update"><?php echo esc_html__( 'Přidat nové a aktualizovat existující', 'tipnijinak' ); ?></option>
                                <option value="add_only"><?php echo esc_html__( 'Přidat pouze nové (přeskočit existující)', 'tipnijinak' ); ?></option>
                                <option value="update_only"><?php echo esc_html__( 'Aktualizovat pouze existující (přeskočit nové)', 'tipnijinak' ); ?></option>
                            </select>
                        </div>
                        
                        <div class="form-field">
                            <label for="match_status"><?php echo esc_html__( 'Stav zápasů', 'tipnijinak' ); ?></label>
                            <select name="match_status" id="match_status">
                                <option value="auto"><?php echo esc_html__( 'Automaticky (podle výsledku)', 'tipnijinak' ); ?></option>
                                <option value="planovany"><?php echo esc_html__( 'Plánovaný', 'tipnijinak' ); ?></option>
                                <option value="probihajici"><?php echo esc_html__( 'Probíhající', 'tipnijinak' ); ?></option>
                                <option value="ukonceny"><?php echo esc_html__( 'Ukončený', 'tipnijinak' ); ?></option>
                                <option value="zrusen"><?php echo esc_html__( 'Zrušený', 'tipnijinak' ); ?></option>
                            </select>
                        </div>
                        
                        <div class="form-field">
                            <label for="competition_id"><?php echo esc_html__( 'Soutěž', 'tipnijinak' ); ?></label>
                            <select name="competition_id" id="competition_id">
                                <option value=""><?php echo esc_html__( '-- Vyberte soutěž --', 'tipnijinak' ); ?></option>
                                <?php
                                $competitions = get_posts( array(
                                    'post_type' => 'soutez',
                                    'posts_per_page' => -1,
                                    'orderby' => 'title',
                                    'order' => 'ASC',
                                ) );
                                
                                foreach ( $competitions as $competition ) {
                                    echo '<option value="' . esc_attr( $competition->ID ) . '">' . esc_html( $competition->post_title ) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="form-field">
                            <label for="round_id"><?php echo esc_html__( 'Kolo', 'tipnijinak' ); ?></label>
                            <div class="round-select-wrapper">
                                <select name="round_id" id="round_id" disabled>
                                    <option value=""><?php echo esc_html__( '-- Nejprve vyberte soutěž --', 'tipnijinak' ); ?></option>
                                </select>
                                <button type="button" id="create-round" class="button button-primary" disabled><?php echo esc_html__( 'Přidat kolo', 'tipnijinak' ); ?></button>
                            </div>
                        </div>
                        
                        
                        <div class="form-actions">
                            <button type="button" id="preview-import" class="button button-primary"><?php echo esc_html__( 'Náhled importu', 'tipnijinak' ); ?></button>
                        </div>
                    </form>
                </div>
                
                <div id="preview-container" class="match-import-preview" style="display: none;">
                    <h2><?php echo esc_html__( 'Náhled importu', 'tipnijinak' ); ?></h2>
                    <div class="preview-actions preview-actions-top">
                        <button type="button" id="process-import-top" class="button button-primary"><?php echo esc_html__( 'Importovat zápasy', 'tipnijinak' ); ?></button>
                        <button type="button" id="cancel-import-top" class="button"><?php echo esc_html__( 'Zrušit', 'tipnijinak' ); ?></button>
                    </div>
                    
                    <div id="preview-content"></div>
                    
                    <div class="preview-actions preview-actions-bottom">
                        <button type="button" id="process-import" class="button button-primary"><?php echo esc_html__( 'Importovat zápasy', 'tipnijinak' ); ?></button>
                        <button type="button" id="cancel-import" class="button"><?php echo esc_html__( 'Zrušit', 'tipnijinak' ); ?></button>
                    </div>
                </div>
                
                <div id="batch-progress" class="match-import-progress" style="display: none;">
                    <h2><?php echo esc_html__( 'Průběh importu', 'tipnijinak' ); ?></h2>
                    <div class="progress-container">
                        <div class="progress-bar" id="import-progress-bar">
                            <div class="progress-bar-fill" style="width: 0%"></div>
                        </div>
                        <div class="progress-text" id="progress-text">0%</div>
                    </div>
                    <div id="progress-details">
                        <p class="progress-status"><?php echo esc_html__( 'Zpracovávám data...', 'tipnijinak' ); ?></p>
                        <ul id="processed-items" class="processed-items-list"></ul>
                    </div>
                    <div class="batch-actions">
                        <button type="button" id="cancel-batch" class="button"><?php echo esc_html__( 'Zrušit import', 'tipnijinak' ); ?></button>
                    </div>
                </div>
                
                <div id="import-results" class="match-import-results" style="display: none;">
                    <h2><?php echo esc_html__( 'Výsledky importu', 'tipnijinak' ); ?></h2>
                    <div id="results-content"></div>
                    
                    <div class="results-actions">
                        <button type="button" id="new-import" class="button button-primary"><?php echo esc_html__( 'Nový import', 'tipnijinak' ); ?></button>
                    </div>
                </div>
            </div>
            
            <!-- Modal pro vytvoření kola (přesunutý MIMO formulář) -->
            <div id="create-round-modal" class="modal" style="display: none;">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h3><?php echo esc_html__( 'Přidat nové kolo', 'tipnijinak' ); ?></h3>
                    
                    <form id="create-round-form">
                        <div class="form-field">
                            <label for="round_name"><?php echo esc_html__( 'Název kola', 'tipnijinak' ); ?></label>
                            <input type="text" id="round_name" name="round_name" required>
                        </div>
                        
                        <div class="form-field">
                            <label for="round_number"><?php echo esc_html__( 'Číslo kola', 'tipnijinak' ); ?></label>
                            <input type="number" id="round_number" name="round_number" min="1" value="1" required>
                        </div>
                        
                        <div class="form-field">
                            <label for="round_date_from"><?php echo esc_html__( 'Datum od', 'tipnijinak' ); ?></label>
                            <input type="datetime-local" id="round_date_from" name="round_date_from" required>
                        </div>
                        
                        <div class="form-field">
                            <label for="round_date_to"><?php echo esc_html__( 'Datum do', 'tipnijinak' ); ?></label>
                            <input type="datetime-local" id="round_date_to" name="round_date_to" required>
                        </div>
                        
                        <div class="form-field">
                            <label for="round_status"><?php echo esc_html__( 'Stav kola', 'tipnijinak' ); ?></label>
                            <select id="round_status" name="round_status" required>
                                <option value="planovano"><?php echo esc_html__( 'Plánováno', 'tipnijinak' ); ?></option>
                                <option value="otevreno"><?php echo esc_html__( 'Otevřeno', 'tipnijinak' ); ?></option>
                                <option value="probihajici"><?php echo esc_html__( 'Probíhající', 'tipnijinak' ); ?></option>
                                <option value="uzavreno"><?php echo esc_html__( 'Uzavřeno', 'tipnijinak' ); ?></option>
                            </select>
                        </div>
                        
                        <div class="modal-actions">
                            <button type="button" id="cancel-round" class="button"><?php echo esc_html__( 'Zrušit', 'tipnijinak' ); ?></button>
                            <button type="submit" id="save-round" class="button button-primary"><?php echo esc_html__( 'Vytvořit kolo', 'tipnijinak' ); ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Ajax handler for CSV preview
     */
    public function ajax_preview_csv_import() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'match-import-nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Bezpečnostní kontrola selhala.', 'tipnijinak' ) ) );
        }

        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Nemáte oprávnění k této akci.', 'tipnijinak' ) ) );
        }

        // Check if file is uploaded
        if ( ! isset( $_FILES['csv_file'] ) || empty( $_FILES['csv_file']['tmp_name'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Žádný soubor nebyl nahrán.', 'tipnijinak' ) ) );
        }

        // Check file type
        $file_type = wp_check_filetype( $_FILES['csv_file']['name'] );
        if ( $file_type['ext'] !== 'csv' ) {
            wp_send_json_error( array( 'message' => __( 'Neplatný typ souboru. Nahrávejte pouze CSV soubory.', 'tipnijinak' ) ) );
        }

        // Read CSV file
        $file = fopen( $_FILES['csv_file']['tmp_name'], 'r' );
        if ( ! $file ) {
            wp_send_json_error( array( 'message' => __( 'Nelze otevřít soubor.', 'tipnijinak' ) ) );
        }

        // Get competition and round info
        $competition_id = isset( $_POST['competition_id'] ) ? intval( $_POST['competition_id'] ) : 0;
        $round_id = isset( $_POST['round_id'] ) ? intval( $_POST['round_id'] ) : 0;
        $import_mode = isset( $_POST['import_mode'] ) ? sanitize_text_field( $_POST['import_mode'] ) : 'add_update';
        $match_status = isset( $_POST['match_status'] ) ? sanitize_text_field( $_POST['match_status'] ) : 'auto';

        // Get competition and round names
        $competition_name = $competition_id ? get_the_title( $competition_id ) : __( 'Neurčeno', 'tipnijinak' );
        $round_name = '';
        
        if ( $round_id ) {
            $round_post = get_post( $round_id );
            $round_name = $round_post ? $round_post->post_title : '';
        } else {
            $round_name = __( 'Neurčeno', 'tipnijinak' );
        }

        // Parse CSV data for preview
        $rows = array();
        $row_index = 0;
        $header = array();

        // Detect the delimiter (either comma or semicolon)
        $sample_line = fgets($file);
        rewind($file);
        $delimiter = (strpos($sample_line, ',') !== false) ? ',' : ';';

        while ( ( $data = fgetcsv( $file, 0, $delimiter ) ) !== FALSE ) {
            // Skip the first row if it's not the header (match_results)
            if ( $row_index === 0 && isset( $data[0] ) && strpos( $data[0], 'match_results' ) !== false ) {
                $row_index++;
                continue;
            }
            
            // Store header row
            if ( $row_index === 0 || empty( $header ) ) {
                $header = $data;
                $row_index++;
                continue;
            }

            // Skip rows with invalid data
            if ( count( $data ) < 9 ) {
                $row_index++;
                continue;
            }

            // Map CSV columns to array keys
            $row = array(
                'liga' => isset( $data[0] ) ? $data[0] : '',
                'zapas' => isset( $data[1] ) ? $data[1] : '',
                'link' => isset( $data[2] ) ? $data[2] : '',
                'vysledek_1' => isset( $data[3] ) ? intval( $data[3] ) : -1,
                'vysledek_2' => isset( $data[4] ) ? intval( $data[4] ) : -1,
                'kurz_1' => isset( $data[5] ) ? floatval( $data[5] ) : 0,
                'kurz_0' => isset( $data[6] ) ? floatval( $data[6] ) : 0,
                'kurz_2' => isset( $data[7] ) ? floatval( $data[7] ) : 0,
                'datum' => isset( $data[8] ) ? $data[8] : '',
            );

            // Parse teams from match name
            $teams = explode( ' - ', $row['zapas'] );
            $row['team_home'] = isset( $teams[0] ) ? trim( $teams[0] ) : '';
            $row['team_away'] = isset( $teams[1] ) ? trim( $teams[1] ) : '';

            // Determine match status
            if ( $match_status === 'auto' ) {
                if ( $row['vysledek_1'] >= 0 && $row['vysledek_2'] >= 0 ) {
                    $row['status'] = 'ukonceny'; // Completed
                } else {
                    // Determine if match is in future or past
                    $match_date = $this->parse_match_date( $row['datum'] );
                    if ( $match_date && $match_date > current_time( 'timestamp' ) ) {
                        $row['status'] = 'planovany'; // Scheduled
                    } else {
                        $row['status'] = 'probihajici'; // In progress
                    }
                }
            } else {
                // Map legacy status values to actual ACF values if needed
                switch ($match_status) {
                    case 'completed':
                        $row['status'] = 'ukonceny';
                        break;
                    case 'scheduled':
                        $row['status'] = 'planovany';
                        break;
                    case 'in_progress':
                        $row['status'] = 'probihajici';
                        break;
                    default:
                        $row['status'] = $match_status; // Use as-is if already using ACF values
                }
            }

            // Determine if match already exists
            $existing_match = $this->find_existing_match( $row['team_home'], $row['team_away'], $row['datum'] );
            $row['exists'] = $existing_match ? true : false;
            $row['match_id'] = $existing_match ? $existing_match->ID : 0;

            // Skip based on import mode
            if ( $import_mode === 'add_only' && $row['exists'] ) {
                $row['action'] = 'skip';
            } elseif ( $import_mode === 'update_only' && ! $row['exists'] ) {
                $row['action'] = 'skip';
            } else {
                $row['action'] = $row['exists'] ? 'update' : 'add';
            }
            
            // Add row index for selection
            $row['index'] = $row_index;
            
            // Default selected status (all selected by default)
            $row['selected'] = true;

            $rows[] = $row;
            $row_index++;
        }

        fclose( $file );

        // Prepare preview HTML
        $html = '<div class="import-summary">';
        $html .= '<p><strong>' . __( 'Soutěž:', 'tipnijinak' ) . '</strong> ' . esc_html( $competition_name ) . '</p>';
        $html .= '<p><strong>' . __( 'Kolo:', 'tipnijinak' ) . '</strong> ' . esc_html( $round_name ) . '</p>';
        $html .= '<p><strong>' . __( 'Režim importu:', 'tipnijinak' ) . '</strong> ' . $this->get_import_mode_label( $import_mode ) . '</p>';
        $html .= '<p><strong>' . __( 'Stav zápasů:', 'tipnijinak' ) . '</strong> ' . $this->get_match_status_label( $match_status ) . '</p>';
        
        $add_count = count( array_filter( $rows, function( $row ) { return $row['action'] === 'add'; } ) );
        $update_count = count( array_filter( $rows, function( $row ) { return $row['action'] === 'update'; } ) );
        $skip_count = count( array_filter( $rows, function( $row ) { return $row['action'] === 'skip'; } ) );
        
        $html .= '<p><strong>' . __( 'Celkem zápasů:', 'tipnijinak' ) . '</strong> ' . count( $rows ) . '</p>';
        $html .= '<p><strong>' . __( 'Nové zápasy:', 'tipnijinak' ) . '</strong> ' . $add_count . '</p>';
        $html .= '<p><strong>' . __( 'Aktualizace zápasů:', 'tipnijinak' ) . '</strong> ' . $update_count . '</p>';
        $html .= '<p><strong>' . __( 'Přeskočené zápasy:', 'tipnijinak' ) . '</strong> ' . $skip_count . '</p>';
        $html .= '</div>';
        
        $html .= '<div class="select-actions">';
        $html .= '<h3 class="selection-heading">' . __( 'Vyberte zápasy k importu:', 'tipnijinak' ) . '</h3>';
        
        $html .= '<div class="select-actions-main">';
        $html .= '<button type="button" id="select-all" class="button">' . __( 'Vybrat vše', 'tipnijinak' ) . '</button>';
        $html .= '<button type="button" id="deselect-all" class="button">' . __( 'Odznačit vše', 'tipnijinak' ) . '</button>';
        
        $html .= '<div class="filter-separator"></div>';
        
        $html .= '<button type="button" id="select-add" class="button">' . __( 'Vybrat jen nové', 'tipnijinak' ) . '</button>';
        $html .= '<button type="button" id="select-update" class="button">' . __( 'Vybrat jen aktualizace', 'tipnijinak' ) . '</button>';
        $html .= '<button type="button" id="select-with-result" class="button">' . __( 'Vybrat s výsledkem', 'tipnijinak' ) . '</button>';
        $html .= '<button type="button" id="select-without-result" class="button">' . __( 'Vybrat bez výsledku', 'tipnijinak' ) . '</button>';
        
        $html .= '</div>';
        
        $html .= '<span class="selected-count">' . count($rows) . ' / ' . count($rows) . '</span>';
        $html .= '<p class="selection-tip">' . __( 'Tip: Kliknutím na řádek můžete zápas vybrat/odznačit', 'tipnijinak' ) . '</p>';
        $html .= '</div>';
        
        $html .= '<table class="wp-list-table widefat fixed striped">';
        $html .= '<thead><tr>';
        $html .= '<th class="check-column"><input type="checkbox" id="select-all-toggle" checked></th>';
        $html .= '<th>' . __( 'Akce', 'tipnijinak' ) . '</th>';
        $html .= '<th>' . __( 'Liga', 'tipnijinak' ) . '</th>';
        $html .= '<th>' . __( 'Zápas', 'tipnijinak' ) . '</th>';
        $html .= '<th>' . __( 'Výsledek', 'tipnijinak' ) . '</th>';
        $html .= '<th>' . __( 'Datum', 'tipnijinak' ) . '</th>';
        $html .= '<th>' . __( 'Kurzy (1-0-2)', 'tipnijinak' ) . '</th>';
        $html .= '</tr></thead>';
        
        $html .= '<tbody>';
        foreach ( $rows as $row ) {
            $result = $row['vysledek_1'] >= 0 && $row['vysledek_2'] >= 0 
                ? $row['vysledek_1'] . ' : ' . $row['vysledek_2'] 
                : __( 'Zatím nehráno', 'tipnijinak' );
            
            $action_class = '';
            $action_label = '';
            
            switch ( $row['action'] ) {
                case 'add':
                    $action_class = 'add';
                    $action_label = __( 'Přidat', 'tipnijinak' );
                    break;
                case 'update':
                    $action_class = 'update';
                    $action_label = __( 'Aktualizovat', 'tipnijinak' );
                    break;
                case 'skip':
                    $action_class = 'skip';
                    $action_label = __( 'Přeskočit', 'tipnijinak' );
                    break;
            }
            
            // Determine if row has result or not
            $has_result = ($row['vysledek_1'] >= 0 && $row['vysledek_2'] >= 0);
            $result_class = $has_result ? 'has-result' : 'no-result';
            
            $html .= '<tr data-index="' . $row['index'] . '" data-action="' . $row['action'] . '" data-has-result="' . ($has_result ? 'true' : 'false') . '" class="' . $result_class . '">';
            $html .= '<td class="check-column"><input type="checkbox" class="row-select-checkbox" data-index="' . $row['index'] . '" ' . checked( $row['selected'], true, false ) . '></td>';
            $html .= '<td class="action-' . $action_class . '">' . $action_label . '</td>';
            $html .= '<td>' . esc_html( $row['liga'] ) . '</td>';
            $html .= '<td>' . esc_html( $row['zapas'] ) . '</td>';
            $html .= '<td>' . esc_html( $result ) . '</td>';
            $html .= '<td>' . esc_html( $row['datum'] ) . '</td>';
            $html .= '<td>' . esc_html( $row['kurz_1'] . ' - ' . $row['kurz_0'] . ' - ' . $row['kurz_2'] ) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        
        // Store data in temporary file for processing
        $temp_file = get_temp_dir() . 'match_import_' . md5( current_time( 'timestamp' ) . wp_rand() ) . '.json';
        $temp_data = array(
            'competition_id' => $competition_id,
            'round_id' => $round_id,
            'import_mode' => $import_mode,
            'match_status' => $match_status,
            'rows' => $rows,
        );
        
        file_put_contents( $temp_file, json_encode( $temp_data ) );
        
        wp_send_json_success( array(
            'html' => $html,
            'temp_file' => basename( $temp_file ),
            'rows' => $rows, // Posíláme data pro client-side manipulaci
            'stats' => array(
                'total' => count( $rows ),
                'add' => $add_count,
                'update' => $update_count,
                'skip' => $skip_count,
            ),
        ) );
    }

    /**
     * Ajax handler for processing import (legacy method - now redirects to batch processing)
     */
    public function ajax_process_csv_import() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'match-import-nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Bezpečnostní kontrola selhala.', 'tipnijinak' ) ) );
        }

        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Nemáte oprávnění k této akci.', 'tipnijinak' ) ) );
        }

        // Check if temp file exists
        if ( ! isset( $_POST['temp_file'] ) || empty( $_POST['temp_file'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Chybí data pro import.', 'tipnijinak' ) ) );
        }

        // For large imports, redirect to the batch processing
        $temp_file = get_temp_dir() . sanitize_file_name( $_POST['temp_file'] );
        
        if ( ! file_exists( $temp_file ) ) {
            wp_send_json_error( array( 'message' => __( 'Dočasný soubor neexistuje.', 'tipnijinak' ) ) );
        }

        // Get import data
        $import_data = json_decode( file_get_contents( $temp_file ), true );
        
        if ( ! $import_data || ! isset( $import_data['rows'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Neplatná data pro import.', 'tipnijinak' ) ) );
        }
        
        // Get selected rows if they were passed
        $selected_rows = array();
        if (isset($_POST['selected_rows']) && !empty($_POST['selected_rows'])) {
            $selected_rows = array_map('intval', $_POST['selected_rows']);
        }
        
        // Calculate total items to process
        $total_to_process = 0;
        foreach ($import_data['rows'] as $row) {
            if (empty($selected_rows) || in_array($row['index'], $selected_rows)) {
                if ($row['action'] !== 'skip') {
                    $total_to_process++;
                }
            }
        }
        
        // Initialize batch processing data
        $batch_data = array(
            'temp_file' => $_POST['temp_file'],
            'selected_rows' => $selected_rows,
            'total_items' => $total_to_process,
            'processed_items' => 0,
            'batch_size' => 5, // Process only 5 items per batch - reduced to avoid timeouts
            'results' => array(
                'added' => 0,
                'updated' => 0,
                'skipped' => 0,
                'failed' => 0,
                'total' => $total_to_process,
                'details' => array(),
            )
        );
        
        // Store batch data in a temporary file
        $batch_file = get_temp_dir() . 'batch_import_' . md5(current_time('timestamp') . wp_rand()) . '.json';
        file_put_contents($batch_file, json_encode($batch_data));
        
        // Return batch processing info to start the process
        wp_send_json_success(array(
            'batch_mode' => true,
            'batch_file' => basename($batch_file),
            'total_items' => $total_to_process,
            'message' => sprintf(__('Začíná dávkový import %d položek...', 'tipnijinak'), $total_to_process),
        ));
    }
    
    /**
     * Ajax handler for batch processing import
     */
    public function ajax_process_batch_import() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'match-import-nonce')) {
            wp_send_json_error(array('message' => __('Bezpečnostní kontrola selhala.', 'tipnijinak')));
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Nemáte oprávnění k této akci.', 'tipnijinak')));
        }

        // Check if batch file exists
        if (!isset($_POST['batch_file']) || empty($_POST['batch_file'])) {
            wp_send_json_error(array('message' => __('Chybí data pro dávkové zpracování.', 'tipnijinak')));
        }

        $batch_file = get_temp_dir() . sanitize_file_name($_POST['batch_file']);
        
        if (!file_exists($batch_file)) {
            wp_send_json_error(array('message' => __('Dočasný soubor dávkového zpracování neexistuje.', 'tipnijinak')));
        }

        // Get batch data
        $batch_data = json_decode(file_get_contents($batch_file), true);
        
        if (!$batch_data) {
            wp_send_json_error(array('message' => __('Neplatná data pro dávkové zpracování.', 'tipnijinak')));
        }
        
        // Get temp file with import data
        $temp_file = get_temp_dir() . sanitize_file_name($batch_data['temp_file']);
        
        if (!file_exists($temp_file)) {
            wp_send_json_error(array('message' => __('Dočasný soubor importu neexistuje.', 'tipnijinak')));
        }

        // Get import data
        $import_data = json_decode(file_get_contents($temp_file), true);
        
        if (!$import_data || !isset($import_data['rows'])) {
            wp_send_json_error(array('message' => __('Neplatná data pro import.', 'tipnijinak')));
        }
        
        // Get selected rows
        $selected_rows = $batch_data['selected_rows'];
        
        // Calculate start position and process a batch
        $start_position = $batch_data['processed_items'];
        $batch_size = $batch_data['batch_size'];
        $processed = 0;
        $items_processed = array();
        
        // Safety measure: Track execution time to avoid timeout
        $max_execution_time = 30; // 30 seconds max per batch
        $start_time = microtime(true);
        $time_safety_margin = 5; // 5 seconds safety margin

        foreach ($import_data['rows'] as $row) {
            // Skip rows that are not selected or already marked for skipping
            if ((!empty($selected_rows) && !in_array($row['index'], $selected_rows)) || $row['action'] === 'skip') {
                continue;
            }
            
            // Skip rows until we reach our current position
            if ($processed < $start_position) {
                $processed++;
                continue;
            }
            
            // Stop if we've processed enough for this batch or approaching time limit
            if ($processed >= $start_position + $batch_size) {
                break;
            }
            
            // Check if we're approaching the execution time limit
            $current_time = microtime(true);
            $elapsed_time = $current_time - $start_time;
            if ($elapsed_time > ($max_execution_time - $time_safety_margin)) {
                error_log('Batch processing approaching time limit. Processed: ' . ($processed - $start_position) . ' items in ' . $elapsed_time . ' seconds.');
                break; // Stop processing to avoid timeout
            }
            
            // Process this row
            $processed++;
            
            // Determine if updating or adding
            $match_id = 0;
            $success = false;
            $message = '';

            if ($row['action'] === 'update' && $row['match_id']) {
                // Update existing match
                $match_id = $row['match_id'];
                $update_result = $this->update_match($match_id, $row, $import_data['competition_id'], $import_data['round_id']);
                
                if ($update_result['success']) {
                    $batch_data['results']['updated']++;
                    $success = true;
                    $message = __('Zápas byl úspěšně aktualizován.', 'tipnijinak');
                } else {
                    $batch_data['results']['failed']++;
                    $success = false;
                    $message = $update_result['message'];
                }
            } else {
                // Add new match
                $add_result = $this->add_match($row, $import_data['competition_id'], $import_data['round_id']);
                
                if ($add_result['success']) {
                    $batch_data['results']['added']++;
                    $match_id = $add_result['match_id'];
                    $success = true;
                    $message = __('Zápas byl úspěšně přidán.', 'tipnijinak');
                } else {
                    $batch_data['results']['failed']++;
                    $success = false;
                    $message = $add_result['message'];
                }
            }

            $batch_data['results']['details'][] = array(
                'match' => $row['zapas'],
                'action' => $row['action'],
                'status' => $success ? 'success' : 'error',
                'message' => $message,
                'match_id' => $match_id,
            );
            
            $items_processed[] = $row['zapas'];
        }
        
        // Update processed count
        $batch_data['processed_items'] = $processed;
        
        // Determine if we're done
        $is_completed = ($batch_data['processed_items'] >= $batch_data['total_items']);
        
        // Update batch file
        file_put_contents($batch_file, json_encode($batch_data));
        
        if ($is_completed) {
            // If complete, prepare final HTML and clean up
            $results = $batch_data['results'];
            
            // Prepare results HTML
            $html = '<div class="import-results-summary">';
            $html .= '<p><strong>' . __('Celkem zpracováno:', 'tipnijinak') . '</strong> ' . $results['total'] . '</p>';
            $html .= '<p><strong>' . __('Přidáno:', 'tipnijinak') . '</strong> ' . $results['added'] . '</p>';
            $html .= '<p><strong>' . __('Aktualizováno:', 'tipnijinak') . '</strong> ' . $results['updated'] . '</p>';
            $html .= '<p><strong>' . __('Přeskočeno:', 'tipnijinak') . '</strong> ' . $results['skipped'] . '</p>';
            $html .= '<p><strong>' . __('Selhalo:', 'tipnijinak') . '</strong> ' . $results['failed'] . '</p>';
            $html .= '</div>';
            
            if (!empty($results['details'])) {
                $html .= '<table class="wp-list-table widefat fixed striped">';
                $html .= '<thead><tr>';
                $html .= '<th>' . __('Zápas', 'tipnijinak') . '</th>';
                $html .= '<th>' . __('Akce', 'tipnijinak') . '</th>';
                $html .= '<th>' . __('Výsledek', 'tipnijinak') . '</th>';
                $html .= '<th>' . __('Zpráva', 'tipnijinak') . '</th>';
                $html .= '</tr></thead>';
                
                $html .= '<tbody>';
                foreach ($results['details'] as $detail) {
                    $action_label = '';
                    switch ($detail['action']) {
                        case 'add':
                            $action_label = __('Přidání', 'tipnijinak');
                            break;
                        case 'update':
                            $action_label = __('Aktualizace', 'tipnijinak');
                            break;
                        case 'skip':
                            $action_label = __('Přeskočení', 'tipnijinak');
                            break;
                    }
                    
                    $status_label = $detail['status'] === 'success' 
                        ? __('Úspěch', 'tipnijinak') 
                        : __('Chyba', 'tipnijinak');
                    
                    $html .= '<tr class="' . esc_attr($detail['status']) . '">';
                    $html .= '<td>' . esc_html($detail['match']) . '</td>';
                    $html .= '<td>' . esc_html($action_label) . '</td>';
                    $html .= '<td>' . esc_html($status_label) . '</td>';
                    $html .= '<td>' . esc_html($detail['message']) . '</td>';
                    $html .= '</tr>';
                }
                $html .= '</tbody></table>';
            }

            // Cleanup temp files
            @unlink($temp_file);
            @unlink($batch_file);
            
            wp_send_json_success(array(
                'is_completed' => true,
                'html' => $html,
                'stats' => array(
                    'total' => $results['total'],
                    'added' => $results['added'],
                    'updated' => $results['updated'],
                    'skipped' => $results['skipped'],
                    'failed' => $results['failed'],
                ),
            ));
        } else {
            // If not complete, return progress info
            $percent_complete = round(($batch_data['processed_items'] / $batch_data['total_items']) * 100);
            
            wp_send_json_success(array(
                'is_completed' => false,
                'batch_file' => basename($batch_file),
                'processed' => $batch_data['processed_items'],
                'total' => $batch_data['total_items'],
                'percent' => $percent_complete,
                'items_processed' => $items_processed,
                'message' => sprintf(__('Zpracováno %d z %d položek (%d%%)...', 'tipnijinak'), 
                    $batch_data['processed_items'], 
                    $batch_data['total_items'],
                    $percent_complete
                ),
            ));
        }
    }

    /**
     * Find existing match by title pattern, team names and date
     */
    private function find_existing_match( $team_home, $team_away, $date_string ) {
        // Parse date
        $match_date = $this->parse_match_date( $date_string );
        if ( !$match_date ) {
            return false;
        }
        
        // First try by exact title match for most accuracy - using team names and date
        $match_name = $team_home . ' - ' . $team_away;
        $date_formatted = date('d.m.Y H:i', $match_date);
        $unique_title = $match_name . ' | ' . $date_formatted;
        
        // Try to find by exact title
        $exact_args = array(
            'post_type' => 'zapas',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'title' => $unique_title,
            'exact' => true
        );
        
        $exact_query = new WP_Query( $exact_args );
        
        if ( $exact_query->have_posts() ) {
            return $exact_query->posts[0];
        }
        
        // If not found by exact title, try a more flexible approach: team names and same day
        $date_obj = new DateTime();
        $date_obj->setTimestamp( $match_date );
        
        // Create date query with some flexibility (same day)
        $date_start = clone $date_obj;
        $date_start->setTime( 0, 0, 0 );
        
        $date_end = clone $date_obj;
        $date_end->setTime( 23, 59, 59 );
        
        $date_query = array(
            'after' => $date_start->format( 'Y-m-d H:i:s' ),
            'before' => $date_end->format( 'Y-m-d H:i:s' ),
            'inclusive' => true,
        );
        
        // Query for matching post by meta values
        $args = array(
            'post_type' => 'zapas',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => 'domaci_tym',
                    'value' => $team_home,
                    'compare' => '='
                ),
                array(
                    'key' => 'hoste_tym',
                    'value' => $team_away,
                    'compare' => '='
                )
            ),
            'date_query' => $date_query
        );
        
        $query = new WP_Query( $args );
        
        if ( $query->have_posts() ) {
            return $query->posts[0];
        }
        
        // If still not found, check for title containing the team names without the date
        $title_args = array(
            'post_type' => 'zapas',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            's' => $match_name, // Search for the match name in title
            'date_query' => $date_query // Restrict to same day
        );
        
        $title_query = new WP_Query( $title_args );
        
        if ( $title_query->have_posts() ) {
            return $title_query->posts[0];
        }
        
        return false;
    }

    /**
     * Add new match
     */
    private function add_match( $data, $competition_id, $round_id ) {
        // Parse date
        $match_date = $this->parse_match_date( $data['datum'] );
        if ( ! $match_date ) {
            return array(
                'success' => false,
                'message' => __( 'Neplatný formát data.', 'tipnijinak' ),
            );
        }
        
        // Find or create teams with liga assignment
        $home_team_id = $this->find_or_create_team($data['team_home'], $data['liga']);
        $away_team_id = $this->find_or_create_team($data['team_away'], $data['liga']);
        
        // Create unique title with team names and date
        $date_formatted = date('d.m.Y H:i', $match_date);
        $unique_title = $data['zapas'] . ' | ' . $date_formatted;
        
        // Create post for match
        $post_data = array(
            'post_title' => $unique_title,
            'post_type' => 'zapas',
            'post_status' => 'publish',
            'post_date' => date( 'Y-m-d H:i:s', $match_date ),
        );
        
        $match_id = wp_insert_post( $post_data );
        
        if ( is_wp_error( $match_id ) ) {
            return array(
                'success' => false,
                'message' => $match_id->get_error_message(),
            );
        }
        
        // Add meta data
        update_post_meta( $match_id, 'liga', $data['liga'] );
        update_post_meta( $match_id, 'domaci_tym', $data['team_home'] );
        update_post_meta( $match_id, 'hoste_tym', $data['team_away'] );
        
        // Add team post references
        if ($home_team_id) {
            // Standard meta field
            update_post_meta($match_id, 'domaci_tym_id', $home_team_id);
            
            // ACF relationship fields
            if (function_exists('update_field')) {
                // Both the field name and the ID referencing approach
                update_field('domaci_tym_id', $home_team_id, $match_id);
                update_field('domaci_tym', $home_team_id, $match_id);
            }
        }
        
        if ($away_team_id) {
            // Standard meta field
            update_post_meta($match_id, 'hoste_tym_id', $away_team_id);
            
            // ACF relationship fields
            if (function_exists('update_field')) {
                // Both the field name and the ID referencing approach
                update_field('hoste_tym_id', $away_team_id, $match_id);
                update_field('hoste_tym', $away_team_id, $match_id);
            }
        }
        
        update_post_meta( $match_id, 'odkaz', $data['link'] );
        update_post_meta( $match_id, 'datum_zapasu', date( 'Y-m-d H:i:s', $match_date ) );
        update_post_meta( $match_id, 'stav_zapasu', $data['status'] );
        
        // Set scores if available
        if ( $data['vysledek_1'] >= 0 && $data['vysledek_2'] >= 0 ) {
            update_post_meta( $match_id, 'skore_domaci', $data['vysledek_1'] );
            update_post_meta( $match_id, 'skore_hoste', $data['vysledek_2'] );
        }
        
        // Set odds properly for ACF
        if ( function_exists( 'update_field' ) ) {
            // Add ACF kurzy fields as group
            $kurzy = array(
                'kurz_domaci' => $data['kurz_1'],
                'kurz_remiza' => $data['kurz_0'],
                'kurz_hoste' => $data['kurz_2']
            );
            update_field( 'kurzy', $kurzy, $match_id );
        } else {
            // Legacy fields for backwards compatibility
            update_post_meta( $match_id, 'kurz_1', $data['kurz_1'] );
            update_post_meta( $match_id, 'kurz_0', $data['kurz_0'] );
            update_post_meta( $match_id, 'kurz_2', $data['kurz_2'] );
        }
        
        // Assign liga taxonomy
        $this->assign_liga_taxonomy($match_id, $data['liga']);
        
        // Connect to competition and round if provided
        if ( $competition_id ) {
            update_post_meta( $match_id, 'soutez', $competition_id );
        }
        
        if ( $round_id ) {
            // Standard meta connection
            update_post_meta( $match_id, 'kolo', $round_id );
            
            // Add match to round's relationship field 'zapasy_kola' if ACF is available
            if ( function_exists( 'update_field' ) ) {
                // Get existing matches in the round
                $existing_matches = get_field('zapasy_kola', $round_id);
                
                // If no existing matches or not an array, initialize as empty array
                if (!is_array($existing_matches)) {
                    $existing_matches = array();
                }
                
                // Add this match ID if not already in the list
                if (!in_array($match_id, $existing_matches)) {
                    $existing_matches[] = $match_id;
                    update_field('zapasy_kola', $existing_matches, $round_id);
                    error_log('Added match ' . $match_id . ' to round ' . $round_id . ' zapasy_kola relationship');
                }
            }
        }
        
        return array(
            'success' => true,
            'match_id' => $match_id,
        );
    }

    /**
     * Update existing match
     */
    private function update_match( $match_id, $data, $competition_id, $round_id ) {
        // Parse date
        $match_date = $this->parse_match_date( $data['datum'] );
        
        // Update post data if date is valid
        if ( $match_date ) {
            // Create unique title with team names and date
            $date_formatted = date('d.m.Y H:i', $match_date);
            $unique_title = $data['zapas'] . ' | ' . $date_formatted;
            
            wp_update_post( array(
                'ID' => $match_id,
                'post_title' => $unique_title,
                'post_date' => date( 'Y-m-d H:i:s', $match_date ),
            ) );
            
            update_post_meta( $match_id, 'datum_zapasu', date( 'Y-m-d H:i:s', $match_date ) );
        }
        
        // Find or create teams with liga assignment
        $home_team_id = $this->find_or_create_team($data['team_home'], $data['liga']);
        $away_team_id = $this->find_or_create_team($data['team_away'], $data['liga']);
        
        // Update all meta fields
        update_post_meta( $match_id, 'liga', $data['liga'] );
        update_post_meta( $match_id, 'odkaz', $data['link'] );
        update_post_meta( $match_id, 'stav_zapasu', $data['status'] );
        
        // Update team posts references
        if ($home_team_id) {
            // Standard meta field
            update_post_meta($match_id, 'domaci_tym_id', $home_team_id);
            
            // ACF relationship fields
            if (function_exists('update_field')) {
                // Both the field name and the ID referencing approach
                update_field('domaci_tym_id', $home_team_id, $match_id);
                update_field('domaci_tym', $home_team_id, $match_id);
            }
        }
        
        if ($away_team_id) {
            // Standard meta field
            update_post_meta($match_id, 'hoste_tym_id', $away_team_id);
            
            // ACF relationship fields
            if (function_exists('update_field')) {
                // Both the field name and the ID referencing approach
                update_field('hoste_tym_id', $away_team_id, $match_id);
                update_field('hoste_tym', $away_team_id, $match_id);
            }
        }
        
        // Update scores if available and match is completed
        if ( $data['vysledek_1'] >= 0 && $data['vysledek_2'] >= 0 ) {
            update_post_meta( $match_id, 'skore_domaci', $data['vysledek_1'] );
            update_post_meta( $match_id, 'skore_hoste', $data['vysledek_2'] );
        }
        
        // Set odds properly for ACF
        if ( function_exists( 'update_field' ) ) {
            // Add ACF kurzy fields as group
            $kurzy = array(
                'kurz_domaci' => $data['kurz_1'],
                'kurz_remiza' => $data['kurz_0'],
                'kurz_hoste' => $data['kurz_2']
            );
            update_field( 'kurzy', $kurzy, $match_id );
        } else {
            // Legacy fields for backwards compatibility
            update_post_meta( $match_id, 'kurz_1', $data['kurz_1'] );
            update_post_meta( $match_id, 'kurz_0', $data['kurz_0'] );
            update_post_meta( $match_id, 'kurz_2', $data['kurz_2'] );
        }
        
        // Assign liga taxonomy
        $this->assign_liga_taxonomy($match_id, $data['liga']);
        
        // Connect to competition and round if provided
        if ( $competition_id ) {
            update_post_meta( $match_id, 'soutez', $competition_id );
        }
        
        if ( $round_id ) {
            // Standard meta connection
            update_post_meta( $match_id, 'kolo', $round_id );
            
            // Add match to round's relationship field 'zapasy_kola' if ACF is available
            if ( function_exists( 'update_field' ) ) {
                // Get existing matches in the round
                $existing_matches = get_field('zapasy_kola', $round_id);
                
                // If no existing matches or not an array, initialize as empty array
                if (!is_array($existing_matches)) {
                    $existing_matches = array();
                }
                
                // Add this match ID if not already in the list
                if (!in_array($match_id, $existing_matches)) {
                    $existing_matches[] = $match_id;
                    update_field('zapasy_kola', $existing_matches, $round_id);
                    error_log('Added match ' . $match_id . ' to round ' . $round_id . ' zapasy_kola relationship');
                }
            }
        }
        
        return array(
            'success' => true,
        );
    }

    /**
     * Parse match date from string
     */
    private function parse_match_date( $date_string ) {
        // Expected format: "DD.MM. HH:MM"
        if ( empty( $date_string ) ) {
            return false;
        }
        
        // Split date and time
        $parts = explode( ' ', $date_string );
        if ( count( $parts ) !== 2 ) {
            return false;
        }
        
        $date_part = $parts[0];
        $time_part = $parts[1];
        
        // Parse date part (DD.MM.)
        if ( ! preg_match( '/^(\d{1,2})\.(\d{1,2})\./', $date_part, $date_matches ) ) {
            return false;
        }
        
        $day = intval( $date_matches[1] );
        $month = intval( $date_matches[2] );
        
        // Use current year, but handle December -> January transition
        $current_month = intval( date( 'n' ) );
        $year = intval( date( 'Y' ) );
        
        if ( $month < $current_month && $current_month > 10 ) {
            $year++; // It's likely next year
        }
        
        // Parse time part (HH:MM)
        if ( ! preg_match( '/^(\d{1,2}):(\d{1,2})$/', $time_part, $time_matches ) ) {
            return false;
        }
        
        $hour = intval( $time_matches[1] );
        $minute = intval( $time_matches[2] );
        
        // Create timestamp
        return mktime( $hour, $minute, 0, $month, $day, $year );
    }

    /**
     * Get human-readable import mode label
     */
    private function get_import_mode_label( $mode ) {
        $labels = array(
            'add_update' => __( 'Přidat nové a aktualizovat existující', 'tipnijinak' ),
            'add_only' => __( 'Přidat pouze nové (přeskočit existující)', 'tipnijinak' ),
            'update_only' => __( 'Aktualizovat pouze existující (přeskočit nové)', 'tipnijinak' ),
        );
        
        return isset( $labels[$mode] ) ? $labels[$mode] : $mode;
    }

    /**
     * Get human-readable match status label
     */
    private function get_match_status_label( $status ) {
        $labels = array(
            'auto' => __( 'Automaticky (podle výsledku)', 'tipnijinak' ),
            'planovany' => __( 'Plánovaný', 'tipnijinak' ),
            'probihajici' => __( 'Probíhající', 'tipnijinak' ),
            'ukonceny' => __( 'Ukončený', 'tipnijinak' ),
            'zrusen' => __( 'Zrušený', 'tipnijinak' ),
            // Legacy values for backward compatibility
            'scheduled' => __( 'Plánovaný', 'tipnijinak' ),
            'in_progress' => __( 'Probíhající', 'tipnijinak' ),
            'completed' => __( 'Ukončený', 'tipnijinak' ),
        );
        
        return isset( $labels[$status] ) ? $labels[$status] : $status;
    }
    
    /**
     * Ajax handler for getting competition rounds
     */
    public function ajax_get_competition_rounds() {
        // Přidáme logování pro diagnostiku
        error_log('ajax_get_competition_rounds called with: ' . print_r($_POST, true));
        
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'match-import-nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Bezpečnostní kontrola selhala.', 'tipnijinak' ) ) );
        }

        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Nemáte oprávnění k této akci.', 'tipnijinak' ) ) );
        }

        // Check if competition ID is provided
        if ( ! isset( $_POST['competition_id'] ) || empty( $_POST['competition_id'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Chybí ID soutěže.', 'tipnijinak' ) ) );
        }

        $competition_id = intval( $_POST['competition_id'] );
        $create_rounds = isset( $_POST['create_rounds'] ) && filter_var( $_POST['create_rounds'], FILTER_VALIDATE_BOOLEAN );

        // Get rounds for this competition
        $rounds = array();
        
        // Nejdříve zkusíme získat všechna kola bez ohledu na soutěž
        $all_rounds = get_posts(array(
            'post_type' => 'kolo',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        error_log('Found ' . count($all_rounds) . ' total rounds');
        
        // First try to get rounds via ACF relationship field (if ACF is active)
        if ( function_exists('get_field') ) {
            // Správný field je 'kola_souteze' u soutěže
            $kola_ids = get_field('kola_souteze', $competition_id);
            error_log('ACF kola_souteze for competition ' . $competition_id . ': ' . print_r($kola_ids, true));
            
            if (!empty($kola_ids) && is_array($kola_ids)) {
                foreach ($kola_ids as $kolo_id) {
                    // Získáme data o každém kole
                    $kolo = get_post($kolo_id);
                    if ($kolo && $kolo->post_type === 'kolo') {
                        $rounds[] = array(
                            'id' => $kolo->ID,
                            'name' => $kolo->post_title,
                            'number' => get_post_meta($kolo->ID, 'cislo_kola', true)
                        );
                    }
                }
            }
        }
        
        // If no rounds found via ACF or ACF not active, try the standard meta query
        if (empty($rounds)) {
            // Zkontrolujeme, zda existuje meta 'kola_souteze' pro post
            $kola_souteze_meta = get_post_meta($competition_id, 'kola_souteze', true);
            error_log('Meta kola_souteze for competition ' . $competition_id . ': ' . print_r($kola_souteze_meta, true));
            
            // Pokud je meta hodnota pole IDs
            if (!empty($kola_souteze_meta) && is_array($kola_souteze_meta)) {
                foreach ($kola_souteze_meta as $kolo_id) {
                    $kolo = get_post($kolo_id);
                    if ($kolo && $kolo->post_type === 'kolo') {
                        $rounds[] = array(
                            'id' => $kolo->ID,
                            'name' => $kolo->post_title,
                            'number' => get_post_meta($kolo->ID, 'cislo_kola', true)
                        );
                    }
                }
            }
            
            // Zkusíme ještě legacy způsob, kde kola mají meta 'soutez' s hodnotou ID soutěže
            if (empty($rounds)) {
                $round_posts = get_posts( array(
                    'post_type' => 'kolo',
                    'posts_per_page' => -1,
                    'meta_query' => array(
                        array(
                            'key' => 'soutez',
                            'value' => $competition_id,
                            'compare' => '='
                        )
                    ),
                    'orderby' => 'meta_value_num',
                    'meta_key' => 'cislo_kola',
                    'order' => 'ASC'
                ) );

                error_log('Found ' . count($round_posts) . ' rounds for competition ' . $competition_id . ' using meta query');

                foreach ( $round_posts as $round_post ) {
                    $rounds[] = array(
                        'id' => $round_post->ID,
                        'name' => $round_post->post_title,
                        'number' => get_post_meta( $round_post->ID, 'cislo_kola', true )
                    );
                }
            }
        }

        // Create kola only if explicitly requested - NENÍ potřeba automatické vytváření
        if ( $create_rounds ) {
            // Create default rounds (1-10)
            for ($i = 1; $i <= 10; $i++) {
                // Check if this round number already exists
                $exists = false;
                foreach ($rounds as $round) {
                    if (isset($round['number']) && $round['number'] == $i) {
                        $exists = true;
                        break;
                    }
                }
                
                // Skip if already exists
                if ($exists) {
                    continue;
                }
                
                $round_name = sprintf(__('Kolo %d', 'tipnijinak'), $i);
                
                // Create new round post
                $round_id = wp_insert_post(array(
                    'post_title' => $round_name,
                    'post_type' => 'kolo',
                    'post_status' => 'publish'
                ));
                
                if (!is_wp_error($round_id)) {
                    // Set round number
                    update_post_meta($round_id, 'cislo_kola', $i);
                    
                    // Connect to competition
                    update_post_meta($round_id, 'soutez', $competition_id);
                    
                    // Add to results
                    $rounds[] = array(
                        'id' => $round_id,
                        'name' => $round_name,
                        'number' => $i
                    );
                }
            }
            
            // Sort rounds by number
            usort($rounds, function($a, $b) {
                return $a['number'] - $b['number'];
            });
        }

        wp_send_json_success( array( 'rounds' => $rounds ) );
    }
    
    /**
     * Ajax handler for creating a single round
     */
    public function ajax_create_single_round() {
        // Debug log
        error_log('AJAX create_single_round called with: ' . print_r($_POST, true));
        
        // Check if post type 'kolo' exists
        if (!post_type_exists('kolo')) {
            error_log('ERROR: Post type "kolo" does not exist in the system!');
            wp_send_json_error( array( 'message' => 'Post type "kolo" neexistuje v systému. Kontaktujte administrátora.' ) );
            return;
        }
        
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'match-import-nonce' ) ) {
            error_log('Nonce verification failed');
            wp_send_json_error( array( 'message' => __( 'Bezpečnostní kontrola selhala.', 'tipnijinak' ) ) );
            return;
        }

        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            error_log('User lacks permissions');
            wp_send_json_error( array( 'message' => __( 'Nemáte oprávnění k této akci.', 'tipnijinak' ) ) );
            return;
        }

        // Check required fields
        $required_fields = array( 'competition_id', 'round_name', 'round_number', 'date_from', 'date_to', 'status' );
        foreach ( $required_fields as $field ) {
            if ( ! isset( $_POST[$field] ) || empty( $_POST[$field] ) ) {
                error_log('Missing required field: ' . $field);
                wp_send_json_error( array( 'message' => sprintf( __( 'Chybí povinné pole: %s', 'tipnijinak' ), $field ) ) );
                return;
            }
        }

        // Get and sanitize data
        $competition_id = intval( $_POST['competition_id'] );
        $round_name = sanitize_text_field( $_POST['round_name'] );
        $round_number = intval( $_POST['round_number'] );
        $date_from = sanitize_text_field( $_POST['date_from'] );
        $date_to = sanitize_text_field( $_POST['date_to'] );
        $status = sanitize_text_field( $_POST['status'] );
        
        // Convert HTML5 datetime-local format to WordPress format
        $date_from_wp = date('Y-m-d H:i:s', strtotime($date_from));
        $date_to_wp = date('Y-m-d H:i:s', strtotime($date_to));
        
        error_log('Creating round with data: ' . print_r([
            'title' => $round_name,
            'type' => 'kolo',
            'status' => 'publish'
        ], true));
        
        // Create new round post
        $round_args = array(
            'post_title' => $round_name,
            'post_type' => 'kolo',
            'post_status' => 'publish'
        );
        
        error_log('Attempting to create round with args: ' . print_r($round_args, true));
        
        // Check global $wp_post_types to verify kolo is registered
        global $wp_post_types;
        error_log('Registered post types: ' . implode(', ', array_keys($wp_post_types)));
        error_log('Is kolo registered properly? ' . (isset($wp_post_types['kolo']) ? 'Yes' : 'No'));
        
        // Try to create the post
        $round_id = wp_insert_post($round_args, true); // Second param true to get WP_Error
        
        if ( is_wp_error( $round_id ) ) {
            error_log('Error creating round: ' . $round_id->get_error_message());
            error_log('Error data: ' . print_r($round_id->get_error_data(), true));
            wp_send_json_error( array( 
                'message' => 'Chyba při vytváření kola: ' . $round_id->get_error_message(),
                'error_details' => $round_id->get_error_data()
            ));
            return;
        }
        
        if ( $round_id === 0 ) {
            error_log('Failed to create round, wp_insert_post returned 0');
            wp_send_json_error( array( 'message' => 'Nepodařilo se vytvořit kolo (wp_insert_post vrátil 0)' ) );
            return;
        }
        
        error_log('Round created successfully with ID: ' . $round_id);
        
        // Verify post was actually created
        $created_post = get_post($round_id);
        if (!$created_post) {
            error_log('ERROR: Post with ID ' . $round_id . ' does not exist after creation!');
            wp_send_json_error( array( 'message' => 'Kolo bylo vytvořeno s ID ' . $round_id . ', ale nelze ho najít v databázi.' ) );
            return;
        }
        error_log('Post verification: ' . print_r($created_post, true));
        
        // Set round meta data
        update_post_meta( $round_id, 'cislo_kola', $round_number );
        update_post_meta( $round_id, 'soutez', $competition_id );
        
        // Set ACF fields if function exists
        if ( function_exists( 'update_field' ) ) {
            update_field( 'cislo_kola', $round_number, $round_id );
            update_field( 'datum_od', $date_from_wp, $round_id );
            update_field( 'datum_do', $date_to_wp, $round_id );
            update_field( 'stav_kola', $status, $round_id );
            
            // The critical part - also update the competition's relationship field to include this round
            $existing_rounds = get_field('kola_souteze', $competition_id);
            if (!is_array($existing_rounds)) {
                $existing_rounds = array();
            }
            
            // Add this round ID if not already in the list
            if (!in_array($round_id, $existing_rounds)) {
                $existing_rounds[] = $round_id;
                update_field('kola_souteze', $existing_rounds, $competition_id);
                error_log('Added round ' . $round_id . ' to competition ' . $competition_id . ' kola_souteze relationship');
            }
        } else {
            // Fallback to regular post meta if ACF is not active
            update_post_meta( $round_id, 'datum_od', $date_from_wp );
            update_post_meta( $round_id, 'datum_do', $date_to_wp );
            update_post_meta( $round_id, 'stav_kola', $status );
        }
        
        // Return success response with round data
        $response_data = array(
            'round' => array(
                'id' => $round_id,
                'name' => $round_name,
                'number' => $round_number,
                'status' => $status,
                'date_from' => $date_from_wp,
                'date_to' => $date_to_wp
            )
        );
        
        error_log('Sending success response: ' . print_r($response_data, true));
        wp_send_json_success( $response_data );
    }
    
    /**
     * Assign liga taxonomy to match
     * 
     * @param int $match_id Match post ID
     * @param string $liga_slug Liga slug from CSV
     */
    // Cache for liga terms to avoid repeated lookups
    private $liga_term_cache = array();
    
    /**
     * Handle liga taxonomy assignment for a match
     * 
     * @param int $match_id Match post ID
     * @param string $liga_slug Liga slug
     * @param string $context Context for logging (add_match or update_match)
     */
    private function handle_match_liga_assignment($match_id, $liga_slug, $context = '') {
        if (!empty($liga_slug)) {
            $this->assign_liga_taxonomy($match_id, $liga_slug);
            error_log('Assigned liga: ' . $liga_slug . ' to match: ' . $match_id . ' (' . $context . ')');
        }
    }
    
    private function assign_liga_taxonomy($post_id, $liga_slug) {
        // Check if liga taxonomy exists
        if (!taxonomy_exists('liga')) {
            error_log('Liga taxonomy does not exist');
            return;
        }
        
        // Skip if liga slug is empty
        if (empty($liga_slug)) {
            return;
        }
        
        // Normalize liga slug
        $normalized_liga_slug = sanitize_title($liga_slug);
        
        // Check cache first
        if (isset($this->liga_term_cache[$normalized_liga_slug])) {
            $term_id = $this->liga_term_cache[$normalized_liga_slug];
            
            // Check if post already has this term
            $current_terms = wp_get_object_terms($post_id, 'liga', array('fields' => 'ids'));
            
            // If the post already has this term, no need to reassign
            if (!is_wp_error($current_terms) && in_array($term_id, $current_terms)) {
                return;
            }
            
            // Add the term - set to true to append to existing terms 
            $result = wp_set_object_terms($post_id, $term_id, 'liga', true);
            
            if (is_wp_error($result)) {
                error_log('Error assigning liga term: ' . $result->get_error_message());
            }
            
            return;
        }
        
        // Check if term exists
        $term = get_term_by('slug', $normalized_liga_slug, 'liga');
        
        if (!$term) {
            // Term doesn't exist, let's create it
            $liga_name = $this->get_pretty_liga_name($normalized_liga_slug);
            $term_result = wp_insert_term($liga_name, 'liga', array(
                'slug' => $normalized_liga_slug,
                'description' => sprintf(__('Liga %s', 'tipnijinak'), $liga_name)
            ));
            
            if (is_wp_error($term_result)) {
                error_log('Error creating liga term: ' . $term_result->get_error_message());
                return;
            }
            
            $term_id = $term_result['term_id'];
        } else {
            $term_id = $term->term_id;
        }
        
        // Cache the term ID
        $this->liga_term_cache[$normalized_liga_slug] = $term_id;
        
        // Add the term to post - set to true to append to existing terms
        $result = wp_set_object_terms($post_id, $term_id, 'liga', true);
        
        if (is_wp_error($result)) {
            error_log('Error assigning liga term: ' . $result->get_error_message());
        }
    }
    
    /**
     * Get pretty liga name from slug
     * 
     * @param string $liga_slug Liga slug
     * @return string Pretty liga name
     */
    private function get_pretty_liga_name($liga_slug) {
        // Define common liga name mappings
        $liga_names = array(
            'liga-mistru' => 'Liga mistrů',
            '1-cesko' => 'Česká liga',
            '1-anglie' => 'Premier League',
            '1-spanelsko' => 'La Liga',
            '1-italie' => 'Serie A',
            '1-nemecko' => 'Bundesliga',
            '1-francie' => 'Ligue 1',
            'evropska-liga' => 'Evropská liga',
        );
        
        // Return mapped name or prettified slug
        if (isset($liga_names[$liga_slug])) {
            return $liga_names[$liga_slug];
        }
        
        // Convert slug to pretty name
        $pretty_name = str_replace('-', ' ', $liga_slug);
        $pretty_name = ucwords($pretty_name);
        
        return $pretty_name;
    }
    
    /**
     * Find or create a team by name - with caching for performance
     * 
     * @param string $team_name Team name
     * @param string $liga_slug Liga slug for team's league (optional)
     * @return int Team post ID or 0 if unable to create
     */
    
    // Team cache to avoid repeated database lookups
    private $team_cache = array();
    private function find_or_create_team($team_name, $liga_slug = '') {
        // Trim and sanitize the team name
        $team_name = trim($team_name);
        
        if (empty($team_name)) {
            return 0;
        }
        
        // Check local cache first to avoid repeated database lookups
        $cache_key = md5($team_name);
        if (isset($this->team_cache[$cache_key])) {
            $team_id = $this->team_cache[$cache_key];
            
            // If liga_slug is provided and we already have the team, update its liga taxonomy
            if (!empty($liga_slug)) {
                $this->assign_liga_taxonomy($team_id, $liga_slug);
            }
            
            return $team_id;
        }
        
        // Try to find existing team by exact name
        $args = array(
            'post_type' => 'tym',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'title' => $team_name,
            'exact' => true,
            'no_found_rows' => true, // Performance optimization
            'update_post_meta_cache' => false, // Performance optimization
            'update_post_term_cache' => false, // Performance optimization
        );
        
        $existing_teams = get_posts($args);
        
        if (!empty($existing_teams)) {
            $team_id = $existing_teams[0]->ID;
            
            // Always check and update the team's liga taxonomy if provided
            if (!empty($liga_slug)) {
                // Normalize the liga slug for comparison
                $normalized_liga_slug = sanitize_title($liga_slug);
                
                // Check if team already has this liga assigned
                $has_this_liga = false;
                $current_ligas = wp_get_object_terms($team_id, 'liga', array('fields' => 'slugs'));
                
                if (!is_wp_error($current_ligas)) {
                    // Debug output
                    error_log('Team ' . $team_id . ' (' . $team_name . ') current ligas: ' . implode(', ', $current_ligas));
                    
                    // Properly compare normalized slugs
                    $has_this_liga = in_array($normalized_liga_slug, $current_ligas);
                }
                
                error_log('Team ' . $team_id . ' (' . $team_name . ') has liga ' . $normalized_liga_slug . '? ' . ($has_this_liga ? 'Yes' : 'No'));
                
                // Only assign if not already assigned
                if (!$has_this_liga) {
                    $this->assign_liga_taxonomy($team_id, $liga_slug);
                    error_log('Updated team ' . $team_id . ' (' . $team_name . ') with new liga: ' . $liga_slug);
                }
            }
            
            // Cache the result
            $this->team_cache[$cache_key] = $team_id;
            
            return $team_id;
        }
        
        // Create a new team post
        $team_data = array(
            'post_title' => $team_name,
            'post_type' => 'tym',
            'post_status' => 'publish',
        );
        
        $team_id = wp_insert_post($team_data);
        
        if (is_wp_error($team_id)) {
            error_log('Error creating team: ' . $team_id->get_error_message());
            return 0;
        }
        
        // Generate team abbreviation (up to 3 characters)
        $abbreviation = $this->generate_team_abbreviation($team_name);
        
        // Set ACF fields if function exists
        if (function_exists('update_field')) {
            update_field('zkratka_tymu', $abbreviation, $team_id);
        } else {
            // Fallback to regular post meta
            update_post_meta($team_id, 'zkratka_tymu', $abbreviation);
        }
        
        // Assign liga taxonomy if provided for new teams
        if (!empty($liga_slug)) {
            // For new teams, we know they don't have any liga, so we can assign directly
            $this->assign_liga_taxonomy($team_id, $liga_slug);
            error_log('Assigned liga: ' . $liga_slug . ' to new team: ' . $team_id);
        }
        
        error_log('Created new team: ' . $team_name . ' (ID: ' . $team_id . ', ABB: ' . $abbreviation . ', Liga: ' . $liga_slug . ')');
        
        // Cache the result
        $cache_key = md5($team_name);
        $this->team_cache[$cache_key] = $team_id;
        
        return $team_id;
    }
    
    /**
     * Generate team abbreviation (up to 3 characters)
     * 
     * @param string $team_name Full team name
     * @return string Team abbreviation
     */
    private function generate_team_abbreviation($team_name) {
        // Handle common team abbreviations manually
        $common_teams = array(
            'Sparta Praha' => 'SPA',
            'Slavia Praha' => 'SLA',
            'AC Sparta Praha' => 'SPA',
            'SK Slavia Praha' => 'SLA',
            'Viktoria Plzeň' => 'PLZ',
            'FC Viktoria Plzeň' => 'PLZ',
            'Baník Ostrava' => 'BAO',
            'FC Baník Ostrava' => 'BAO',
            'Slovan Liberec' => 'LIB',
            'FC Slovan Liberec' => 'LIB',
            'Bohemians Praha' => 'BOH',
            'Bohemians 1905' => 'BOH',
            'Dukla Praha' => 'DUK',
            'Manchester United' => 'MUN',
            'Manchester City' => 'MCI',
            'Arsenal' => 'ARS',
            'Liverpool' => 'LIV',
            'Chelsea' => 'CHE',
            'FC Barcelona' => 'BAR',
            'Barcelona' => 'BAR',
            'Real Madrid' => 'RMA',
            'Atletico Madrid' => 'ATM',
            'Bayern Mnichov' => 'BAY',
            'FC Bayern München' => 'BAY',
            'Borussia Dortmund' => 'BVB',
            'Paris Saint-Germain' => 'PSG',
            'Juventus' => 'JUV',
            'AC Milan' => 'MIL',
            'Inter Milan' => 'INT',
            'Inter' => 'INT',
        );
        
        if (isset($common_teams[$team_name])) {
            return $common_teams[$team_name];
        }
        
        // Generate abbreviation automatically
        // First, get just the main part of the name (remove FC, AC, etc.)
        $name_parts = preg_split('/\s+/', $team_name);
        
        // Remove common prefixes
        $prefixes = array('FC', 'AC', 'SK', 'FK', 'TJ');
        if (in_array($name_parts[0], $prefixes) && count($name_parts) > 1) {
            array_shift($name_parts);
        }
        
        // If only one word, take first 3 letters
        if (count($name_parts) === 1) {
            return strtoupper(substr($name_parts[0], 0, 3));
        }
        
        // If multiple words, take first letter of each (up to 3)
        $abbreviation = '';
        foreach ($name_parts as $part) {
            $abbreviation .= strtoupper(substr($part, 0, 1));
            if (strlen($abbreviation) >= 3) {
                break;
            }
        }
        
        // If still less than 3 chars, add first letters of first word
        while (strlen($abbreviation) < 3 && !empty($name_parts[0]) && strlen($name_parts[0]) > 1) {
            $abbreviation .= strtoupper(substr($name_parts[0], strlen($abbreviation), 1));
        }
        
        // If still less than 3, pad with X
        while (strlen($abbreviation) < 3) {
            $abbreviation .= 'X';
        }
        
        return substr($abbreviation, 0, 3);
    }
}

// Initialize the class
$tipnijinak_match_import = new Tipnijinak_Match_Import();