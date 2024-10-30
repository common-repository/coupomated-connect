<?php
if (!defined('ABSPATH')) exit;


/**
 * The function  `coupomated_import_coupons`  fetches coupon data from an API, logs the import status, clears the existing data in the 'coupomated_coupons' table, and inserts the new coupon data into the table. If the  `$create_coupons`  parameter is true, it schedules a 'coupomated_create_coupon_event' to run in 3 seconds.
 */
function coupomated_import_coupons($create_coupons = true)
{

    $api_data = coupomated_call_api('coupon');


    $import_id = coupomated_log_import_status('coupon', $api_data['title'] ?? null, count($api_data));
    $error = isset($api_data['title']);
    if ($error) return $error;

    


    global $wpdb;

    $coupons_table_name = $wpdb->prefix . 'coupomated_coupons';

    $wpdb->query('TRUNCATE TABLE ' . $coupons_table_name);


    foreach ($api_data as $coupon_data) {

        $coupon_id = $coupon_data['coupon_id'];
        $merchant_id = $coupon_data['merchant_id'];
        $network_id = $coupon_data['network_id'];
        $title = $coupon_data['title'];
        $description = $coupon_data['description'];
        $discount = $coupon_data['discount'];
        $coupon_code = $coupon_data['coupon_code'];
        $plain_link = $coupon_data['plain_link'];
        $exclusive = $coupon_data['exclusive'];
        $start_date = $coupon_data['start_date'];
        $end_date = $coupon_data['end_date'];
        $verified_at = $coupon_data['verified_at'];
        $created_at = $coupon_data['created_at'];
        $updated_at = $coupon_data['updated_at'];
        $category_ids = implode(',', $coupon_data['category_ids']);
        $category_names = implode(',', $coupon_data['category_names']);
        $category_names_list = $coupon_data['category_names_list'];
        $affiliate_link = $coupon_data['affiliate_link'];
        $merchant_logo = $coupon_data['merchant_logo'];
        $merchant_name = $coupon_data['merchant_name'];


        $data = array(
            'coupon_id' => $coupon_id,
            'merchant_id' => $merchant_id,
            'network_id' => $network_id,
            'title' => $title,
            'description' => $description,
            'discount' => $discount,
            'coupon_code' => $coupon_code,
            'plain_link' => $plain_link,
            'exclusive' => $exclusive,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'verified_at' => $verified_at,
            'created_at' => $created_at,
            'updated_at' => $updated_at,
            'category_ids' => $category_ids,
            'category_names' => $category_names,
            'category_names_list' => $category_names_list,
            'affiliate_link' => $affiliate_link,
            'merchant_logo' => $merchant_logo,
            'merchant_name' => $merchant_name,
            'import_status' => 'pending',

        );


        $wpdb->insert(
            $coupons_table_name,
            $data
        );
        
    }

    if ($create_coupons)
        wp_schedule_single_event(time() + 3, 'coupomated_create_coupon_event', [$import_id]);


    return 'Data fetched and inserted to database, Import will begin after 3 seconds.';
}


/**
 * This code is a function that imports stores data from an API into a WordPress plugin. It fetches the data from the API, stores it in a custom table in the database, and updates or inserts the data based on the store ID. It also schedules an event to create the stores in the WordPress theme after a delay of 3 seconds.
 */
