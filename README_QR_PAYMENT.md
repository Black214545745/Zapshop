# 🚀 ZapShop QR Code Payment System

ระบบการชำระเงินด้วย QR Code แบบอัตโนมัติสำหรับ ZapShop ที่รองรับ PromptPay และการตรวจสอบสถานะแบบ Real-time

## 📋 สารบัญ

- [ภาพรวมระบบ](#ภาพรวมระบบ)
- [โครงสร้างฐานข้อมูล](#โครงสร้างฐานข้อมูล)
- [Flow การทำงาน](#flow-การทำงาน)
- [ไฟล์ที่เกี่ยวข้อง](#ไฟล์ที่เกี่ยวข้อง)
- [การติดตั้ง](#การติดตั้ง)
- [การทดสอบ](#การทดสอบ)
- [การใช้งานจริง](#การใช้งานจริง)
- [การแก้ไขปัญหา](#การแก้ไขปัญหา)

## 🎯 ภาพรวมระบบ

ระบบ QR Code Payment ของ ZapShop ประกอบด้วย:

- **QR Code Generation**: สร้าง QR Code สำหรับ PromptPay
- **Auto-Check Payment**: ตรวจสอบสถานะการชำระเงินอัตโนมัติ
- **Webhook Integration**: รับ callback จากธนาคาร
- **Real-time Status**: อัปเดตสถานะแบบ Real-time
- **Transaction Management**: จัดการธุรกรรมและสต็อกสินค้า

## 🗄️ โครงสร้างฐานข้อมูล

### ตาราง `orders`
```sql
CREATE TABLE orders (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    shipping_address TEXT NOT NULL,
    shipping_phone VARCHAR(20) NOT NULL,
    shipping_email VARCHAR(100) NOT NULL,
    order_status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### ตาราง `order_items`
```sql
CREATE TABLE order_items (
    id SERIAL PRIMARY KEY,
    order_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    quantity INTEGER NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### ตาราง `payments`
```sql
CREATE TABLE payments (
    id SERIAL PRIMARY KEY,
    order_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_status VARCHAR(50) DEFAULT 'pending',
    payment_details TEXT,
    promptpay_id VARCHAR(50),
    qr_code_generated BOOLEAN DEFAULT FALSE,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## 🔄 Flow การทำงาน

### 1. การสร้างคำสั่งซื้อ
```
ลูกค้าเลือกสินค้า → Checkout → สร้าง Order + Payment → แสดง QR Code
```

### 2. การชำระเงิน
```
ลูกค้าสแกน QR Code → ธนาคารประมวลผล → ส่ง Webhook → อัปเดตสถานะ
```

### 3. การตรวจสอบสถานะ
```
ลูกค้า Refresh หน้า → ระบบ Query DB → แสดงสถานะล่าสุด
```

## 📁 ไฟล์ที่เกี่ยวข้อง

### ไฟล์หลัก
- `checkout.php` - หน้าสร้างคำสั่งซื้อและแสดง QR Code
- `generate_qr.php` - สร้าง QR Code สำหรับ PromptPay
- `webhook.php` - รับ callback จากธนาคาร
- `check_payment_status.php` - ตรวจสอบสถานะการชำระเงิน (AJAX)
- `payment-status.php` - แสดงสถานะการชำระเงิน
- `payment-success.php` - หน้าชำระเงินสำเร็จ

### ไฟล์ทดสอบ
- `test_webhook.php` - ทดสอบระบบ Webhook

## 🛠️ การติดตั้ง

### 1. ตรวจสอบฐานข้อมูล
```bash
# รันไฟล์สร้างตาราง
php recreate_orders_table_fixed.php
```

### 2. ตั้งค่า PromptPay ID
แก้ไขไฟล์ `generate_qr.php`:
```php
$promptpay_id = "0812345678"; // เปลี่ยนเป็น ID จริงของร้านค้า
```

### 3. ตั้งค่า Webhook URL
ธนาคารจะต้องส่ง webhook มาที่:
```
https://yourdomain.com/webhook.php
```

### 4. ตรวจสอบสิทธิ์ไฟล์
```bash
chmod 644 *.php
chmod 755 uploads/
```

## 🧪 การทดสอบ

### 1. ทดสอบการสร้างคำสั่งซื้อ
1. เข้าไปที่ `checkout.php`
2. เลือกสินค้าและสร้างคำสั่งซื้อ
3. ตรวจสอบ QR Code ที่แสดง

### 2. ทดสอบ Webhook
1. เข้าไปที่ `test_webhook.php` (ต้องเป็น admin)
2. เลือก Order ID ที่ต้องการทดสอบ
3. กดปุ่ม "ส่ง Webhook ทดสอบ"
4. ตรวจสอบสถานะในฐานข้อมูล

### 3. ทดสอบการตรวจสอบสถานะ
1. ใช้ `check_payment_status.php` ผ่าน AJAX
2. ตรวจสอบ response ที่ได้รับ

## 🌐 การใช้งานจริง

### 1. การตั้งค่าธนาคาร
ธนาคารจะต้องส่งข้อมูลในรูปแบบ:
```json
{
    "order_id": "123",
    "amount": "599.00",
    "transaction_id": "TXN123456",
    "payment_date": "2024-01-15 14:30:00",
    "status": "success"
}
```

### 2. การตั้งค่า Webhook
- URL: `https://yourdomain.com/webhook.php`
- Method: `POST`
- Content-Type: `application/json`
- Timeout: 30 วินาที

### 3. การตรวจสอบความถูกต้อง
ระบบจะตรวจสอบ:
- Order ID ต้องมีอยู่จริง
- ยอดเงินต้องตรงกับคำสั่งซื้อ
- สถานะต้องเป็น 'pending' ก่อน

## 🔧 การแก้ไขปัญหา

### ปัญหาที่พบบ่อย

#### 1. QR Code ไม่แสดง
- ตรวจสอบ JavaScript console
- ตรวจสอบ Network tab ใน Developer Tools
- ตรวจสอบ error log ของ PHP

#### 2. Webhook ไม่ทำงาน
- ตรวจสอบ URL ของ webhook
- ตรวจสอบ firewall และ security settings
- ตรวจสอบ error log ของ PHP

#### 3. สถานะไม่อัปเดต
- ตรวจสอบฐานข้อมูลโดยตรง
- ตรวจสอบ transaction log
- ตรวจสอบ webhook response

### การ Debug

#### 1. เปิด Error Log
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

#### 2. ตรวจสอบฐานข้อมูล
```sql
-- ตรวจสอบ orders
SELECT * FROM orders WHERE id = [order_id];

-- ตรวจสอบ payments
SELECT * FROM payments WHERE order_id = [order_id];

-- ตรวจสอบ order_items
SELECT * FROM order_items WHERE order_id = [order_id];
```

#### 3. ตรวจสอบ Webhook
```bash
# ทดสอบ webhook ด้วย curl
curl -X POST https://yourdomain.com/webhook.php \
  -H "Content-Type: application/json" \
  -d '{"order_id":"123","amount":"599.00"}'
```

## 📊 การติดตามและ Monitoring

### 1. Log Files
- PHP Error Log
- Web Server Access Log
- Custom Payment Log (ในฐานข้อมูล)

### 2. Database Monitoring
- ตรวจสอบสถานะ orders
- ตรวจสอบสถานะ payments
- ตรวจสอบ transaction history

### 3. Webhook Monitoring
- ตรวจสอบ response time
- ตรวจสอบ success rate
- ตรวจสอบ error patterns

## 🔒 ความปลอดภัย

### 1. Webhook Security
- ตรวจสอบ IP address ของธนาคาร
- ใช้ HMAC signature (ถ้าธนาคารรองรับ)
- ตรวจสอบ SSL certificate

### 2. Database Security
- ใช้ prepared statements
- ตรวจสอบ user permissions
- เข้ารหัสข้อมูลที่สำคัญ

### 3. Input Validation
- ตรวจสอบข้อมูลที่รับเข้ามา
- ใช้ whitelist สำหรับข้อมูลที่อนุญาต
- ตรวจสอบ data type และ format

## 📈 การพัฒนาต่อ

### 1. ฟีเจอร์ที่อาจเพิ่ม
- การรองรับหลายสกุลเงิน
- การรองรับหลายธนาคาร
- การแจ้งเตือนผ่าน LINE/Email
- การสร้างรายงานการชำระเงิน

### 2. การปรับปรุงประสิทธิภาพ
- ใช้ Redis สำหรับ caching
- ใช้ queue สำหรับ webhook processing
- เพิ่ม database indexes
- ใช้ connection pooling

### 3. การปรับปรุง UX
- เพิ่ม loading animation
- เพิ่ม sound notification
- เพิ่ม push notification
- ปรับปรุง responsive design

## 📞 การติดต่อ

หากมีปัญหาหรือคำถามเกี่ยวกับระบบ QR Code Payment:

- **Technical Support**: admin@zapshop.com
- **Documentation**: docs.zapshop.com
- **GitHub Issues**: github.com/zapshop/issues

---

**หมายเหตุ**: เอกสารนี้เป็นเวอร์ชัน 1.0 ของระบบ QR Code Payment สำหรับ ZapShop กรุณาอ่านและทำความเข้าใจก่อนใช้งานจริง
