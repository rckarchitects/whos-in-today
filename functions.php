<?php

// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

// Define global locations array - load from database
function WITGetLocations() {
    global $wpdb;
    $tablename = $wpdb->prefix."whosintoday_locations";

    // Check if table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$tablename'") != $tablename) {
        // Fallback to hardcoded array if table doesn't exist yet
        return array('Studio','Home','Holiday','Elsewhere','Unpaid Leave','Absent','Isolating');
    }

    $locations = $wpdb->get_results("SELECT location_name FROM " . $tablename . " ORDER BY display_order ASC, location_name ASC");
    $location_array = array();
    foreach ($locations as $location) {
        $location_array[] = $location->location_name;
    }

    // If no locations in database, return default
    if (empty($location_array)) {
        return array('Studio','Home','Holiday','Elsewhere','Unpaid Leave','Absent','Isolating');
    }

    return $location_array;
}

// Initialize global locations array
if (!isset($GLOBALS['WITLocations'])) {
    $GLOBALS['WITLocations'] = WITGetLocations();
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

	// Load locations dynamically from database
	$WITLocations = WITGetLocations();
	
	
	
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

	// Load locations dynamically from database
	$WITLocations = WITGetLocations();

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

function WITListLocations() {

	// Check user capabilities
	if (!current_user_can('manage_options')) {
		wp_die(__('You do not have sufficient permissions to access this page.'));
	}

	echo "<div class=\"wrap\"><h1>" . esc_html__('Manage Locations', 'whos-in-today') . "</h1>";
	echo "<p>" . esc_html__('Add, edit, and remove location types. Drag and drop to reorder locations.', 'whos-in-today') . "</p>";

	global $wpdb;
	$tablename = $wpdb->prefix."whosintoday_locations";

	// Check if we're editing a location
	$editing_id = isset($_GET['edit_location']) ? intval($_GET['edit_location']) : 0;
	$editing_location = null;

	if ($editing_id > 0) {
		$editing_location = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $tablename . " WHERE id = %d", $editing_id));
	}

	$locationsList = $wpdb->get_results("SELECT * FROM " . $tablename . " ORDER BY display_order ASC, location_name ASC");

	// Always show the table with add/edit form
	echo "<div><table class=\"wp-list-table widefat fixed striped table-view-list\"><thead><tr><th style='width: 40px;'></th><th>Location Name</th><th colspan=\"2\">Actions</th></tr></thead>";

	// Add/Edit form - always visible
	echo "<tbody>";
	if ($editing_location) {
		echo "<tr style='background-color: #fffbcc;'><td></td><form method='post' action=''>";
		wp_nonce_field('wit_edit_location_' . $editing_id, 'wit_edit_location_nonce');
		echo "<input type='hidden' name='location_id' value='" . esc_attr($editing_id) . "' />";
		echo "<td><input type='text' name='location_name' value='" . esc_attr($editing_location->location_name) . "' placeholder='" . esc_attr__('Location Name', 'whos-in-today') . "' required='required' style='width: 100%;' /></td>";
		echo "<td colspan='2'><input type='submit' value='" . esc_attr__('Update', 'whos-in-today') . "' class='button button-primary' name='but_edit_location' /> <a href='" . esc_url(admin_url('admin.php?page=wit_locations')) . "' class='button'>" . esc_html__('Cancel', 'whos-in-today') . "</a></td></form></tr>";
	} else {
		echo "<tr><td></td><form method='post' action=''>";
		wp_nonce_field('wit_add_location', 'wit_location_nonce');
		echo "<td><input type='text' name='location_name' placeholder='" . esc_attr__('New Location Name', 'whos-in-today') . "' required='required' style='width: 100%;' /></td>";
		echo "<td colspan='2'><input type='submit' value='" . esc_attr__('Add Location', 'whos-in-today') . "' class='button button-primary' name='but_add_location' /></td></form></tr>";
	}
	echo "</tbody>";

	// Display existing locations
	if(count($locationsList) > 0){
		echo "<tbody id='wit-sortable-locations'>";
		foreach( $locationsList as $location ) {

			$edit_url = admin_url('admin.php?page=wit_locations&edit_location=' . $location->id);
			$delete_url = wp_nonce_url(admin_url('admin.php?page=wit_locations&delete_location=' . $location->id), 'delete_location_' . $location->id);

			echo "<tr data-location-id='" . esc_attr($location->id) . "' style='cursor: move;'>";
			echo "<td style='text-align: center;'><span class='dashicons dashicons-menu' style='color: #999;'></span></td>";
			echo "<td><strong>" . esc_html($location->location_name) . "</strong></td>";
			echo "<td><a href='" . esc_url($edit_url) . "' class='button'>Edit</a></td>";
			echo "<td><a href='" . esc_url($delete_url) . "' class='button' onclick='return confirm(\"" . esc_js(__('Are you sure you want to delete this location? This action cannot be undone.', 'whos-in-today')) . "\");'>Delete</a></td></tr>";

		}
		echo "</tbody>";
	} else {
		echo "<tbody><tr><td colspan='4'>" . esc_html__('No locations found. Add one using the form above.', 'whos-in-today') . "</td></tr></tbody>";
	}

	echo "</table></div>";

	// Add JavaScript for sortable functionality
	?>
	<script type="text/javascript">
	jQuery(document).ready(function($) {
		$('#wit-sortable-locations').sortable({
			handle: '.dashicons-menu',
			opacity: 0.7,
			cursor: 'move',
			axis: 'y',
			update: function(event, ui) {
				var order = [];
				$('#wit-sortable-locations tr').each(function(index) {
					var locationId = $(this).data('location-id');
					if (locationId) {
						order.push({
							id: locationId,
							order: index
						});
					}
				});

				// Send AJAX request to update order
				$.post(ajaxurl, {
					action: 'wit_update_location_order',
					nonce: '<?php echo wp_create_nonce('wit_update_location_order'); ?>',
					order: order
				}, function(response) {
					if (response.success) {
						// Optional: show success message
						console.log('Location order updated');
					}
				});
			}
		});
		$('#wit-sortable-locations').disableSelection();
	});
	</script>
	<style>
	#wit-sortable-locations tr.ui-sortable-helper {
		background-color: #f0f0f1;
		box-shadow: 0 2px 4px rgba(0,0,0,0.1);
	}
	#wit-sortable-locations tr:hover {
		background-color: #f6f7f7;
	}
	</style>
	<?php

	echo "</div>";

}

