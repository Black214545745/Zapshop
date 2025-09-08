<?php
session_start();
if (!isset($_SESSION['admin_username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

include 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$product_id = $_POST['product_id'] ?? null;
$quantity = (int)($_POST['quantity'] ?? 0);

// Debug log
error_log("Stock update request - product_id: " . $product_id . ", quantity: " . $quantity);

if (!$product_id || $quantity === 0) {
    echo json_encode(['success' => false, 'message' => 'พารามิเตอร์ไม่ถูกต้อง - product_id: ' . $product_id . ', quantity: ' . $quantity]);
    exit();
}

$conn = getConnection();

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้']);
    exit();
}

// ใช้วิธีง่ายๆ โดยไม่ใช้ transaction
try {
    // ดึงข้อมูลสต็อกปัจจุบัน
    $sql_select = "SELECT current_stock, name FROM products WHERE id = $1";
    $result = pg_query_params($conn, $sql_select, [$product_id]);
    
    if (!$result) {
        throw new Exception('ไม่สามารถดึงข้อมูลสินค้าได้: ' . pg_last_error($conn));
    }
    
    if (pg_num_rows($result) === 0) {
        throw new Exception('ไม่พบสินค้า');
    }
    
    $row = pg_fetch_assoc($result);
    $current_stock = (int)$row['current_stock'];
    $product_name = $row['name'];
    $new_stock = $current_stock + $quantity;
    
    // ตรวจสอบว่าสต็อกใหม่ไม่เป็นลบ
    if ($new_stock < 0) {
        throw new Exception('ไม่สามารถลดสต็อกได้ เนื่องจากจำนวนจะต่ำกว่า 0');
    }
    
    // อัปเดตสต็อก
    $sql_update = "UPDATE products SET current_stock = $1, updated_at = CURRENT_TIMESTAMP WHERE id = $2";
    $result_update = pg_query_params($conn, $sql_update, [$new_stock, $product_id]);
    
    if (!$result_update) {
        throw new Exception('ไม่สามารถอัปเดตสต็อกได้: ' . pg_last_error($conn));
    }
    
    // บันทึก Activity Log (ถ้ามีฟังก์ชัน logActivity)
    try {
        $action_type = $quantity > 0 ? 'stock_add' : 'stock_subtract';
        $description = $quantity > 0 ? 
            "เพิ่มสต็อกสินค้า '{$product_name}' จำนวน " . abs($quantity) . " ชิ้น" :
            "ลดสต็อกสินค้า '{$product_name}' จำนวน " . abs($quantity) . " ชิ้น";
        
        if (function_exists('logActivity')) {
            logActivity($_SESSION['admin_id'] ?? null, $action_type, $description, 'products', $product_id);
        }
    } catch (Exception $log_error) {
        // ไม่ให้ log error ทำให้การอัปเดตสต็อกล้มเหลว
        error_log("Activity log error: " . $log_error->getMessage());
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'อัปเดตสต็อกสำเร็จ',
        'old_stock' => $current_stock,
        'new_stock' => $new_stock,
        'change' => $quantity
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
// ไม่ปิด connection ที่นี่ เพราะอาจถูกปิดโดยระบบอื่นแล้ว
?>
