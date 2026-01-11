<?php

// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

// Define global locations array
if (!isset($GLOBALS['WITLocations'])) {
    $GLOBALS['WITLocations'] = array('Studio','Home','Holiday','Elsewhere','Unpaid Leave','Absent','Isolating');
}

function WIT_CMAP_API() {
	
	$api_address = "";
	$consumar_key = "";
	$consumar_secret = "";
	
	return array($api_address,$consumar_key,$consumar_secret);
	
}

function WITDisplayAPIData() {
	
	// This is still under development
	
	$api_details = WIT_CMAP_API();
	
	if ($api_details[0]) {
	
	echo "<h3>CMAP</h3><p>API Credentials: " .  implode (",",$api_details) . "</p>";
	
	$url = $api_details . "/OAuth/RequestAuth";
	
	}
		
}

function WITBeginWeek($input) {
	
	$dayofweek = date("w", $input);
	if ($dayofweek == 1) { $dayofweek = 0; }
	elseif ($dayofweek == 2) { $dayofweek = 1; }
	elseif ($dayofweek == 3) { $dayofweek = 2; }
	elseif ($dayofweek == 4) { $dayofweek = 3; }
	elseif ($dayofweek == 5) { $dayofweek = 4; }
	elseif ($dayofweek == 6) { $dayofweek = 5; }
	elseif ($dayofweek == 0) { $dayofweek = 6; }
	$daysofweek = (($dayofweek) * 86400 ) - 7200;
	$today = mktime(0, 0, 0, date("n", $input), date("j", $input), date("Y", $input));
	$monday = ( $today - $daysofweek );
	return($monday);
	
}

function WITCheckBankHoliday($time) {

	if (date("w",$time) == 0 OR date("w",$time) == 6 ) { return __("It's the weekend!", 'whos-in-today'); }
	else {

		global $wpdb;
		$tablename = $wpdb->prefix."whosintoday_bankholidays";
		$date_check = date("Y-m-d",$time);
		$entriesList = $wpdb->get_results($wpdb->prepare("SELECT `name` FROM `".$tablename."` WHERE `bankholiday` = %s LIMIT 1", $date_check));

		if(count($entriesList) > 0){

			foreach( $entriesList as $entry ) {
				return $entry->name;
			}

		}


	}

}

function WITCheckAllUsers() {
	
	$all_users = get_users(array( 'role__in' => array( 'author','editor','administrator' ) ));
	
	$output_id = array();
	$output_name = array();
	
	foreach ($all_users AS $user) {
		
			$output_id[] = $user->ID;
			$output_name[] = $user->first_name . "&nbsp;" . $user->last_name;
		
	}

	return array($output_id,$output_name);
	
}

function WITNoLocationFound($day) {

	$user_array = WITCheckAllUsers();
	$user_ids = $user_array[0];
	$user_name = $user_array[1];
	$user_role = isset($user_array[2]) ? $user_array[2] : array();
	$array_in_today = array();
	$output = '';

	global $wpdb;
	$tablename = $wpdb->prefix."whosintoday";
	$today = date("Y-m-d",time());

	$sql = $wpdb->prepare("SELECT `userid` FROM ".$tablename." WHERE `day` = %s", $today);

	$entriesList = $wpdb->get_results($sql);

	//var_dump($user_ids);

	foreach( $entriesList as $entry ) { $array_in_today[] = $entry->userid; }

	$count = 0;

	foreach ($user_ids AS $id) {

		if (!in_array($id,$array_in_today)) {

			if (!isset($title_needed)) { $output = $output . "<h3>" . esc_html__('No Location Found', 'whos-in-today') . "</h3>"; $title_needed = "no"; }

			if (1 == 1) {
				$output = $output . "<span class='WITnotconfirmed'>" . esc_html($user_name[$count]) . "</span> ";
			}

		}

		$count++;

	}
	
	return $output;
	
}

