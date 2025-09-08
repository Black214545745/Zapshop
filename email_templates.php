<?php
/**
 * เทมเพลตอีเมลสำหรับติดต่อธนาคาร
 * ใช้สำหรับส่งอีเมลสมัครใช้บริการ Payment Gateway
 */

// เทมเพลตอีเมลสำหรับ TrueMoney
$trueMoneyTemplate = [
    'subject' => 'สมัครใช้บริการ TrueMoney Payment Gateway - Dynamic QR Code',
    'body' => 'เรียน คุณ/ท่าน ที่ดูแลเรื่อง Payment Gateway

ดิฉัน/ผมชื่อ [ชื่อ-นามสกุล] เป็นเจ้าของธุรกิจ [ชื่อธุรกิจ] 
ที่อยู่: [ที่อยู่ธุรกิจ]
โทรศัพท์: [เบอร์โทรศัพท์]
อีเมล: [อีเมล]

ดิฉัน/ผมมีความประสงค์จะสมัครใช้บริการ TrueMoney Payment Gateway 
เพื่อสร้าง Dynamic QR Code สำหรับ Thai QR Payment ในเว็บไซต์ [URL เว็บไซต์]

รายละเอียดธุรกิจ:
- ประเภทธุรกิจ: [ประเภท]
- ผลิตภัณฑ์/บริการ: [รายละเอียด]
- รายได้เฉลี่ยต่อเดือน: [จำนวนเงิน] บาท
- จำนวนลูกค้าต่อเดือน: [จำนวนคน]

ดิฉัน/ผมต้องการทราบข้อมูลเพิ่มเติมดังนี้:
1. ค่าธรรมเนียมการใช้งาน
2. ข้อกำหนดและเอกสารที่ต้องส่ง
3. ระยะเวลาการอนุมัติ
4. ระบบทดสอบ (Sandbox) มีหรือไม่
5. การสนับสนุนทางเทคนิค

กรุณาส่งข้อมูลและแบบฟอร์มสมัครมาที่อีเมลนี้ หรือติดต่อกลับมาที่เบอร์โทรศัพท์ข้างต้น

ขอบคุณล่วงหน้าสำหรับการพิจารณา

ขอแสดงความนับถือ
[ชื่อ-นามสกุล]
[ตำแหน่ง]
[ชื่อธุรกิจ]'
];

// เทมเพลตอีเมลสำหรับ SCB
$scbTemplate = [
    'subject' => 'สมัครใช้บริการ SCB Easy Pay - Dynamic QR Code สำหรับ Thai QR Payment',
    'body' => 'เรียน คุณ/ท่าน ที่ดูแลเรื่อง SCB Easy Pay

ดิฉัน/ผมชื่อ [ชื่อ-นามสกุล] เป็นเจ้าของธุรกิจ [ชื่อธุรกิจ] 
ที่อยู่: [ที่อยู่ธุรกิจ]
โทรศัพท์: [เบอร์โทรศัพท์]
อีเมล: [อีเมล]

ดิฉัน/ผมมีความประสงค์จะสมัครใช้บริการ SCB Easy Pay 
เพื่อสร้าง Dynamic QR Code สำหรับ Thai QR Payment ในเว็บไซต์ [URL เว็บไซต์]

รายละเอียดธุรกิจ:
- ประเภทธุรกิจ: [ประเภท]
- ผลิตภัณฑ์/บริการ: [รายละเอียด]
- รายได้เฉลี่ยต่อเดือน: [จำนวนเงิน] บาท
- จำนวนลูกค้าต่อเดือน: [จำนวนคน]

ดิฉัน/ผมต้องการทราบข้อมูลเพิ่มเติมดังนี้:
1. ค่าธรรมเนียมการใช้งาน
2. ข้อกำหนดและเอกสารที่ต้องส่ง
3. ระยะเวลาการอนุมัติ
4. ระบบทดสอบ (Sandbox) มีหรือไม่
5. การสนับสนุนทางเทคนิค
6. เอกสาร API และตัวอย่างการใช้งาน

กรุณาส่งข้อมูลและแบบฟอร์มสมัครมาที่อีเมลนี้ หรือติดต่อกลับมาที่เบอร์โทรศัพท์ข้างต้น

ขอบคุณล่วงหน้าสำหรับการพิจารณา

ขอแสดงความนับถือ
[ชื่อ-นามสกุล]
[ตำแหน่ง]
[ชื่อธุรกิจ]'
];