function WITAddLocation() {

	// Check user capabilities
	if (!current_user_can('manage_options')) {
		wp_die(__('You do not have sufficient permissions to access this page.'));
	}

	global $wpdb;

	$tablename = $wpdb->prefix."whosintoday_locations";

	$location_name = sanitize_text_field($_POST['location_name']);
	$display_order = isset($_POST['display_order']) ? intval($_POST['display_order']) : 0;

	// Validate location name is not empty
	if (empty($location_name)) {
		wp_die('Location name cannot be empty');
	}

	// Check if location already exists
	$query = $wpdb->prepare("SELECT id FROM " . $tablename . " WHERE location_name = %s LIMIT 1", $location_name);
	$existing = $wpdb->get_results($query);

	if(count($existing) == 0){
		$insert_sql = $wpdb->prepare("INSERT INTO " . $tablename . " (location_name, display_order) VALUES (%s, %d)", $location_name, $display_order);
		$wpdb->query($insert_sql);
	}

	// Redirect back to locations page
	wp_redirect(admin_url('admin.php?page=wit_locations'));
	exit;

}

function WITEditLocation() {

	// Check user capabilities
	if (!current_user_can('manage_options')) {
		wp_die(__('You do not have sufficient permissions to access this page.'));
	}

	global $wpdb;

	$tablename = $wpdb->prefix."whosintoday_locations";

	$location_id = intval($_POST['location_id']);
	$location_name = sanitize_text_field($_POST['location_name']);
	$display_order = isset($_POST['display_order']) ? intval($_POST['display_order']) : 0;

	// Validate location name is not empty
	if (empty($location_name)) {
		wp_die('Location name cannot be empty');
	}

	// Update the location
	$update_sql = $wpdb->prepare("UPDATE " . $tablename . " SET location_name = %s, display_order = %d WHERE id = %d", $location_name, $display_order, $location_id);
	$wpdb->query($update_sql);

	// Redirect back to locations page
	wp_redirect(admin_url('admin.php?page=wit_locations'));
	exit;

}

function WITDeleteLocation($id) {

	// Check user capabilities
	if (!current_user_can('manage_options')) {
		wp_die(__('You do not have sufficient permissions to access this page.'));
	}

	global $wpdb;
	$tablename = $wpdb->prefix."whosintoday_locations";

	$wpdb->query($wpdb->prepare("DELETE FROM " . $tablename . " WHERE id = %d", $id));

	// Redirect back to locations page
	wp_redirect(admin_url('admin.php?page=wit_locations'));
	exit;

}

