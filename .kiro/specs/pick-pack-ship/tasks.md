# Implementation Plan

## Pick-Pack-Ship (WMS) System

- [x] 1. Database Migration





  - [x] 1.1 Create migration file for WMS tables and fields


    - Add wms_status, picker_id, packer_id, timestamps to transactions table
    - Create wms_activity_logs table
    - Create wms_batch_picks and wms_batch_pick_orders tables
    - _Requirements: 1.1, 1.3, 3.3, 5.1, 9.5_
  - [x] 1.2 Create migration runner script


    - _Requirements: 1.1_


- [x] 2. WMS Service Layer





  - [x] 2.1 Create WMSService class with pick operations

    - Implement getPickQueue(), startPicking(), confirmItemPicked(), completePicking()
    - _Requirements: 1.1, 1.2, 1.3, 1.5, 1.6_
  - [ ]* 2.2 Write property test for pick queue filtering
    - **Property 1: Pick queue contains only confirmed/paid orders**
    - **Validates: Requirements 1.1**
  - [ ]* 2.3 Write property test for pick queue sorting
    - **Property 2: Pick queue sorting by date**
    - **Validates: Requirements 1.2**

  - [x] 2.4 Implement batch pick operations





    - Implement createBatchPick(), getBatchPickList(), completeBatchPick()
    - _Requirements: 2.1, 2.2, 2.3, 2.4_
  - [ ]* 2.5 Write property test for batch pick consolidation
    - **Property 5: Batch pick consolidation**
    - **Validates: Requirements 2.1, 2.2**
  - [x] 2.6 Implement pack operations






    - Implement getPackQueue(), startPacking(), completePacking()
    - _Requirements: 3.1, 3.2, 3.3, 3.5_
  - [ ]* 2.7 Write property test for pack validation
    - **Property 7: Pack validation**
    - **Validates: Requirements 3.2**
  - [x] 2.8 Implement ship operations

    - Implement getShipQueue(), assignCarrier(), confirmShipped()
    - _Requirements: 5.1, 5.2, 5.3, 5.4_
  - [ ]* 2.9 Write property test for tracking number status transition
    - **Property 10: Tracking number triggers shipped status**
    - **Validates: Requirements 5.3, 7.4**
  - [x] 2.10 Implement dashboard and exception operations

    - Implement getDashboardStats(), getOverdueOrders(), exception handling methods
    - _Requirements: 6.1, 6.2, 6.3, 9.1, 9.2, 9.4, 9.5_
  - [ ]* 2.11 Write property test for dashboard counts accuracy
    - **Property 11: Dashboard counts accuracy**
    - **Validates: Requirements 6.1**

- [ ] 3. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.


- [x] 4. WMS Print Service




  - [x] 4.1 Create WMSPrintService class


    - Implement generatePackingSlip(), generateShippingLabel()
    - _Requirements: 3.4, 4.1, 4.2, 4.5_
  - [ ]* 4.2 Write property test for shipping label required fields
    - **Property 9: Shipping label contains required fields**
    - **Validates: Requirements 4.1, 4.2**


  - [x] 4.3 Implement batch printing methods





    - Implement generateBatchPackingSlips(), generateBatchLabels()

















    - _Requirements: 8.2, 8.3_









- [ ] 5. WMS API Layer

  - [x] 5.1 Create api/wms.php with pick endpoints

    - Implement get_pick_queue, start_pick, confirm_item_picked, complete_pick, create_batch_pick
    - _Requirements: 1.1, 1.2, 1.3, 1.5, 1.6, 2.1_
  - [x] 5.2 Add pack endpoints


    - Implement get_pack_queue, start_pack, complete_pack
    - _Requirements: 3.1, 3.2, 3.5_

  - [x] 5.3 Add ship endpoints

    - Implement get_ship_queue, assign_tracking, confirm_shipped
    - _Requirements: 5.1, 5.2, 5.3_

  - [x] 5.4 Add dashboard and exception endpoints

    - Implement get_dashboard, get_exceptions, resolve_exception
    - _Requirements: 6.1, 6.2, 6.3, 9.4, 9.5_

  - [x] 5.5 Add print endpoints


    - Implement print_packing_slip, print_shipping_label
    - _Requirements: 3.4, 4.1, 8.2, 8.3_

- [ ] 6. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 7. WMS Tab UI

  - [x] 7.1 Create includes/inventory/wms.php tab content

    - Create base structure with sub-tabs navigation
    - _Requirements: 6.1_

  - [x] 7.2 Implement Dashboard sub-tab
    - Show status counts, today's metrics, overdue orders, performance summary

    - _Requirements: 6.1, 6.2, 6.3, 6.4_
  - [x] 7.3 Implement Pick Queue sub-tab

    - Show pending orders, start pick button, item checklist
    - _Requirements: 1.2, 1.3, 1.4, 1.5_

  - [x] 7.4 Implement Pack Station sub-tab
    - Show picked orders, pack confirmation, print buttons

    - _Requirements: 3.1, 3.2, 3.4, 3.5_
  - [x] 7.5 Implement Ship Queue sub-tab
    - Show packed orders, carrier selection, tracking input
    - _Requirements: 5.1, 5.2, 5.4_
  - [x] 7.6 Implement Exceptions sub-tab

    - Show orders with issues, resolution form
    - _Requirements: 9.4, 9.5_


- [x] 8. Integration with Inventory Page




  - [x] 8.1 Add WMS tab to inventory/index.php


    - Add tab button and include wms.php content
    - _Requirements: 6.1_
  - [x] 8.2 Add WMS menu item to quick access


    - Update includes/header.php with WMS quick access
    - _Requirements: 6.5_


- [x] 9. Order Status Synchronization




  - [x] 9.1 Implement status mapping in WMSService


    - Map wms_status to customer-facing status
    - _Requirements: 7.1, 7.2, 7.3, 7.4_
  - [ ]* 9.2 Write property test for status mapping
    - **Property 12: WMS to customer status mapping**
    - **Validates: Requirements 7.2, 7.3, 7.4**
  - [x] 9.3 Add LINE notification on status change


    - Send notification when order ships
    - _Requirements: 7.5_


- [x] 10. Data Export




  - [x] 10.1 Implement JSON export for fulfillment data


    - _Requirements: 10.1_
  - [ ]* 10.2 Write property test for serialization round-trip
    - **Property 14: Data serialization round-trip**
    - **Validates: Requirements 10.3**
  - [x] 10.3 Implement CSV export for carriers


    - _Requirements: 10.4_



- [x] 11. Final Checkpoint - Ensure all tests pass



  - Ensure all tests pass, ask the user if questions arise.
    