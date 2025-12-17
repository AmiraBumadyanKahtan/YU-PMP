<?php
// =========================================================
// 6. DOCUMENTS MANAGEMENT
// =========================================================

/*function getProjectDocuments($project_id) {
    $db = Database::getInstance()->pdo();
    $sql = "
        SELECT d.*, u.full_name_en as uploader_name 
        FROM documents d
        JOIN users u ON u.id = d.uploaded_by
        WHERE d.parent_type = 'project' AND d.parent_id = ? AND d.is_deleted = 0
        ORDER BY d.uploaded_at DESC
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([$project_id]);
    return $stmt->fetchAll();
}*/
function getProjectDocuments($project_id) {
    $db = Database::getInstance()->pdo();
    $sql = "
        SELECT d.*, u.full_name_en as uploader_name 
        FROM documents d
        JOIN users u ON u.id = d.uploaded_by
        WHERE d.is_deleted = 0 
        AND (
            (d.parent_type = 'project' AND d.parent_id = ?) OR
            (d.parent_type = 'milestone' AND d.parent_id IN (SELECT id FROM project_milestones WHERE project_id = ?)) OR
            (d.parent_type = 'task' AND d.parent_id IN (SELECT id FROM project_tasks WHERE project_id = ?)) OR
            (d.parent_type = 'risk' AND d.parent_id IN (SELECT id FROM risk_assessments WHERE parent_type='project' AND parent_id = ?))
        )
        ORDER BY d.uploaded_at DESC
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([$project_id, $project_id, $project_id, $project_id]);
    return $stmt->fetchAll();
}

/*function uploadProjectDocument($data, $file) {
    $db = Database::getInstance()->pdo();
    
    // إعدادات الرفع
    $targetDir = __DIR__ . "/../../assets/uploads/documents/";
    if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
    
    $fileName = time() . '_' . basename($file["name"]);
    $targetFilePath = $targetDir . $fileName;
    $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
    
    // التحقق من النوع والحجم (اختياري)
    $allowedTypes = ['jpg','png','jpeg','gif','pdf','doc','docx','xls','xlsx','ppt','pptx','txt'];
    if (!in_array($fileType, $allowedTypes)) return ['ok'=>false, 'error'=>'Invalid file type.'];
    
    if (move_uploaded_file($file["tmp_name"], $targetFilePath)) {
        $stmt = $db->prepare("
            INSERT INTO documents 
            (parent_type, parent_id, title, description, file_name, file_path, file_size, file_type, uploaded_by, uploaded_at, created_at)
            VALUES 
            ('project', ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        if ($stmt->execute([
            $data['project_id'], 
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

/*function deleteDocument($doc_id) {
    $db = Database::getInstance()->pdo();
    // Soft Delete
    return $db->prepare("UPDATE documents SET is_deleted = 1, deleted_at = NOW() WHERE id = ?")->execute([$doc_id]);
}*/

/**
 * دالة مساعدة لتنسيق حجم الملف
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
 */
function getAllProjectTasks($project_id) {
    $db = Database::getInstance()->pdo();
    $stmt = $db->prepare("SELECT id, title FROM project_tasks WHERE project_id = ? AND is_deleted = 0 ORDER BY title ASC");
    $stmt->execute([$project_id]);
    return $stmt->fetchAll();
}

/**
 * تحديث دالة الرفع لتقبل parent_type و parent_id
 */
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
}
?>