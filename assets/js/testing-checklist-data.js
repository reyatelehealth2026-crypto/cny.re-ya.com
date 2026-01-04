// Complete test categories data
const testCategories = [
    {
        id: 'auth',
        icon: '🔐',
        title: 'Authentication & Authorization',
        tests: [
            {
                id: '1.1',
                title: 'Admin Login - Valid Credentials',
                prerequisites: 'Valid admin account exists',
                steps: [
                    'Navigate to /auth/login.php',
                    'Enter valid username and password',
                    'Click "Login" button'
                ],
                expected: 'Redirect to dashboard, session created',
                files: 'auth/login.php, classes/AdminAuth.php'
            },
            {
                id: '1.2',
                title: 'Admin Login - Invalid Credentials',
                prerequisites: 'None',
                steps: [
                    'Navigate to /auth/login.php',
                    'Enter invalid credentials',
                    'Click "Login" button'
                ],
                expected: 'Error message displayed, no session created',
                files: 'auth/login.php'
            },
            {
                id: '1.3',
                title: 'LINE LIFF Authentication',
                prerequisites: 'LINE test account',
                steps: [
                    'Open any LIFF app URL',
                    'Verify LINE login prompt',
                    'Complete LINE authentication'
                ],
                expected: 'LIFF initialized, user profile loaded',
                files: 'liff/index.php, assets/js/liff-init.js'
            },
            {
                id: '1.4',
                title: 'Unauthorized Access Prevention',
                prerequisites: 'Not logged in',
                steps: [
                    'Navigate directly to /dashboard.php',
                    'Observe behavior'
                ],
                expected: 'Redirect to login page',
                files: 'includes/auth_check.php'
            },
            {
                id: '1.5',
                title: 'Pharmacist Role Access',
                prerequisites: 'Pharmacist account',
                steps: [
                    'Login as pharmacist',
                    'Attempt to access admin-only pages',
                    'Verify pharmacist dashboard access'
                ],
                expected: 'Admin pages blocked, pharmacist features accessible',
                files: 'pharmacist-dashboard.php, classes/AdminAuth.php'
            }
        ]
    },
    {
        id: 'line',
        icon: '💬',
        title: 'LINE Integration',
        tests: [
            {
                id: '2.1',
                title: 'Webhook Message Processing',
                prerequisites: 'Webhook URL configured in LINE Developers',
                steps: [
                    'Send a text message to LINE bot',
                    'Check webhook.php logs',
                    'Verify response received'
                ],
                expected: 'Message processed, appropriate response sent',
                files: 'webhook.php, classes/LineAPI.php'
            },
            {
                id: '2.2',
                title: 'Broadcast Message Delivery',
                prerequisites: 'Admin access, test users',
                steps: [
                    'Navigate to /broadcast.php',
                    'Create broadcast message',
                    'Select target audience',
                    'Send broadcast'
                ],
                expected: 'Message delivered to all targeted users',
                files: 'broadcast.php, api/broadcast.php'
            },
            {
                id: '2.3',
                title: 'Rich Menu Interaction',
                prerequisites: 'Rich Menu configured',
                steps: [
                    'Open LINE chat with bot',
                    'Tap Rich Menu buttons',
                    'Verify actions executed'
                ],
                expected: 'Correct actions triggered for each button',
                files: 'rich-menu.php, classes/DynamicRichMenu.php'
            },
            {
                id: '2.4',
                title: 'LIFF App Initialization',
                prerequisites: 'LIFF app registered',
                steps: [
                    'Open LIFF URL in LINE',
                    'Check browser console',
                    'Verify liff.init() success'
                ],
                expected: 'LIFF SDK initialized, no errors',
                files: 'liff/index.php, assets/js/liff-init.js'
            },
            {
                id: '2.5',
                title: 'Flex Message Rendering',
                prerequisites: 'Flex template created',
                steps: [
                    'Send Flex Message via admin panel',
                    'Check LINE chat display',
                    'Test interactive elements'
                ],
                expected: 'Message renders correctly, buttons work',
                files: 'classes/FlexTemplates.php, flex-builder.php'
            }
        ]
    },
    {
        id: 'ecommerce',
        icon: '🛒',
        title: 'E-commerce & Shop',
        tests: [
            {
                id: '3.1',
                title: 'Product Browsing',
                prerequisites: 'Products exist in database',
                steps: [
                    'Navigate to /liff-shop.php',
                    'Browse product categories',
                    'Check product images and prices'
                ],
                expected: 'All products display correctly',
                files: 'liff-shop.php, api/shop-products.php'
            },
            {
                id: '3.2',
                title: 'Add to Cart',
                prerequisites: 'User logged in via LIFF',
                steps: [
                    'Select a product',
                    'Click "Add to Cart"',
                    'Verify cart count updates'
                ],
                expected: 'Cart count increases, item stored',
                files: 'liff-shop.php, api/checkout.php'
            },
            {
                id: '3.3',
                title: 'Checkout Process',
                prerequisites: 'Items in cart',
                steps: [
                    'Proceed to checkout',
                    'Enter shipping details',
                    'Select payment method',
                    'Confirm order'
                ],
                expected: 'Order created, confirmation sent',
                files: 'liff-checkout.php, api/checkout.php'
            }
        ]
    },
    {
        id: 'inventory',
        icon: '📦',
        title: 'Inventory & WMS',
        tests: [
            {
                id: '4.1',
                title: 'Stock Level Tracking',
                prerequisites: 'Products with stock',
                steps: [
                    'Navigate to /inventory/index.php',
                    'View current stock levels',
                    'Verify accuracy'
                ],
                expected: 'Stock levels match database',
                files: 'inventory/index.php, classes/InventoryService.php'
            },
            {
                id: '4.2',
                title: 'WMS - Pick Task Creation',
                prerequisites: 'Order placed',
                steps: [
                    'Navigate to WMS dashboard',
                    'View pending pick tasks',
                    'Verify order details'
                ],
                expected: 'Pick task created for order',
                files: 'includes/inventory/wms-dashboard.php, classes/WMSService.php'
            }
        ]
    },
    {
        id: 'accounting',
        icon: '💰',
        title: 'Accounting System',
        tests: [
            {
                id: '5.1',
                title: 'Receipt Voucher Creation (AR)',
                prerequisites: 'Customer account exists',
                steps: [
                    'Navigate to /accounting.php → AR tab',
                    'Create new receipt voucher',
                    'Enter amount and details',
                    'Save voucher'
                ],
                expected: 'AR record created, balance updated',
                files: 'includes/accounting/ar.php, classes/ReceiptVoucherService.php'
            }
        ]
    },
    {
        id: 'pharmacy',
        icon: '💊',
        title: 'Pharmacy & Consultation',
        tests: [
            {
                id: '6.1',
                title: 'Consultation Session Creation',
                prerequisites: 'User logged in LIFF',
                steps: [
                    'Navigate to /liff-pharmacy-consult.php',
                    'Request consultation',
                    'Fill in symptoms/questions',
                    'Submit request'
                ],
                expected: 'Session created, pharmacist notified',
                files: 'liff-pharmacy-consult.php, api/pharmacist.php'
            }
        ]
    },
    {
        id: 'ai',
        icon: '🤖',
        title: 'AI & Chatbot',
        tests: [
            {
                id: '7.1',
                title: 'AI Chatbot Response',
                prerequisites: 'AI configured',
                steps: [
                    'Send message to chatbot',
                    'Verify response received',
                    'Check response relevance'
                ],
                expected: 'Appropriate AI response generated',
                files: 'ai-chatbot.php, api/ai-chat.php'
            }
        ]
    },
    {
        id: 'loyalty',
        icon: '🎁',
        title: 'Loyalty & Points',
        tests: [
            {
                id: '8.1',
                title: 'Points Earning',
                prerequisites: 'Points rules configured',
                steps: [
                    'Complete qualifying action (e.g., purchase)',
                    'Check points balance',
                    'Verify points awarded'
                ],
                expected: 'Correct points added to account',
                files: 'classes/LoyaltyPoints.php, api/points.php'
            }
        ]
    }
];

// Initialize test data from categories
function initializeTestData() {
    const data = {};
    testCategories.forEach(category => {
        category.tests.forEach(test => {
            data[test.id] = { status: 'pending', notes: '' };
        });
    });
    return data;
}
