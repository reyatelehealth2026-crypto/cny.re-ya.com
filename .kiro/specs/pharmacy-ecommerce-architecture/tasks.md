# Implementation Plan

## Phase 1: Core Infrastructure

- [ ] 1. Set up project structure and database schema
  - [ ] 1.1 Create database migration for products table with product_type field
    - Add columns: id, line_account_id, name, description, price, stock, product_type (ENUM: 'OTC', 'Prescription'), image_url, reorder_threshold, is_active, created_at, updated_at
    - Add indexes for line_account_id and product_type
    - _Requirements: 1.1_
  - [ ]* 1.2 Write property test for product type validation
    - **Property 1: Product Type Validation**
    - **Validates: Requirements 1.1**
  - [ ] 1.3 Create database migration for orders table
    - Add columns: id, user_id, line_account_id, items (JSON), subtotal, discount, total, status, payment_slip_url, prescription_approval_id, created_at, updated_at
    - Add indexes for user_id, line_account_id, status
    - _Requirements: 3.5, 4.2_
  - [ ] 1.4 Create database migration for stock_movements table
    - Add columns: id, product_id, quantity, movement_type, reference_id, reason, created_by, created_at
    - Add indexes for product_id, movement_type, created_at
    - _Requirements: 5.3, 5.4_
  - [ ] 1.5 Create database migration for video_call_sessions table



    - Add columns: id, user_id, pharmacist_id, status, started_at, ended_at, duration, notes, created_at
    - Add indexes for user_id, pharmacist_id, status
    - _Requirements: 2.1, 2.4_
  - [ ] 1.6 Create database migration for point_transactions table
    - Add columns: id, user_id, points, transaction_type, order_id, balance_after, created_at
    - Add indexes for user_id, transaction_type
    - _Requirements: 6.1, 6.5_

- [ ] 2. Checkpoint - Ensure all migrations run successfully
  - Ensure all tests pass, ask the user if questions arise.

## Phase 2: Product Service Implementation

- [ ] 3. Implement Product Service
  - [ ] 3.1 Create ProductService class with CRUD operations
    - Implement createProduct(), getProduct(), listProducts(), updateProduct(), deleteProduct()
    - Validate product_type is either 'OTC' or 'Prescription'
    - _Requirements: 1.1_
  - [ ]* 3.2 Write property test for product type validation
    - **Property 1: Product Type Validation**
    - **Validates: Requirements 1.1**
  - [ ] 3.3 Implement checkProductType() method
    - Return product type for given product ID
    - _Requirements: 1.1, 1.2, 1.3_
  - [ ] 3.4 Implement product listing with filters
    - Filter by category, product_type, price range, stock availability
    - Ensure all returned products have name, price, and stock
    - _Requirements: 3.1_
  - [ ]* 3.5 Write property test for product display completeness
    - **Property 7: Product Display Completeness**
    - **Validates: Requirements 3.1**

## Phase 3: Cart Service Implementation

- [ ] 4. Implement Cart Service
  - [ ] 4.1 Create CartService class
    - Implement addToCart(), removeFromCart(), getCart(), clearCart()
    - Store cart in Redis for fast access
    - _Requirements: 1.2, 3.2_
  - [ ]* 4.2 Write property test for OTC product cart addition
    - **Property 2: OTC Product Cart Addition**
    - **Validates: Requirements 1.2**
  - [ ]* 4.3 Write property test for cart state persistence
    - **Property 8: Cart State Persistence**
    - **Validates: Requirements 3.2**
  - [ ] 4.4 Implement calculateTotal() method
    - Calculate subtotal, apply discounts, return total
    - _Requirements: 3.2_
  - [ ] 4.5 Implement validateCartStock() method
    - Check stock availability for all cart items
    - Return validation result with insufficient items
    - _Requirements: 3.3_
  - [ ]* 4.6 Write property test for stock validation at checkout
    - **Property 9: Stock Validation at Checkout**
    - **Validates: Requirements 3.3**