// เทมเพลตอีเมลสำหรับ KBank
$kbankTemplate = [
    'subject' => 'สมัครใช้บริการ KBank Payment Gateway - Dynamic QR Code สำหรับ Thai QR Payment',
    'body' => 'เรียน คุณ/ท่าน ที่ดูแลเรื่อง KBank Payment Gateway

ดิฉัน/ผมชื่อ [ชื่อ-นามสกุล] เป็นเจ้าของธุรกิจ [ชื่อธุรกิจ] 
ที่อยู่: [ที่อยู่ธุรกิจ]
โทรศัพท์: [เบอร์โทรศัพท์]
อีเมล: [อีเมล]

ดิฉัน/ผมมีความประสงค์จะสมัครใช้บริการ KBank Payment Gateway 
เพื่อสร้าง Dynamic QR Code สำหรับ Thai QR Payment ในเว็บไซต์ [URL เว็บไซต์]

รายละเอียดธุรกิจ:
- ประเภทธุรกิจ: [ประเภท]
- ผลิตภัณฑ์/บริการ: [รายละเอียด]
- รายได้เฉลี่ยต่อเดือน: [จำนวนเงิน] บาท
- จำนวนลูกค้าต่อเดือน: [จำนวนคน]
- จำนวนพนักงาน: [จำนวนคน]

ดิฉัน/ผมต้องการทราบข้อมูลเพิ่มเติมดังนี้:
1. ค่าธรรมเนียมการใช้งาน
2. ข้อกำหนดและเอกสารที่ต้องส่ง
3. ระยะเวลาการอนุมัติ
4. ระบบทดสอบ (Sandbox) มีหรือไม่
5. การสนับสนุนทางเทคนิค
6. เอกสาร API และตัวอย่างการใช้งาน
7. การฝึกอบรมและสนับสนุน

กรุณาส่งข้อมูลและแบบฟอร์มสมัครมาที่อีเมลนี้ หรือติดต่อกลับมาที่เบอร์โทรศัพท์ข้างต้น

ขอบคุณล่วงหน้าสำหรับการพิจารณา

ขอแสดงความนับถือ
[ชื่อ-นามสกุล]
[ตำแหน่ง]
[ชื่อธุรกิจ]'
];

// แสดงเทมเพลตอีเมล
echo "<h1>เทมเพลตอีเมลสำหรับติดต่อธนาคาร</h1>";
echo "<p><strong>หมายเหตุ:</strong> ใช้เทมเพลตเหล่านี้เป็นแนวทางในการเขียนอีเมลติดต่อธนาคาร</p>";

// TrueMoney Template
echo "<div class='card mb-4'>";
echo "<div class='card-header'>";
echo "<h3>TrueMoney - เทมเพลตอีเมล</h3>";
echo "</div>";
echo "<div class='card-body'>";
echo "<h5>หัวข้ออีเมล:</h5>";
echo "<p><code>{$trueMoneyTemplate['subject']}</code></p>";
echo "<h5>เนื้อหาอีเมล:</h5>";
echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
echo htmlspecialchars($trueMoneyTemplate['body']);
echo "</pre>";
echo "<button class='btn btn-primary' onclick='copyToClipboard(this.previousElementSibling.textContent)'>คัดลอกเนื้อหา</button>";
echo "</div>";
echo "</div>";

// SCB Template
echo "<div class='card mb-4'>";
echo "<div class='card-header'>";
echo "<h3>SCB Easy Pay - เทมเพลตอีเมล</h3>";
echo "</div>";
echo "<div class='card-body'>";
echo "<h5>หัวข้ออีเมล:</h5>";
echo "<p><code>{$scbTemplate['subject']}</code></p>";
echo "<h5>เนื้อหาอีเมล:</h5>";
echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
echo htmlspecialchars($scbTemplate['body']);
echo "</pre>";
echo "<button class='btn btn-primary' onclick='copyToClipboard(this.previousElementSibling.textContent)'>คัดลอกเนื้อหา</button>";
echo "</div>";
echo "</div>";

