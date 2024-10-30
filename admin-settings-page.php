<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * The provided PHP code is part of a WordPress plugin that fetches coupon, store, and category data from an API and imports it into a WordPress website. The code does the following:
 * 1. Checks if an API key is available and valid.
 * 2. If the API key is valid, it fetches user subscription details from the API, formats the data, and displays it in a user-friendly format.
 * 3. The code provides an interface for the user to manually sync categories, import stores, and import coupons. These actions can be triggered by submitting a form.
 * 4. The code also provides an interface for the user to set API settings, including the API key, the frequency of imports, and the theme of the website.
 * 5. If the API key is not valid, it shows an error message to the user.
 */

 coupomated_check_wp_cron_status_notice();


$has_api_key = (bool) (strlen(get_option('coupomated_import_apikey', '')) == 32);
$offer = coupomated_call_api('offer.json');

if (isset($offer['title']))
    echo '<div class="updated"><h2> ' . esc_html($offer['title']) . ' - <a href="' .  esc_html($offer['link']) . '" target="_blank">' . esc_html($offer['btn']) . '</a> </h2></div>';


if ($has_api_key) {

    // Convert JSON response to an associative array
    $userSubscription = coupomated_call_api('user');

    if (isset($userSubscription['title'])) {
        $userSubscriptionHTML = 'Unable to valid API, Error - ' . $userSubscription['title'];
        $has_api_key = false;
    } else {

        // Map plan codes to their corresponding names
        $planNames = [
            'STD' => 'Standard',
            'PRE' => 'Premium',
            'ENT' => 'Enterprise',
            'STR' => 'Starter',
        ];

        // Format subscription status
        $subscriptionStatus = $userSubscription['subscription_status'];

        // Format plan name
        $planCode = $userSubscription['plan'];
        $planName = isset($planNames[$planCode]) ? $planNames[$planCode] : 'Unknown';

        // Format subscription dates
        $subscriptionStartsAt = date('F j, Y, g:i a', strtotime($userSubscription['subscription_starts_at']));
        $subscriptionEndsAt = date('F j, Y, g:i a', strtotime($userSubscription['subscription_ends_at']));

        // Format API usage
        $apiUsage = $userSubscription['api_usage'];
        $apiQuota = $apiUsage['quota'];
        $apiUsage = $apiUsage['usage'];


        $userSubscriptionHTML = "
                <div class='subscription-details'>
                    <h2>User Subscription Details</h2>
                    <p><strong>User Name:</strong> " . esc_html($userSubscription['user_name']) . "</p>
                    <p><strong>Email:</strong> " . esc_html($userSubscription['user_email']) . "</p>
                    <p><strong>Plan:</strong> " . esc_html($planName) . "</p>
                    <p><strong>Subscription Status:</strong> " . esc_html($subscriptionStatus) . "</p>
                    <p><strong>Subscription Starts At:</strong> " . esc_html($subscriptionStartsAt) . "</p>
                    <p><strong>Subscription Ends At:</strong> " . esc_html($subscriptionEndsAt) . "</p>
                    <p><strong>API Quota:</strong> " . esc_html($apiQuota) . "</p>
                    <p><strong>API Usage:</strong> " . esc_html($apiUsage) . "</p>
                </div>
                ";

    }
} else {
    $userSubscriptionHTML = 'Please save valid API Key first';
}

if (isset($_POST['action']) && $_POST['action'] == 'sync_cats') {
    $statutes  = coupomated_sync_categories();
    foreach ($statutes as $cat_import_for => $imported)
        echo '<div class="updated"><p>' . esc_html(ucwords(str_replace('_',' ',$cat_import_for))) . ' sync -  ' . esc_html($imported) . '</p></div>';
}


if (isset($_POST['action']) && $_POST['action'] === 'fetch_api') {

    check_admin_referer('coupomated_fetch_api');

    $import_status = coupomated_import_stores(true);
    // $import_status = 'Import has been run';

    echo '<div class="updated"><p>' . esc_html($import_status) . '.</p></div>';
}




if (isset($_POST['action']) && $_POST['action'] === 'fetch_coupon_api') {

    check_admin_referer('coupomated_fetch_coupon_api');

    $import_status = coupomated_import_coupons(true);
    // $import_status = 'Import has been run';
    echo '<div class="updated"><p>' . esc_html($import_status) . '</p></div>';
}

?>