- [ ] 5. Checkpoint - Cart functionality complete
  - Ensure all tests pass, ask the user if questions arise.

## Phase 4: Order Service Implementation

- [ ] 6. Implement Order Service
  - [ ] 6.1 Create OrderService class
    - Implement createOrder(), getOrder(), listOrders(), updateOrderStatus()
    - Check for prescription products and set status accordingly
    - _Requirements: 1.3, 3.5_
  - [ ]* 6.2 Write property test for prescription order blocking
    - **Property 3: Prescription Order Blocking**
    - **Validates: Requirements 1.3**
  - [ ] 6.3 Implement order creation with stock reduction
    - Create order record, reduce stock for each item, create stock movements
    - _Requirements: 3.5_
  - [ ]* 6.4 Write property test for order stock reduction
    - **Property 10: Order Stock Reduction**
    - **Validates: Requirements 3.5**
  - [ ] 6.5 Implement serializeOrder() and deserializeOrder() methods
    - Serialize order to JSON for storage
    - Deserialize JSON back to Order object
    - _Requirements: 4.5, 4.6_
  - [ ]* 6.6 Write property test for order data round-trip
    - **Property 11: Order Data Round-Trip**
    - **Validates: Requirements 4.5, 4.6**
  - [ ] 6.7 Implement uploadPaymentSlip() method
    - Store slip image, update order status to pending_verification
    - _Requirements: 4.2_
  - [ ]* 6.8 Write property test for payment slip state transition
    - **Property 12: Payment Slip State Transition**
    - **Validates: Requirements 4.2**
  - [ ] 6.9 Implement verifyPayment() method
    - Update order status based on verification result
    - Send LINE notification to user
    - _Requirements: 4.3, 4.4_

## Phase 5: Prescription Approval Service

- [ ] 7. Implement Prescription Approval Service
  - [ ] 7.1 Create PrescriptionApprovalService class
    - Implement requestApproval(), approveOrder(), rejectOrder(), getPendingApprovals()
    - _Requirements: 1.3, 1.4, 1.5_
  - [ ] 7.2 Implement approval request creation
    - Create approval request record, notify pharmacists
    - _Requirements: 1.3_
  - [ ] 7.3 Implement order approval flow
    - Update order status, allow checkout to proceed
    - _Requirements: 1.4_
  - [ ] 7.4 Implement order rejection flow
    - Remove prescription items, notify user with reason
    - _Requirements: 1.5_
  - [ ]* 7.5 Write property test for prescription rejection cleanup
    - **Property 4: Prescription Rejection Cleanup**
    - **Validates: Requirements 1.5**

- [ ] 8. Checkpoint - Order and approval flow complete
  - Ensure all tests pass, ask the user if questions arise.

## Phase 6: Inventory Service Implementation

- [ ] 9. Implement Inventory Service
  - [ ] 9.1 Create InventoryService class
    - Implement getStock(), adjustStock(), receiveGoods(), createPurchaseOrder()
    - _Requirements: 5.1, 5.2, 5.3, 5.4_
  - [ ] 9.2 Implement low stock alert generation
    - Check products below reorder threshold, generate alerts
    - _Requirements: 5.1_
  - [ ]* 9.3 Write property test for low stock alert generation
    - **Property 13: Low Stock Alert Generation**
    - **Validates: Requirements 5.1**
  - [ ] 9.4 Implement purchase order creation
    - Validate required fields: supplier, products, quantities, delivery date
    - _Requirements: 5.2_
  - [ ]* 9.5 Write property test for purchase order recording
    - **Property 14: Purchase Order Recording**
    - **Validates: Requirements 5.2**
  - [ ] 9.6 Implement goods receipt
    - Increase stock quantities, create stock movement records
    - _Requirements: 5.3_
  - [ ]* 9.7 Write property test for goods receipt stock update
    - **Property 15: Goods Receipt Stock Update**
    - **Validates: Requirements 5.3**
  - [ ] 9.8 Implement stock movement query with filters
    - Filter by date range, movement type
    - _Requirements: 5.5_
  - [ ]* 9.9 Write property test for stock movement query filtering
    - **Property 16: Stock Movement Query Filtering**
    - **Validates: Requirements 5.5**

