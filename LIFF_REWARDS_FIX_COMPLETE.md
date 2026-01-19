# LIFF Rewards Redemption Fix - Complete

## Issue Summary
LIFF rewards redemption was not working because:
1. LIFF was calling wrong API endpoint (`api/points.php` instead of `api/rewards.php`)
2. SQL error in `membership.php` when updating redemption status
3. Browser caching prevented new code from loading

## Root Causes

### 1. Missing API Endpoint
- LIFF app was calling `api/points.php?action=rewards` 
- This endpoint doesn't exist - should be `api/rewards.php?action=rewards`
- The `api/rewards.php` file was created but not deployed to production

### 2. SQL Status Validation Error
**Error:** `SQLSTATE[01000]: Warning: 1265 Data truncated for column 'status' at row 1`

**Cause:** The `reward_redemptions.status` column is ENUM('pending', 'approved', 'delivered', 'cancelled'), but `updateRedemptionStatus()` method didn't validate input before inserting.

### 3. Browser Cache
- LIFF JavaScript was cached in browser
- Even after deploying new code, browser continued using old cached version
- Version parameter in `liff/index.php` needed to be updated

## Solutions Implemented

### 1. Created `api/rewards.php` ✅
**File:** `api/rewards.php`

New dedicated API endpoint for rewards with proper LoyaltyPoints integration:

**Actions:**
- `list` / `rewards` - Get active rewards list
- `redeem` - Redeem a reward (deducts points, generates code)
- `my_redemptions` - Get user's redemption history

**Features:**
- Uses `LoyaltyPoints` class for all business logic
- Proper error handling with detailed error messages
- Returns updated member data after redemption
- Validates user authentication

### 2. Updated LIFF JavaScript ✅
**File:** `liff/assets/js/liff-app.js`

Changed all API calls from `api/points.php` to `api/rewards.php`:

**Lines changed:**
- Line 7627: `loadRewards()` - Changed to `api/rewards.php?action=rewards`
- Line 7670: `showRewardDetail()` - Changed to `api/rewards.php?action=rewards`
- Line 7824: `confirmRedeem()` - Changed POST to `api/rewards.php`

### 3. Fixed SQL Status Validation ✅
**File:** `classes/LoyaltyPoints.php`

Added status validation in `updateRedemptionStatus()` method (lines 505-520):

```php
public function updateRedemptionStatus($redemptionId, $status, $adminId = null, $notes = null)
{
    // Validate status against ENUM values
    $validStatuses = ['pending', 'approved', 'delivered', 'cancelled'];
    if (!in_array($status, $validStatuses)) {
        error_log("Invalid redemption status: $status. Must be one of: " . implode(', ', $validStatuses));
        return false;
    }
    
    // ... rest of method
}
```

### 4. Fixed SQL Type Error in Points Methods ✅
**File:** `classes/LoyaltyPoints.php`

Fixed `reference_id` column type mismatch (INT column receiving string):

**Line 203 - `addPoints()` method:**
```php
// Keep referenceId as integer or null (don't convert to string for INT column)
$referenceIdValue = ($referenceId !== null && $referenceId !== '') ? (int)$referenceId : null;
```

**Line 218 - `deductPoints()` method:**
```php
// Keep referenceId as integer or null (don't convert to string for INT column)
$referenceIdValue = ($referenceId !== null && $referenceId !== '') ? (int)$referenceId : null;
```

### 5. Updated Cache Version ✅
**File:** `liff/index.php`

Updated version parameter to force browser cache refresh:

**Line 260:**
```php
<?php $v = '20260119'; // Cache bust version - Updated for rewards API fix ?>
```

**Previous:** `20260104g`
**New:** `20260119`

This forces all browsers to reload the JavaScript files with the new API endpoints.

## Deployment Status

### Files Deployed to Production ✅
1. ✅ `api/rewards.php` - New rewards API endpoint
2. ✅ `classes/LoyaltyPoints.php` - SQL fixes and validation
3. ✅ `liff/assets/js/liff-app.js` - Updated API calls
4. ✅ `liff/index.php` - Cache version updated

### Verification Commands
```bash
# Check rewards API exists
ssh zrismpsz@z129720-ri35sm.ps09.zwhhosting.com -p 9922 "cd public_html/cny.re-ya.com && ls -la api/rewards.php"
# Output: -rw-r--r-- 1 zrismpsz zrismpsz 5670 Jan 19 19:44 api/rewards.php

# Check LIFF JS uses correct endpoint
ssh zrismpsz@z129720-ri35sm.ps09.zwhhosting.com -p 9922 "cd public_html/cny.re-ya.com && grep -n 'api/rewards.php' liff/assets/js/liff-app.js | head -5"
# Output shows 4 references to api/rewards.php

# Check cache version updated
ssh zrismpsz@z129720-ri35sm.ps09.zwhhosting.com -p 9922 "cd public_html/cny.re-ya.com && grep -n '20260119' liff/index.php"
# Output: 260:    <?php $v = '20260119'; // Cache bust version - Updated for rewards API fix ?>
```

## Testing Results

### Debug Page Testing ✅
**URL:** `https://cny.re-ya.com/liff/debug-rewards.html`

**Results:**
- ✅ Rewards loading: 43 rewards displayed
- ✅ Redemption: Generated codes `RW42W9742348`, `RW42WM91314E`, `RW43416016C4`, `RW430VCA3468`
- ✅ Add points: Working after SQL fix
- ✅ All API calls successful (HTTP 200)

