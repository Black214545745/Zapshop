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
    <title>ชำระเงินด้วยการโอนเงิน - ZapShop</title>
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

        .bank-accounts {
            padding: 20px;
        }

        .bank-account {
            border: 2px solid var(--makro-border);
            border-radius: var(--makro-radius-sm);
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .bank-account:hover {
            border-color: var(--makro-blue);
            box-shadow: 0 4px 15px rgba(0, 102, 204, 0.2);
        }

        .bank-account.selected {
            border-color: var(--makro-blue);
            background: rgba(0, 102, 204, 0.05);
        }

        .bank-logo {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            margin-bottom: 15px;
        }

        .scb { background: linear-gradient(135deg, #4e2a84 0%, #7b68ee 100%); }
        .bbl { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); }
        .kbank { background: linear-gradient(135deg, #059669 0%, #10b981 100%); }
        .ktb { background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%); }

        .bank-info h4 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .account-number {
            font-family: 'Courier New', monospace;
            font-size: 18px;
            font-weight: bold;
            color: var(--makro-blue);
            background: var(--makro-light-gray);
            padding: 10px;
            border-radius: 6px;
            margin: 10px 0;
            text-align: center;
            letter-spacing: 2px;
        }

        .copy-btn {
            background: var(--makro-blue);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .copy-btn:hover {
            background: #0056b3;
            transform: translateY(-1px);
        }

        .transfer-instructions {
            background: var(--makro-light-gray);
            padding: 20px;
            border-radius: var(--makro-radius-sm);
            margin: 20px 0;
        }

        .instruction-step {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 15px;
        }

        .step-number {
            background: var(--makro-blue);
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            flex-shrink: 0;
        }

        .step-text {
            flex: 1;
            line-height: 1.6;
        }

        .btn-confirm {
            background: linear-gradient(135deg, var(--makro-green) 0%, #20c997 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: var(--makro-radius-sm);
            font-size: 18px;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-confirm:hover {
            background: linear-gradient(135deg, #20c997 0%, #1a9f7a 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }

        .btn-confirm:disabled {
            background: var(--makro-gray);
            cursor: not-allowed;
            transform: none;
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
            
            .bank-account {
                padding: 15px;
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
                    <i class="fas fa-university"></i> ชำระเงินด้วยการโอนเงิน
                </h1>
                <p class="page-subtitle">เลือกธนาคารและทำการโอนเงินตามข้อมูลที่แสดง</p>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="payment-card fade-in-up">
                    <div class="payment-header">
                        <h3 class="mb-0">
                            <i class="fas fa-university text-success me-2"></i>
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

                    <div class="bank-accounts">
                        <h4 class="mb-3">เลือกธนาคารที่ต้องการโอนเงิน</h4>
                        
                        <div class="bank-account" data-bank="scb">
                            <div class="d-flex align-items-center">
                                <div class="bank-logo scb me-3">
                                    <i class="fas fa-university"></i>
                                </div>
                                <div class="bank-info flex-grow-1">
                                    <h4>ธนาคารไทยพาณิชย์ (SCB)</h4>
                                    <div class="account-number" data-account="123-4-56789-0">123-4-56789-0</div>
                                    <div class="text-muted">ชื่อบัญชี: บริษัท ZapShop จำกัด</div>
                                    <button class="copy-btn" onclick="copyToClipboard('123-4-56789-0')">
                                        <i class="fas fa-copy me-1"></i>คัดลอกเลขบัญชี
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="bank-account" data-bank="bbl">
                            <div class="d-flex align-items-center">
                                <div class="bank-logo bbl me-3">
                                    <i class="fas fa-university"></i>
                                </div>
                                <div class="bank-info flex-grow-1">
                                    <h4>ธนาคารกรุงเทพ (BBL)</h4>
                                    <div class="account-number" data-account="123-4-56789-1">123-4-56789-1</div>
                                    <div class="text-muted">ชื่อบัญชี: บริษัท ZapShop จำกัด</div>
                                    <button class="copy-btn" onclick="copyToClipboard('123-4-56789-1')">
                                        <i class="fas fa-copy me-1"></i>คัดลอกเลขบัญชี
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="bank-account" data-bank="kbank">
                            <div class="d-flex align-items-center">
                                <div class="bank-logo kbank me-3">
                                    <i class="fas fa-university"></i>
                                </div>
                                <div class="bank-info flex-grow-1">
                                    <h4>ธนาคารกสิกรไทย (KBANK)</h4>
                                    <div class="account-number" data-account="123-4-56789-2">123-4-56789-2</div>
                                    <div class="text-muted">ชื่อบัญชี: บริษัท ZapShop จำกัด</div>
                                    <button class="copy-btn" onclick="copyToClipboard('123-4-56789-2')">
                                        <i class="fas fa-copy me-1"></i>คัดลอกเลขบัญชี
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="bank-account" data-bank="ktb">
                            <div class="d-flex align-items-center">
                                <div class="bank-logo ktb me-3">
                                    <i class="fas fa-university"></i>
                                </div>
                                <div class="bank-info flex-grow-1">
                                    <h4>ธนาคารกรุงไทย (KTB)</h4>
                                    <div class="account-number" data-account="123-4-56789-3">123-4-56789-3</div>
                                    <div class="text-muted">ชื่อบัญชี: บริษัท ZapShop จำกัด</div>
                                    <button class="copy-btn" onclick="copyToClipboard('123-4-56789-3')">
                                        <i class="fas fa-copy me-1"></i>คัดลอกเลขบัญชี
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="transfer-instructions">
                            <h5 class="mb-3">
                                <i class="fas fa-info-circle text-info me-2"></i>
                                วิธีการโอนเงิน
                            </h5>
                            
                            <div class="instruction-step">
                                <div class="step-number">1</div>
                                <div class="step-text">เลือกธนาคารที่ต้องการโอนเงินจากรายการด้านบน</div>
                            </div>
                            
                            <div class="instruction-step">
                                <div class="step-number">2</div>
                                <div class="step-text">คัดลอกเลขบัญชีและทำการโอนเงินผ่านแอปธนาคารหรือตู้ ATM</div>
                            </div>
                            
                            <div class="instruction-step">
                                <div class="step-number">3</div>
                                <div class="step-text">โอนเงินเป็นจำนวน <strong>฿<?php echo number_format($payment_data['amount'], 2); ?></strong></div>
                            </div>
                            
                            <div class="instruction-step">
                                <div class="step-number">4</div>
                                <div class="step-text">เก็บใบเสร็จการโอนเงินไว้เป็นหลักฐาน</div>
                            </div>
                            
                            <div class="instruction-step">
                                <div class="step-number">5</div>
                                <div class="step-text">คลิกปุ่ม "ยืนยันการโอนเงิน" ด้านล่าง</div>
                            </div>
                        </div>

                        <button type="button" class="btn-confirm" id="confirmButton" disabled>
                            <i class="fas fa-check me-2"></i>
                            ยืนยันการโอนเงิน
                        </button>

                        <div class="text-center mt-3">
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>
                                การยืนยันจะเสร็จสิ้นภายใน 24 ชั่วโมงหลังจากได้รับเงิน
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedBank = null;

        // จัดการการเลือกธนาคาร
        document.querySelectorAll('.bank-account').forEach(account => {
            account.addEventListener('click', function() {
                // ลบการเลือกจากธนาคารอื่น
                document.querySelectorAll('.bank-account').forEach(acc => {
                    acc.classList.remove('selected');
                });
                
                // เลือกธนาคารนี้
                this.classList.add('selected');
                selectedBank = this.dataset.bank;
                
                // เปิดใช้งานปุ่มยืนยัน
                document.getElementById('confirmButton').disabled = false;
            });
        });

        // ฟังก์ชันคัดลอกเลขบัญชี
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                // แสดงข้อความว่าคัดลอกสำเร็จ
                const button = event.target.closest('.copy-btn');
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check me-1"></i>คัดลอกแล้ว';
                button.style.background = '#28a745';
                
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.style.background = '';
                }, 2000);
            }).catch(function(err) {
                console.error('Could not copy text: ', err);
                alert('ไม่สามารถคัดลอกข้อความได้');
            });
        }

        // จัดการการยืนยันการโอนเงิน
        document.getElementById('confirmButton').addEventListener('click', function() {
            if (!selectedBank) {
                alert('กรุณาเลือกธนาคารก่อน');
                return;
            }

            const confirmButton = this;
            const originalText = confirmButton.innerHTML;
            
            // แสดงสถานะกำลังประมวลผล
            confirmButton.disabled = true;
            confirmButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>กำลังประมวลผล...';
            
            // จำลองการประมวลผล
            setTimeout(() => {
                // ส่งข้อมูลไปยัง payment-complete.php
                const formData = new FormData();
                formData.append('payment_method', 'bank_transfer');
                formData.append('transaction_id', '<?php echo $payment_data['transaction_id']; ?>');
                formData.append('order_id', '<?php echo $payment_data['order_id']; ?>');
                formData.append('amount', '<?php echo $payment_data['amount']; ?>');
                formData.append('bank', selectedBank);
                
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
                        confirmButton.disabled = false;
                        confirmButton.innerHTML = originalText;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
                    confirmButton.disabled = false;
                    confirmButton.innerHTML = originalText;
                });
            }, 2000);
        });
    </script>
</body>
</html>
