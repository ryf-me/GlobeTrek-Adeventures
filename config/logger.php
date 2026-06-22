<?php
function logActivity(string $action, string $entityType, ?int $entityId = null, ?string $details = null): void
{
    try {
        $db = getDB();
        $userId = $_SESSION['user_id'] ?? null;
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;

        $stmt = $db->prepare(
            "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, details, ip_address)
             VALUES (:user_id, :action, :entity_type, :entity_id, :details, :ip_address)"
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':action' => $action,
            ':entity_type' => $entityType,
            ':entity_id' => $entityId,
            ':details' => $details,
            ':ip_address' => $ipAddress,
        ]);
    } catch (Exception $e) {
        // Silently fail - logging should never break the app
    }
}
