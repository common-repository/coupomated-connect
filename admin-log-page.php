<?php
if (!defined('ABSPATH')) exit;
/**
 * This code is a WordPress plugin that allows users to import coupons, stores, and categories from an API. It includes functionality to schedule coupon imports, display import logs, and re-trigger imports if needed.
 */
?>
<div class="wrap">

    <?php include('cpd-header.php')  ?>

    <?php

    if (isset($_POST['import_id']) && isset($_POST['import_for'])) {
        
        
        check_admin_referer('retrigger_import','retrigger_import_nonce');
        
        if (isset($_POST['retrigger_import_nonce']) && wp_verify_nonce( sanitize_text_field( wp_unslash ($_POST['retrigger_import_nonce'])), 'retrigger_import')) {
            if ($_POST['import_for'] == 'coupon')
                wp_schedule_single_event(time(), 'coupomated_create_coupon_event', [intval($_POST['import_id'])]);
            else  if ($_POST['import_for'] == 'store')
                wp_schedule_single_event(time(), 'coupomated_create_store_event', [intval($_POST['import_id'])]);

            echo '<div class="updated"><p>' . esc_html(ucfirst(sanitize_text_field($_POST['import_for']))) . ' Import with #ID  -  ' . intval($_POST['import_id']) . ' has been triggered, it will soon be imported.</p></div>';
        }
    }

    global $wpdb;
    $import_log_table_name = $wpdb->prefix . 'coupomated_import_log';
    $per_page = 20;
    $page = isset($_GET['page_num']) ? absint($_GET['page_num']) : 1;
    $offset = $page > 1 ? ($page - 1) * $per_page : 0;


    $import_logs = $wpdb->get_results($wpdb->prepare("SELECT * FROM $import_log_table_name ORDER BY import_date DESC LIMIT %d OFFSET %d", $per_page, $offset), ARRAY_A);
    $total_logs = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $import_log_table_name"));
    $total_pages = ceil($total_logs / $per_page);
    if (!empty($import_logs)) { ?>


        <table class="wp-list-table widefat striped cpd-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Total Records</th>
                    <th>Processed Records</th>
                    <th>Status</th>
                    <!-- <th>Time Taken</th> -->

                </tr>
            </thead>
            <tbody>


                <?php foreach ($import_logs as $import_log) : ?>
                    <tr>
                        <td><?php echo esc_html($import_log['id']); ?></td>
                        <td><?php echo esc_html($import_log['import_date']); ?></td>
                        <td><?php echo esc_html(ucwords(str_replace('_', ' ', $import_log['import_for']))); ?></td>
                        <td><?php echo esc_html($import_log['total_records']); ?></td>
                        <td><?php echo esc_html($import_log['processed_records']); ?></td>
                        <td>
                            <?php
                            echo esc_html(ucfirst($import_log['status']));
                            if ($import_log['status'] == 'error') {
                                echo esc_html(' - ' . $import_log['summary']);
                            }
                            if (in_array($import_log['import_for'], ['store', 'coupon']) && $import_log['status'] != 'Imported') : ?>
                                <br>
                                <form method="POST" action="<?php echo esc_url(admin_url('admin.php')); ?>?page=coupon-import-log">
                                    <?php wp_nonce_field('retrigger_import', 'retrigger_import_nonce'); ?>
                                    <input type="hidden" name="import_for" value="<?php echo esc_html($import_log['import_for']); ?>">
                                    <input type="hidden" name="import_id" value="<?php echo esc_html($import_log['id']); ?>">
                                    <input type="submit" value="Re-Trigger" class="button">
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

            </tbody>
        </table>


        <div class="tablenav pagination">
            <div class="tablenav-pages">
                <!-- <span class="pagination-links"> -->
                <?php


                if ($total_pages > 1) {




                    if ($page > 1) {
                        echo '<a class="prev-page" href="' . esc_url(add_query_arg('page_num', ($page - 1))) . '" class="prev prev-page button">Prev</a>';
                    }

                    $max_pages = 3;
                    $start_page = max($page - $max_pages, 1);
                    $end_page = min($page + $max_pages, $total_pages);

                    if ($start_page > 1) {
                        echo '<a class="first-page button" href="' . esc_url(add_query_arg('page_num', 1)) . '">1</a>';
                        if ($start_page > 2) {
                            echo '<span>...</span>';
                        }
                    }

                    for ($i = $start_page; $i <= $end_page; $i++) {
                        echo '<a href="' . esc_url(add_query_arg('page_num', $i)) . '"' . ($page == $i ? ' class="active button button-primary current-page"' : 'class="button"') . '>' . intval($i) . '</a>';
                    }

                    if ($end_page < $total_pages) {
                        if ($end_page < $total_pages - 1) {
                            echo '<span>...</span>';
                        }
                        echo '<a  class="last-page button" href="' . esc_url(add_query_arg('page_num', $total_pages)) . '">' . intval($total_pages) . '</a>';
                    }

                    if ($page < $total_pages) {
                        echo '<a class="next-page button" href="' . esc_url(add_query_arg('page_num', ($page + 1))) . '" class="next next-page button">Next</a>';
                    }

                    echo '</div>';
                }
                ?>
                <!-- </span> -->
            </div>
        </div>
    <?php

    } else {
        echo '
        <div class="error notice">
            <p>
                <strong>
                    No import logs found.
                </strong>
            </p>
        </div>
        ';
    }
    ?>

    <?php include('cpd-footer.php')  ?>

</div>