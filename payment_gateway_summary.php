<?php
/**
 * สรุประบบ Payment Gateway ทั้งหมด
 * แสดงภาพรวมและลิงก์ไปยังระบบต่างๆ
 */

session_start();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สรุประบบ Payment Gateway - ZapShop</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .feature-card {
            transition: transform 0.2s, box-shadow 0.2s;
            border: none;
            border-radius: 15px;
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }
        .progress-bar {
            height: 8px;
            border-radius: 4px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid mt-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="text-center">
                    <h1 class="display-4 text-primary">
                        <i class="fas fa-credit-card me-3"></i>
                        ระบบ Payment Gateway
                    </h1>
                    <p class="lead text-muted">ZapShop - ระบบชำระเงินครบครันด้วย Thai QR Payment</p>
                    <div class="progress mx-auto" style="width: 300px; height: 8px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                    </div>
                    <p class="text-success mt-2"><strong>✅ เสร็จสมบูรณ์ 100%</strong></p>
                </div>
            </div>
        </div>

        <!-- สถานะระบบ -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card feature-card bg-primary text-white text-center">
                    <div class="card-body">
                        <i class="fas fa-cog fa-3x mb-3"></i>
                        <h5>การตั้งค่า</h5>
                        <span class="badge bg-light text-primary status-badge">พร้อมใช้งาน</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card feature-card bg-success text-white text-center">
                    <div class="card-body">
                        <i class="fas fa-credit-card fa-3x mb-3"></i>
                        <h5>การชำระเงิน</h5>
                        <span class="badge bg-light text-success status-badge">พร้อมใช้งาน</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card feature-card bg-info text-white text-center">
                    <div class="card-body">
                        <i class="fas fa-webhook fa-3x mb-3"></i>
                        <h5>Callback</h5>
                        <span class="badge bg-light text-info status-badge">พร้อมใช้งาน</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card feature-card bg-warning text-white text-center">
                    <div class="card-body">
                        <i class="fas fa-chart-line fa-3x mb-3"></i>
                        <h5>รายงาน</h5>
                        <span class="badge bg-light text-warning status-badge">พร้อมใช้งาน</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ระบบหลัก -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card feature-card h-100">
                    <div class="card-header bg-primary text-white">
                        <h3><i class="fas fa-cogs me-2"></i>ระบบหลัก</h3>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <a href="payment_gateway_dashboard.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-tachometer-alt me-2 text-primary"></i>
                                    <strong>Dashboard หลัก</strong>
                                    <br>
                                    <small class="text-muted">หน้าจัดการระบบ Payment Gateway</small>
                                </div>
                                <i class="fas fa-chevron-right text-muted"></i>
                            </a>
                            
                            <a href="payment_gateway_config.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-cog me-2 text-primary"></i>
                                    <strong>ตั้งค่าระบบ</strong>
                                    <br>
                                    <small class="text-muted">จัดการ API Key, Provider, การเชื่อมต่อ</small>
                                </div>
                                <i class="fas fa-chevron-right text-muted"></i>
                            </a>
                            
                            <a href="payment_gateway_test.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-flask me-2 text-primary"></i>
                                    <strong>ทดสอบระบบ</strong>
                                    <br>
                                    <small class="text-muted">ทดสอบการเชื่อมต่อและการสร้างการชำระเงิน</small>
                                </div>
                                <i class="fas fa-chevron-right text-muted"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card feature-card h-100">
                    <div class="card-header bg-success text-white">
                        <h3><i class="fas fa-credit-card me-2"></i>การชำระเงิน</h3>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <a href="payment_gateway_test.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-credit-card me-2 text-success"></i>
                                    <strong>ทดสอบการชำระเงิน</strong>
                                    <br>
                                    <small class="text-muted">ทดสอบระบบการชำระเงิน</small>
                                </div>
                                <i class="fas fa-chevron-right text-muted"></i>
                            </a>
                            
                            <a href="checkout.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-shopping-cart me-2 text-success"></i>
                                    <strong>หน้าชำระเงิน</strong>
                                    <br>
                                    <small class="text-muted">ทดสอบการชำระเงินจริง</small>
                                </div>
                                <i class="fas fa-chevron-right text-muted"></i>
                            </a>
                            
                            <!-- ลิงก์ QR Payment ถูกลบออกแล้ว -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ระบบสนับสนุน -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card feature-card h-100">
                    <div class="card-header bg-info text-white">
                        <h3><i class="fas fa-bell me-2"></i>ระบบแจ้งเตือน</h3>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <a href="notification_system.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-bell me-2 text-info"></i>
                                    <strong>ระบบแจ้งเตือน</strong>
                                    <br>
                                    <small class="text-muted">ส่งการแจ้งเตือนผ่าน LINE, Email, SMS</small>
                                </div>
                                <i class="fas fa-chevron-right text-muted"></i>
                            </a>
                            
                            <a href="payment_callback_handler.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-webhook me-2 text-info"></i>
                                    <strong>Callback Handler</strong>
                                    <br>
                                    <small class="text-muted">จัดการ Callback จาก Payment Gateway</small>
                                </div>
                                <i class="fas fa-chevron-right text-muted"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card feature-card h-100">
                    <div class="card-header bg-warning text-white">
                        <h3><i class="fas fa-chart-line me-2"></i>รายงานและสถิติ</h3>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <a href="payment_reports.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-chart-bar me-2 text-warning"></i>
                                    <strong>รายงานการชำระเงิน</strong>
                                    <br>
                                    <small class="text-muted">สถิติและรายงานต่างๆ</small>
                                </div>
                                <i class="fas fa-chevron-right text-muted"></i>
                            </a>
                            
                            <a href="payment_gateway_api.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-code me-2 text-warning"></i>
                                    <strong>โค้ด API</strong>
                                    <br>
                                    <small class="text-muted">โค้ดสำหรับเชื่อมต่อ Payment Gateway</small>
                                </div>
                                <i class="fas fa-chevron-right text-muted"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- เอกสารและคำแนะนำ -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card feature-card h-100">
                    <div class="card-header bg-secondary text-white">
                        <h3><i class="fas fa-file-alt me-2"></i>เอกสารและคำแนะนำ</h3>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <a href="application_documents.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-file-alt me-2 text-secondary"></i>
                                    <strong>เอกสารสำหรับสมัคร</strong>
                                    <br>
                                    <small class="text-muted">เอกสารที่ต้องเตรียมสำหรับสมัครใช้บริการ</small>
                                </div>
                                <i class="fas fa-chevron-right text-muted"></i>
                            </a>
                            
                            <a href="email_templates.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-envelope me-2 text-secondary"></i>
                                    <strong>เทมเพลตอีเมล</strong>
                                    <br>
                                    <small class="text-muted">เทมเพลตสำหรับติดต่อธนาคาร</small>
                                </div>
                                <i class="fas fa-chevron-right text-muted"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card feature-card h-100">
                    <div class="card-header bg-dark text-white">
                        <h3><i class="fas fa-lightbulb me-2"></i>ขั้นตอนการใช้งาน</h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle me-2"></i>ขั้นตอนการใช้งาน:</h6>
                            <ol class="mb-0">
                                <li><strong>สมัครใช้บริการ:</strong> ติดต่อธนาคาร</li>
                                <li><strong>ตั้งค่าระบบ:</strong> ใส่ API Key และข้อมูล</li>
                                <li><strong>ทดสอบระบบ:</strong> ทดสอบการเชื่อมต่อ</li>
                                <li><strong>ใช้งานจริง:</strong> เปิดใช้งานในระบบ</li>
                            </ol>
                        </div>
                        <a href="payment_gateway_dashboard.php" class="btn btn-primary w-100">
                            <i class="fas fa-play me-2"></i>เริ่มใช้งาน
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- สถิติระบบ -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card feature-card">
                    <div class="card-header bg-success text-white">
                        <h3><i class="fas fa-chart-pie me-2"></i>สถิติระบบ</h3>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <div class="border rounded p-3">
                                    <h4 class="text-primary">9</h4>
                                    <p class="text-muted mb-0">ไฟล์หลัก</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border rounded p-3">
                                    <h4 class="text-success">100%</h4>
                                    <p class="text-muted mb-0">เสร็จสมบูรณ์</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border rounded p-3">
                                    <h4 class="text-info">3</h4>
                                    <p class="text-muted mb-0">Provider รองรับ</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border rounded p-3">
                                    <h4 class="text-warning">24/7</h4>
                                    <p class="text-muted mb-0">พร้อมใช้งาน</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <hr>
        
        <!-- Footer -->
        <div class="text-center mb-4">
            <p class="text-muted">
                <i class="fas fa-code me-2"></i>
                ระบบ Payment Gateway ของ ZapShop พร้อมใช้งานแล้ว!
            </p>
            <div class="btn-group" role="group">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-home me-2"></i>หน้าแรก
                </a>
                <a href="checkout.php" class="btn btn-primary">
                    <i class="fas fa-shopping-cart me-2"></i>ทดสอบการชำระเงิน
                </a>
                <a href="admin-dashboard.php" class="btn btn-info">
                    <i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
