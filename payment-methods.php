<?php
session_start();
require_once 'config.php';

// ตรวจสอบว่าผู้ใช้ล็อกอินแล้วหรือไม่
if (!isset($_SESSION['user_id'])) {
    header('Location: user-login.php');
    exit();
}

// ตรวจสอบว่ามีสินค้าในตะกร้าหรือไม่
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit();
}

$conn = getConnection();
$user_id = $_SESSION['user_id'];
$totalAmount = 0;
$cartItems = [];

// คำนวณยอดรวมและดึงข้อมูลสินค้า
foreach ($_SESSION['cart'] as $product_id => $quantity) {
    $query = "SELECT id, name, price, current_stock FROM products WHERE id = $1";
    $result = pg_query_params($conn, $query, [$product_id]);
    
    if ($result && pg_num_rows($result) > 0) {
        $product = pg_fetch_assoc($result);
        $itemTotal = $product['price'] * $quantity;
        $totalAmount += $itemTotal;
        
        $cartItems[] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'quantity' => $quantity,
            'total' => $itemTotal,
            'stock' => $product['current_stock']
        ];
    }
}

// คำนวณค่าจัดส่ง
$shippingCost = 38; // ค่าจัดส่งคงที่
$grandTotal = $totalAmount + $shippingCost;

