# Calendar Feed Setup for WPO365 Sites

## The Problem

WPO365 (WordPress Office 365 integration) intercepts all unauthenticated requests and redirects them to Microsoft 365 login. This prevents calendar applications (Outlook, iOS Calendar, Google Calendar) from subscribing to the calendar feed, as they cannot authenticate.

## The Solution

This plugin uses a **Must-Use (MU) Plugin** to bypass WPO365 authentication for the calendar feed only, while keeping the rest of your site protected.

### What is an MU-Plugin?

Must-Use plugins are WordPress plugins that:
- Load before regular plugins (including WPO365)
- Cannot be deactivated from the admin panel
- Are stored in `wp-content/mu-plugins/` directory

By loading before WPO365, the MU-plugin can intercept calendar feed requests and prevent authentication redirects.

## Installation

### Automatic Installation (Recommended)

1. Go to **Who's In Today → Settings** in your WordPress admin
2. Scroll to the **Calendar Feed** section
3. Look for the "WPO365 Bypass Setup" row
4. Click **"Install WPO365 Bypass Now"** button
5. You should see a success message

### Manual Installation

If the automatic installation fails (due to file permissions):

1. Locate the source file:
   ```
   wp-content/plugins/whos-in-today/calendar-feed-loader.php
   ```

2. Create the mu-plugins directory if it doesn't exist:
   ```
   wp-content/mu-plugins/
   ```

3. Copy the file:
   ```
   wp-content/plugins/whos-in-today/calendar-feed-loader.php
   TO
   wp-content/mu-plugins/calendar-feed-loader.php
   ```

4. Via FTP, SSH, or file manager - just copy the file to the new location

## Verification

1. Go to **Who's In Today → Settings**
2. In the Calendar Feed section, you should see:
   - ✓ **MU-Plugin installed - WPO365 bypass is active**

3. Test the feed:
   - Copy the feed URL
   - Open an incognito/private browser window
   - Paste the URL
   - You should see iCal content (not a login page)

## How It Works

1. User requests: `yoursite.com/calendar-feed/?token=xxxxx`
2. MU-plugin loads before WPO365
3. MU-plugin detects the `/calendar-feed` URL pattern
4. MU-plugin tells WPO365 to skip authentication for this request
5. WordPress loads the feed normally
6. Feed validates the token and returns calendar data

## Security

- The feed URL contains a 32-character secret token
- Only requests with a valid token receive calendar data
- Invalid tokens return "401 Unauthorized"
- The token can be regenerated from Settings if compromised
- Regenerating the token requires re-subscribing in all calendar apps

## Troubleshooting

### Feed still redirects to login

1. **Verify MU-plugin is installed:**
   - Check `wp-content/mu-plugins/calendar-feed-loader.php` exists
   - File size should be around 2-3 KB

2. **Check file permissions:**
   - MU-plugin file should be readable by the web server
   - Try chmod 644 on the file

3. **Clear caches:**
   - WPO365 might cache authentication rules
   - Clear any WordPress caching plugins
   - Try in a fresh incognito window

4. **Check WordPress version:**
   - Ensure WordPress is up to date
   - Some older versions handle MU-plugins differently

### MU-plugin won't install automatically

This happens if WordPress doesn't have write permission to `wp-content/mu-plugins/`:

1. Use FTP or file manager to create the directory
2. Set permissions: chmod 755 on the directory
3. Manually copy the file (see Manual Installation above)

### Feed returns "Function not found"

This means the feed generator didn't load:

1. Check `functions.php` is present in the plugin directory
2. Deactivate and reactivate the plugin
3. Check error logs for PHP errors

## Support

If you continue to experience issues:

1. Check WordPress error logs
2. Verify WPO365 version is up to date
3. Test the feed URL in an incognito browser
4. Contact your hosting provider about file permissions

## Technical Details

**File:** `calendar-feed-loader.php`
**Location:** `wp-content/mu-plugins/calendar-feed-loader.php`
**Hook Priority:** `muplugins_loaded` priority 1
**WPO365 Filters Used:**
- `wpo365/openidconnect/should_redirect`
- `wpo365/user/skip_authentication`
- `wpo365/user/login_required`
