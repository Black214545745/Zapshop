<?php
session_start();
require_once 'config.php';

// ตรวจสอบว่าผู้ใช้ล็อกอินแล้วหรือไม่
if (!isset($_SESSION['user_id'])) {
    header('Location: user-login.php');
    exit();
}

// รับข้อมูลจาก URL
$order_id = $_GET['order_id'] ?? '';
$payment_method = $_GET['method'] ?? '';

if (empty($order_id) || empty($payment_method)) {
    header('Location: cart.php');
    exit();
}

// ดึงข้อมูล order
$conn = getConnection();
$orderQuery = "SELECT o.*, up.full_name, up.email, up.phone, up.address 
               FROM orders o 
               LEFT JOIN user_profiles up ON o.user_id = up.user_id 
               WHERE o.id = $1";
$orderResult = pg_query_params($conn, $orderQuery, [$order_id]);

if (!$orderResult || pg_num_rows($orderResult) == 0) {
    header('Location: cart.php');
    exit();
}

$order = pg_fetch_assoc($orderResult);

// ดึงข้อมูล order details
$detailsQuery = "SELECT * FROM order_details WHERE order_id = $1";
$detailsResult = pg_query_params($conn, $detailsQuery, [$order_id]);
$orderDetails = [];

if ($detailsResult) {
    while ($row = pg_fetch_assoc($detailsResult)) {
        $orderDetails[] = $row;
    }
}

pg_close($conn);

// ข้อมูลการชำระเงินตาม method
$paymentMethods = [
    'promptpay' => [
        'name' => 'QR พร้อมเพย์',
        'icon' => 'fas fa-qrcode',
        'description' => 'สแกน QR Code เพื่อชำระเงิน',
        'instructions' => [
            '1. เปิดแอปธนาคารหรือแอปพร้อมเพย์',
            '2. เลือก "สแกน QR Code"',
            '3. สแกน QR Code ด้านล่าง',
            '4. กรอกจำนวนเงิน: ฿' . number_format($order['grand_total'], 2),
            '5. ตรวจสอบข้อมูลและยืนยันการชำระเงิน'
        ]
    ],
    'credit_card' => [
        'name' => 'บัตรเครดิต/บัตรเดบิต',
        'icon' => 'fas fa-credit-card',
        'description' => 'ชำระเงินด้วยบัตรเครดิตหรือบัตรเดบิต',
        'instructions' => [
            '1. กรอกข้อมูลบัตรเครดิต/เดบิต',
            '2. ระบุหมายเลขบัตร, วันหมดอายุ, และ CVV',
            '3. ตรวจสอบข้อมูลและยืนยันการชำระเงิน',
            '4. รอการยืนยันจากธนาคาร'
        ]
    ],
    'bank_transfer' => [
        'name' => 'โอนเงินผ่านธนาคาร',
        'icon' => 'fas fa-university',
        'description' => 'โอนเงินผ่าน Internet Banking',
        'instructions' => [
            '1. เข้าสู่ระบบ Internet Banking',
            '2. เลือก "โอนเงิน"',
            '3. กรอกข้อมูลบัญชีปลายทาง',
            '4. ยืนยันการโอนเงิน'
        ]
    ],
    'mobile_banking' => [
        'name' => 'Mobile Banking',
        'icon' => 'fas fa-mobile-alt',
        'description' => 'ชำระเงินผ่านแอป Mobile Banking',
        'instructions' => [
            '1. เปิดแอป Mobile Banking',
            '2. เลือก "โอนเงิน" หรือ "ชำระเงิน"',
            '3. กรอกข้อมูลการชำระเงิน',
            '4. ยืนยันการชำระเงิน'
        ]
    ],
    'cash_on_delivery' => [
        'name' => 'เก็บเงินปลายทาง',
        'icon' => 'fas fa-money-bill-wave',
        'description' => 'ชำระเงินเมื่อได้รับสินค้า',
        'instructions' => [
            '1. ไม่ต้องชำระเงินล่วงหน้า',
            '2. รอรับสินค้าที่บ้าน',
            '3. ชำระเงินให้กับพนักงานจัดส่ง',
            '4. ตรวจสอบสินค้าก่อนชำระเงิน'
        ]
    ],
    'digital_wallet' => [
        'name' => 'ดิจิทัลวอลเล็ตร้านค้า',
        'icon' => 'fas fa-wallet',
        'description' => 'ชำระเงินด้วยดิจิทัลวอลเล็ต',
        'instructions' => [
            '1. เปิดแอปดิจิทัลวอลเล็ต',
            '2. เลือก "ชำระเงิน"',
            '3. สแกน QR Code หรือกรอกข้อมูล',
            '4. ยืนยันการชำระเงิน'
        ]
    ]
];

