<?php
if (!defined('ABSPATH')) exit;
/**
 * 
 * The given PHP code is designed to work with a WordPress plugin for importing coupons, stores, and categories from a feed API. 
 * The code first sets up global variables for the WordPress database ($wpdb) and the coupon table name. It then sets parameters for pagination, including how many coupons to display per page, and which page to display based on the user's input or the default settings.
 * Next, it queries the database to get the coupons for the current page and the total number of coupons. It calculates the total number of pages based on the total number of coupons and the number of coupons per page.
 * The code then generates a HTML table to display the coupons. It loops through each coupon, displaying the title, description, discount, coupon code, categories, affiliate link, merchant name, import status, and summary. 
 * If there are multiple pages of coupons, the code generates pagination links. The user can navigate to the previous page, the next page, or any page number in between. If there are no coupons found, it displays a message to the user.
 * It has ability to search the coupons.
 * 
 */

global $wpdb;
global $coupon_table_name;
$coupon_table_name = $wpdb->prefix . 'coupomated_coupons';


$per_page = 10;
$page = isset($_GET['page_num']) ? absint($_GET['page_num']) : 1;
$offset = $page > 1 ? ($page - 1) * $per_page : 0;

$like_search_term = isset($_GET['coupon_search']) ? sanitize_text_field($_GET['coupon_search']) : '';

$query = $wpdb->prepare(
    "SELECT * FROM $coupon_table_name 
    WHERE title LIKE %s
    OR description LIKE %s
    OR coupon_code LIKE %s
    OR category_names LIKE %s
    OR import_status LIKE %s
    OR affiliate_link LIKE %s
    OR merchant_name LIKE %s
    LIMIT %d OFFSET %d",
    '%' . $wpdb->esc_like($like_search_term) . '%',
    '%' . $wpdb->esc_like($like_search_term) . '%',
    '%' . $wpdb->esc_like($like_search_term) . '%',
    '%' . $wpdb->esc_like($like_search_term) . '%',
    '%' . $wpdb->esc_like($like_search_term) . '%',
    '%' . $wpdb->esc_like($like_search_term) . '%',
    '%' . $wpdb->esc_like($like_search_term) . '%',
    $per_page,
    $offset
);

$coupons = $wpdb->get_results($query, ARRAY_A);

$total_coupons = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COUNT(*) FROM $coupon_table_name 
        WHERE title LIKE %s
        OR description LIKE %s
        OR coupon_code LIKE %s
        OR category_names LIKE %s
        OR import_status LIKE %s
        OR affiliate_link LIKE %s
        OR merchant_name LIKE %s",
        '%' . $wpdb->esc_like($like_search_term) . '%',
        '%' . $wpdb->esc_like($like_search_term) . '%',
        '%' . $wpdb->esc_like($like_search_term) . '%',
        '%' . $wpdb->esc_like($like_search_term) . '%',
        '%' . $wpdb->esc_like($like_search_term) . '%',
        '%' . $wpdb->esc_like($like_search_term) . '%',
        '%' . $wpdb->esc_like($like_search_term) . '%'
    )
);
$total_pages = ceil($total_coupons / $per_page);


?>



