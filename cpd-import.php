<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Plugin Name:       Coupomated Connect
 * Plugin URI:        https://www.coupomated.com/free-wordpress-plugin-coupon-feed-integration/
 * Description:       Coupomated Connect is a WordPress plugin that automate affiliate marketing on coupon and cashback websites. Using Coupomated Coupon API, this plugin allows effortless import from a vast pool of affiliate stores, coupons & offers.
 * Version:           1.6
 * Author:            Coupomated
 * Author URI:        https://www.coupomated.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       coupomated
 * Domain Path:       /languages
 */
 
/*

This code is a part of a WordPress plugin for importing coupons. 
 1. It starts by requiring two PHP files: 'theme-config.php' and 'api-import.php'.
 2. The plugin registers activation and uninstall hooks to execute specific functions when the plugin is activated or uninstalled. 
 3. On activation, it creates three tables in the WordPress database: 'coupomated_stores', 'coupomated_coupons', and 'coupomated_import_log', which store the data of the stores, coupons, and the import logs respectively.
 4. On uninstallation, it drops the three tables from the database.
 5. It adds a custom schedule to the WordPress cron schedules for importing stores and coupons at specific intervals.
 6. It enqueues a CSS file for the admin section of the plugin.
 7. It registers settings for the plugin in the WordPress options table.
 8. It adds a top-level menu item in the WordPress admin menu with four sub-menu items: 'Coupons', 'Stores', 'Import Log', and 'Settings'.
 9. It includes the PHP files for the admin pages of the plugin.
 10. It has functions for logging the import status, updating the import status, incrementing the import count, and reprocessing coupons and stores.
 11. It also has functions for getting the count of coupons and stores in the database.
 Overall, this plugin helps in importing coupons from different stores, and it keeps a log of the import process. It also provides an admin interface to manage the coupons, stores, and logs.

 */



require_once(plugin_dir_path(__FILE__) . 'theme-config.php');
require_once(plugin_dir_path(__FILE__) . 'api-import.php');


register_activation_hook(__FILE__, 'coupomated_plugin_activate');



register_uninstall_hook(__FILE__, 'coupomated_plugin_uninstall');

/**
 * Callback function to be executed on plugin activation
 */


function coupomated_plugin_activate()
{
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $store_table_name = $wpdb->prefix . 'coupomated_stores';
    $coupon_table_name = $wpdb->prefix . 'coupomated_coupons';
    $import_log_table_name = $wpdb->prefix . 'coupomated_import_log';


    $store_table_sql = "CREATE TABLE IF NOT EXISTS $store_table_name (
        id INT(11) NOT NULL AUTO_INCREMENT,
        store_id VARCHAR(255) NOT NULL,
        name VARCHAR(255) NOT NULL,
        website VARCHAR(255) NOT NULL,
        domain_name VARCHAR(255) NOT NULL,
        country VARCHAR(255),
        logo VARCHAR(255) NOT NULL,
        stars INT(11),
        featured INT(11),
        category_ids VARCHAR(2555) NOT NULL,
        category_names VARCHAR(2555) NOT NULL,
        affiliate_link VARCHAR(2555) NOT NULL,
        import_status VARCHAR(255),
        import_summary VARCHAR(2555),
        reference VARCHAR(255),
        PRIMARY KEY (id)
    ) $charset_collate;";


    $coupon_table_sql = "CREATE TABLE IF NOT EXISTS $coupon_table_name (
        id INT(11) NOT NULL AUTO_INCREMENT,
        coupon_id INT(11) NOT NULL,
        merchant_id VARCHAR(255) NOT NULL,
        network_id VARCHAR(255),
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        discount VARCHAR(255),
        coupon_code VARCHAR(255),
        plain_link VARCHAR(1555),
        exclusive INT(11),
        start_date VARCHAR(255),
        end_date VARCHAR(255),
        verified_at VARCHAR(255),
        created_at VARCHAR(255),
        updated_at VARCHAR(255),
        category_ids VARCHAR(500),
        category_names VARCHAR(2555),
        category_names_list VARCHAR(2555),
        affiliate_link VARCHAR(2555),
        merchant_logo VARCHAR(255),
        merchant_name VARCHAR(255),
        import_status VARCHAR(25),
        import_summary VARCHAR(255),
        reference VARCHAR(255),
        PRIMARY KEY (id)
    ) $charset_collate;";



    $import_log_table_sql = "CREATE TABLE IF NOT EXISTS $import_log_table_name (
        id INT(11) NOT NULL AUTO_INCREMENT,
        import_for varchar(25) NOT NULL,
        import_date DATETIME NOT NULL,
        end_date DATETIME,
        total_records INT(11) NOT NULL,
        processed_records INT(11) NOT NULL,
        status VARCHAR(255) NOT NULL,
        summary TEXT,
        PRIMARY KEY (id)
    ) $charset_collate;";


    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($store_table_sql);
    dbDelta($coupon_table_sql);
    dbDelta($import_log_table_sql);
}

