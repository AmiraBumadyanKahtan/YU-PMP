// Strategic Management System - Main JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // 1. تهيئة السايدبار (Sidebar)
    initSidebar();
    
    // 2. تهيئة قائمة المستخدم (User Dropdown)
    initUserDropdown();
    
    // 3. التعامل مع النوافذ المنبثقة عند فتح الصفحة (Modals from URL)
    initUrlModals();
});

// --- وظائف السايدبار ---
function initSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const body = document.body;

    // التحقق من الحالة المحفوظة
    if (localStorage.getItem('sidebar_collapsed') === 'true') {
        sidebar?.classList.add('collapsed');
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function(e) {
            e.stopPropagation(); // منع انتشار الحدث
            sidebar?.classList.toggle('collapsed');
            localStorage.setItem('sidebar_collapsed', sidebar?.classList.contains('collapsed'));
        });
    }

    // إغلاق السايدبار في الجوال عند النقر خارجه
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768) {
            if (sidebar && !sidebar.contains(e.target) && !sidebarToggle?.contains(e.target)) {
                sidebar.classList.add('collapsed');
            }
        }
    });
}

// --- وظائف قائمة المستخدم ---
function initUserDropdown() {
    const userDropdown = document.querySelector('.user-dropdown');
    const dropdownToggle = document.querySelector('.user-dropdown-toggle');

    if (dropdownToggle && userDropdown) {
        dropdownToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('active');
        });

        // إغلاق القائمة عند النقر في أي مكان آخر
        document.addEventListener('click', function(e) {
            if (!userDropdown.contains(e.target)) {
                userDropdown.classList.remove('active');
            }
        });
    }
}

// --- وظائف النوافذ المنبثقة (GLOBAL MODALS) ---
// هذه الدوال متاحة في كل مكان لأنها معرفة في الـ Global Scope

window.openModal = function(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden'; // منع التمرير الخلفي
    } else {
        console.error('Modal not found:', modalId);
    }
};

window.closeModal = function(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }
};

// إغلاق المودال عند النقر خارجه
window.onclick = function(event) {
    if (event.target.classList.contains('custom-modal')) {
        event.target.classList.remove('show');
        document.body.style.overflow = '';
    }
};

// فتح المودال بناءً على رابط URL (لصفحة المبادرات)
function initUrlModals() {
    const urlParams = new URLSearchParams(window.location.search);
    const trigger = urlParams.get('trigger');
    
    if (trigger === 'update_status') window.openModal('modal-update-status');
    else if (trigger === 'add_milestone') window.openModal('modal-add-milestone');
    else if (trigger === 'upload_file') window.openModal('modal-upload');
    
    // تنظيف الرابط
    if (trigger) {
        const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?id=' + urlParams.get('id');
        window.history.replaceState({path: newUrl}, '', newUrl);
    }
}

// وظائف مساعدة عامة
window.navigateTo = function(url) {
    window.location.href = url;
};

window.formatCurrency = function(amount, currency = 'SAR') {
    return new Intl.NumberFormat('en-US').format(amount) + ' ' + currency;
};