function coupomated_import_stores($create_stores = true)
{
    $api_data = coupomated_call_api('store');

    $import_id = coupomated_log_import_status('store', $api_data['title'] ?? null, count($api_data));
    $error = isset($api_data['title']);
    if ($error) return $error;

    global $wpdb;

    $stores_table_name = $wpdb->prefix . 'coupomated_stores';

    $wpdb->query('TRUNCATE TABLE ' . $stores_table_name);


    foreach ($api_data as $store_data) {

        $store_id = $store_data['id'];
        $name = $store_data['name'];
        $website = $store_data['website'];
        $domain_name = $store_data['domain_name'];
        $country = $store_data['country'];
        $logo = $store_data['logo'];
        $stars = $store_data['stars'];
        $featured = $store_data['featured'];
        $category_ids = implode(',', $store_data['category_ids']);
        $category_names = implode(',', $store_data['category_names']);
        $affiliate_link = $store_data['affiliate_link'];


        $data = array(
            'store_id' => $store_id,
            'name' => $name,
            'website' => $website,
            'domain_name' => $domain_name,
            'country' => $country,
            'logo' => $logo,
            'stars' => $stars,
            'featured' => $featured,
            'category_ids' => $category_ids,
            'category_names' => $category_names,
            'affiliate_link' => $affiliate_link,
            'import_status' => 'pending',
        );


        $existing_store = $wpdb->get_row($wpdb->prepare("SELECT * FROM $stores_table_name WHERE store_id = %s", $store_id));

        if ($existing_store) {

            $wpdb->update(
                $stores_table_name,
                $data,
                array('store_id' => $store_id)
            );
        } else {
            $wpdb->insert(
                $stores_table_name,
                $data
            );
        }
    }

    if ($create_stores)
        wp_schedule_single_event(time() + 3, 'coupomated_create_store_event', [$import_id]);

    return 'Data fetched and inserted to database, Import will begin after 3 seconds.';
}

/**
 * This code is a function that creates stores in a WordPress theme based on data imported from a custom table in the database. It retrieves pending store data from the table in batches, creates the stores in the theme, updates the import status and summary in the table, and schedules the next batch creation event. It also increments the import count if an import ID is provided.
 */
function coupomated_create_stores($import_id = 0)
{
    global $wpdb;

    $stores_table_name = $wpdb->prefix . 'coupomated_stores';


    $batchSize = intval(get_option('coupomated_import_store_batch_size', 50));
    if($batchSize == 0) $batchSize = 50;




    // prepare and protect the below query from sql injection using wp prepare for $ variables
    $query = $wpdb->prepare("SELECT *  FROM {$stores_table_name} WHERE import_status = 'pending' LIMIT %d", $batchSize);
    $result = $wpdb->get_results($query);


    if (empty($result)) {

        if ($import_id > 0)
            coupomated_update_import_status($import_id, ['status' => 'Imported', 'end_date' => current_time('mysql')]);

        return;
    } else {
        if ($import_id > 0)
            coupomated_update_import_status($import_id, ['status' => 'Importing']);
    }




    foreach ($result as $row) {

        $created = coupomated_create_theme_store($row);

        $wpdb->update($stores_table_name, array('import_status' => $created['status'], 'import_summary' => ($created['message']),), array('id' => $row->id), array('%s', '%s'), array('%d'));
    }

    wp_schedule_single_event(time() + 1, 'coupomated_create_store_event', [$import_id]);

    if ($import_id > 0)
        coupomated_increment_import_count($import_id, count($result));
}

/**
 * This code is a function that creates coupons in a WordPress theme based on data imported from a custom table in the database. It retrieves pending coupon data from the table in batches, creates the coupons in the theme, updates the import status and summary in the table, and schedules the next batch creation event. It also increments the import count if an import ID is provided.
 */
function coupomated_create_coupon($import_id = 0)
{
    global $wpdb;

    $coupon_table_name = $wpdb->prefix . 'coupomated_coupons';


    $batchSize = intval(get_option('coupomated_import_coupon_batch_size', 200));
    if ($batchSize == 0) $batchSize = 200;




    // use wp prepate for $ vars
    $query = $wpdb->prepare("SELECT * FROM {$coupon_table_name} WHERE import_status = 'pending' LIMIT %d", $batchSize);
    $result = $wpdb->get_results($query);


    if (empty($result)) {
        

        if ($import_id > 0)
            coupomated_update_import_status($import_id, ['status' => 'Imported', 'end_date' => current_time('mysql')]);

        deleteCoupons();

        return;
    } else {
        if ($import_id > 0)
            coupomated_update_import_status($import_id, ['status' => 'Importing']);
    }


    foreach ($result as $row) {

        
        $created = coupomated_create_theme_coupon($row);
        $wpdb->update($coupon_table_name, array('import_status' => $created['status'], 'import_summary' => ($created['message']),), array('id' => $row->id), array('%s', '%s'), array('%d'));
    }

    wp_schedule_single_event(time() + 3, 'coupomated_create_coupon_event', [$import_id]);

    if ($import_id > 0)
        coupomated_increment_import_count($import_id, count($result));
}


