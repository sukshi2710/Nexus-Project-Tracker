<?php
session_start();
header('Content-Type: application/json');
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized operation.']);
    exit;
}

$rawData = $_POST['raw_data'] ?? '';
$perGroup = intval($_POST['students_per_group'] ?? 4);

if (empty(trim($rawData))) {
    echo json_encode(['success' => false, 'message' => 'Roster input text cannot be empty.']);
    exit;
}

// Fetch all available faculty
$stmt = $pdo->query("SELECT id FROM users WHERE role = 'Faculty'");
$facultyIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($facultyIds)) {
    echo json_encode(['success' => false, 'message' => 'No faculty members available in the database for assignment.']);
    exit;
}

$lines = explode("\n", str_replace("\r", "", $rawData));
$students = [];

foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line)) continue;
    $parts = explode(",", $line);
    if (count($parts) === 3) {
        $students[] = [
            'reg' => trim($parts[0]),
            'name' => trim($parts[1]),
            'email' => trim($parts[2])
        ];
    }
}

if (empty($students)) {
    echo json_encode(['success' => false, 'message' => 'Failed to parse any valid student lines. Format: Reg, Name, Email']);
    exit;
}

// Round-Robin shuffling & allocation
shuffle($students);
$studentChunks = array_chunk($students, $perGroup);
$facultyCount = count($facultyIds);
$facultyIndex = 0;
$defaultPassword = password_hash('password123', PASSWORD_BCRYPT);

try {
    $pdo->beginTransaction();

    foreach ($studentChunks as $idx => $chunk) {
        $groupName = "AIT-TEAM-" . (time() + $idx);
        $assignedFaculty = $facultyIds[$facultyIndex % $facultyCount];
        $facultyIndex++;

        // Create group
        $stmtG = $pdo->prepare("INSERT INTO project_groups (group_name, faculty_id) VALUES (?, ?)");
        $stmtG->execute([$groupName, $assignedFaculty]);
        $groupId = $pdo->lastInsertId();

        // Insert students and map group_id
        $stmtU = $pdo->prepare("INSERT INTO users (register_number, name, email, role, password, group_id) VALUES (?, ?, ?, 'Student', ?, ?)");
        foreach ($chunk as $st) {
            $stmtU->execute([$st['reg'], $st['name'], $st['email'], $defaultPassword, $groupId]);
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Successfully processed ' . count($students) . ' students into ' . count($studentChunks) . ' project group(s).']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>