$paymentInfo = $paymentMethods[$payment_method] ?? $paymentMethods['promptpay'];

// ใช้รูป QR Code เดิม ไม่จำกัดวงเงิน
$qrCodeImage = 'img/Screenshot 2025-09-05 132548.png';

// ข้อมูลบัญชีธนาคาร
$bankAccounts = [
    [
        'bank' => 'ธนาคารกสิกรไทย',
        'account_name' => 'บริษัท แซปช็อป จำกัด',
        'account_number' => '123-4-56789-0',
        'branch' => 'สาขาสีลม'
    ],
    [
        'bank' => 'ธนาคารไทยพาณิชย์',
        'account_name' => 'บริษัท แซปช็อป จำกัด',
        'account_number' => '987-6-54321-0',
        'branch' => 'สาขาสีลม'
    ],
    [
        'bank' => 'ธนาคารกรุงเทพ',
        'account_name' => 'บริษัท แซปช็อป จำกัด',
        'account_number' => '456-7-89012-3',
        'branch' => 'สาขาสีลม'
    ]
];
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ชำระเงิน - <?php echo $paymentInfo['name']; ?> - ZapShop</title>
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

        .payment-header {
            background: linear-gradient(135deg, var(--makro-red) 0%, var(--makro-red-dark) 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
        }

        .payment-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .payment-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 0;
        }

        .payment-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .payment-method-card {
            background: white;
            border-radius: var(--makro-radius);
            box-shadow: var(--makro-shadow);
            padding: 30px;
            margin-bottom: 30px;
        }

        .method-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--makro-light-gray);
        }

        .method-icon {
            color: var(--makro-red);
            font-size: 2rem;
        }

        .method-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--makro-red);
            margin: 0;
        }

        .method-description {
            color: var(--makro-gray);
            font-size: 1.1rem;
            margin: 0;
        }

        .payment-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .payment-instructions {
            background: var(--makro-light-gray);
            border-radius: var(--makro-radius-sm);
            padding: 25px;
        }

        .instructions-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--makro-red);
            margin-bottom: 20px;
        }

        .instructions-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .instructions-list li {
            padding: 10px 0;
            border-bottom: 1px solid var(--makro-border);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .instructions-list li:last-child {
            border-bottom: none;
        }

        .step-number {
            background: var(--makro-red);
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .step-text {
            flex: 1;
        }

        .payment-details {
            background: white;
            border: 2px solid var(--makro-border);
            border-radius: var(--makro-radius-sm);
            padding: 25px;
        }

        .details-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--makro-red);
            margin-bottom: 20px;
        }

        .qr-code-container {
            text-align: center;
            margin-bottom: 20px;
        }

        .qr-code {
            width: 200px;
            height: 200px;
            border: 2px solid var(--makro-border);
            border-radius: var(--makro-radius-sm);
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
        }

        .qr-code-placeholder {
            color: var(--makro-gray);
            font-size: 0.9rem;
        }

        .payment-info {
            background: var(--makro-light-gray);
            border-radius: var(--makro-radius-sm);
            padding: 20px;
            margin-bottom: 20px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid var(--makro-border);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: var(--makro-gray);
            font-weight: 500;
        }

        .info-value {
            font-weight: 600;
            color: #2c3e50;
        }

        .amount-value {
            font-size: 1.2rem;
            color: var(--makro-red);
        }

        .bank-accounts {
            margin-top: 20px;
        }

        .bank-account {
            background: white;
            border: 1px solid var(--makro-border);
            border-radius: var(--makro-radius-sm);
            padding: 15px;
            margin-bottom: 15px;
        }

        .bank-name {
            font-weight: 600;
            color: var(--makro-red);
            margin-bottom: 5px;
        }

        .account-details {
            font-size: 0.9rem;
            color: var(--makro-gray);
        }

        .copy-button {
            background: var(--makro-blue);
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.8rem;
            cursor: pointer;
            margin-left: 10px;
        }

        .copy-button:hover {
            background: #0056b3;
        }

        .order-summary {
            background: white;
            border-radius: var(--makro-radius);
            box-shadow: var(--makro-shadow);
            padding: 30px;
            margin-bottom: 30px;
        }

        .summary-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--makro-red);
            margin-bottom: 20px;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid var(--makro-border);
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-info {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .item-details {
            font-size: 0.9rem;
            color: var(--makro-gray);
        }

        .item-total {
            font-weight: 600;
            color: var(--makro-red);
        }

        .total-section {
            background: var(--makro-light-gray);
            border-radius: var(--makro-radius-sm);
            padding: 20px;
            margin-top: 20px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
        }

        .total-row:last-child {
            border-top: 2px solid var(--makro-border);
            margin-top: 10px;
            padding-top: 20px;
        }

        .total-label {
            color: var(--makro-gray);
        }

        .total-value {
            font-weight: 600;
            color: #2c3e50;
        }

        .grand-total {
            font-size: 1.3rem;
            color: var(--makro-red);
        }

        .action-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .btn-action {
            padding: 15px 30px;
            border-radius: var(--makro-radius-sm);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--makro-blue) 0%, #0056b3 100%);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #0056b3 0%, var(--makro-blue) 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--makro-shadow-hover);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--makro-green) 0%, #20c997 100%);
            color: white;
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #20c997 0%, var(--makro-green) 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--makro-shadow-hover);
        }

        .btn-warning {
            background: linear-gradient(135deg, var(--makro-orange) 0%, #ff8c42 100%);
            color: white;
        }

        .btn-warning:hover {
            background: linear-gradient(135deg, #ff8c42 0%, var(--makro-orange) 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--makro-shadow-hover);
        }

        @media (max-width: 768px) {
            .payment-content {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .btn-action {
                width: 100%;
                max-width: 300px;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<?php include 'include/menu.php'; ?>

<div class="payment-header">
    <div class="container">
        <div class="text-center">
            <h1 class="payment-title">
                <i class="<?php echo $paymentInfo['icon']; ?>"></i> <?php echo $paymentInfo['name']; ?>
            </h1>
            <p class="payment-subtitle"><?php echo $paymentInfo['description']; ?></p>
        </div>
    </div>
</div>

<div class="payment-container">
    <div class="row">
        <div class="col-lg-8">
            <div class="payment-method-card">
                <div class="method-header">
                    <i class="<?php echo $paymentInfo['icon']; ?> method-icon"></i>
                    <div>
                        <h2 class="method-title"><?php echo $paymentInfo['name']; ?></h2>
                        <p class="method-description"><?php echo $paymentInfo['description']; ?></p>
                    </div>
                </div>
                
                <div class="payment-content">
                    <div class="payment-instructions">
                        <h3 class="instructions-title">วิธีการชำระเงิน</h3>
                        <ul class="instructions-list">
                            <?php foreach ($paymentInfo['instructions'] as $index => $instruction): ?>
                            <li>
                                <div class="step-number"><?php echo $index + 1; ?></div>
                                <div class="step-text"><?php echo $instruction; ?></div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <div class="payment-details">
                        <h3 class="details-title">ข้อมูลการชำระเงิน</h3>
                        
                        <?php if ($payment_method === 'promptpay'): ?>
                        <div class="qr-code-container">
                            <div class="qr-code">
                                <img src="<?php echo $qrCodeImage; ?>" alt="QR Code สำหรับชำระเงิน" style="width: 180px; height: 180px; border-radius: 8px;">
                            </div>
                            <p class="qr-code-placeholder">QR Code สำหรับชำระเงิน</p>
                            <div class="payment-amount-notice" style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 15px; margin-top: 15px; text-align: center;">
                                <i class="fas fa-exclamation-triangle" style="color: #856404; margin-right: 8px;"></i>
                                <strong style="color: #856404;">กรุณากรอกจำนวนเงิน: ฿<?php echo number_format($order['grand_total'], 2); ?></strong>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="payment-info">
                            <div class="info-row">
                                <span class="info-label">หมายเลขคำสั่งซื้อ:</span>
                                <span class="info-value"><?php echo htmlspecialchars($order['id']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">ยอดชำระเงิน:</span>
                                <span class="info-value amount-value">฿<?php echo number_format($order['grand_total'], 2); ?></span>
                            </div>
                            <?php if ($payment_method === 'promptpay'): ?>
                            <div class="info-row">
                                <span class="info-label">PromptPay ID:</span>
                                <span class="info-value">0812345678</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">จำนวนเงินที่ต้องชำระ:</span>
                                <span class="info-value amount-value">฿<?php echo number_format($order['grand_total'], 2); ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="info-row">
                                <span class="info-label">วันที่สั่งซื้อ:</span>
                                <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?></span>
                            </div>
                        </div>
                        
                        <?php if (in_array($payment_method, ['bank_transfer', 'mobile_banking'])): ?>
                        <div class="bank-accounts">
                            <h4 style="color: var(--makro-red); margin-bottom: 15px;">บัญชีธนาคาร</h4>
                            <?php foreach ($bankAccounts as $account): ?>
                            <div class="bank-account">
                                <div class="bank-name"><?php echo $account['bank']; ?></div>
                                <div class="account-details">
                                    ชื่อบัญชี: <?php echo $account['account_name']; ?><br>
                                    เลขที่บัญชี: <?php echo $account['account_number']; ?>
                                    <button class="copy-button" onclick="copyToClipboard('<?php echo $account['account_number']; ?>')">คัดลอก</button><br>
                                    สาขา: <?php echo $account['branch']; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php elseif ($payment_method === 'credit_card'): ?>
                        <div class="bank-accounts">
                            <h4 style="color: var(--makro-red); margin-bottom: 15px;">ข้อมูลบัตรเครดิต/เดบิต</h4>
                            <div class="bank-account">
                                <div class="bank-name">ชำระเงินด้วยบัตร</div>
                                <div class="account-details">
                                    หมายเลขบัตร: 1234-5678-9012-3456<br>
                                    ชื่อบนบัตร: ZAP SHOP<br>
                                    วันหมดอายุ: 12/25<br>
                                    CVV: 123
                                </div>
                            </div>
                        </div>
                        <?php elseif ($payment_method === 'digital_wallet'): ?>
                        <div class="bank-accounts">
                            <h4 style="color: var(--makro-red); margin-bottom: 15px;">ดิจิทัลวอลเล็ต</h4>
                            <div class="bank-account">
                                <div class="bank-name">ร้านค้าดิจิทัลวอลเล็ต</div>
                                <div class="account-details">
                                    Wallet ID: ZAPSHOP001<br>
                                    ชื่อร้าน: ZapShop Digital Wallet<br>
                                    หมายเหตุ: ไม่สามารถคืนเงินได้
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="order-summary">
                <h3 class="summary-title">สรุปการสั่งซื้อ</h3>
                
                <?php foreach ($orderDetails as $item): ?>
                <div class="order-item">
                    <div class="item-info">
                        <div class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                        <div class="item-details">฿<?php echo number_format($item['price'], 2); ?> × <?php echo $item['quantity']; ?> ชิ้น</div>
                    </div>
                    <div class="item-total">฿<?php echo number_format($item['total'], 2); ?></div>
                </div>
                <?php endforeach; ?>
                
                <div class="total-section">
                    <div class="total-row">
                        <span class="total-label">ยอดรวมสินค้า</span>
                        <span class="total-value">฿<?php echo number_format($order['grand_total'] - 38, 2); ?></span>
                    </div>
                    <div class="total-row">
                        <span class="total-label">ค่าจัดส่ง</span>
                        <span class="total-value">฿38.00</span>
                    </div>
                    <div class="total-row">
                        <span class="total-label">ยอดชำระเงินทั้งหมด</span>
                        <span class="total-value grand-total">฿<?php echo number_format($order['grand_total'], 2); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="action-buttons">
        <button class="btn-action btn-success" onclick="confirmPayment()">
            <i class="fas fa-check"></i> ยืนยันการชำระเงิน
        </button>
        <a href="payment-methods.php" class="btn-action btn-warning">
            <i class="fas fa-arrow-left"></i> เปลี่ยนวิธีการชำระเงิน
        </a>
        <a href="product-list1.php" class="btn-action btn-primary">
            <i class="fas fa-shopping-bag"></i> ช้อปปิ้งต่อ
        </a>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
// QR Code แสดงจากรูปภาพแล้ว ไม่ต้องใช้ JavaScript

// ฟังก์ชันคัดลอกข้อมูล
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('คัดลอกข้อมูลเรียบร้อยแล้ว: ' + text);
    }, function(err) {
        console.error('Could not copy text: ', err);
        // Fallback สำหรับ browser เก่า
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        alert('คัดลอกข้อมูลเรียบร้อยแล้ว: ' + text);
    });
}

// ฟังก์ชันยืนยันการชำระเงิน
function confirmPayment() {
    if (confirm('คุณได้ชำระเงินเรียบร้อยแล้วหรือไม่?')) {
        // ส่งข้อมูลไปยังหน้า success
        window.location.href = 'checkout-success.php?order_id=<?php echo $order_id; ?>';
    }
}

// Auto refresh สำหรับตรวจสอบสถานะการชำระเงิน
<?php if ($payment_method === 'promptpay'): ?>
setInterval(function() {
    // ตรวจสอบสถานะการชำระเงิน (ตัวอย่าง)
    // ในระบบจริงควรเรียก API เพื่อตรวจสอบสถานะ
}, 30000); // ตรวจสอบทุก 30 วินาที
<?php endif; ?>
</script>
</body>
</html>