/**
 * This code sets up scheduled events for importing coupons and stores from a feed API in a WordPress plugin. It defines the frequency of the imports, creates intervals based on the frequency, and schedules the events to occur at regular intervals.
 */
function coupomated_schedule_api_import()
{

    $coupons_import_frequency = 1;


    $coupons_interval = $coupons_import_frequency * HOUR_IN_SECONDS;


    if (!wp_next_scheduled('coupons_api_import_event')) {
        wp_schedule_event(time(), 'coupomated_schedule_coupon', 'coupons_api_import_event');
    }



    $stores_import_frequency = 1;


    $stores_interval = $stores_import_frequency * HOUR_IN_SECONDS;


    if (!wp_next_scheduled('stores_api_import_event')) {
        wp_schedule_event(time(), 'coupomated_schedule_store', 'stores_api_import_event');
    }
}
add_action('init', 'coupomated_schedule_api_import');

/**
 * This code sets up an event in a WordPress plugin to initiate the import of coupons from a feed API. When the event is triggered, the function  `coupomated_import_coupons`  is called with the parameter  `true` , indicating that the import should be initiated.
 */
function coupomated_initiate_cpn_import_event()
{
    coupomated_import_coupons(true);
}
add_action('coupons_api_import_event', 'coupomated_initiate_cpn_import_event');

function coupomated_initiate_store_import_event()
{
    coupomated_import_stores(true);
}
add_action('stores_api_import_event', 'coupomated_initiate_store_import_event');


add_action('coupomated_create_store_event', 'coupomated_create_stores');
add_action('coupomated_create_coupon_event', 'coupomated_create_coupon');


/**
 * This code defines a function  `coupomated_call_api`  that fetches data from a feed API based on the specified parameter ( `$api_for` ). The API URL is constructed using the base URL and API key, and the response is retrieved using  `wp_remote_get()` . If the response is successful, the JSON data is decoded and returned. If there are any errors during the API request or if the JSON response is invalid, appropriate error messages are added and an empty array is returned.
 */
function coupomated_call_api($api_for)
{



    $base_url = 'https://api.coupomated.com/';


    $api_key =  get_option('coupomated_import_apikey');


    switch ($api_for) {
        case 'coupon':
            $base_url .= 'coupons/all';
            break;
        case 'store_category':
            $base_url .= 'categories/merchant';
            break;
        case 'coupon_category':
            $base_url .= 'categories/coupon';
            break;
        case 'store':
            $base_url .= 'merchants';
            break;
        case 'coupon_tag':
            $base_url .= 'tags';
            break;
        default:
            $base_url .= $api_for;
    }


    $url = $base_url .  '?apikey=' . $api_key;



    $response = wp_remote_get($url,['timeout'=>90]);


    if (is_wp_error($response)) {

        $error_message = $response->get_error_message();

        add_settings_error('api_error', 'api_error', 'API request failed: ' . $error_message, 'error');
        return [];
    }


    $body = wp_remote_retrieve_body($response);


    $data = json_decode($body, true);
    if (is_null($data)) {


        add_settings_error('api_error', 'api_error', 'Invalid JSON response from the API.', 'error');
        return [];
    }


    return $data;
}

