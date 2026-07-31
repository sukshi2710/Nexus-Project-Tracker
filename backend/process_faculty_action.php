<?php
session_start();
header('Content-Type: application/json');
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Faculty') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized operation.']);
    exit;
}

$groupId = intval($_POST['group_id'] ?? 0);
$phaseNum = intval($_POST['phase_number'] ?? 0);
$action = $_POST['action'] ?? '';
$feedback = trim($_POST['rejection_feedback'] ?? '');

if (!$groupId || !in_array($action, ['Approve', 'Reject'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters supplied.']);
    exit;
}

try {
    $pdo->beginTransaction();

    if ($phaseNum === 0) {
        // Phase 0 Title Pitch processing
        if ($action === 'Approve') {
            $stmt = $pdo->prepare("UPDATE project_groups SET title_status = 'Approved', current_phase = 1 WHERE id = ?");
            $stmt->execute([$groupId]);
            $msg = 'Title proposal approved. Group advanced to Phase 1.';
        } else {
            $stmt = $pdo->prepare("UPDATE project_groups SET title_status = 'Rejected' WHERE id = ?");
            $stmt->execute([$groupId]);
            $msg = 'Title proposal rejected.';
        }
    } else {
        // Phase 1 to 4 Sprint processing
        if ($action === 'Approve') {
            $stmtM = $pdo->prepare("UPDATE milestone_ledger SET submission_status = 'Approved', rejection_feedback = NULL WHERE group_id = ? AND phase_number = ?");
            $stmtM->execute([$groupId, $phaseNum]);

            // Advance project group state
            $stmtG = $pdo->prepare("SELECT current_phase FROM project_groups WHERE id = ?");
            $stmtG->execute([$groupId]);
            $curr = $stmtG->fetchColumn();

            $nextPhase = ($curr >= 4) ? 5 : ($curr + 1);

            $stmtU = $pdo->prepare("UPDATE project_groups SET current_phase = ? WHERE id = ?");
            $stmtU->execute([$nextPhase, $groupId]);

            $msg = 'Sprint phase ' . $phaseNum . ' approved. Group current phase advanced to ' . $nextPhase;
        } else {
            $stmtM = $pdo->prepare("UPDATE milestone_ledger SET submission_status = 'Rejected', rejection_feedback = ? WHERE group_id = ? AND phase_number = ?");
            $stmtM->execute([$feedback, $groupId, $phaseNum]);
            $msg = 'Sprint phase ' . $phaseNum . ' rejected with revision feedback.';
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => $msg]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>