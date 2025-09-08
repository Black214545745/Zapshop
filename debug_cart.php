<?php
session_start();
include 'config.php';

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วหรือไม่
if (!isset($_SESSION['user_id'])) {
    header("Location: user-login.php");
    exit();
}

// สร้างข้อมูลทดสอบในตะกร้า
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    $_SESSION['cart'] = [
        1 => 2,  // สินค้า ID 1 จำนวน 2 ชิ้น
        2 => 1   // สินค้า ID 2 จำนวน 1 ชิ้น
    ];
}

$productIds = array_keys($_SESSION['cart']);
$products = [];
$total_amount = 0;
$total_items = 0;

if(count($productIds) > 0) {
    $conn = getConnection();
    $placeholders = implode(',', array_map(function($i) { return '$' . ($i + 1); }, range(0, count($productIds) - 1)));
    $query = "SELECT id, name, price, image_url, description, current_stock FROM products WHERE id IN ($placeholders)";
    $result = pg_query_params($conn, $query, $productIds);
    
    if ($result) {
        while ($product = pg_fetch_assoc($result)) {
            $products[] = $product;
        }
    }
    pg_close($conn);
}

// คำนวณยอดรวม
foreach ($products as $product) {
    $quantity = $_SESSION['cart'][$product['id']];
    $total_amount += $product['price'] * $quantity;
    $total_items += $quantity;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug ตะกร้าสินค้า - ZapShop</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            background: #f8f9fa;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        .debug-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 5px;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-warning { background: #ffc107; color: black; }
        .btn-danger { background: #dc3545; color: white; }
        .btn:hover { opacity: 0.8; }
        .alert {
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            border-left: 4px solid;
        }
        .alert-success { background: #d4edda; border-color: #28a745; color: #155724; }
        .alert-danger { background: #f8d7da; border-color: #dc3545; color: #721c24; }
        .alert-info { background: #d1ecf1; border-color: #17a2b8; color: #0c5460; }
        .cart-item {
            display: grid;
            grid-template-columns: 70px 1fr 100px 100px 100px 80px;
            gap: 15px;
            align-items: center;
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin: 10px 0;
            background: white;
        }
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .quantity-btn {
            width: 30px;
            height: 30px;
            border: 1px solid #dee2e6;
            background: #f8f9fa;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .quantity-btn:hover {
            background: #e9ecef;
        }
        .quantity-input {
            width: 50px;
            text-align: center;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 5px;
        }
        .remove-btn {
            width: 30px;
            height: 30px;
            border: 1px solid #dc3545;
            background: #dc3545;
            color: white;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .remove-btn:hover {
            background: #c82333;
        }
        .debug-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
            font-family: monospace;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="debug-section">
            <h2>🐛 Debug ตะกร้าสินค้า</h2>
            <p>ไฟล์นี้ใช้สำหรับทดสอบการทำงานของปุ่มในตะกร้าสินค้า</p>
            
            <div class="alert alert-info">
                <strong>ข้อมูล Session:</strong><br>
                User ID: <?php echo $_SESSION['user_id']; ?><br>
                Cart Items: <?php echo count($_SESSION['cart']); ?><br>
                Total Items: <?php echo $total_items; ?><br>
                Total Amount: ฿<?php echo number_format($total_amount, 2); ?>
            </div>
        </div>

        <?php if (count($products) > 0): ?>
            <div class="debug-section">
                <h3>🛒 สินค้าในตะกร้า</h3>
                
                <div class="cart-item" style="background: #e9ecef; font-weight: bold;">
                    <div>รูปภาพ</div>
                    <div>สินค้า</div>
                    <div>ราคา</div>
                    <div>จำนวน</div>
                    <div>ยอดรวม</div>
                    <div>จัดการ</div>
                </div>
                
                <?php foreach ($products as $product): ?>
                    <div class="cart-item">
                        <div>
                            <img src="https://placehold.co/70x70/cccccc/333333?text=Image" alt="Product Image" style="width: 70px; height: 70px; object-fit: cover; border-radius: 4px;">
                        </div>
                        <div>
                            <div style="font-weight: bold;"><?php echo htmlspecialchars($product['name']); ?></div>
                            <div style="font-size: 12px; color: #6c757d;"><?php echo htmlspecialchars($product['description'] ?: 'ไม่มีรายละเอียด'); ?></div>
                        </div>
                        <div>฿<?php echo number_format($product['price'], 2); ?></div>
                        <div class="quantity-controls">
                            <button class="quantity-btn" onclick="updateQuantity(<?php echo $product['id']; ?>, -1)" title="ลดจำนวน">
                                <i class="fas fa-minus"></i>
                            </button>
                            <input type="number" class="quantity-input" value="<?php echo $_SESSION['cart'][$product['id']]; ?>" 
                                   min="1" max="<?php echo $product['current_stock']; ?>" 
                                   onchange="updateQuantity(<?php echo $product['id']; ?>, this.value - <?php echo $_SESSION['cart'][$product['id']]; ?>)">
                            <button class="quantity-btn" onclick="updateQuantity(<?php echo $product['id']; ?>, 1)" title="เพิ่มจำนวน">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <div>฿<?php echo number_format($product['price'] * $_SESSION['cart'][$product['id']], 2); ?></div>
                        <div>
                            <button class="remove-btn" onclick="removeItem(<?php echo $product['id']; ?>)" title="ลบสินค้า">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="debug-section">
                <div class="alert alert-warning">
                    <strong>ไม่มีสินค้าในตะกร้า</strong><br>
                    กรุณาเพิ่มสินค้าลงในตะกร้าก่อน
                </div>
            </div>
        <?php endif; ?>

        <div class="debug-section">
            <h3>🔧 ทดสอบ API</h3>
            <p>ทดสอบการทำงานของ cart-update.php และ cart-delete.php</p>
            
            <div class="debug-info">
                <strong>API Endpoints:</strong><br>
                - cart-update.php: อัปเดตจำนวนสินค้า<br>
                - cart-delete.php: ลบสินค้าออกจากตะกร้า<br>
                <br>
                <strong>Test Data:</strong><br>
                - Product ID 1: จำนวน <?php echo $_SESSION['cart'][1] ?? 0; ?><br>
                - Product ID 2: จำนวน <?php echo $_SESSION['cart'][2] ?? 0; ?><br>
            </div>
            
            <button class="btn btn-primary" onclick="testUpdateAPI()">🧪 ทดสอบ Update API</button>
            <button class="btn btn-warning" onclick="testDeleteAPI()">🧪 ทดสอบ Delete API</button>
            <button class="btn btn-success" onclick="location.reload()">🔄 รีเฟรชหน้า</button>
        </div>

        <div class="debug-section">
            <h3>📊 Console Log</h3>
            <div id="console-log" class="debug-info" style="max-height: 300px; overflow-y: auto;">
                <div>Console log จะแสดงที่นี่...</div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
    // ฟังก์ชันสำหรับแสดง log ในหน้าเว็บ
    function logToPage(message, type = 'info') {
        const consoleLog = document.getElementById('console-log');
        const timestamp = new Date().toLocaleTimeString();
        const logEntry = document.createElement('div');
        logEntry.innerHTML = `[${timestamp}] ${message}`;
        logEntry.style.color = type === 'error' ? '#dc3545' : type === 'success' ? '#28a745' : '#17a2b8';
        consoleLog.appendChild(logEntry);
        consoleLog.scrollTop = consoleLog.scrollHeight;
        
        // แสดงใน browser console ด้วย
        console.log(`[${timestamp}] ${message}`);
    }

    // ฟังก์ชันอัปเดตจำนวนสินค้า
    function updateQuantity(productId, change) {
        const currentQty = <?php echo json_encode($_SESSION['cart'] ?? []); ?>[productId] || 0;
        const newQty = Math.max(1, currentQty + change);
        
        logToPage(`🔄 อัปเดตจำนวนสินค้า ID: ${productId}, เปลี่ยนจาก ${currentQty} เป็น ${newQty}`);
        
        fetch('cart-update.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${productId}&quantity=${newQty}`
        })
        .then(response => {
            logToPage(`📡 Response status: ${response.status}`);
            return response.json();
        })
        .then(data => {
            if (data.success) {
                logToPage(`✅ อัปเดตสำเร็จ: ${data.message}`, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                logToPage(`❌ อัปเดตล้มเหลว: ${data.message}`, 'error');
            }
        })
        .catch(error => {
            logToPage(`💥 Error: ${error.message}`, 'error');
            console.error('Error:', error);
        });
    }

    // ฟังก์ชันลบสินค้า
    function removeItem(productId) {
        if (confirm('คุณต้องการลบสินค้านี้ออกจากตะกร้าหรือไม่?')) {
            logToPage(`🗑️ ลบสินค้า ID: ${productId}`);
            
            fetch('cart-delete.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${productId}`
            })
            .then(response => {
                logToPage(`📡 Response status: ${response.status}`);
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    logToPage(`✅ ลบสำเร็จ: ${data.message}`, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    logToPage(`❌ ลบล้มเหลว: ${data.message}`, 'error');
                }
            })
            .catch(error => {
                logToPage(`💥 Error: ${error.message}`, 'error');
                console.error('Error:', error);
            });
        }
    }

    // ฟังก์ชันทดสอบ API
    function testUpdateAPI() {
        logToPage('🧪 ทดสอบ Update API...');
        
        fetch('cart-update.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=1&quantity=5'
        })
        .then(response => response.json())
        .then(data => {
            logToPage(`📊 Test Result: ${JSON.stringify(data)}`, data.success ? 'success' : 'error');
        })
        .catch(error => {
            logToPage(`💥 Test Error: ${error.message}`, 'error');
        });
    }

    function testDeleteAPI() {
        logToPage('🧪 ทดสอบ Delete API...');
        
        fetch('cart-delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=999'
        })
        .then(response => response.json())
        .then(data => {
            logToPage(`📊 Test Result: ${JSON.stringify(data)}`, data.success ? 'success' : 'error');
        })
        .catch(error => {
            logToPage(`💥 Test Error: ${error.message}`, 'error');
        });
    }

    // แสดงข้อความเริ่มต้น
    logToPage('🚀 เริ่มต้น Debug ตะกร้าสินค้า', 'success');
    logToPage('📱 เปิด Developer Tools (F12) เพื่อดู Console Log');
    </script>
</body>
</html>
