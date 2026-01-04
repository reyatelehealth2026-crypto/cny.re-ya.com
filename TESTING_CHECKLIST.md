# System Testing Checklist

**Version**: 2026-01-04-v1  
**Last Updated**: January 4, 2026  
**Purpose**: Comprehensive testing checklist for Telepharmacy & E-commerce Platform

---

## How to Use This Checklist

1. Check off items as you complete testing
2. Document any issues found in the "Notes" section of each category
3. Use the "Status" field to track: ✅ Pass | ❌ Fail | ⏭️ Skip | 🔄 Retest
4. Reference the "Related Files" to locate relevant code

---

## Testing Environment Setup

- [ ] **Environment Prepared**
  - Database: Development/Staging/Production (circle one)
  - Test Data: Loaded and verified
  - LINE Test Account: Configured
  - Admin Account: Available
  - Browser DevTools: Ready

---

## 1. Authentication & Authorization Testing

**Status**: ⬜ Not Started | 🔄 In Progress | ✅ Complete

### Test Cases

- [ ] **1.1: Admin Login - Valid Credentials**
  - **Prerequisites**: Valid admin account exists
  - **Steps**:
    1. Navigate to `/auth/login.php`
    2. Enter valid username and password
    3. Click "Login" button
  - **Expected**: Redirect to dashboard, session created
  - **Related Files**: `auth/login.php`, `classes/AdminAuth.php`

- [ ] **1.2: Admin Login - Invalid Credentials**
  - **Prerequisites**: None
  - **Steps**:
    1. Navigate to `/auth/login.php`
    2. Enter invalid credentials
    3. Click "Login" button
  - **Expected**: Error message displayed, no session created
  - **Related Files**: `auth/login.php`

- [ ] **1.3: LINE LIFF Authentication**
  - **Prerequisites**: LINE test account
  - **Steps**:
    1. Open any LIFF app URL
    2. Verify LINE login prompt
    3. Complete LINE authentication
  - **Expected**: LIFF initialized, user profile loaded
  - **Related Files**: `liff/index.php`, `assets/js/liff-init.js`


- [ ] **1.4: Unauthorized Access Prevention**
  - **Prerequisites**: Not logged in
  - **Steps**:
    1. Navigate directly to `/dashboard.php`
    2. Observe behavior
  - **Expected**: Redirect to login page
  - **Related Files**: `includes/auth_check.php`

- [ ] **1.5: Pharmacist Role Access**
  - **Prerequisites**: Pharmacist account
  - **Steps**:
    1. Login as pharmacist
    2. Attempt to access admin-only pages
    3. Verify pharmacist dashboard access
  - **Expected**: Admin pages blocked, pharmacist features accessible
  - **Related Files**: `pharmacist-dashboard.php`, `classes/AdminAuth.php`

**Notes**:
```
[Document any issues or observations here]
```

---

## 2. LINE Integration Testing

**Status**: ⬜ Not Started | 🔄 In Progress | ✅ Complete

### Test Cases

- [ ] **2.1: Webhook Message Processing**
  - **Prerequisites**: Webhook URL configured in LINE Developers
  - **Steps**:
    1. Send a text message to LINE bot
    2. Check webhook.php logs
    3. Verify response received
  - **Expected**: Message processed, appropriate response sent
  - **Related Files**: `webhook.php`, `classes/LineAPI.php`

- [ ] **2.2: Broadcast Message Delivery**
  - **Prerequisites**: Admin access, test users
  - **Steps**:
    1. Navigate to `/broadcast.php`
    2. Create broadcast message
    3. Select target audience
    4. Send broadcast
  - **Expected**: Message delivered to all targeted users
  - **Related Files**: `broadcast.php`, `api/broadcast.php`

- [ ] **2.3: Rich Menu Interaction**
  - **Prerequisites**: Rich Menu configured
  - **Steps**:
    1. Open LINE chat with bot
    2. Tap Rich Menu buttons
    3. Verify actions executed
  - **Expected**: Correct actions triggered for each button
  - **Related Files**: `rich-menu.php`, `classes/DynamicRichMenu.php`

