# การตั้งค่าการเชื่อมต่อ PostgreSQL บน Render.com

## ภาพรวม
โค้ดนี้ได้รับการปรับปรุงให้รองรับการเชื่อมต่อกับฐานข้อมูล PostgreSQL บน Render.com และยังคงใช้งานได้กับ MySQL บน localhost

## การตั้งค่าบน Render.com

### 1. สร้าง Web Service บน Render.com
1. เข้าไปที่ [Render.com](https://render.com)
2. สร้าง Web Service ใหม่
3. เชื่อมต่อกับ GitHub repository ของคุณ
4. เลือก PHP เป็น runtime

### 2. ตั้งค่า Environment Variables
ใน Render Dashboard ให้ตั้งค่า Environment Variables ดังนี้:

```
DATABASE_URL=postgresql://username:password@host:port/database_name
```

**หมายเหตุ:** Render จะสร้าง DATABASE_URL ให้คุณอัตโนมัติเมื่อคุณสร้าง PostgreSQL database

### 3. สร้าง PostgreSQL Database
1. ใน Render Dashboard ให้สร้าง PostgreSQL database ใหม่
2. ตั้งชื่อ database (เช่น "shoppingcart-db")
3. เลือก region ที่เหมาะสม
4. Render จะให้ DATABASE_URL มาให้คุณ

### 4. นำเข้า Schema
1. ใช้ PgAdmin4 หรือ psql เพื่อเชื่อมต่อกับฐานข้อมูล
2. รันไฟล์ `postgresql_schema.sql` เพื่อสร้างตารางและข้อมูลตัวอย่าง

## การใช้งาน PgAdmin4

### 1. ดาวน์โหลดและติดตั้ง PgAdmin4
- ดาวน์โหลดจาก [https://www.pgadmin.org/download/](https://www.pgadmin.org/download/)

### 2. เชื่อมต่อกับฐานข้อมูล
1. เปิด PgAdmin4
2. คลิกขวาที่ "Servers" → "Register" → "Server"
3. ในแท็บ "General":
   - Name: ShoppingCart DB (หรือชื่อที่คุณต้องการ)
4. ในแท็บ "Connection":
   - Host name/address: คัดลอกจาก DATABASE_URL
   - Port: 5432 (หรือ port ที่ระบุใน DATABASE_URL)
   - Maintenance database: ชื่อฐานข้อมูล
   - Username: ชื่อผู้ใช้จาก DATABASE_URL
   - Password: รหัสผ่านจาก DATABASE_URL
   - Save password: ✓

### 3. นำเข้า Schema
1. คลิกขวาที่ฐานข้อมูล → "Query Tool"
2. เปิดไฟล์ `postgresql_schema.sql`
3. คัดลอกเนื้อหาและวางใน Query Tool
4. กด F5 หรือคลิก "Execute" เพื่อรัน

## การทดสอบการเชื่อมต่อ

### 1. ทดสอบบน Localhost
```bash
# เปิดเบราว์เซอร์ไปที่
http://localhost/shoppingcart1/test_connection.php
```

### 2. ทดสอบบน Render.com
```bash
# เปิดเบราว์เซอร์ไปที่
https://your-app-name.onrender.com/test_connection.php
```

## ฟีเจอร์ที่เพิ่มเข้ามา

### 1. ฟังก์ชัน Universal Database Functions
- `isPostgreSQL()` - ตรวจสอบประเภทฐานข้อมูล
- `executeQuery($sql, $params)` - รัน query ที่รองรับทั้ง MySQL และ PostgreSQL
- `fetchData($result)` - ดึงข้อมูลจาก result set
- `numRows($result)` - นับจำนวนแถว
- `closeConnection()` - ปิดการเชื่อมต่อ

### 2. การจัดการ SSL
- เพิ่ม `sslmode=require` สำหรับการเชื่อมต่อที่ปลอดภัย

### 3. Timezone และ Character Set
- ตั้งค่า timezone เป็น 'Asia/Bangkok' สำหรับ PostgreSQL
- ตั้งค่า charset เป็น 'utf8' สำหรับ MySQL

## การแก้ไขปัญหา

### ปัญหาที่พบบ่อย

1. **การเชื่อมต่อล้มเหลว**
   - ตรวจสอบ DATABASE_URL ใน Environment Variables
   - ตรวจสอบว่า PostgreSQL extension ถูกติดตั้งใน PHP

2. **SSL Connection Error**
   - ตรวจสอบว่า `sslmode=require` อยู่ใน connection string

3. **Permission Denied**
   - ตรวจสอบ username และ password
   - ตรวจสอบว่า user มีสิทธิ์เข้าถึงฐานข้อมูล

### การ Debug
ใช้ไฟล์ `test_connection.php` เพื่อตรวจสอบ:
- ประเภทฐานข้อมูลที่ใช้งาน
- สถานะการเชื่อมต่อ
- รายชื่อตารางที่มีอยู่
- ข้อมูลการตั้งค่า

## โครงสร้างฐานข้อมูล

### ตารางหลัก
- `users` - ข้อมูลผู้ใช้
- `products` - ข้อมูลสินค้า
- `orders` - ข้อมูลคำสั่งซื้อ
- `order_items` - รายการสินค้าในคำสั่งซื้อ
- `cart` - ตะกร้าสินค้า
- `admin_users` - ข้อมูลผู้ดูแลระบบ

### Indexes และ Triggers
- Indexes สำหรับเพิ่มประสิทธิภาพการค้นหา
- Triggers สำหรับอัพเดท `updated_at` อัตโนมัติ

## การ Deploy

### 1. Push โค้ดไปยัง GitHub
```bash
git add .
git commit -m "Add PostgreSQL support"
git push origin main
```

### 2. Deploy บน Render.com
- Render จะ deploy อัตโนมัติเมื่อมีการ push โค้ดใหม่
- ตรวจสอบ logs ใน Render Dashboard หากมีปัญหา

## ข้อมูลเพิ่มเติม

- **PostgreSQL Documentation:** https://www.postgresql.org/docs/
- **Render Documentation:** https://render.com/docs
- **PgAdmin4 Documentation:** https://www.pgadmin.org/docs/

