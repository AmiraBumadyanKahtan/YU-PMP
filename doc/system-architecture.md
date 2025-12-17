# PMS YU SYSTEM — CORE ARCHITECTURE DOCUMENTATION
Version: 1.0
Last Update: 2025-12-06

==================================================
✅ هذا الملف هو المرجع الرسمي الوحيد:
- للصلاحيات (RBAC)
- للهيكل الإداري والأكاديمي (Hierarchy)
- للفروع (Branches)
- لأنظمة الموافقات (Approval Workflows)
==================================================


--------------------------------------------------
1️⃣ SYSTEM ROLES (RBAC)
--------------------------------------------------

التحكم بالصلاحيات يتم عبر system_roles + permissions + role_permissions.

الأدوار الرسمية المعتمدة:

| role_key | الوصف |
|----------|--------|
| super_admin | تحكم كامل |
| ceo | الرئيس التنفيذي (Final Authority) |
| university_president | رئيس الجامعة الأكاديمي |
| strategy_office | رئيس المكتب الاستراتيجي |
| strategy_staff | موظف مكتب استراتيجي |
| department_manager | رئيس قسم |
| finance | رئيس قسم المالية |
| employee | موظف عادي |
| auditor | مدقق فقط |

ملاحظة:
- لا يُستخدم vp أو نائب رئيس في أي موافقة.
- كل شيء يرجع في النهاية إلى CEO فقط.


--------------------------------------------------
2️⃣ USER HIERARCHY (REPORTING)
--------------------------------------------------

الهيكل الإداري والأكاديمي يتم عبر جدول:

user_hierarchy (
  user_id,
  manager_id,
  reporting_type ENUM('academic','administrative')
)

قواعد النظام:
- كل مستخدم ممكن يكون له مسارين:
  - إداري → CEO
  - أكاديمي → University President → CEO
- الموافقات التشغيلية تعتمد المسار الإداري.
- الموافقات الأكاديمية تعتمد المسار الأكاديمي فقط.
- لا يوجد نائب رئيس في أي مسار.


--------------------------------------------------
3️⃣ BRANCHES & MULTI-BRANCH
--------------------------------------------------

الدعم متعدد الفروع يتم عبر:

branches(id, code, name)
user_branches(user_id, branch_id)

قواعد:
- المستخدم ممكن يعمل في أكثر من فرع.
- الصلاحيات لا تتغير حسب الفرع.
- المشاريع التشغيلية مرتبطة بفرع واحد.
- المبادرات والركائز متعددة الفروع افتراضياً.


--------------------------------------------------
4️⃣ PROJECT & STRATEGIC STRUCTURE
--------------------------------------------------

الهيكل الرسمي:

PILLAR
 └── INITIATIVE
      ├── TASKS
      └── OPERATIONAL PROJECT (اختياري)
            └── TASKS

أنواع الكيانات المعتمدة للموافقات:
- pillar
- initiative
- operational_project


--------------------------------------------------
5️⃣ APPROVAL WORKFLOW ENGINE
--------------------------------------------------

الجداول الرسمية:

approval_entity_types
approval_workflows
approval_workflow_stages
approval_instances
approval_actions

❌ جدول approvals القديم يعتبر DEPRECATED.
✅ لا يُستخدم لأي منطق جديد.


--------------------------------------------------
6️⃣ OFFICIAL WORKFLOWS (FINAL)
--------------------------------------------------

🔴 PILLAR WORKFLOW (Strategic Pillar)

1. Strategy Staff
2. Strategy Office (رئيس المكتب الاستراتيجي)
3. CEO (FINAL)

----------------------------------

🟠 INITIATIVE WORKFLOW (Strategic Initiative)

1. Strategy Staff
2. Strategy Office (رئيس المكتب الاستراتيجي)
3. Finance Department Head (Budget Approval)
4. CEO (FINAL)

----------------------------------

🔵 OPERATIONAL PROJECT WORKFLOW (Operational Project)

1. Project Manager
   - من operational_projects.manager_id
2. Department Head
   - من departments.manager_id
3. Finance Department Head
4. CEO (FINAL)


--------------------------------------------------
7️⃣ ASSIGNMENT RULES (IMPORTANT)
--------------------------------------------------

طريقة تحديد صاحب الموافقة:

| المرحلة | التحديد يتم من |
|---------|----------------|
| Project Manager | operational_projects.manager_id |
| Department Head | departments.manager_id |
| Strategy Office | users.role_id = strategy_office |
| Finance | users.role_id = finance |
| CEO | users.role_id = ceo |