- [ ] **2.4: LIFF App Initialization**
  - **Prerequisites**: LIFF app registered
  - **Steps**:
    1. Open LIFF URL in LINE
    2. Check browser console
    3. Verify liff.init() success
  - **Expected**: LIFF SDK initialized, no errors
  - **Related Files**: `liff/index.php`, `assets/js/liff-init.js`

- [ ] **2.5: Flex Message Rendering**
  - **Prerequisites**: Flex template created
  - **Steps**:
    1. Send Flex Message via admin panel
    2. Check LINE chat display
    3. Test interactive elements
  - **Expected**: Message renders correctly, buttons work
  - **Related Files**: `classes/FlexTemplates.php`, `flex-builder.php`

**Notes**:
```
[Document any issues or observations here]
```

---

## 3. E-commerce & Shop Testing

**Status**: ⬜ Not Started | 🔄 In Progress | ✅ Complete

### Test Cases

- [ ] **3.1: Product Browsing**
  - **Prerequisites**: Products exist in database
  - **Steps**:
    1. Navigate to `/liff-shop.php`
    2. Browse product categories
    3. Check product images and prices
  - **Expected**: All products display correctly
  - **Related Files**: `liff-shop.php`, `api/shop-products.php`

- [ ] **3.2: Add to Cart**
  - **Prerequisites**: User logged in via LIFF
  - **Steps**:
    1. Select a product
    2. Click "Add to Cart"
    3. Verify cart count updates
  - **Expected**: Cart count increases, item stored
  - **Related Files**: `liff-shop.php`, `api/checkout.php`

- [ ] **3.3: Cart Management**
  - **Prerequisites**: Items in cart
  - **Steps**:
    1. View cart
    2. Update quantities
    3. Remove items
  - **Expected**: Cart updates correctly
  - **Related Files**: `liff-checkout.php`, `api/checkout.php`

- [ ] **3.4: Checkout Process**
  - **Prerequisites**: Items in cart
  - **Steps**:
    1. Proceed to checkout
    2. Enter shipping details
    3. Select payment method
    4. Confirm order
  - **Expected**: Order created, confirmation sent
  - **Related Files**: `liff-checkout.php`, `api/checkout.php`

- [ ] **3.5: Order Confirmation**
  - **Prerequisites**: Order completed
  - **Steps**:
    1. Check LINE messages
    2. View order in "My Orders"
    3. Verify order details
  - **Expected**: Confirmation message received, order visible
  - **Related Files**: `liff-my-orders.php`, `api/orders.php`

- [ ] **3.6: Product Administration - Create**
  - **Prerequisites**: Admin access
  - **Steps**:
    1. Navigate to `/shop/products.php`
    2. Click "Add Product"
    3. Fill in product details
    4. Save product
  - **Expected**: Product created and visible in shop
  - **Related Files**: `shop/products.php`, `shop/product-detail.php`

- [ ] **3.7: Product Administration - Update**
  - **Prerequisites**: Product exists
  - **Steps**:
    1. Edit existing product
    2. Modify details
    3. Save changes
  - **Expected**: Changes reflected immediately
  - **Related Files**: `shop/product-detail.php`

- [ ] **3.8: Product Administration - Delete**
  - **Prerequisites**: Product exists
  - **Steps**:
    1. Select product
    2. Click delete
    3. Confirm deletion
  - **Expected**: Product removed from shop
  - **Related Files**: `shop/products.php`

- [ ] **3.9: CNY Product Sync**
  - **Prerequisites**: CNY API configured
  - **Steps**:
    1. Navigate to `/admin/sync-cny.php`
    2. Trigger sync
    3. Monitor progress
  - **Expected**: Products synced from CNY API
  - **Related Files**: `admin/sync-cny.php`, `cron/sync_cny_products.php`

- [ ] **3.10: Promotion Application**
  - **Prerequisites**: Active promotion
  - **Steps**:
    1. Add eligible product to cart
    2. Proceed to checkout
    3. Verify discount applied
  - **Expected**: Correct discount calculated
  - **Related Files**: `shop/promotions.php`, `api/checkout.php`

**Notes**:
```
[Document any issues or observations here]
```

---

## 4. Inventory & WMS Testing

**Status**: ⬜ Not Started | 🔄 In Progress | ✅ Complete

### Test Cases

