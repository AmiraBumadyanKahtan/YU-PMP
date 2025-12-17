
## V001__add_soft_delete_columns
- أُضيفت حقول is_deleted/deleted_at للجداول الأساسية (pillars, strategic_objectives, initiatives, operational_projects, milestones, tasks, kpis, documents).
- سياسة الاستعلام الافتراضي: استبعاد السجلات المحذوفة لينًا (is_deleted = 0).

## V002__add_basic_check_constraints
- أضيفت قيود CHECK للميزانيات، التواريخ، ونسب التقدم.
- أي إدخال مخالف يُرفض على مستوى DB لمنع الفوضى في طبقة التطبيق.

## V003__normalize_entity_type_values_in_activity_log (مؤجل للتنفيذ لاحقًا)
- توحيد قيم entity_type إلى مجموعة معتمدة.
- خطوة تمهيدية قبل نقل السجل إلى نموذج audit_events/audit_changes.

## V004__add_base_indexes
- إضافة فهارس مركّبة خفيفة لرفع الأداء في العروض العامة.

## V001__merge_system_roles_into_roles
- دمج محتوى system_roles داخل roles واعتماد roles كمرجع الأدوار العالمية الوحيد.

## V001__merge_system_roles_into_roles
- دمج محتوى system_roles داخل roles واعتماد roles كمرجع الأدوار العالمية.
- لم يتم إسقاط system_roles بعد (انتقال مؤقت).


## V002__users_add_primary_role
- إضافة primary_role_id وربطه بـ roles.
- ترحيل مبدئي من system_roles عبر role_key إلى roles.

## V002__users_add_primary_role
- إضافة users.primary_role_id مع FK إلى roles(id) وفهرس idx_users_primary_role.
- تعبئة مبدئية للدور الأساسي من users.role_id عبر system_roles → roles.


# ADR-0001: توحيد الأدوار العالمية على جدول roles
## القرار
اعتماد جدول roles كمصدر واحد للأدوار العالمية وربط users.primary_role_id و approval_workflow_stages.stage_role_id به.
## السياق
وجود system_roles و roles خلق ازدواجية وتعقيدًا في الموافقات وRBAC.
## التبعات
تبسيط إدارة الوصول، تمهيد لبناء صلاحيات محلية ضمن الكيانات (المبادرات/المشاريع).
## الحالة
مقبول - 2025-12-07


## V003__approval_stages_use_roles
- إضافة stage_role_id وربطه بـ roles، تعبئة من system_roles تمهيدًا لإلغاء العمود القديم.

## V003__approval_stages_use_roles
- إضافة عمود انتقالي stage_role_id وربطه بـ roles(id) مع فهرس idx_aws_stage_role.
- تعبئة مبدئية لقيم stage_role_id عبر مطابقة system_roles.role_key إلى roles.role_key.
- إبقاء system_role_id مؤقتًا لحين تعديل التطبيق للقراءة من stage_role_id.


## V004__verify_role_permissions_and_user_roles
- التحقق من أن role_permissions و user_roles يشيران إلى roles (سليم في النسخة الحالية).

## V005__add_initiative_permissions
- إضافة مفاتيح صلاحيات تغطي إدارة المبادرات ومكوّناتها والركائز.

## V005__add_initiative_and_pillar_permissions
- إضافة مفاتيح صلاحيات تغطي إدارة المبادرات ومكوّناتها (فريق/مهام/KPIs/وثائق/مخاطر/تحديثات التقدم).
- إضافة صلاحيات لإدارة الركائز والأهداف الاستراتيجية (عرض/إنشاء/تعديل/حذف).
- لا توجد ربطات دورية حتى الآن؛ سنوزعها لاحقًا عبر role_permissions.


## V006__entity_scoped_role_permissions
- إضافة جداول initiative_role_permissions و project_role_permissions لربط أدوار الفريق بصلاحيات محلية ضمن الكيان.


## V006__deduplicate_roles
- دمج executive_ceo ضمن ceo مع نقل جميع الصلاحيات وعضويات المستخدمين ومراحل الموافقات.
- دمج department_head ضمن department_manager مع نقل الصلاحيات والعضويات والمراحل.
- حذف الأدوار البديلة بعد تحويل جميع المراجع.