🚫 لا يتم استخدام user_id ثابت داخل workflow.


--------------------------------------------------
8️⃣ APPROVAL INSTANCE LIFECYCLE
--------------------------------------------------

الحالات الرسمية:

- in_progress
- approved
- rejected
- returned

كل إجراء يتم تسجيله في:
approval_actions


--------------------------------------------------
9️⃣ SECURITY & ACCESS RULES
--------------------------------------------------

- لا يُسمح للمستخدم بعرض أي موافقة إلا:
  - إذا كان هو المراجع الحالي
  - أو هو منشئ الطلب
  - أو لديه role = super_admin
- التعديل مسموح فقط:
  - في حالة returned
  - من صاحب الطلب فقط


--------------------------------------------------
🔟 DASHBOARDS RULES
--------------------------------------------------

| Dashboard | من يشاهد |
|-----------|----------|
| CEO Dashboard | ceo فقط |
| Strategy Dashboard | strategy_office + strategy_staff |
| Department Dashboard | department_manager فقط |
| Employee Dashboard | employee فقط |


--------------------------------------------------
✅ THIS DOCUMENT IS SYSTEM LAW
--------------------------------------------------

أي واجهة جديدة:
- يجب الالتزام بهذا الملف حرفياً.
- أي تغيير يتم هنا أولاً قبل التنفيذ البرمجي.

==================================================
END OF DOCUMENT
==================================================

--------------------------------------------------
✅ UPDATE 2025-12-06 — LOGIN & SESSION INIT
--------------------------------------------------

تم اعتماد نظام تسجيل دخول رسمي يعتمد على:

- system_roles
- user_hierarchy
- user_branches
- permissions

عند تسجيل الدخول يتم تخزين في الجلسة:

$_SESSION['user_id']
$_SESSION['username']
$_SESSION['full_name']
$_SESSION['role_id']
$_SESSION['role_key']
$_SESSION['role_name']
$_SESSION['permissions']
$_SESSION['branches']
$_SESSION['hierarchy']

أي صفحة لاحقًا تعتمد مباشرة على هذه القيم.
--------------------------------------------------

--------------------------------------------------
✅ UPDATE 2025-12-06 — DEPARTMENTS MODULE
--------------------------------------------------

تم اعتماد موديول الأقسام وفق الآتي:

- التحكم في الوصول يتم عن طريق:
  permission_key = manage_departments

- العمليات المدعومة:
  - إنشاء قسم
  - تعديل قسم
  - حذف قسم
  - عرض الأقسام
  - عرض تفاصيل القسم

- رئيس القسم يتم تخزينه في:
  departments.manager_id

- رئيس القسم يستخدم لاحقاً في:
  - مسار الموافقات للمشاريع التشغيلية
  - المسار الإداري في hierarchy

- جميع العمليات مرتبطة بـ:
  activity_log

- لا يُسمح بحذف أي قسم إذا كان مرتبط بـ:
  - initiatives
  - operational_projects
  - users
  - collaborations

--------------------------------------------------

--------------------------------------------------
✅ UPDATE 2025-12-06 — PERMISSIONS FIX
--------------------------------------------------

تم إضافة الصلاحية التالية:

permission_key: manage_departments

وتم ربطها مع:
- super_admin
- strategy_office (اختياري)

أي صفحة أقسام تعتمد على:
Auth::can('manage_departments')
--------------------------------------------------
--------------------------------------------------
✅ UPDATE 2025-12-06 — DEPARTMENT CREATE FIX
--------------------------------------------------

تم فصل:
- create.php → للعرض فقط (GET)
- save.php → للحفظ فقط (POST)

ممنوع تنفيذ INSERT داخل create.php نهائيًا.

التحقق الإجباري:
- manage_departments permission
- $_SERVER['REQUEST_METHOD'] === 'POST'

مدير القسم اختياري (NULL مسموح).

--------------------------------------------------
--------------------------------------------------
✅ UPDATE 2025-12-06 — ACTIVITY LOG FUNCTION FIX
--------------------------------------------------

تم اعتماد الدالة الرسمية لتسجيل الأنشطة:

log_activity(
    user_id,
    action,
    entity_type,
    entity_id,
    old_value,
    new_value
)

ويجب استدعاء:
require_once "../../core/functions.php";

في أي صفحة تستخدم تسجيل الأنشطة.

