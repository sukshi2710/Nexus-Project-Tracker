<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    echo 'Unauthorized access.';
    exit;
}

$type = $_GET['type'] ?? '';

if ($type === 'students') {
    $stmt = $pdo->query("SELECT u.*, g.group_name FROM users u LEFT JOIN project_groups g ON u.group_id = g.id WHERE u.role = 'Student' ORDER BY u.id DESC");
    $rows = $stmt->fetchAll();

    if (empty($rows)) {
        echo '<div class="text-muted p-3">No student records found.</div>';
        exit;
    }
?>
    <div class="table-responsive">
        <table class="table table-striped align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Reg No</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Assigned Group</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($r['register_number']) ?></code></td>
                        <td><?= htmlspecialchars($r['name']) ?></td>
                        <td><?= htmlspecialchars($r['email']) ?></td>
                        <td>
                            <?php if ($r['group_name']): ?>
                                <span class="badge bg-primary"><?= htmlspecialchars($r['group_name']) ?></span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Unassigned</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($r['group_id']): ?>
                                <button class="btn btn-warning btn-sm me-1" onclick="manageStudent(<?= $r['id'] ?>, 'unassign')">
                                    🔗 Unassign
                                </button>
                            <?php endif; ?>
                            <button class="btn btn-danger btn-sm" onclick="manageStudent(<?= $r['id'] ?>, 'delete')">
                                🗑️ Delete
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php
} elseif ($type === 'faculty') {
    $stmt = $pdo->query("SELECT * FROM users WHERE role = 'Faculty' ORDER BY id DESC");
    $rows = $stmt->fetchAll();
?>
    <table class="table table-striped align-middle">
        <thead>
            <tr>
                <th>Faculty Reg ID</th>
                <th>Name</th>
                <th>Email</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><code><?= htmlspecialchars($r['register_number']) ?></code></td>
                    <td><?= htmlspecialchars($r['name']) ?></td>
                    <td><?= htmlspecialchars($r['email']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php
} elseif ($type === 'master') {
    $stmt = $pdo->query("SELECT g.*, u.name AS advisor_name FROM project_groups g LEFT JOIN users u ON g.faculty_id = u.id ORDER BY g.id DESC");
    $rows = $stmt->fetchAll();
?>
    <table class="table table-hover align-middle mb-0">
        <thead class="table-dark">
            <tr>
                <th>Group Name</th>
                <th>Advisor</th>
                <th>Project Title</th>
                <th>Title Status</th>
                <th style="min-width: 180px;">Phase Progress</th>
                <th>Deliverables</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r):
                $pct = min(100, intval(($r['current_phase'] / 5) * 100));
            ?>
                <tr>
                    <td><strong><?= htmlspecialchars($r['group_name']) ?></strong></td>
                    <td><?= htmlspecialchars($r['advisor_name'] ?? 'Unassigned') ?></td>
                    <td><?= htmlspecialchars($r['project_title'] ?? 'N/A') ?></td>
                    <td>
                        <span class="badge bg-<?= $r['title_status'] === 'Approved' ? 'success' : ($r['title_status'] === 'Rejected' ? 'danger' : 'warning') ?>">
                            <?= $r['title_status'] ?>
                        </span>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="progress flex-grow-1 me-2">
                                <div class="progress-bar <?= $pct === 100 ? 'progress-bar-success' : '' ?>" style="width: <?= $pct ?>%"></div>
                            </div>
                            <small class="text-muted"><?= $r['current_phase'] ?>/5</small>
                        </div>
                    </td>
                    <td>
                        <?php if ($r['github_link']): ?>
                            <a href="<?= htmlspecialchars($r['github_link']) ?>" target="_blank" class="btn btn-sm btn-outline-light me-1">GitHub</a>
                            <a href="<?= htmlspecialchars($r['doc_link']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">Docs</a>
                        <?php else: ?>
                            <span class="text-muted small">Pending</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php
}
?>