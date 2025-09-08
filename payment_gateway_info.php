<?php
/**
 * ข้อมูล Payment Gateway สำหรับ Thai QR Payment
 * ใช้สำหรับศึกษาข้อมูลและติดต่อธนาคาร
 */

// ข้อมูลธนาคารและ Payment Gateway ที่รองรับ Thai QR Payment
$paymentGateways = [
    'kbank' => [
        'name' => 'KBank Payment Gateway',
        'website' => 'https://www.kasikornbank.com/th/business/sme/merchant-service/payment-gateway',
        'features' => [
            'รองรับ Thai QR Payment ครบถ้วน',
            'มี API ที่ชัดเจนและครบถ้วน',
            'รองรับ Dynamic QR Code',
            'มีระบบทดสอบ (Sandbox)',
            'ค่าธรรมเนียม: 2.5-3.5%'
        ],
        'requirements' => [
            'มีบัญชีธุรกิจกับ KBank',
            'มีรายได้ประจำเดือน 50,000+ บาท',
            'มีเอกสารธุรกิจครบถ้วน',
            'มีเว็บไซต์ที่มี SSL Certificate'
        ],
        'contact' => [
            'phone' => '02-888-8888',
            'email' => 'payment@kasikornbank.com',
            'line' => '@kbankpayment'
        ]
    ],
    
    'scb' => [
        'name' => 'SCB Easy Pay',
        'website' => 'https://www.scb.co.th/th/business-banking/merchant-services/easy-pay',
        'features' => [
            'ใช้งานง่าย มี API ที่ชัดเจน',
            'รองรับ Thai QR Payment',
            'มีระบบทดสอบ',
            'ค่าธรรมเนียม: 2.5-3.0%'
        ],
        'requirements' => [
            'มีบัญชีธุรกิจกับ SCB',
            'มีรายได้ประจำเดือน 30,000+ บาท',
            'มีเอกสารธุรกิจครบถ้วน'
        ],
        'contact' => [
            'phone' => '02-777-7777',
            'email' => 'easypay@scb.co.th',
            'line' => '@scbeasypay'
        ]
    ],
    
    'truemoney' => [
        'name' => 'TrueMoney',
        'website' => 'https://truemoney.com/merchant',
        'features' => [
            'ค่าธรรมเนียมต่ำ ใช้งานง่าย',
            'รองรับ Thai QR Payment',
            'มีระบบทดสอบ',
            'ค่าธรรมเนียม: 1.5-2.5%'
        ],
        'requirements' => [
            'มีบัญชีธุรกิจ',
            'มีรายได้ประจำเดือน 20,000+ บาท',
            'มีเอกสารธุรกิจครบถ้วน'
        ],
        'contact' => [
            'phone' => '02-900-9000',
            'email' => 'merchant@truemoney.com',
            'line' => '@truemoney'
        ]
    ],
    
    'lnwpay' => [
        'name' => 'LnwPay (Line Pay)',
        'website' => 'https://pay.line.me/th/',
        'features' => [
            'ใช้งานง่าย มีลูกค้า Line จำนวนมาก',
            'รองรับ Thai QR Payment',
            'มีระบบทดสอบ',
            'ค่าธรรมเนียม: 2.5-3.5%'
        ],
        'requirements' => [
            'มีบัญชีธุรกิจ',
            'มีรายได้ประจำเดือน 25,000+ บาท',
            'มีเอกสารธุรกิจครบถ้วน'
        ],
        'contact' => [
            'phone' => '02-123-4567',
            'email' => 'merchant@linepay.co.th',
            'line' => '@linepayth'
        ]
    ]
];

// แสดงข้อมูล Payment Gateway
echo "<h1>ข้อมูล Payment Gateway สำหรับ Thai QR Payment</h1>";
echo "<p><strong>หมายเหตุ:</strong> ข้อมูลนี้ใช้สำหรับการศึกษาข้อมูลเท่านั้น กรุณาติดต่อธนาคารโดยตรงเพื่อข้อมูลที่ถูกต้อง</p>";

foreach ($paymentGateways as $key => $gateway) {
    echo "<div class='card mb-4'>";
    echo "<div class='card-header'>";
    echo "<h3>{$gateway['name']}</h3>";
    echo "</div>";
    echo "<div class='card-body'>";
    
    echo "<h4>คุณสมบัติ:</h4>";
    echo "<ul>";
    foreach ($gateway['features'] as $feature) {
        echo "<li>{$feature}</li>";
    }
    echo "</ul>";
    
    echo "<h4>ข้อกำหนด:</h4>";
    echo "<ul>";
    foreach ($gateway['requirements'] as $requirement) {
        echo "<li>{$requirement}</li>";
    }
    echo "</ul>";
    
    echo "<h4>ข้อมูลติดต่อ:</h4>";
    echo "<ul>";
    foreach ($gateway['contact'] as $type => $value) {
        echo "<li><strong>" . ucfirst($type) . ":</strong> {$value}</li>";
    }
    echo "</ul>";
    
    echo "<p><a href='{$gateway['website']}' target='_blank' class='btn btn-primary'>เว็บไซต์</a></p>";
    echo "</div>";
    echo "</div>";
}

echo "<hr>";
echo "<h2>ขั้นตอนการสมัครใช้บริการ</h2>";
echo "<ol>";
echo "<li><strong>ติดต่อธนาคาร:</strong> โทรหรืออีเมลไปยังธนาคารที่คุณมีบัญชีธุรกิจอยู่</li>";
echo "<li><strong>ระบุความต้องการ:</strong> บอกว่าต้องการ Dynamic QR Code สำหรับ Thai QR Payment</li>";
echo "<li><strong>ส่งเอกสาร:</strong> ส่งเอกสารธุรกิจตามที่ธนาคารต้องการ</li>";
echo "<li><strong>รอการอนุมัติ:</strong> ธนาคารจะตรวจสอบและอนุมัติภายใน 7-14 วัน</li>";
echo "<li><strong>รับข้อมูล API:</strong> เมื่อได้รับการอนุมัติ จะได้รับ API Documentation, API Key, และ Secret</li>";
echo "</ol>";

echo "<h2>คำแนะนำสำหรับ ZapShop</h2>";
echo "<p><strong>เริ่มต้น:</strong> แนะนำให้เริ่มจาก TrueMoney เพราะค่าธรรมเนียมต่ำและใช้งานง่าย</p>";
echo "<p><strong>ขยายธุรกิจ:</strong> เมื่อธุรกิจเติบโตแล้ว ค่อยขยายไปยังธนาคารอื่นๆ</p>";
echo "<p><strong>ระบบทดสอบ:</strong> ใช้ระบบทดสอบ (Sandbox) ก่อน เพื่อทดสอบการทำงาน</p>";
?>
