# Changelog - การเพิ่มการรองรับ PostgreSQL

## วันที่: 2024-12-27

### ไฟล์ที่เพิ่มใหม่
- `postgresql_schema.sql` - Schema สำหรับ PostgreSQL
- `test_connection.php` - ไฟล์ทดสอบการเชื่อมต่อ
- `pgadmin_connection_info.php` - ข้อมูลการเชื่อมต่อ PgAdmin4
- `README_POSTGRESQL_SETUP.md` - คู่มือการตั้งค่า PostgreSQL
- `env_example.txt` - ตัวอย่าง Environment Variables
- `CHANGELOG_POSTGRESQL.md` - ไฟล์นี้

### ไฟล์ที่แก้ไข

#### 1. config.php
**การเปลี่ยนแปลง:**
- เพิ่มการรองรับ PostgreSQL ผ่าน DATABASE_URL
- เพิ่มฟังก์ชัน Universal Database Functions:
  - `isPostgreSQL()` - ตรวจสอบประเภทฐานข้อมูล
  - `executeQuery($sql, $params)` - รัน query ที่รองรับทั้ง MySQL และ PostgreSQL
  - `fetchData($result)` - ดึงข้อมูลจาก result set
  - `numRows($result)` - นับจำนวนแถว
  - `closeConnection()` - ปิดการเชื่อมต่อ
- เพิ่ม SSL support (`sslmode=require`)
- เพิ่มการตั้งค่า timezone และ charset
- แก้ไข base_url เป็น `shoppingcart1`

#### 2. index.php
**การเปลี่ยนแปลง:**
- แก้ไข SQL query ให้เข้ากับโครงสร้างตาราง PostgreSQL
- เปลี่ยนจาก `pg_query`, `pg_num_rows`, `pg_fetch_assoc` เป็นฟังก์ชัน universal
- ปรับโครงสร้างตาราง products:
  - `product_name` → `name`
  - `profile_image` → `image_url`
  - `detail` → `description`
  - ลบ JOIN กับตาราง categories (ใช้ `category` column แทน)
- ใช้ฟังก์ชัน `closeConnection()` แทนการปิดการเชื่อมต่อแบบเดิม

### ฟีเจอร์ใหม่

#### 1. การรองรับฐานข้อมูลแบบ Hybrid
- ระบบสามารถทำงานได้ทั้งกับ MySQL (localhost) และ PostgreSQL (Render.com)
- ตรวจสอบอัตโนมัติจาก Environment Variable `DATABASE_URL`

#### 2. ฟังก์ชัน Universal Database
- ฟังก์ชันที่ทำงานได้กับทั้ง MySQL และ PostgreSQL
- ลดความซับซ้อนในการเขียนโค้ด
- ง่ายต่อการบำรุงรักษา

#### 3. การจัดการ SSL
- รองรับ SSL connection สำหรับ PostgreSQL บน Render.com
- เพิ่มความปลอดภัยในการเชื่อมต่อ

#### 4. การตั้งค่า Timezone และ Character Set
- ตั้งค่า timezone เป็น 'Asia/Bangkok' สำหรับ PostgreSQL
- ตั้งค่า charset เป็น 'utf8' สำหรับ MySQL

### โครงสร้างฐานข้อมูล PostgreSQL

#### ตารางหลัก
- `users` - ข้อมูลผู้ใช้
- `products` - ข้อมูลสินค้า
- `orders` - ข้อมูลคำสั่งซื้อ
- `order_items` - รายการสินค้าในคำสั่งซื้อ
- `cart` - ตะกร้าสินค้า
- `admin_users` - ข้อมูลผู้ดูแลระบบ

#### Indexes และ Triggers
- Indexes สำหรับเพิ่มประสิทธิภาพการค้นหา
- Triggers สำหรับอัพเดท `updated_at` อัตโนมัติ

### ข้อมูลตัวอย่าง
- เพิ่มข้อมูลตัวอย่างสำหรับ admin user
- เพิ่มข้อมูลตัวอย่างสำหรับ products

### การทดสอบ
- ไฟล์ `test_connection.php` สำหรับทดสอบการเชื่อมต่อ
- ไฟล์ `pgadmin_connection_info.php` สำหรับแสดงข้อมูลการเชื่อมต่อ PgAdmin4

### การ Deploy บน Render.com
1. สร้าง PostgreSQL database บน Render.com
2. ตั้งค่า Environment Variable `DATABASE_URL`
3. นำเข้า schema จากไฟล์ `postgresql_schema.sql`
4. Deploy web service

### หมายเหตุสำคัญ
- ฐานข้อมูล PostgreSQL บน Render.com จะหมดอายุในวันที่ 7 กันยายน 2025
- ต้อง upgrade เป็น paid plan เพื่อใช้งานต่อ
- ใช้ SSL connection (`sslmode=require`) สำหรับความปลอดภัย

### การแก้ไขปัญหา
- เพิ่มการจัดการ error ที่ดีขึ้น
- แสดงข้อความ error ที่ชัดเจน
- ไฟล์ทดสอบสำหรับ debug

### ขั้นตอนต่อไป
1. ทดสอบการเชื่อมต่อบน localhost
2. Deploy บน Render.com
3. ทดสอบการทำงานของระบบ
4. แก้ไขไฟล์อื่นๆ ที่อาจต้องปรับปรุง