- [ ] **4.1: Stock Level Tracking**
  - **Prerequisites**: Products with stock
  - **Steps**:
    1. Navigate to `/inventory/index.php`
    2. View current stock levels
    3. Verify accuracy
  - **Expected**: Stock levels match database
  - **Related Files**: `inventory/index.php`, `classes/InventoryService.php`

- [ ] **4.2: Purchase Order Creation**
  - **Prerequisites**: Supplier exists
  - **Steps**:
    1. Navigate to `/inventory/purchase-orders.php`
    2. Create new PO
    3. Add items and quantities
    4. Save PO
  - **Expected**: PO created with pending status
  - **Related Files**: `inventory/purchase-orders.php`, `classes/PurchaseOrderService.php`

- [ ] **4.3: Goods Receiving**
  - **Prerequisites**: Approved PO exists
  - **Steps**:
    1. Navigate to `/inventory/goods-receive.php`
    2. Select PO
    3. Confirm received quantities
    4. Complete receiving
  - **Expected**: Stock increased, PO status updated
  - **Related Files**: `inventory/goods-receive.php`, `classes/InventoryService.php`

- [ ] **4.4: WMS - Pick Task Creation**
  - **Prerequisites**: Order placed
  - **Steps**:
    1. Navigate to WMS dashboard
    2. View pending pick tasks
    3. Verify order details
  - **Expected**: Pick task created for order
  - **Related Files**: `includes/inventory/wms-dashboard.php`, `classes/WMSService.php`

- [ ] **4.5: WMS - Picking Process**
  - **Prerequisites**: Pick task exists
  - **Steps**:
    1. Start pick task
    2. Scan/select items
    3. Confirm quantities
    4. Complete picking
  - **Expected**: Order moves to packing status
  - **Related Files**: `includes/inventory/wms-pick.php`, `api/wms.php`

- [ ] **4.6: WMS - Packing Process**
  - **Prerequisites**: Order picked
  - **Steps**:
    1. View packing queue
    2. Select order
    3. Pack items
    4. Generate packing slip
  - **Expected**: Order ready for shipping
  - **Related Files**: `includes/inventory/wms-pack.php`, `classes/WMSPrintService.php`

- [ ] **4.7: WMS - Shipping Process**
  - **Prerequisites**: Order packed
  - **Steps**:
    1. View shipping queue
    2. Generate shipping label
    3. Mark as shipped
    4. Enter tracking number
  - **Expected**: Order status updated, customer notified
  - **Related Files**: `includes/inventory/wms-ship.php`, `api/wms.php`

- [ ] **4.8: Low Stock Alerts**
  - **Prerequisites**: Product below minimum level
  - **Steps**:
    1. Navigate to `/inventory/low-stock.php`
    2. View low stock items
    3. Verify alert accuracy
  - **Expected**: All low stock items listed
  - **Related Files**: `inventory/low-stock.php`, `cron/restock_notification.php`

- [ ] **4.9: Stock Adjustment**
  - **Prerequisites**: Admin access
  - **Steps**:
    1. Navigate to `/inventory/stock-adjustment.php`
    2. Select product
    3. Adjust quantity with reason
    4. Save adjustment
  - **Expected**: Stock updated, movement logged
  - **Related Files**: `inventory/stock-adjustment.php`, `classes/InventoryService.php`

- [ ] **4.10: Stock Movement History**
  - **Prerequisites**: Stock movements exist
  - **Steps**:
    1. Navigate to `/inventory/stock-movements.php`
    2. Filter by product/date
    3. Review movement records
  - **Expected**: All movements displayed accurately
  - **Related Files**: `inventory/stock-movements.php`

**Notes**:
```
[Document any issues or observations here]
```

---

## 5. Accounting System Testing

**Status**: ⬜ Not Started | 🔄 In Progress | ✅ Complete

### Test Cases

- [ ] **5.1: Receipt Voucher Creation (AR)**
  - **Prerequisites**: Customer account exists
  - **Steps**:
    1. Navigate to `/accounting.php` → AR tab
    2. Create new receipt voucher
    3. Enter amount and details
    4. Save voucher
  - **Expected**: AR record created, balance updated
  - **Related Files**: `includes/accounting/ar.php`, `classes/ReceiptVoucherService.php`

