<?php
session_start();
header('Content-Type: application/json');
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized operation.']);
    exit;
}

$reg = trim($_POST['fac_reg'] ?? '');
$name = trim($_POST['fac_name'] ?? '');
$email = trim($_POST['fac_email'] ?? '');
$password = trim($_POST['fac_password'] ?? 'password123');

if (empty($reg) || empty($name) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Register ID, Name, and Email are required.']);
    exit;
}

try {
    // Check if register number or email already exists
    $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE register_number = ? OR email = ?");
    $stmtCheck->execute([$reg, $email]);
    if ($stmtCheck->fetch()) {
        echo json_encode(['success' => false, 'message' => 'A user with this Register Number or Email already exists.']);
        exit;
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO users (register_number, name, email, role, password, group_id) VALUES (?, ?, ?, 'Faculty', ?, NULL)");
    $stmt->execute([$reg, $name, $email, $hash]);

    echo json_encode(['success' => true, 'message' => "Faculty member '$name' created successfully."]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>