<?php
/**
 * เอกสารสำหรับสมัครใช้บริการ Payment Gateway
 * ใช้สำหรับเตรียมเอกสารก่อนติดต่อธนาคาร
 */

// ข้อมูลเอกสารที่ต้องเตรียม
$requiredDocuments = [
    'business_registration' => [
        'name' => 'หนังสือรับรองการจดทะเบียนนิติบุคคล',
        'description' => 'หนังสือรับรองการจดทะเบียนนิติบุคคล (ไม่เกิน 3 เดือน)',
        'required' => true,
        'notes' => 'ถ้าเป็นร้านค้าขนาดเล็ก อาจใช้สำเนาบัตรประชาชนแทน'
    ],
    'business_license' => [
        'name' => 'ใบอนุญาตประกอบธุรกิจ',
        'description' => 'ใบอนุญาตประกอบธุรกิจที่เกี่ยวข้อง (ถ้ามี)',
        'required' => false,
        'notes' => 'ขึ้นอยู่กับประเภทธุรกิจ'
    ],
    'bank_account' => [
        'name' => 'สำเนาบัญชีธนาคาร',
        'description' => 'สำเนาบัญชีธนาคารธุรกิจ (ไม่เกิน 3 เดือน)',
        'required' => true,
        'notes' => 'ต้องเป็นบัญชีธุรกิจ ไม่ใช่บัญชีส่วนตัว'
    ],
    'id_card' => [
        'name' => 'สำเนาบัตรประชาชน',
        'description' => 'สำเนาบัตรประชาชนของผู้ถือหุ้น/เจ้าของธุรกิจ',
        'required' => true,
        'notes' => 'ต้องชัดเจน อ่านได้'
    ],
    'house_registration' => [
        'name' => 'สำเนาทะเบียนบ้าน',
        'description' => 'สำเนาทะเบียนบ้านของผู้ถือหุ้น/เจ้าของธุรกิจ',
        'required' => true,
        'notes' => 'ต้องชัดเจน อ่านได้'
    ],
    'website_info' => [
        'name' => 'ข้อมูลเว็บไซต์',
        'description' => 'URL เว็บไซต์ และข้อมูลธุรกิจออนไลน์',
        'required' => true,
        'notes' => 'เว็บไซต์ต้องมี SSL Certificate และข้อมูลธุรกิจครบถ้วน'
    ],
    'business_plan' => [
        'name' => 'แผนธุรกิจ',
        'description' => 'แผนธุรกิจและรายได้ที่คาดการณ์',
        'required' => false,
        'notes' => 'ช่วยในการพิจารณาอนุมัติ'
    ],
    'financial_statement' => [
        'name' => 'งบการเงิน',
        'description' => 'งบการเงินย้อนหลัง 6 เดือน (ถ้ามี)',
        'required' => false,
        'notes' => 'แสดงความสามารถในการทำธุรกิจ'
    ]
];

// ข้อมูลธนาคารที่แนะนำให้ติดต่อ
$recommendedBanks = [
    'truemoney' => [
        'name' => 'TrueMoney',
        'priority' => 'สูงสุด (แนะนำให้เริ่มต้น)',
        'reason' => 'ค่าธรรมเนียมต่ำ ข้อกำหนดไม่เข้มงวด ใช้งานง่าย',
        'contact_method' => 'โทรศัพท์',
        'contact_info' => '02-900-9000',
        'best_time' => 'จันทร์-ศุกร์ 9:00-17:00 น.',
        'preparation' => [
            'เตรียมข้อมูลธุรกิจให้ชัดเจน',
            'บอกว่าต้องการ Dynamic QR Code สำหรับ Thai QR Payment',
            'ถามเรื่องค่าธรรมเนียมและข้อกำหนด',
            'ถามเรื่องระบบทดสอบ (Sandbox)',
            'ถามเรื่องระยะเวลาการอนุมัติ'
        ]
    ],
    'scb' => [
        'name' => 'SCB Easy Pay',
        'priority' => 'สูง (ทางเลือกที่ 2)',
        'reason' => 'มี API ที่ชัดเจน ระบบทดสอบดี',
        'contact_method' => 'อีเมล',
        'contact_info' => 'easypay@scb.co.th',
        'best_time' => 'จันทร์-ศุกร์ 8:30-16:30 น.',
        'preparation' => [
            'เขียนอีเมลเป็นทางการ',
            'ระบุความต้องการ Dynamic QR Code',
            'แนบเอกสารธุรกิจที่จำเป็น',
            'ถามเรื่องค่าธรรมเนียมและข้อกำหนด',
            'ถามเรื่องระยะเวลาการอนุมัติ'
        ]
    ],
    'kbank' => [
        'name' => 'KBank Payment Gateway',
        'priority' => 'ปานกลาง (สำหรับธุรกิจขนาดใหญ่)',
        'reason' => 'ระบบครบถ้วน แต่ข้อกำหนดเข้มงวด',
        'contact_method' => 'โทรศัพท์ + อีเมล',
        'contact_info' => '02-888-8888, payment@kasikornbank.com',
        'best_time' => 'จันทร์-ศุกร์ 8:00-17:00 น.',
        'preparation' => [
            'เตรียมเอกสารธุรกิจครบถ้วน',
            'มีรายได้ประจำเดือน 50,000+ บาท',
            'มีเว็บไซต์ที่มี SSL Certificate',
            'เตรียมแผนธุรกิจและงบการเงิน'
        ]
    ]
];

