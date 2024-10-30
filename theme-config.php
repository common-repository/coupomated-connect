<?php
if (!defined('ABSPATH')) exit;

if (!function_exists('wp_generate_attachment_metadata')) {
    include(ABSPATH . 'wp-admin/includes/image.php');
}



global $coupomated_theme_configs;



/**
 * This code defines an array  `$coupomated_theme_configs`  that holds configuration settings for a WordPress plugin. The settings specify the mapping of various elements like coupon categories, store categories, coupon tags, store types, and store fields to their corresponding WordPress entities like taxonomies and post types. It also includes settings for coupon fields like affiliate links, coupon codes, titles, descriptions, and whether to download the logo.
 */

$coupomated_theme_configs = [

    'rehub' => [
        'type' => [
            'coupon_category' => 'category',
            'store_category' => false,
            'coupon_tag' => 'post_tag',
            'store_type' => 'taxonomy',
            'store' => 'dealstore',


            'coupon_type' => 'post',
            'coupon' => 'post',
        ],
        'store'  =>  [
            'fields' => [
                'brand_url' => 'affiliate_link'
            ],


            'category' => false,
        ],
        'coupon' => [
            'fields' => [
                'rehub_offer_product_url' => 'affiliate_link',
                'rehub_offer_product_coupon' => 'coupon_code',
                'rehub_offer_discount' => 'discount',
                'rehub_offer_name' => 'title',
                'rehub_offer_product_desc' => 'description',
            ],
            'category' => 'category',
            'download_logo' => true,

        ],
    ],
    'clipmydeals' => [
        'type' => [
            'coupon_category' => 'offer_categories',
            'store_category' => false,
            'coupon_tag' => false,
            'store_type' => 'taxonomy',
            'store' => 'stores',
            'coupon_type' => 'post',
            'coupon' => 'coupons',
        ],
        'store'  =>  [
            'fields' => [
                'brand_url' => 'affiliate_link'
            ],


            'category' => false,
        ],
        'coupon' => [
            'fields' => [
                'cmd_url' => 'affiliate_link',
                'cmd_code' => 'coupon_code',
                'cmd_lmd_id' => 'coupon_id',
                'cmd_badge' => 'discount',
                // 'cmd_valid_till' => 'end_date',
                // 'cmd_verified_on' => 'verified_at',
            ],
            'category' => 'offer_categories',
            'download_logo' => false,

        ],
    ],
    'couponorb' => [
        'type' => [
            'coupon_category' => 'coupon_cat',
            'store_category' => 'store_cat',
            'coupon_tag' => false,
            'store_type' => 'post',
            'store' => 'store',
            'coupon_type' => 'post',
            'coupon' => 'coupon',
        ],
        'store'  =>  [
            'fields' => [
                'affiliate_link' => 'affiliate_link',
                'homepage' => 'website',
            ],
            'category' => 'store_cat',
            'is_acf' => true,
            'download_logo' => true,
        ],
        'coupon' => [
            'fields' => [
                'discount' => 'discount',
                'coupon_code' => 'coupon_code',
                // 'expiry_date' => 'end_date',
                'link' => 'affiliate_link',
                // 'is_affiliate_link' => true,

            ],
            'category' => 'coupon_cat',
            'download_logo' => false,
            'is_acf' => true,
            'date_format' => 'Ymd',
        ]
    ]

];

/**
 * This code defines a function  `coupomated_create_theme_store`  that is used to create or update a store in WordPress based on the provided store data. The function checks the theme configuration, creates a taxonomy term or a post for the store, updates the store fields, sets the store logo as the post thumbnail if specified, assigns categories to the store, and calls a theme-specific import function if available. The function returns a status and message indicating whether the store creation/update was successful or not.
 */