function WITAdminCalendar() {

	// Check user capabilities
	if (!current_user_can('manage_options')) {
		wp_die(__('You do not have sufficient permissions to access this page.'));
	}

	global $wpdb;
	$tablename = $wpdb->prefix."whosintoday";

	// Get current month/year or from GET params
	$current_month = isset($_GET['wit_month']) ? intval($_GET['wit_month']) : intval(date('n'));
	$current_year = isset($_GET['wit_year']) ? intval($_GET['wit_year']) : intval(date('Y'));

	// Calculate navigation dates
	$prev_month = $current_month - 1;
	$prev_year = $current_year;
	if ($prev_month < 1) {
		$prev_month = 12;
		$prev_year--;
	}

	$next_month = $current_month + 1;
	$next_year = $current_year;
	if ($next_month > 12) {
		$next_month = 1;
		$next_year++;
	}

	// Get first and last day of month
	$first_day = mktime(0, 0, 0, $current_month, 1, $current_year);
	$days_in_month = intval(date('t', $first_day));
	$last_day = mktime(23, 59, 59, $current_month, $days_in_month, $current_year);

	// Get day of week for first day (0=Sunday, 1=Monday, etc.)
	$first_day_of_week = intval(date('w', $first_day));
	// Adjust so Monday = 0
	$first_day_of_week = ($first_day_of_week == 0) ? 6 : $first_day_of_week - 1;

	// Fetch all entries for this month
	$start_date = date('Y-m-d', $first_day);
	$end_date = date('Y-m-d', $last_day);
	$entries = $wpdb->get_results($wpdb->prepare(
		"SELECT * FROM " . $tablename . " WHERE `day` >= %s AND `day` <= %s ORDER BY day ASC, userid ASC",
		$start_date,
		$end_date
	));

	// Organize entries by day
	$entries_by_day = array();
	foreach ($entries as $entry) {
		$day_key = $entry->day;
		if (!isset($entries_by_day[$day_key])) {
			$entries_by_day[$day_key] = array();
		}
		$entries_by_day[$day_key][] = $entry;
	}

	// Fetch bank holidays for this month
	$bh_tablename = $wpdb->prefix."whosintoday_bankholidays";
	$bank_holidays = $wpdb->get_results($wpdb->prepare(
		"SELECT * FROM " . $bh_tablename . " WHERE `bankholiday` >= %s AND `bankholiday` <= %s ORDER BY bankholiday ASC",
		$start_date,
		$end_date
	));

	// Organize bank holidays by day
	$bank_holidays_by_day = array();
	foreach ($bank_holidays as $bh) {
		$bank_holidays_by_day[$bh->bankholiday] = $bh->name;
	}

	// Pre-load all users
	$user_ids = array_unique(array_column($entries, 'userid'));
	$users_cache = array();
	if (!empty($user_ids)) {
		$users = get_users(array('include' => $user_ids));
		foreach ($users as $user) {
			$users_cache[$user->ID] = $user;
		}
	}

	// Navigation
	$prev_url = admin_url('admin.php?page=WhosInToday&wit_month=' . $prev_month . '&wit_year=' . $prev_year);
	$next_url = admin_url('admin.php?page=WhosInToday&wit_month=' . $next_month . '&wit_year=' . $next_year);
	$today_url = admin_url('admin.php?page=WhosInToday');

	echo '<div class="wit-calendar-nav" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">';
	echo '<a href="' . esc_url($prev_url) . '" class="button">&larr; ' . esc_html__('Previous Month', 'whos-in-today') . '</a>';
	echo '<h2 style="margin: 0;">' . esc_html(date('F Y', $first_day)) . '</h2>';
	echo '<div>';
	echo '<a href="' . esc_url($today_url) . '" class="button" style="margin-right: 10px;">' . esc_html__('Today', 'whos-in-today') . '</a>';
	echo '<a href="' . esc_url($next_url) . '" class="button">' . esc_html__('Next Month', 'whos-in-today') . ' &rarr;</a>';
	echo '</div>';
	echo '</div>';

	// Calendar grid
	echo '<table class="wit-calendar-table" style="width: 100%; border-collapse: collapse; table-layout: fixed;">';
	echo '<thead><tr>';
	$weekdays = array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun');
	foreach ($weekdays as $day) {
		echo '<th style="padding: 10px; text-align: center; border: 1px solid #ddd; background-color: #f9f9f9;">' . esc_html__($day, 'whos-in-today') . '</th>';
	}
	echo '</tr></thead><tbody>';

	// Fill in the calendar
	$day_counter = 1;
	$current_day_of_week = 0;

	echo '<tr>';

	// Empty cells before first day
	for ($i = 0; $i < $first_day_of_week; $i++) {
		echo '<td style="padding: 10px; border: 1px solid #ddd; background-color: #f5f5f5; height: 100px; vertical-align: top;"></td>';
		$current_day_of_week++;
	}

	// Days of the month
	while ($day_counter <= $days_in_month) {
		$current_date = date('Y-m-d', mktime(0, 0, 0, $current_month, $day_counter, $current_year));
		$is_today = ($current_date == date('Y-m-d'));
		$entry_count = isset($entries_by_day[$current_date]) ? count($entries_by_day[$current_date]) : 0;

		// Cell styling
		$cell_style = 'padding: 10px; border: 1px solid #ddd; vertical-align: top; height: 100px; position: relative;';
		if ($is_today) {
			$cell_style .= ' background-color: #fff3cd; border-color: #ffc107;';
		}

		echo '<td style="' . esc_attr($cell_style) . '" class="wit-calendar-cell" data-date="' . esc_attr($current_date) . '">';
		echo '<div style="font-weight: bold; margin-bottom: 5px;">' . esc_html($day_counter) . '</div>';

		// Display bank holiday if exists
		if (isset($bank_holidays_by_day[$current_date])) {
			echo '<div style="background-color: #d63638; color: white; padding: 3px 6px; border-radius: 4px; font-size: 10px; margin-bottom: 5px; font-weight: bold;">' . esc_html($bank_holidays_by_day[$current_date]) . '</div>';
		}

		if ($entry_count > 0) {
			echo '<div class="wit-entry-badge" style="background-color: #2271b1; color: white; padding: 3px 8px; border-radius: 10px; font-size: 11px; display: inline-block; cursor: pointer;" onclick="witToggleEntries(\'' . esc_js($current_date) . '\')">';
			echo esc_html(sprintf(_n('%d entry', '%d entries', $entry_count, 'whos-in-today'), $entry_count));
			echo '</div>';

			// Hidden entries container
			echo '<div id="wit-entries-' . esc_attr($current_date) . '" class="wit-entries-container" style="display: none; margin-top: 10px; padding: 10px; background: white; border: 1px solid #ccc; border-radius: 4px; position: absolute; z-index: 1000; left: 0; top: 100%; min-width: 300px; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">';
			echo '<div style="font-weight: bold; margin-bottom: 8px; border-bottom: 1px solid #ddd; padding-bottom: 5px;">' . esc_html(WITTidyDate($current_date)) . '</div>';

			foreach ($entries_by_day[$current_date] as $entry) {
				$user = isset($users_cache[$entry->userid]) ? $users_cache[$entry->userid] : false;
				if ($user && (!$user->first_name OR !$user->last_name)) {
					$username = $user->user_login;
				} elseif ($user) {
					$username = $user->first_name . " " . $user->last_name;
				} else {
					$username = 'Unknown User';
				}

				$delete_url = wp_nonce_url('?page=WhosInToday&wit_month=' . $current_month . '&wit_year=' . $current_year . '&delid=' . $entry->id, 'delete_wit_entry_' . $entry->id);

				echo '<div style="padding: 5px 0; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">';
				echo '<div><strong>' . esc_html($username) . '</strong><br><span style="color: #666; font-size: 12px;">' . esc_html($entry->location) . '</span></div>';
				echo '<a href="' . esc_url($delete_url) . '" class="button button-small" style="margin-left: 10px;" onclick="return confirm(\'' . esc_js(__('Are you sure you want to delete this entry?', 'whos-in-today')) . '\');">' . esc_html__('Delete', 'whos-in-today') . '</a>';
				echo '</div>';
			}

			echo '<div style="margin-top: 8px; text-align: right;"><a href="#" onclick="witToggleEntries(\'' . esc_js($current_date) . '\'); return false;" style="font-size: 12px;">' . esc_html__('Close', 'whos-in-today') . '</a></div>';
			echo '</div>';
		}

		echo '</td>';

		$day_counter++;
		$current_day_of_week++;

		// Start new row after Sunday
		if ($current_day_of_week == 7 && $day_counter <= $days_in_month) {
			echo '</tr><tr>';
			$current_day_of_week = 0;
		}
	}

	// Fill remaining cells
	while ($current_day_of_week < 7 && $current_day_of_week > 0) {
		echo '<td style="padding: 10px; border: 1px solid #ddd; background-color: #f5f5f5; height: 100px; vertical-align: top;"></td>';
		$current_day_of_week++;
	}

	echo '</tr></tbody></table>';

	// JavaScript for toggling entries
	?>
	<script type="text/javascript">
	function witToggleEntries(date) {
		var container = document.getElementById('wit-entries-' + date);
		if (container.style.display === 'none') {
			// Close all other open containers
			var allContainers = document.querySelectorAll('.wit-entries-container');
			allContainers.forEach(function(c) {
				c.style.display = 'none';
			});
			// Open this one
			container.style.display = 'block';
		} else {
			container.style.display = 'none';
		}
	}

	// Close entries when clicking outside
	document.addEventListener('click', function(event) {
		var isCalendarCell = event.target.closest('.wit-calendar-cell');
		var isEntriesContainer = event.target.closest('.wit-entries-container');
		var isBadge = event.target.closest('.wit-entry-badge');

		if (!isCalendarCell && !isEntriesContainer && !isBadge) {
			var allContainers = document.querySelectorAll('.wit-entries-container');
			allContainers.forEach(function(c) {
				c.style.display = 'none';
			});
		}
	});
	</script>
	<style>
	.wit-calendar-cell:hover {
		background-color: #f9f9f9;
	}
	.wit-entry-badge:hover {
		background-color: #135e96;
	}
	</style>
	<?php
}

