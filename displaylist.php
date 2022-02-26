<?php

global $wpdb;
$tablename = $wpdb->prefix."whosintoday";

// Delete record
if(isset($_GET['delid'])){
  $delid = $_GET['delid'];
  $wpdb->query("DELETE FROM ".$tablename." WHERE id=".$delid);
}
?>
<div class="wrap">
<h1>All Entries</h1>

<p>Showing data from the previous four weeks.</p>

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
  
  $entriesList = $wpdb->get_results("SELECT * FROM ".$tablename." WHERE `day` >= '" . $lastfourweeks . "' AND `day` <= '" . date("Y-m-d",time()) . "' order by day DESC, userid ASC");
  
  //define $nowday;
  
  if(count($entriesList) > 0){
    $count = 1;
    foreach($entriesList as $entry){
      $id = $entry->id;
	  $userid = $entry->userid;
      $date = $entry->day;
	  $location = $entry->location;
	  
	  $user = get_user_by('id',$userid);
	  
	  if ($date != $nowday) { echo $tablestart . "<h3>" . WITTidyDate($date) . "</h3>"; $nowday = $date; }
	  
	  if (!$user->first_name OR !$user->last_name) { $username = $user->user_login; } else { $username = $user->first_name." ".$user->last_name; }

      echo "<tr>
      <td>".$count."</td>
	  <td>".$username." (".$entry->userid.")</td>
      <td>".$date."</td>
      <td>".$location."</td>
      <td><button><a href='?page=WhosInToday&amp;delid=".$id."'>Delete</a></button></td>
      </tr>
      ";
      $count++;
	  $nowday = $date;
   }
 }else{
   echo "<tr><td colspan='5'>No record found</td></tr>";
 }
?>
</table>
</div>