function coupomated_create_theme_store($store)
{
    global $wpdb;

    $theme = get_option('coupomated_import_theme');

    try {


        global $coupomated_theme_configs;
        if (!isset($coupomated_theme_configs[$theme]))
            return ["status" =>  "failed", "message" => "Theme not set"];
        if (!isset($coupomated_theme_configs[$theme]['type']['store']))
            return ["status" => "failed", "message" => "Config not available for this theme"];

        if ($coupomated_theme_configs[$theme]['type']['store_type'] == 'taxonomy') {
            $wp_store =  (array) Coupomated_TaxonomyHelper::firstOrCreate($coupomated_theme_configs[$theme]['type']['store'], $store->store_id, $store->name);

            foreach ($coupomated_theme_configs[$theme]['store']['fields'] as $theme_field => $coupomated_column)
                update_term_meta($wp_store['term_id'], $theme_field, $store->{$coupomated_column});
        } else {
            $store_helper = new Coupomated_WP_Post_Helper();
            $wp_store = (array) $store_helper->firstOrCreate($coupomated_theme_configs[$theme]['type']['store'], $store->store_id, $store->name);

            foreach ($coupomated_theme_configs[$theme]['store']['fields'] as $theme_field => $coupomated_column) {
                if (isset($coupomated_theme_configs[$theme]['store']['is_acf']))
                    update_field($theme_field, $store->{$coupomated_column}, $wp_store['ID']);
                else
                    update_post_meta($wp_store['ID'], $theme_field, $store->{$coupomated_column});
            }
            if ($coupomated_theme_configs[$theme]['coupon']['download_logo'] && !has_post_thumbnail($wp_coupon['ID']) ) {
                $attachment_helper = new Coupomated_WP_Attachment_Helper();
                $attachment_helper->getOrCreateAttachment($store->logo);
                if ($attachment_helper->attachment_id)
                    set_post_thumbnail($wp_store['ID'], $attachment_helper->attachment_id);
            }



            if ($coupomated_theme_configs[$theme]['store']['category']) {
                $category_ids = [];
                // Assuming $coupon->category_names is a comma-separated string of category names.

                // Split the category names into an array and prepare both raw and encoded versions.
                $raw_category_names = explode(',', $store->category_names);
                $category_names = [];
                foreach ($raw_category_names as $name) {
                    $name = trim($name); // Trim whitespace
                    $category_names[] = $name; // Add the raw name
                    $category_names[] = htmlentities($name); // Add the HTML entity encoded name
                }

                // Ensure unique values to optimize the query.
                $category_names = array_unique($category_names);

                // Generate placeholders for the IN clause based on the number of category names.
                $placeholders = implode(',', array_fill(0, count($category_names), '%s'));

                // The taxonomy condition from your configuration for the 'store' context.
                $taxonomy_condition = $coupomated_theme_configs[$theme]['store']['category'];

                // Prepare the SQL statement with placeholders for the category names and the taxonomy condition.
                $sql = $wpdb->prepare(
                    "SELECT {$wpdb->terms}.term_id
    FROM {$wpdb->terms}
    INNER JOIN {$wpdb->term_taxonomy} ON {$wpdb->terms}.term_id = {$wpdb->term_taxonomy}.term_id
    WHERE {$wpdb->terms}.name IN ($placeholders)
    AND taxonomy = %s",
                    // Merge the category names and taxonomy condition into one array for wpdb->prepare.
                    array_merge($category_names, [$taxonomy_condition])
                );

                // Execute the query to get category IDs.
                $category_ids = $wpdb->get_col($sql);

                // $category_ids now contains the term_ids of the categories matching your criteria.


                if (!empty($category_ids)) {
                    $ret = wp_set_post_terms($wp_store['ID'], $category_ids, $taxonomy_condition);
                }
            }
        }
        if (function_exists('coupomated_' . $theme . '_store_import')) ('coupomated_' . $theme . '_store_import')($wp_store, $store);

        return ["status" => 'success', "message" => 'Created/Updated Store with ID - ' . ($wp_store['ID'] ?? $wp_store['term_id'])];
    } catch (Exception $e) {
        return ["status" => 'failed', "message" => $e->getMessage()];
    }
}

