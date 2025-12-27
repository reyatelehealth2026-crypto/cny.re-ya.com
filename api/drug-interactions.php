<?php
/**
 * Drug Interactions API
 * API endpoint for checking drug interactions in LIFF app
 * 
 * Requirements: 12.1 - Check for interactions with existing cart items and user medication history
 * 
 * Endpoints:
 * POST /api/drug-interactions.php?action=check - Check interactions for a product
 * GET /api/drug-interactions.php?action=list - List all interactions (admin)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Initialize database
$db = Database::getInstance()->getConnection();

// Get action
$action = $_GET['action'] ?? $_POST['action'] ?? 'check';

try {
    switch ($action) {
        case 'check':
            handleCheckInteractions($db);
            break;
        case 'check_cart':
            handleCheckCartInteractions($db);
            break;
        case 'list':
            handleListInteractions($db);
            break;
        case 'get_user_medications':
            handleGetUserMedications($db);
            break;
        default:
            jsonResponse(false, 'Invalid action', null, 400);
    }
} catch (Exception $e) {
    error_log("Drug Interactions API Error: " . $e->getMessage());
    jsonResponse(false, 'Server error: ' . $e->getMessage(), null, 500);
}

/**
 * Check interactions for a product being added to cart
 * Requirements: 12.1 - Check for interactions with existing cart items and user medication history
 * 
 * @param PDO $db Database connection
 */
function handleCheckInteractions($db) {
    // Get input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $input = $_POST;
    }
    
    $productId = $input['product_id'] ?? null;
    $cartItems = $input['cart_items'] ?? [];
    $userMedications = $input['user_medications'] ?? [];
    $lineUserId = $input['line_user_id'] ?? null;
    $lineAccountId = $input['line_account_id'] ?? null;
    
    if (!$productId) {
        jsonResponse(false, 'Product ID is required', null, 400);
        return;
    }
    
    // Get product details
    $product = getProductDetails($db, $productId);
    if (!$product) {
        jsonResponse(false, 'Product not found', null, 404);
        return;
    }
    
    // If user is logged in, get their medication history
    if ($lineUserId && empty($userMedications)) {
        $userMedications = getUserMedicationHistory($db, $lineUserId, $lineAccountId);
    }
    
    // Get generic names from cart items
    $cartDrugs = [];
    foreach ($cartItems as $item) {
        $cartProduct = getProductDetails($db, $item['product_id'] ?? $item['id'] ?? 0);
        if ($cartProduct && !empty($cartProduct['generic_name'])) {
            $cartDrugs[] = $cartProduct['generic_name'];
        } elseif ($cartProduct) {
            $cartDrugs[] = $cartProduct['name'];
        }
    }
    
    // Check interactions
    $interactions = checkDrugInteractions($db, $product, $cartDrugs, $userMedications);
    
    // Determine if product can be added
    $canAdd = true;
    $requiresAcknowledgment = false;
    $requiresConsultation = false;
    
    foreach ($interactions as $interaction) {
        if ($interaction['severity'] === 'severe' || $interaction['severity'] === 'contraindicated') {
            $canAdd = false;
            $requiresConsultation = true;
        } elseif ($interaction['severity'] === 'moderate') {
            $requiresAcknowledgment = true;
        }
    }
    
    jsonResponse(true, 'Interaction check completed', [
        'product' => [
            'id' => $product['id'],
            'name' => $product['name'],
            'generic_name' => $product['generic_name'] ?? null
        ],
        'interactions' => $interactions,
        'can_add' => $canAdd,
        'requires_acknowledgment' => $requiresAcknowledgment,
        'requires_consultation' => $requiresConsultation,
        'interaction_count' => count($interactions),
        'severity_summary' => getSeveritySummary($interactions)
    ]);
}

/**
 * Check interactions for entire cart
 * 
 * @param PDO $db Database connection
 */
