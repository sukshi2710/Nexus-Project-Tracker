<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Faculty') {
    echo 'Unauthorized access.';
    exit;
}

$facultyId = $_SESSION['user_id'];
$type = $_GET['type'] ?? 'ongoing';

if ($type === 'ongoing') {
    $stmt = $pdo->prepare("
        SELECT g.*, 
               (SELECT COUNT(*) FROM users u WHERE u.group_id = g.id) AS student_count
        FROM project_groups g 
        WHERE g.faculty_id = ? AND g.current_phase < 5 
        ORDER BY g.id DESC
    ");
    $stmt->execute([$facultyId]);
    $groups = $stmt->fetchAll();

    if (empty($groups)) {
        echo '<div class="text-muted p-3">No active ongoing projects assigned to you.</div>';
        exit;
    }
?>
    <table class="table table-hover align-middle mb-0">
        <thead class="table-dark">
            <tr>
                <th>Group Name</th>
                <th>Project Title</th>
                <th>Title Status</th>
                <th style="min-width: 180px;">Sprint Progress</th>
                <th>Team Size</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($groups as $g):
                $pct = min(100, intval(($g['current_phase'] / 5) * 100));
            ?>
                <tr>
                    <td><strong><?= htmlspecialchars($g['group_name']) ?></strong></td>
                    <td><?= htmlspecialchars($g['project_title'] ?? 'Not Proposed Yet') ?></td>
                    <td>
                        <span class="badge bg-<?= $g['title_status'] === 'Approved' ? 'success' : ($g['title_status'] === 'Rejected' ? 'danger' : 'warning') ?>">
                            <?= $g['title_status'] ?>
                        </span>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="progress flex-grow-1 me-2">
                                <div class="progress-bar" style="width: <?= $pct ?>%"></div>
                            </div>
                            <small class="text-muted"><?= $g['current_phase'] ?>/5</small>
                        </div>
                    </td>
                    <td><span class="badge bg-secondary"><?= $g['student_count'] ?> Student(s)</span></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php
} elseif ($type === 'archived') {
    $stmt = $pdo->prepare("
        SELECT g.*, 
               (SELECT GROUP_CONCAT(name SEPARATOR ', ') FROM users u WHERE u.group_id = g.id) AS team_members
        FROM project_groups g 
        WHERE g.faculty_id = ? AND g.current_phase >= 5 
        ORDER BY g.id DESC
    ");
    $stmt->execute([$facultyId]);
    $completed = $stmt->fetchAll();

    if (empty($completed)) {
        echo '<div class="text-muted p-3">No archived or completed projects found.</div>';
        exit;
    }
?>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Group Name</th>
                    <th>Project Title</th>
                    <th>Team Members</th>
                    <th>Deliverables</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($completed as $c): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($c['group_name']) ?></strong></td>
                        <td><?= htmlspecialchars($c['project_title'] ?? 'N/A') ?></td>
                        <td><small class="text-muted"><?= htmlspecialchars($c['team_members'] ?? 'None') ?></small></td>
                        <td>
                            <?php if (!empty($c['github_link'])): ?>
                                <a href="<?= htmlspecialchars($c['github_link']) ?>" target="_blank" class="btn btn-sm btn-outline-light me-1">GitHub</a>
                                <a href="<?= htmlspecialchars($c['doc_link']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">Docs</a>
                            <?php else: ?>
                                <span class="text-muted small">No Links</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php
}
?>