/**
 * This code defines a function  `coupomated_create_theme_coupon`  that creates or updates a coupon in WordPress based on the provided coupon data. The function checks the theme configuration, creates a post for the coupon, updates the coupon fields, assigns categories to the coupon, sets the merchant logo as the post thumbnail if specified, and calls a theme-specific import function if available. The function returns a status and message indicating whether the coupon creation/update was successful or not.
 */
function coupomated_create_theme_coupon($coupon)
{

    global $wpdb;

    $theme = get_option('coupomated_import_theme');

    try {
        global $coupomated_theme_configs;
        if (!isset($coupomated_theme_configs[$theme]))
            return ["status" =>  "failed", "message" => "Theme not set"];
        if (!isset($coupomated_theme_configs[$theme]['type']['store']))
            return ["status" => "failed", "message" => "Config not available for this theme"];


        if ($coupomated_theme_configs[$theme]['type']['coupon_type'] == 'post') {
            $coupon_helper = new Coupomated_WP_Post_Helper();
            $wp_coupon = (array) $coupon_helper->firstOrCreate($coupomated_theme_configs[$theme]['type']['coupon'], $coupon->coupon_id, $coupon->title);

            $wpdb->update(
                $wpdb->posts,
                array(
                    'post_title' => $coupon->title,
                    'post_content' => $coupon->description,
                    'post_status' => 'publish',
                ),
                array('ID' => $wp_coupon['ID']),
                array('%s', '%s'),
                array('%d')
            );

            foreach ($coupomated_theme_configs[$theme]['coupon']['fields'] as $theme_field => $coupomated_column) {
                if (isset($coupomated_theme_configs[$theme]['coupon']['is_acf']))
                    update_field($theme_field, $coupon->{$coupomated_column}, $wp_coupon['ID']);
                else
                    update_post_meta($wp_coupon['ID'], $theme_field, (string) $coupon->{$coupomated_column});
            }



            if ($coupomated_theme_configs[$theme]['coupon']['category']) {
                $category_ids = [];
                // Assuming $coupon->category_names is a comma-separated string of category names.

                // Split the category names into an array and prepare both raw and encoded versions.
                $raw_category_names = explode(',', $coupon->category_names);
                $category_names = [];
                foreach ($raw_category_names as $name) {
                    $category_names[] = trim($name); // Add the raw name
                    $category_names[] = htmlentities(trim($name)); // Add the HTML entity encoded name
                }

                // Ensure unique values to optimize the query.
                $category_names = array_unique($category_names);

                // Generate placeholders for the IN clause based on the number of category names.
                $placeholders = implode(',', array_fill(0, count($category_names), '%s'));

                // The taxonomy condition from your configuration.
                $taxonomy_condition = $coupomated_theme_configs[$theme]['coupon']['category'];

                // Prepare the SQL statement with placeholders for the category names and the taxonomy condition.
                $sql = $wpdb->prepare(
                    "SELECT {$wpdb->terms}.term_id
    FROM {$wpdb->terms}
    INNER JOIN {$wpdb->term_taxonomy} ON {$wpdb->terms}.term_id = {$wpdb->term_taxonomy}.term_id
    WHERE {$wpdb->terms}.name IN ($placeholders)
    AND taxonomy = %s",
                    // Merge the category names and taxonomy condition into one array for wpdb->prepare.
                    array_merge($category_names, [$taxonomy_condition])
                );

                // Execute the query to get category IDs.
                $category_ids = $wpdb->get_col($sql);



                if (!empty($category_ids))
                    wp_set_post_terms($wp_coupon['ID'], $category_ids, $coupomated_theme_configs[$theme]['coupon']['category']);
            }

            if ($coupomated_theme_configs[$theme]['coupon']['download_logo'] && !has_post_thumbnail($wp_coupon['ID']) ) {
                $attachment_helper = new Coupomated_WP_Attachment_Helper();
                $attachment_helper->getOrCreateAttachment($coupon->merchant_logo);
                if ($attachment_helper->attachment_id)
                    set_post_thumbnail($wp_coupon['ID'], $attachment_helper->attachment_id);
            }
        }

        if (function_exists('coupomated_' . $theme . '_coupon_import')) ('coupomated_' . $theme . '_coupon_import')($wp_coupon, $coupon);

        return ["status" => 'success', "message" => 'Created/Updated Coupon with ID - ' . ($wp_coupon['ID'])];
    } catch (Exception $e) {
        return ["status" => 'failed', "message" => $e->getMessage()];
    }
}