function handleCheckCartInteractions($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $input = $_POST;
    }
    
    $cartItems = $input['cart_items'] ?? [];
    $lineUserId = $input['line_user_id'] ?? null;
    $lineAccountId = $input['line_account_id'] ?? null;
    
    if (empty($cartItems)) {
        jsonResponse(true, 'No items in cart', [
            'interactions' => [],
            'can_checkout' => true
        ]);
        return;
    }
    
    // Get user medications
    $userMedications = [];
    if ($lineUserId) {
        $userMedications = getUserMedicationHistory($db, $lineUserId, $lineAccountId);
    }
    
    // Get all products in cart
    $cartProducts = [];
    foreach ($cartItems as $item) {
        $product = getProductDetails($db, $item['product_id'] ?? $item['id'] ?? 0);
        if ($product) {
            $cartProducts[] = $product;
        }
    }
    
    // Check interactions between all cart items
    $allInteractions = [];
    $checkedPairs = [];
    
    for ($i = 0; $i < count($cartProducts); $i++) {
        for ($j = $i + 1; $j < count($cartProducts); $j++) {
            $pairKey = min($cartProducts[$i]['id'], $cartProducts[$j]['id']) . '-' . 
                       max($cartProducts[$i]['id'], $cartProducts[$j]['id']);
            
            if (!isset($checkedPairs[$pairKey])) {
                $interactions = checkDrugInteractions(
                    $db, 
                    $cartProducts[$i], 
                    [$cartProducts[$j]['generic_name'] ?? $cartProducts[$j]['name']], 
                    []
                );
                $allInteractions = array_merge($allInteractions, $interactions);
                $checkedPairs[$pairKey] = true;
            }
        }
        
        // Check against user medications
        if (!empty($userMedications)) {
            $interactions = checkDrugInteractions($db, $cartProducts[$i], [], $userMedications);
            $allInteractions = array_merge($allInteractions, $interactions);
        }
    }
    
    // Deduplicate interactions
    $allInteractions = deduplicateInteractions($allInteractions);
    
    // Determine checkout eligibility
    $canCheckout = true;
    $unacknowledgedModerate = [];
    
    foreach ($allInteractions as $interaction) {
        if ($interaction['severity'] === 'severe' || $interaction['severity'] === 'contraindicated') {
            $canCheckout = false;
        } elseif ($interaction['severity'] === 'moderate' && empty($interaction['acknowledged'])) {
            $unacknowledgedModerate[] = $interaction;
        }
    }
    
    jsonResponse(true, 'Cart interaction check completed', [
        'interactions' => $allInteractions,
        'can_checkout' => $canCheckout,
        'unacknowledged_moderate' => $unacknowledgedModerate,
        'severity_summary' => getSeveritySummary($allInteractions)
    ]);
}

/**
 * List all drug interactions (for admin)
 * 
 * @param PDO $db Database connection
 */
function handleListInteractions($db) {
    $stmt = $db->query("SELECT * FROM drug_interactions ORDER BY severity DESC, drug1_name ASC");
    $interactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    jsonResponse(true, 'Interactions retrieved', [
        'interactions' => $interactions,
        'count' => count($interactions)
    ]);
}

/**
 * Get user's medication history
 * 
 * @param PDO $db Database connection
 */
function handleGetUserMedications($db) {
    $lineUserId = $_GET['line_user_id'] ?? null;
    $lineAccountId = $_GET['line_account_id'] ?? null;
    
    if (!$lineUserId) {
        jsonResponse(false, 'User ID is required', null, 400);
        return;
    }
    
    $medications = getUserMedicationHistory($db, $lineUserId, $lineAccountId);
    
    jsonResponse(true, 'Medications retrieved', [
        'medications' => $medications
    ]);
}

/**
 * Get product details by ID
 * 
 * @param PDO $db Database connection
 * @param int $productId Product ID
 * @return array|null Product details or null if not found
 */
