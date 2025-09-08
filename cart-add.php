<?php
session_start();
include 'config.php';

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วหรือไม่
if (!isset($_SESSION['user_id'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบก่อนเพิ่มสินค้าลงตะกร้า']);
        exit();
    } else {
        $_SESSION['message'] = "กรุณาเข้าสู่ระบบก่อนเพิ่มสินค้าลงตะกร้า";
        $_SESSION['message_type'] = "error";
        header('Location: user-login.php');
        exit();
    }
}

$product_id = null;
$quantity = 1;

// รับข้อมูลจาก GET หรือ POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['id'] ?? null;
    $quantity = intval($_POST['quantity'] ?? 1);
} else {
    $product_id = $_GET['id'] ?? null;
    $quantity = intval($_GET['quantity'] ?? 1);
}

if (!empty($product_id)) {
    // ตรวจสอบว่าสินค้ามีอยู่จริงและมีสต็อกหรือไม่
    $conn = getConnection();
    $query = "SELECT id, name, price, current_stock FROM products WHERE id = $1";
    $result = pg_query_params($conn, $query, [$product_id]);
    
    if ($result && pg_num_rows($result) > 0) {
        $product = pg_fetch_assoc($result);
        
        // ตรวจสอบสต็อก
        if ($product['current_stock'] <= 0) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'สินค้าหมดแล้ว']);
                exit();
            } else {
                $_SESSION['message'] = "สินค้าหมดแล้ว";
                $_SESSION['message_type'] = "error";
                header('Location: product-list1.php');
                exit();
            }
        }
        
        // ตรวจสอบว่าจำนวนที่ต้องการไม่เกินสต็อก
        if ($quantity > $product['current_stock']) {
            $quantity = $product['current_stock'];
        }
        
        // เพิ่มสินค้าลงตะกร้า
        if (empty($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] = $quantity;
        } else {
            $_SESSION['cart'][$product_id] += $quantity;
            
            // ตรวจสอบว่าจำนวนรวมในตะกร้าไม่เกินสต็อก
            if ($_SESSION['cart'][$product_id] > $product['current_stock']) {
                $_SESSION['cart'][$product_id] = $product['current_stock'];
            }
        }
        
        // สร้างข้อความ
        $message = "เพิ่มสินค้าลงตะกร้าแล้ว";
        if ($_SESSION['cart'][$product_id] >= $product['current_stock']) {
            $message .= " (จำนวนสูงสุดตามสต็อก)";
        }
        
        // บันทึก Activity Log
        logActivity($_SESSION['user_id'], 'cart_add', 'Added product to cart: ' . $product['name'] . ' (Qty: ' . $quantity . ')', 'products', $product_id);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'message' => $message,
                'cart_count' => array_sum($_SESSION['cart'])
            ]);
            exit();
        } else {
            $_SESSION['message'] = $message;
            $_SESSION['message_type'] = "success";
        }
        
    } else {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'ไม่พบสินค้าที่ระบุ']);
            exit();
        } else {
            $_SESSION['message'] = "ไม่พบสินค้าที่ระบุ";
            $_SESSION['message_type'] = "error";
        }
    }
    
    // ไม่ต้องปิดการเชื่อมต่อเพราะ logActivity() ปิดแล้ว
} else {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'รหัสสินค้าไม่ถูกต้อง']);
        exit();
    } else {
        $_SESSION['message'] = "รหัสสินค้าไม่ถูกต้อง";
        $_SESSION['message_type'] = "error";
    }
}

// สำหรับ GET request ให้ redirect ไปยังหน้า cart
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cart.php');
    exit();
}
?>