function WITPopUpScript() {
	
	$output = "
		
			<script>
	
			function ShowLocations(date) {
			  document.getElementById(date).style.display = 'block';
			}
			
			function HideLocations(date) {
			  document.getElementById(date).style.display = 'none';
			}
			
			</script>

		";
	
	return $output;
	
}

function whos_in_today_shortcode() {

	global $wpdb;

	$holidaycheck = WITCheckBankHoliday(time());
	$output = '';

	if (!$holidaycheck) {

		$tablename = $wpdb->prefix."whosintoday";
		$today = date("Y-m-d",time());
		$entriesList = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$tablename." WHERE `day` = %s order by location, userid", $today));

		if(count($entriesList) > 0){

			$current_location = NULL;
			$array_names = array();

			foreach( $entriesList as $entry ) {

				if ($current_location != $entry->location) { $output = $output . "<h3><strong>" . esc_html($entry->location) . "</strong></h3>"; }

				$user = get_userdata( $entry->userid );
				if ($user && (!$user->first_name OR !$user->last_name)) { $username = $user->user_login; } elseif ($user) { $username = $user->first_name."&nbsp;".$user->last_name; } else { $username = 'Unknown User'; }

				$output = $output . "<span class='WITconfirmed'>" . esc_html($username) . "</span> ";

				$current_location = $entry->location;

			}

			$output = $output . WITNoLocationFound(date("Y-m-d",time()));

		} else {

			$output = __('Nobody is in today!', 'whos-in-today');

		}

	} else { $output = $holidaycheck; }

	return "<div>" . $output . "</div>";

}

function WITPopUp($day) {
	
	global $wpdb;
	
	$tablename = $wpdb->prefix."whosintoday";
	$sql = $wpdb->prepare("SELECT * FROM `".$tablename."` WHERE `day` = %s AND `location` = 'Studio' order by userid", $day);
	$entriesList = $wpdb->get_results($sql);
	
	$output = "<div id='" . esc_attr($day) ."_studio' class='WIT-popup'>";

	$total = count($entriesList);

	if ($total > 0) {

	$output = $output . "<strong>Studio&mdash;" . esc_html(WITTidyDate($day)) . "<br />" . sprintf(_n('%d person in the studio', '%d people in the studio', $total, 'whos-in-today'), intval($total)) . "</strong><br />";
	
	$array_output = array();
	
		foreach( $entriesList as $entry ) {
			
			$user = get_userdata( $entry->userid );
			if ($user && (!$user->first_name OR !$user->last_name)) { $username = $user->user_login; } elseif ($user) { $username = $user->first_name."&nbsp;".$user->last_name; } else { $username = 'Unknown User'; }
			
			$array_output[] = $username;
			
		}
		
	asort($array_output);

	// Escape each username before imploding
	$escaped_output = array_map('esc_html', $array_output);
	$output = $output . implode(", ", $escaped_output);
	
	} else { $output = $output . sprintf(__("There's nobody in the studio on %s.", 'whos-in-today'), date("l",WITDropToTime($day))); }
	
	$output = $output . "</div>";
	
	return $output;
	
}

