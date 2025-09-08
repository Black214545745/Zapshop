<?php
session_start();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Makro-Style Buttons Demo - ZapShop</title>
    
    <!-- Bootstrap 5.3.0 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6.4.0 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Design System -->
    <link href="assets/css/zapshop-design-system.css" rel="stylesheet">
    
    <!-- ZapShop Button System -->
    <link href="assets/css/zapshop-buttons.css" rel="stylesheet">
    
    <style>
        .demo-section {
            padding: var(--spacing-3xl) 0;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .demo-section:last-child {
            border-bottom: none;
        }
        
        .demo-title {
            font-size: var(--font-size-3xl);
            font-weight: var(--font-weight-bold);
            color: var(--gray-800);
            margin-bottom: var(--spacing-lg);
            text-align: center;
        }
        
        .demo-subtitle {
            font-size: var(--font-size-lg);
            color: var(--gray-600);
            text-align: center;
            margin-bottom: var(--spacing-2xl);
        }
        
        .demo-card {
            background: var(--white);
            border-radius: var(--border-radius-xl);
            padding: var(--spacing-xl);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-lg);
            border: 1px solid var(--gray-200);
        }
        
        .demo-card h4 {
            color: var(--gray-800);
            margin-bottom: var(--spacing-lg);
            font-weight: var(--font-weight-semibold);
        }
        
        .demo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: var(--spacing-xl);
            margin-top: var(--spacing-2xl);
        }
        
        .code-example {
            background: var(--gray-900);
            color: var(--gray-100);
            padding: var(--spacing-lg);
            border-radius: var(--border-radius-lg);
            font-family: var(--font-family-mono);
            font-size: var(--font-size-sm);
            overflow-x: auto;
            margin-top: var(--spacing-lg);
        }
        
        .button-showcase {
            display: flex;
            gap: var(--spacing-md);
            flex-wrap: wrap;
            align-items: center;
            margin-bottom: var(--spacing-lg);
        }
        
        .button-showcase .btn {
            margin-bottom: var(--spacing-sm);
        }
        
        .hero-demo {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: var(--white);
            padding: var(--spacing-3xl) 0;
            text-align: center;
            margin-bottom: var(--spacing-3xl);
        }
        
        .hero-demo h1 {
            font-size: var(--font-size-4xl);
            font-weight: var(--font-weight-bold);
            margin-bottom: var(--spacing-lg);
        }
        
        .hero-demo p {
            font-size: var(--font-size-xl);
            opacity: 0.9;
            margin-bottom: var(--spacing-2xl);
        }
        
        .floating-demo {
            position: relative;
            min-height: 400px;
            background: var(--light-gray);
            border-radius: var(--border-radius-xl);
            padding: var(--spacing-xl);
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .floating-demo h3 {
            color: var(--gray-800);
            margin-bottom: var(--spacing-lg);
        }
        
        .floating-demo p {
            color: var(--gray-600);
            max-width: 500px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-store text-primary me-2"></i>
                ZapShop
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">หน้าแรก</a>
                <a class="nav-link active" href="zapshop-buttons-demo.php">ปุ่มแบบ ZapShop</a>
            </div>
        </div>
    </nav>
    
    <!-- Hero Demo -->
    <section class="hero-demo">
        <div class="container">
            <h1>ปุ่มแบบ ZapShop</h1>
            <p>ระบบปุ่มที่สวยงามและใช้งานง่าย เหมือนเว็บไซต์ของ ZapShop</p>
            <div class="hero-buttons">
                <a href="#button-basics" class="btn btn-lg btn-outline-hover">
                    <i class="fas fa-play me-2"></i>
                    เริ่มต้นใช้งาน
                </a>
                <a href="#button-layouts" class="btn btn-lg btn-gradient">
                    <i class="fas fa-th-large me-2"></i>
                    ดูการจัดวาง
                </a>
            </div>
        </div>
    </section>
    
    <!-- Basic Buttons -->
    <section id="button-basics" class="demo-section">
        <div class="container">
            <h2 class="demo-title">ปุ่มพื้นฐาน</h2>
            <p class="demo-subtitle">ปุ่มหลักที่ใช้บ่อยในเว็บไซต์</p>
            
            <div class="demo-grid">
                <div class="demo-card">
                    <h4>ปุ่มหลัก (Primary Buttons)</h4>
                    <div class="button-showcase">
                        <button class="btn btn-primary">ปุ่มหลัก</button>
                        <button class="btn btn-primary btn-sm">ปุ่มเล็ก</button>
                        <button class="btn btn-primary btn-lg">ปุ่มใหญ่</button>
                        <button class="btn btn-primary btn-xl">ปุ่มใหญ่มาก</button>
                    </div>
                    <div class="code-example">
&lt;button class="btn btn-primary"&gt;ปุ่มหลัก&lt;/button&gt;
&lt;button class="btn btn-primary btn-sm"&gt;ปุ่มเล็ก&lt;/button&gt;
&lt;button class="btn btn-primary btn-lg"&gt;ปุ่มใหญ่&lt;/button&gt;
                    </div>
                </div>
                
                <div class="demo-card">
                    <h4>ปุ่มรอง (Secondary Buttons)</h4>
                    <div class="button-showcase">
                        <button class="btn btn-secondary">ปุ่มรอง</button>
                        <button class="btn btn-outline">ปุ่มขอบ</button>
                        <button class="btn btn-ghost">ปุ่มใส</button>
                    </div>
                    <div class="code-example">
&lt;button class="btn btn-secondary"&gt;ปุ่มรอง&lt;/button&gt;
&lt;button class="btn btn-outline"&gt;ปุ่มขอบ&lt;/button&gt;
&lt;button class="btn btn-ghost"&gt;ปุ่มใส&lt;/button&gt;
                    </div>
                </div>
                
                <div class="demo-card">
                    <h4>ปุ่มสถานะ (Status Buttons)</h4>
                    <div class="button-showcase">
                        <button class="btn btn-success">สำเร็จ</button>
                        <button class="btn btn-warning">เตือน</button>
                        <button class="btn btn-danger">ผิดพลาด</button>
                        <button class="btn btn-info">ข้อมูล</button>
                    </div>
                    <div class="code-example">
&lt;button class="btn btn-success"&gt;สำเร็จ&lt;/button&gt;
&lt;button class="btn btn-warning"&gt;เตือน&lt;/button&gt;
&lt;button class="btn btn-danger"&gt;ผิดพลาด&lt;/button&gt;
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Special Button Styles -->
    <section class="demo-section">
        <div class="container">
            <h2 class="demo-title">ปุ่มพิเศษ</h2>
            <p class="demo-subtitle">ปุ่มที่มีเอฟเฟกต์พิเศษและสวยงาม</p>
            
            <div class="demo-grid">
                <div class="demo-card">
                    <h4>ปุ่มขอบแบบ Hover</h4>
                    <div class="button-showcase">
                        <button class="btn btn-outline-hover">ปุ่มขอบ</button>
                        <button class="btn btn-outline-hover btn-sm">ปุ่มเล็ก</button>
                        <button class="btn btn-outline-hover btn-lg">ปุ่มใหญ่</button>
                    </div>
                    <div class="code-example">
&lt;button class="btn btn-outline-hover"&gt;ปุ่มขอบ&lt;/button&gt;
&lt;button class="btn btn-outline-hover btn-sm"&gt;ปุ่มเล็ก&lt;/button&gt;
                    </div>
                </div>
                
                <div class="demo-card">
                    <h4>ปุ่มไล่ระดับสี</h4>
                    <div class="button-showcase">
                        <button class="btn btn-gradient">ปุ่มไล่สี</button>
                        <button class="btn btn-gradient btn-sm">ปุ่มเล็ก</button>
                        <button class="btn btn-gradient btn-lg">ปุ่มใหญ่</button>
                    </div>
                    <div class="code-example">
&lt;button class="btn btn-gradient"&gt;ปุ่มไล่สี&lt;/button&gt;
&lt;button class="btn btn-gradient btn-lg"&gt;ปุ่มใหญ่&lt;/button&gt;
                    </div>
                </div>
                
                <div class="demo-card">
                    <h4>ปุ่มไอคอน</h4>
                    <div class="button-showcase">
                        <button class="btn btn-primary btn-icon-only">
                            <i class="fas fa-heart"></i>
                        </button>
                        <button class="btn btn-secondary btn-icon-only btn-sm">
                            <i class="fas fa-star"></i>
                        </button>
                        <button class="btn btn-success btn-icon-only btn-lg">
                            <i class="fas fa-check"></i>
                        </button>
                    </div>
                    <div class="code-example">
&lt;button class="btn btn-primary btn-icon-only"&gt;
    &lt;i class="fas fa-heart"&gt;&lt;/i&gt;
&lt;/button&gt;
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Button Layouts -->
    <section id="button-layouts" class="demo-section">
        <div class="container">
            <h2 class="demo-title">การจัดวางปุ่ม</h2>
            <p class="demo-subtitle">รูปแบบการจัดวางปุ่มที่สวยงามและเป็นระเบียบ</p>
            
            <div class="demo-grid">
                <div class="demo-card">
                    <h4>กลุ่มปุ่มหลัก</h4>
                    <div class="btn-group-primary">
                        <button class="btn btn-primary">บันทึก</button>
                        <button class="btn btn-outline">ยกเลิก</button>
                        <button class="btn btn-secondary">ดูตัวอย่าง</button>
                    </div>
                    <div class="code-example">
&lt;div class="btn-group-primary"&gt;
    &lt;button class="btn btn-primary"&gt;บันทึก&lt;/button&gt;
    &lt;button class="btn btn-outline"&gt;ยกเลิก&lt;/button&gt;
    &lt;button class="btn btn-secondary"&gt;ดูตัวอย่าง&lt;/button&gt;
&lt;/div&gt;
                    </div>
                </div>
                
                <div class="demo-card">
                    <h4>กลุ่มปุ่มรอง</h4>
                    <div class="btn-group-secondary">
                        <button class="btn btn-sm btn-outline">แก้ไข</button>
                        <button class="btn btn-sm btn-outline">ลบ</button>
                        <button class="btn btn-sm btn-outline">คัดลอก</button>
                    </div>
                    <div class="code-example">
&lt;div class="btn-group-secondary"&gt;
    &lt;button class="btn btn-sm btn-outline"&gt;แก้ไข&lt;/button&gt;
    &lt;button class="btn btn-sm btn-outline"&gt;ลบ&lt;/button&gt;
&lt;/div&gt;
                    </div>
                </div>
                
                <div class="demo-card">
                    <h4>ปุ่มการทำงานของการ์ด</h4>
                    <div class="card-actions">
                        <button class="btn btn-outline-hover">ดูรายละเอียด</button>
                        <button class="btn btn-primary">เพิ่มลงตะกร้า</button>
                    </div>
                    <div class="code-example">
&lt;div class="card-actions"&gt;
    &lt;button class="btn btn-outline-hover"&gt;ดูรายละเอียด&lt;/button&gt;
    &lt;button class="btn btn-primary"&gt;เพิ่มลงตะกร้า&lt;/button&gt;
&lt;/div&gt;
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Button States -->
    <section class="demo-section">
        <div class="container">
            <h2 class="demo-title">สถานะของปุ่ม</h2>
            <p class="demo-subtitle">ปุ่มในสถานะต่างๆ ที่ใช้งานจริง</p>
            
            <div class="demo-grid">
                <div class="demo-card">
                    <h4>ปุ่มโหลด</h4>
                    <div class="button-showcase">
                        <button class="btn btn-primary btn-loading">กำลังโหลด</button>
                        <button class="btn btn-secondary btn-loading">กำลังบันทึก</button>
                    </div>
                    <div class="code-example">
&lt;button class="btn btn-primary btn-loading"&gt;กำลังโหลด&lt;/button&gt;
&lt;button class="btn btn-secondary btn-loading"&gt;กำลังบันทึก&lt;/button&gt;
                    </div>
                </div>
                
                <div class="demo-card">
                    <h4>ปุ่มสถานะ</h4>
                    <div class="button-showcase">
                        <button class="btn btn-success-state">สำเร็จแล้ว</button>
                        <button class="btn btn-error-state">เกิดข้อผิดพลาด</button>
                    </div>
                    <div class="code-example">
&lt;button class="btn btn-success-state"&gt;สำเร็จแล้ว&lt;/button&gt;
&lt;button class="btn btn-error-state"&gt;เกิดข้อผิดพลาด&lt;/button&gt;
                    </div>
                </div>
                
                <div class="demo-card">
                    <h4>ปุ่มแอนิเมชัน</h4>
                    <div class="button-showcase">
                        <button class="btn btn-primary btn-pulse">ปุ่มเต้น</button>
                        <button class="btn btn-secondary btn-bounce">ปุ่มเด้ง</button>
                        <button class="btn btn-success btn-slide-in">ปุ่มเลื่อน</button>
                    </div>
                    <div class="code-example">
&lt;button class="btn btn-primary btn-pulse"&gt;ปุ่มเต้น&lt;/button&gt;
&lt;button class="btn btn-secondary btn-bounce"&gt;ปุ่มเด้ง&lt;/button&gt;
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Floating Elements -->
    <section class="demo-section">
        <div class="container">
            <h2 class="demo-title">ปุ่มลอยและองค์ประกอบพิเศษ</h2>
            <p class="demo-subtitle">ปุ่มที่ลอยอยู่เหนือเนื้อหาและองค์ประกอบพิเศษ</p>
            
            <div class="demo-grid">
                <div class="demo-card">
                    <h4>ปุ่มลอย (Floating Action Button)</h4>
                    <div class="floating-demo">
                        <div>
                            <h3>ปุ่มลอยอยู่ด้านล่างขวา</h3>
                            <p>ปุ่มนี้จะลอยอยู่เหนือเนื้อหาทั้งหมด และสามารถใช้สำหรับการทำงานหลักได้</p>
                        </div>
                    </div>
                    <div class="code-example">
&lt;button class="btn-float" onclick="scrollToTop()" title="กลับขึ้นด้านบน"&gt;
    &lt;i class="fas fa-arrow-up"&gt;&lt;/i&gt;
&lt;/button&gt;
                    </div>
                </div>
                
                <div class="demo-card">
                    <h4>ปุ่มขนาดต่างๆ</h4>
                    <div class="button-showcase">
                        <button class="btn btn-primary btn-tiny">Tiny</button>
                        <button class="btn btn-primary btn-small">Small</button>
                        <button class="btn btn-primary btn-medium">Medium</button>
                        <button class="btn btn-primary btn-large">Large</button>
                        <button class="btn btn-primary btn-xlarge">XLarge</button>
                    </div>
                    <div class="code-example">
&lt;button class="btn btn-primary btn-tiny"&gt;Tiny&lt;/button&gt;
&lt;button class="btn btn-primary btn-large"&gt;Large&lt;/button&gt;
                    </div>
                </div>
                
                <div class="demo-card">
                    <h4>ปุ่มความกว้างต่างๆ</h4>
                    <div class="button-showcase">
                        <button class="btn btn-primary btn-quarter">25%</button>
                        <button class="btn btn-primary btn-third">33%</button>
                        <button class="btn btn-primary btn-half">50%</button>
                        <button class="btn btn-primary btn-full">100%</button>
                    </div>
                    <div class="code-example">
&lt;button class="btn btn-primary btn-quarter"&gt;25%&lt;/button&gt;
&lt;button class="btn btn-primary btn-full"&gt;100%&lt;/button&gt;
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Usage Guide -->
    <section class="demo-section">
        <div class="container">
            <h2 class="demo-title">คู่มือการใช้งาน</h2>
            <p class="demo-subtitle">วิธีการใช้งานระบบปุ่มแบบ ZapShop</p>
            
            <div class="demo-card">
                <h4>ขั้นตอนการใช้งาน</h4>
                <ol>
                    <li><strong>เพิ่ม CSS:</strong> เพิ่มไฟล์ CSS ทั้งสองไฟล์ในหน้าเว็บ</li>
                    <li><strong>ใช้คลาสปุ่ม:</strong> ใช้คลาส <code>.btn</code> พร้อมกับ modifier classes</li>
                    <li><strong>จัดกลุ่มปุ่ม:</strong> ใช้คลาส <code>.btn-group-primary</code> หรือ <code>.btn-group-secondary</code></li>
                    <li><strong>ปรับขนาด:</strong> ใช้คลาส <code>.btn-sm</code>, <code>.btn-lg</code> เป็นต้น</li>
                </ol>
                
                <h5 class="mt-4">ตัวอย่างการใช้งานจริง:</h5>
                <div class="code-example">
&lt;!-- ปุ่มหลักในหน้า Hero --&gt;
&lt;div class="hero-buttons"&gt;
    &lt;a href="#" class="btn btn-lg btn-outline-hover"&gt;ดูสินค้า&lt;/a&gt;
    &lt;a href="#" class="btn btn-lg btn-gradient"&gt;เข้าสู่ระบบ&lt;/a&gt;
&lt;/div&gt;

&lt;!-- ปุ่มการทำงานของสินค้า --&gt;
&lt;div class="product-actions"&gt;
    &lt;button class="btn btn-outline-hover"&gt;ดูรายละเอียด&lt;/button&gt;
    &lt;button class="btn btn-primary"&gt;เพิ่มลงตะกร้า&lt;/button&gt;
&lt;/div&gt;

&lt;!-- ปุ่มลอย --&gt;
&lt;button class="btn-float" onclick="scrollToTop()"&gt;
    &lt;i class="fas fa-arrow-up"&gt;&lt;/i&gt;
&lt;/button&gt;
                </div>
            </div>
        </div>
    </section>
    
    <!-- Floating Action Button -->
    <button class="btn-float" onclick="scrollToTop()" title="กลับขึ้นด้านบน">
        <i class="fas fa-arrow-up"></i>
    </button>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Scroll to top function
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }
        
        // Show/hide floating button based on scroll position
        window.addEventListener('scroll', function() {
            const floatBtn = document.querySelector('.btn-float');
            if (window.scrollY > 300) {
                floatBtn.style.display = 'flex';
            } else {
                floatBtn.style.display = 'none';
            }
        });
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Hide floating button initially
            document.querySelector('.btn-float').style.display = 'none';
            
            // Add click effects to all buttons
            document.querySelectorAll('.btn').forEach(button => {
                button.addEventListener('click', function() {
                    this.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 150);
                });
            });
        });
        
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>
