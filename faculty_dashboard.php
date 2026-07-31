<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Faculty') {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Nexus - Faculty Console</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css?v=3">
</head>

<body>
    <nav class="navbar navbar-expand-lg mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"><span class="brand-badge">NEXUS</span> FACULTY</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nexusNavbar" aria-controls="nexusNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end mt-2 mt-lg-0" id="nexusNavbar">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center">
                    <span class="me-lg-3 mb-2 mb-lg-0 text-muted small">Advisor: <?= htmlspecialchars($_SESSION['display_name']) ?></span>
                    <a href="logout.php" class="btn btn-outline-light btn-sm w-100 w-lg-auto">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mb-5">
        <!-- TOAST CONTAINER -->
        <div class="toast-container position-fixed bottom-0 end-0 p-3">
            <div id="nexusToast" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header">
                    <strong class="me-auto" id="toastTitle">System Notification</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body" id="toastBody">Action completed.</div>
            </div>
        </div>

        <!-- REJECTION FEEDBACK MODAL -->
        <div class="modal fade" id="rejectionModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-header-title fw-bold text-danger">Provide Rejection Feedback</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small">Let the student group know what needs to be revised before resubmitting.</p>
                        <div class="mb-3">
                            <label class="form-label text-muted small">Revision Feedback / Comments</label>
                            <textarea id="rejectionFeedbackText" class="form-control" rows="3" placeholder="e.g., SRS document lacks an ER diagram. Please revise." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger btn-sm" id="confirmRejectBtn">Submit Rejection</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 1. PENDING EVALUATION FEED -->
        <div class="card p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0 text-primary">Pending Evaluation Feed</h5>
                <span class="badge badge-pulse bg-warning text-dark">Live Polling (10s)</span>
            </div>
            <div id="feedContainer">
                <div class="text-center py-3 text-muted">Loading pending evaluation items...</div>
            </div>
        </div>

        <!-- 2. ONGOING PROJECTS LIST -->
        <div class="card p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">My Ongoing Projects</h5>
                <button class="btn btn-sm btn-outline-secondary" onclick="fetchOngoingProjects()">Refresh List</button>
            </div>
            <div id="ongoingProjectsContainer" class="table-responsive">
                <div class="text-center py-3 text-muted">Loading ongoing projects...</div>
            </div>
        </div>

        <!-- 3. ARCHIVED / COMPLETED PROJECTS GRID -->
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0 text-success">Archived & Completed Projects</h5>
                <button class="btn btn-sm btn-outline-secondary" onclick="fetchArchivedProjects()">Refresh Archive</button>
            </div>
            <div id="archivedProjectsContainer" class="table-responsive">
                <div class="text-center py-3 text-muted">Loading archive grid...</div>
            </div>
        </div>
    </div>

    <!-- FACULTY SYSTEM STATUS MODAL -->
    <div class="modal fade" id="facSystemStatusModal" tabindex="-1" aria-labelledby="facSystemStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-start" style="background-color: #111528; border: 1px solid #1e2540; color: #e2e8f0;">
                <div class="modal-header" style="border-bottom: 1px solid #1e2540;">
                    <h5 class="modal-title fw-bold" id="facSystemStatusModalLabel">System Status</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex align-items-center mb-3 p-3 rounded" style="background-color: #1a2035; border: 1px solid #28304d;">
                        <span class="badge bg-success me-3 p-2">● Operational</span>
                        <div>
                            <strong class="d-block text-white">All Faculty Portal Systems Active</strong>
                            <span class="text-muted small">Evaluations, Roster Management, and Grade Sync running normally.</span>
                        </div>
                    </div>
                    <ul class="list-group list-group-flush bg-transparent small">
                        <li class="list-group-item bg-transparent text-muted d-flex justify-content-between px-0 border-secondary border-opacity-25">
                            <span>Database Connectivity</span>
                            <span class="text-success fw-bold">99.9% Up</span>
                        </li>
                        <li class="list-group-item bg-transparent text-muted d-flex justify-content-between px-0 border-0">
                            <span>Advisor Review Engine</span>
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

    <!-- FACULTY SUPPORT & DOCS MODAL -->
    <div class="modal fade" id="facSupportDocsModal" tabindex="-1" aria-labelledby="facSupportDocsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-start" style="background-color: #111528; border: 1px solid #1e2540; color: #e2e8f0;">
                <div class="modal-header" style="border-bottom: 1px solid #1e2540;">
                    <h5 class="modal-title fw-bold" id="facSupportDocsModalLabel">Faculty Support & Documentation</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Faculty portal guidance and project evaluation workflows.</p>

                    <div class="mb-3 p-3 rounded" style="background-color: #1a2035; border: 1px solid #28304d;">
                        <h6 class="fw-bold text-white mb-1">Advisor Evaluation Guidelines</h6>
                        <p class="text-muted small mb-0">Use the master grid to update student phase milestones, approve project titles, and verify repository deliverables.</p>
                    </div>

                    <div class="p-3 rounded" style="background-color: #1a2035; border: 1px solid #28304d;">
                        <span class="text-muted small d-block">System Administrator Contact:</span>
                        <strong class="text-white">admin@nexus.edu</strong>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #1e2540;">
                    <button type="button" class="btn btn-outline-light btn-sm" data-bs-dismiss="modal">Close</button>
                    <a href="mailto:admin@nexus.edu?subject=Faculty%20Dashboard%20Support" class="btn btn-primary btn-sm">Contact Admin</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showToast(msg, isSuccess = true) {
            var toastEl = document.getElementById('nexusToast');
            var toastBody = document.getElementById('toastBody');
            var toastTitle = document.getElementById('toastTitle');

            toastTitle.innerText = isSuccess ? 'Success' : 'Attention';
            toastTitle.className = isSuccess ? 'me-auto text-success fw-bold' : 'me-auto text-danger fw-bold';
            toastBody.innerText = msg;

            var bsToast = new bootstrap.Toast(toastEl, {
                delay: 4000
            });
            bsToast.show();
        }

        function fetchFeed() {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'backend/get_faculty_feed.php', true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    document.getElementById('feedContainer').innerHTML = xhr.responseText;
                }
            };
            xhr.send();
        }

        function fetchOngoingProjects() {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'backend/get_faculty_projects.php?type=ongoing', true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    document.getElementById('ongoingProjectsContainer').innerHTML = xhr.responseText;
                }
            };
            xhr.send();
        }

        function fetchArchivedProjects() {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'backend/get_faculty_projects.php?type=archived', true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    document.getElementById('archivedProjectsContainer').innerHTML = xhr.responseText;
                }
            };
            xhr.send();
        }

        var rejectionModal = new bootstrap.Modal(document.getElementById('rejectionModal'));
        var pendingRejectParams = null;

        function processAction(groupId, phaseNum, actionType) {
            if (actionType === 'Reject') {
                pendingRejectParams = {
                    groupId: groupId,
                    phaseNum: phaseNum
                };
                document.getElementById('rejectionFeedbackText').value = '';
                rejectionModal.show();
                return;
            }

            executeFacultyAction(groupId, phaseNum, 'Approve', '');
        }

        document.getElementById('confirmRejectBtn').addEventListener('click', function() {
            var feedback = document.getElementById('rejectionFeedbackText').value.trim();
            if (!feedback) {
                showToast("Please enter a rejection reason for the student team.", false);
                return;
            }

            if (pendingRejectParams) {
                executeFacultyAction(pendingRejectParams.groupId, pendingRejectParams.phaseNum, 'Reject', feedback);
                pendingRejectParams = null;
            }
            rejectionModal.hide();
        });

        function executeFacultyAction(groupId, phaseNum, actionType, feedback) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'backend/process_faculty_action.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var res = JSON.parse(xhr.responseText);
                    showToast(res.message, res.success);
                    fetchFeed();
                    fetchOngoingProjects();
                    fetchArchivedProjects();
                }
            };
            xhr.send('group_id=' + groupId + '&phase_number=' + phaseNum + '&action=' + actionType + '&rejection_feedback=' + encodeURIComponent(feedback));
        }

        fetchFeed();
        fetchOngoingProjects();
        fetchArchivedProjects();
        setInterval(fetchFeed, 10000);
    </script>
    <!-- NEXUS DASHBOARD FOOTER -->
    <footer class="nexus-footer">
        <div class="container-fluid px-4">
            <div class="d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
                <div class="d-flex align-items-center mb-2 mb-md-0">
                    <span class="brand-badge me-1">NEXUS</span>
                    <span class="footer-text ms-1">&copy; <?= date('Y') ?> Academic Management System. All rights reserved.</span>
                </div>
                <div class="footer-links">
                    <a href="#" class="footer-link" data-bs-toggle="modal" data-bs-target="#facSystemStatusModal">System Status</a>
                    <span class="footer-divider">•</span>
                    <a href="#" class="footer-link" data-bs-toggle="modal" data-bs-target="#facSupportDocsModal">Support & Docs</a>
                    <span class="footer-divider">•</span>
                    <a href="privacy.html" target="_blank" class="footer-link">Privacy Policy</a>
                </div>
            </div>
        </div>
    </footer>
</body>

</html>