function whos_in_today_heatmap_shortcode() {
	
global $wpdb;

	$tablename = $wpdb->prefix."whosintoday";
	$start_date = date("Y-m-d",WITBeginWeek(time()));
	$end_date = date("Y-m-d",WITBeginWeek(time() + 1209600));
	$entriesList = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$tablename." WHERE `day` >= %s AND `day` < %s AND `location` = 'Studio' order by day, location, userid", $start_date, $end_date));
	
	$array_location = array();
	
	$total_users = count_users();
	
	foreach( $entriesList as $entry ) { $array_location[] = $entry->day; }
	
	$array_count_locations = array_count_values($array_location);
	
	$count = 1;
	
	$daycount = WITBeginWeek(time());
			
		$output = "	<div><table style='width: 100%;'>
								<th style=\"text-align: center;\">M</th>
								<th style=\"text-align: center;\">T</th>
								<th style=\"text-align: center;\">W</th>
								<th style=\"text-align: center;\">T</th>
								<th style=\"text-align: center;\">F</th>
								<tr>";
			
		while ( $count <= 14) {

			$current_date = date("Y-m-d",$daycount);
			$howmanyintoday = isset($array_count_locations[$current_date]) ? $array_count_locations[$current_date] : 0;

			if (date("Y-m-d",$daycount) == date("Y-m-d",time())) { $border = " WITheatmap-today"; } else { $border = ''; }

			if (date("w",$daycount) != 0 && date("w",$daycount) != 6) { $output = $output . "<td class='WITheatmap" . esc_attr($border) . "' onmouseover=\"ShowLocations('" . esc_js(date("Y-m-d",$daycount)) . "_studio')\" onmouseout=\"HideLocations('" . esc_js(date("Y-m-d",$daycount)) . "_studio')\">" . WITPopUp(date("Y-m-d",$daycount)) . esc_html(date("d",$daycount)) . "</td>"; }

			if ($count == 7) { $output = $output .  "</tr><tr>"; }

			$count++;
			$daycount = $daycount + 86400;

		}
		
		$output = $output .  "</tr></table></div>";
		  
 
 return WITPopUpScript() . $output;
 
}

function WITCheckThisUserArray($user_id) {
	
	global $wpdb;

	$tablename = $wpdb->prefix."whosintoday";
	$entriesList = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$tablename." WHERE `userid` = %d order by day, location", intval($user_id)));
	
	$output = array();
		
	foreach( $entriesList as $entry ) {
	
		$output[] = $entry->day . "|" . $entry->location . "|" . $user_id;
		
	}
	
	return $output;
	
}

function whos_in_today_grid_shortcode() {

	if(isset($_POST['wit_submit_table'])){
		// Verify nonce for security
		if (!isset($_POST['wit_submit_nonce']) || !wp_verify_nonce($_POST['wit_submit_nonce'], 'wit_submit_table')) {
			return '<div class="error">Security check failed</div>';
		}
		WITSubmit($_POST);
	}
	
	global $WITLocations;
	
	
	
	//$search_array = WITDaysArray();
	
	
	
	//if (get_current_user_id() == 1) { echo "<p><pre><code>"; var_dump($search_array); echo "</code></pre></p>"; }
	
	$user_info = wp_get_current_user();
	$current_user = $user_info->ID;
	$user_name = $user_info->first_name . " " . $user_info->last_name;
	
	$array_of_user_days = WITCheckThisUserArray($current_user);
	
	//echo "<p><code><pre>"; var_dump($array_of_user_days); echo  "</pre></code></p>";
	
	$count = 1;
	
	$daycount = WITBeginWeek(time());
	
	$array_of_bank_holidays = WITBankHolidayArray();

		$output = "<div>";

		if ($user_name) { $output = $output . "<h2>" . esc_html($user_name) . "</h2>"; }

		$output = $output . "<form action='' method='post'>";
		ob_start();
		wp_nonce_field('wit_submit_table', 'wit_submit_nonce');
		$output = $output . ob_get_clean();
		$output = $output . "<table class=\"wp-list-table widefat fixed striped table-view-list\"><tr>";
			
		while ( $count <= 14) {

			$current_date = date("Y-m-d",$daycount);
			$array_count_locations = isset($array_count_locations) ? $array_count_locations : array();
			$total_users = isset($total_users) ? $total_users : array('total_users' => 1);
			$howmanyintoday = isset($array_count_locations[$current_date]) ? count($array_count_locations[$current_date]) : 0;
			$total_users_count = isset($total_users['total_users']) ? $total_users['total_users'] : 1;

			if (date("w",$daycount) != 0 && date("w",$daycount) != 6) { $output = $output . "<td id=\"wit_" . esc_attr(date("Y-m-d",$daycount)) . "\" style='text-align: left; background: rgba(19,110,191," . esc_attr(round ( $howmanyintoday / $total_users_count , 1)) . "); vertical-align: top;'><p><strong>" . esc_html(date("D d M Y",$daycount)) . "</strong></p>";
			
			
			
			if (!in_array(date("Y-m-d",$daycount),$array_of_bank_holidays)) {
			
				foreach ($WITLocations AS $location) {
					
					$check_string = date("Y-m-d",$daycount) . "|" . $location . "|" . get_current_user_id();
					
					if (date("Y-m-d",$daycount) >=  date("Y-m-d",time())) {
					
						$output = $output . "
						<input type='radio' value='" . esc_attr($check_string) . "' name='" . esc_attr(date("Y-m-d",$daycount)) . "' ";
												
						if ( in_array($check_string,$array_of_user_days)) { $output = $output . " checked=\"checked\" "; }
						
						$output = $output . " class=\"WITselect\" />
						<label for='" . esc_attr($current_user) . "'>" . esc_html($location) . "</label><br />";
						
					} elseif ( in_array($check_string,$array_of_user_days)) { $output = $output . "<span style='padding: 3px 12px 3px 12px; background: #136ebf; height: 20px; border-radius: 10px; font-size: 0.9em; line-height: 30px; color: white;'>" . esc_html($location) . "</span> ";; }
					
				}
				
			} else { $output = $output . WITGetBankHoliday(date("Y-m-d",$daycount)); }
			
			$output = $output . "</td>"; }
			
			if ($count == 7) { $output = $output . "</tr><tr>"; }
			
			$count++;
			$daycount = $daycount + 86400;
			
		}
		
		$output = $output . "</tr></table><p><input type='Submit' name='wit_submit_table' class='button button-primary' value='Update My Whereabouts' /></form></p></div>";
		
		return $output;
	
	
}

