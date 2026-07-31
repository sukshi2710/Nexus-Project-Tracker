<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Nexus - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css?v=3">
</head>

<body>
    <nav class="navbar navbar-expand-lg mb-4">
        <div class="container-fluid">
            <a class="navbar-brand text-white fw-bold" href="#">
                <span class="text-indigo style-brand">NEXUS</span> ADMIN
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nexusNavbar" aria-controls="nexusNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end mt-2 mt-lg-0" id="nexusNavbar">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center">
                    <span class="me-lg-3 mb-2 mb-lg-0 text-muted small">Welcome, <?= htmlspecialchars($_SESSION['display_name']) ?></span>
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

        <!-- DYNAMIC CONFIRMATION MODAL -->
        <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-header-title fw-bold" id="confirmModalLabel">Confirm Action</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="confirmModalBody">
                        Are you sure you want to proceed?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger btn-sm" id="confirmModalBtn">Confirm</button>
                    </div>
                </div>
            </div>
        </div>

        <ul class="nav nav-pills mb-4" id="adminTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="ingest-tab" data-bs-toggle="pill" data-bs-target="#ingest" type="button">Bulk Roster Ingest</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="solo-tab" data-bs-toggle="pill" data-bs-target="#solo" type="button">Solo Deployment</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="students-tab" data-bs-toggle="pill" data-bs-target="#students" type="button" onclick="loadStudents()">Students</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="faculty-tab" data-bs-toggle="pill" data-bs-target="#faculty" type="button" onclick="loadFaculty()">Faculty</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="master-tab" data-bs-toggle="pill" data-bs-target="#master" type="button" onclick="loadMasterGrid()">Projects Master Grid</button>
            </li>
        </ul>


        <!-- EDIT USER MODAL -->
        <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-header-title fw-bold">Edit User Details</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editUserForm">
                            <input type="hidden" id="edit_user_id">
                            <input type="hidden" id="edit_user_type"> <!-- 'students' or 'faculty' -->
                            <div class="mb-3">
                                <label class="form-label text-muted small">Register / Staff ID</label>
                                <input type="text" id="edit_reg" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted small">Full Name</label>
                                <input type="text" id="edit_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted small">Email Address</label>
                                <input type="email" id="edit_email" class="form-control" required>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary btn-sm" onclick="submitUserEdit()">Save Changes</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-content" id="adminTabsContent">
            <!-- BULK INGEST TAB -->
            <div class="tab-pane fade show active" id="ingest">
                <div class="card p-4">
                    <h5 class="card-title mb-3 fw-bold">Bulk Roster Ingestion & Round-Robin Allocation</h5>
                    <p class="text-muted small">Enter student data line by line: <code>RegisterNumber, Name, Email</code></p>
                    <form id="bulkForm">
                        <div class="mb-3">
                            <label class="form-label text-muted small">Students Per Group</label>
                            <input type="number" id="students_per_group" class="form-control" value="4" min="1" max="10" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small">Raw Student Roster</label>
                            <textarea id="raw_data" class="form-control" rows="8" placeholder="MC001, John Doe, john@nexus.edu&#10;MC002, Jane Smith, jane@nexus.edu" required></textarea>
                        </div>
                        <button type="button" class="btn btn-primary" onclick="submitBulkIngest()">Process Roster & Allocate</button>
                    </form>
                </div>
            </div>

            <!-- SOLO DEPLOYMENT TAB -->
            <div class="tab-pane fade" id="solo">
                <div class="card p-4">
                    <h5 class="card-title mb-3 fw-bold">Solo Student Deployment</h5>
                    <p class="text-muted small">Directly provision a 1-person team with auto-assigned faculty advisor.</p>
                    <form id="soloForm">
                        <div class="mb-3">
                            <label class="form-label text-muted small">Register Number</label>
                            <input type="text" id="solo_reg" class="form-control" placeholder="MC000" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small">Full Name</label>
                            <input type="text" id="solo_name" class="form-control" placeholder="Alan Smith" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small">Email Address</label>
                            <input type="email" id="solo_email" class="form-control" placeholder="alan.smith@nexus.edu" required>
                        </div>
                        <button type="button" class="btn btn-primary" onclick="submitSoloStudent()">Deploy Solo Project</button>
                    </form>
                </div>
            </div>

            <!-- STUDENTS TAB -->
            <div class="tab-pane fade" id="students">
                <div class="card p-4">
                    <h5 class="card-title mb-3 fw-bold">Registered Students</h5>
                    <div id="studentsTableContainer" class="table-responsive">Loading...</div>
                </div>
            </div>

            <!-- FACULTY TAB -->
            <div class="tab-pane fade" id="faculty">
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card p-4">
                            <h5 class="card-title mb-3 fw-bold">Add New Faculty Advisor</h5>
                            <form id="addFacultyForm">
                                <div class="mb-3">
                                    <label class="form-label text-muted small">Faculty Register / Staff ID</label>
                                    <input type="text" id="fac_reg" class="form-control" placeholder="FAC000" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted small">Full Name</label>
                                    <input type="text" id="fac_name" class="form-control" placeholder="Dr. Alan Smith" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted small">Email Address</label>
                                    <input type="email" id="fac_email" class="form-control" placeholder="alan.smith@nexus.edu" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted small">Default Password</label>
                                    <input type="password" id="fac_password" class="form-control" value="password123" required>
                                </div>
                                <button type="button" class="btn btn-primary w-100" onclick="submitAddFaculty()">Register Faculty</button>
                            </form>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card p-4">
                            <h5 class="card-title mb-3 fw-bold">Registered Faculty Advisors</h5>
                            <div id="facultyTableContainer" class="table-responsive">Loading...</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MASTER GRID TAB -->
            <div class="tab-pane fade" id="master">
                <div class="card p-4">
                    <h5 class="card-title mb-3 fw-bold">Projects Master Grid</h5>
                    <div id="masterGridContainer" class="table-responsive">Loading...</div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Interactive Toast Notifier
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

        // Dynamic Confirmation Modal Logic
        var pendingStudentAction = null;
        var confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));

        function manageStudent(studentId, actionType) {
            var bodyText = actionType === 'delete' ?
                "Are you sure you want to permanently delete this student account?" :
                "Unassign this student from their project group?";

            document.getElementById('confirmModalBody').innerText = bodyText;

            pendingStudentAction = function() {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'backend/manage_student.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        var res = JSON.parse(xhr.responseText);
                        showToast(res.message, res.success);
                        if (res.success) {
                            loadStudents();
                        }
                    }
                };
                xhr.send('student_id=' + studentId + '&action=' + actionType);
            };

            confirmModal.show();
        }

        document.getElementById('confirmModalBtn').addEventListener('click', function() {
            if (pendingStudentAction) {
                pendingStudentAction();
                pendingStudentAction = null;
            }
            confirmModal.hide();
        });

        function submitBulkIngest() {
            var rawData = document.getElementById('raw_data').value;
            var perGroup = document.getElementById('students_per_group').value;

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'backend/bulk_ingest.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var res = JSON.parse(xhr.responseText);
                    showToast(res.message, res.success);
                    if (res.success) document.getElementById('bulkForm').reset();
                }
            };
            xhr.send('raw_data=' + encodeURIComponent(rawData) + '&students_per_group=' + encodeURIComponent(perGroup));
        }

        function submitSoloStudent() {
            var reg = document.getElementById('solo_reg').value;
            var name = document.getElementById('solo_name').value;
            var email = document.getElementById('solo_email').value;

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'backend/add_solo_student.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var res = JSON.parse(xhr.responseText);
                    showToast(res.message, res.success);
                    if (res.success) document.getElementById('soloForm').reset();
                }
            };
            xhr.send('solo_reg=' + encodeURIComponent(reg) + '&solo_name=' + encodeURIComponent(name) + '&solo_email=' + encodeURIComponent(email));
        }

        function submitAddFaculty() {
            var reg = document.getElementById('fac_reg').value;
            var name = document.getElementById('fac_name').value;
            var email = document.getElementById('fac_email').value;
            var password = document.getElementById('fac_password').value;

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'backend/add_faculty.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var res = JSON.parse(xhr.responseText);
                    showToast(res.message, res.success);
                    if (res.success) {
                        document.getElementById('addFacultyForm').reset();
                        document.getElementById('fac_password').value = 'password123';
                        loadFaculty();
                    }
                }
            };
            xhr.send('fac_reg=' + encodeURIComponent(reg) + '&fac_name=' + encodeURIComponent(name) + '&fac_email=' + encodeURIComponent(email) + '&fac_password=' + encodeURIComponent(password));
        }

        function loadStudents() {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'backend/get_archive_grid.php?type=students', true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    document.getElementById('studentsTableContainer').innerHTML = xhr.responseText;
                }
            };
            xhr.send();
        }

        function loadFaculty() {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'backend/get_archive_grid.php?type=faculty', true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    document.getElementById('facultyTableContainer').innerHTML = xhr.responseText;
                }
            };
            xhr.send();
        }

        function loadMasterGrid() {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'backend/get_archive_grid.php?type=master', true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    document.getElementById('masterGridContainer').innerHTML = xhr.responseText;
                }
            };
            xhr.send();
        }
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
                    <a href="#" class="footer-link">System Status</a>
                    <span class="footer-divider">•</span>
                    <a href="#" class="footer-link">Support & Docs</a>
                    <span class="footer-divider">•</span>
                    <a href="#" class="footer-link">Privacy Policy</a>
                </div>
            </div>
        </div>
    </footer>
</body>

</html>