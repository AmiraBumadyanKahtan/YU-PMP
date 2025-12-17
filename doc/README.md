# Strategic Management System - Al Yamamah University
## نظام إدارة الاستراتيجية - جامعة اليمامة

A comprehensive project management system similar to OpenProject, designed specifically for managing strategic pillars and initiatives at Al Yamamah University.

نظام شامل لإدارة المشاريع مشابه لـ OpenProject، مصمم خصيصاً لإدارة الركائز والمبادرات الاستراتيجية في جامعة اليمامة.

---

## Features / المميزات

### English
- **Multi-language Support**: Full English and Arabic interface
- **Strategic Dashboard**: Overview of all pillars and initiatives
- **Pillar Management**: Detailed view of each strategic pillar with objectives
- **Initiative Tracking**: Complete initiative details with KPIs, milestones, and progress tracking
- **Team Management**: Assign and manage team members for each initiative
- **Document Management**: Upload and organize initiative documents
- **Budget Tracking**: Monitor budget allocation and spending
- **Activity Log**: Track all activities and changes
- **Comments System**: Team collaboration through comments
- **Progress Visualization**: Interactive charts and progress bars
- **Responsive Design**: Works on all screen sizes
- **Professional UI**: Clean design with smooth animations

### العربية
- **دعم متعدد اللغات**: واجهة كاملة باللغة العربية والإنجليزية
- **لوحة معلومات استراتيجية**: نظرة عامة على جميع الركائز والمبادرات
- **إدارة الركائز**: عرض تفصيلي لكل ركيزة استراتيجية مع الأهداف
- **تتبع المبادرات**: تفاصيل كاملة للمبادرة مع مؤشرات الأداء والمعالم الزمنية
- **إدارة الفرق**: تعيين وإدارة أعضاء الفريق لكل مبادرة
- **إدارة المستندات**: رفع وتنظيم مستندات المبادرة
- **تتبع الميزانية**: مراقبة تخصيص وإنفاق الميزانية
- **سجل النشاط**: تتبع جميع الأنشطة والتغييرات
- **نظام التعليقات**: التعاون الجماعي من خلال التعليقات
- **تصور التقدم**: رسوم بيانية تفاعلية وأشرطة تقدم
- **تصميم متجاوب**: يعمل على جميع أحجام الشاشات
- **واجهة احترافية**: تصميم نظيف مع رسوم متحركة سلسة

---

## Installation / التثبيت

### Prerequisites / المتطلبات الأولية
- XAMPP (or any PHP development environment)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web browser (Chrome, Firefox, Safari, Edge)

### Installation Steps / خطوات التثبيت

#### 1. Setup XAMPP / إعداد XAMPP

**English:**
1. Install XAMPP if not already installed
2. Make sure Apache and MySQL services are running
3. Configure MySQL to run on port 3308 (if needed)

**Arabic:**
1. قم بتثبيت XAMPP إذا لم يكن مثبتاً بالفعل
2. تأكد من تشغيل خدمات Apache و MySQL
3. قم بتكوين MySQL ليعمل على المنفذ 3308 (إذا لزم الأمر)

#### 2. Copy Files / نسخ الملفات

**English:**
1. Copy the `strategic-project-system` folder to your XAMPP `htdocs` directory
   - Path: `C:\xampp\htdocs\strategic-project-system` (Windows)
   - Path: `/Applications/XAMPP/htdocs/strategic-project-system` (Mac)

**Arabic:**
1. انسخ مجلد `strategic-project-system` إلى مجلد `htdocs` في XAMPP
   - المسار: `C:\xampp\htdocs\strategic-project-system` (ويندوز)
   - المسار: `/Applications/XAMPP/htdocs/strategic-project-system` (ماك)

#### 3. Create Database / إنشاء قاعدة البيانات

**English:**
1. Open phpMyAdmin: `http://localhost:3308/phpmyadmin`
2. Click on "Import" tab
3. Choose the file: `database/strategic_db.sql`
4. Click "Go" to import the database
5. The database will be created with demo data

**Arabic:**
1. افتح phpMyAdmin: `http://localhost:3308/phpmyadmin`
2. اضغط على تبويب "Import"
3. اختر الملف: `database/strategic_db.sql`
4. اضغط على "Go" لاستيراد قاعدة البيانات
5. سيتم إنشاء قاعدة البيانات مع بيانات تجريبية

#### 4. Configure Database Connection / تكوين الاتصال بقاعدة البيانات

**English:**
The system is pre-configured for:
- Host: `localhost:3308`
- Database: `strategic_management`
- Username: `root`
- Password: (empty)

If you need to change these settings, edit: `includes/config.php`