<div class="wrap">

    <?php include('cpd-header.php') ?>

    <div class="cpd-section cpd-search-section">
        <form method="GET" action="<?php echo esc_url(admin_url('admin.php')); ?>">
            <input type="hidden" name="page" value="coupon-import-coupons">
            <label for="coupon_search">Search:</label>
            <input type="text" placeholder="Title,Coupon,Merchant" name="coupon_search" id="coupon_search" value="<?php echo esc_html(sanitize_text_field(($_GET['coupon_search'] ?? ''))); ?>">
            <input type="submit" value="Search" class="button">
        </form>
    </div>

    <?php if (!empty($coupons)) : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Discount</th>
                    <th>Coupon Code</th>
                    <!-- <th>Plain Link</th> -->
                    <!-- <th>Exclusive</th> -->
                    <!-- <th>Start Date</th> -->
                    <!-- <th>End Date</th> -->
                    <!-- <th>Verified At</th> -->
                    <!-- <th>Created At</th> -->
                    <!-- <th>Updated At</th> -->
                    <!-- <th>Category IDs</th> -->
                    <th>Categories</th>
                    <!-- <th>Category Names List</th> -->
                    <th>Affiliate Link</th>
                    <!-- <th>Merchant Logo</th> -->
                    <th>Merchant Name</th>
                    <th>Import Status</th>
                    <th>Summary</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($coupons as $coupon) : ?>
                    <tr>
                        <td><?php echo esc_html($coupon['title']) ?></td>
                        <td><?php echo esc_html($coupon['description']) ?></td>
                        <td><?php echo esc_html($coupon['discount']) ?></td>
                        <td><?php echo esc_html($coupon['coupon_code']) ?></td>
                        <!-- <td><?php echo esc_html($coupon['plain_link']) ?></td> -->
                        <!-- <td><?php echo esc_html($coupon['exclusive']) ?></td> -->
                        <!-- <td><?php echo esc_html($coupon['start_date']) ?></td> -->
                        <!-- <td><?php echo esc_html($coupon['end_date']) ?></td> -->
                        <!-- <td><?php echo esc_html($coupon['verified_at']) ?></td> -->
                        <!-- <td><?php echo esc_html($coupon['created_at']) ?></td> -->
                        <!-- <td><?php echo esc_html($coupon['updated_at']) ?></td> -->
                        <!-- <td><?php echo esc_html($coupon['category_ids']) ?></td> -->
                        <td><?php echo esc_html($coupon['category_names']) ?></td>
                        <!-- <td><?php echo esc_html($coupon['category_names_list']) ?></td> -->
                        <td><?php echo esc_html($coupon['affiliate_link']) ?></td>
                        <!-- <td><?php echo esc_html($coupon['merchant_logo']) ?></td> -->
                        <td><?php echo esc_html($coupon['merchant_name']) ?></td>
                        <td><?php echo esc_html($coupon['import_status']) ?></td>
                        <td><?php echo esc_html($coupon['import_summary']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>


        <?php

        if ($total_pages > 1) :
            $max_pages = 3;
            $start_page = max($page - $max_pages, 1);
            $end_page = min($page + $max_pages, $total_pages);
            $base_url = remove_query_arg('page_num'); // Remove existing page_num parameter from URL.

            if (!empty($search_term)) {
                $base_url = add_query_arg('coupon_search', $search_term, $base_url); // Add the search term to the URL.
            }

        ?>

            <div class="tablenav pagination">
                <div class="tablenav-pages">
                    <?php if ($page > 1) : ?>
                        <a href="<?php echo esc_url(add_query_arg('page_num', ($page - 1))); ?>" class="prev prev-page button">Prev</a>
                    <?php endif; ?>

                    <?php if ($start_page > 1) : ?>
                        <a class="button" href="<?php echo esc_url(add_query_arg('page_num', 1)); ?>">1</a>
                        <?php if ($start_page > 2) : ?>
                            <span>...</span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $start_page; $i <= $end_page; $i++) : ?>
                        <a href="<?php echo esc_url(add_query_arg('page_num', $i)); ?>" <?php echo boolval($page == $i) ? 'class="active button button-primary"' : 'class="button"'; ?>><?php echo intval($i); ?></a>
                    <?php endfor; ?>

                    <?php if ($end_page < $total_pages) : ?>
                        <?php if ($end_page < $total_pages - 1) : ?>
                            <span>...</span>
                        <?php endif; ?>
                        <a class="button" href="<?php echo esc_url(add_query_arg('page_num', $total_pages)); ?>"><?php echo intval($total_pages); ?></a>
                    <?php endif; ?>

                    <?php if ($page < $total_pages) : ?>
                        <a href="<?php echo esc_url(add_query_arg('page_num', ($page + 1))); ?>" class="next next-page button">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>


    <?php else : ?>
        <div class="error notice">
            <p>
                <strong>
                    No coupons found.
                </strong>
            </p>
        </div>
    <?php endif; ?>

    <?php include('cpd-footer.php')  ?>

</div>