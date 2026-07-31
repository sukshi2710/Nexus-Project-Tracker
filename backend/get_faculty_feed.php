<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Faculty') {
    echo '<div class="alert alert-danger py-2">Unauthorized access.</div>';
    exit;
}

$facultyId = $_SESSION['user_id'];

// 1. Fetch Title Pitches
$stmtPitches = $pdo->prepare("SELECT * FROM project_groups WHERE faculty_id = ? AND title_status = 'Pending' AND project_title IS NOT NULL");
$stmtPitches->execute([$facultyId]);
$pitches = $stmtPitches->fetchAll();

// 2. Fetch Sprint Justifications
$stmtSprints = $pdo->prepare("SELECT m.*, g.group_name, g.project_title 
                              FROM milestone_ledger m 
                              JOIN project_groups g ON m.group_id = g.id 
                              WHERE g.faculty_id = ? AND m.submission_status = 'Pending'");
$stmtSprints->execute([$facultyId]);
$sprints = $stmtSprints->fetchAll();

if (empty($pitches) && empty($sprints)) {
    echo '<div class="text-center py-4 text-muted small">No pending evaluation items currently in your review queue.</div>';
    exit;
}

foreach ($pitches as $p): ?>
    <div class="feed-item">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0 fw-bold text-primary"><?= htmlspecialchars($p['group_name']) ?></h6>
            <span class="badge badge-pulse bg-warning text-dark">Phase 0: Title Pitch</span>
        </div>
        <p class="mb-1 text-main"><strong>Proposed Title:</strong> <?= htmlspecialchars($p['project_title']) ?></p>
        <p class="text-muted small mb-3"><?= htmlspecialchars($p['project_abstract']) ?></p>
        <div>
            <button class="btn btn-success btn-sm me-2" onclick="processAction(<?= $p['id'] ?>, 0, 'Approve')">✅ Approve Title & Begin Phase 1</button>
            <button class="btn btn-danger btn-sm" onclick="processAction(<?= $p['id'] ?>, 0, 'Reject')">❌ Reject Title Proposal</button>
        </div>
    </div>
<?php endforeach;

foreach ($sprints as $s): ?>
    <div class="feed-item">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0 fw-bold text-primary"><?= htmlspecialchars($s['group_name']) ?></h6>
            <span class="badge badge-pulse bg-primary">Phase <?= $s['phase_number'] ?> Sprint Review</span>
        </div>
        <p class="mb-1 text-main"><strong>Project:</strong> <?= htmlspecialchars($s['project_title']) ?></p>
        <div class="p-3 bg-dark rounded mb-3 border border-secondary">
            <small class="text-muted d-block mb-1"><strong>Student Justification Brief:</strong></small>
            <p class="mb-0 text-main small"><?= htmlspecialchars($s['justification_text']) ?></p>
        </div>
        <div>
            <button class="btn btn-success btn-sm me-2" onclick="processAction(<?= $s['group_id'] ?>, <?= $s['phase_number'] ?>, 'Approve')">✅ Approve Sprint & Advance State</button>
            <button class="btn btn-danger btn-sm" onclick="processAction(<?= $s['group_id'] ?>, <?= $s['phase_number'] ?>, 'Reject')">❌ Reject Phase Sprint</button>
        </div>
    </div>
<?php endforeach;
?>