**Arabic:**
النظام مُعد مسبقاً لـ:
- الخادم: `localhost:3308`
- قاعدة البيانات: `strategic_management`
- اسم المستخدم: `root`
- كلمة المرور: (فارغة)

إذا كنت بحاجة لتغيير هذه الإعدادات، عدّل: `includes/config.php`

#### 5. Access the System / الوصول إلى النظام

**English:**
Open your web browser and go to:
```
http://localhost:3308/strategic-project-system/
```

**Arabic:**
افتح متصفح الويب واذهب إلى:
```
http://localhost:3308/strategic-project-system/
```

---

## Demo Users / المستخدمون التجريبيون

All demo users have the same password: `password`

| Username | Role | النوع |
|----------|------|-------|
| ceo | Chief Executive Officer | الرئيس التنفيذي |
| strategy_director | Strategy Office Director | مدير مكتب الاستراتيجية |
| ahmed.saud | Pillar Lead | قائد الركيزة |
| sarah.khalid | Initiative Owner | مسؤول المبادرة |

---

## System Structure / هيكل النظام

```
strategic-project-system/
├── assets/
│   ├── css/
│   │   └── style.css          # Main stylesheet
│   ├── js/
│   │   └── main.js            # Main JavaScript
│   ├── images/
│   │   ├── logo.png           # University logo
│   │   └── favicon-32x32.png  # Favicon
│   └── uploads/               # Uploaded files
├── includes/
│   ├── config.php             # Configuration
│   ├── Database.php           # Database class
│   └── functions.php          # Helper functions
├── database/
│   └── strategic_db.sql       # Database SQL
├── index.php                  # Main dashboard
├── pillar_detail.php          # Pillar details
├── initiative_detail.php      # Initiative details
└── README.md                  # This file
```

---

## Key Pages / الصفحات الرئيسية

### 1. Dashboard (index.php) / لوحة المعلومات
- Overview of all pillars
- Key statistics
- Budget overview
- Quick access to pillars

### 2. Pillar Detail (pillar_detail.php) / تفاصيل الركيزة
- Pillar information and progress
- Strategic objectives
- List of all initiatives
- Budget tracking

### 3. Initiative Detail (initiative_detail.php) / تفاصيل المبادرة
- Complete initiative information
- KPIs with progress tracking
- Timeline and milestones
- Team members
- Documents and resources
- Activity log
- Comments section

---

## Database Schema / مخطط قاعدة البيانات

Main tables:
- `users` - System users
- `pillars` - Strategic pillars
- `strategic_objectives` - Objectives for each pillar
- `initiatives` - Initiatives under pillars
- `kpis` - Key Performance Indicators
- `milestones` - Initiative milestones
- `team_assignments` - Team member assignments
- `documents` - Uploaded documents
- `activity_log` - Activity tracking
- `comments` - User comments
- `notifications` - System notifications

---

## Customization / التخصيص

### Changing Colors / تغيير الألوان
Edit the CSS variables in `assets/css/style.css`:
```css
:root {
    --primary-orange: #FF8C00;
    --dark-orange: #E67E00;
    /* ... */
}
```

### Adding New Features / إضافة ميزات جديدة
1. Create new PHP files in the root directory
2. Use the helper functions from `includes/functions.php`
3. Follow the existing page structure

---

## Troubleshooting / حل المشاكل

### Problem: Database connection error
**Solution:**
1. Check if MySQL is running on port 3308
2. Verify database credentials in `includes/config.php`
3. Make sure the database `strategic_management` exists

### Problem: CSS/JS not loading
**Solution:**
1. Check file paths in HTML
2. Clear browser cache
3. Verify Apache is running

### Problem: Arabic text not displaying correctly
**Solution:**
1. Ensure database charset is utf8mb4
2. Check HTML charset meta tag
3. Verify PHP files are saved with UTF-8 encoding

---

## Future Enhancements / التحسينات المستقبلية

- [ ] User authentication system
- [ ] Email notifications
- [ ] Real-time collaboration
- [ ] Advanced reporting
- [ ] Data export (Excel, PDF)
- [ ] File upload functionality
- [ ] Risk assessment module
- [ ] Integration with external systems
- [ ] Mobile app
- [ ] Advanced analytics dashboard

---

## Support / الدعم

For questions or issues:
- Check the documentation
- Review the code comments
- Inspect browser console for errors

---

## Credits / الشكر والتقدير

Developed for Al Yamamah University
جامعة اليمامة

---

## License / الترخيص

This system is developed specifically for Al Yamamah University.
هذا النظام مطور خصيصاً لجامعة اليمامة.

---

## Version / الإصدار

Version 1.0 - November 2024
الإصدار 1.0 - نوفمبر 2024