--------------------------------------------------
--------------------------------------------------
✅ UPDATE 2025-12-06 — DEPARTMENTS SOFT DELETE + TOAST UI
--------------------------------------------------

تم اعتماد الحذف الناعم (Soft Delete) للأقسام.

الأعمدة المعتمدة:
- is_deleted (0 | 1)
- deleted_at (datetime)

السياسة المعتمدة:
- لا يُستخدم DELETE نهائيًا على جدول departments.
- أي حذف يتم عبر:
  UPDATE departments SET is_deleted = 1, deleted_at = NOW()

التحقق قبل الحذف:
- users
- initiatives
- operational_projects

آلية التفاعل:
- الحذف يتم عبر AJAX.
- التحذيرات تظهر عبر Toast (SweetAlert).
- لا يتم استخدام صفحات die() أو أخطاء بيضاء.

جميع القوائم تعتمد دائمًا:
WHERE is_deleted = 0

--------------------------------------------------
--------------------------------------------------
✅ UPDATE 2025-12-07 — SAFE FK COLUMN CHECK
--------------------------------------------------

تم تعديل فحص الارتباطات في حذف الأقسام ليكون آمنًا
حتى في حال اختلاف أسماء الأعمدة بين الجداول.

- initiatives:
  pillar_id OR department_id
- operational_projects:
  department_id
- users:
  department_id

الهدف:
منع أي PDOException بسبب اختلاف بنية الجداول.

--------------------------------------------------
--------------------------------------------------
✅ UPDATE 2025-12-07 — USERS MODULE SOFT DELETE + TOAST
--------------------------------------------------

تم اعتماد الحذف الناعم (Soft Delete) للمستخدمين.

الأعمدة المعتمدة:
- is_deleted
- deleted_at

السياسة:
- ممنوع استخدام DELETE نهائيًا على جدول users.
- أي حذف يتم عبر:
  UPDATE users SET is_deleted = 1, deleted_at = NOW()

التحقق قبل الحذف:
- initiatives.owner_user_id
- operational_projects.manager_id
- initiative_team.user_id
- collaborations.requested_by / assigned_user_id

الواجهة:
- الحذف يتم عبر AJAX
- التحذيرات عبر SweetAlert Toast
- لا يتم استخدام confirm أو صفحات إعادة توجيه

جميع القوائم تعتمد:
WHERE users.is_deleted = 0

--------------------------------------------------
--------------------------------------------------
✅ UPDATE 2025-12-07 — USERS DELETE PERMISSION FIX
--------------------------------------------------

سبب ظهور رسالة Access Denied عند حذف المستخدم
كان بسبب أن:

- الصلاحية manage_users لم تكن محمّلة داخل الجلسة
- أو لم تكن مربوطة فعليًا مع دور super_admin

الحل المعتمد:
- ربط manage_users مع super_admin في role_permissions
- إعادة تسجيل الدخول بعد أي تعديل صلاحيات

--------------------------------------------------
--------------------------------------------------
✅ UPDATE 2025-12-07 — USERS SOFT DELETE FINAL FIX
--------------------------------------------------

سبب فشل الحذف الناعم للمستخدمين:
- متغير $db لم يكن معرّفًا داخل delete.php

تم التصحيح عبر:
$db = Database::getInstance()->pdo();

الآن:
- Soft Delete يعمل فعليًا
- users.is_deleted يتم تحديثه
- السجلات تختفي من الواجهة تلقائيًا

--------------------------------------------------
==================================================
APPROVAL DASHBOARD MODULE
==================================================

• Location:
  /modules/approvals/dashboard.php

• Visible For Roles:
  - super_admin
  - ceo
  - strategy_office
  - department_manager
  - finance

• Permissions:
  - view_approvals
  - approve_requests

• Sections:
  - Pending Approvals
  - My Requests
  - My Decisions

• Design:
  - Unified with global header and sidebar
  - Card-based layout
  - Status badges
  - Responsive grid

==================================================
--------------------------------------------------
✅ UPDATE 2025-12-06 — APPROVAL VIEW & ACTION
--------------------------------------------------

تم اعتماد:

• /modules/approvals/view.php
  - عرض تفاصيل الطلب
  - تحديد المرحلة الحالية
  - عرض الأزرار حسب المراجع الحالي فقط

• /modules/approvals/action.php
  - تنفيذ:
    - approve
    - reject
    - return
  - تسجيل كل إجراء في approval_actions