function WITDaysArray($time) {
	
	global $wpdb;
	
	if (!$time) { $time = time(); }

	$tablename = $wpdb->prefix."whosintoday";

	$start_date = date("Y-m-d",WITBeginWeek(time()));
	$end_date = date("Y-m-d",WITBeginWeek(time() + 1209600));
	$sql = $wpdb->prepare("SELECT * FROM `".$tablename."` WHERE `day` >= %s AND `day` < %s", $start_date, $end_date);

	$entriesList = $wpdb->get_results($sql);
	
	//echo "<p><code>" . $sql . "</code></p>";
	

	$array_users = array();
	$array_day = array();
	$array_location = array();
	
	foreach( $entriesList as $entry ) {
			
		$array_day[] = $entry->day;
		$array_users[] = $entry->userid;
		$array_location[] = $entry->location;
		
		//echo "<p><code>" . $entry->day . ", " . $entry->userid . ", " . $entry->location . "</code></p>";
		
	}
	
	$output = array($array_day,$array_users,$array_location);

	return $output;
	
}

function WITCheckInArray($search_array,$day,$location,$userid) {
	
	$day_location = array_search($day,$search_array[0]);
	
	if ($day == $search_array[0][$day_location] && $userid == $search_array[1][$day_location] && $location == $search_array[2][$day_location]) { return $location; }//  else { echo "<p><code>" . $day . "|" . $location . "|" . $userid . "</code></p>"; }
	
}

function WITSelect($selected) {
	
	global $WITLocations;
	
	foreach ($WITLocations AS $location) {
		
		echo "<option value=\"" . $location . "\"";
		
		if ($location == $selected) { echo "selected=\"selected\""; }
		
		echo ">" . $location . "</option>";
		
	}
	
}

function WITSubmit($array) {

	foreach ($array AS $item) {

		// Sanitize input
		$item = sanitize_text_field($item);
		$string = explode ("|",$item);

		if (count($string) == 3) {
			$day = sanitize_text_field($string[0]);
			$location = sanitize_text_field($string[1]);
			$userid = intval($string[2]);

			// Validate date format (Y-m-d)
			if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $day)) {
				WITCheckExisting($day,$userid,$location);
			}
		}

	}

}