function getProductDetails($db, $productId) {
    // Check if is_prescription column exists
    try {
        $stmt = $db->prepare("
            SELECT id, name, sku, generic_name, 
                   CASE WHEN EXISTS(SELECT 1 FROM information_schema.columns WHERE table_name = 'products' AND column_name = 'is_prescription') 
                        THEN is_prescription ELSE 0 END as is_prescription,
                   category_id
            FROM products 
            WHERE id = ?
        ");
        $stmt->execute([$productId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Exception $e) {
        // Fallback without is_prescription
        $stmt = $db->prepare("
            SELECT id, name, sku, generic_name, 0 as is_prescription, category_id
            FROM products 
            WHERE id = ?
        ");
        $stmt->execute([$productId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}

/**
 * Get user's medication history from health profile
 * 
 * @param PDO $db Database connection
 * @param string $lineUserId LINE user ID
 * @param int|null $lineAccountId LINE account ID
 * @return array List of medication names
 */
function getUserMedicationHistory($db, $lineUserId, $lineAccountId = null) {
    $medications = [];
    
    try {
        // Get user ID
        $userQuery = "SELECT id FROM users WHERE line_user_id = ?";
        $params = [$lineUserId];
        
        if ($lineAccountId) {
            $userQuery .= " AND line_account_id = ?";
            $params[] = $lineAccountId;
        }
        
        $stmt = $db->prepare($userQuery);
        $stmt->execute($params);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return [];
        }
        
        // Check if health_profiles table exists and get medications
        $stmt = $db->prepare("
            SELECT current_medications 
            FROM health_profiles 
            WHERE user_id = ?
        ");
        $stmt->execute([$user['id']]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($profile && !empty($profile['current_medications'])) {
            $meds = json_decode($profile['current_medications'], true);
            if (is_array($meds)) {
                foreach ($meds as $med) {
                    if (is_string($med)) {
                        $medications[] = $med;
                    } elseif (is_array($med) && isset($med['name'])) {
                        $medications[] = $med['name'];
                    }
                }
            }
        }
    } catch (Exception $e) {
        // Table might not exist, return empty array
        error_log("Error getting user medications: " . $e->getMessage());
    }
    
    return $medications;
}

/**
 * Check drug interactions
 * Requirements: 12.1 - Check for interactions with existing cart items and user medication history
 * 
 * @param PDO $db Database connection
 * @param array $product Product being added
 * @param array $cartDrugs Drug names from cart
 * @param array $userMedications User's current medications
 * @return array List of interactions found
 */
function checkDrugInteractions($db, $product, $cartDrugs, $userMedications) {
    $interactions = [];
    $productGeneric = strtolower($product['generic_name'] ?? $product['name'] ?? '');
    $productName = $product['name'] ?? '';
    
    if (empty($productGeneric)) {
        return [];
    }
    
    // Combine cart drugs and user medications
    $allDrugsToCheck = array_merge($cartDrugs, $userMedications);
    $allDrugsToCheck = array_unique(array_filter($allDrugsToCheck));
    
    foreach ($allDrugsToCheck as $drug) {
        $drugLower = strtolower($drug);
        
        // Check database for interactions
        $stmt = $db->prepare("
            SELECT * FROM drug_interactions 
            WHERE (LOWER(drug1_generic) LIKE ? AND LOWER(drug2_generic) LIKE ?)
               OR (LOWER(drug2_generic) LIKE ? AND LOWER(drug1_generic) LIKE ?)
               OR (LOWER(drug1_name) LIKE ? AND LOWER(drug2_name) LIKE ?)
               OR (LOWER(drug2_name) LIKE ? AND LOWER(drug1_name) LIKE ?)
        ");
        
        $stmt->execute([
            "%{$productGeneric}%", "%{$drugLower}%",
            "%{$productGeneric}%", "%{$drugLower}%",
            "%{$productGeneric}%", "%{$drugLower}%",
            "%{$productGeneric}%", "%{$drugLower}%"
        ]);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $interactions[] = [
                'id' => $row['id'],
                'drug1' => $productName,
                'drug1_generic' => $productGeneric,
                'drug2' => $drug,
                'drug2_generic' => $drugLower,
                'severity' => $row['severity'],
                'description' => $row['description'],
                'recommendation' => $row['recommendation'],
                'source' => in_array($drug, $userMedications) ? 'user_medication' : 'cart'
            ];
        }
    }
    
    // Also check built-in interactions (fallback)
    $builtInInteractions = checkBuiltInInteractions($productGeneric, $allDrugsToCheck, $userMedications);
    $interactions = array_merge($interactions, $builtInInteractions);
    
    return deduplicateInteractions($interactions);
}

/**
 * Check built-in interaction database
 * 
 * @param string $productGeneric Product generic name
 * @param array $drugsToCheck Drugs to check against
 * @param array $userMedications User's medications (for source tracking)
 * @return array List of interactions
 */
function checkBuiltInInteractions($productGeneric, $drugsToCheck, $userMedications) {
    $interactions = [];
    
    // Built-in interaction database
    $builtInDb = [
        ['drug1' => 'warfarin', 'drug2' => 'aspirin', 'severity' => 'severe', 
         'description' => 'เพิ่มความเสี่ยงเลือดออก', 'recommendation' => 'หลีกเลี่ยงการใช้ร่วมกัน'],
        ['drug1' => 'warfarin', 'drug2' => 'ibuprofen', 'severity' => 'severe',
         'description' => 'เพิ่มความเสี่ยงเลือดออกในทางเดินอาหาร', 'recommendation' => 'ใช้ paracetamol แทน'],
        ['drug1' => 'metformin', 'drug2' => 'alcohol', 'severity' => 'severe',
         'description' => 'เพิ่มความเสี่ยง lactic acidosis', 'recommendation' => 'งดแอลกอฮอล์'],
        ['drug1' => 'simvastatin', 'drug2' => 'grapefruit', 'severity' => 'moderate',
         'description' => 'เพิ่มระดับยาในเลือด เสี่ยงกล้ามเนื้อสลาย', 'recommendation' => 'หลีกเลี่ยงเกรปฟรุต'],
        ['drug1' => 'ciprofloxacin', 'drug2' => 'antacid', 'severity' => 'moderate',
         'description' => 'ลดการดูดซึมยาปฏิชีวนะ', 'recommendation' => 'ทานห่างกัน 2 ชั่วโมง'],
        ['drug1' => 'metronidazole', 'drug2' => 'alcohol', 'severity' => 'severe',
         'description' => 'ทำให้คลื่นไส้ อาเจียน ปวดหัวรุนแรง', 'recommendation' => 'งดแอลกอฮอล์ระหว่างใช้ยา'],
        ['drug1' => 'levothyroxine', 'drug2' => 'calcium', 'severity' => 'moderate',
         'description' => 'ลดการดูดซึมยาไทรอยด์', 'recommendation' => 'ทานห่างกัน 4 ชั่วโมง'],
        ['drug1' => 'tramadol', 'drug2' => 'ssri', 'severity' => 'severe',
         'description' => 'เพิ่มความเสี่ยง serotonin syndrome', 'recommendation' => 'หลีกเลี่ยงการใช้ร่วมกัน'],
        ['drug1' => 'paracetamol', 'drug2' => 'alcohol', 'severity' => 'moderate',
         'description' => 'เพิ่มความเสี่ยงตับเสียหาย', 'recommendation' => 'จำกัดแอลกอฮอล์'],
        ['drug1' => 'ibuprofen', 'drug2' => 'aspirin', 'severity' => 'moderate',
         'description' => 'ลดฤทธิ์ป้องกันหัวใจของ aspirin', 'recommendation' => 'ทาน aspirin ก่อน 30 นาที'],
    ];
    
    foreach ($drugsToCheck as $drug) {
        $drugLower = strtolower($drug);
        
        foreach ($builtInDb as $entry) {
            $match = false;
            
            if ((strpos($productGeneric, $entry['drug1']) !== false && strpos($drugLower, $entry['drug2']) !== false) ||
                (strpos($productGeneric, $entry['drug2']) !== false && strpos($drugLower, $entry['drug1']) !== false)) {
                $match = true;
            }
            
            if ($match) {
                $interactions[] = [
                    'id' => 'builtin_' . md5($entry['drug1'] . $entry['drug2']),
                    'drug1' => $productGeneric,
                    'drug1_generic' => $productGeneric,
                    'drug2' => $drug,
                    'drug2_generic' => $drugLower,
                    'severity' => $entry['severity'],
                    'description' => $entry['description'],
                    'recommendation' => $entry['recommendation'],
                    'source' => in_array($drug, $userMedications) ? 'user_medication' : 'cart'
                ];
            }
        }
    }
    
    return $interactions;
}

/**
 * Deduplicate interactions
 * 
 * @param array $interactions List of interactions
 * @return array Deduplicated list
 */
function deduplicateInteractions($interactions) {
    $unique = [];
    $seen = [];
    
    foreach ($interactions as $interaction) {
        $key = strtolower($interaction['drug1_generic'] ?? $interaction['drug1']) . '|' . 
               strtolower($interaction['drug2_generic'] ?? $interaction['drug2']);
        $keyReverse = strtolower($interaction['drug2_generic'] ?? $interaction['drug2']) . '|' . 
                      strtolower($interaction['drug1_generic'] ?? $interaction['drug1']);
        
        if (!isset($seen[$key]) && !isset($seen[$keyReverse])) {
            $unique[] = $interaction;
            $seen[$key] = true;
        }
    }
    
    return $unique;
}

/**
 * Get severity summary
 * 
 * @param array $interactions List of interactions
 * @return array Summary by severity
 */
function getSeveritySummary($interactions) {
    $summary = [
        'contraindicated' => 0,
        'severe' => 0,
        'moderate' => 0,
        'mild' => 0
    ];
    
    foreach ($interactions as $interaction) {
        $severity = $interaction['severity'] ?? 'mild';
        if (isset($summary[$severity])) {
            $summary[$severity]++;
        }
    }
    
    return $summary;
}

/**
 * Send JSON response
 * 
 * @param bool $success Success status
 * @param string $message Response message
 * @param mixed $data Response data
 * @param int $statusCode HTTP status code
 */
function jsonResponse($success, $message, $data = null, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
