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
    <title>ชำระเงินด้วยบัตรเครดิต - ZapShop</title>
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

        .credit-card-form {
            padding: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
        }

        .form-control {
            border: 2px solid var(--makro-border);
            border-radius: var(--makro-radius-sm);
            padding: 12px 15px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--makro-red);
            box-shadow: 0 0 0 0.2rem rgba(227, 24, 55, 0.25);
        }

        .card-number-input {
            background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23ccc"><path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>');
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 20px;
            padding-right: 50px;
        }

        .card-icons {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .card-icon {
            width: 40px;
            height: 25px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: white;
            font-weight: bold;
        }

        .visa {
            background: linear-gradient(135deg, #1a1f71 0%, #00539c 100%);
        }

        .mastercard {
            background: linear-gradient(135deg, #eb001b 0%, #f79e1b 100%);
        }

        .amex {
            background: linear-gradient(135deg, #006fcf 0%, #00d4aa 100%);
        }

        .btn-pay {
            background: linear-gradient(135deg, var(--makro-red) 0%, var(--makro-red-dark) 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: var(--makro-radius-sm);
            font-size: 18px;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-pay:hover {
            background: linear-gradient(135deg, var(--makro-red-dark) 0%, #a0102a 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(227, 24, 55, 0.3);
        }

        .btn-pay:disabled {
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
        }
    </style>
</head>
<body>
    <?php include 'include/menu.php'; ?>

    <div class="page-header">
        <div class="container">
            <div class="page-header-content">
                <h1 class="page-title">
                    <i class="fas fa-credit-card"></i> ชำระเงินด้วยบัตรเครดิต
                </h1>
                <p class="page-subtitle">กรุณากรอกข้อมูลบัตรเครดิตของคุณ</p>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="payment-card fade-in-up">
                    <div class="payment-header">
                        <h3 class="mb-0">
                            <i class="fas fa-credit-card text-primary me-2"></i>
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

                    <form class="credit-card-form" id="creditCardForm">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label">หมายเลขบัตรเครดิต</label>
                                    <input type="text" class="form-control card-number-input" id="cardNumber" 
                                           placeholder="1234 5678 9012 3456" maxlength="19" required>
                                    <div class="card-icons">
                                        <div class="card-icon visa">VISA</div>
                                        <div class="card-icon mastercard">MC</div>
                                        <div class="card-icon amex">AMEX</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">วันหมดอายุ</label>
                                    <input type="text" class="form-control" id="expiryDate" 
                                           placeholder="MM/YY" maxlength="5" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">CVV</label>
                                    <input type="text" class="form-control" id="cvv" 
                                           placeholder="123" maxlength="4" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label">ชื่อผู้ถือบัตร</label>
                                    <input type="text" class="form-control" id="cardholderName" 
                                           placeholder="ชื่อ-นามสกุลตามที่ปรากฏบนบัตร" required>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn-pay" id="payButton">
                            <i class="fas fa-lock me-2"></i>
                            ชำระเงิน ฿<?php echo number_format($payment_data['amount'], 2); ?>
                        </button>

                        <div class="security-info">
                            <i class="fas fa-shield-alt"></i>
                            การชำระเงินของคุณได้รับการปกป้องด้วยการเข้ารหัส SSL 256-bit
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // จัดการการกรอกหมายเลขบัตรเครดิต
        document.getElementById('cardNumber').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s/g, '');
            let formattedValue = '';
            
            for (let i = 0; i < value.length; i++) {
                if (i > 0 && i % 4 === 0) {
                    formattedValue += ' ';
                }
                formattedValue += value[i];
            }
            
            e.target.value = formattedValue;
        });

        // จัดการการกรอกวันหมดอายุ
        document.getElementById('expiryDate').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            
            if (value.length >= 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            
            e.target.value = value;
        });

        // จัดการการกรอก CVV
        document.getElementById('cvv').addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '');
        });

        // จัดการการส่งฟอร์ม
        document.getElementById('creditCardForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const payButton = document.getElementById('payButton');
            const originalText = payButton.innerHTML;
            
            // แสดงสถานะกำลังประมวลผล
            payButton.disabled = true;
            payButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>กำลังประมวลผล...';
            
            // จำลองการประมวลผลการชำระเงิน
            setTimeout(() => {
                // ส่งข้อมูลไปยัง payment-complete.php
                const formData = new FormData();
                formData.append('payment_method', 'credit_card');
                formData.append('transaction_id', '<?php echo $payment_data['transaction_id']; ?>');
                formData.append('order_id', '<?php echo $payment_data['order_id']; ?>');
                formData.append('amount', '<?php echo $payment_data['amount']; ?>');
                
                fetch('payment-complete.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = 'payment-success.php';
                    } else {
                        alert('เกิดข้อผิดพลาดในการชำระเงิน: ' + data.message);
                        payButton.disabled = false;
                        payButton.innerHTML = originalText;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
                    payButton.disabled = false;
                    payButton.innerHTML = originalText;
                });
            }, 2000);
        });
    </script>
</body>
</html>
