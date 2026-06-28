<?php
/**
 * File: admin/ajax/assign-staff.php
 * Purpose: AJAX endpoint for assigning/unassigning staff to bookings/inquiries, and retrieving current assignments.
 * Dependencies: config/database.php, config/csrf.php, config/logger.php
 * Used By: admin/booking-detail.php, admin/inquiries.php (via AJAX calls)
 * Parent Files: none (standalone AJAX endpoint)
 * Child Files: config/database.php, config/csrf.php, config/logger.php
 * @package GlobeTrek\Admin
 */

// === SESSION START ===
// Must start session to access $_SESSION for authentication.
session_start();

// === JSON RESPONSE HEADER ===
// All responses are JSON — set the content type header upfront.
header('Content-Type: application/json');

// === AUTHENTICATION CHECK ===
// Only admin and staff users can use this endpoint. Returns 403 if unauthorized.
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['admin', 'staff'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// === CONFIGURATION ===
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/csrf.php';
require_once __DIR__ . '/../../config/logger.php';

$db = getDB();
$action = $_POST['action'] ?? '';

// === CSRF VALIDATION ===
// Prevent cross-site request forgery on POST actions (assign/unassign).
if (!validateCSRFToken($_POST['csrf_token'] ?? null)) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    exit;
}

// === ACTION: ASSIGN STAFF ===
if ($action === 'assign') {
    // Cast and validate input parameters
    $staffId = (int)($_POST['staff_id'] ?? 0);
    $entityType = $_POST['entity_type'] ?? '';
    $entityId = (int)($_POST['entity_id'] ?? 0);

    // Validate: staff_id must be positive, entity_type must be 'booking' or 'inquiry', entity_id must be positive
    if ($staffId <= 0 || !in_array($entityType, ['booking', 'inquiry']) || $entityId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        exit;
    }

    // === DUPLICATE CHECK ===
    // Prevent assigning the same staff member to the same entity twice.
    $checkStmt = $db->prepare("SELECT id FROM staff_assignments WHERE staff_id = :sid AND entity_type = :type AND entity_id = :eid LIMIT 1");
    $checkStmt->execute([':sid' => $staffId, ':type' => $entityType, ':eid' => $entityId]);

    if ($checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Already assigned']);
        exit;
    }

    try {
        // === INSERT ASSIGNMENT ===
        // Records who made the assignment (assigned_by) for audit purposes.
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

        // === ACTIVITY LOG ===
        // Log the assignment action for the system audit trail.
        logActivity('staff_assigned', 'assignment', $db->lastInsertId(), "Staff #$staffId assigned to $entityType #$entityId");

        // === FETCH STAFF NAME FOR RESPONSE ===
        // The client needs the staff member's name to update the UI without a page reload.
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
        // Silently catch DB errors — don't expose internal details to the client
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    exit;
}

// === ACTION: UNASSIGN STAFF ===
if ($action === 'unassign') {
    $assignmentId = (int)($_POST['assignment_id'] ?? 0);

    if ($assignmentId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid assignment ID']);
        exit;
    }

    try {
        // Delete the assignment record by its primary key
        $stmt = $db->prepare("DELETE FROM staff_assignments WHERE id = :id");
        $stmt->execute([':id' => $assignmentId]);

        logActivity('staff_unassigned', 'assignment', $assignmentId, 'Staff assignment removed');

        echo json_encode(['success' => true, 'message' => 'Assignment removed']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    exit;
}

// === ACTION: GET ASSIGNED STAFF ===
// Returns all staff members assigned to a given entity (booking or inquiry).
// Uses GET parameters since this is a read-only operation.
if ($action === 'get_assigned') {
    $entityType = $_GET['entity_type'] ?? '';
    $entityId = (int)($_GET['entity_id'] ?? 0);

    if (!in_array($entityType, ['booking', 'inquiry']) || $entityId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        exit;
    }

    // Join assignments with staff profiles and users to get full staff details
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

// === FALLBACK: Unknown action ===
echo json_encode(['success' => false, 'message' => 'Unknown action']);