- [ ] **5.2: Payment Voucher Creation (AP)**
  - **Prerequisites**: Supplier account exists
  - **Steps**:
    1. Navigate to `/accounting.php` → AP tab
    2. Create new payment voucher
    3. Enter amount and details
    4. Save voucher
  - **Expected**: AP record created, balance updated
  - **Related Files**: `includes/accounting/ap.php`, `classes/PaymentVoucherService.php`

- [ ] **5.3: Expense Recording**
  - **Prerequisites**: Expense categories configured
  - **Steps**:
    1. Navigate to `/accounting.php` → Expenses tab
    2. Add new expense
    3. Select category
    4. Enter amount and details
    5. Save expense
  - **Expected**: Expense recorded and categorized
  - **Related Files**: `includes/accounting/expenses.php`, `classes/ExpenseService.php`

- [ ] **5.4: Aging Report Generation**
  - **Prerequisites**: AR/AP records exist
  - **Steps**:
    1. View AR or AP aging report
    2. Verify age bracket calculations
    3. Check totals
  - **Expected**: Accurate aging analysis displayed
  - **Related Files**: `includes/accounting/ar.php`, `classes/AgingHelper.php`

- [ ] **5.5: Accounting Dashboard**
  - **Prerequisites**: Financial data exists
  - **Steps**:
    1. Navigate to `/accounting.php`
    2. View dashboard summaries
    3. Verify metrics
  - **Expected**: Current financial overview displayed
  - **Related Files**: `includes/accounting/dashboard.php`, `classes/AccountingDashboardService.php`

**Notes**:
```
[Document any issues or observations here]
```

---

## 6. Pharmacy & Consultation Testing

**Status**: ⬜ Not Started | 🔄 In Progress | ✅ Complete

### Test Cases

- [ ] **6.1: Consultation Session Creation**
  - **Prerequisites**: User logged in LIFF
  - **Steps**:
    1. Navigate to `/liff-pharmacy-consult.php`
    2. Request consultation
    3. Fill in symptoms/questions
    4. Submit request
  - **Expected**: Session created, pharmacist notified
  - **Related Files**: `liff-pharmacy-consult.php`, `api/pharmacist.php`

- [ ] **6.2: Pharmacist Prescription Review**
  - **Prerequisites**: Consultation session exists
  - **Steps**:
    1. Login as pharmacist
    2. View pending consultations
    3. Review patient information
    4. Access medication history
  - **Expected**: Complete patient data displayed
  - **Related Files**: `pharmacist-dashboard.php`, `includes/pharmacy/dashboard.php`

- [ ] **6.3: Drug Interaction Check**
  - **Prerequisites**: Medications selected
  - **Steps**:
    1. Navigate to `/drug-interactions.php`
    2. Enter multiple medications
    3. Run interaction check
  - **Expected**: Warnings displayed if interactions found
  - **Related Files**: `drug-interactions.php`, `api/drug-interactions.php`

- [ ] **6.4: Medication Dispensing**
  - **Prerequisites**: Approved prescription
  - **Steps**:
    1. Navigate to `/dispense-drugs.php`
    2. Select prescription
    3. Confirm medications
    4. Record dispensing
  - **Expected**: Dispensing logged, inventory updated
  - **Related Files**: `dispense-drugs.php`, `includes/pharmacy/dispense.php`

- [ ] **6.5: Video Call Initiation**
  - **Prerequisites**: Appointment scheduled
  - **Steps**:
    1. Navigate to video call page
    2. Join call at scheduled time
    3. Test audio/video
    4. End call
  - **Expected**: WebRTC connection established
  - **Related Files**: `liff-video-call.php`, `api/video-call.php`

**Notes**:
```
[Document any issues or observations here]
```

---


## 7. AI & Chatbot Testing

**Status**: ⬜ Not Started | 🔄 In Progress | ✅ Complete

### Test Cases

- [ ] **7.1: AI Chatbot Response**
  - **Prerequisites**: AI configured
  - **Steps**:
    1. Send message to chatbot
    2. Verify response received
    3. Check response relevance
  - **Expected**: Appropriate AI response generated
  - **Related Files**: `ai-chatbot.php`, `api/ai-chat.php`, `classes/GeminiChat.php`