/**
 * Callback function to be executed on plugin uninstallation
 */

function coupomated_plugin_uninstall()
{
    global $wpdb;


    $store_table_name = $wpdb->prefix . 'coupomated_stores';
    $coupon_table_name = $wpdb->prefix . 'coupomated_coupons';
    $import_log_table_name = $wpdb->prefix . 'coupomated_import_log';


    // Drop all tables
    $wpdb->query("DROP TABLE IF EXISTS $store_table_name");


    $wpdb->query("DROP TABLE IF EXISTS $coupon_table_name");

    $wpdb->query("DROP TABLE IF EXISTS $import_log_table_name");
}


/**
 * Add custom cron schedules for store and coupon imports
 */

function coupomated_add_schedule($schedules)
{

    $store_interval = intval(get_option('coupomated_import_store_frequency', 24)) * 60 * 60;
    $coupon_interval = intval(get_option('coupomated_import_coupon_frequency', 24)) * 60 * 60;
    if ($store_interval == 0)
        $store_interval = 5 * 24 * 365 * 60 * 60;

    $schedules['coupomated_schedule_store'] = array(
        'interval' => $store_interval,
        'display' => __('Store Import')
    );

    $schedules['coupomated_schedule_coupon'] = array(
        'interval' => $coupon_interval,
        'display' => __('Coupon Import')
    );

    return $schedules;
}

add_filter('cron_schedules', 'coupomated_add_schedule');



/**
 * Register plugin settings
 */
function coupomated_plugin_enqueue_styles()
{
    wp_enqueue_style('cpd-admin-css', plugin_dir_url(__FILE__) . 'coupomated_admin.css', array(), '1.0' );
}
add_action('admin_enqueue_scripts', 'coupomated_plugin_enqueue_styles');


/**
 * Register plugin settings
 */

function coupomated_plugin_register_settings()
{
    foreach (['api_key', 'theme', 'store_frequency', 'coupon_frequency', 'plan', 'apikey','coupon_batch_size','store_batch_size'] as $key)
        register_setting('coupon-import-plugin-settings-group', 'coupomated_import_' . $key);
}
add_action('admin_init', 'coupomated_plugin_register_settings');


/**
 * Add admin menu pages
 */

function coupomated_plugin_add_admin_menu()
{
    add_menu_page(
        'Coupomated Connect',
        'Coupomated Connect',
        'manage_options',
        'coupon-import',
        'coupomated_plugin_settings_page',
        'dashicons-nametag',
        85
    );



    add_submenu_page(
        'coupon-import',
        'Coupons',
        'Coupons',
        'manage_options',
        'coupon-import-coupons',
        'coupomated_plugin_coupons_page'
    );



    add_submenu_page(
        'coupon-import',
        'Stores',
        'Stores',
        'manage_options',
        'coupon-import-stores',
        'coupomated_plugin_stores_page'
    );


    add_submenu_page(
        'coupon-import',
        'Import Log',
        'Import Log',
        'manage_options',
        'coupon-import-log',
        'coupomated_plugin_log_page'
    );
}
add_action('admin_menu', 'coupomated_plugin_add_admin_menu');