## Phase 7: Video Call Service Implementation

- [ ] 10. Implement Video Call Service
  - [ ] 10.1 Create VideoCallService class
    - Implement createSession(), acceptSession(), endSession(), getActiveSession()
    - _Requirements: 2.1, 2.2, 2.4_
  - [ ] 10.2 Implement session creation with pharmacist notification
    - Create session with 'waiting' status, notify available pharmacists
    - _Requirements: 2.1_
  - [ ]* 10.3 Write property test for video session creation
    - **Property 5: Video Session Creation**
    - **Validates: Requirements 2.1**
  - [ ] 10.4 Implement session acceptance
    - Update session with pharmacist ID, establish connection
    - _Requirements: 2.2_
  - [ ] 10.5 Implement session end with recording
    - Calculate duration, save notes, update status
    - _Requirements: 2.4_
  - [ ]* 10.6 Write property test for video session recording
    - **Property 6: Video Session Recording**
    - **Validates: Requirements 2.4**
  - [ ] 10.7 Implement timeout handling
    - Check for sessions waiting > 5 minutes, notify user
    - _Requirements: 2.5_

- [ ] 11. Checkpoint - Video call functionality complete
  - Ensure all tests pass, ask the user if questions arise.

## Phase 8: Member and Loyalty Service

- [ ] 12. Implement Member Service
  - [ ] 12.1 Create MemberService class
    - Implement register(), getMember(), updateMember(), getPointBalance()
    - _Requirements: 6.1, 6.2_
  - [ ] 12.2 Implement points earning
    - Calculate points based on order total, create transaction record
    - _Requirements: 6.1_
  - [ ]* 12.3 Write property test for points calculation
    - **Property 17: Points Calculation**
    - **Validates: Requirements 6.1**
  - [ ] 12.4 Implement points redemption
    - Validate balance, apply discount, create transaction record
    - _Requirements: 6.3, 6.4_
  - [ ]* 12.5 Write property test for points redemption validation
    - **Property 18: Points Redemption Validation**
    - **Validates: Requirements 6.3, 6.4**
  - [ ]* 12.6 Write property test for points transaction recording
    - **Property 19: Points Transaction Recording**
    - **Validates: Requirements 6.5**
  - [ ] 12.7 Implement point history retrieval
    - Return transaction history for user
    - _Requirements: 6.5_

## Phase 9: LINE Account Management

- [ ] 13. Implement LINE Account Service
  - [ ] 13.1 Create LineAccountService class
    - Implement addAccount(), getAccount(), listAccounts(), updateAccount()
    - _Requirements: 7.1_
  - [ ] 13.2 Implement webhook URL generation
    - Generate unique webhook URL for each account
    - _Requirements: 7.1_
  - [ ]* 13.3 Write property test for webhook URL uniqueness
    - **Property 20: Webhook URL Uniqueness**
    - **Validates: Requirements 7.1**
  - [ ] 13.4 Implement webhook routing
    - Parse webhook path, identify target account
    - _Requirements: 7.2_
  - [ ]* 13.5 Write property test for webhook routing accuracy
    - **Property 21: Webhook Routing Accuracy**
    - **Validates: Requirements 7.2**
  - [ ] 13.6 Implement Rich Menu configuration
    - Apply menu to selected LINE OA account
    - _Requirements: 7.3_

- [ ] 14. Checkpoint - LINE account management complete
  - Ensure all tests pass, ask the user if questions arise.

## Phase 10: Broadcast and Auto-Reply

