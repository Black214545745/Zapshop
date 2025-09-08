<?php
/**
 * คู่มือการใช้งานระบบ Payment Gateway
 * คำแนะนำและขั้นตอนการใช้งานอย่างละเอียด
 */

session_start();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>คู่มือการใช้งาน Payment Gateway - ZapShop</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .manual-section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #007bff;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 1rem;
        }
        .warning-box {
            border-left: 4px solid #ffc107;
            background: #fff3cd;
            padding: 1rem;
            margin: 1rem 0;
        }
        .success-box {
            border-left: 4px solid #28a745;
            background: #d4edda;
            padding: 1rem;
            margin: 1rem 0;
        }
        .code-block {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 5px;
            padding: 1rem;
            font-family: 'Courier New', monospace;
            margin: 1rem 0;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-4">
        <!-- Header -->
        <div class="text-center mb-5">
            <h1 class="display-4 text-primary">
                <i class="fas fa-book me-3"></i>
                คู่มือการใช้งาน
            </h1>
            <p class="lead text-muted">ระบบ Payment Gateway ของ ZapShop</p>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                <strong>ระบบพร้อมใช้งานแล้ว!</strong> ใช้คู่มือนี้เพื่อเริ่มต้นใช้งาน
            </div>
        </div>

        <!-- สารบัญ -->
        <div class="manual-section p-4">
            <h2><i class="fas fa-list me-2"></i>สารบัญ</h2>
            <div class="row">
                <div class="col-md-6">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <a href="#overview" class="text-decoration-none">
                                <i class="fas fa-eye me-2"></i>ภาพรวมระบบ
                            </a>
                        </li>
                        <li class="list-group-item">
                            <a href="#setup" class="text-decoration-none">
                                <i class="fas fa-cog me-2"></i>การตั้งค่าเริ่มต้น
                            </a>
                        </li>
                        <li class="list-group-item">
                            <a href="#testing" class="text-decoration-none">
                                <i class="fas fa-flask me-2"></i>การทดสอบระบบ
                            </a>
                        </li>
                        <li class="list-group-item">
                            <a href="#usage" class="text-decoration-none">
                                <i class="fas fa-play me-2"></i>การใช้งานจริง
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <a href="#troubleshooting" class="text-decoration-none">
                                <i class="fas fa-tools me-2"></i>การแก้ไขปัญหา
                            </a>
                        </li>
                        <li class="list-group-item">
                            <a href="#faq" class="text-decoration-none">
                                <i class="fas fa-question-circle me-2"></i>คำถามที่พบบ่อย
                            </a>
                        </li>
                        <li class="list-group-item">
                            <a href="#support" class="text-decoration-none">
                                <i class="fas fa-headset me-2"></i>การขอความช่วยเหลือ
                            </a>
                        </li>
                        <li class="list-group-item">
                            <a href="#updates" class="text-decoration-none">
                                <i class="fas fa-sync me-2"></i>การอัปเดตระบบ
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- ภาพรวมระบบ -->
        <div id="overview" class="manual-section p-4">
            <h2><i class="fas fa-eye me-2"></i>ภาพรวมระบบ</h2>
            <p>ระบบ Payment Gateway ของ ZapShop เป็นระบบที่ครบครันสำหรับการชำระเงินผ่าน Thai QR Payment โดยมีฟีเจอร์หลักดังนี้:</p>
            
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-check-circle text-success me-2"></i>ฟีเจอร์หลัก</h5>
                    <ul>
                        <li>สร้างการชำระเงินแบบ Dynamic</li>
                        <li>รองรับหลาย Provider (TrueMoney, SCB, KBank)</li>
                        <li>ระบบ Callback และ Webhook</li>
                        <li>ระบบแจ้งเตือนอัตโนมัติ</li>
                        <li>รายงานและสถิติแบบ Real-time</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h5><i class="fas fa-shield-alt text-info me-2"></i>ความปลอดภัย</h5>
                    <ul>
                        <li>การตรวจสอบ Signature</li>
                        <li>การเข้ารหัสข้อมูล</li>
                        <li>ระบบ Log และ Audit Trail</li>
                        <li>การตรวจสอบสิทธิ์ผู้ใช้</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- การตั้งค่าเริ่มต้น -->
        <div id="setup" class="manual-section p-4">
            <h2><i class="fas fa-cog me-2"></i>การตั้งค่าเริ่มต้น</h2>
            
            <div class="warning-box">
                <h5><i class="fas fa-exclamation-triangle me-2"></i>ข้อควรระวัง</h5>
                <p>ก่อนเริ่มใช้งาน คุณต้องสมัครใช้บริการ Payment Gateway จากธนาคารก่อน</p>
            </div>

            <h4>ขั้นตอนที่ 1: สมัครใช้บริการ</h4>
            <div class="d-flex align-items-start mb-3">
                <div class="step-number">1</div>
                <div>
                    <h6>เตรียมเอกสาร</h6>
                    <p>ดูรายการเอกสารที่จำเป็นได้ที่ <a href="application_documents.php">เอกสารสำหรับสมัคร</a></p>
                </div>
            </div>

            <div class="d-flex align-items-start mb-3">
                <div class="step-number">2</div>
                <div>
                    <h6>ติดต่อธนาคาร</h6>
                    <p>ใช้เทมเพลตอีเมลจาก <a href="email_templates.php">เทมเพลตอีเมล</a></p>
                </div>
            </div>

            <div class="d-flex align-items-start mb-3">
                <div class="step-number">3</div>
                <div>
                    <h6>รอการอนุมัติ</h6>
                    <p>ใช้เวลาประมาณ 7-14 วัน</p>
                </div>
            </div>

            <h4>ขั้นตอนที่ 2: ตั้งค่าระบบ</h4>
            <div class="d-flex align-items-start mb-3">
                <div class="step-number">4</div>
                <div>
                    <h6>เข้าไปที่หน้าตั้งค่า</h6>
                    <p>ไปที่ <a href="payment_gateway_config.php">ตั้งค่า Payment Gateway</a></p>
                </div>
            </div>

            <div class="d-flex align-items-start mb-3">
                <div class="step-number">5</div>
                <div>
                    <h6>ใส่ข้อมูลที่ได้รับจากธนาคาร</h6>
                    <ul>
                        <li>Provider (เลือกธนาคาร)</li>
                        <li>API Key</li>
                        <li>API Secret</li>
                        <li>Merchant ID</li>
                        <li>Callback URL</li>
                    </ul>
                </div>
            </div>

            <div class="d-flex align-items-start mb-3">
                <div class="step-number">6</div>
                <div>
                    <h6>บันทึกการตั้งค่า</h6>
                    <p>คลิกปุ่ม "บันทึกการตั้งค่า"</p>
                </div>
            </div>
        </div>

        <!-- การทดสอบระบบ -->
        <div id="testing" class="manual-section p-4">
            <h2><i class="fas fa-flask me-2"></i>การทดสอบระบบ</h2>
            
            <div class="success-box">
                <h5><i class="fas fa-info-circle me-2"></i>คำแนะนำ</h5>
                <p>ควรทดสอบระบบในโหมด Sandbox ก่อนใช้งานจริง</p>
            </div>

            <h4>ขั้นตอนการทดสอบ</h4>
            
            <div class="d-flex align-items-start mb-3">
                <div class="step-number">1</div>
                <div>
                    <h6>ทดสอบการเชื่อมต่อ</h6>
                    <p>ไปที่ <a href="payment_gateway_test.php">ระบบทดสอบ Payment Gateway</a></p>
                    <p>คลิกปุ่ม "ทดสอบการเชื่อมต่อ" เพื่อตรวจสอบว่า API ทำงานได้หรือไม่</p>
                </div>
            </div>

            <div class="d-flex align-items-start mb-3">
                <div class="step-number">2</div>
                <div>
                                            <h6>ทดสอบการสร้างการชำระเงิน</h6>
                        <p>คลิกปุ่ม "ทดสอบการสร้างการชำระเงิน" เพื่อตรวจสอบว่าสร้างการชำระเงินได้หรือไม่</p>
                </div>
            </div>

            <div class="d-flex align-items-start mb-3">
                <div class="step-number">3</div>
                <div>
                    <h6>ทดสอบ Callback</h6>
                    <p>ไปที่ <a href="payment_callback_handler.php">Payment Callback Handler</a></p>
                    <p>คลิกปุ่ม "ทดสอบ Callback" เพื่อตรวจสอบว่าระบบรับข้อมูลได้หรือไม่</p>
                </div>
            </div>

            <div class="d-flex align-items-start mb-3">
                <div class="step-number">4</div>
                <div>
                    <h6>ทดสอบการแจ้งเตือน</h6>
                    <p>ไปที่ <a href="notification_system.php">ระบบแจ้งเตือน</a></p>
                    <p>ทดสอบการส่งการแจ้งเตือนผ่าน LINE, Email, และ SMS</p>
                </div>
            </div>

            <div class="d-flex align-items-start mb-3">
                <div class="step-number">5</div>
                <div>
                    <h6>ทดสอบการชำระเงิน</h6>
                    <p>ไปที่ <a href="checkout.php">หน้าชำระเงิน</a></p>
                                            <p>ทดสอบการสร้างคำสั่งซื้อและการแสดงการชำระเงิน</p>
                </div>
            </div>
        </div>

        <!-- การใช้งานจริง -->
        <div id="usage" class="manual-section p-4">
            <h2><i class="fas fa-play me-2"></i>การใช้งานจริง</h2>
            
            <div class="warning-box">
                <h5><i class="fas fa-exclamation-triangle me-2"></i>ข้อควรระวัง</h5>
                <p>เมื่อใช้งานจริง ต้องเปลี่ยนจากโหมด Sandbox เป็นโหมด Production</p>
            </div>

            <h4>ขั้นตอนการใช้งานจริง</h4>
            
            <div class="d-flex align-items-start mb-3">
                <div class="step-number">1</div>
                <div>
                    <h6>เปลี่ยนเป็นโหมด Production</h6>
                    <p>ไปที่ <a href="payment_gateway_config.php">ตั้งค่า Payment Gateway</a></p>
                    <p>ยกเลิกการเลือก "Sandbox Mode"</p>
                </div>
            </div>

            <div class="d-flex align-items-start mb-3">
                <div class="step-number">2</div>
                <div>
                    <h6>ทดสอบระบบในโหมด Production</h6>
                    <p>ทำการทดสอบทั้งหมดอีกครั้งในโหมด Production</p>
                </div>
            </div>

            <div class="d-flex align-items-start mb-3">
                <div class="step-number">3</div>
                <div>
                    <h6>เริ่มใช้งานจริง</h6>
                    <p>ระบบพร้อมใช้งานจริงแล้ว!</p>
                </div>
            </div>

            <h4>การตรวจสอบสถานะ</h4>
            <p>คุณสามารถตรวจสอบสถานะการชำระเงินได้ที่:</p>
            <ul>
                <li><a href="payment_reports.php">รายงานการชำระเงิน</a> - ดูสถิติและรายงานต่างๆ</li>
                <li><a href="payment_gateway_dashboard.php">Payment Gateway Dashboard</a> - ดูสถานะโดยรวมของระบบ</li>
            </ul>
        </div>

        <!-- การแก้ไขปัญหา -->
        <div id="troubleshooting" class="manual-section p-4">
            <h2><i class="fas fa-tools me-2"></i>การแก้ไขปัญหา</h2>
            
            <h4>ปัญหาที่พบบ่อย</h4>
            
            <div class="card mb-3">
                <div class="card-header">
                    <h6><i class="fas fa-exclamation-triangle text-warning me-2"></i>การชำระเงินโหลดช้า</h6>
                </div>
                <div class="card-body">
                    <p><strong>สาเหตุ:</strong> การเชื่อมต่ออินเทอร์เน็ตช้า หรือ API ทำงานช้า</p>
                    <p><strong>วิธีแก้:</strong></p>
                    <ul>
                        <li>ตรวจสอบการเชื่อมต่ออินเทอร์เน็ต</li>
                        <li>ลองใช้วิธีการชำระเงินอื่น</li>
                        <li>ตรวจสอบสถานะ API ของ Payment Gateway</li>
                    </ul>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <h6><i class="fas fa-exclamation-triangle text-warning me-2"></i>การเชื่อมต่อ API ล้มเหลว</h6>
                </div>
                <div class="card-body">
                    <p><strong>สาเหตุ:</strong> API Key ไม่ถูกต้อง หรือ API ไม่อยู่ในโหมดที่ถูกต้อง</p>
                    <p><strong>วิธีแก้:</strong></p>
                    <ul>
                        <li>ตรวจสอบ API Key และ Secret</li>
                        <li>ตรวจสอบว่าใช้โหมด Sandbox หรือ Production ถูกต้อง</li>
                        <li>ตรวจสอบสถานะ API ของ Payment Gateway</li>
                    </ul>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <h6><i class="fas fa-exclamation-triangle text-warning me-2"></i>Callback ไม่ทำงาน</h6>
                </div>
                <div class="card-body">
                    <p><strong>สาเหตุ:</strong> Callback URL ไม่ถูกต้อง หรือเซิร์ฟเวอร์ไม่สามารถเข้าถึงได้</p>
                    <p><strong>วิธีแก้:</strong></p>
                    <ul>
                        <li>ตรวจสอบ Callback URL ในการตั้งค่า</li>
                        <li>ตรวจสอบว่าเซิร์ฟเวอร์สามารถเข้าถึงได้จากภายนอก</li>
                        <li>ตรวจสอบไฟร์วอลล์และการตั้งค่าเซิร์ฟเวอร์</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- คำถามที่พบบ่อย -->
        <div id="faq" class="manual-section p-4">
            <h2><i class="fas fa-question-circle me-2"></i>คำถามที่พบบ่อย</h2>
            
            <div class="accordion" id="faqAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq1">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1">
                            ระบบรองรับธนาคารอะไรบ้าง?
                        </button>
                    </h2>
                    <div id="collapse1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            ระบบรองรับธนาคารหลัก 3 แห่ง:
                            <ul>
                                <li><strong>TrueMoney</strong> - ข้อกำหนดไม่เข้มงวด ค่าธรรมเนียมต่ำ</li>
                                <li><strong>SCB Easy Pay</strong> - มีชื่อเสียงและเชื่อถือได้</li>
                                <li><strong>KBank Payment Gateway</strong> - รองรับธุรกิจขนาดใหญ่</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq2">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2">
                            ใช้เวลานานเท่าไหร่ในการสมัคร?
                        </button>
                    </h2>
                    <div id="collapse2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            โดยทั่วไปใช้เวลาประมาณ 7-14 วัน ขึ้นอยู่กับ:
                            <ul>
                                <li>ความสมบูรณ์ของเอกสาร</li>
                                <li>ธนาคารที่เลือก</li>
                                <li>ประเภทของธุรกิจ</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq3">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse3">
                            ค่าธรรมเนียมเป็นอย่างไร?
                        </button>
                    </h2>
                    <div id="collapse3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            ค่าธรรมเนียมขึ้นอยู่กับธนาคารที่เลือก:
                            <ul>
                                <li><strong>TrueMoney:</strong> 1.5-2.5% ต่อรายการ</li>
                                <li><strong>SCB Easy Pay:</strong> 2-3% ต่อรายการ</li>
                                <li><strong>KBank:</strong> 2-3.5% ต่อรายการ</li>
                            </ul>
                            <p class="text-muted">* ราคาอาจเปลี่ยนแปลงได้ โปรดตรวจสอบกับธนาคารโดยตรง</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- การขอความช่วยเหลือ -->
        <div id="support" class="manual-section p-4">
            <h2><i class="fas fa-headset me-2"></i>การขอความช่วยเหลือ</h2>
            
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-book text-primary me-2"></i>เอกสารเพิ่มเติม</h5>
                    <ul>
                        <li><a href="application_documents.php">เอกสารสำหรับสมัคร</a></li>
                        <li><a href="email_templates.php">เทมเพลตอีเมล</a></li>
                        <li><a href="payment_gateway_api.php">โค้ด API</a></li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h5><i class="fas fa-tools text-info me-2"></i>เครื่องมือทดสอบ</h5>
                    <ul>
                        <li><a href="payment_gateway_test.php">ทดสอบการเชื่อมต่อ</a></li>
                        <li><a href="payment_gateway_test.php">ทดสอบ Payment Gateway</a></li>
                        <li><a href="payment_callback_handler.php">ทดสอบ Callback</a></li>
                    </ul>
                </div>
            </div>

            <div class="alert alert-info mt-3">
                <h6><i class="fas fa-info-circle me-2"></i>หากต้องการความช่วยเหลือเพิ่มเติม</h6>
                <p>1. ตรวจสอบคู่มือนี้ให้ครบถ้วน</p>
                <p>2. ใช้เครื่องมือทดสอบเพื่อตรวจสอบปัญหา</p>
                <p>3. ตรวจสอบไฟล์ Log ในโฟลเดอร์ <code>logs/</code></p>
                <p>4. ติดต่อทีมพัฒนา หรือสร้าง Issue ในระบบ</p>
            </div>
        </div>

        <!-- การอัปเดตระบบ -->
        <div id="updates" class="manual-section p-4">
            <h2><i class="fas fa-sync me-2"></i>การอัปเดตระบบ</h2>
            
            <div class="success-box">
                <h5><i class="fas fa-info-circle me-2"></i>ข้อมูลการอัปเดต</h5>
                <p>ระบบ Payment Gateway จะได้รับการอัปเดตเป็นประจำเพื่อเพิ่มฟีเจอร์ใหม่และแก้ไขปัญหา</p>
            </div>

            <h4>การอัปเดตอัตโนมัติ</h4>
            <p>ระบบจะตรวจสอบการอัปเดตอัตโนมัติและแจ้งเตือนเมื่อมีเวอร์ชันใหม่</p>

            <h4>การอัปเดตด้วยตนเอง</h4>
            <p>หากต้องการอัปเดตด้วยตนเอง:</p>
            <ol>
                <li>ตรวจสอบเวอร์ชันปัจจุบันใน <a href="payment_gateway_dashboard.php">Dashboard</a></li>
                <li>ดาวน์โหลดไฟล์อัปเดตล่าสุด</li>
                <li>ทำการ Backup ระบบก่อนอัปเดต</li>
                <li>อัปเดตไฟล์ตามคำแนะนำ</li>
                <li>ทดสอบระบบหลังอัปเดต</li>
            </ol>
        </div>

        <hr>
        
        <!-- Footer -->
        <div class="text-center mb-4">
            <p class="text-muted">
                <i class="fas fa-code me-2"></i>
                คู่มือการใช้งานระบบ Payment Gateway ของ ZapShop
            </p>
            <div class="btn-group" role="group">
                <a href="payment_gateway_summary.php" class="btn btn-secondary">
                    <i class="fas fa-home me-2"></i>หน้าสรุป
                </a>
                <a href="payment_gateway_dashboard.php" class="btn btn-primary">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </a>
                <a href="checkout.php" class="btn btn-success">
                    <i class="fas fa-shopping-cart me-2"></i>ทดสอบระบบ
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