## V007__entity_scoped_role_permissions
- إنشاء initiative_role_permissions و project_role_permissions لربط أدوار الفريق المحلية بصلاحيات محلية.
- Seed لأدوار الفريق القياسية: Project Manager / Team Member / Coordinator / Viewer في كل من المبادرات والمشاريع التشغيلية.
- هذه الصلاحيات تُطبَّق فقط ضمن نطاق الكيان الذي يكون فيه المستخدم عضوًا في الفريق.


## V012__create_department_branches
- إنشاء جدول ربط M:N بين الأقسام والفروع (department_branches) بمفتاح مركّب PRIMARY KEY (department_id, branch_id).
- قيود FK مع ON DELETE CASCADE لضمان نظافة البيانات عند حذف قسم/فرع.
- Seed اختياري: ربط قسم "Finance" بكل الفروع النشطة.

## V013__views_projects_visibility_by_branch
- إنشاء Views لتحديد المشاريع المرئية للمستخدمين:
  - vw_project_visibility_by_branch: بناءً على تقاطع الفروع بين المستخدم وقسم المشروع.
  - vw_project_visibility_by_global_role: رؤية شاملة لأدوار محددة ('super_admin','ceo','strategy_office').
  - vw_project_visibility: دمج الحالتين.
  - vw_user_projects: تفاصيل المشاريع المتاحة لكل مستخدم.

## V014__harden_pillars_and_objectives
- إضافة حقول Soft Delete (is_deleted, deleted_at) في pillars و strategic_objectives.
- قيود CHECK: نسب تقدّم الركائز (0..100)، ومنطق التواريخ، وتنسيق اللون.
- فرض فريد على objective_code في strategic_objectives.
- فهارس مساعدة للأداء.

## V015__harden_initiatives
- إضافة Soft Delete (is_deleted, deleted_at) إن لم يكن موجودًا.
- قيود CHECK: التقدم (0..100)، التواريخ (start <= due)، الميزانية الدنيا <= العليا.
- فهارس مساعدة: (pillar_id, status_id)، (owner_user_id)، (start_date, due_date).
/* ======================================================================
   V015b__harden_initiative_milestones_and_tasks.sql
   الغرض:
     - إضافة Soft Delete للجداول: initiative_milestones, initiative_tasks.
     - إضافة قيود CHECK للتواريخ، التكاليف، التقدّم، الوزن.
     - إضافة فهارس مساعدة للأداء.
   ====================================================================== */

## V015b__harden_initiative_milestones_and_tasks
- إضافة Soft Delete (is_deleted, deleted_at) للمعالم والمهام.
- قيود CHECK على التواريخ، التكاليف، التقدّم، والوزن.
- فهارس لتحسين الاستعلامات الشائعة.

## V0XX__department_branches
- إضافة جدول ربط M:N بين الأقسام والفروع لتعريف نطاق عمل القسم عبر فروع الجامعة.

## V016__harden_operational_projects
- Soft Delete (is_deleted, deleted_at) + CHECK: الميزانية/التواريخ/التقدّم + فهارس (department/status, manager/status, dates).

## V016b__harden_project_milestones_and_tasks
- Soft Delete للمعالم/المهام + CHECK: التواريخ/التكاليف/الوزن/التقدّم + فهارس شائعة.

## V016c__project_tasks_triggers
- BEFORE INSERT/UPDATE تمنع ربط مهمة بمعلَم لا ينتمي لنفس المشروع.

## V024__overdue_and_upcoming_views
- إنشاء Views للمهام/المعالم المتأخرة والمقبلة للمشاريع والمبادرات.
- ملخصات صحّة لكل مشروع/مبادرة (tasks/milestones: total/open/overdue).

## V025__daily_snapshot_tables_and_proc
- جداول Snapshot يومية للمشاريع والمبادرات + KPIs + المخاطر.
- إجراء `sp_capture_daily_snapshots(p_snapshot_date)` لالتقاط صورة يومية.

## V026__event_scheduler_daily_snapshot (اختياري)
- حدث يومي `ev_capture_daily_snapshots` ينفّذ الإجراء الساعة 02:30 صباحًا.