// แสดงเอกสารที่ต้องเตรียม
echo "<h1>เอกสารสำหรับสมัครใช้บริการ Payment Gateway</h1>";
echo "<p><strong>หมายเหตุ:</strong> เตรียมเอกสารเหล่านี้ให้ครบถ้วนก่อนติดต่อธนาคาร</p>";

echo "<h2>เอกสารที่ต้องเตรียม</h2>";
foreach ($requiredDocuments as $key => $document) {
    $requiredBadge = $document['required'] ? '<span class="badge bg-danger">จำเป็น</span>' : '<span class="badge bg-warning">ไม่จำเป็น</span>';
    
    echo "<div class='card mb-3'>";
    echo "<div class='card-header d-flex justify-content-between align-items-center'>";
    echo "<h5>{$document['name']}</h5>";
    echo $requiredBadge;
    echo "</div>";
    echo "<div class='card-body'>";
    echo "<p><strong>รายละเอียด:</strong> {$document['description']}</p>";
    echo "<p><strong>หมายเหตุ:</strong> {$document['notes']}</p>";
    echo "</div>";
    echo "</div>";
}

echo "<hr>";

// แสดงธนาคารที่แนะนำ
echo "<h2>ธนาคารที่แนะนำให้ติดต่อ</h2>";
foreach ($recommendedBanks as $key => $bank) {
    echo "<div class='card mb-4'>";
    echo "<div class='card-header'>";
    echo "<h3>{$bank['name']} - {$bank['priority']}</h3>";
    echo "</div>";
    echo "<div class='card-body'>";
    
    echo "<p><strong>เหตุผลที่แนะนำ:</strong> {$bank['reason']}</p>";
    echo "<p><strong>วิธีติดต่อ:</strong> {$bank['contact_method']}</p>";
    echo "<p><strong>ข้อมูลติดต่อ:</strong> {$bank['contact_info']}</p>";
    echo "<p><strong>เวลาที่เหมาะสม:</strong> {$bank['best_time']}</p>";
    
    echo "<h5>การเตรียมตัว:</h5>";
    echo "<ul>";
    foreach ($bank['preparation'] as $prep) {
        echo "<li>{$prep}</li>";
    }
    echo "</ul>";
    
    echo "</div>";
    echo "</div>";
}

echo "<hr>";

// แสดงขั้นตอนการติดต่อ
echo "<h2>ขั้นตอนการติดต่อธนาคาร</h2>";
echo "<div class='alert alert-info'>";
echo "<h4>ขั้นตอนที่ 1: เตรียมเอกสาร</h4>";
echo "<ul>";
echo "<li>รวบรวมเอกสารที่จำเป็นทั้งหมด</li>";
echo "<li>ตรวจสอบความถูกต้องและความชัดเจน</li>";
echo "<li>เตรียมข้อมูลธุรกิจให้ครบถ้วน</li>";
echo "</ul>";
echo "</div>";

echo "<div class='alert alert-warning'>";
echo "<h4>ขั้นตอนที่ 2: ติดต่อธนาคาร</h4>";
echo "<ul>";
echo "<li>โทรหรืออีเมลไปยังธนาคารที่เลือก</li>";
echo "<li>บอกว่าต้องการ Dynamic QR Code สำหรับ Thai QR Payment</li>";
echo "<li>ถามเรื่องค่าธรรมเนียมและข้อกำหนด</li>";
echo "<li>ถามเรื่องระยะเวลาการอนุมัติ</li>";
echo "</ul>";
echo "</div>";

echo "<div class='alert alert-success'>";
echo "<h4>ขั้นตอนที่ 3: ส่งเอกสาร</h4>";
echo "<ul>";
echo "<li>ส่งเอกสารตามที่ธนาคารต้องการ</li>";
echo "<li>รอการตรวจสอบและอนุมัติ</li>";
echo "<li>เตรียมรับข้อมูล API เมื่อได้รับการอนุมัติ</li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<h2>คำแนะนำเพิ่มเติม</h2>";
echo "<div class='alert alert-primary'>";
echo "<ul>";
echo "<li><strong>เริ่มจาก TrueMoney:</strong> เพราะข้อกำหนดไม่เข้มงวด และค่าธรรมเนียมต่ำ</li>";
echo "<li><strong>เตรียมข้อมูลให้ชัดเจน:</strong> ธนาคารจะถามรายละเอียดธุรกิจ</li>";
echo "<li><strong>ถามเรื่องระบบทดสอบ:</strong> ใช้ Sandbox ก่อนเพื่อทดสอบ</li>";
echo "<li><strong>เปรียบเทียบหลายธนาคาร:</strong> เพื่อเลือกที่เหมาะสมที่สุด</li>";
echo "</ul>";
echo "</div>";

echo "<p><a href='payment_gateway_info.php' class='btn btn-primary'>ดูข้อมูล Payment Gateway</a></p>";
echo "<p><a href='test_qr_fake_complete.php' class='btn btn-secondary'>กลับไปหน้าทดสอบ</a></p>";
?>