- [ ] **7.2: AI Settings Configuration**
  - **Prerequisites**: Admin access
  - **Steps**:
    1. Navigate to `/ai-settings.php`
    2. Modify AI parameters
    3. Save settings
    4. Test chatbot behavior
  - **Expected**: Settings applied to chatbot
  - **Related Files**: `ai-settings.php`, `ai-pharmacy-settings.php`

- [ ] **7.3: Symptom Assessment**
  - **Prerequisites**: User logged in LIFF
  - **Steps**:
    1. Navigate to `/liff-symptom-assessment.php`
    2. Answer symptom questions
    3. Complete assessment
  - **Expected**: Recommendations provided
  - **Related Files**: `liff-symptom-assessment.php`, `api/symptom-assessment.php`

- [ ] **7.4: Onboarding Assistant**
  - **Prerequisites**: New user account
  - **Steps**:
    1. Trigger onboarding flow
    2. Follow assistant prompts
    3. Complete setup
  - **Expected**: User guided through setup
  - **Related Files**: `onboarding-assistant.php`, `api/onboarding-assistant.php`

- [ ] **7.5: Pharmacy AI Features**
  - **Prerequisites**: Pharmacy AI enabled
  - **Steps**:
    1. Ask medication questions
    2. Request drug information
    3. Verify accuracy
  - **Expected**: Accurate pharmacy information provided
  - **Related Files**: `api/pharmacy-ai.php`, `classes/GeminiAI.php`

**Notes**:
```
[Document any issues or observations here]
```

---

## 8. Loyalty & Points System Testing

**Status**: ⬜ Not Started | 🔄 In Progress | ✅ Complete

### Test Cases

- [ ] **8.1: Points Earning**
  - **Prerequisites**: Points rules configured
  - **Steps**:
    1. Complete qualifying action (e.g., purchase)
    2. Check points balance
    3. Verify points awarded
  - **Expected**: Correct points added to account
  - **Related Files**: `classes/LoyaltyPoints.php`, `api/points.php`

- [ ] **8.2: Points History View**
  - **Prerequisites**: Points transactions exist
  - **Steps**:
    1. Navigate to `/liff-points-history.php`
    2. View transaction history
    3. Verify dates and amounts
  - **Expected**: All transactions displayed correctly
  - **Related Files**: `liff-points-history.php`, `api/points-history.php`

- [ ] **8.3: Points Redemption**
  - **Prerequisites**: Sufficient points balance
  - **Steps**:
    1. Navigate to `/liff-redeem-points.php`
    2. Select reward
    3. Confirm redemption
  - **Expected**: Points deducted, reward applied
  - **Related Files**: `liff-redeem-points.php`, `api/points.php`

- [ ] **8.4: Points Rules Configuration**
  - **Prerequisites**: Admin access
  - **Steps**:
    1. Navigate to `/admin-points-settings.php`
    2. Create/modify points rule
    3. Save configuration
  - **Expected**: New rules applied to system
  - **Related Files**: `admin-points-settings.php`, `api/points-rules.php`

- [ ] **8.5: Rewards Management**
  - **Prerequisites**: Admin access
  - **Steps**:
    1. Navigate to `/admin-rewards.php`
    2. Create new reward
    3. Set points cost
    4. Activate reward
  - **Expected**: Reward available for redemption
  - **Related Files**: `admin-rewards.php`, `api/admin/rewards.php`

**Notes**:
```
[Document any issues or observations here]
```

---

## 9. Analytics & Reporting Testing

**Status**: ⬜ Not Started | 🔄 In Progress | ✅ Complete

### Test Cases

- [ ] **9.1: Analytics Dashboard Load**
  - **Prerequisites**: Analytics data exists
  - **Steps**:
    1. Navigate to `/analytics.php`
    2. View key metrics
    3. Check chart rendering
  - **Expected**: Dashboard displays with all metrics
  - **Related Files**: `analytics.php`, `includes/analytics/overview.php`

- [ ] **9.2: Date Range Filtering**
  - **Prerequisites**: Dashboard loaded
  - **Steps**:
    1. Select custom date range
    2. Apply filter
    3. Verify data updates
  - **Expected**: Data filtered to selected dates
  - **Related Files**: `analytics.php`, `app/Controllers/AnalyticsController.php`

