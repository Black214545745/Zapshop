# ZapShop - E-commerce Platform

ZapShop เป็นแพลตฟอร์มร้านค้าออนไลน์ที่พัฒนาด้วย PHP และ PostgreSQL พร้อมระบบจัดการสินค้า การชำระเงิน และการจัดการผู้ใช้

## คุณสมบัติหลัก

- 🛒 ระบบร้านค้าออนไลน์
- 📱 Responsive Design
- 🗄️ ฐานข้อมูล PostgreSQL
- 💳 ระบบชำระเงิน
- 👥 การจัดการผู้ใช้
- 📊 ระบบรายงาน
- 🖼️ การจัดการรูปภาพสินค้า

## เทคโนโลยีที่ใช้

- **Backend**: PHP 8.0+
- **Database**: PostgreSQL
- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5
- **Icons**: Font Awesome
- **Deployment**: Render.com

## การติดตั้ง

### ข้อกำหนดระบบ
- PHP 8.0 หรือใหม่กว่า
- PostgreSQL 12 หรือใหม่กว่า
- Web Server (Apache/Nginx)

### ขั้นตอนการติดตั้ง

1. Clone repository
```bash
git clone https://github.com/yourusername/zapshop.git
cd zapshop
```

2. ตั้งค่าฐานข้อมูล
- สร้างฐานข้อมูล PostgreSQL
- แก้ไขการเชื่อมต่อใน `config.php`

3. รัน SQL Scripts
```bash
php setup_database.php
```

4. ตั้งค่า Web Server
- ชี้ Document Root ไปที่โฟลเดอร์โปรเจค
- เปิดใช้งาน mod_rewrite

## การ Deploy บน Render

### 1. เชื่อมต่อกับ GitHub
- เชื่อมต่อ GitHub repository กับ Render
- เลือก branch `main`

### 2. ตั้งค่า Environment Variables
```
DATABASE_URL=postgresql://username:password@host:port/database
```

### 3. Build Command
```bash
# ไม่ต้องใช้ build command สำหรับ PHP
```

### 4. Start Command
```bash
php -S 0.0.0.0:$PORT
```

## โครงสร้างโปรเจค

```
zapshop/
├── assets/                 # CSS, JS, Images
├── upload_image/          # รูปภาพสินค้า
├── includes/              # ไฟล์ include
├── config.php             # การตั้งค่าฐานข้อมูล
├── index.php              # หน้าแรก
├── product-list1.php      # รายการสินค้า
├── cart.php               # ตะกร้าสินค้า
├── checkout.php           # ชำระเงิน
├── admin-*.php            # หน้าจัดการ
└── README.md
```

## การใช้งาน

### สำหรับผู้ใช้ทั่วไป
1. เข้าสู่ระบบหรือสมัครสมาชิก
2. เลือกสินค้าที่ต้องการ
3. เพิ่มลงตะกร้า
4. ชำระเงิน

### สำหรับผู้ดูแลระบบ
1. เข้าสู่ระบบ Admin
2. จัดการสินค้าและหมวดหมู่
3. ดูรายงานการขาย
4. จัดการผู้ใช้

## การสนับสนุน

หากพบปัญหาหรือต้องการความช่วยเหลือ กรุณาสร้าง Issue ใน GitHub repository

## License

MIT License - ดูรายละเอียดในไฟล์ [LICENSE](LICENSE)

## ผู้พัฒนา

พัฒนาโดยทีม ZapShop Development Team
