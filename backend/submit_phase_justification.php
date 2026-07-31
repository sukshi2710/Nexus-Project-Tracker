<?php
session_start();
header('Content-Type: application/json');
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized request.']);
    exit;
}

$groupId = $_SESSION['group_id'];
$action = $_POST['action'] ?? '';

if (!$groupId) {
    echo json_encode(['success' => false, 'message' => 'Student not assigned to a group.']);
    exit;
}

if ($action === 'title_pitch') {
    $title = trim($_POST['project_title'] ?? '');
    $abstract = trim($_POST['project_abstract'] ?? '');

    if (empty($title) || empty($abstract)) {
        echo json_encode(['success' => false, 'message' => 'Title and Abstract are required.']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE project_groups SET project_title = ?, project_abstract = ?, title_status = 'Pending' WHERE id = ?");
    $stmt->execute([$title, $abstract, $groupId]);

    echo json_encode(['success' => true, 'message' => 'Title proposal submitted for advisor review.']);
    exit;
} 

if ($action === 'sprint_submission') {
    $phaseNum = intval($_POST['phase_number'] ?? 0);
    $text = trim($_POST['justification_text'] ?? '');
    $charLen = mb_strlen($text);

    if ($charLen < 150 || $charLen > 200) {
        echo json_encode(['success' => false, 'message' => "Strict Guard Triggered: Submission length ($charLen) must be strictly between 150 and 200 characters."]);
        exit;
    }

    // Insert or update pending milestone
    $stmt = $pdo->prepare("INSERT INTO milestone_ledger (group_id, phase_number, justification_text, submission_status) 
                           VALUES (?, ?, ?, 'Pending') 
                           ON DUPLICATE KEY UPDATE justification_text = VALUES(justification_text), submission_status = 'Pending'");
    $stmt->execute([$groupId, $phaseNum, $text]);

    echo json_encode(['success' => true, 'message' => 'Sprint brief successfully logged and sent for evaluation.']);
    exit;
}

if ($action === 'final_gate') {
    $github = trim($_POST['github_link'] ?? '');
    $doc = trim($_POST['doc_link'] ?? '');

    if (empty($github) || empty($doc)) {
        echo json_encode(['success' => false, 'message' => 'All deliverable URLs are required.']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE project_groups SET github_link = ?, doc_link = ? WHERE id = ?");
    $stmt->execute([$github, $doc, $groupId]);

    echo json_encode(['success' => true, 'message' => 'Final project deliverables submitted successfully.']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action request.']);
?>