- [ ] **9.3: CRM Analytics**
  - **Prerequisites**: Customer data exists
  - **Steps**:
    1. Navigate to `/crm-analytics.php`
    2. View customer metrics
    3. Check engagement data
  - **Expected**: Customer behavior insights displayed
  - **Related Files**: `crm-analytics.php`, `includes/analytics/crm.php`

- [ ] **9.4: Sales Reports**
  - **Prerequisites**: Orders exist
  - **Steps**:
    1. Navigate to `/shop/reports.php`
    2. Generate sales report
    3. Verify calculations
  - **Expected**: Accurate revenue and order data
  - **Related Files**: `shop/reports.php`

- [ ] **9.5: Inventory Reports**
  - **Prerequisites**: Inventory data exists
  - **Steps**:
    1. Navigate to `/inventory/reports.php`
    2. View stock reports
    3. Check valuations
  - **Expected**: Current inventory status displayed
  - **Related Files**: `inventory/reports.php`, `includes/inventory/reports.php`

**Notes**:
```
[Document any issues or observations here]
```

---

## 10. Notification & Communication Testing

**Status**: ⬜ Not Started | 🔄 In Progress | ✅ Complete

### Test Cases

- [ ] **10.1: Notification Creation**
  - **Prerequisites**: Notification event occurs
  - **Steps**:
    1. Trigger notification event
    2. Check notifications table
    3. Verify notification created
  - **Expected**: Notification record exists
  - **Related Files**: `classes/NotificationService.php`, `api/user-notifications.php`

- [ ] **10.2: Scheduled Messages**
  - **Prerequisites**: Admin access
  - **Steps**:
    1. Navigate to `/scheduled.php`
    2. Create scheduled message
    3. Set send time
    4. Wait for scheduled time
  - **Expected**: Message sent at scheduled time
  - **Related Files**: `scheduled.php`, `cron/send_scheduled.php`

- [ ] **10.3: Drip Campaigns**
  - **Prerequisites**: Campaign configured
  - **Steps**:
    1. Navigate to `/drip-campaigns.php`
    2. Create campaign
    3. Set triggers and delays
    4. Activate campaign
  - **Expected**: Sequential messages sent based on triggers
  - **Related Files**: `drip-campaigns.php`, `cron/process_drip_campaigns.php`

- [ ] **10.4: Appointment Reminders**
  - **Prerequisites**: Appointment scheduled
  - **Steps**:
    1. Schedule appointment
    2. Wait for reminder time
    3. Check LINE messages
  - **Expected**: Reminder sent before appointment
  - **Related Files**: `cron/appointment_reminder.php`

- [ ] **10.5: Medication Refill Reminders**
  - **Prerequisites**: Medication history exists
  - **Steps**:
    1. Set refill reminder
    2. Wait for reminder date
    3. Check notifications
  - **Expected**: Refill reminder sent
  - **Related Files**: `cron/medication_refill_reminder.php`

**Notes**:
```
[Document any issues or observations here]
```

---

## 11. CRM & Customer Management Testing

**Status**: ⬜ Not Started | 🔄 In Progress | ✅ Complete

### Test Cases

- [ ] **11.1: Customer Profile View**
  - **Prerequisites**: Customer data exists
  - **Steps**:
    1. Navigate to `/user-detail.php?id=X`
    2. View customer profile
    3. Check data completeness
  - **Expected**: Complete customer information displayed
  - **Related Files**: `user-detail.php`, `api/user-profile.php`

- [ ] **11.2: Customer Segmentation**
  - **Prerequisites**: Multiple customers exist
  - **Steps**:
    1. Navigate to `/customer-segments.php`
    2. Create new segment
    3. Define criteria
    4. View segment members
  - **Expected**: Customers grouped by criteria
  - **Related Files**: `customer-segments.php`

- [ ] **11.3: Customer Tagging**
  - **Prerequisites**: Tags configured
  - **Steps**:
    1. Navigate to `/user-tags.php`
    2. Apply tags to customers
    3. Filter by tags
  - **Expected**: Tags applied and filterable
  - **Related Files**: `user-tags.php`

- [ ] **11.4: Auto-Reply Rules**
  - **Prerequisites**: Admin access
  - **Steps**:
    1. Navigate to `/auto-reply.php`
    2. Create auto-reply rule
    3. Set trigger keywords
    4. Test with matching message
  - **Expected**: Auto-reply sent when triggered
  - **Related Files**: `auto-reply.php`, `classes/AutoTagManager.php`

