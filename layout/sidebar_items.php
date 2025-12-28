<?php
// layout/sidebar_items.php

$sidebarItems = [
  "MAIN" => [
    [
      "title"       => "Dashboard",
      "icon"        => "fa-solid fa-home",
      "url"         => "index.php",
     
    ],
    [
      "title"       => "CEO Dashboards",
      "icon"        => "fa-solid fa-chart-pie",
      "url"         => "modules/dashboard/ceo_org.php",
      "permissions" => ["view_ceo_dashboard"], // صلاحية جديدة أضفناها
    ],
    [
      "title"       => "Transparency View",
      "icon"        => "fa-solid fa-globe",
      "url"         => "modules/dashboard/public_dashboard.php",
      "permissions" => ["view_public_dashboard"], // صلاحية جديدة أضفناها
    ],
  ],

  "STRATEGY & PROJECTS" => [
    [
      "title"       => "Strategic Pillars",
      "icon"        => "fa-solid fa-landmark",
      "url"         => "modules/pillars/index.php",
      "permissions" => ["pillar_view"],
    ],
    [
      "title"       => "Initiatives",
      "icon"        => "fa-solid fa-chess",
      "url"         => "modules/initiatives/list.php",
      "permissions" => ["init_view_dashboard"],
    ],
    [
      "title"       => "Projects",
      "icon"        => "fa-solid fa-diagram-project",
      "url"         => "modules/operational_projects/index.php",
      "permissions" => ["proj_view_dashboard"],
    ],
    [
      "title"       => "Strategic Objectives",
      "icon"        => "fa-solid fa-bullseye",
      "url"         => "modules/strategic_objectives/list.php",
      "permissions" => ["view_strategic_objectives"], // صلاحية جديدة
    ],
    [
      "title"       => "KPIs Center",
      "icon"        => "fa-solid fa-chart-line",
      "url"         => "modules/kpis/list.php",
      "permissions" => ["view_all_kpis", "pkpi_view", "ikpi_view"], // تظهر إذا كان لديه أي واحدة منها
    ],
  ],

  "WORKFLOW" => [
    [
      "title"       => "My Approvals",
      "icon"        => "fa-solid fa-check-double",
      "url"         => "modules/approvals/dashboard.php",
      "permissions" => ["view_approvals"], // صلاحية جديدة
    ],
    [
      "title"       => "Collab Requests",
      "icon"        => "fa-solid fa-handshake",
      "url"         => "modules/collaborations/index.php",
      "permissions" => ["proj_manage_team"], // لرؤساء الأقسام والمدراء (المسؤولين عن قبول الموظفين)
    ],
  ],

  "ADMINISTRATION" => [
    [
      "title"       => "Departments",
      "icon"        => "fa-solid fa-building",
      "url"         => "modules/departments/list.php",
      "permissions" => ["sys_dept_view"],
    ],
    [
      "title"       => "Users",
      "icon"        => "fa-solid fa-users",
      "url"         => "modules/users/list.php",
      "permissions" => ["sys_user_view"],
    ],
    [
      "title"       => "Branches",
      "icon"        => "fa-solid fa-map-location-dot",
      "url"         => "modules/branches/list.php",
      "permissions" => ["sys_dept_branches"],
    ],
    [
      "title"       => "Roles & Permissions",
      "icon"        => "fa-solid fa-shield-halved",
      "url"         => "modules/roles/list.php",
      "permissions" => ["sys_role_view"], 
    ],
    [
        "title"       => "Announcements",
        "icon"        => "fa-solid fa-bullhorn",
        "url"         => "modules/announcements/list.php",
        "permissions" => ["sys_manage_announcements"],
    ],
  ],

  "SYSTEM SETTINGS" => [
    [
      "title"       => "Approval Workflows",
      "icon"        => "fa-solid fa-code-branch",
      "url"         => "modules/workflows/list.php",
      "permissions" => ["manage_workflows", "sys_settings_manage"],
    ],
    [
      "title"       => "Project Roles",
      "icon"        => "fa-solid fa-id-badge",
      "url"         => "modules/project_roles/list.php",
      "permissions" => ["manage_project_roles", "sys_settings_manage"],
    ],
    [
      "title"       => "Resources Types",
      "icon"        => "fa-solid fa-box",
      "url"         => "modules/resources/list.php",
      "permissions" => ["sys_settings_manage"], // عادة إدارة الأنواع تكون للأدمن
    ],
    [
      "title"       => "System Logs",
      "icon"        => "fa-solid fa-list-ul",
      "url"         => "modules/logs/index.php",
      "permissions" => ["sys_view_logs"],
    ],
  ],
];
?>