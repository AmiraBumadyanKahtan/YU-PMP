<?php
// layout/sidebar_items.php

$sidebarItems = [
  "MAIN" => [
    [
      "title"       => "Dashboard",
      "icon"        => "fa-solid fa-home",
      "url"         => "index.php",
      "permissions" => ["view_reports", "view_ceo_dashboard", "view_approvals"], // عرض للجميع تقريباً
    ],
    [
      "title"       => "Transparency View",
      "icon"        => "fa-solid fa-globe",
      "url"         => "public_dashboard.php",
      "permissions" => ["view_project"], // يظهر لجميع الموظفين الذين لديهم صلاحية عرض المشاريع
    ],
    [
      "title"       => "Insights",
      "icon"        => "fa-solid fa-chart-pie",
      "url"         => "modules/dashboard/project_dashboard.php", // تأكد أن المسار مطابق لمكان الملف الجديد
      "permissions" => ["view_project"], // متاح لأي شخص لديه حق رؤية المشاريع
    ],
  ],

  "PROJECTS" => [
    [
      "title"       => "Projects",
      "icon"        => "fa-solid fa-diagram-project",
      "url"         => "modules/operational_projects/index.php",
      "permissions" => ["view_project"],
    ],
    [
      "title"       => "Project Updates",
      "icon"        => "fa-solid fa-rotate",
      "url"         => "modules/project_updates/create.php",
      "permissions" => ["send_progress_update"],
    ],
    [
      "title"       => "Initiatives",
      "icon"        => "fa-solid fa-chess",
      "url"         => "modules/initiatives/list.php",
      "permissions" => ["view_initiative"],
    ],
    [
      "title"       => "Strategic Pillars",
      "icon"        => "fa-solid fa-layer-group",
      "url"         => "modules/pillars/index.php",
      "permissions" => ["view_pillars", "approve_pillar"],
    ],
    [
      "title"       => "Strategic Objectives",
      "icon"        => "fa-solid fa-bullseye",
      "url"         => "modules/strategic_objectives/list.php",
      "permissions" => ["view_strategic_objectives"],
    ],
    [
      "title"       => "KPIs",
      "icon"        => "fa-solid fa-chart-line",
      "url"         => "modules/kpis/list.php",
      "permissions" => ["manage_project_kpis", "view_initiative"],
    ],
  ],

  "APPROVALS" => [
    [
      "title"       => "My Approvals",
      "icon"        => "fa-solid fa-check-double",
      "url"         => "modules/approvals/dashboard.php",
      "permissions" => ["view_approvals"],
    ],
    [
      "title"       => "CEO Updates",
      "icon"        => "fa-solid fa-file-lines",
      "url"         => "modules/reports/ceo_updates.php",
      "permissions" => ["view_project_updates_ceo"], // تأكد أن هذا الإذن ممنوح لدور CEO في قاعدة البيانات
    ],
    [
      "title"       => "Incoming Collab Requests",
      "icon"        => "fa-solid fa-handshake",
      "url"         => "modules/collaborations/index.php",
      "permissions" => ["manage_project_team"], // فقط لرؤساء الأقسام
    ],
  ],

  "ADMIN CONTROL" => [
    [
      "title"       => "Departments",
      "icon"        => "fa-solid fa-building",
      "url"         => "modules/departments/list.php",
      "permissions" => ["manage_departments"],
    ],
    [
      "title"       => "Users",
      "icon"        => "fa-solid fa-users",
      "url"         => "modules/users/list.php",
      "permissions" => ["manage_users"],
    ],
    [
      "title"       => "Branches",
      "icon"        => "fa-solid fa-map-location-dot",
      "url"         => "modules/branches/list.php",
      "permissions" => ["manage_departments"], // أو صلاحية جديدة إذا أردت
    ],
    [
      "title"       => "Roles & Permissions",
      "icon"        => "fa-solid fa-shield-halved",
      "url"         => "modules/roles/list.php",
      "permissions" => ["manage_rbac"], 
    ],
    [
      "title"       => "Approval Workflows",
      "icon"        => "fa-solid fa-code-branch",
      "url"         => "modules/workflows/list.php",
      "permissions" => ["manage_rbac"], // سنستخدم صلاحية إدارة الصلاحيات مبدئياً
    ],
    [
      "title"       => "Project Roles",
      "icon"        => "fa-solid fa-id-badge",
      "url"         => "modules/project_roles/list.php",
      "permissions" => ["manage_rbac"],
    ],
    [
      "title"       => "Resources",
      "icon"        => "fa-solid fa-box",
      "url"         => "modules/resources/list.php",
      "permissions" => ["manage_project_resources"],
    ],
    [
      "title"       => "Pillar Statuses",
      "icon"        => "fa-solid fa-flag",
      "url"         => "modules/pillar_status/list.php",
      "permissions" => ["view_pillars"],
    ],
  ],
];
?>