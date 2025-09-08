# 🔧 ZapShop Payment Trigger System

ระบบ Trigger อัจฉริยะที่ทำงานอัตโนมัติเมื่อ `payments.payment_status` เปลี่ยน โดยไม่ต้องพึ่ง webhook.php

**✨ ฟีเจอร์ใหม่: รองรับหลาย Payment ต่อ Order ด้วย Logic อัจฉริยะ + Rollback อัตโนมัติ**

## 📋 สารบัญ

- [ภาพรวมระบบ](#ภาพรวมระบบ)
- [การทำงานของ Trigger](#การทำงานของ-trigger)
- [ไฟล์ที่เกี่ยวข้อง](#ไฟล์ที่เกี่ยวข้อง)
- [การติดตั้ง](#การติดตั้ง)
- [การทดสอบ](#การทดสอบ)
- [การใช้งาน](#การใช้งาน)
- [ข้อควรระวัง](#ข้อควรระวัง)
- [การแก้ไขปัญหา](#การแก้ไขปัญหา)

## 🎯 ภาพรวมระบบ

ระบบ Payment Trigger อัจฉริยะของ ZapShop ประกอบด้วย:

- **Automatic Order Status Update**: อัปเดต order_status อัตโนมัติตาม Logic อัจฉริยะ
- **Real-time Synchronization**: ซิงค์ข้อมูลระหว่างตาราง payments และ orders แบบ Real-time
- **Database-Level Logic**: ใช้ PostgreSQL Trigger แทนการเรียก PHP
- **Multiple Payment Support**: รองรับหลาย Payment ต่อ Order ได้ครบถ้วน
- **Smart Logic**: ตรวจสอบ EXISTS ของ paid payments เพื่อตัดสินใจ Order Status
- **Auto Rollback**: รองรับการ rollback กลับไป pending อัตโนมัติ

## 🔄 การทำงานของ Trigger

### 1. Function: `update_order_status()`

```sql
CREATE OR REPLACE FUNCTION update_order_status()
RETURNS TRIGGER AS $$
BEGIN
    -- เช็คว่า order_id ที่เกี่ยวข้องยังมี payment ที่จ่ายแล้วอยู่หรือไม่
    IF EXISTS (
        SELECT 1
        FROM payments
        WHERE order_id = NEW.order_id
          AND payment_status = 'paid'
    ) THEN
        -- มีอย่างน้อย 1 payment เป็น paid → อัปเดต order เป็น paid
        UPDATE orders
        SET order_status = 'paid',
            updated_at = NOW()
        WHERE id = NEW.order_id;
        
        -- บันทึก activity log (ถ้ามีตาราง)
        BEGIN
            INSERT INTO activity_logs (user_id, action, description, table_name, record_id)
            VALUES (
                NEW.user_id,
                'payment_completed',
                'Payment completed via smart trigger for order ' || NEW.order_id,
                'payments',
                NEW.id
            );
        EXCEPTION
            WHEN OTHERS THEN
                NULL; -- ไม่ critical ถ้า log ไม่สำเร็จ
        END;
    ELSE
        -- ไม่มี payment ที่เป็น paid → กลับไป pending
        UPDATE orders
        SET order_status = 'pending',
            updated_at = NOW()
        WHERE id = NEW.order_id;
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;
```

### 2. Trigger: `trg_update_order_status`

```sql
CREATE TRIGGER trg_update_order_status
AFTER INSERT OR UPDATE ON payments
FOR EACH ROW
EXECUTE FUNCTION update_order_status();
```

### 3. Trigger: `trg_update_order_status_delete`

```sql
CREATE TRIGGER trg_update_order_status_delete
AFTER DELETE ON payments
FOR EACH ROW
EXECUTE FUNCTION update_order_status();
```

## 📁 ไฟล์ที่เกี่ยวข้อง

### ไฟล์หลัก
- `create_payment_trigger.php` - สร้าง Trigger และ Function
- `test_trigger.php` - ทดสอบการทำงานของ Trigger

### ไฟล์ฐานข้อมูล
- ตาราง `payments` - เก็บข้อมูลการชำระเงิน
- ตาราง `orders` - เก็บข้อมูลคำสั่งซื้อ
- ตาราง `activity_logs` - เก็บ log การทำงาน (ถ้ามี)

## 🛠️ การติดตั้ง

### 1. รันไฟล์สร้าง Trigger

```bash
# เข้าไปที่ไฟล์ create_payment_trigger.php
http://yourdomain.com/create_payment_trigger.php
```

### 2. ตรวจสอบการสร้าง

ไฟล์จะแสดงผลการสร้าง:
- ✅ สร้าง Function สำเร็จ
- ✅ สร้าง Trigger สำเร็จ
- ✅ สร้าง Insert Trigger สำเร็จ

### 3. ตรวจสอบ Trigger ที่สร้าง

```sql
-- ตรวจสอบ Trigger ทั้งหมด
SELECT 
    trigger_name,
    event_manipulation,
    action_timing,
    action_statement
FROM information_schema.triggers 
WHERE event_object_table = 'payments'
ORDER BY trigger_name;
```

## 🧪 การทดสอบ

### 1. ทดสอบการทำงานของ Trigger

```bash
# เข้าไปที่ไฟล์ test_trigger.php
http://yourdomain.com/test_trigger.php
```

### 2. ทดสอบเปลี่ยน Payment Status

1. เลือก Payment ที่ต้องการทดสอบ
2. กดปุ่ม "ทดสอบเปลี่ยนเป็น 'paid'"
3. ตรวจสอบผลลัพธ์
4. ระบบจะคืนค่าสถานะเดิมอัตโนมัติ

### 3. ทดสอบด้วย SQL โดยตรง

```sql
-- อัปเดต payment_status เป็น 'paid'
UPDATE payments 
SET payment_status = 'paid' 
WHERE id = [payment_id];

-- Trigger จะรันอัตโนมัติและอัปเดต order_status
-- ไม่ต้องเรียก PHP หรือ webhook เพิ่ม
```

## 🔧 การใช้งาน

### 1. การอัปเดต Payment Status

#### วิธีที่ 1: ผ่าน PHP
```php
$updateQuery = "UPDATE payments SET payment_status = 'paid' WHERE id = $1";
$updateResult = pg_query_params($conn, $updateQuery, [$paymentId]);

// Trigger จะรันอัตโนมัติ
// ไม่ต้องอัปเดต order_status แยก
```

#### วิธีที่ 2: ผ่าน SQL โดยตรง
```sql
UPDATE payments 
SET payment_status = 'paid' 
WHERE order_id = 123;

-- Trigger จะรันอัตโนมัติ
-- orders.order_status จะเปลี่ยนเป็น 'paid'
```

### 2. การตรวจสอบสถานะ

```sql
-- ตรวจสอบสถานะ payment และ order
SELECT p.payment_status, o.order_status
FROM payments p
JOIN orders o ON p.order_id = o.id
WHERE p.id = [payment_id];
```

### 3. การจัดการกรณีพิเศษ

#### กรณี Refund/Cancellation
```sql
-- เปลี่ยน payment_status กลับเป็น 'pending'
UPDATE payments 
SET payment_status = 'pending' 
WHERE id = [payment_id];

-- Trigger จะเปลี่ยน order_status เป็น 'pending'
```

#### กรณี Payment Failed
```sql
-- เปลี่ยน payment_status เป็น 'failed'
UPDATE payments 
SET payment_status = 'failed' 
WHERE id = [payment_id];

-- Trigger จะเปลี่ยน order_status เป็น 'failed'
```

## ⚠️ ข้อควรระวัง

### 1. การทำงานของ Trigger

- **Trigger จะรันทุกครั้ง** ที่ payment_status เปลี่ยน
- **ไม่สามารถยกเลิก** การทำงานของ Trigger ได้
- **ควรทดสอบ** ก่อนใช้งานจริง

### 2. กรณีมีหลาย Payment ต่อ Order

```sql
-- ตรวจสอบ payment ทั้งหมดของ order
SELECT p.payment_status, COUNT(*)
FROM payments p
WHERE p.order_id = [order_id]
GROUP BY p.payment_status;
```

**Logic ปัจจุบัน:**
- ถ้า payment_status = 'paid' → order_status = 'paid'
- ถ้า payment_status = 'failed' → order_status = 'failed'
- ถ้า payment_status = 'pending' → order_status = 'pending'

**ข้อควรระวัง:**
- ถ้ามีหลาย payment ต่อ order อาจต้องปรับ logic
- ควรตรวจสอบสถานะรวมของทุก payment ก่อนอัปเดต order

### 3. Performance

- **Trigger จะรันทุกครั้ง** ที่มีการ UPDATE/INSERT
- **อาจส่งผลต่อ performance** หากมี transaction จำนวนมาก
- **ควรเพิ่ม index** บน payment_status และ order_id

## 🔧 การแก้ไขปัญหา

### 1. Trigger ไม่ทำงาน

#### ตรวจสอบ Trigger
```sql
-- ตรวจสอบว่า Trigger มีอยู่จริง
SELECT trigger_name, event_manipulation, action_timing
FROM information_schema.triggers 
WHERE event_object_table = 'payments';
```

#### ตรวจสอบ Function
```sql
-- ตรวจสอบว่า Function มีอยู่จริง
SELECT routine_name, routine_type
FROM information_schema.routines 
WHERE routine_name = 'trg_update_order_status';
```

#### ตรวจสอบ Error Log
```sql
-- เปิด error logging
SET log_statement = 'all';
SET log_min_messages = 'notice';
```

### 2. Trigger ทำงานผิดพลาด

#### ตรวจสอบ Logic
```sql
-- ตรวจสอบข้อมูลก่อนและหลัง
SELECT p.payment_status, o.order_status
FROM payments p
JOIN orders o ON p.order_id = o.id
WHERE p.id = [payment_id];
```

#### Debug Function
```sql
-- เพิ่ม debug ใน Function
RAISE NOTICE 'Payment ID: %, Status: %', NEW.id, NEW.payment_status;
```

### 3. Performance Issues

#### เพิ่ม Index
```sql
-- เพิ่ม index บน payment_status
CREATE INDEX idx_payments_status ON payments(payment_status);

-- เพิ่ม index บน order_id
CREATE INDEX idx_payments_order_id ON payments(order_id);
```

#### ตรวจสอบ Query Plan
```sql
-- ตรวจสอบ query plan
EXPLAIN (ANALYZE, BUFFERS) 
UPDATE payments SET payment_status = 'paid' WHERE id = [payment_id];
```

## 📊 การ Monitor และ Maintenance

### 1. ตรวจสอบ Trigger Performance

```sql
-- ตรวจสอบ Trigger ที่ใช้งานบ่อย
SELECT 
    schemaname,
    tablename,
    n_tup_ins,
    n_tup_upd,
    n_tup_del
FROM pg_stat_user_tables 
WHERE tablename = 'payments';
```

### 2. ตรวจสอบ Error Log

```sql
-- ตรวจสอบ error log
SELECT * FROM pg_stat_activity 
WHERE state = 'active' 
AND query LIKE '%trigger%';
```

### 3. การ Backup และ Restore

```sql
-- Backup Function และ Trigger
pg_dump -t payments -t orders --schema-only your_database > schema_backup.sql

-- Restore Function และ Trigger
psql your_database < schema_backup.sql
```

## 🚀 การพัฒนาต่อ

### 1. เพิ่มฟีเจอร์ใหม่

#### การแจ้งเตือน
```sql
-- เพิ่มการแจ้งเตือนเมื่อ payment สำเร็จ
IF NEW.payment_status = 'paid' THEN
    -- ส่ง email หรือ LINE notification
    PERFORM send_notification(NEW.user_id, 'payment_success');
END IF;
```

#### การตรวจสอบความถูกต้อง
```sql
-- เพิ่มการตรวจสอบยอดเงิน
IF NEW.amount != (SELECT total_amount FROM orders WHERE id = NEW.order_id) THEN
    RAISE EXCEPTION 'Amount mismatch';
END IF;
```

### 2. ปรับปรุง Performance

#### ใช้ Conditional Trigger
```sql
-- Trigger เฉพาะเมื่อ payment_status เปลี่ยน
CREATE TRIGGER payments_status_change
AFTER UPDATE ON payments
FOR EACH ROW
WHEN (OLD.payment_status IS DISTINCT FROM NEW.payment_status)
EXECUTE FUNCTION trg_update_order_status();
```

#### ใช้ Batch Update
```sql
-- อัปเดตหลาย payment พร้อมกัน
UPDATE payments 
SET payment_status = 'paid' 
WHERE order_id IN (123, 124, 125);
```

## 📞 การติดต่อ

หากมีปัญหาหรือคำถามเกี่ยวกับระบบ Trigger:

- **Technical Support**: admin@zapshop.com
- **Documentation**: docs.zapshop.com
- **GitHub Issues**: github.com/zapshop/issues

---

**หมายเหตุ**: เอกสารนี้เป็นเวอร์ชัน 1.0 ของระบบ Payment Trigger สำหรับ ZapShop กรุณาอ่านและทำความเข้าใจก่อนใช้งานจริง
