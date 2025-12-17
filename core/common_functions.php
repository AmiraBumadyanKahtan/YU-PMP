<?php

function deleteDocument($doc_id) {
    $db = Database::getInstance()->pdo();
    // Soft Delete
    return $db->prepare("UPDATE documents SET is_deleted = 1, deleted_at = NOW() WHERE id = ?")->execute([$doc_id]);
}
?>