- [ ] 15. Implement Broadcast Service
  - [ ] 15.1 Create BroadcastService class
    - Implement createBroadcast(), scheduleBroadcast(), sendBroadcast()
    - _Requirements: 9.1, 9.2_
  - [ ] 15.2 Implement audience targeting
    - Filter users by tags, segments, LINE account
    - _Requirements: 9.1, 7.4_
  - [ ]* 15.3 Write property test for broadcast account isolation
    - **Property 22: Broadcast Account Isolation**
    - **Validates: Requirements 7.4**
  - [ ] 15.4 Implement scheduled broadcast
    - Queue messages, send at specified time
    - _Requirements: 9.2_
  - [ ]* 15.5 Write property test for scheduled broadcast timing
    - **Property 25: Scheduled Broadcast Timing**
    - **Validates: Requirements 9.2**
  - [ ] 15.6 Implement unsubscribe handling
    - Exclude unsubscribed users from broadcasts
    - _Requirements: 9.5_
  - [ ]* 15.7 Write property test for unsubscribe exclusion
    - **Property 26: Unsubscribe Exclusion**
    - **Validates: Requirements 9.5**
  - [ ] 15.8 Implement drip campaigns
    - Send sequential messages with configured delays
    - _Requirements: 9.3_

- [ ] 16. Implement Auto-Reply Service
  - [ ] 16.1 Create AutoReplyService class
    - Implement keyword matching and response
    - _Requirements: 8.1_
  - [ ]* 16.2 Write property test for auto-reply keyword matching
    - **Property 23: Auto-Reply Keyword Matching**
    - **Validates: Requirements 8.1**
  - [ ] 16.3 Integrate AI chatbot for symptom queries
    - Invoke AI for medication and symptom questions
    - _Requirements: 8.2_
  - [ ] 16.4 Implement red flag detection and alerting
    - Detect emergency symptoms, alert pharmacists
    - _Requirements: 8.3_
  - [ ]* 16.5 Write property test for red flag alert
    - **Property 24: Red Flag Alert**
    - **Validates: Requirements 8.3**

## Phase 11: Reporting and Analytics

- [ ] 17. Implement Analytics Service
  - [ ] 17.1 Create AnalyticsService class
    - Implement dashboard metrics, sales reports, customer analytics
    - _Requirements: 10.1, 10.2, 10.3_
  - [ ] 17.2 Implement sales report generation
    - Aggregate by date range, category, payment status
    - _Requirements: 10.2_
  - [ ]* 17.3 Write property test for report data aggregation
    - **Property 27: Report Data Aggregation**
    - **Validates: Requirements 10.2**
  - [ ] 17.4 Implement report export
    - Generate CSV and PDF formats
    - _Requirements: 10.4_
  - [ ] 17.5 Implement scheduled reports
    - Auto-generate and email at configured intervals
    - _Requirements: 10.5_
  - [ ] 17.6 Implement per-account analytics aggregation
    - Aggregate data per LINE OA account
    - _Requirements: 7.5_

## Phase 12: LIFF Integration

- [ ] 18. Implement LIFF Pages
  - [ ] 18.1 Create LIFF shop page
    - Display products with images, prices, stock
    - Implement add to cart functionality
    - _Requirements: 3.1, 3.2_
  - [ ] 18.2 Create LIFF checkout page
    - Display cart, payment options, order summary
    - _Requirements: 3.3, 4.1_
  - [ ] 18.3 Create LIFF order history page
    - Display user's orders with status
    - _Requirements: 3.6_
  - [ ] 18.4 Create LIFF member card page
    - Display point balance, membership tier
    - _Requirements: 6.2_
  - [ ] 18.5 Create LIFF video call page
    - Implement video call UI for consultation
    - _Requirements: 2.2, 2.3_

- [ ] 19. Final Checkpoint - Complete system integration
  - Ensure all tests pass, ask the user if questions arise.