- [ ] **11.5: Activity Logging**
  - **Prerequisites**: Customer interactions occur
  - **Steps**:
    1. View customer profile
    2. Check activity log
    3. Verify all interactions logged
  - **Expected**: Complete activity history displayed
  - **Related Files**: `classes/ActivityLogger.php`, `activity-logs.php`

**Notes**:
```
[Document any issues or observations here]
```

---

## 12. Appointment & Video Call Testing

**Status**: ⬜ Not Started | 🔄 In Progress | ✅ Complete

### Test Cases

- [ ] **12.1: Appointment Booking**
  - **Prerequisites**: User logged in LIFF
  - **Steps**:
    1. Navigate to `/liff-appointment.php`
    2. Select date and time
    3. Choose service type
    4. Confirm booking
  - **Expected**: Appointment created successfully
  - **Related Files**: `liff-appointment.php`, `api/appointments.php`

- [ ] **12.2: Appointment Confirmation**
  - **Prerequisites**: Appointment booked
  - **Steps**:
    1. Check LINE messages
    2. View "My Appointments"
    3. Verify details
  - **Expected**: Confirmation sent, appointment visible
  - **Related Files**: `liff-my-appointments.php`, `api/appointments.php`

- [ ] **12.3: Video Call Connection**
  - **Prerequisites**: Appointment scheduled
  - **Steps**:
    1. Join video call at scheduled time
    2. Test camera and microphone
    3. Verify connection quality
  - **Expected**: Stable video/audio connection
  - **Related Files**: `liff-video-call.php`, `video-call.php`

- [ ] **12.4: Call Duration Recording**
  - **Prerequisites**: Video call completed
  - **Steps**:
    1. End video call
    2. Check appointment record
    3. Verify duration logged
  - **Expected**: Call duration recorded accurately
  - **Related Files**: `api/video-call.php`

- [ ] **12.5: Appointment Reminders**
  - **Prerequisites**: Appointment scheduled
  - **Steps**:
    1. Wait for reminder time
    2. Check notifications
  - **Expected**: Reminder sent to both parties
  - **Related Files**: `cron/appointment_reminder.php`

**Notes**:
```
[Document any issues or observations here]
```

---

## 13. Content & Template Management Testing

**Status**: ⬜ Not Started | 🔄 In Progress | ✅ Complete

### Test Cases

- [ ] **13.1: Flex Template Creation**
  - **Prerequisites**: Admin access
  - **Steps**:
    1. Navigate to `/flex-builder.php`
    2. Design Flex Message
    3. Save template
  - **Expected**: Template saved successfully
  - **Related Files**: `flex-builder.php`, `classes/FlexTemplates.php`

- [ ] **13.2: Template Preview**
  - **Prerequisites**: Template exists
  - **Steps**:
    1. Select template
    2. Click preview
    3. Verify rendering
  - **Expected**: Template displays correctly
  - **Related Files**: `flex-builder.php`, `assets/js/flex-preview.js`

- [ ] **13.3: Rich Menu Update**
  - **Prerequisites**: Admin access
  - **Steps**:
    1. Navigate to `/rich-menu.php`
    2. Modify menu layout
    3. Upload new image
    4. Apply to users
  - **Expected**: Rich Menu updated for all users
  - **Related Files**: `rich-menu.php`, `api/dynamic-rich-menu.php`

- [ ] **13.4: Broadcast Catalog Creation**
  - **Prerequisites**: Products exist
  - **Steps**:
    1. Navigate to broadcast catalog
    2. Select products
    3. Create catalog message
    4. Send to users
  - **Expected**: Product catalog delivered
  - **Related Files**: `includes/broadcast/catalog.php`

- [ ] **13.5: Welcome Message Configuration**
  - **Prerequisites**: Admin access
  - **Steps**:
    1. Navigate to `/welcome-settings.php`
    2. Configure welcome message
    3. Save settings
    4. Test with new follower
  - **Expected**: Welcome message sent to new followers
  - **Related Files**: `welcome-settings.php`, `includes/settings/welcome.php`

**Notes**:
```
[Document any issues or observations here]
```

---

## 14. System Administration Testing

