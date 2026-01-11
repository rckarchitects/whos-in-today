<?php

// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

// Check user capabilities
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

global $wpdb;
$tablename = $wpdb->prefix."whosintoday";

// Delete record
if(isset($_GET['delid'])){
  // Verify nonce for security
  if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'delete_wit_entry_' . intval($_GET['delid']))) {
    wp_die('Security check failed');
  }
  $delid = intval($_GET['delid']);
  $wpdb->query($wpdb->prepare("DELETE FROM ".$tablename." WHERE id=%d", $delid));

  // Redirect back to calendar to refresh view
  $redirect_url = admin_url('admin.php?page=WhosInToday');
  if (isset($_GET['wit_month']) && isset($_GET['wit_year'])) {
    $redirect_url = admin_url('admin.php?page=WhosInToday&wit_month=' . intval($_GET['wit_month']) . '&wit_year=' . intval($_GET['wit_year']));
  }
  wp_redirect($redirect_url);
  exit;
}
?>
<div class="wrap">
<h1><?php _e('All Entries', 'whos-in-today'); ?></h1>

<?php
// Display calendar view
if (function_exists('WITAdminCalendar')) {
    WITAdminCalendar();
} else {
    echo '<p>' . esc_html__('Calendar view not available.', 'whos-in-today') . '</p>';
}
?>

</div>
