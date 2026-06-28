<?php
/**
 * File: config/logger.php
 * Purpose: Activity audit logging system for tracking user and system actions
 *
 * This file provides:
 *   1. A single logActivity() function for recording audit trail entries
 *   2. Automatic capture of user ID and IP address
 *   3. Fail-silent design (logging never breaks the application)
 *
 * Dependencies:
 *   - config/database.php (for getDB() — accessed via function call)
 *
 * Used By:
 *   - admin/bookings.php (log status changes)
 *   - admin/inquiries.php (log replies and status changes)
 *   - admin/users.php (log role changes and deletions)
 *   - admin/staff.php (log availability toggles and deletions)
 *   - admin/testimonials.php (log approval/rejection actions)
 *   - pages/cancel-booking.php (log booking cancellations)
 *   - config/error-handler.php (log system errors)
 *
 * Parent Files: None (loaded via require_once)
 * Child Files: None (no includes — uses getDB() at runtime)
 *
 * Database Table: activity_logs
 *   Columns: id, user_id, action, entity_type, entity_id, details, ip_address, created_at
 *
 * @package GlobeTrek\Config
 */

// =============================================================================
// ACTIVITY LOGGING FUNCTION
// =============================================================================
/**
 * Log an activity to the activity_logs database table.
 *
 * Records the who, what, when, and where of each action for audit purposes.
 * Automatically captures the current user's ID and IP address from the session.
 *
 * Fail-Silent Design:
 *   If the database is unavailable or the insert fails, the error is silently
 *   ignored. This ensures that logging never causes a user-facing error.
 *   The trade-off is that log entries may be lost during database outages.
 *
 * @param string     $action     The action performed (e.g., 'booking_cancelled', 'status_updated')
 * @param string     $entityType The type of entity (e.g., 'booking', 'inquiry', 'user')
 * @param int|null   $entityId   The ID of the affected entity (null for system-level actions)
 * @param string|null $details   Additional details about the action (free-form text)
 * @return void
 *
 * Usage:
 *   logActivity('booking_cancelled', 'booking', $bookingId, 'User cancelled booking #12345');
 *   logActivity('status_updated', 'inquiry', $inquiryId, 'Status changed from pending to resolved');
 *   logActivity('system_error', 'error', null, 'Database connection failed');
 */
function logActivity(string $action, string $entityType, ?int $entityId = null, ?string $details = null): void
{
    try {
        $db = getDB();

        // Capture the current user's ID from the session
        // null for guest users or system-level actions
        $userId = $_SESSION['user_id'] ?? null;

        // Capture the client's IP address for audit purposes
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;

        // Insert the activity log entry
        $stmt = $db->prepare(
            "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, details, ip_address)
             VALUES (:user_id, :action, :entity_type, :entity_id, :details, :ip_address)"
        );
        $stmt->execute([
            ':user_id'    => $userId,
            ':action'     => $action,
            ':entity_type'=> $entityType,
            ':entity_id'  => $entityId,
            ':details'    => $details,
            ':ip_address' => $ipAddress,
        ]);
    } catch (Exception $e) {
        // SILENTLY FAIL — logging should never break the application
        // If the database is down, log entries are lost but the app continues working
        // This is an intentional design trade-off: availability > logging
    }
}