**Status**: ⬜ Not Started | 🔄 In Progress | ✅ Complete

### Test Cases

- [ ] **14.1: System Settings Update**
  - **Prerequisites**: Admin access
  - **Steps**:
    1. Navigate to `/settings.php`
    2. Modify configuration
    3. Save changes
    4. Verify applied
  - **Expected**: Settings saved and active
  - **Related Files**: `settings.php`, `config/config.php`

- [ ] **14.2: User Permission Management**
  - **Prerequisites**: Admin access
  - **Steps**:
    1. Navigate to `/admin-users.php`
    2. Modify user role
    3. Save changes
    4. Test access
  - **Expected**: Permissions updated correctly
  - **Related Files**: `admin-users.php`, `classes/AdminAuth.php`

- [ ] **14.3: Activity Log Review**
  - **Prerequisites**: System activity exists
  - **Steps**:
    1. Navigate to `/activity-logs.php`
    2. Filter by date/user
    3. Review events
  - **Expected**: All system events logged
  - **Related Files**: `activity-logs.php`, `classes/ActivityLogger.php`

- [ ] **14.4: Database Migration**
  - **Prerequisites**: Migration file exists
  - **Steps**:
    1. Navigate to migration script
    2. Run migration
    3. Verify schema updated
    4. Check data integrity
  - **Expected**: Schema updated without data loss
  - **Related Files**: `install/run_*_migration.php`, `database/migration_*.sql`

- [ ] **14.5: Data Synchronization**
  - **Prerequisites**: Sync configured
  - **Steps**:
    1. Navigate to `/sync-dashboard.php`
    2. Trigger sync operation
    3. Monitor progress
    4. Verify completion
  - **Expected**: Data synced successfully
  - **Related Files**: `sync-dashboard.php`, `classes/SyncWorker.php`

**Notes**:
```
[Document any issues or observations here]
```

---

## 15. Mobile & PWA Testing

**Status**: ⬜ Not Started | 🔄 In Progress | ✅ Complete

### Test Cases

- [ ] **15.1: PWA Installation**
  - **Prerequisites**: HTTPS enabled
  - **Steps**:
    1. Open site in mobile browser
    2. Click "Add to Home Screen"
    3. Install PWA
    4. Launch from home screen
  - **Expected**: App installs and launches
  - **Related Files**: `manifest.json`, `sw.js`

- [ ] **15.2: Offline Functionality**
  - **Prerequisites**: PWA installed
  - **Steps**:
    1. Enable airplane mode
    2. Open PWA
    3. Navigate pages
  - **Expected**: Cached content displays
  - **Related Files**: `sw.js`, `offline.html`

- [ ] **15.3: Service Worker Update**
  - **Prerequisites**: Service worker active
  - **Steps**:
    1. Deploy new service worker
    2. Reload app
    3. Verify update applied
  - **Expected**: New service worker activated
  - **Related Files**: `sw.js`

- [ ] **15.4: Push Notifications**
  - **Prerequisites**: Notifications enabled
  - **Steps**:
    1. Grant notification permission
    2. Trigger notification event
    3. Check device notifications
  - **Expected**: Push notification received
  - **Related Files**: `sw.js`, `classes/NotificationService.php`

- [ ] **15.5: Responsive Design**
  - **Prerequisites**: None
  - **Steps**:
    1. Open site on mobile device
    2. Test portrait/landscape
    3. Check different screen sizes
    4. Verify touch interactions
  - **Expected**: Layout adapts correctly
  - **Related Files**: `assets/css/*.css`, `liff/assets/css/liff-app.css`

**Notes**:
```
[Document any issues or observations here]
```

---

## Testing Summary

### Overall Progress

- **Total Test Cases**: 127
- **Completed**: ___
- **Passed**: ___
- **Failed**: ___
- **Skipped**: ___

### Critical Issues Found

```
[List any critical issues that block functionality]
```

### Non-Critical Issues

```
[List minor issues or improvements needed]
```

### Recommendations

```
[Provide recommendations for improvements or next steps]
```

---

## Sign-Off

**Tested By**: ___________________  
**Date**: ___________________  
**Environment**: Development / Staging / Production  
**Overall Status**: ✅ Pass | ❌ Fail | 🔄 Needs Retest

**Notes**:
```
[Final comments or observations]
```
