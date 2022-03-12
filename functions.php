<?php

$WITLocations = array('Studio','Home','Holiday','Elsewhere','Unpaid Leave','Absent','Isolating');

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
	
	if (date("w",$time) == 0 OR date("w",$time) == 6 ) { return "It's the weekend!"; }
	else {
		
		global $wpdb;
		$tablename = $wpdb->prefix."whosintoday_bankholidays";	
		$entriesList = $wpdb->get_results("SELECT `name` FROM `".$tablename."` WHERE `bankholiday` = '" . date("Y-m-d",$time) . "' LIMIT 1");
		
		if(count($entriesList) > 0){
			
			foreach( $entriesList as $entry ) {
				return $entry->name;
			}
			
		}


	}
	
}

function WITCheckAllUsers() {
	
	$all_users = get_users();
	
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
	$array_in_today = array();
	
	global $wpdb;
	$tablename = $wpdb->prefix."whosintoday";
	
	$sql = "SELECT `userid` FROM ".$tablename." WHERE `day` = '" . date("Y-m-d",time()) . "'";
	
	$entriesList = $wpdb->get_results($sql);
	
	//var_dump($user_ids);
	
	foreach( $entriesList as $entry ) { $array_in_today[] = $entry->userid; }
	
	$count = 0;

	foreach ($user_ids AS $id) {
	
		if (!in_array($id,$array_in_today)) {
			
			if (!$title_needed) { $output = $output . "<h3>No Location Found</h3>"; $title_needed = "no"; }
			
			$output = $output . "<span class='WITnotconfirmed'>" . $user_name[$count] . "</span> ";
		
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

	if (!$holidaycheck) {

		$tablename = $wpdb->prefix."whosintoday";	
		$entriesList = $wpdb->get_results("SELECT * FROM ".$tablename." WHERE `day` = '" . date("Y-m-d",time()) . "' order by location, userid");

		if(count($entriesList) > 0){
				
			$current_location = NULL;
			$array_names = array();

			foreach( $entriesList as $entry ) {
				
				if ($current_location != $entry->location) { $output = $output . "<h3><strong>" . $entry->location . "</strong></h3>"; }
				
				$user = get_userdata( $entry->userid );
				if (!$user->first_name OR !$user->last_name) { $username = $user->user_login; } else { $username = $user->first_name."&nbsp;".$user->last_name; }
				
				$output = $output . "<span class='WITconfirmed'>" . $username . "</span> ";
				
				$current_location = $entry->location;

			}
			
			$output = $output . WITNoLocationFound(date("Y-m-d",time()));
			   
		} else {
			
			$output = "Nobody is in today!";
			   
		}
		
	} else { $output = $holidaycheck; }
	 
	return "<div>" . $output . "</div>";
 
}

function WITPopUp($day) {
	
	global $wpdb;
	
	$tablename = $wpdb->prefix."whosintoday";
	$sql = "SELECT * FROM `".$tablename."` WHERE `day` = '" . $day . "' AND `location` = 'Studio' order by userid";
	$entriesList = $wpdb->get_results($sql);
	
	
	
	$output = "<div id='" . $day ."_studio' class='WIT-popup'>";
	
	$total = count($entriesList);
	
	if ($total > 0) {
		
	$output = $output . "<p>Studio&mdash;" . WITTidyDate($day) . "<br />" . $total . " people in the studio</p>";
	
	$array_output = array();
	
		foreach( $entriesList as $entry ) {
			
			$user = get_userdata( $entry->userid );
			if (!$user->first_name OR !$user->last_name) { $username = $user->user_login; } else { $username = $user->first_name."&nbsp;".$user->last_name; }
			
			$array_output[] = $username;
			
		}
		
	asort($array_output);
		
	$output = $output . implode(", ",$array_output);
	
	} else { $output = $output . "There's nobody in the studio on " . date("l",WITDropToTime($day))	. "."; }
	
	$output = $output . "</div>";
	
	return $output;
	
}

function whos_in_today_heatmap_shortcode() {
	
global $wpdb;

	$tablename = $wpdb->prefix."whosintoday";	
	$entriesList = $wpdb->get_results("SELECT * FROM ".$tablename." WHERE `day` >= '" . date("Y-m-d",WITBeginWeek(time())) . "' AND `day` < '" . date("Y-m-d",WITBeginWeek(time() + 1209600)) . "' AND `location` = 'Studio' order by day, location, userid");
	
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
			
			$howmanyintoday = count($array_count_locations[date("Y-m-d",$daycount)]);
			
			if (date("Y-m-d",$daycount) == date("Y-m-d",time())) { $border = " WITheatmap-today"; } else { unset($border); }
			
			if (date("w",$daycount) != 0 && date("w",$daycount) != 6) { $output = $output . "<td class='WITheatmap" . $border . "' onmouseover=\"ShowLocations('" . date("Y-m-d",$daycount) . "_studio')\" onmouseout=\"HideLocations('" . date("Y-m-d",$daycount) . "_studio')\">" . WITPopUp(date("Y-m-d",$daycount)) . date("d",$daycount) . "</td>"; }
			
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
	$entriesList = $wpdb->get_results("SELECT * FROM ".$tablename." WHERE `userid` = " . intval($user_id) . " order by day, location");
	
	$output = array();
		
	foreach( $entriesList as $entry ) {
	
		$output[] = $entry->day . "|" . $entry->location . "|" . $user_id;
		
	}
	
	return $output;
	
}

function whos_in_today_grid_shortcode() {
	
	if(isset($_POST['wit_submit_table'])){ WITSubmit($_POST); }
	
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
		
		if ($user_name) { $output = $output . "<h2>" . $user_name . "</h2>"; }
		
		$output = $output . "<form action='' method='post'><table class=\"wp-list-table widefat fixed striped table-view-list\"><tr>";
			
		while ( $count <= 14) {
			
			$howmanyintoday = count($array_count_locations[date("Y-m-d",$daycount)]);
			
			if (date("w",$daycount) != 0 && date("w",$daycount) != 6) { $output = $output . "<td id=\"wit_" . date("Y-m-d",$daycount) . "\" style='text-align: left; background: rgba(19,110,191," . round ( $howmanyintoday / $total_users['total_users'] , 1) . "); vertical-align: top;'><p><strong>" . date("D d M Y",$daycount) . "</strong></p>";
			
			
			
			if (!in_array(date("Y-m-d",$daycount),$array_of_bank_holidays)) {
			
				foreach ($WITLocations AS $location) {
					
					$check_string = date("Y-m-d",$daycount) . "|" . $location . "|" . get_current_user_id();
					
					if (date("Y-m-d",$daycount) >=  date("Y-m-d",time())) {
					
						$output = $output . "
						<input type='radio' value='" . $check_string . "' name='" . date("Y-m-d",$daycount) . "' ";
												
						if ( in_array($check_string,$array_of_user_days)) { $output = $output . " checked=\"checked\" "; }
						
						$output = $output . " class=\"WITselect\" />
						<label for='" . $current_user . "'>" . $location . "</label><br />";
						
					} elseif ( in_array($check_string,$array_of_user_days)) { $output = $output . "<span style='padding: 3px 12px 3px 12px; background: #136ebf; height: 20px; border-radius: 10px; font-size: 0.9em; line-height: 30px; color: white;'>" . $location . "</span> ";; }
					
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
	
	$sql = "SELECT * FROM `".$tablename."` WHERE `day` >= '" . date("Y-m-d",WITBeginWeek(time())) . "' AND `day` < '" . date("Y-m-d",WITBeginWeek(time() + 1209600)) . "'";
	
	$tablename = $wpdb->prefix."whosintoday";
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
		
		$string = explode ("|",$item);
		$day = $string[0];
		$location = $string[1];
		$userid = $string[2];
		
		if (count($string) == 3) { WITCheckExisting($day,$userid,$location); }		
		
	}
	
}

function WITCheckExisting($day,$userid,$location) {
	
global $wpdb;

$tablename = $wpdb->prefix."whosintoday";
	
 if($userid != '' && $day != ''){
	 
		$query = "SELECT `day` FROM ". $tablename . " WHERE `day` = '" . $day . "' AND `userid` = " . intval($userid) . " LIMIT 1";
		
	//	echo "<p><code>" . $query . "</code></p>";

		$check_data = $wpdb->get_results($query);
					if(count($check_data) == 1){
					  $insert_sql = "UPDATE `".$tablename."` SET `userid` = " . intval($userid) . ", `day` = '" . $day . "', `location` = '" . $location . "' WHERE `day` = '" . $day . "' AND `userid` = " . intval($userid) . " AND `location` != '" . $location . "' LIMIT 1";
					  
					} else {
					
					$insert_sql = "INSERT INTO ".$tablename."(userid,day,location) values (". intval($userid) .",'".$day."','".$location."') ";
					
					}
					
				$wpdb->query($insert_sql);
				
			}
							
			//echo "<p><code>" . $insert_sql . "</code></p>";

}

function WITHowManyInToday($location) {
	
	global $wpdb;
	$tablename = $wpdb->prefix."whosintoday";
	$query = "SELECT count(`userid`) AS `quantity`, `day` FROM ". $tablename . " WHERE `day` >= '" . WITBeginWeek(time()) . "'";
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
	
	
	
}

function WITListBankHolidays() {
	
	echo "<div class=\"wrap\"><h1>Bank Holidays</h1>";
	global $wpdb;
	$tablename = $wpdb->prefix."whosintoday_bankholidays";
	$query = "SELECT * FROM ". $tablename . " WHERE `bankholiday` > '" . date("Y-m-d",time()) . "' ORDER BY `bankholiday` ASC";
	$entriesList = $wpdb->get_results($query);
	
	if(count($entriesList) > 0){
		
		echo "<div><table class=\"wp-list-table widefat fixed striped table-view-list\"><tr><th>Date</th><th>Name</th><th colspan=\"2\">Added By</th></tr>";
		
		echo "<tr><form method='post' action=''><td><input type='text' name='name' required='required' /><input type='hidden' name='added_by' value='" . get_current_user_id() . "' /></td><td colspan='2'><input type='date' name='bankholiday' value='" . date("Y-m-d",time()) . "' /></td><td><input type='submit' value='Add' class='button' name='but_add_bh' /></td></form></tr>";
		
		foreach( $entriesList as $entry ) {
			
			$user_info = get_userdata( $entry->added_by );

			echo "<tr><td>" . $entry->name . "</td><td>" . WITTidyDate($entry->bankholiday) . "</td><td>" . $user_info->first_name . "&nbsp;" . $user_info->last_name . "</td><td><button href=\"\" class='button'>Delete</button></td></tr>";
			
		}
		
		echo "</table></div>";
	
	} else { echo "<p>No entries found</p>"; }
	
	echo "</div>";
	
}

function WITAddBankHoliday() {
	
global $wpdb;

$tablename = $wpdb->prefix."whosintoday_bankholidays";
	
		$query = "SELECT `bankholiday` FROM `". $tablename . "` WHERE `bankholiday` = '" . $_POST['bankholiday'] . "' LIMIT 1";
		$entriesList = $wpdb->get_results($query);
		
		if(count($entriesList) == 0){

			  $insert_sql = "INSERT INTO `".$tablename."` (`id`,`added_by`,`name`,`bankholiday`) VALUES (NULL," . intval($_POST['added_by']) . ",'" . addslashes($_POST['name']) . "','" . $_POST['bankholiday'] . "')";
			  $sql = $wpdb->get_results($insert_sql);
			  
		}
	
}

function whos_in_today_next_bank_holiday() {
	
global $wpdb;

$tablename = $wpdb->prefix."whosintoday_bankholidays";
	
		$query = "SELECT * FROM `". $tablename . "` WHERE `bankholiday` >= '" . date("Y-m-d",time()) . "' ORDER BY `bankholiday` ASC LIMIT 1";
		$entriesList = $wpdb->get_results($query);
		
		if(count($entriesList) > 0){
			
			$output = "<div>";
			
			foreach( $entriesList as $entry ) {

			  $output = $output . "<div><p>The next bank holiday is " . $entry->name . " on " . WITTidyDate($entry->bankholiday) . ".</p></div>";
			  
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
	$query = "SELECT * FROM `" . $tablename . "` WHERE `bankholiday` = '" . $bankholiday . "' LIMIT 1";
	$entriesList = $wpdb->get_results($query);
	foreach( $entriesList as $entry ) { $output = $entry->name; }
	return "<p>" . $output . "</p>";
	
	
}

function WITCalendar() {
	
	$week_begin = WITBeginWeek ( mktime(12,0,0,intval(date("n",time())),1,intval(date("y",time()))) );
	
	$counter = 1;
	
	$bank_holiday_array = WITBankHolidayArray();
	
	$location_array = WITDaysArray($week_begin);
	
	echo "<h2>Calendar</h2>";
	
	echo "<table class='wp-list-table widefat fixed table-view-list'><tr>";
	
	while ($counter <= 35) {
		
		$day_key = array_search(date("Y-m-d",$week_begin),$location_array[0]);
		
		echo "<td>";
		echo date("l, j F",$week_begin);
		if ($day_key > 0) { echo $day_key; }
		echo "</td>";
		
		if (in_array(date("Y-m-d",$week_begin))) { echo WITGetBankHoliday(date("Y-m-d",$week_begin)); }
		
		if ($counter == 7 OR $counter == 14 OR $counter == 21 OR $counter == 28) { echo "</tr><tr>"; }
		
		$counter++;
		
		$week_begin = $week_begin + 86400;
		
	}
	
	echo "</tr></table>";

	
	echo "<p>Beginning of the first week of the month is " . date("Y-m-d",$week_begin) . "</p>";
	
	
}
