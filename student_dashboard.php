<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Student') {
    header('Location: index.php');
    exit;
}

$group_id = $_SESSION['group_id'];
$group = null;
$milestones = [];

if ($group_id) {
    $stmt = $pdo->prepare("SELECT g.*, u.name as advisor_name FROM project_groups g LEFT JOIN users u ON g.faculty_id = u.id WHERE g.id = ?");
    $stmt->execute([$group_id]);
    $group = $stmt->fetch();

    $stmt2 = $pdo->prepare("SELECT * FROM milestone_ledger WHERE group_id = ?");
    $stmt2->execute([$group_id]);
    $rows = $stmt2->fetchAll();
    foreach ($rows as $row) {
        $milestones[$row['phase_number']] = $row;
    }
}

// Calculate progress percentage
$progressPercent = $group ? min(100, intval(($group['current_phase'] / 5) * 100)) : 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Nexus - Student Workspace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css?v=2">
</head>

<body>
    <nav class="navbar navbar-expand-lg mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"><span class="brand-badge">NEXUS</span> WORKSPACE</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nexusNavbar" aria-controls="nexusNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end mt-2 mt-lg-0" id="nexusNavbar">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center">
                    <span class="me-lg-3 mb-2 mb-lg-0 text-muted small"><?= htmlspecialchars($_SESSION['display_name']) ?></span>
                    <a href="logout.php" class="btn btn-outline-light btn-sm w-100 w-lg-auto">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mb-5">
        <div id="alertBox"></div>

        <?php if (!$group): ?>
            <div class="alert alert-warning">You are not currently assigned to any project team. Please contact your admin.</div>
        <?php else: ?>
            <!-- PROJECT SUMMARY & PROGRESS BAR -->
            <div class="card p-4 mb-4">
                <div class="row align-items-center">
                    <div class="col-md-7">
                        <span class="badge bg-primary mb-2"><?= htmlspecialchars($group['group_name']) ?></span>
                        <h4 class="mb-1 fw-bold"><?= htmlspecialchars($group['project_title'] ?? 'Title Pending Approval') ?></h4>
                        <p class="text-muted small mb-0">Advisor: <strong><?= htmlspecialchars($group['advisor_name'] ?? 'Unassigned') ?></strong></p>
                    </div>
                    <div class="col-md-5 mt-3 mt-md-0">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small">Overall Progress</span>
                            <span class="fw-bold text-primary small"><?= $progressPercent ?>% Complete</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar <?= $progressPercent === 100 ? 'progress-bar-success' : '' ?>" style="width: <?= $progressPercent ?>%"></div>
                        </div>
                        <small class="text-muted d-block text-end mt-1" style="font-size: 0.75rem;">Current: Phase <?= $group['current_phase'] ?> / 5</small>
                    </div>
                </div>
            </div>

            <!-- PHASE 0: PITCH CARD -->
            <div class="card p-4 mb-4 <?= ($group['current_phase'] > 0 && $group['title_status'] === 'Approved') ? 'card-approved' : '' ?>">
                <div class="card-header-custom">
                    <h5 class="fw-bold mb-0">Phase 0: Project Pitch</h5>
                    <span class="badge bg-<?= $group['title_status'] === 'Approved' ? 'success' : ($group['title_status'] === 'Rejected' ? 'danger' : 'warning') ?>">
                        <?= $group['title_status'] ?>
                    </span>
                </div>
                <?php if ($group['title_status'] === 'Approved'): ?>
                    <div class="alert alert-success mb-0 mt-2">
                        <strong>Approved Title:</strong> <?= htmlspecialchars($group['project_title']) ?>
                        <p class="mb-0 text-muted small mt-1"><?= htmlspecialchars($group['project_abstract']) ?></p>
                    </div>
                <?php else: ?>
                    <?php if ($group['title_status'] === 'Pending' && !empty($group['project_title'])): ?>
                        <div class="alert alert-info mb-0">Awaiting Faculty Approval for: <strong><?= htmlspecialchars($group['project_title']) ?></strong></div>
                    <?php else: ?>
                        <form id="titleForm" class="mt-2">
                            <div class="mb-3">
                                <label class="form-label text-muted small">Project Title</label>
                                <input type="text" id="project_title" class="form-control" placeholder="Enter concise project title" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted small">Project Abstract</label>
                                <textarea id="project_abstract" class="form-control" rows="3" placeholder="Briefly describe the project scope and target audience" required></textarea>
                            </div>
                            <button type="button" class="btn btn-primary" onclick="submitTitlePitch()">Submit Proposal</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- SPRINT CARDS 1 to 4 -->
            <?php
            $phase_names = [
                1 => "Phase 1: Requirements Gathering & SRS",
                2 => "Phase 2: Database Design & Architecture",
                3 => "Phase 3: Frontend Responsive Layout",
                4 => "Phase 4: Component Integration & API Connectivity"
            ];

            for ($p = 1; $p <= 4; $p++):
                $is_unlocked = ($group['title_status'] === 'Approved') && ($group['current_phase'] >= $p);
                $phase_data = $milestones[$p] ?? null;
                $is_approved = ($phase_data && $phase_data['submission_status'] === 'Approved');
                $is_pending = ($phase_data && $phase_data['submission_status'] === 'Pending');
                $is_rejected = ($phase_data && $phase_data['submission_status'] === 'Rejected');
            ?>
                <div class="card p-4 <?= !$is_unlocked ? 'card-locked' : '' ?> <?= $is_approved ? 'card-approved' : ($is_rejected ? 'border-danger' : ($is_unlocked && !$is_pending ? 'card-active' : '')) ?>">
                    <div class="card-header-custom">
                        <h5 class="fw-bold mb-0"><?= $phase_names[$p] ?></h5>
                        <?php if ($is_approved): ?>
                            <span class="badge bg-success">Approved</span>
                        <?php elseif ($is_pending): ?>
                            <span class="badge bg-warning text-dark">Under Faculty Review</span>
                        <?php elseif ($is_rejected): ?>
                            <span class="badge bg-danger">Revision Required</span>
                        <?php elseif ($is_unlocked): ?>
                            <span class="badge bg-primary">Active Sprint</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Locked</span>
                        <?php endif; ?>
                    </div>

                    <?php if ($is_rejected && !empty($phase_data['rejection_feedback'])): ?>
                        <div class="alert alert-danger py-2 mb-3 mt-2">
                            <strong>Faculty Revision Comment:</strong> <?= htmlspecialchars($phase_data['rejection_feedback']) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($is_approved): ?>
                        <p class="text-muted small mb-0 mt-2"><strong>Submitted Justification:</strong> <?= htmlspecialchars($phase_data['justification_text']) ?></p>
                    <?php elseif ($is_pending): ?>
                        <p class="text-muted small mb-0 mt-2"><strong>Pending Review Brief:</strong> <?= htmlspecialchars($phase_data['justification_text']) ?></p>
                    <?php elseif ($is_unlocked || $is_rejected): ?>
                        <form id="form_phase_<?= $p ?>" class="mt-2">
                            <div class="mb-2">
                                <label class="form-label text-muted small">Sprint Execution Brief (150 - 200 characters required)</label>
                                <textarea id="justification_<?= $p ?>" class="form-control" rows="3" oninput="validateChars(<?= $p ?>)" placeholder="Summarize your technical implementation and achievements for this phase..."><?= $is_rejected ? htmlspecialchars($phase_data['justification_text']) : '' ?></textarea>
                                <div class="d-flex justify-content-between mt-1">
                                    <small id="counter_<?= $p ?>" class="char-counter char-invalid">0 / 150-200 characters</small>
                                </div>
                            </div>
                            <button type="button" id="btn_<?= $p ?>" class="btn btn-primary mt-2" disabled onclick="submitSprint(<?= $p ?>)">
                                <?= $is_rejected ? 'Resubmit Revised Brief' : 'Submit Sprint Brief' ?>
                            </button>
                        </form>
                    <?php else: ?>
                        <p class="text-muted small mb-0 mt-2">Complete and clear Phase <?= $p - 1 ?> to unlock this sprint module.</p>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>

            <!-- PHASE 5: DELIVERABLES GATE & CERTIFICATE -->
            <div class="card p-4 mb-5 <?= ($group['current_phase'] < 5) ? 'card-locked' : 'border-success' ?>">
                <div class="card-header-custom">
                    <h5 class="fw-bold mb-0 text-success">Phase 5: Final Deliverables Gate</h5>
                    <span class="badge bg-<?= $group['current_phase'] >= 5 ? 'success' : 'secondary' ?>">
                        <?= $group['current_phase'] >= 5 ? 'Unlocked' : 'Locked' ?>
                    </span>
                </div>
                <?php if ($group['current_phase'] >= 5): ?>
                    <?php if (!empty($group['github_link'])): ?>
                        <div class="alert alert-success mt-2 mb-3">
                            <h6 class="fw-bold">Deliverables Submitted & Approved!</h6>
                            <p class="mb-1 small"><strong>GitHub Repository:</strong> <a href="<?= htmlspecialchars($group['github_link']) ?>" target="_blank" class="text-decoration-underline"><?= htmlspecialchars($group['github_link']) ?></a></p>
                            <p class="mb-0 small"><strong>Documentation PDF:</strong> <a href="<?= htmlspecialchars($group['doc_link']) ?>" target="_blank" class="text-decoration-underline"><?= htmlspecialchars($group['doc_link']) ?></a></p>
                        </div>
                        <a href="certificate.php" target="_blank" class="btn btn-outline-success w-100 py-2 fw-bold">
                            Download Certificate of Completion (PDF)
                        </a>
                    <?php else: ?>
                        <form id="finalGateForm" class="mt-2">
                            <div class="mb-3">
                                <label class="form-label text-muted small">GitHub Repository URL</label>
                                <input type="url" id="github_link" class="form-control" placeholder="https://github.com/username/repository" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted small">Documentation PDF / Cloud Link</label>
                                <input type="url" id="doc_link" class="form-control" placeholder="https://drive.google.com/file/..." required>
                            </div>
                            <button type="button" class="btn btn-success" onclick="submitFinalGate()">Complete Project Submission</button>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-muted small mb-0 mt-2">Complete Phases 1 to 4 to unlock final deliverables submission.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- SYSTEM STATUS MODAL -->
    <div class="modal fade" id="systemStatusModal" tabindex="-1" aria-labelledby="systemStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-start" style="background-color: #111528; border: 1px solid #1e2540; color: #e2e8f0;">
                <div class="modal-header" style="border-bottom: 1px solid #1e2540;">
                    <h5 class="modal-title fw-bold" id="systemStatusModalLabel">System Status</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex align-items-center mb-3 p-3 rounded" style="background-color: #1a2035; border: 1px solid #28304d;">
                        <span class="badge bg-success me-3 p-2">● Operational</span>
                        <div>
                            <strong class="d-block text-white">All Services Operational</strong>
                            <span class="text-muted small">Database, Auth, and Roster Services running normally.</span>
                        </div>
                    </div>
                    <ul class="list-group list-group-flush bg-transparent small">
                        <li class="list-group-item bg-transparent text-muted d-flex justify-content-between px-0 border-secondary border-opacity-25">
                            <span>Database Connectivity</span>
                            <span class="text-success fw-bold">Connected & 100% up</span>
                        </li>
                        <li class="list-group-item bg-transparent text-muted d-flex justify-content-between px-0 border-0">
                            <span>Project Milestone Engine</span>
                            <span class="text-success fw-bold">Active</span>
                        </li>
                    </ul>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #1e2540;">
                    <button type="button" class="btn btn-outline-light btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- SUPPORT & DOCS MODAL -->
    <div class="modal fade" id="supportDocsModal" tabindex="-1" aria-labelledby="supportDocsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-start" style="background-color: #111528; border: 1px solid #1e2540; color: #e2e8f0;">
                <div class="modal-header" style="border-bottom: 1px solid #1e2540;">
                    <h5 class="modal-title fw-bold" id="supportDocsModalLabel">Support & Documentation</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Have questions regarding project allocations or submission guidelines?</p>

                    <div class="mb-3 p-3 rounded" style="background-color: #1a2035; border: 1px solid #28304d;">
                        <h6 class="fw-bold text-white mb-1">Student Documentation</h6>
                        <p class="text-muted small mb-0">Ensure all group deliverables and links are submitted prior to phase deadlines.</p>
                    </div>

                    <div class="p-3 rounded" style="background-color: #1a2035; border: 1px solid #28304d;">
                        <span class="text-muted small d-block">Technical Administrator Contact:</span>
                        <strong class="text-white">admin@nexus.edu</strong>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #1e2540;">
                    <button type="button" class="btn btn-outline-light btn-sm" data-bs-dismiss="modal">Close</button>
                    <a href="mailto:admin@nexus.edu?subject=Student%20Dashboard%20Support" class="btn btn-primary btn-sm">Contact Support</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showAlert(msg, type = 'success') {
            document.getElementById('alertBox').innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${msg}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>`;
            window.scrollTo(0, 0);
        }

        function validateChars(phaseNum) {
            var txt = document.getElementById('justification_' + phaseNum).value;
            var len = txt.length;
            var counter = document.getElementById('counter_' + phaseNum);
            var btn = document.getElementById('btn_' + phaseNum);

            counter.innerText = len + ' / 150-200 characters';

            if (len >= 150 && len <= 200) {
                counter.className = 'char-counter char-valid';
                btn.disabled = false;
            } else {
                counter.className = 'char-counter char-invalid';
                btn.disabled = true;
            }
        }

        function submitTitlePitch() {
            var title = document.getElementById('project_title').value;
            var abstract = document.getElementById('project_abstract').value;

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'backend/submit_phase_justification.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var res = JSON.parse(xhr.responseText);
                    showAlert(res.message, res.success ? 'success' : 'danger');
                    if (res.success) setTimeout(function() {
                        location.reload();
                    }, 1200);
                }
            };
            xhr.send('action=title_pitch&project_title=' + encodeURIComponent(title) + '&project_abstract=' + encodeURIComponent(abstract));
        }

        function submitSprint(phaseNum) {
            var txt = document.getElementById('justification_' + phaseNum).value;

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'backend/submit_phase_justification.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var res = JSON.parse(xhr.responseText);
                    showAlert(res.message, res.success ? 'success' : 'danger');
                    if (res.success) setTimeout(function() {
                        location.reload();
                    }, 1200);
                }
            };
            xhr.send('action=sprint_submission&phase_number=' + phaseNum + '&justification_text=' + encodeURIComponent(txt));
        }

        function submitFinalGate() {
            var github = document.getElementById('github_link').value;
            var doc = document.getElementById('doc_link').value;

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'backend/submit_phase_justification.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var res = JSON.parse(xhr.responseText);
                    showAlert(res.message, res.success ? 'success' : 'danger');
                    if (res.success) setTimeout(function() {
                        location.reload();
                    }, 1200);
                }
            };
            xhr.send('action=final_gate&github_link=' + encodeURIComponent(github) + '&doc_link=' + encodeURIComponent(doc));
        }
    </script>
    <!-- NEXUS DASHBOARD FOOTER -->
    <!-- NEXUS DASHBOARD FOOTER -->
    <footer class="nexus-footer">
        <div class="container-fluid px-4">
            <div class="d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
                <div class="d-flex align-items-center mb-2 mb-md-0">
                    <span class="brand-badge me-1">NEXUS</span>
                    <span class="footer-text ms-1">&copy; <?= date('Y') ?> Academic Management System. All rights reserved.</span>
                </div>
                <div class="footer-links">
                    <a href="#" class="footer-link" data-bs-toggle="modal" data-bs-target="#systemStatusModal">System Status</a>
                    <span class="footer-divider">•</span>
                    <a href="#" class="footer-link" data-bs-toggle="modal" data-bs-target="#supportDocsModal">Support & Docs</a>
                    <span class="footer-divider">•</span>
                    <a href="privacy.html" target="_blank" class="footer-link">Privacy Policy</a>
                </div>
            </div>
        </div>
    </footer>
</body>

</html>