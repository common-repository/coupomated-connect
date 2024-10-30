<?php
if (!defined('ABSPATH')) exit;
/**
 * This code retrieves and displays a list of imported stores from a custom table in WordPress. It also includes pagination functionality for navigating and searching through the list of stores.
 */
global $wpdb;
global $store_table_name;
$store_table_name = $wpdb->prefix . 'coupomated_stores';

$per_page = 10;
$page = isset($_GET['page_num']) ? absint($_GET['page_num']) : 1;
$offset = $page > 1 ? ($page - 1) * $per_page : 0;



$search_term = isset($_GET['store_search']) ? sanitize_text_field($_GET['store_search']) : '';

$per_page = intval($per_page);
$offset = intval($offset);

$stores = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM $store_table_name WHERE name LIKE %s OR website LIKE %s OR category_names LIKE %s OR import_summary LIKE %s
        LIMIT %d OFFSET %d",
        '%' . $wpdb->esc_like($search_term) . '%',
        '%' . $wpdb->esc_like($search_term) . '%',
        '%' . $wpdb->esc_like($search_term) . '%',
        '%' . $wpdb->esc_like($search_term) . '%',
        $per_page,
        $offset
    ),
    ARRAY_A
);

$total_stores = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COUNT(*) FROM $store_table_name WHERE name LIKE %s OR website LIKE %s OR category_names LIKE %s OR import_summary LIKE %s",
        '%' . $wpdb->esc_like($search_term) . '%',
        '%' . $wpdb->esc_like($search_term) . '%',
        '%' . $wpdb->esc_like($search_term) . '%',
        '%' . $wpdb->esc_like($search_term) . '%'
    )
);

$total_pages = ceil($total_stores / $per_page);

?>

<div class="wrap">

    <?php include('cpd-header.php')  ?>

    <div class="cpd-section cpd-search-section">
        <form method="GET" action="<?php echo esc_url(admin_url('admin.php')); ?>">
            <input type="hidden" name="page" value="coupon-import-stores">
            <label for="store_search">Search:</label>
            <input type="text" placeholder="Name,Website,Category" name="store_search" id="store_search" value="<?php echo esc_html(sanitize_text_field($_GET['store_search'] ?? '')); ?>">
            <input type="submit" value="Search" class="button">
        </form>
    </div>

    <?php if (!empty($stores)) : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Website</th>
                    <!-- <th>Domain Name</th> -->
                    <!-- <th>Country</th> -->
                    <th>Logo</th>
                    <!-- <th>Stars</th> -->
                    <th>Featured</th>
                    <!-- <th>Category IDs</th> -->
                    <th>Categories</th>
                    <th>Affiliate Link</th>
                    <th>Import Status</th>
                    <th>Summary</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stores as $store) : ?>
                    <tr>
                        <td><?php echo esc_html($store['store_id']) ?></td>
                        <td><?php echo esc_html($store['name']) ?></td>
                        <td><?php echo esc_html($store['website']) ?></td>
                        <!-- <td><?php echo esc_html($store['domain_name']) ?></td> -->
                        <!-- <td><?php echo esc_html($store['country']) ?></td> -->
                        <td><img class="mw-100" src="<?php echo esc_html($store['logo']) ?>" loading="lazy"></td>
                        <!-- <td><?php echo esc_html($store['stars']) ?></td> -->
                        <td><?php echo boolval($store['featured']) ? 'Yes' : 'No'; ?></td>
                        <!-- <td><?php echo esc_html($store['category_ids']) ?></td> -->
                        <td><?php echo esc_html($store['category_names']) ?></td>
                        <td><?php echo esc_html($store['affiliate_link']) ?></td>
                        <td><?php echo esc_html($store['import_status']) ?></td>
                        <td><?php echo esc_html($store['import_summary']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($total_pages > 1) : ?>
            <?php $base_url = remove_query_arg('page_num'); // Remove existing page_num parameter from URL.

            if (!empty($search_term)) {
                $base_url = add_query_arg('store_search', $search_term, $base_url); // Add the search term to the URL.
            }
            ?>

            <div class="tablenav pagination">
                <div class="tablenav-pages">
                    <?php if ($page > 1) : ?>
                        <a href="<?php echo esc_url(add_query_arg('page_num', ($page - 1))); ?>" class="prev prev-page button">Prev</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++) : ?>
                        <a href="<?php echo esc_url(add_query_arg('page_num', $i)); ?>" <?php echo boolval($page == $i) ? 'class="active button button-primary"' : 'class="button"'; ?>><?php echo intval($i); ?></a>
                    <?php endfor; ?>

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
                    No stores found.
                </strong>
            </p>
        </div>
    <?php endif; ?>

    <?php include('cpd-footer.php')  ?>

</div>

<style>

</style>