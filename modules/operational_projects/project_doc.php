<?php
// =========================================================
// 6. DOCUMENTS MANAGEMENT
// =========================================================

/**
 * دالة لرفع ملف جديد وحفظ بياناته في قاعدة البيانات.
 * تتعامل مع رفع الملف للخادم ثم إدخال السجل في الجدول.
 * * @param array $data مصفوفة البيانات (العنوان، الوصف، النوع، المعرف الأب)
 * @param array $file مصفوفة الملف المرفوع ($_FILES)
 * @return array مصفوفة النتيجة ['ok' => bool, 'error' => string]
 */
/**
 * دالة لرفع ملف جديد وحفظ بياناته في قاعدة البيانات.
 */
function uploadProjectDocument($data, $file) {
    $db = Database::getInstance()->pdo();
    
    // المسار النسبي لمجلد الرفع (تأكد من وجود المجلد وصلاحياته)
    $targetDir = "../../assets/uploads/documents/"; 
    
    if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
    
    $fileName = time() . '_' . basename($file["name"]);
    $targetFilePath = $targetDir . $fileName;
    $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
    
    // افتراضياً يربط بالمشروع إذا لم يحدد
    $parentType = $data['parent_type'] ?? 'project';
    $parentId   = $data['parent_id'] ?? $data['project_id'];

    // التحقق من الامتدادات المسموحة
    $allowedTypes = ['jpg','png','jpeg','gif','pdf','doc','docx','xls','xlsx','ppt','pptx','txt','zip','rar'];
    if (!in_array($fileType, $allowedTypes)) return ['ok'=>false, 'error'=>'Invalid file type.'];

    if (move_uploaded_file($file["tmp_name"], $targetFilePath)) {
        $stmt = $db->prepare("
            INSERT INTO documents 
            (parent_type, parent_id, title, description, file_name, file_path, file_size, file_type, uploaded_by, uploaded_at, created_at)
            VALUES 
            (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        // المسار الذي سيحفظ في قاعدة البيانات
        $dbPath = 'assets/uploads/documents/' . $fileName;

        if ($stmt->execute([
            $parentType, 
            $parentId, 
            $data['title'], 
            $data['description'], 
            $fileName, 
            $dbPath, 
            $file['size'], 
            $fileType, 
            $_SESSION['user_id']
        ])) {
            return ['ok'=>true];
        }
    }
    return ['ok'=>false, 'error'=>'File upload failed check permissions.'];
}



/**
 * دالة مساعدة لتنسيق حجم الملف.
 * تحول الحجم من بايت (Bytes) إلى صيغة مقروءة (KB, MB, GB).
 * * @param int $bytes الحجم بالبايت
 * @return string الحجم المنسق
 */

/**
 * دالة مساعدة لتنسيق حجم الملف.
 */
function formatSizeUnits($bytes) {
    if ($bytes >= 1073741824) { $bytes = number_format($bytes / 1073741824, 2) . ' GB'; }
    elseif ($bytes >= 1048576) { $bytes = number_format($bytes / 1048576, 2) . ' MB'; }
    elseif ($bytes >= 1024) { $bytes = number_format($bytes / 1024, 2) . ' KB'; }
    elseif ($bytes > 1) { $bytes = $bytes . ' bytes'; }
    elseif ($bytes == 1) { $bytes = $bytes . ' byte'; }
    else { $bytes = '0 bytes'; }
    return $bytes;
}
// ... (في نهاية الملف أو قسم المستندات)

/**
 * دالة جديدة: جلب قائمة مهام المشروع (للقائمة المنسدلة)
 *//*
function getAllProjectTasks($project_id) {
    $db = Database::getInstance()->pdo();
    $stmt = $db->prepare("SELECT id, title FROM project_tasks WHERE project_id = ? AND is_deleted = 0 ORDER BY title ASC");
    $stmt->execute([$project_id]);
    return $stmt->fetchAll();
}*/

/**
 * تحديث دالة الرفع لتقبل parent_type و parent_id
 *//*
function uploadProjectDocument($data, $file) {
    $db = Database::getInstance()->pdo();
    
    $targetDir = __DIR__ . "/../../assets/uploads/documents/";
    if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
    
    $fileName = time() . '_' . basename($file["name"]);
    $targetFilePath = $targetDir . $fileName;
    $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
    
    // Default to project if not specified
    $parentType = $data['parent_type'] ?? 'project';
    $parentId   = $data['parent_id'] ?? $data['project_id'];

    if (move_uploaded_file($file["tmp_name"], $targetFilePath)) {
        $stmt = $db->prepare("
            INSERT INTO documents 
            (parent_type, parent_id, title, description, file_name, file_path, file_size, file_type, uploaded_by, uploaded_at, created_at)
            VALUES 
            (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        if ($stmt->execute([
            $parentType, 
            $parentId, 
            $data['title'], 
            $data['description'], 
            $fileName, 
            'assets/uploads/documents/' . $fileName, 
            $file['size'], 
            $fileType, 
            $_SESSION['user_id']
        ])) {
            return ['ok'=>true];
        }
    }
    return ['ok'=>false, 'error'=>'File upload failed.'];
}*/
?>