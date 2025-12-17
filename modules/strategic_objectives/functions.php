<?php
require_once "../../core/init.php";

class StrategicObjectiveService
{
    /**
     * Get strategic objective by ID
     */
    public static function getById(int $id)
    {
        $db = Database::getInstance()->pdo();
        $stmt = $db->prepare("
            SELECT so.*, 
                   p.pillar_number, 
                   p.name AS pillar_name
            FROM strategic_objectives so
            LEFT JOIN pillars p ON so.pillar_id = p.id
            WHERE so.id = ?
            LIMIT 1
        ");

        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Check if unique code exists (except current id)
     */
    public static function isCodeExists(string $code, int $excludeId = 0): bool
    {
        $db = Database::getInstance()->pdo();
        $stmt = $db->prepare("SELECT COUNT(*) FROM strategic_objectives WHERE objective_code = ? AND id != ?");
        $stmt->execute([$code, $excludeId]);

        return $stmt->fetchColumn() > 0;
    }

    /**
     * Get all pillars for dropdown
     */
    public static function getAllPillars(): array
    {
        $db = Database::getInstance()->pdo();
        return $db->query("
            SELECT id, pillar_number, name 
            FROM pillars 
            ORDER BY pillar_number ASC
        ")->fetchAll();
    }

    /**
     * Get linked initiatives for objective
     */
    public static function getLinkedInitiatives(int $objId): array
    {
        $db = Database::getInstance()->pdo();

        $stmt = $db->prepare("
            SELECT id, initiative_code, name, status_id
            FROM initiatives
            WHERE strategic_objective_id = ?
            ORDER BY id DESC
        ");
        $stmt->execute([$objId]);

        return $stmt->fetchAll();
    }

    /**
     * Create objective
     */
    public static function create(array $data): int
    {
        $db = Database::getInstance()->pdo();

        $stmt = $db->prepare("
            INSERT INTO strategic_objectives (pillar_id, objective_code, objective_text)
            VALUES (?, ?, ?)
        ");

        $stmt->execute([
            $data['pillar_id'],
            $data['objective_code'],
            $data['objective_text']
        ]);

        return $db->lastInsertId();
    }

    /**
     * Update objective
     */
    public static function update(int $id, array $data): bool
    {
        $db = Database::getInstance()->pdo();

        $stmt = $db->prepare("
            UPDATE strategic_objectives
            SET pillar_id = ?, 
                objective_code = ?, 
                objective_text = ?
            WHERE id = ?
        ");

        return $stmt->execute([
            $data['pillar_id'],
            $data['objective_code'],
            $data['objective_text'],
            $id
        ]);
    }

    /**
     * Delete objective
     */
    public static function delete(int $id): bool
    {
        $db = Database::getInstance()->pdo();

        // Check if initiatives are linked — block delete
        $stmt = $db->prepare("SELECT COUNT(*) FROM initiatives WHERE strategic_objective_id = ?");
        $stmt->execute([$id]);

        if ($stmt->fetchColumn() > 0) {
            return false; // still linked, prevent delete
        }

        $stmt = $db->prepare("DELETE FROM strategic_objectives WHERE id = ?");
        return $stmt->execute([$id]);
    }
}


/**
 * Helper — used directly in view.php
 */

function getObjectiveLinkedInitiatives($id)
{
    return StrategicObjectiveService::getLinkedInitiatives($id);
}

function getAllPillarsForSelect()
{
    return StrategicObjectiveService::getAllPillars();
}
function getStrategicObjectiveById($id) {
    $db = Database::getInstance()->pdo();
    $stmt = $db->prepare("SELECT * FROM strategic_objectives WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function deleteStrategicObjective($id) {
    $db = Database::getInstance()->pdo();
    $stmt = $db->prepare("DELETE FROM strategic_objectives WHERE id = ?");
    return $stmt->execute([$id]);
}