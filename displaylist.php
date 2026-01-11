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
}
?>
<div class="wrap">
<h1><?php _e('All Entries', 'whos-in-today'); ?></h1>

<p><?php _e('Showing data from the previous four weeks.', 'whos-in-today'); ?></p>

<?php $tablestart = "<table class='wp-list-table widefat fixed striped table-view-list'>

  <tr>
   <th>#</th>
   <th>Name (User ID)</th>
   <th>Date</th>
   <th colspan='2'>Location</th>
  </tr>";
  
  ?>
    
  <?php
  // Select records
  
  WITCalendar();
  
  $lastfourweeks = date("Y-m-d",WITBeginWeek(time() - 2419200));
  $today = date("Y-m-d",time());

  $entriesList = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$tablename." WHERE `day` >= %s AND `day` <= %s order by day DESC, userid ASC", $lastfourweeks, $today));

  $nowday = '';

  // Pre-load all users to avoid N+1 query issue
  $user_ids = array_unique(array_column($entriesList, 'userid'));
  $users_cache = array();
  if (!empty($user_ids)) {
    $users = get_users(array('include' => $user_ids));
    foreach ($users as $user) {
      $users_cache[$user->ID] = $user;
    }
  }

  if(count($entriesList) > 0){
    $count = 1;
    foreach($entriesList as $entry){
      $id = $entry->id;
	  $userid = $entry->userid;
      $date = $entry->day;
	  $location = $entry->location;

	  $user = isset($users_cache[$userid]) ? $users_cache[$userid] : false;
	  
	  if ($date != $nowday) { echo $tablestart . "<h3>" . WITTidyDate($date) . "</h3>"; $nowday = $date; }
	  
	  if ($user && (!$user->first_name OR !$user->last_name)) { $username = $user->user_login; } elseif ($user) { $username = $user->first_name." ".$user->last_name; } else { $username = 'Unknown User'; }

      $delete_url = wp_nonce_url('?page=WhosInToday&amp;delid='.$id, 'delete_wit_entry_'.$id);
      echo "<tr>
      <td>".esc_html($count)."</td>
	  <td>".esc_html($username)." (".esc_html($entry->userid).")</td>
      <td>".esc_html($date)."</td>
      <td>".esc_html($location)."</td>
      <td><button><a href='".esc_url($delete_url)."'>Delete</a></button></td>
      </tr>
      ";
      $count++;
	  $nowday = $date;
   }
 }else{
   echo "<tr><td colspan='5'>" . esc_html__('No record found', 'whos-in-today') . "</td></tr>";
 }
?>
</table>
</div>