/**
 * This code defines a function  `coupomated_sync_categories`  that synchronizes categories from a feed API with the WordPress theme. The  `coupomated_sync_tax`  function is called for each category type ('coupon_category', 'store_category', 'coupon_tag'), which fetches the taxonomy data from the feed API, sorts the taxonomies based on parent IDs, checks if the taxonomy already exists in the theme, and if not, creates a new taxonomy. The import status is then updated, and 'Synced' is returned.
 */
function coupomated_sync_categories()
{

    $statutes = [];
    foreach (['coupon_category', 'store_category', 'coupon_tag'] as $tax)
        $statutes[$tax] = coupomated_sync_tax($tax);
    return $statutes;
}


function coupomated_sync_tax_sort_parent($tax1, $tax2)
{
    if ($tax1['parent_id'] == $tax2['parent_id']) {
        return 0;
    }
    return ($tax1['parent_id'] < $tax2['parent_id']) ? -1 : 1;
}


function coupomated_sync_tax($tax_import_for)
{
    global $coupomated_theme_configs;
    $theme = get_option('coupomated_import_theme');


    if (!isset($coupomated_theme_configs[$theme]['type'][$tax_import_for])) return 'Theme is not configured';
    if (!($coupomated_theme_configs[$theme]['type'][$tax_import_for])) return 'Taxonomy is not available for this theme';

    $taxonomies = coupomated_call_api($tax_import_for);

    $import_id = coupomated_log_import_status($tax_import_for, $taxonomies['title'] ?? null, count($taxonomies));

    if (isset($taxonomies['error'])) return $taxonomies['title'];

    if (!$taxonomies) return 'API Response is not proper';


    usort($taxonomies, 'coupomated_sync_tax_sort_parent');



    $theme_taxonomy = $coupomated_theme_configs[$theme]['type'][$tax_import_for];


    foreach ($taxonomies as $taxonomy) {
        $taxonomy_id = $taxonomy['id'];
        $taxonomy_name = $taxonomy['name'];
        $parent_id = $taxonomy['parent_id'];
        $parent_name = $taxonomy['parent_name'];



        $existing_taxonomy = get_term_by('id', $taxonomy_id, $theme_taxonomy);
        if (!$existing_taxonomy) {
            $existing_taxonomy = get_term_by('name', $taxonomy_name, $theme_taxonomy);
        }


        if (!$existing_taxonomy) {

            $parent = false;
            if ($parent_id) {
                $parent = get_term_by('id', $parent_id, $theme_taxonomy);
                if (!$parent) {
                    $parent = get_term_by('name', $parent_name, $theme_taxonomy);
                }
            }



            $taxonomy_args = array(
                'name' => $taxonomy_name,
                'slug' => sanitize_title($taxonomy_name),
                'parent' => 0,
                'taxonomy' => $theme_taxonomy,
            );

            $new_taxonomy_id = wp_insert_term($taxonomy_name, $theme_taxonomy, $taxonomy_args);
            update_term_meta($new_taxonomy_id, 'coupomated_id', $taxonomy_id);



            if ($parent_id && !is_wp_error($new_taxonomy_id) && $parent->term_id) {
                wp_update_term($new_taxonomy_id['term_id'], $theme_taxonomy, array('parent' => $parent->term_id));
            }
        }
    }
    if ($import_id)
        coupomated_update_import_status($import_id, ['status' => 'Imported', 'end_date' => current_time('mysql'), 'processed_records' => count($taxonomies)]);

    return 'Synced';
}

/**
 * This code clears scheduled hooks associated with importing stores and coupons from an API when the relevant options are updated.
 */
function coupomated_delete_store_import_schedule($old_value, $new_value, $option_name)
{
    wp_clear_scheduled_hook('stores_api_import_event');
}

function coupomated_delete_coupomated_schedule($old_value, $new_value, $option_name)
{
    wp_clear_scheduled_hook('coupons_api_import_event');
}

add_action("update_option_coupomated_import_store_frequency", 'coupomated_delete_store_import_schedule', 10, 3);
add_action("update_option_coupomated_import_coupon_frequency", 'coupomated_delete_coupomated_schedule', 10, 3);