/**
 * Callback function to display settings page
 */
function coupomated_plugin_settings_page()
{
    require_once(plugin_dir_path(__FILE__) . 'admin-settings-page.php');
}

/**
 * Callback function to display coupon page
 */

function coupomated_plugin_coupons_page()
{

    require_once(plugin_dir_path(__FILE__) . 'admin-coupons-page.php');
}

/**
 * Callback function to display store page
 */
function coupomated_plugin_stores_page()
{

    require_once(plugin_dir_path(__FILE__) . 'admin-stores-page.php');
}

/**
 * Callback function to display import log page
 */
function coupomated_plugin_log_page()
{

    require_once(plugin_dir_path(__FILE__) . 'admin-log-page.php');
}


/**
 * Log the import status and return the insert ID
 */
function coupomated_log_import_status($import_for, $error = null, $total_records = 0)
{
    global $wpdb;


    $import_log_table_name = $wpdb->prefix . 'coupomated_import_log';


    $import_log_table_name = $wpdb->prefix . 'coupomated_import_log';


    $data = array(
        'import_for' => $import_for,
        'total_records' => $total_records,
        'processed_records' => 0,
        'status' => $error  ? 'error' : 'Downloaded',
        'summary' => $error,
        'import_date' => current_time('mysql'),
    );


    $inserted = $wpdb->insert($import_log_table_name, $data);
    if ($inserted)
        return $wpdb->insert_id;
    else return 0;
}


/**
 * Update the import status in the import log table
 */
function coupomated_update_import_status($import_id, $data)
{

    global $wpdb;



    $import_log_table_name = $wpdb->prefix . 'coupomated_import_log';

    $wpdb->update(
        $import_log_table_name,
        $data,
        array('id' => $import_id)
    );
}

/**
 * Increment the processed records count for the import log
 */
function coupomated_increment_import_count($import_id, $increment = 0)
{
    global $wpdb;
    $import_log_table_name = $wpdb->prefix . 'coupomated_import_log';
    return $wpdb->query($wpdb->prepare("UPDATE $import_log_table_name SET processed_records = processed_records + %d WHERE id = %d", (int) $increment, $import_id));

}

/**
 * Get the count of coupons in the coupon table
 */
function coupomated_coupon_table_count()
{
    global $wpdb;
    $coupon_table_name = $wpdb->prefix . 'coupomated_coupons';

    return $wpdb->get_var("SELECT COUNT(*) FROM {$coupon_table_name} ");
}

/**
 * Get the count of stores in the store table
 */
function coupomated_store_table_count()
{
    global $wpdb;
    $store_table_name = $wpdb->prefix . 'coupomated_stores';

    return $wpdb->get_var("SELECT COUNT(*) FROM {$store_table_name} ");
}

/**
 * Reprocess all coupons by setting their import status to 'pending'
 */
function coupomated_reprocess_coupons()
{
    global $wpdb;
    $coupon_table_name = $wpdb->prefix . 'coupomated_coupons';
    $wpdb->query("UPDATE $coupon_table_name SET import_status ='pending', import_summary=''");
}


/**
 * Reprocess all stores by setting their import status to 'pending'
 */

function coupomated_reprocess_stores()
{
    global $wpdb;
    $store_table_name = $wpdb->prefix . 'coupomated_stores';

    $wpdb->query("UPDATE $store_table_name SET import_status ='pending', import_summary=''");
}


/**
 * Get the next run time for a scheduled event
 */