<div class="wrap">

    <?php include('cpd-header.php'); ?>
     
    <div class="cpd-card">
        <div class="cpd-card-body">
            <div class="cpd-wrapper">
                <div class="cpd-settings cpd-col">
                    <div class="cpd-section">
                        <h2 class="cpd-section-heading">API Settings</h2>

                        <form method="post" action="options.php">
                            <?php settings_fields('coupon-import-plugin-settings-group'); ?>
                            <?php do_settings_sections('coupon-import-settings'); ?>
                            <div class="form-group-wrapper">
                                <div class="form-group">
                                    <label for="coupomated_apikey">API Key:</label>
                                    <input type="text" class="form-control" id="coupomated_apikey" name="coupomated_import_apikey" value="<?php echo esc_attr(get_option('coupomated_import_apikey')); ?>">
                                </div>

                                <div class="form-group hidden">
                                    <label for="plan">Plan:</label>
                                    <select class="form-control" id="plan" name="coupomated_import_plan">
                                        <option value="basic" <?php selected(get_option('coupomated_import_plan'), 'basic'); ?>>Basic</option>
                                        <option value="standard" <?php selected(get_option('coupomated_import_plan'), 'standard'); ?>>Standard</option>
                                        <option value="premium" <?php selected(get_option('coupomated_import_plan'), 'premium'); ?>>Premium</option>
                                    </select>
                                </div>
								
								
                                <div class="form-group">
                                    <label for="theme">Theme:</label>
                                    <select class="form-control" id="theme" name="coupomated_import_theme">
                                        <option value="rehub" <?php selected(get_option('coupomated_import_theme'), 'rehub'); ?>>Rehub</option>
                                        <option value="couponorb" <?php selected(get_option('coupomated_import_theme'), 'couponorb'); ?>>CouponORB</option>
										<option value="clipmydeals" <?php selected(get_option('coupomated_import_theme'), 'clipmydeals'); ?>>ClipMyDeals</option>
                                        <option value="couponer" <?php selected(get_option('coupomated_import_theme'), 'couponer'); ?>>Couponer[Coming Soon]</option>
                                        <option value="couponxl" <?php selected(get_option('coupomated_import_theme'), 'couponxl'); ?>>CouponXL[Coming Soon]</option>
                                    </select>
                                </div>
								

                                <div class="form-group">
                                    <label for="store_import_frequency">Store - Frequency in Hours:</label>
                                    <input type="number" class="form-control" id="store_import_frequency" name="coupomated_import_store_frequency" value="<?php echo esc_attr(intval(get_option('coupomated_import_store_frequency', 24))); ?>">
                                    <p>Every "x" hours, the import will run again. Ex: 12 means it will run 2 times a day. Set 0 if you don't want it to run again.</p>
                                </div>

                                <div class="form-group">
                                    <label for="coupomated_frequency">Coupon - Frequency in Hours:</label>
                                    <input type="number" class="form-control" id="coupomated_frequency" name="coupomated_import_coupon_frequency" value="<?php echo esc_attr(intval(get_option('coupomated_import_coupon_frequency', 12))); ?>">
                                    <p>Every "x" hours, the import will run again. Ex: 12 means it will run 2 times a day.</p>
                                </div>
								
								 <div class="form-group">
                                    <label for="store_batch_size">Store Import Batch Size:</label>
                                    <input type="number" class="form-control" id="store_batch_size" name="coupomated_import_store_batch_size" value="<?php echo esc_attr(intval(get_option('coupomated_import_store_batch_size', 50))); ?>">
                                    <p>How many stores to create per batch. If you have good server and want import to finish sooner, increase this number.</p>
                                </div>


								<div class="form-group">
                                    <label for="coupon_batch_size">Coupon Import Batch Size:</label>
                                    <input type="number" class="form-control" id="coupon_batch_size" name="coupomated_import_coupon_batch_size" value="<?php echo esc_attr(intval(get_option('coupomated_import_coupon_batch_size', 200))); ?>">
                                    <p>How many coupons to create per batch. If you have good server and want import to finish sooner, increase this number.</p>
                                </div>

                            </div>

                            <?php submit_button('Save Settings'); ?>
                        </form>
                    </div>

                    <div class="cpd-section hidden">

                        <b> Next Store Run Time </b> -  <?php echo esc_html(coupomated_get_next_event_runtime('stores_api_import_event'));?>
                        <br>
                        <b> Next Coupon Run Time </b> - <?php echo esc_html(coupomated_get_next_event_runtime('coupons_api_import_event'));?>
                    </div>

                </div>

                <div class="cpd-subscription-info cpd-col">

                    <?php echo wp_kses_post($userSubscriptionHTML); ?>

                </div>
            </div>
        </div>
    </div>

    <div class="cpd-card">
        <div class="cpd-card-body">
            <?php if ($has_api_key) : ?>
                <div class="cpd-section">
                    <h2 class="cpd-section-heading">Import Data Manually</h2>

                    <p style="color:red"> Make sure you have set the correct theme.</p>

                </div>

                <div class="cpd-wrapper-bottom">



                    <div class="cpd-section">

                        <form method="post">
                            <input type="hidden" name="action" value="sync_cats">
                            <input type="submit" value="Sync Categories" class="button button-primary">
                        </form>
                    </div>

                    <div class="cpd-section">
                        <form method="post">
                            <?php wp_nonce_field('coupomated_fetch_api'); ?>
                            <input type="hidden" name="page" value="<?php echo esc_html(sanitize_text_field($_REQUEST['page'])); ?>">
                            <input type="hidden" name="action" value="fetch_api">
                            <input type="submit" class="button button-primary" value="Run Import Stores">
                        </form>
                    </div>

                    <div class="cpd-section">
                        <form method="post">
                            <?php wp_nonce_field('coupomated_fetch_coupon_api'); ?>
                            <input type="hidden" name="page" value="<?php echo esc_html(sanitize_text_field($_REQUEST['page'])); ?>">
                            <input type="hidden" name="action" value="fetch_coupon_api">
                            <input type="submit" class="button button-primary" value="Run Import Coupons">
                        </form>
                    </div>
                </div>

            <?php endif; ?>
        </div>
    </div>
    
    <?php include('cpd-footer.php'); ?>

</div>