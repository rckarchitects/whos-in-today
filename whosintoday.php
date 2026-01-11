<?php
/*
   Plugin Name: Who's In Today?
   Plugin URI: https://russellcurtis.co.uk
   description: Plugin for locating people
   Version: 1.0.0
   Author: Russell Curtis
   Author URI: https://russellcurtis.co.uk
*/

// Only load functions if not already loaded
if (!function_exists('whosintoday_table')) {

    // Load text domain for translations
    function whosintoday_load_textdomain() {
        load_plugin_textdomain('whos-in-today', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    add_action('plugins_loaded', 'whosintoday_load_textdomain');

    function whosintoday_table(){
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $tablename = $wpdb->prefix."whosintoday";

        $sql = "CREATE TABLE " . $tablename . " (
        id mediumint NOT NULL AUTO_INCREMENT,
        userid int NOT NULL,
        day date NOT NULL,
        location varchar(80) NOT NULL,
        PRIMARY KEY  (id)
        ) " . $charset_collate . ";";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    function whosintoday_bankholidays_table(){
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $tablename = $wpdb->prefix."whosintoday_bankholidays";

        $sql = "CREATE TABLE " . $tablename . " (
        id mediumint NOT NULL AUTO_INCREMENT,
        added_by int NOT NULL,
        name varchar(80) NOT NULL,
        bankholiday date NOT NULL,
        PRIMARY KEY  (id)
        )" . $charset_collate . ";";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    function whosintoday_locations_table(){
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $tablename = $wpdb->prefix."whosintoday_locations";

        $sql = "CREATE TABLE " . $tablename . " (
        id mediumint NOT NULL AUTO_INCREMENT,
        location_name varchar(80) NOT NULL,
        display_order int NOT NULL DEFAULT 0,
        PRIMARY KEY  (id)
        )" . $charset_collate . ";";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );

        // Populate with default locations if table is empty
        $count = $wpdb->get_var("SELECT COUNT(*) FROM " . $tablename);
        if ($count == 0) {
            $default_locations = array('Studio', 'Home', 'Holiday', 'Elsewhere', 'Unpaid Leave', 'Absent', 'Isolating');
            $order = 0;
            foreach ($default_locations as $location) {
                $wpdb->insert($tablename, array(
                    'location_name' => $location,
                    'display_order' => $order
                ));
                $order++;
            }
        }
    }

    register_activation_hook( __FILE__, 'whosintoday_table' );
    register_activation_hook( __FILE__, 'whosintoday_bankholidays_table' );
    register_activation_hook( __FILE__, 'whosintoday_locations_table' );

    // Check and create locations table if it doesn't exist (for existing installations)
    function whosintoday_check_locations_table() {
        global $wpdb;
        $tablename = $wpdb->prefix."whosintoday_locations";

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$tablename'") != $tablename) {
            whosintoday_locations_table();
        }
    }
    add_action('admin_init', 'whosintoday_check_locations_table');

    // Only require functions.php if file exists and we're past plugins_loaded
    function whosintoday_load_functions() {
        $functions_file = plugin_dir_path(__FILE__) . 'functions.php';
        if (file_exists($functions_file)) {
            require_once($functions_file);
        }
    }
    add_action('plugins_loaded', 'whosintoday_load_functions', 1);

    // Add menu - must wait until admin_menu action
    function whosintoday_menu() {
        add_menu_page("Who's In Today?", "Who's In Today?","manage_options", "WhosInToday", "displayList",'dashicons-businessman');
        add_submenu_page("WhosInToday","Bank Holidays", "Bank Holidays","manage_options", "bankholidays", "bankHolidays");
        add_submenu_page("WhosInToday","Locations", "Locations","manage_options", "wit_locations", "manageLocations");
        add_submenu_page("WhosInToday","Settings", "Settings","manage_options", "wit_settings", "witSettings");
    }
    add_action("admin_menu", "whosintoday_menu");

    // Shortcode registration
    function whosintoday_register_shortcodes() {
        if (function_exists('whos_in_today_shortcode')) {
            add_shortcode( 'who_is_in_today', 'whos_in_today_shortcode');
        }
        if (function_exists('whos_in_today_heatmap_shortcode')) {
            add_shortcode( 'who_is_in_today_heatmap', 'whos_in_today_heatmap_shortcode');
        }
        if (function_exists('whos_in_today_next_bank_holiday')) {
            add_shortcode( 'whos_in_today_next_bank_holiday', 'whos_in_today_next_bank_holiday');
        }
        if (function_exists('whos_in_today_grid_shortcode')) {
            add_shortcode( 'who_is_in_today_grid', 'whos_in_today_grid_shortcode');
        }
    }
    add_action('init', 'whosintoday_register_shortcodes');

    // Custom CSS for the WIT plugin
    function WITLoadCustomCSS() {
        wp_register_style('whos-in-today-styles', plugins_url('styles.css',__FILE__ ));
        wp_enqueue_style('whos-in-today-styles');
    }
    add_action( 'wp_enqueue_scripts', 'WITLoadCustomCSS');

    function displayList(){
        $displaylist_file = plugin_dir_path(__FILE__) . 'displaylist.php';
        if (file_exists($displaylist_file)) {
            include $displaylist_file;
        } else {
            echo '<div class="wrap"><h1>Error</h1><p>Display list file not found.</p></div>';
        }
    }

    function addEntry(){
        $addentry_file = plugin_dir_path(__FILE__) . 'addentry.php';
        if (file_exists($addentry_file)) {
            include $addentry_file;
        } else {
            echo '<div class="wrap"><h1>Error</h1><p>Add entry file not found.</p></div>';
        }
    }

    function bankHolidays(){
        if (function_exists('WITListBankHolidays')) {
            WITListBankHolidays();
        } else {
            echo '<div class="wrap"><h1>Bank Holidays</h1><p>Function not yet loaded.</p></div>';
        }
    }

    function manageLocations(){
        if (function_exists('WITListLocations')) {
            WITListLocations();
        } else {
            echo '<div class="wrap"><h1>Locations</h1><p>Function not yet loaded.</p></div>';
        }
    }

    function witSettings(){
        if (function_exists('WITShowSettings')) {
            WITShowSettings();
        } else {
            echo '<div class="wrap"><h1>Settings</h1><p>Function not yet loaded.</p></div>';
        }
    }

    // Handle bank holiday form submission via admin_init hook
    function whosintoday_handle_bank_holiday_submission() {
        if (isset($_POST['but_add_bh']) && !empty($_POST['but_add_bh'])) {
            // Verify nonce for security
            if (!isset($_POST['wit_bank_holiday_nonce']) || !wp_verify_nonce($_POST['wit_bank_holiday_nonce'], 'wit_add_bank_holiday')) {
                wp_die('Security check failed');
            }
            if (function_exists('WITAddBankHoliday')) {
                WITAddBankHoliday();
            }
        }
    }
    add_action('admin_init', 'whosintoday_handle_bank_holiday_submission');

    // Handle bank holiday deletion via admin_init hook
    function whosintoday_handle_bank_holiday_deletion() {
        if (isset($_GET['delete_bh']) && !empty($_GET['delete_bh'])) {
            // Verify nonce for security
            if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'delete_bank_holiday_' . intval($_GET['delete_bh']))) {
                wp_die('Security check failed');
            }
            if (function_exists('WITDeleteBankHoliday')) {
                WITDeleteBankHoliday(intval($_GET['delete_bh']));
            }
        }
    }
    add_action('admin_init', 'whosintoday_handle_bank_holiday_deletion');

    // Handle location form submission via admin_init hook
    function whosintoday_handle_location_submission() {
        if (isset($_POST['but_add_location']) && !empty($_POST['but_add_location'])) {
            // Verify nonce for security
            if (!isset($_POST['wit_location_nonce']) || !wp_verify_nonce($_POST['wit_location_nonce'], 'wit_add_location')) {
                wp_die('Security check failed');
            }
            if (function_exists('WITAddLocation')) {
                WITAddLocation();
            }
        }
        if (isset($_POST['but_edit_location']) && !empty($_POST['but_edit_location'])) {
            // Verify nonce for security
            if (!isset($_POST['wit_edit_location_nonce']) || !wp_verify_nonce($_POST['wit_edit_location_nonce'], 'wit_edit_location_' . intval($_POST['location_id']))) {
                wp_die('Security check failed');
            }
            if (function_exists('WITEditLocation')) {
                WITEditLocation();
            }
        }
    }
    add_action('admin_init', 'whosintoday_handle_location_submission');

    // Handle location deletion via admin_init hook
    function whosintoday_handle_location_deletion() {
        if (isset($_GET['delete_location']) && !empty($_GET['delete_location'])) {
            // Verify nonce for security
            if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'delete_location_' . intval($_GET['delete_location']))) {
                wp_die('Security check failed');
            }
            if (function_exists('WITDeleteLocation')) {
                WITDeleteLocation(intval($_GET['delete_location']));
            }
        }
    }
    add_action('admin_init', 'whosintoday_handle_location_deletion');

    // Handle AJAX request to update location order
    function whosintoday_ajax_update_location_order() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wit_update_location_order')) {
            wp_send_json_error('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        // Get the order data
        $order = isset($_POST['order']) ? $_POST['order'] : array();

        if (empty($order)) {
            wp_send_json_error('No order data provided');
        }

        global $wpdb;
        $tablename = $wpdb->prefix."whosintoday_locations";

        // Update each location's display order
        foreach ($order as $item) {
            $id = intval($item['id']);
            $display_order = intval($item['order']);

            $wpdb->update(
                $tablename,
                array('display_order' => $display_order),
                array('id' => $id),
                array('%d'),
                array('%d')
            );
        }

        wp_send_json_success('Order updated successfully');
    }
    add_action('wp_ajax_wit_update_location_order', 'whosintoday_ajax_update_location_order');

    // Handle settings form submission via admin_init hook
    function whosintoday_handle_settings_submission() {
        if (isset($_POST['wit_save_settings']) && !empty($_POST['wit_save_settings'])) {
            // Verify nonce for security
            if (!isset($_POST['wit_settings_nonce']) || !wp_verify_nonce($_POST['wit_settings_nonce'], 'wit_save_settings')) {
                wp_die('Security check failed');
            }
            if (function_exists('WITSaveSettings')) {
                WITSaveSettings();
            }
        }
    }
    add_action('admin_init', 'whosintoday_handle_settings_submission');

}
