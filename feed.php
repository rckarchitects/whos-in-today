<?php
/**
 * Direct Calendar Feed - Bypasses WPO365 by loading before plugins
 * This file is accessed via WordPress rewrite, not directly
 */

// Stop WPO365 from loading by defining early constant
define('WPO365_SKIP', true);

// Prevent any output buffering
while (@ob_end_clean());

// Find WordPress root
$wp_root = dirname(dirname(dirname(dirname(__FILE__))));
if (file_exists($wp_root . '/wp-load.php')) {
    // Load only WordPress basics - SHORTINIT skips plugins
    define('SHORTINIT', true);
    require_once($wp_root . '/wp-load.php');
} else {
    // Fallback: try relative path
    require_once('../../../wp-load.php');
}

// Load wpdb manually since SHORTINIT doesn't include it
require_once(ABSPATH . WPINC . '/wp-db.php');
require_once(ABSPATH . WPINC . '/option.php');
require_once(ABSPATH . WPINC . '/formatting.php');
require_once(ABSPATH . WPINC . '/user.php');
require_once(ABSPATH . WPINC . '/meta.php');
require_once(ABSPATH . WPINC . '/class-wpdb.php');

global $wpdb;
if (!isset($wpdb)) {
    $wpdb = new wpdb(DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);
}

// Verify token
$saved_token = $wpdb->get_var("SELECT option_value FROM {$wpdb->options} WHERE option_name = 'wit_calendar_feed_token' LIMIT 1");
$provided_token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';

if (empty($saved_token) || $provided_token !== $saved_token) {
    http_response_code(401);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Unauthorized';
    exit;
}

// Get weeks ahead setting
$weeks_ahead = $wpdb->get_var("SELECT option_value FROM {$wpdb->options} WHERE option_name = 'wit_weeks_ahead' LIMIT 1");
if (empty($weeks_ahead)) {
    $weeks_ahead = 2;
}

// Get data
$tablename = $wpdb->prefix . "whosintoday";
$start_date = date("Y-m-d", strtotime('-2 weeks'));
$end_date = date("Y-m-d", strtotime('+' . $weeks_ahead . ' weeks'));

$entries = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM " . $tablename . " WHERE `day` >= %s AND `day` <= %s ORDER BY day ASC, userid ASC",
    $start_date,
    $end_date
));

// Get users
$user_ids = array_unique(wp_list_pluck($entries, 'userid'));
$users_cache = array();

foreach ($user_ids as $uid) {
    $first_name = $wpdb->get_var($wpdb->prepare(
        "SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key = 'first_name' LIMIT 1",
        $uid
    ));
    $last_name = $wpdb->get_var($wpdb->prepare(
        "SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key = 'last_name' LIMIT 1",
        $uid
    ));

    if ($first_name && $last_name) {
        $users_cache[$uid] = $first_name . ' ' . $last_name;
    } elseif ($first_name) {
        $users_cache[$uid] = $first_name;
    } else {
        $user = $wpdb->get_row($wpdb->prepare(
            "SELECT user_login FROM {$wpdb->users} WHERE ID = %d LIMIT 1",
            $uid
        ));
        $users_cache[$uid] = $user ? $user->user_login : 'Unknown';
    }
}

// Build iCal
$ical = "BEGIN:VCALENDAR\r\n";
$ical .= "VERSION:2.0\r\n";
$ical .= "PRODID:-//Who's In Today//Location Calendar//EN\r\n";
$ical .= "X-WR-CALNAME:Team Locations\r\n";
$ical .= "CALSCALE:GREGORIAN\r\n";
$ical .= "METHOD:PUBLISH\r\n";

foreach ($entries as $entry) {
    $user_name = isset($users_cache[$entry->userid]) ? $users_cache[$entry->userid] : 'Unknown';
    $location = $entry->location;
    $date = $entry->day;

    // Escape special characters for iCal format
    $user_name = str_replace(array('\\', ',', ';', "\n"), array('\\\\', '\\,', '\\;', '\\n'), $user_name);
    $location = str_replace(array('\\', ',', ';', "\n"), array('\\\\', '\\,', '\\;', '\\n'), $location);

    $uid = md5($entry->id . '-' . $date . '-' . $entry->userid) . '@' . $_SERVER['HTTP_HOST'];
    $date_formatted = date('Ymd', strtotime($date));
    $now_formatted = gmdate('Ymd\THis\Z');

    $ical .= "BEGIN:VEVENT\r\n";
    $ical .= "UID:" . $uid . "\r\n";
    $ical .= "DTSTAMP:" . $now_formatted . "\r\n";
    $ical .= "DTSTART;VALUE=DATE:" . $date_formatted . "\r\n";
    $ical .= "DTEND;VALUE=DATE:" . date('Ymd', strtotime($date . ' +1 day')) . "\r\n";
    $ical .= "SUMMARY:" . $user_name . " - " . $location . "\r\n";
    $ical .= "DESCRIPTION:" . $user_name . " at " . $location . "\r\n";
    $ical .= "LOCATION:" . $location . "\r\n";
    $ical .= "STATUS:CONFIRMED\r\n";
    $ical .= "TRANSP:TRANSPARENT\r\n";
    $ical .= "END:VEVENT\r\n";
}

$ical .= "END:VCALENDAR\r\n";

// Send output
http_response_code(200);
header('Content-Type: text/calendar; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
echo $ical;
exit;