function WITCheckExisting($day,$userid,$location) {

global $wpdb;

$tablename = $wpdb->prefix."whosintoday";

 if($userid != '' && $day != ''){

		$query = $wpdb->prepare("SELECT `day` FROM ". $tablename . " WHERE `day` = %s AND `userid` = %d LIMIT 1", $day, intval($userid));

	//	echo "<p><code>" . $query . "</code></p>";

		$check_data = $wpdb->get_results($query);
					if(count($check_data) == 1){
					  $insert_sql = $wpdb->prepare("UPDATE `".$tablename."` SET `userid` = %d, `day` = %s, `location` = %s WHERE `day` = %s AND `userid` = %d AND `location` != %s LIMIT 1", intval($userid), $day, $location, $day, intval($userid), $location);

					} else {

					$insert_sql = $wpdb->prepare("INSERT INTO ".$tablename."(userid,day,location) values (%d,%s,%s)", intval($userid), $day, $location);

					}

				$wpdb->query($insert_sql);

			}

			//echo "<p><code>" . $insert_sql . "</code></p>";

}

function WITHowManyInToday($location) {
	
	global $wpdb;
	$tablename = $wpdb->prefix."whosintoday";
	$week_start = date("Y-m-d", WITBeginWeek(time()));
	$query = $wpdb->prepare("SELECT count(`userid`) AS `quantity`, `day` FROM ". $tablename . " WHERE `day` >= %s", $week_start);
	$entriesList = $wpdb->get_results($query);
	
	$date_array = array();
	$quantity_array = array();
	
	foreach( $entriesList as $entry ) {
		
		$date_array[]  = $entry->quantity;
		$quantity_array[] = $entry->day;
				
	}
	
	return array($date_array,$quantity_array);
	
}

function WITDropToTime($day) {

	// Convert Y-m-d date string to Unix timestamp
	$date_array = explode("-", $day);
	if (count($date_array) == 3) {
		return mktime(0, 0, 0, intval($date_array[1]), intval($date_array[2]), intval($date_array[0]));
	}
	return time();

}