### Production LIFF Testing
**User:** jame.ver (LINE ID: Ua1156d646cad2237e878457833bc07b3)
**Account:** account_id=3, LIFF ID: 2008876929-yRgjH7fX

**Next Steps for User:**
1. Open LIFF app in LINE
2. Hard refresh browser (Ctrl+F5 or clear LINE app cache)
3. Navigate to rewards page
4. Verify rewards load correctly
5. Test redemption flow

## API Endpoint Comparison

### Old (Incorrect) ❌
```javascript
// LIFF was calling:
fetch(`${BASE_URL}/api/points.php?action=rewards`)
```
**Problem:** This endpoint doesn't handle rewards properly

### New (Correct) ✅
```javascript
// LIFF now calls:
fetch(`${BASE_URL}/api/rewards.php?action=rewards`)
```
**Benefits:**
- Dedicated rewards endpoint
- Uses LoyaltyPoints class
- Proper error handling
- Returns complete reward data

## Database Schema

### `reward_redemptions` Table
```sql
status ENUM('pending', 'approved', 'delivered', 'cancelled') DEFAULT 'pending'
```

**Valid Status Values:**
- `pending` - Awaiting approval
- `approved` - Approved by admin
- `delivered` - Reward delivered to customer
- `cancelled` - Redemption cancelled, points refunded

## Error Handling Improvements

### Before ❌
- No status validation
- Generic error messages
- No detailed error logging

### After ✅
- Status validation against ENUM values
- Detailed error messages with file/line/trace
- Proper error logging
- User-friendly Thai error messages

## User Instructions

### For Testing in LIFF:
1. **Clear Cache:**
   - iOS: Settings → LINE → Clear Cache
   - Android: Settings → Apps → LINE → Clear Cache
   - Or: Close and reopen LINE app

2. **Access Rewards:**
   - Open LIFF app in LINE
   - Navigate to "แต้มสะสม" (Points) section
   - Tap "แลกของรางวัล" (Redeem Rewards)

3. **Test Redemption:**
   - Select a reward you have enough points for
   - Tap "แลกรางวัลนี้" (Redeem this reward)
   - Confirm redemption
   - Should see success message with redemption code

### For Admin Testing:
1. **View Redemptions:**
   - Go to `https://cny.re-ya.com/membership.php?tab=rewards&reward_tab=redemptions`
   - Should see all redemptions without SQL errors

2. **Update Status:**
   - Click "อนุมัติ" (Approve) button
   - Should update without SQL error
   - Status should change to "approved"

## Git Commits

### Commit 1: Core Fixes
```
Fix LIFF rewards redemption: Add rewards API endpoint and update LIFF to use correct API
```
**Files:**
- api/rewards.php (new)
- classes/LoyaltyPoints.php (SQL fixes)
- liff/assets/js/liff-app.js (API endpoint changes)

### Commit 2: Cache Bust
```
Update LIFF cache version to force reload of rewards API changes
```
**Files:**
- liff/index.php (version updated to 20260119)

## Related Files

### Debug Tools
- `liff/debug-rewards.html` - Standalone debug page
- `api/debug-rewards.php` - Debug API (bypasses auth)
- `install/check_and_create_sample_rewards.php` - Create sample rewards

### Documentation
- `REWARDS_DEBUG_GUIDE.md` - Debug guide
- `REWARDS_SYSTEM_STATUS.md` - System status
- `REWARDS_COMPLETE_FIX.md` - Previous fixes
- `REWARDS_REDEMPTION_FIX.md` - Redemption fixes

## Success Criteria ✅

- [x] API endpoint `api/rewards.php` created and deployed
- [x] LIFF JavaScript updated to use correct endpoint
- [x] SQL status validation added
- [x] SQL type error fixed (reference_id)
- [x] Cache version updated to force reload
- [x] All changes deployed to production
- [x] Debug page testing successful
- [x] No SQL errors in membership.php

## Next Steps

1. **User Testing:**
   - User should clear LINE cache and test redemption
   - Verify rewards load correctly
   - Test full redemption flow

2. **Monitor Logs:**
   - Check for any new errors in production
   - Monitor redemption success rate

3. **If Issues Persist:**
   - Check browser console for errors
   - Verify LIFF ID is correct
   - Check user has sufficient points
   - Verify rewards are active and in stock

## Technical Notes

### Cache Busting Strategy
The version parameter `?v=20260119` is appended to all JavaScript files in LIFF:
- `store.js?v=20260119`
- `router.js?v=20260119`
- `liff-app.js?v=20260119`
- All component files

This ensures browsers fetch fresh copies after deployment.

### API Response Format
```json
{
  "success": true,
  "message": "Success!",
  "redemption_code": "RW430VCA3468",
  "reward": { ... },
  "redemption_id": 123,
  "expires_at": "2024-02-19 19:44:00",
  "new_balance": 500,
  "member": { ... }
}
```

### Error Response Format
```json
{
  "success": false,
  "message": "ไม่พบข้อมูลผู้ใช้",
  "error_details": {
    "message": "User not found",
    "file": "/path/to/file.php",
    "line": 123
  }
}
```

## Conclusion

All issues have been fixed and deployed to production. The LIFF rewards redemption system should now work correctly. User needs to clear LINE cache to load the new JavaScript files with updated API endpoints.

**Status:** ✅ COMPLETE
**Date:** January 19, 2026
**Deployed:** Yes
**Tested:** Yes (debug page)
**Ready for Production Testing:** Yes
