<?php
// layout/sidebar.php

// 1. التأكد من وجود الثوابت (لأجل BASE_URL)
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../core/config.php';
}

// 2. استدعاء مصفوفة العناصر
require_once __DIR__ . "/sidebar_items.php";

// 3. جلب صلاحيات المستخدم الحالية من الجلسة
$userPermissions = $_SESSION['permissions'] ?? [];

// دالة مساعدة لتحديد الرابط النشط
function isActiveLink($url) {
    $currentUri = $_SERVER['REQUEST_URI'];
    // إذا كان الرابط هو index.php، نتأكد أننا في الجذر أو الصفحة بالتحديد
    if ($url === 'index.php') {
        return ($currentUri === BASE_URL || $currentUri === BASE_URL . 'index.php');
    }
    // غير ذلك، نبحث عن جزئية الرابط داخل العنوان الحالي
    return strpos($currentUri, $url) !== false;
}
?>

<aside class="sidebar">

<?php foreach ($sidebarItems as $section => $links): ?>

    <?php 
    $hasSectionAccess = false;
    foreach ($links as $checkItem) {
        if (!isset($checkItem["permissions"]) || array_intersect($checkItem["permissions"], $userPermissions)) {
            $hasSectionAccess = true;
            break;
        }
    }
    if (!$hasSectionAccess) continue; 
    ?>

    <div class="sidebar-section">

        <div class="sidebar-section-header" onclick="toggleSection(this)">
            <span><?php echo $section; ?></span>
            <i class="fa-solid fa-chevron-down"></i>
        </div>

        <div class="sidebar-links">
            <?php foreach ($links as $item): ?>

                <?php
                // ✅ فلترة الصلاحيات
                if (isset($item["permissions"]) && is_array($item["permissions"])) {
                    // إذا لم يكن لدى المستخدم أي من الصلاحيات المطلوبة، تخطي هذا العنصر
                    if (!array_intersect($item["permissions"], $userPermissions)) {
                        continue;
                    }
                }

                // تحديد الكلاس النشط
                $activeClass = isActiveLink($item["url"]) ? "active" : "";
                
                // ✅ إضافة BASE_URL للرابط ليعمل من أي مكان
                $fullUrl = BASE_URL . $item["url"];
                ?>

                <a href="<?php echo $fullUrl; ?>" class="sidebar-link <?php echo $activeClass; ?>">
                    
                        <i class="SB-icon <?php echo $item["icon"] ; ?>"></i>
                
                    <span><?php echo $item["title"]; ?></span>
                </a>

            <?php endforeach; ?>
        </div>

    </div>

<?php endforeach; ?>

</aside>

<script>
function toggleSection(el) {
    el.parentElement.classList.toggle("collapsed");
    // تدوير السهم عند الطي
    const icon = el.querySelector('i');
    if (el.parentElement.classList.contains('collapsed')) {
        icon.style.transform = 'rotate(-90deg)';
    } else {
        icon.style.transform = 'rotate(0deg)';
    }
}
</script>