function WITListBankHolidays() {

	// Check user capabilities
	if (!current_user_can('manage_options')) {
		wp_die(__('You do not have sufficient permissions to access this page.'));
	}

	echo "<div class=\"wrap\"><h1>" . esc_html__('Bank Holidays', 'whos-in-today') . "</h1>";
	global $wpdb;
	$tablename = $wpdb->prefix."whosintoday_bankholidays";
	$today = date("Y-m-d",time());

	// Get upcoming bank holidays
	$query_upcoming = $wpdb->prepare("SELECT * FROM ". $tablename . " WHERE `bankholiday` >= %s ORDER BY `bankholiday` ASC", $today);
	$upcomingEntries = $wpdb->get_results($query_upcoming);

	// Get the most recent past bank holiday
	$query_past = $wpdb->prepare("SELECT * FROM ". $tablename . " WHERE `bankholiday` < %s ORDER BY `bankholiday` DESC LIMIT 1", $today);
	$pastEntry = $wpdb->get_results($query_past);

	// Always show the table with add form
	echo "<div><table class=\"wp-list-table widefat fixed striped table-view-list\"><tr><th>Name</th><th>Date</th><th colspan=\"2\">Added By</th></tr>";

	// Add form - always visible
	echo "<tr><form method='post' action=''>";
	wp_nonce_field('wit_add_bank_holiday', 'wit_bank_holiday_nonce');
	echo "<td><input type='text' name='name' placeholder='" . esc_attr__('Bank Holiday Name', 'whos-in-today') . "' required='required' /></td><td colspan='2'><input type='date' name='bankholiday' value='" . esc_attr(date("Y-m-d",time())) . "' /></td><td><input type='submit' value='" . esc_attr__('Add', 'whos-in-today') . "' class='button button-primary' name='but_add_bh' /></td></form></tr>";

	// Display most recent past bank holiday if it exists
	if(count($pastEntry) > 0){
		echo "<tr class='wit-past-holiday' style='opacity: 0.6;'><td colspan='4'><strong>" . esc_html__('Most Recent Past Bank Holiday', 'whos-in-today') . "</strong></td></tr>";
		foreach( $pastEntry as $entry ) {
			$user_info = get_userdata( $entry->added_by );
			$display_name = ($user_info && $user_info->first_name && $user_info->last_name) ? $user_info->first_name . "&nbsp;" . $user_info->last_name : ($user_info ? $user_info->user_login : 'Unknown User');

			$delete_url = wp_nonce_url(admin_url('admin.php?page=bankholidays&delete_bh=' . $entry->id), 'delete_bank_holiday_' . $entry->id);

			echo "<tr style='opacity: 0.6;'><td>" . esc_html($entry->name) . "</td><td>" . esc_html(WITTidyDate($entry->bankholiday)) . "</td><td>" . esc_html($display_name) . "</td><td><a href='" . esc_url($delete_url) . "' class='button'>Delete</a></td></tr>";
		}
	}

	// Display upcoming entries if any
	if(count($upcomingEntries) > 0){
		echo "<tr><td colspan='4'><strong>" . esc_html__('Upcoming Bank Holidays', 'whos-in-today') . "</strong></td></tr>";
		foreach( $upcomingEntries as $entry ) {

			$user_info = get_userdata( $entry->added_by );
			$display_name = ($user_info && $user_info->first_name && $user_info->last_name) ? $user_info->first_name . "&nbsp;" . $user_info->last_name : ($user_info ? $user_info->user_login : 'Unknown User');

			$delete_url = wp_nonce_url(admin_url('admin.php?page=bankholidays&delete_bh=' . $entry->id), 'delete_bank_holiday_' . $entry->id);

			echo "<tr><td>" . esc_html($entry->name) . "</td><td>" . esc_html(WITTidyDate($entry->bankholiday)) . "</td><td>" . esc_html($display_name) . "</td><td><a href='" . esc_url($delete_url) . "' class='button'>Delete</a></td></tr>";

		}
	} else {
		echo "<tr><td colspan='4'>" . esc_html__('No upcoming bank holidays found. Add one using the form above.', 'whos-in-today') . "</td></tr>";
	}

	echo "</table></div>";

	echo "</div>";
	
}

function WITAddBankHoliday() {

	// Check user capabilities
	if (!current_user_can('manage_options')) {
		wp_die(__('You do not have sufficient permissions to access this page.'));
	}

global $wpdb;

$tablename = $wpdb->prefix."whosintoday_bankholidays";

		$bankholiday_date = sanitize_text_field($_POST['bankholiday']);
		$added_by = get_current_user_id(); // Use current user ID instead of POST data
		$name = sanitize_text_field($_POST['name']);

		// Validate date format (Y-m-d) and that it's a valid date
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $bankholiday_date)) {
			wp_die('Invalid date format');
		}

		// Verify it's an actual valid date
		$date_parts = explode('-', $bankholiday_date);
		if (!checkdate(intval($date_parts[1]), intval($date_parts[2]), intval($date_parts[0]))) {
			wp_die('Invalid date');
		}

		$query = $wpdb->prepare("SELECT `bankholiday` FROM `". $tablename . "` WHERE `bankholiday` = %s LIMIT 1", $bankholiday_date);
		$entriesList = $wpdb->get_results($query);

		if(count($entriesList) == 0){

			  $insert_sql = $wpdb->prepare("INSERT INTO `".$tablename."` (`id`,`added_by`,`name`,`bankholiday`) VALUES (NULL,%d,%s,%s)", $added_by, $name, $bankholiday_date);
			  $wpdb->query($insert_sql);

		}

}

