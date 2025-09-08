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
    <title>ทดสอบปุ่มตะกร้าสินค้า - ZapShop</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            background: #f8f9fa;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        .test-section {
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
        .quantity-btn:active {
            background: #dee2e6;
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
        .remove-btn:active {
            background: #bd2130;
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
        .test-result {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 5px;
            padding: 15px;
            margin: 10px 0;
        }
        .error-log {
            background: #ffebee;
            border: 1px solid #ffcdd2;
            border-radius: 5px;
            padding: 15px;
            margin: 10px 0;
            color: #c62828;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="test-section">
            <h2>🧪 ทดสอบปุ่มตะกร้าสินค้า</h2>
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
            <div class="test-section">
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
                            <button class="quantity-btn" onclick="testUpdateQuantity(<?php echo $product['id']; ?>, -1)" title="ลดจำนวน">
                                <i class="fas fa-minus"></i>
                            </button>
                            <input type="number" class="quantity-input" value="<?php echo $_SESSION['cart'][$product['id']]; ?>" 
                                   min="1" max="<?php echo $product['current_stock']; ?>" 
                                   onchange="testUpdateQuantity(<?php echo $product['id']; ?>, this.value - <?php echo $_SESSION['cart'][$product['id']]; ?>)">
                            <button class="quantity-btn" onclick="testUpdateQuantity(<?php echo $product['id']; ?>, 1)" title="เพิ่มจำนวน">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <div>฿<?php echo number_format($product['price'] * $_SESSION['cart'][$product['id']], 2); ?></div>
                        <div>
                            <button class="remove-btn" onclick="testRemoveItem(<?php echo $product['id']; ?>)" title="ลบสินค้า">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="test-section">
                <div class="alert alert-warning">
                    <strong>ไม่มีสินค้าในตะกร้า</strong><br>
                    กรุณาเพิ่มสินค้าลงในตะกร้าก่อน
                </div>
            </div>
        <?php endif; ?>

        <div class="test-section">
            <h3>🔧 ทดสอบฟังก์ชัน JavaScript</h3>
            <p>ทดสอบการทำงานของฟังก์ชัน updateQuantity และ removeItem</p>
            
            <button class="btn btn-primary" onclick="testJavaScriptFunctions()">🧪 ทดสอบฟังก์ชัน JavaScript</button>
            <button class="btn btn-warning" onclick="testFetchAPI()">🧪 ทดสอบ Fetch API</button>
            <button class="btn btn-success" onclick="location.reload()">🔄 รีเฟรชหน้า</button>
        </div>

        <div class="test-section">
            <h3>📊 ผลการทดสอบ</h3>
            <div id="test-results">
                <div class="test-result">
                    <strong>เริ่มต้นการทดสอบ...</strong><br>
                    กดปุ่มทดสอบด้านบนเพื่อเริ่มการทดสอบ
                </div>
            </div>
        </div>

        <div class="test-section">
            <h3>📋 Console Log</h3>
            <div id="console-log" class="debug-info" style="max-height: 300px; overflow-y: auto;">
                <div>Console log จะแสดงที่นี่...</div>
            </div>
        </div>

        <div class="test-section">
            <h3>⚠️ Error Log</h3>
            <div id="error-log" class="error-log" style="max-height: 200px; overflow-y: auto;">
                <div>Error log จะแสดงที่นี่...</div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
    // ฟังก์ชันสำหรับแสดงผลการทดสอบ
    function showTestResult(message, type = 'info') {
        const testResults = document.getElementById('test-results');
        const resultDiv = document.createElement('div');
        resultDiv.className = 'test-result';
        resultDiv.innerHTML = `<strong>[${new Date().toLocaleTimeString()}]</strong> ${message}`;
        testResults.appendChild(resultDiv);
        testResults.scrollTop = testResults.scrollHeight;
    }

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

    // ฟังก์ชันสำหรับแสดง error
    function showError(message) {
        const errorLog = document.getElementById('error-log');
        const timestamp = new Date().toLocaleTimeString();
        const errorEntry = document.createElement('div');
        errorEntry.innerHTML = `[${timestamp}] ${message}`;
        errorLog.appendChild(errorEntry);
        errorLog.scrollTop = errorLog.scrollHeight;
        
        // แสดงใน browser console ด้วย
        console.error(`[${timestamp}] ${message}`);
    }

    // ฟังก์ชันทดสอบ JavaScript
    function testJavaScriptFunctions() {
        showTestResult('🧪 เริ่มทดสอบฟังก์ชัน JavaScript...');
        
        try {
            // ทดสอบว่าฟังก์ชันมีอยู่หรือไม่
            if (typeof updateQuantity === 'function') {
                showTestResult('✅ ฟังก์ชัน updateQuantity พบแล้ว');
            } else {
                showTestResult('❌ ฟังก์ชัน updateQuantity ไม่พบ');
                showError('ฟังก์ชัน updateQuantity ไม่ได้ถูกประกาศ');
            }
            
            if (typeof removeItem === 'function') {
                showTestResult('✅ ฟังก์ชัน removeItem พบแล้ว');
            } else {
                showTestResult('❌ ฟังก์ชัน removeItem ไม่พบ');
                showError('ฟังก์ชัน removeItem ไม่ได้ถูกประกาศ');
            }
            
            // ทดสอบการคำนวณ
            const testCart = <?php echo json_encode($_SESSION['cart'] ?? []); ?>;
            showTestResult(`📊 ข้อมูลตะกร้า: ${JSON.stringify(testCart)}`);
            
        } catch (error) {
            showTestResult(`❌ เกิดข้อผิดพลาด: ${error.message}`);
            showError(`JavaScript Error: ${error.message}`);
        }
    }

    // ฟังก์ชันทดสอบ Fetch API
    function testFetchAPI() {
        showTestResult('🧪 เริ่มทดสอบ Fetch API...');
        
        // ทดสอบ cart-update.php
        fetch('cart-update.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=1&quantity=3'
        })
        .then(response => {
            showTestResult(`📡 cart-update.php Response Status: ${response.status}`);
            return response.json();
        })
        .then(data => {
            showTestResult(`📊 cart-update.php Result: ${JSON.stringify(data)}`);
        })
        .catch(error => {
            showTestResult(`❌ cart-update.php Error: ${error.message}`);
            showError(`Fetch Error (cart-update.php): ${error.message}`);
        });
        
        // ทดสอบ cart-delete.php
        fetch('cart-delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=999'
        })
        .then(response => {
            showTestResult(`📡 cart-delete.php Response Status: ${response.status}`);
            return response.json();
        })
        .then(data => {
            showTestResult(`📊 cart-delete.php Result: ${JSON.stringify(data)}`);
        })
        .catch(error => {
            showTestResult(`❌ cart-delete.php Error: ${error.message}`);
            showError(`Fetch Error (cart-delete.php): ${error.message}`);
        });
    }

    // ฟังก์ชันทดสอบอัปเดตจำนวนสินค้า
    function testUpdateQuantity(productId, change) {
        const currentQty = <?php echo json_encode($_SESSION['cart'] ?? []); ?>[productId] || 0;
        const newQty = Math.max(1, currentQty + change);
        
        showTestResult(`🔄 ทดสอบอัปเดตจำนวนสินค้า ID: ${productId}, เปลี่ยนจาก ${currentQty} เป็น ${newQty}`);
        logToPage(`🔄 อัปเดตจำนวนสินค้า ID: ${productId}, เปลี่ยนจาก ${currentQty} เป็น ${newQty}`);
        
        try {
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
                    showTestResult(`✅ อัปเดตสำเร็จ: ${data.message}`);
                    logToPage(`✅ อัปเดตสำเร็จ: ${data.message}`, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showTestResult(`❌ อัปเดตล้มเหลว: ${data.message}`);
                    logToPage(`❌ อัปเดตล้มเหลว: ${data.message}`, 'error');
                }
            })
            .catch(error => {
                showTestResult(`💥 Error: ${error.message}`);
                logToPage(`💥 Error: ${error.message}`, 'error');
                showError(`Fetch Error: ${error.message}`);
            });
        } catch (error) {
            showTestResult(`💥 JavaScript Error: ${error.message}`);
            showError(`JavaScript Error: ${error.message}`);
        }
    }

    // ฟังก์ชันทดสอบลบสินค้า
    function testRemoveItem(productId) {
        if (confirm('คุณต้องการลบสินค้านี้ออกจากตะกร้าหรือไม่?')) {
            showTestResult(`🗑️ ทดสอบลบสินค้า ID: ${productId}`);
            logToPage(`🗑️ ลบสินค้า ID: ${productId}`);
            
            try {
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
                        showTestResult(`✅ ลบสำเร็จ: ${data.message}`);
                        logToPage(`✅ ลบสำเร็จ: ${data.message}`, 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showTestResult(`❌ ลบล้มเหลว: ${data.message}`);
                        logToPage(`❌ ลบล้มเหลว: ${data.message}`, 'error');
                    }
                })
                .catch(error => {
                    showTestResult(`💥 Error: ${error.message}`);
                    logToPage(`💥 Error: ${error.message}`, 'error');
                    showError(`Fetch Error: ${error.message}`);
                });
            } catch (error) {
                showTestResult(`💥 JavaScript Error: ${error.message}`);
                showError(`JavaScript Error: ${error.message}`);
            }
        }
    }

    // ฟังก์ชันที่ใช้จริงในหน้าตะกร้าสินค้า
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
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'เกิดข้อผิดพลาด');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('เกิดข้อผิดพลาดในการอัปเดต');
        });
    }

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
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'เกิดข้อผิดพลาด');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาดในการลบ');
            });
        }
    }

    // แสดงข้อความเริ่มต้น
    showTestResult('🚀 เริ่มต้นการทดสอบปุ่มตะกร้าสินค้า', 'success');
    logToPage('🚀 เริ่มต้นการทดสอบปุ่มตะกร้าสินค้า', 'success');
    logToPage('📱 เปิด Developer Tools (F12) เพื่อดู Console Log');
    
    // ทดสอบว่าฟังก์ชันทำงานได้หรือไม่
    document.addEventListener('DOMContentLoaded', function() {
        showTestResult('✅ หน้าเว็บโหลดเสร็จแล้ว');
        
        // ทดสอบการคลิกปุ่ม
        const quantityBtns = document.querySelectorAll('.quantity-btn');
        const removeBtns = document.querySelectorAll('.remove-btn');
        
        showTestResult(`🔍 พบปุ่ม +/- จำนวน ${quantityBtns.length} ปุ่ม`);
        showTestResult(`🔍 พบปุ่มลบจำนวน ${removeBtns.length} ปุ่ม`);
        
        // เพิ่ม event listener สำหรับการทดสอบ
        quantityBtns.forEach((btn, index) => {
            btn.addEventListener('click', function() {
                showTestResult(`🖱️ ปุ่ม +/- ถูกคลิก (ปุ่มที่ ${index + 1})`);
            });
        });
        
        removeBtns.forEach((btn, index) => {
            btn.addEventListener('click', function() {
                showTestResult(`🖱️ ปุ่มลบถูกคลิก (ปุ่มที่ ${index + 1})`);
            });
        });
    });
    </script>
</body>
</html>