/**
 * Theme specific code  - CouponORB for coupon import. Set expiry date as per format. Assign store. Set it as not expired.
 */
function coupomated_couponorb_coupon_import($coupon, $metadata)
{
    $orb_theme_option =  get_option('CouponINT');
    $date_format = $orb_theme_option['expiry_date_format'] ?? 'Ymd';
    if ($metadata->end_date)
        update_post_meta($coupon['ID'], 'expiry_date', wp_date( $date_format , strtotime($metadata->end_date)));

    $store = Coupomated_WP_Post_Helper::getStoreByReference('store', $metadata->merchant_id, $metadata->merchant_name);
    
    if ($store) {
        // wp_set_post_terms($coupon['ID'], $store->ID,'store');
        update_field('store', $store->ID, $coupon['ID']);
    }

    update_field('is_affiliate_link',1,$coupon['ID']);
}

/**
 * Theme specific code  - Rehub for coupon import. Set expiry date as per format. Assign store. Set it as not expired.
 */
function coupomated_rehub_coupon_import($coupon, $metadata)
{
    delete_post_meta($coupon['ID'], 're_post_expired');
    if ($metadata->end_date)
        update_post_meta($coupon['ID'], 'rehub_offer_coupon_date', wp_date('Y-m-d', strtotime($metadata->end_date)) . ' 23:59');

    $store = Coupomated_TaxonomyHelper::getTermIdByReference('dealstore', $metadata->merchant_id, $metadata->merchant_name);

    if ($metadata->coupon_code && strlen($metadata->coupon_code) > 1)
        update_post_meta($coupon['ID'], 'rehub_offer_coupon_mask', 1);
    else
        delete_post_meta($coupon['ID'], 'rehub_offer_coupon_mask');

    if ($store) {

        wp_set_post_terms($coupon['ID'], $store->term_id, 'dealstore');
    }

    wp_set_object_terms($coupon['ID'], 'no', 'offerexpiration', false);
}

/**
 * Theme specific code  - Rehub for Store Import. Set logo. 
 */
function coupomated_rehub_store_import($store, $metadata)
{
    $has_brand_image = get_term_meta($store['term_id'], 'brandimage', true);
    if (!$has_brand_image) {
        $attachment_helper = new Coupomated_WP_Attachment_Helper();
        $attachment_url = $attachment_helper->getOrCreateAttachment($metadata->logo);
        update_term_meta($store['term_id'], 'brandimage', $attachment_url);
    }
}




/**
 * Theme specific code  - ClipMyDeals for Store Import. Set logo. 
 */
function coupomated_clipmydeals_store_import($store, $metadata)
{
    $attachment_helper = new Coupomated_WP_Attachment_Helper();
    $attachment_url = $attachment_helper->getOrCreateAttachment($metadata->logo);
    $_POST =  cmd_get_taxonomy_options($store['term_id'], 'stores');;


    $_POST['store_url'] = $metadata->website;
    $_POST['store_aff_url'] = $metadata->affiliate_link;

    // following fields are to be set if its not already set, else retain it as is

    $_POST['store_logo'] = $_POST['store_logo'] ?? $attachment_url;
    $_POST['popular'] = $_POST['popular'] ?? 'no';
    $_POST['store_intro'] = $_POST['store_intro'] ?? '';
    $_POST['store_banner'] = $_POST['store_banner'] ?? null;
    $_POST['store_color'] = $_POST['store_color'] ?? null;
    $_POST['map'] = $_POST['map'] ?? null;
    $_POST['video'] = $_POST['video'] ?? null;
    $_POST['page_title'] = $_POST['page_title'] ?? null;
    $_POST['store_category'] = $_POST['store_category'] ?? '';
    $_POST['store_display_priority'] = $_POST['store_display_priority'] ?? 0;
    $_POST['status'] = $_POST['status'] ?? 'active';

    clipmydeals_save_store_custom_fields($store['term_id']);
}