pg_close($conn);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เลือกวิธีการชำระเงิน - ZapShop</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --makro-red: #e31837;
            --makro-red-dark: #c41230;
            --makro-orange: #ff6b35;
            --makro-yellow: #ffd23f;
            --makro-blue: #0066cc;
            --makro-green: #28a745;
            --makro-gray: #6c757d;
            --makro-light-gray: #f8f9fa;
            --makro-border: #e9ecef;
            --makro-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            --makro-shadow-hover: 0 4px 16px rgba(0, 0, 0, 0.15);
            --makro-radius: 12px;
            --makro-radius-sm: 8px;
        }

        body {
            font-family: 'Kanit', sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            color: #2c3e50;
        }

        .page-header {
            background: linear-gradient(135deg, var(--makro-red) 0%, var(--makro-red-dark) 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .page-subtitle {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 0;
        }

        .payment-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .payment-methods-section {
            background: white;
            border-radius: var(--makro-radius);
            box-shadow: var(--makro-shadow);
            padding: 30px;
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--makro-red);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .payment-methods-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .payment-method {
            position: relative;
            border: 2px solid var(--makro-border);
            border-radius: var(--makro-radius-sm);
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }

        .payment-method:hover {
            border-color: var(--makro-red);
            transform: translateY(-2px);
            box-shadow: var(--makro-shadow-hover);
        }

        .payment-method.selected {
            border-color: var(--makro-red);
            background: linear-gradient(135deg, #fff5f5 0%, #ffeaea 100%);
        }

        .payment-method.selected::after {
            content: '✓';
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--makro-red);
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: bold;
        }

        .payment-icon {
            font-size: 2rem;
            margin-bottom: 10px;
            color: var(--makro-red);
        }

        .payment-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .payment-description {
            font-size: 0.85rem;
            color: var(--makro-gray);
        }

        .selected-method-display {
            background: var(--makro-light-gray);
            border-radius: var(--makro-radius-sm);
            padding: 20px;
            margin-top: 20px;
            display: none;
        }

        .selected-method-display.show {
            display: block;
        }

        .selected-method-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .selected-method-icon {
            font-size: 1.5rem;
            color: var(--makro-red);
        }

        .selected-method-text {
            font-weight: 600;
            color: #2c3e50;
        }

        .order-summary-section {
            background: white;
            border-radius: var(--makro-radius);
            box-shadow: var(--makro-shadow);
            padding: 30px;
            position: sticky;
            top: 20px;
        }

        .summary-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--makro-red);
            margin-bottom: 20px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid var(--makro-border);
        }

        .summary-item:last-child {
            border-bottom: none;
        }

        .summary-label {
            color: var(--makro-gray);
        }

        .summary-value {
            font-weight: 600;
            color: #2c3e50;
        }

        .total-amount {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--makro-red);
        }

        .terms-section {
            background: white;
            border-radius: var(--makro-radius);
            box-shadow: var(--makro-shadow);
            padding: 20px;
            margin-bottom: 20px;
        }

        .terms-text {
            font-size: 0.9rem;
            color: var(--makro-gray);
            line-height: 1.5;
        }

        .terms-link {
            color: var(--makro-blue);
            text-decoration: none;
        }

        .terms-link:hover {
            text-decoration: underline;
        }

        .checkout-button {
            background: linear-gradient(135deg, var(--makro-orange) 0%, #ff8c42 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: var(--makro-radius-sm);
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: var(--makro-shadow);
            width: 100%;
        }

        .checkout-button:hover {
            background: linear-gradient(135deg, #ff8c42 0%, var(--makro-orange) 100%);
            transform: translateY(-2px);
            box-shadow: var(--makro-shadow-hover);
        }

        .checkout-button:disabled {
            background: var(--makro-gray);
            cursor: not-allowed;
            transform: none;
        }

        @media (max-width: 768px) {
            .payment-methods-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 10px;
            }
            
            .payment-method {
                padding: 15px;
            }
            
            .checkout-button {
                width: 100%;
                padding: 15px;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>

<?php include 'include/menu.php'; ?>

<div class="page-header">
    <div class="container">
        <div class="text-center">
            <h1 class="page-title">
                <i class="fas fa-credit-card"></i> วิธีการชำระเงิน
            </h1>
            <p class="page-subtitle">เลือกวิธีการชำระเงินที่สะดวกสำหรับคุณ</p>
        </div>
    </div>
</div>

<div class="payment-container">
    <div class="row">
        <div class="col-lg-8">
            <div class="payment-methods-section">
                <h2 class="section-title">
                    <i class="fas fa-wallet"></i> วิธีการชำระเงิน
                </h2>
                
                <div class="payment-methods-grid">
                    <div class="payment-method" data-method="promptpay">
                        <div class="payment-icon">
                            <i class="fas fa-qrcode"></i>
                        </div>
                        <div class="payment-name">QR พร้อมเพย์</div>
                        <div class="payment-description">ชำระผ่าน QR Code</div>
                    </div>
                    
                    <div class="payment-method" data-method="credit_card">
                        <div class="payment-icon">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <div class="payment-name">บัตรเครดิต/บัตรเดบิต</div>
                        <div class="payment-description">Visa, Mastercard</div>
                    </div>
                    
                    <div class="payment-method" data-method="bank_transfer">
                        <div class="payment-icon">
                            <i class="fas fa-university"></i>
                        </div>
                        <div class="payment-name">โอนเงินผ่านธนาคาร</div>
                        <div class="payment-description">Internet Banking</div>
                    </div>
                    
                    <div class="payment-method" data-method="mobile_banking">
                        <div class="payment-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <div class="payment-name">Mobile Banking</div>
                        <div class="payment-description">K Plus, SCB Easy</div>
                    </div>
                    
                    <div class="payment-method" data-method="cash_on_delivery">
                        <div class="payment-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="payment-name">เก็บเงินปลายทาง</div>
                        <div class="payment-description">ชำระเมื่อได้รับสินค้า</div>
                    </div>
                    
                    <div class="payment-method" data-method="digital_wallet">
                        <div class="payment-icon">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div class="payment-name">ดิจิทัลวอลเล็ตร้านค้า</div>
                        <div class="payment-description">ไม่สามารถคืนเงิน</div>
                    </div>
                </div>
                
                <div class="selected-method-display" id="selectedMethodDisplay">
                    <div class="selected-method-info">
                        <div class="selected-method-icon" id="selectedMethodIcon">
                            <i class="fas fa-qrcode"></i>
                        </div>
                        <div class="selected-method-text" id="selectedMethodText">
                            QR พร้อมเพย์
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="terms-section">
                <p class="terms-text">
                    โดยการคลิก "สั่งสินค้า" ฉันได้อ่านและยอมรับเงื่อนไขการให้บริการ ZapShop 
                    <a href="#" class="terms-link">นโยบายการคืนเงินและคืนสินค้า</a>
                </p>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="order-summary-section">
                <h3 class="summary-title">สรุปการสั่งซื้อ</h3>
                
                <div class="summary-item">
                    <span class="summary-label">รวมการสั่งซื้อ</span>
                    <span class="summary-value">฿<?php echo number_format($totalAmount, 2); ?></span>
                </div>
                
                <div class="summary-item">
                    <span class="summary-label">การจัดส่ง</span>
                    <span class="summary-value">฿<?php echo number_format($shippingCost, 2); ?></span>
                </div>
                
                <div class="summary-item">
                    <span class="summary-label">ยอดชำระเงินทั้งหมด</span>
                    <span class="summary-value total-amount">฿<?php echo number_format($grandTotal, 2); ?></span>
                </div>
                
                <div class="summary-item" style="margin-top: 20px; padding-top: 20px; border-top: 2px solid var(--makro-border);">
                    <button class="checkout-button" id="checkoutButton" disabled style="width: 100%; position: static; margin: 0; padding: 15px; font-size: 1.1rem;">
                        <i class="fas fa-shopping-cart"></i> สั่งสินค้า
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const paymentMethods = document.querySelectorAll('.payment-method');
    const selectedMethodDisplay = document.getElementById('selectedMethodDisplay');
    const selectedMethodIcon = document.getElementById('selectedMethodIcon');
    const selectedMethodText = document.getElementById('selectedMethodText');
    const checkoutButton = document.getElementById('checkoutButton');
    
    let selectedMethod = null;
    
    // เพิ่ม event listener สำหรับแต่ละ payment method
    paymentMethods.forEach(method => {
        method.addEventListener('click', function() {
            // ลบ selected class จากทุก method
            paymentMethods.forEach(m => m.classList.remove('selected'));
            
            // เพิ่ม selected class ให้ method ที่ถูกเลือก
            this.classList.add('selected');
            
            // เก็บข้อมูล method ที่เลือก
            selectedMethod = this.dataset.method;
            
            // แสดงข้อมูล method ที่เลือก
            const methodName = this.querySelector('.payment-name').textContent;
            const methodIcon = this.querySelector('.payment-icon i').className;
            
            selectedMethodIcon.className = methodIcon;
            selectedMethodText.textContent = methodName;
            selectedMethodDisplay.classList.add('show');
            
            // เปิดใช้งานปุ่มสั่งสินค้า
            checkoutButton.disabled = false;
        });
    });
    
    // Event listener สำหรับปุ่มสั่งสินค้า
    checkoutButton.addEventListener('click', function() {
        if (!selectedMethod) {
            alert('กรุณาเลือกวิธีการชำระเงิน');
            return;
        }
        
        // ส่งข้อมูลไปหน้า checkout
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'checkout.php';
        
        const paymentMethodInput = document.createElement('input');
        paymentMethodInput.type = 'hidden';
        paymentMethodInput.name = 'payment_method';
        paymentMethodInput.value = selectedMethod;
        
        const grandTotalInput = document.createElement('input');
        grandTotalInput.type = 'hidden';
        grandTotalInput.name = 'grand_total';
        grandTotalInput.value = <?php echo $grandTotal; ?>;
        
        form.appendChild(paymentMethodInput);
        form.appendChild(grandTotalInput);
        document.body.appendChild(form);
        form.submit();
    });
    
    // เลือก QR พร้อมเพย์ เป็นค่าเริ่มต้น
    const defaultMethod = document.querySelector('[data-method="promptpay"]');
    if (defaultMethod) {
        defaultMethod.click();
    }
});
</script>
</body>
</html>