• approval_functions.php:
  - getApprovalInstance
  - canUserActOnApproval
  - processApprovalAction
  - advanceApprovalStage

--------------------------------------------------
--------------------------------------------------
✅ UPDATE 2025-12-06 — APPROVAL NOTIFICATION BADGE
--------------------------------------------------

1) New function
   File: /modules/approvals/approval_functions.php

   - getUserPendingApprovalsCount(user_id:int):int
     • تجمع كل طلبات الموافقات بحالة in_progress
     • أنواع الإسناد المدعومة:
       - system_role        → users.role_id = system_roles.id
       - pillar_lead        → pillars.lead_user_id = user
       - initiative_owner   → initiatives.owner_user_id = user
       - project_manager    → operational_projects.manager_id = user
       - department_manager → departments.manager_id = user (via operational_projects.department_id)
       - hierarchy_manager  → user_hierarchy.manager_id = user (academic/administrative)

2) Header Integration
   File: /layout/header.php

   - إضافة استدعاء:
     • require core/init.php
     • require modules/approvals/approval_functions.php

   - متغير جديد:
     • $pendingApprovalsCount = getUserPendingApprovalsCount($_SESSION['user_id'])

   - استبدال رقم ثابت في الجرس:
     • من: <span class="notification-badge">3</span>
     • إلى: <span class="notification-badge"><?= $pendingApprovalsCount ?></span>
       (مع شرط إخفاء إذا القيمة 0 حسب المطلوب)

--------------------------------------------------
✅ 2025-12-07 — Approval + To-Do Full Automation

- Every approval stage now:
  • Generates To-Do automatically for the next reviewer
  • Updates notification badge instantly
  • Links directly from To-Do → Approval View

- To-Do now supports:
  • Approvals
  • Tasks
  • Reminders
  • System generated items

- user_todos is now the master task tracker for:
  • Approvals
  • Projects
  • General follow-ups

2025-12-07
- Auto To-Do generation added for approval workflows
- user_todos is now the single source for:
  - Approvals
  - Tasks
  - Notifications
- Header notification badge now reads from user_todos
- Every approval stage creates a task for the next reviewer automatically

2025-12-07
- Linked approval actions to auto-advance workflow stages
- advanceApprovalStage() now called from approvals/action.php
- Each approval action now generates a To-Do task automatically
- Notification badge reflects real pending tasks
حالات المشروع بعد الموافقة

Draft → أثناء الإعداد

Pending Approval → بعد إرسال الفلو

Returned → رجع للمُنشئ للتعديل (يُسمح بالتعديل)

Rejected → توقف نهائي (لا تعديل)

Approved → بعد الموافقة الكاملة (يُسمح بالتنفيذ والتعديل المحدود)

In Progress → عندما تبدأ المهام فعليًا (progress > 0 مثلاً)

On Hold → إيقاف مؤقت

Completed → عند وصول progress = 100%

متى نجمّد التعديل؟

مسموح التعديل في الحالات:
Draft, Returned, Approved, In Progress

ممنوع في:
Pending Approval, Rejected, Completed

شروط إرسال المشروع للفلو

project_code

name

department_id

manager_id

budget_min / budget_max

start_date / end_date

فريق واحد على الأقل في project_team

لو ناقص شيء → زر "Send for Approval" ما يشتغل (أو يرجّع errors).

التعاون مع قسم آخر

من فورم المشروع:

collab_department_id

collab_contact_user_id

يتم إنشاء / تحديث سجل في collaborations:

parent_type = 'project'

parent_id = project.id

department_id = collab_department_id

assigned_user_id = collab_contact_user_id

requested_by = project creator

داخل منطق الموافقات (في approval_functions.php):

بعد موافقة Department Head الأساسي

يتم إرسال To-Do وApproval step لرئيس القسم المتعاون

بعد موافقته → تكمل السلسلة إلى Finance ثم CEO

حساب التقدم

يعتمد على project_tasks:

Project Progress =
    SUM(task.progress * task.weight)
    ÷
    SUM(task.weight)

الميزانية في تفاصيل المشروع

Min / Max → من الفورم

Approved → من المالية عبر الفلو

Spent → من:

project_tasks.cost_spent

project_milestones.cost_spent

work_resources.total_cost (parent_type = 'project')

Remaining = approved_budget - spent_budget (لو approved > 0)

