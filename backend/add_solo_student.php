<?php
session_start();
header('Content-Type: application/json');
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized operation.']);
    exit;
}

$reg = trim($_POST['solo_reg'] ?? '');
$name = trim($_POST['solo_name'] ?? '');
$email = trim($_POST['solo_email'] ?? '');

if (empty($reg) || empty($name) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'All solo deployment fields are required.']);
    exit;
}

// Fetch random faculty advisor
$stmt = $pdo->query("SELECT id FROM users WHERE role = 'Faculty' ORDER BY RAND() LIMIT 1");
$facultyId = $stmt->fetchColumn();

if (!$facultyId) {
    echo json_encode(['success' => false, 'message' => 'No faculty member available for assignment.']);
    exit;
}

try {
    $pdo->beginTransaction();

    $groupName = "SOLO-" . $reg;
    $stmtG = $pdo->prepare("INSERT INTO project_groups (group_name, faculty_id) VALUES (?, ?)");
    $stmtG->execute([$groupName, $facultyId]);
    $groupId = $pdo->lastInsertId();

    $defaultPassword = password_hash('password123', PASSWORD_BCRYPT);
    $stmtU = $pdo->prepare("INSERT INTO users (register_number, name, email, role, password, group_id) VALUES (?, ?, ?, 'Student', ?, ?)");
    $stmtU->execute([$reg, $name, $email, $defaultPassword, $groupId]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Solo deployment successfully provisioned for ' . $name]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>