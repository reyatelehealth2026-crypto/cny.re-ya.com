# 🎁 Rewards Creation Guide

## Problem Summary

The LIFF rewards redemption page was showing **no rewards** because the `rewards` table is empty for `line_account_id=3`.

The system is working correctly - there are just no rewards to display!

## Solution: Create Sample Rewards

I've created **3 different methods** to create sample rewards. Choose the one that works best for you:

---

## Method 1: Web Interface (Easiest) ⭐

**Best for:** Quick setup without SSH access

1. Open in browser:
   ```
   https://cny.re-ya.com/install/create_rewards_web.php
   ```

2. Select options:
   - **LINE Account ID:** 3
   - **Action:** Create Sample Rewards

3. Click **Execute**

4. Done! The page will show you all created rewards.

---

## Method 2: SSH Command Line

**Best for:** Server administrators with SSH access

1. SSH into your server:
   ```bash
   ssh zrismpsz@z129720-ri35sm.ps09.zwhhosting.com -p 9922
   ```

2. Navigate to project directory:
   ```bash
   cd ~/public_html/cny.re-ya.com
   ```

3. Run the PHP script:
   ```bash
   php install/create_rewards_account3.php
   ```

4. The script will create 5 sample rewards and show you a summary.

---

## Method 3: Direct SQL (Advanced)

**Best for:** Database administrators

1. Open phpMyAdmin or MySQL client

2. Run the SQL file:
   ```
   install/create_sample_rewards_account3.sql
   ```

Or copy-paste this SQL:

```sql
INSERT INTO rewards (line_account_id, name, description, points_required, reward_type, reward_value, stock, max_per_user, is_active, created_at, updated_at)
VALUES
(3, 'ส่วนลด 50 บาท', 'รับส่วนลด 50 บาท สำหรับการซื้อครั้งถัดไป', 500, 'discount', 50, -1, 0, 1, NOW(), NOW()),
(3, 'ส่วนลด 100 บาท', 'รับส่วนลด 100 บาท สำหรับการซื้อครั้งถัดไป', 1000, 'discount', 100, -1, 0, 1, NOW(), NOW()),
(3, 'ส่วนลด 200 บาท', 'รับส่วนลด 200 บาท (จำกัด 5 สิทธิ์)', 2000, 'discount', 200, 5, 1, 1, NOW(), NOW()),
(3, 'จัดส่งฟรี', 'รับบริการจัดส่งฟรีสำหรับคำสั่งซื้อครั้งถัดไป', 300, 'free_shipping', 0, -1, 0, 1, NOW(), NOW()),
(3, 'ของขวัญพิเศษ', 'รับของขวัญพิเศษจากร้าน (จำนวนจำกัด)', 1500, 'gift', 0, 10, 1, 1, NOW(), NOW());
```

---

## Sample Rewards Created

The scripts will create these 5 rewards:

| Reward | Points | Type | Stock |
|--------|--------|------|-------|
| ส่วนลด 50 บาท | 500 | Discount | Unlimited |
| ส่วนลด 100 บาท | 1000 | Discount | Unlimited |
| ส่วนลด 200 บาท | 2000 | Discount | 5 (Limited) |
| จัดส่งฟรี | 300 | Free Shipping | Unlimited |
| ของขวัญพิเศษ | 1500 | Gift | 10 (Limited) |

---

## Testing After Creation

### 1. Debug Page
Open: https://cny.re-ya.com/liff/debug-rewards.html

This page will:
- Show all available rewards
- Let you test redemption
- Display all errors and logs

### 2. LIFF App
Open: https://liff.line.me/2008876929-yRgjH7fX

Navigate to: **Redeem** page (แลกของรางวัล)

You should now see all 5 rewards!

---

## Verify Rewards Exist

To check if rewards were created successfully:

```sql
SELECT id, name, points_required, stock, is_active 
FROM rewards 
WHERE line_account_id = 3;
```

Expected result: 5 rows

---

## Add Test Points

If you need points to test redemption:

1. Open debug page: https://cny.re-ya.com/liff/debug-rewards.html
2. Click **"Add 1000 Test Points"** button
3. Or use SQL:

```sql
UPDATE users 
SET total_points = 5000, available_points = 5000 
WHERE line_user_id = 'Ua1156d646cad2237e878457833bc07b3';
```

---

## Files Created

1. **install/create_rewards_web.php** - Web interface (recommended)
2. **install/create_rewards_account3.php** - CLI script
3. **install/create_sample_rewards_account3.sql** - SQL script

---

## What Was Fixed Previously

✅ Created `api/rewards.php` endpoint with proper LoyaltyPoints integration
✅ Fixed SQL validation issues in `LoyaltyPoints::updateRedemptionStatus()`
✅ Fixed `reference_id` type errors in `addPoints()` and `deductPoints()`
✅ Added error alerts in LIFF app
✅ Updated cache version to force browser reload

**The system is working correctly - it just needs rewards data!**

---

## Next Steps

1. **Create rewards** using Method 1 (web interface)
2. **Test in LIFF** at https://liff.line.me/2008876929-yRgjH7fX
3. **Add test points** if needed
4. **Try redemption** - should work perfectly now!

---

## Support

If you still see issues after creating rewards:

1. Check browser console for errors (F12)
2. Clear LINE app cache
3. Hard refresh the LIFF page (Ctrl+Shift+R)
4. Check the debug page for detailed logs

The rewards system is fully functional and ready to use! 🎉