function WITShowSettings() {

	// Check user capabilities
	if (!current_user_can('manage_options')) {
		wp_die(__('You do not have sufficient permissions to access this page.'));
	}

	// Get current setting value (default to 2 weeks)
	$weeks_ahead = get_option('wit_weeks_ahead', 2);

	echo '<div class="wrap">';
	echo '<h1>' . esc_html__('Who\'s In Today - Settings', 'whos-in-today') . '</h1>';

	// Show success message if just saved
	if (isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true') {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved successfully.', 'whos-in-today') . '</p></div>';
	}

	echo '<form method="post" action="">';
	wp_nonce_field('wit_save_settings', 'wit_settings_nonce');

	echo '<table class="form-table" role="presentation">';
	echo '<tbody>';

	// Weeks ahead setting
	echo '<tr>';
	echo '<th scope="row"><label for="wit_weeks_ahead">' . esc_html__('Weeks Ahead', 'whos-in-today') . '</label></th>';
	echo '<td>';
	echo '<input type="number" name="wit_weeks_ahead" id="wit_weeks_ahead" value="' . esc_attr($weeks_ahead) . '" min="1" max="52" class="small-text" />';
	echo '<p class="description">' . esc_html__('Number of weeks ahead that users can add their location entries. Default is 2 weeks.', 'whos-in-today') . '</p>';
	echo '</td>';
	echo '</tr>';

	echo '</tbody>';
	echo '</table>';

	echo '<p class="submit">';
	echo '<input type="submit" name="wit_save_settings" id="submit" class="button button-primary" value="' . esc_attr__('Save Settings', 'whos-in-today') . '" />';
	echo '</p>';

	echo '</form>';
	echo '</div>';

}

function WITSaveSettings() {

	// Check user capabilities
	if (!current_user_can('manage_options')) {
		wp_die(__('You do not have sufficient permissions to access this page.'));
	}

	// Get and validate weeks ahead value
	$weeks_ahead = isset($_POST['wit_weeks_ahead']) ? intval($_POST['wit_weeks_ahead']) : 2;

	// Validate range (1-52 weeks)
	if ($weeks_ahead < 1) {
		$weeks_ahead = 1;
	} elseif ($weeks_ahead > 52) {
		$weeks_ahead = 52;
	}

	// Save setting
	update_option('wit_weeks_ahead', $weeks_ahead);

	// Redirect back to settings page with success message
	wp_redirect(admin_url('admin.php?page=wit_settings&settings-updated=true'));
	exit;

}

function WITGetWeeksAhead() {
	// Helper function to get weeks ahead setting with default fallback
	return get_option('wit_weeks_ahead', 2);
}