7️⃣ التوثيق الوظيفي النهائي
عنصر	من يدخل؟	متى؟	أين؟
Project Code	النظام	تلقائي	create
Budget Min/Max	المدير	أثناء الإنشاء	create
Approved Budget	المالية	أثناء الموافقة	approvals
Project Manager	Dropdown	حسب القسم	create
Initiative	Dropdown	اختياري	create
Update Frequency	المدير	create	reminders
Reminders	النظام	تلقائي	cron
CEO Receives Updates	النظام	بعد إرسال التحديث	approvals
Collaboration Dept	المدير	create	collaboration
Collaboration Users	Dropdown	create	ajax

--------------------------------------------------
✅ RBAC FINAL BINDING — SIDEBAR & PAGES
--------------------------------------------------

• Siderbar visibility is controlled strictly by:
  permissions[] not roles.

• Page access is enforced using:
  Auth::require('permission_key')

• Super Admin always bypasses permissions.

• All modules must:
  - Hide links without permission
  - Block pages without permission

--------------------------------------------------
--------------------------------------------------
✅ UPDATE 2025-12-08 — SIDEBAR PERMISSION ENGINE
--------------------------------------------------

تم إلغاء الاعتماد على role_key في السايدبار نهائيًا.

التحكم في عرض عناصر السايدبار يتم الآن حصريًا عبر:

$_SESSION['permissions']

كل عنصر في sidebar_items.php يعتمد على:

"permissions" => [permission_key_1, permission_key_2]

التحقق يتم عبر:
array_intersect(item.permissions, user.permissions)

أي عنصر لا يمتلك المستخدم إحدى صلاحياته:
❌ لا يظهر في الواجهة
✅ لا يمكن التحايل عليه بالرابط المباشر

--------------------------------------------------
--------------------------------------------------
✅ PROJECT UPDATE REPORTING LOGIC — FINALIZED
--------------------------------------------------

• Updates do NOT modify project progress.
• Updates are short notification reports for the CEO.
• progress_percent is used for visual reference only.
• Real project progress will be calculated later from:
  - Tasks
  - Milestones
  - KPIs

Workflow:
Project Manager → Sends Update → CEO Notification Only

--------------------------------------------------
--------------------------------------------------
✅ PROJECT UPDATES (CEO REPORTING ONLY)
--------------------------------------------------

Table: project_updates

Purpose:
- Used ONLY for short executive reporting.
- Does NOT modify project progress.
- Does NOT affect KPIs or tasks.
- Used strictly for:
  Project Manager → CEO visibility.

progress_percent:
- Informational only.
- Visual indicator.
- Not used in calculations.

status:
- pending → Not yet viewed by CEO
- viewed → Read by CEO

--------------------------------------------------
--------------------------------------------------
✅ CEO PROJECT UPDATES MODULE
--------------------------------------------------

Page:
modules/project_updates/project_updates_ceo.php

Permission:
view_project_updates_ceo

Role:
CEO only

Behavior:
- Displays all submitted project updates.
- Shows:
  • Project code
  • Project name
  • Sender
  • Date
  • Description
  • Informational Progress %
- Status:
  • pending → Not viewed yet
  • viewed → Marked by CEO

CEO Interaction:
- CEO clicks "Mark as Viewed"
- Status updates immediately
- No impact on real project progress.

This module is for executive visibility only.

--------------------------------------------------
--------------------------------------------------
✅ UPDATE 2025-12-08 — CEO UPDATE NOTIFICATION ENGINE
--------------------------------------------------

Source Table:
project_updates

Usage:
- Exclusive for CEO executive reporting.
- Does NOT affect:
  • project progress
  • KPIs
  • tasks
  • budgets

Header Logic:
- If user role = ceo:
  - Notification badge reads from:
    project_updates WHERE status = 'pending'
  - Notification button redirects to:
    /modules/reports/project_updates_ceo.php

- If user role != ceo:
  - Notification badge reads from:
    user_todos
  - Redirects to:
    /modules/approvals/dashboard.php

Status Lifecycle:
- pending → Not viewed
- viewed → Seen by CEO

Progress Percent:
- Informational only
- Visual indicator
- No calculation impact

--------------------------------------------------
تم اعتماد startApprovalWorkflow() الجديدة والتي تقوم بـ:

- تعيين أول مرحلة تلقائيًا
- تحديث current_stage_id داخل approval_instances
- تحديد المراجع بناءً على assignment_type
- إنشاء To-Do تلقائي للمراجع
- تسجيل الإجراء داخل approval_actions

ممنوع استخدام أي fallback للدالة.
أي instance بدون current_stage_id يعتبر BUG.
