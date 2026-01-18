# Inbox v2 - Video and Location Message Support

## Overview
Added support for displaying video, location, audio, and file messages in Inbox v2.

## Changes Made

### 1. Message Display Support (inbox-v2.php)
Added rendering for additional message types:

- **Video Messages**: Shows video player with controls
- **Location Messages**: Shows static map image with Google Maps link, displays address and coordinates
- **Audio Messages**: Shows audio player with controls
- **File Messages**: Shows download button with file name

### 2. Message Preview Icons
Updated `getMessagePreview()` and `getMessagePreviewLocal()` functions to show appropriate emoji icons:
- 🎥 Video
- 📍 Location
- 🎵 Audio
- 📄 File

### 3. Thai Language Encoding Fix
Created diagnostic and fix scripts for Thai text encoding issues in location messages:

- `install/check_table_encoding.php` - Checks database charset settings
- `install/fix_location_encoding.php` - Fixes encoding of existing location messages

**Note**: Database connection already properly configured with `utf8mb4` charset. The encoding issue only affects existing data that was saved before proper charset configuration.

### 4. JavaScript Function Scope Fix
Fixed "toggleHUD is not defined" and "togglePanel is not defined" errors:

**Problem**: Functions were defined in a `<script>` tag that appears later in the HTML (line 2985+), but HTML buttons calling these functions appear earlier (lines 1780-1783). When the page loads, the onclick handlers reference functions that haven't been defined yet.

**Solution**: Moved function definitions to the early script block (around line 2620) where global variables are set, ensuring functions are available before any HTML that references them.

Functions moved:
- `toggleHUD()` - Toggle HUD Dashboard visibility
- `togglePanel()` - Alias for toggleHUD()
- `generateGhostDraft()` - Stub function (full implementation loads later)

## Webhook Support
The webhook (`webhook.php`) already supports receiving these message types from LINE:
- Video messages
- Location messages (with address and coordinates)
- Audio messages
- File messages

## Testing

### To Test Video/Location Display:
1. Send a video or location from LINE app to the bot
2. Open Inbox v2 and select the conversation
3. Verify the message displays correctly

### To Fix Thai Encoding Issues:
1. Visit: `https://cny.re-ya.com/install/check_table_encoding.php`
2. Review charset settings
3. Visit: `https://cny.re-ya.com/install/fix_location_encoding.php`
4. Verify Thai text displays correctly in location messages

### To Verify JavaScript Fix:
1. Open Inbox v2 in browser
2. Open browser console (F12)
3. Click "HUD" button - should work without errors
4. Click "ข้อมูลลูกค้า" (customer info) button - should work without errors
5. No "toggleHUD is not defined" or "togglePanel is not defined" errors should appear

## Files Modified
- `inbox-v2.php` - Added message type display support and fixed JavaScript scope
- `webhook.php` - Already had support for receiving these message types
- `install/fix_location_encoding.php` - New encoding fix script
- `install/check_table_encoding.php` - New encoding diagnostic script

## Requirements Met
- ✅ Display video messages with player
- ✅ Display location messages with map and address
- ✅ Display audio messages with player
- ✅ Display file messages with download link
- ✅ Show appropriate icons in message preview
- ✅ Support Thai language (UTF-8/utf8mb4)
- ✅ Fix JavaScript function scope errors

## Deployment
Changes pushed to production server (cny.re-ya.com) via git push.

Commits:
- `feat(inbox-v2): Add video and location message support`
- `fix(inbox-v2): Fix toggleHUD and togglePanel undefined errors by defining functions early`