/**
 * Theme specific code  - ClipMyDeals for coupon import. Set Type 
 */
function coupomated_clipmydeals_coupon_import($coupon, $metadata)
{

    update_post_meta($coupon['ID'], 'cmd_type', strlen($metadata->coupon_code) > 1 ? 'code' : 'deal');
    update_post_meta($coupon['ID'], 'cmd_start_date', date('Y-m-d'));

    if (is_null($metadata->end_date))
        update_post_meta($coupon['ID'], 'cmd_valid_till', '');
    else
        update_post_meta($coupon['ID'], 'cmd_valid_till', date('Y-m-d', strtotime($metadata->end_date)));

    if (is_null($metadata->verified_at))
        update_post_meta($coupon['ID'], 'cmd_verified_on', '');
    else
        update_post_meta($coupon['ID'], 'cmd_verified_on', date('Y-m-d', strtotime($metadata->verified_at)));



    update_post_meta($coupon['ID'], 'cmd_display_priority',  intval(get_post_meta($coupon['ID'], 'cmd_display_priority', true)));


    $store = Coupomated_TaxonomyHelper::getTermIdByReference('stores', $metadata->merchant_id, $metadata->merchant_name);

    if ($store) {

        wp_set_post_terms($coupon['ID'], [intval($store->term_id)], 'stores');
    }
}

function deleteCoupons()
{
    global $wpdb;
    global $coupomated_theme_configs;
    $coupon_table_name = $wpdb->prefix . 'coupomated_coupons';
    $theme = get_option('coupomated_import_theme');
    $post_type  = $coupomated_theme_configs[$theme]['type']['coupon'];
    $wpdb->query("UPDATE {$wpdb->prefix}posts SET post_status = 'trash' WHERE post_type = '{$post_type}'  AND ID IN (SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key  = 'coupomated_rid' AND meta_value NOT IN ( SELECT coupon_id from {$coupon_table_name}))");
}


/**
 * This code defines a class  `Coupomated_TaxonomyHelper`  in a WordPress plugin, which provides static methods to create or retrieve terms in a taxonomy based on a reference ID and name. The  `firstOrCreate`  method checks if a term with the given reference ID already exists and returns it if found. Otherwise, it creates a new term with the given name and updates the reference ID as a meta field.
 */
class Coupomated_TaxonomyHelper
{
    public static function firstOrCreate($taxonomy, $referenceId, $name)
    {

        $term = self::getTermIdByReference($taxonomy, $referenceId, $name);

        if ($term) {

            return $term;
        } else {

            $term = wp_insert_term($name, $taxonomy);

            if (is_wp_error($term)) {

                return false;
            } else {

                self::updateTermReference($term['term_id'], $referenceId);


                return $term;
            }
        }
    }

    public static function getTermIdByReference($taxonomy, $referenceId, $name)
    {
        $term = get_terms([
            'taxonomy'   => $taxonomy,
            'meta_key'   => 'coupomated_rid',
            'meta_value' => $referenceId,
            'hide_empty' => false,
        ]);

        if (!empty($term)) {
            return $term[0];
        }

        $term = get_term_by('name', $name, $taxonomy);
        if ($term) return $term;


        $term = get_term_by('id', $referenceId, $taxonomy);
        if ($term) return $term;



        return false;
    }

    public static function updateTermReference($termId, $referenceId)
    {
        update_term_meta($termId, 'coupomated_rid', $referenceId);
    }
}



