<?php
/**
 * Dashboard หลักสำหรับ Payment Gateway
 * แสดงสถานะและลิงก์ไปยังระบบต่างๆ
 */

session_start();

// โหลดการตั้งค่า Payment Gateway
$configFile = 'payment_gateway_settings.json';
$currentConfig = [];

if (file_exists($configFile)) {
    $currentConfig = json_decode(file_get_contents($configFile), true);
}

// ตรวจสอบสถานะ
$status = [
    'enabled' => $currentConfig['enabled'] ?? false,
    'provider' => $currentConfig['provider'] ?? '',
    'configured' => !empty($currentConfig['api_key']) && !empty($currentConfig['merchant_id']),
    'sandbox_mode' => $currentConfig['sandbox_mode'] ?? true
];

// สถานะการทดสอบ
$testStatus = [
    'connection' => 'ยังไม่ได้ทดสอบ',
    'qr_generation' => 'ยังไม่ได้ทดสอบ',
    'payment_status' => 'ยังไม่ได้ทดสอบ'
];

// ข้อมูลสถิติ (ตัวอย่าง)
$statistics = [
    'total_transactions' => 0,
    'successful_payments' => 0,
    'failed_payments' => 0,
    'total_amount' => 0
];
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Gateway Dashboard - ZapShop</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .status-card {
            transition: transform 0.2s;
        }
        .status-card:hover {
            transform: translateY(-5px);
        }
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }
        .status-active { background-color: #28a745; }
        .status-inactive { background-color: #dc3545; }
        .status-warning { background-color: #ffc107; }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <h1><i class="fas fa-tachometer-alt me-2"></i>Payment Gateway Dashboard</h1>
                <p class="text-muted">จัดการและติดตามระบบ Payment Gateway ของ ZapShop</p>
            </div>
        </div>
        
        <!-- สถานะโดยรวม -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card status-card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title">สถานะระบบ</h4>
                                <h2><?php echo $status['enabled'] ? 'เปิดใช้งาน' : 'ปิดใช้งาน'; ?></h2>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-power-off fa-3x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card status-card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title">Provider</h4>
                                <h2><?php echo $status['provider'] ?: 'ยังไม่ได้ตั้งค่า'; ?></h2>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-credit-card fa-3x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card status-card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title">โหมดการทำงาน</h4>
                                <h2><?php echo $status['sandbox_mode'] ? 'Sandbox' : 'Production'; ?></h2>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-flask fa-3x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card status-card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title">การตั้งค่า</h4>
                                <h2><?php echo $status['configured'] ? 'ครบถ้วน' : 'ไม่ครบถ้วน'; ?></h2>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-cog fa-3x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- สถิติการชำระเงิน -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-bar me-2"></i>สถิติการชำระเงิน</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 text-center">
                                <div class="border rounded p-3">
                                    <h4 class="text-primary"><?php echo number_format($statistics['total_transactions']); ?></h4>
                                    <p class="text-muted mb-0">รายการทั้งหมด</p>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="border rounded p-3">
                                    <h4 class="text-success"><?php echo number_format($statistics['successful_payments']); ?></h4>
                                    <p class="text-muted mb-0">ชำระเงินสำเร็จ</p>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="border rounded p-3">
                                    <h4 class="text-danger"><?php echo number_format($statistics['failed_payments']); ?></h4>
                                    <p class="text-muted mb-0">ชำระเงินล้มเหลว</p>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="border rounded p-3">
                                    <h4 class="text-info">฿<?php echo number_format($statistics['total_amount'], 2); ?></h4>
                                    <p class="text-muted mb-0">ยอดรวมทั้งหมด</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- เมนูการจัดการ -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-cogs me-2"></i>การตั้งค่า</h3>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <a href="payment_gateway_config.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-cog me-2"></i>
                                    <strong>ตั้งค่า Payment Gateway</strong>
                                    <br>
                                    <small class="text-muted">จัดการการตั้งค่า API, Provider, และการเชื่อมต่อ</small>
                                </div>
                                <i class="fas fa-chevron-right"></i>
                            </a>
                            
                            <a href="application_documents.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-file-alt me-2"></i>
                                    <strong>เอกสารสำหรับสมัคร</strong>
                                    <br>
                                    <small class="text-muted">ดูเอกสารที่ต้องเตรียมสำหรับสมัครใช้บริการ</small>
                                </div>
                                <i class="fas fa-chevron-right"></i>
                            </a>
                            
                            <a href="email_templates.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-envelope me-2"></i>
                                    <strong>เทมเพลตอีเมล</strong>
                                    <br>
                                    <small class="text-muted">เทมเพลตสำหรับติดต่อธนาคาร</small>
                                </div>
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-flask me-2"></i>การทดสอบ</h3>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <a href="payment_gateway_test.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-plug me-2"></i>
                                    <strong>ทดสอบการเชื่อมต่อ</strong>
                                    <br>
                                    <small class="text-muted">ทดสอบการเชื่อมต่อ API และการสร้าง QR Code</small>
                                </div>
                                <i class="fas fa-chevron-right"></i>
                            </a>
                            
                            <a href="test_local_qr.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-qrcode me-2"></i>
                                    <strong>ทดสอบ Local QR Code</strong>
                                    <br>
                                    <small class="text-muted">ทดสอบ Local QR Code Generator ที่เร็วที่สุด</small>
                                </div>
                                <i class="fas fa-chevron-right"></i>
                            </a>
                            
                            <!-- ลิงก์ QR Code ถูกลบออกแล้ว -->
                            
                            <a href="payment_gateway_api.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-code me-2"></i>
                                    <strong>โค้ด API Integration</strong>
                                    <br>
                                    <small class="text-muted">ดูโค้ดสำหรับเชื่อมต่อ Payment Gateway</small>
                                </div>
                                <i class="fas fa-chevron-right"></i>
                            </a>
                            
                            <a href="payment_callback_handler.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-webhook me-2"></i>
                                    <strong>Callback Handler</strong>
                                    <br>
                                    <small class="text-muted">จัดการ Callback จาก Payment Gateway</small>
                                </div>
                                <i class="fas fa-chevron-right"></i>
                            </a>
                            
                            <a href="notification_system.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-bell me-2"></i>
                                    <strong>ระบบแจ้งเตือน</strong>
                                    <br>
                                    <small class="text-muted">ส่งการแจ้งเตือนผ่าน LINE, Email, SMS</small>
                                </div>
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-line me-2"></i>รายงานและสถิติ</h3>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <a href="payment_reports.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-chart-bar me-2"></i>
                                    <strong>รายงานการชำระเงิน</strong>
                                    <br>
                                    <small class="text-muted">ดูสถิติและรายงานต่างๆ</small>
                                </div>
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-book me-2"></i>คู่มือและเอกสาร</h3>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <a href="payment_gateway_manual.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-book me-2"></i>
                                    <strong>คู่มือการใช้งาน</strong>
                                    <br>
                                    <small class="text-muted">คำแนะนำและขั้นตอนการใช้งานอย่างละเอียด</small>
                                </div>
                                <i class="fas fa-chevron-right"></i>
                            </a>
                            
                            <a href="payment_gateway_summary.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-home me-2"></i>
                                    <strong>หน้าสรุประบบ</strong>
                                    <br>
                                    <small class="text-muted">ภาพรวมและลิงก์ไปยังระบบต่างๆ</small>
                                </div>
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- สถานะการทดสอบ -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-clipboard-check me-2"></i>สถานะการทดสอบ</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="d-flex align-items-center">
                                    <span class="status-indicator status-warning"></span>
                                    <div>
                                        <strong>การเชื่อมต่อ API</strong>
                                        <br>
                                        <small class="text-muted"><?php echo $testStatus['connection']; ?></small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-center">
                                    <span class="status-indicator status-warning"></span>
                                    <div>
                                        <strong>การสร้าง QR Code</strong>
                                        <br>
                                        <small class="text-muted"><?php echo $testStatus['qr_generation']; ?></small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-center">
                                    <span class="status-indicator status-warning"></span>
                                    <div>
                                        <strong>การตรวจสอบสถานะ</strong>
                                        <br>
                                        <small class="text-muted"><?php echo $testStatus['payment_status']; ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- คำแนะนำและขั้นตอนต่อไป -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-lightbulb me-2"></i>คำแนะนำและขั้นตอนต่อไป</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!$status['enabled']): ?>
                            <div class="alert alert-warning">
                                <h4><i class="fas fa-exclamation-triangle me-2"></i>ขั้นตอนแรก: เปิดใช้งาน Payment Gateway</h4>
                                <p>คุณต้องเปิดใช้งาน Payment Gateway ก่อนเพื่อเริ่มใช้งานระบบ</p>
                                <a href="payment_gateway_config.php" class="btn btn-warning">ไปตั้งค่าตอนนี้</a>
                            </div>
                        <?php elseif (!$status['configured']): ?>
                            <div class="alert alert-info">
                                <h4><i class="fas fa-info-circle me-2"></i>ขั้นตอนที่ 2: ตั้งค่าการเชื่อมต่อ</h4>
                                <p>คุณต้องตั้งค่า API Key, Merchant ID และข้อมูลอื่นๆ ให้ครบถ้วน</p>
                                <a href="payment_gateway_config.php" class="btn btn-info">ไปตั้งค่าตอนนี้</a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <h4><i class="fas fa-check-circle me-2"></i>พร้อมใช้งาน!</h4>
                                <p>Payment Gateway ของคุณพร้อมใช้งานแล้ว ขั้นตอนต่อไปคือการทดสอบระบบ</p>
                                <a href="payment_gateway_test.php" class="btn btn-success">ทดสอบระบบตอนนี้</a>
                            </div>
                        <?php endif; ?>
                        
                        <hr>
                        
                        <h5>ขั้นตอนการใช้งาน:</h5>
                        <ol>
                            <li><strong>สมัครใช้บริการ:</strong> ติดต่อธนาคารเพื่อสมัครใช้บริการ Payment Gateway</li>
                            <li><strong>ตั้งค่าระบบ:</strong> ตั้งค่า API Key, Merchant ID และข้อมูลอื่นๆ</li>
                            <li><strong>ทดสอบระบบ:</strong> ทดสอบการเชื่อมต่อและการสร้าง QR Code</li>
                            <li><strong>ใช้งานจริง:</strong> เปิดใช้งานในระบบ Production</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        
        <hr>
        
        <!-- ลิงก์กลับ -->
        <div class="text-center mb-4">
            <a href="index.php" class="btn btn-secondary me-2">
                <i class="fas fa-home me-2"></i>หน้าแรก
            </a>
            <a href="checkout.php" class="btn btn-primary me-2">
                <i class="fas fa-shopping-cart me-2"></i>หน้าชำระเงิน
            </a>
            <a href="admin-dashboard.php" class="btn btn-info">
                <i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard
            </a>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
