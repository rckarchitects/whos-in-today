<?php
/**
 * Calendar Feed Early Loader
 * This file must be loaded before WordPress plugins to bypass WPO365
 *
 * Place this in wp-content/mu-plugins/ (create the directory if it doesn't exist)
 * MU-plugins load before regular plugins, giving us a chance to intercept
 */

// Check if this is a calendar feed request BEFORE any plugins load
add_action('muplugins_loaded', function() {
    // Check for calendar feed URL patterns
    $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

    if (strpos($request_uri, '/calendar-feed') !== false ||
        (isset($_GET['wit_calendar_feed']) && $_GET['wit_calendar_feed'] == '1')) {

        // Verify token is present
        if (!isset($_GET['token'])) {
            return; // Let normal auth handle it
        }

        // Define constant to tell WPO365 to skip authentication
        if (!defined('WPO365_BYPASS_CALENDAR_FEED')) {
            define('WPO365_BYPASS_CALENDAR_FEED', true);
        }

        // Remove WPO365 authentication hooks before they can fire
        remove_all_filters('authenticate');
        remove_all_filters('determine_current_user');

        // Add filter to bypass WPO365 redirect
        add_filter('wpo365/openidconnect/should_redirect', '__return_false', 1);
        add_filter('wpo365/user/skip_authentication', '__return_true', 1);
        add_filter('wpo365/user/login_required', '__return_false', 1);

        // Also handle the actual request at parse_request
        add_action('parse_request', function($wp) {
            if (isset($wp->query_vars['wit_calendar_feed']) && $wp->query_vars['wit_calendar_feed'] == '1') {
                // Verify token
                $saved_token = get_option('wit_calendar_feed_token');
                $provided_token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';

                if (empty($saved_token) || $provided_token !== $saved_token) {
                    http_response_code(401);
                    header('Content-Type: text/plain; charset=utf-8');
                    echo 'Unauthorized';
                    exit;
                }

                // Load feed generation
                $plugin_dir = WP_PLUGIN_DIR . '/whos-in-today';
                if (file_exists($plugin_dir . '/functions.php')) {
                    require_once($plugin_dir . '/functions.php');
                }

                if (function_exists('WITGenerateCalendarFeed')) {
                    WITGenerateCalendarFeed();
                } else {
                    http_response_code(500);
                    header('Content-Type: text/plain; charset=utf-8');
                    echo 'Feed generation function not found';
                }
                exit;
            }
        }, 1);
    }
}, 1);
