<?php
/*
   Plugin Name: Who's In Today?
   Plugin URI: https://russellcurtis.co.uk
   description: Plugin for locating people
   Version: 1.0.0
   Author: Russell Curtis
   Author URI: https://russellcurtis.co.uk
*/

require_once('functions.php');

function whosintoday_table(){

  global $wpdb;
  $charset_collate = $wpdb->get_charset_collate();

  $tablename = $wpdb->prefix."whosintoday";

  $sql = "CREATE TABLE " . $tablename . " (
  id mediumint(11) NOT NULL AUTO_INCREMENT,
  userid int(6) NOT NULL,
  day date NOT NULL,
  location varchar(80) NOT NULL,
  PRIMARY KEY  (id)
  ) " . $charset_collate . ";";

  require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
  dbDelta( $sql );

}

function whosintoday_bankholidays_table(){

  global $wpdb;
  $charset_collate = $wpdb->get_charset_collate();

  $tablename = $wpdb->prefix."whosintoday_bankholidays";

  $sql = "CREATE TABLE " . $tablename . " (
  id mediumint(11) NOT NULL AUTO_INCREMENT,
  added_by int(6) NOT NULL,
  name varchar(80) NOT NULL,
  bankholiday date NOT NULL,
  PRIMARY KEY  (id)
  )" . $charset_collate . ";";
  
  require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
  dbDelta( $sql );

}

register_activation_hook( __FILE__, 'whosintoday_table' );
register_activation_hook( __FILE__, 'whosintoday_bankholidays_table' );

// Add menu
function whosintoday_menu() {

    add_menu_page("Who's In Today?", "Who's In Today?","manage_options", "WhosInToday", "displayList",plugins_url('/whosintoday/img/icon.png'));
	add_submenu_page("WhosInToday","Bank Holidays", "Bank Holidays","manage_options", "bankholidays", "bankHolidays");

}
add_action("admin_menu", "whosintoday_menu");

add_shortcode( 'who_is_in_today', 'whos_in_today_shortcode');
add_shortcode( 'who_is_in_today_heatmap', 'whos_in_today_heatmap_shortcode');
add_shortcode( 'whos_in_today_next_bank_holiday', 'whos_in_today_next_bank_holiday');
add_shortcode( 'who_is_in_today_grid', 'whos_in_today_grid_shortcode');

// Custom CSS for the WIT plugin

function WITLoadCustomCSS() {
    wp_register_style('whos-in-today-styles', plugins_url('styles.css',__FILE__ ));
    wp_enqueue_style('whos-in-today-styles');
}

add_action( 'wp_enqueue_scripts',WITLoadCustomCSS);

function displayList(){
  include "displaylist.php";
}

function addEntry(){
  include "addentry.php";
}

function bankHolidays(){
	WITListBankHolidays();
}

if ($_POST['but_add_bh']) { WITAddBankHoliday(); }