/**
 * This code defines a class  `Coupomated_WP_Post_Helper`  in a WordPress plugin, which provides a static method  `firstOrCreate`  to create a new post or retrieve an existing post based on a custom ID. The method checks if a post with the given custom ID already exists and returns it if found. Otherwise, it creates a new post with the given post type, title, and custom ID as a meta field.
 */
class Coupomated_WP_Post_Helper
{
    public static function firstOrCreate($post_type, $custom_id, $title)
    {

        $existing_post = get_posts(array(
            'post_type'      => $post_type,
            'meta_key'       => 'coupomated_rid',
            'meta_value'     => $custom_id,
            'posts_per_page' => 1,
        ));


        if (!empty($existing_post)) {
            return $existing_post[0];
        }


        $new_post = array(
            'post_type'    => $post_type,
            'post_title'   => $title,
            'post_status'  => 'publish',
        );

        $new_post_id = wp_insert_post($new_post);
        update_post_meta($new_post_id, 'coupomated_rid', $custom_id);


        return get_post($new_post_id);
    }

    public static function getStoreByReference($post_type, $custom_id, $title)
    {
       

        $existing_post = get_posts(array(
            'post_type'      => $post_type,
            'meta_key'       => 'coupomated_rid',
            'meta_value'     => $custom_id,
            'posts_per_page' => 1,
        ));
        
        if (!empty($existing_post)) {
            return $existing_post[0];
        }

        // get post by title
        $existing_post = get_posts(array(
            'post_type'      => $post_type,
            'title'     => $title,
            'posts_per_page' => 1,
            'post_status'    => 'publish',
        ));
        if (!empty($existing_post)) {
            return $existing_post[0];
        }
        
        return false;
    }
}

/**
 * This code defines a class  `Coupomated_WP_Attachment_Helper`  in a WordPress plugin, which handles image attachments. It can either find an existing image attachment by its URL or create a new one if it doesn't exist, download the image data, and insert the image as an attachment in the WordPress database.
 */
class Coupomated_WP_Attachment_Helper
{
    protected $wpdb;
    public $attachment_id;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->attachment_id = null;
    }

    public function getOrCreateAttachment($image_url)
    {
        $attachment = $this->findAttachmentByImageName($image_url);

        if ($attachment) {
            return $attachment;
        }

        return $this->createAttachment($image_url);
    }

    protected function findAttachmentByImageName($image_url)
    {
        $image_name = basename($image_url);

        $attachment_id = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT post_id FROM {$this->wpdb->postmeta}
                WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s",
                '%' . $this->wpdb->esc_like($image_name) . '%'
            )
        );

        if ($attachment_id) {
            $this->attachment_id = $attachment_id;
            return wp_get_attachment_url($attachment_id);
        }

        return null;
    }

    protected function createAttachment($image_url)
    {
        $image_data = $this->downloadImage($image_url);
        $attachment_id = $this->insertAttachment($image_data, $image_url);

        if ($attachment_id) {
            $this->attachment_id = $attachment_id;
            return wp_get_attachment_url($attachment_id);
        }

        return null;
    }

    protected function downloadImage($image_url)
    {
        $response = wp_remote_get($image_url);

        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            return wp_remote_retrieve_body($response);
        }

        return null;
    }

    protected function insertAttachment($image_data, $image_url)
    {
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['path'] . '/' . basename($image_url);
        $file_name = basename($file_path);

        if (wp_mkdir_p($upload_dir['path'])) {
            if (file_put_contents($file_path, $image_data)) {
                $attachment = array(
                    'guid'           => $upload_dir['url'] . '/' . $file_name,
                    'post_mime_type' => wp_check_filetype($file_name)['type'],
                    'post_title'     => sanitize_file_name($file_name),
                    'post_content'   => '',
                    'post_status'    => 'inherit'
                );

                $attachment_id = wp_insert_attachment($attachment, $file_path);
                if (!is_wp_error($attachment_id)) {
                    wp_update_attachment_metadata($attachment_id, wp_generate_attachment_metadata($attachment_id, $file_path));
                    return $attachment_id;
                }
            }
        }

        return null;
    }
}
