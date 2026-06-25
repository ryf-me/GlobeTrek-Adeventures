<?php
/**
 * AJAX Staff Assignment Endpoint
 *
 * Handles staff assignment/unassignment via AJAX requests.
 * Returns JSON responses.
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['admin', 'staff'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/csrf.php';
require_once __DIR__ . '/../../config/logger.php';

$db = getDB();
$action = $_POST['action'] ?? '';

if (!validateCSRFToken($_POST['csrf_token'] ?? null)) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    exit;
}

if ($action === 'assign') {
    $staffId = (int)($_POST['staff_id'] ?? 0);
    $entityType = $_POST['entity_type'] ?? '';
    $entityId = (int)($_POST['entity_id'] ?? 0);

    if ($staffId <= 0 || !in_array($entityType, ['booking', 'inquiry']) || $entityId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        exit;
    }

    // Check if already assigned
    $checkStmt = $db->prepare("SELECT id FROM staff_assignments WHERE staff_id = :sid AND entity_type = :type AND entity_id = :eid LIMIT 1");
    $checkStmt->execute([':sid' => $staffId, ':type' => $entityType, ':eid' => $entityId]);

    if ($checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Already assigned']);
        exit;
    }

    try {
        $stmt = $db->prepare(
            "INSERT INTO staff_assignments (staff_id, entity_type, entity_id, assigned_by)
             VALUES (:sid, :type, :eid, :assigned_by)"
        );
        $stmt->execute([
            ':sid' => $staffId,
            ':type' => $entityType,
            ':eid' => $entityId,
            ':assigned_by' => $_SESSION['user_id'],
        ]);

        logActivity('staff_assigned', 'assignment', $db->lastInsertId(), "Staff #$staffId assigned to $entityType #$entityId");

        // Get staff name for response
        $staffStmt = $db->prepare("SELECT u.full_name FROM staff_profiles sp JOIN users u ON sp.user_id = u.id WHERE sp.id = :sid");
        $staffStmt->execute([':sid' => $staffId]);
        $staffName = $staffStmt->fetchColumn();

        echo json_encode([
            'success' => true,
            'message' => 'Staff assigned successfully',
            'staff_name' => $staffName,
            'assignment_id' => $db->lastInsertId(),
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    exit;
}

if ($action === 'unassign') {
    $assignmentId = (int)($_POST['assignment_id'] ?? 0);

    if ($assignmentId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid assignment ID']);
        exit;
    }

    try {
        $stmt = $db->prepare("DELETE FROM staff_assignments WHERE id = :id");
        $stmt->execute([':id' => $assignmentId]);

        logActivity('staff_unassigned', 'assignment', $assignmentId, 'Staff assignment removed');

        echo json_encode(['success' => true, 'message' => 'Assignment removed']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    exit;
}

if ($action === 'get_assigned') {
    $entityType = $_GET['entity_type'] ?? '';
    $entityId = (int)($_GET['entity_id'] ?? 0);

    if (!in_array($entityType, ['booking', 'inquiry']) || $entityId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        exit;
    }

    $stmt = $db->prepare(
        "SELECT sa.id AS assignment_id, sa.assigned_at,
                sp.id AS staff_id, sp.department, sp.position,
                u.full_name AS staff_name
         FROM staff_assignments sa
         JOIN staff_profiles sp ON sa.staff_id = sp.id
         JOIN users u ON sp.user_id = u.id
         WHERE sa.entity_type = :type AND sa.entity_id = :eid
         ORDER BY u.full_name ASC"
    );
    $stmt->execute([':type' => $entityType, ':eid' => $entityId]);
    $assigned = $stmt->fetchAll();

    echo json_encode(['success' => true, 'assigned' => $assigned]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