function WITDeleteBankHoliday($id) {

	// Check user capabilities
	if (!current_user_can('manage_options')) {
		wp_die(__('You do not have sufficient permissions to access this page.'));
	}

	global $wpdb;
	$tablename = $wpdb->prefix."whosintoday_bankholidays";

	$wpdb->query($wpdb->prepare("DELETE FROM `".$tablename."` WHERE `id` = %d", $id));

	// Redirect back to bank holidays page
	wp_redirect(admin_url('admin.php?page=bankholidays'));
	exit;

}

function whos_in_today_next_bank_holiday() {
	
global $wpdb;

$tablename = $wpdb->prefix."whosintoday_bankholidays";
	$today = date("Y-m-d",time());

		$query = $wpdb->prepare("SELECT * FROM `". $tablename . "` WHERE `bankholiday` >= %s ORDER BY `bankholiday` ASC LIMIT 1", $today);
		$entriesList = $wpdb->get_results($query);
		
		if(count($entriesList) > 0){
			
			$output = "<div>";
			
			foreach( $entriesList as $entry ) {

			  $output = $output . "<div><p>" . sprintf(__('The next bank holiday is %s on %s.', 'whos-in-today'), esc_html($entry->name), esc_html(WITTidyDate($entry->bankholiday))) . "</p></div>";
			  
			}
			
			$output = $output . "</div>";
			  
		}
		
		return $output;
	
}

function WITBankHolidayArray() {
	
	global $wpdb;

	$tablename = $wpdb->prefix."whosintoday_bankholidays";
	$query = "SELECT * FROM `". $tablename . "` ORDER BY `bankholiday` ASC";
	$entriesList = $wpdb->get_results($query);
	$output = array();
	foreach( $entriesList as $entry ) { $output[] = $entry->bankholiday; }
	return $output;
	
}

function WITTidyDate($date) {
	
	$date_array = explode("-",$date);
	
	$time = mktime(12,0,0,$date_array[1],$date_array[2],$date_array[0]);
	
	return date("l, j F Y",$time);
	
}

function WITGetBankHoliday($bankholiday) {
	
	global $wpdb;

	$tablename = $wpdb->prefix."whosintoday_bankholidays";
	$query = $wpdb->prepare("SELECT * FROM `" . $tablename . "` WHERE `bankholiday` = %s LIMIT 1", $bankholiday);
	$entriesList = $wpdb->get_results($query);
	$output = '';
	foreach( $entriesList as $entry ) { $output = $entry->name; }
	return "<p>" . esc_html($output) . "</p>";
	
	
}

function WITCalendar() {

	$week_begin = WITBeginWeek ( mktime(12,0,0,intval(date("n",time())),1,intval(date("Y",time()))) );

	$counter = 1;

	$bank_holiday_array = WITBankHolidayArray();

	$location_array = WITDaysArray($week_begin);

	echo "<h2>" . esc_html__('Calendar', 'whos-in-today') . "</h2>";

	echo "<table class='wp-list-table widefat fixed table-view-list'><tr>";

	while ($counter <= 35) {

		$day_key = array_search(date("Y-m-d",$week_begin), isset($location_array[0]) ? $location_array[0] : array());

		echo "<td>";
		echo date("l, j F",$week_begin);
		if ($day_key !== false && $day_key > 0) { echo $day_key; }
		echo "</td>";

		if (in_array(date("Y-m-d",$week_begin), $bank_holiday_array)) { echo WITGetBankHoliday(date("Y-m-d",$week_begin)); }

		if ($counter == 7 OR $counter == 14 OR $counter == 21 OR $counter == 28) { echo "</tr><tr>"; }

		$counter++;

		$week_begin = $week_begin + 86400;

	}

	echo "</tr></table>";


	echo "<p>Beginning of the first week of the month is " . date("Y-m-d",$week_begin) . "</p>";


}
