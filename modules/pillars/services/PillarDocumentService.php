<?php
// modules/pillars/services/PillarDocumentService.php

require_once __DIR__ . '/../../../core/Database.php';

class PillarDocumentService {

    public static function getAll($pillar_id) {
        $db = Database::getInstance()->pdo();
        $stmt = $db->prepare("
            SELECT d.*, u.full_name_en as uploader_name 
            FROM documents d
            JOIN users u ON u.id = d.uploaded_by
            WHERE d.parent_type = 'pillar' AND d.parent_id = ? AND d.is_deleted = 0
            ORDER BY d.uploaded_at DESC
        ");
        $stmt->execute([$pillar_id]);
        return $stmt->fetchAll();
    }

    public static function upload($data, $file, $user_id) {
        $db = Database::getInstance()->pdo();
        $targetDir = __DIR__ . "/../../../assets/uploads/documents/";
        if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
        
        $fileName = time() . '_' . basename($file["name"]);
        $targetFilePath = $targetDir . $fileName;
        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
        
        if (move_uploaded_file($file["tmp_name"], $targetFilePath)) {
            $stmt = $db->prepare("
                INSERT INTO documents (parent_type, parent_id, title, description, file_name, file_path, file_size, file_type, uploaded_by, uploaded_at)
                VALUES ('pillar', ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            return $stmt->execute([
                $data['pillar_id'], $data['title'], $data['description'], 
                $fileName, 'assets/uploads/documents/' . $fileName, 
                $file['size'], $fileType, $user_id
            ]);
        }
        return false;
    }

    public static function delete($doc_id) {
        // نستخدم شرط التحقق لتجنب التضارب إذا كانت الدالة معرفة في مكان آخر
        if (!function_exists('deleteDocument')) {
             $db = Database::getInstance()->pdo();
             return $db->prepare("UPDATE documents SET is_deleted=1 WHERE id=?")->execute([$doc_id]);
        } else {
             return deleteDocument($doc_id);
        }
    }
}
?>