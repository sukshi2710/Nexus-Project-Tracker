<?php
session_start();
header('Content-Type: application/json');
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized operation.']);
    exit;
}

$studentId = intval($_POST['student_id'] ?? 0);
$action = $_POST['action'] ?? '';

if (!$studentId || !in_array($action, ['unassign', 'delete'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid student ID or action.']);
    exit;
}

try {
    if ($action === 'unassign') {
        // Set group_id to NULL to unassign the student
        $stmt = $pdo->prepare("UPDATE users SET group_id = NULL WHERE id = ? AND role = 'Student'");
        $stmt->execute([$studentId]);
        echo json_encode(['success' => true, 'message' => 'Student unassigned from project group successfully.']);
    } elseif ($action === 'delete') {
        // Delete student account permanently
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'Student'");
        $stmt->execute([$studentId]);
        echo json_encode(['success' => true, 'message' => 'Student account permanently removed.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>