// KBank Template
echo "<div class='card mb-4'>";
echo "<div class='card-header'>";
echo "<h3>KBank Payment Gateway - เทมเพลตอีเมล</h3>";
echo "</div>";
echo "<div class='card-body'>";
echo "<h5>หัวข้ออีเมล:</h5>";
echo "<p><code>{$kbankTemplate['subject']}</code></p>";
echo "<h5>เนื้อหาอีเมล:</h5>";
echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
echo htmlspecialchars($kbankTemplate['body']);
echo "</pre>";
echo "<button class='btn btn-primary' onclick='copyToClipboard(this.previousElementSibling.textContent)'>คัดลอกเนื้อหา</button>";
echo "</div>";
echo "</div>";

echo "<hr>";

// แสดงคำแนะนำการเขียนอีเมล
echo "<h2>คำแนะนำการเขียนอีเมล</h2>";
echo "<div class='alert alert-info'>";
echo "<h4>เคล็ดลับการเขียนอีเมลให้มีประสิทธิภาพ:</h4>";
echo "<ul>";
echo "<li><strong>ใช้หัวข้อที่ชัดเจน:</strong> ระบุธนาคารและความต้องการให้ชัดเจน</li>";
echo "<li><strong>แนะนำตัวเอง:</strong> บอกชื่อ ธุรกิจ และข้อมูลติดต่อ</li>";
echo "<li><strong>ระบุความต้องการ:</strong> บอกว่าต้องการ Dynamic QR Code สำหรับ Thai QR Payment</li>";
echo "<li><strong>ให้ข้อมูลธุรกิจ:</strong> ประเภทธุรกิจ รายได้ และจำนวนลูกค้า</li>";
echo "<li><strong>ถามคำถามที่สำคัญ:</strong> ค่าธรรมเนียม ข้อกำหนด และระยะเวลา</li>";
echo "<li><strong>ใช้ภาษาทางการ:</strong> เขียนอย่างสุภาพและเป็นทางการ</li>";
echo "<li><strong>ตรวจสอบก่อนส่ง:</strong> ตรวจสอบการสะกดและไวยากรณ์</li>";
echo "</ul>";
echo "</div>";

echo "<div class='alert alert-warning'>";
echo "<h4>สิ่งที่ต้องระวัง:</h4>";
echo "<ul>";
echo "<li><strong>อย่าใช้ภาษาพูด:</strong> ใช้ภาษาทางการเสมอ</li>";
echo "<li><strong>อย่าขอข้อมูลที่ไม่จำเป็น:</strong> ขอเฉพาะข้อมูลที่เกี่ยวข้อง</li>";
echo "<li><strong>อย่าทำให้อีเมลยาวเกินไป:</strong> เขียนให้กระชับและชัดเจน</li>";
echo "<li><strong>อย่าลืมข้อมูลติดต่อ:</strong> ต้องมีเบอร์โทรและอีเมลที่ชัดเจน</li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<h2>ขั้นตอนการส่งอีเมล</h2>";
echo "<ol>";
echo "<li><strong>เลือกเทมเพลต:</strong> เลือกเทมเพลตที่เหมาะสมกับธนาคาร</li>";
echo "<li><strong>แก้ไขข้อมูล:</strong> แก้ไขข้อมูลส่วนตัวและธุรกิจให้ตรงกับความเป็นจริง</li>";
echo "<li><strong>ตรวจสอบ:</strong> ตรวจสอบเนื้อหาและข้อมูลติดต่อ</li>";
echo "<li><strong>ส่งอีเมล:</strong> ส่งไปยังอีเมลของธนาคาร</li>";
echo "<li><strong>ติดตาม:</strong> ติดตามการตอบกลับภายใน 2-3 วัน</li>";
echo "</ol>";

echo "<hr>";
echo "<p><a href='application_documents.php' class='btn btn-primary'>ดูเอกสารที่ต้องเตรียม</a></p>";
echo "<p><a href='payment_gateway_info.php' class='btn btn-secondary'>ดูข้อมูล Payment Gateway</a></p>";
echo "<p><a href='test_qr_fake_complete.php' class='btn btn-info'>กลับไปหน้าทดสอบ</a></p>";

// JavaScript สำหรับคัดลอกเนื้อหา
echo "<script>";
echo "function copyToClipboard(text) {";
echo "    navigator.clipboard.writeText(text).then(function() {";
echo "        alert('คัดลอกเนื้อหาแล้ว!');";
echo "    }, function(err) {";
echo "        console.error('ไม่สามารถคัดลอกได้: ', err);";
echo "    });";
echo "}";
echo "</script>";
?>