function coupomated_get_next_event_runtime($event_hook) {
    $timestamp = wp_next_scheduled($event_hook);
    if ($timestamp) {
        $schedule = wp_get_schedule($event_hook);
        return date('Y-m-d H:i:s', $timestamp + wp_get_schedule($schedule));
    }
    return null;
}

// Hook into 'admin_notices' to display the message if CRON is disabled
// add_action( 'admin_notices', 'coupomated_check_wp_cron_status_notice' );

function coupomated_check_wp_cron_status_notice() {
    // If DISABLE_WP_CRON is defined and set to true, WP Cron is disabled.
    if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
        $class = 'notice notice-error';
        $message = __( 'Coupomated Connect has identified an issue :  WP Cron is disabled on this site.  Scheduled tasks for imports will not work.  To rectify this, please ensure the <code>DISABLE_WP_CRON</code> constant is not set to <code>true</code> in your <code>wp-config.php</code> file. For more details,fix, check with hosting provider or developer.' );

        printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), wp_kses( $message, array( 'a' => array( 'href' => array(), 'target' => array() ), 'code' => array() ) ) );
    }
}


// Hook into the 'site_status_tests' filter to add our custom health check.
add_filter( 'site_status_tests', 'coupomated_add_cron_test' );


/**
 * Register the WP Cron status test with Site Health.
 * 
 * @param array $tests Array of existing tests.
 * @return array Modified array with our test added.
 */
function coupomated_add_cron_test( $tests ) {
	
    $tests['direct']['check_wp_cron'] = array(
        'label' => __( 'WP Cron status' ),
        'test'  => 'coupomated_test_wp_cron_status',
    );

    return $tests;
}

/**
 * Test WP Cron's status and return the appropriate result.
 * 
 * @return array Test results with label, status, badge, description, and actions.
 */
function coupomated_test_wp_cron_status() {
	
    // If DISABLE_WP_CRON is defined and set to true, WP Cron is disabled.
    if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
    
        return array(
            'label'       => __( 'WP Cron is disabled'),
            'status'      => 'recommended',
            'badge'       => array(
                'label' => __( 'Performance' ), // Badge label.
                'color' => 'red',               // Badge color.
            ),
            'description' => sprintf( 
                __( 'Coupomated Connect has identified an issue :  WP Cron is disabled on this site.  Scheduled tasks for imports will not work.  To rectify this, please ensure the <code>DISABLE_WP_CRON</code> constant is not set to <code>true</code> in your <code>wp-config.php</code> file. For more details,fix, check with hosting provider or developer.' ),
                '<br>'
            ),
            'actions'     => '', // Any additional action buttons, kept empty for now.
			'test'  => 'coupomated_test_wp_cron_status',
        );
    }

    // Default return when everything is okay.
    return array(
        'label'       => __( 'WP Cron is enabled'),
        'status'      => 'good',
        'badge'       => array(
            'label' => __( 'Performance' ),
            'color' => 'blue',
        ),
        'description' => __( 'WP Cron is working as expected.'),
        'actions'     => '',
		'test'  => 'coupomated_test_wp_cron_status',
    );
}

// Add a filter to modify HTTP request arguments.
// This filter is applied to the array of arguments used in an HTTP request.
add_filter('http_request_args', 'coupomated_http_request_args', 100, 1);
function coupomated_http_request_args($r) // This function takes the request arguments as a parameter.
{
	$r['timeout'] = 90; // Sets the timeout for the HTTP request to 90 seconds.
	return $r; // Returns the modified request arguments.
}
 
// Add an action to modify cURL options for the WordPress HTTP API.
// This action is triggered when the WordPress HTTP API uses cURL for requests.
add_action('http_api_curl', 'coupomated_http_api_curl', 100, 1);
function coupomated_http_api_curl($handle) // This function takes the cURL handle as a parameter.
{
	curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 90); // Sets the timeout for the connection phase to 90 seconds.
	curl_setopt($handle, CURLOPT_TIMEOUT, 90); // Sets the maximum time the cURL request is allowed to take to 90 seconds.
}
