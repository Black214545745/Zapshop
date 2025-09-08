<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['payment_data'])) {
    header("Location: user-login.php");
    exit();
}

$payment_data = $_SESSION['payment_data'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ชำระเงินด้วย PayPal - ZapShop</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --makro-red: #e31837;
            --makro-red-dark: #c41230;
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
            padding: 40px 0;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" fill="rgba(255,255,255,0.1)"><polygon points="0,0 1000,100 1000,0"/></svg>');
            background-size: cover;
        }

        .page-header-content {
            position: relative;
            z-index: 2;
            text-align: center;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .page-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 0;
        }

        .payment-card {
            background: white;
            border-radius: var(--makro-radius);
            box-shadow: var(--makro-shadow);
            margin-bottom: 25px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .payment-card:hover {
            box-shadow: var(--makro-shadow-hover);
            transform: translateY(-2px);
        }

        .payment-header {
            background: var(--makro-light-gray);
            padding: 20px;
            border-bottom: 1px solid var(--makro-border);
        }

        .payment-summary {
            background: var(--makro-light-gray);
            padding: 20px;
            border-radius: var(--makro-radius-sm);
            margin-bottom: 20px;
        }

        .paypal-section {
            padding: 30px;
            text-align: center;
        }

        .paypal-logo {
            background: linear-gradient(135deg, #003087 0%, #009cde 100%);
            color: white;
            padding: 30px;
            border-radius: var(--makro-radius-sm);
            margin: 20px 0;
            text-align: center;
        }

        .paypal-logo i {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .paypal-logo h3 {
            margin-bottom: 10px;
            font-weight: 600;
        }

        .paypal-logo p {
            margin-bottom: 0;
            opacity: 0.9;
        }

        .payment-details {
            background: var(--makro-light-gray);
            padding: 20px;
            border-radius: var(--makro-radius-sm);
            margin: 20px 0;
            text-align: left;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid var(--makro-border);
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: #2c3e50;
        }

        .detail-value {
            color: var(--makro-blue);
            font-weight: 600;
        }

        .amount-value {
            color: var(--makro-red);
            font-size: 1.2rem;
            font-weight: 700;
        }

        .exchange-rate {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: var(--makro-radius-sm);
            margin: 20px 0;
            text-align: center;
        }

        .exchange-rate i {
            color: #f39c12;
            margin-right: 8px;
        }

        .paypal-benefits {
            background: var(--makro-light-gray);
            padding: 20px;
            border-radius: var(--makro-radius-sm);
            margin: 20px 0;
        }

        .benefit-item {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .benefit-item:last-child {
            margin-bottom: 0;
        }

        .benefit-icon {
            background: var(--makro-blue);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .benefit-text {
            flex: 1;
            line-height: 1.6;
        }

        .btn-paypal {
            background: linear-gradient(135deg, #003087 0%, #009cde 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: var(--makro-radius-sm);
            font-size: 18px;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
            max-width: 300px;
        }

        .btn-paypal:hover {
            background: linear-gradient(135deg, #002a6e 0%, #0088c4 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 48, 135, 0.3);
        }

        .btn-paypal:disabled {
            background: var(--makro-gray);
            cursor: not-allowed;
            transform: none;
        }

        .security-info {
            background: var(--makro-light-gray);
            padding: 15px;
            border-radius: var(--makro-radius-sm);
            margin-top: 20px;
            text-align: center;
            color: var(--makro-gray);
        }

        .security-info i {
            color: var(--makro-green);
            margin-right: 8px;
        }

        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }
            
            .payment-card {
                margin: 0 15px 25px 15px;
            }
            
            .paypal-section {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <?php include 'include/menu.php'; ?>

    <div class="page-header">
        <div class="container">
            <div class="page-header-content">
                <h1 class="page-title">
                    <i class="fab fa-paypal"></i> ชำระเงินด้วย PayPal
                </h1>
                <p class="page-subtitle">ชำระเงินอย่างปลอดภัยด้วย PayPal</p>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="payment-card fade-in-up">
                    <div class="payment-header">
                        <h3 class="mb-0">
                            <i class="fab fa-paypal text-primary me-2"></i>
                            ข้อมูลการชำระเงิน
                        </h3>
                    </div>

                    <div class="payment-summary">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>ยอดรวม:</strong> ฿<?php echo number_format($payment_data['amount'], 2); ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Transaction ID:</strong> <?php echo $payment_data['transaction_id']; ?>
                            </div>
                        </div>
                    </div>

                    <div class="paypal-section">
                        <div class="paypal-logo">
                            <i class="fab fa-paypal"></i>
                            <h3>PayPal</h3>
                            <p>ชำระเงินออนไลน์อย่างปลอดภัย</p>
                        </div>

                        <div class="payment-details">
                            <div class="detail-row">
                                <span class="detail-label">จำนวนเงิน (บาท):</span>
                                <span class="detail-value amount-value">฿<?php echo number_format($payment_data['amount'], 2); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">จำนวนเงิน (USD):</span>
                                <span class="detail-value">$<?php echo number_format($payment_data['amount'] / 35, 2); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Transaction ID:</span>
                                <span class="detail-value"><?php echo $payment_data['transaction_id']; ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">วันที่:</span>
                                <span class="detail-value"><?php echo date('d/m/Y H:i'); ?></span>
                            </div>
                        </div>

                        <div class="exchange-rate">
                            <i class="fas fa-info-circle"></i>
                            <strong>อัตราแลกเปลี่ยน:</strong> 1 USD = 35 บาท (ประมาณการ)
                        </div>

                        <div class="paypal-benefits">
                            <h5 class="mb-3">
                                <i class="fas fa-star text-warning me-2"></i>
                                ข้อดีของการใช้ PayPal
                            </h5>
                            
                            <div class="benefit-item">
                                <div class="benefit-icon">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <div class="benefit-text">
                                    <strong>ความปลอดภัยสูง:</strong> ข้อมูลการชำระเงินของคุณได้รับการปกป้องด้วยการเข้ารหัสระดับสูง
                                </div>
                            </div>
                            
                            <div class="benefit-item">
                                <div class="benefit-icon">
                                    <i class="fas fa-globe"></i>
                                </div>
                                <div class="benefit-text">
                                    <strong>รองรับสกุลเงินหลายชนิด:</strong> ชำระเงินด้วยสกุลเงินที่คุณต้องการ
                                </div>
                            </div>
                            
                            <div class="benefit-item">
                                <div class="benefit-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="benefit-text">
                                    <strong>การยืนยันรวดเร็ว:</strong> ได้รับการยืนยันการชำระเงินทันที
                                </div>
                            </div>
                            
                            <div class="benefit-item">
                                <div class="benefit-icon">
                                    <i class="fas fa-headset"></i>
                                </div>
                                <div class="benefit-text">
                                    <strong>บริการลูกค้าตลอด 24 ชั่วโมง:</strong> ได้รับความช่วยเหลือเมื่อต้องการ
                                </div>
                            </div>
                        </div>

                        <button type="button" class="btn-paypal" id="paypalButton">
                            <i class="fab fa-paypal me-2"></i>
                            ชำระเงินด้วย PayPal
                        </button>

                        <div class="security-info">
                            <i class="fas fa-lock"></i>
                            การชำระเงินของคุณได้รับการปกป้องด้วยการเข้ารหัส SSL 256-bit และมาตรฐานความปลอดภัยของ PayPal
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // จัดการการชำระเงินด้วย PayPal
        document.getElementById('paypalButton').addEventListener('click', function() {
            const paypalButton = this;
            const originalText = paypalButton.innerHTML;
            
            // แสดงสถานะกำลังประมวลผล
            paypalButton.disabled = true;
            paypalButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>กำลังเชื่อมต่อ PayPal...';
            
            // จำลองการเชื่อมต่อ PayPal
            setTimeout(() => {
                // แสดงสถานะการชำระเงิน
                paypalButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>กำลังประมวลผลการชำระเงิน...';
                
                // จำลองการประมวลผลการชำระเงิน
                setTimeout(() => {
                    // ส่งข้อมูลไปยัง payment-complete.php
                    const formData = new FormData();
                    formData.append('payment_method', 'paypal');
                    formData.append('transaction_id', '<?php echo $payment_data['transaction_id']; ?>');
                    formData.append('order_id', '<?php echo $payment_data['order_id']; ?>');
                    formData.append('amount', '<?php echo $payment_data['amount']; ?>');
                    formData.append('currency', 'THB');
                    formData.append('usd_amount', '<?php echo number_format($payment_data['amount'] / 35, 2); ?>');
                    
                    fetch('payment-complete.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.href = 'payment-success.php';
                        } else {
                            alert('เกิดข้อผิดพลาด: ' + data.message);
                            paypalButton.disabled = false;
                            paypalButton.innerHTML = originalText;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
                        paypalButton.disabled = false;
                        paypalButton.innerHTML = originalText;
                    });
                }, 2000);
            }, 2000);
        });
    </script>
</body>
</html>
