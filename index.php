<?php
session_start();
require_once 'config/db.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'Admin') header('Location: admin_dashboard.php');
    elseif ($_SESSION['role'] === 'Faculty') header('Location: faculty_dashboard.php');
    elseif ($_SESSION['role'] === 'Student') header('Location: student_dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($identifier) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR register_number = ?");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['display_name'] = $user['name'];
            $_SESSION['group_id'] = $user['group_id'] ?? null;

            if ($user['role'] === 'Admin') header('Location: admin_dashboard.php');
            elseif ($user['role'] === 'Faculty') header('Location: faculty_dashboard.php');
            elseif ($user['role'] === 'Student') header('Location: student_dashboard.php');
            exit;
        } else {
            $error = 'Invalid credentials or account does not exist.';
        }
    } else {
        $error = 'Please fill in all required fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexus - Project Management & Evaluation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #0b0d14;
            color: #94a3b8;
        }

        .bg-grid {
            background-image: radial-gradient(rgba(255, 255, 255, 0.05) 1px, transparent 1px);
            background-size: 24px 24px;
        }

        .accent-purple {
            color: #818cf8;
        }

        .feature-card {
            background: rgba(18, 22, 36, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(12px);
        }

        .login-card {
            background: rgba(15, 19, 32, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.7);
        }

        .input-group-custom {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.2s ease;
        }

        .input-group-custom:focus-within {
            border-color: #6366f1;
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
        }

        .btn-gradient {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            transition: all 0.2s ease;
        }

        .btn-gradient:hover {
            opacity: 0.95;
            transform: translateY(-1px);
        }
    </style>
</head>

<body class="min-h-screen bg-grid flex flex-col justify-between overflow-x-hidden">

    <!-- Top Navigation Bar -->
    <header class="w-full px-8 py-6 flex items-center justify-between z-10">
        <div class="flex items-center space-x-2">
            <span class="text-xl font-bold tracking-wider text-white">NEXUS</span>
            <span class="text-xs px-2 py-0.5 rounded-full bg-slate-800 text-slate-400 font-mono border border-slate-700">v2.0</span>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="w-full max-w-7xl mx-auto px-6 grid grid-cols-1 lg:grid-cols-12 gap-12 items-center my-auto py-8">

        <!-- Left Side: Hero Text & Features -->
        <div class="lg:col-span-7 space-y-8">
            <div class="space-y-4">
                <h1 class="text-4xl lg:text-5xl font-extrabold text-white leading-tight tracking-tight">
                    Centralized <span class="accent-purple">Project Hub</span> & Live Tracking
                </h1>
                <p class="text-slate-400 text-lg leading-relaxed max-w-2xl">
                    Seamlessly coordinate student rosters, automated team allocations, real-time project milestones, and faculty evaluations in one sleek workspace.
                </p>
            </div>

            <!-- Feature Cards List -->
            <div class="space-y-4 max-w-xl">
                <!-- Feature 1 -->
                <div class="feature-card p-4 rounded-xl flex items-start space-x-4">
                    <div class="p-2.5 rounded-lg bg-indigo-950/60 text-indigo-400 border border-indigo-800/40 mt-1">
                        <i data-lucide="cpu" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-white font-semibold text-sm">Automated Allocation</h3>
                        <p class="text-slate-400 text-xs mt-0.5">Round-robin faculty advisor assignment engine.</p>
                    </div>
                </div>

                <!-- Feature 2 -->
                <div class="feature-card p-4 rounded-xl flex items-start space-x-4">
                    <div class="p-2.5 rounded-lg bg-indigo-950/60 text-indigo-400 border border-indigo-800/40 mt-1">
                        <i data-lucide="bar-chart-3" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-white font-semibold text-sm">Real-Time Evaluation</h3>
                        <p class="text-slate-400 text-xs mt-0.5">Master tracking grids for seamless milestone updates.</p>
                    </div>
                </div>

                <!-- Feature 3 -->
                <div class="feature-card p-4 rounded-xl flex items-start space-x-4">
                    <div class="p-2.5 rounded-lg bg-indigo-950/60 text-indigo-400 border border-indigo-800/40 mt-1">
                        <i data-lucide="shield-check" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-white font-semibold text-sm">Role-Based Gatekeeper</h3>
                        <p class="text-slate-400 text-xs mt-0.5">Tailored portals for Admin, Faculty, and Students.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side: Login Card -->
        <div class="lg:col-span-5 flex justify-center lg:justify-end">
            <div class="login-card w-full max-w-md p-8 rounded-2xl relative z-10">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-white">Welcome back</h2>
                    <p class="text-xs text-slate-400 mt-1">Enter your system credentials to access your dashboard.</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="mb-4 p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 text-xs flex items-center space-x-2">
                        <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="index.php" class="space-y-5">
                    <!-- Email / Identifier Input -->
                    <div>
                        <label class="block text-xs text-slate-400 mb-1.5 font-medium">Email or Register Number</label>
                        <div class="input-group-custom flex items-center rounded-lg px-3 py-2">
                            <i data-lucide="user" class="w-4 h-4 text-slate-500 mr-2.5"></i>
                            <input type="text" name="email" class="bg-transparent text-sm text-white placeholder-slate-600 focus:outline-none w-full" placeholder="admin@nexus.edu" required>
                        </div>
                    </div>

                    <!-- Password Input -->
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="password" class="form-label mb-0">Password</label>
                            <a href="#" class="hover:text-slate-400 transition-colors" data-bs-toggle="modal" data-bs-target="#forgotModal">Forgot?</a>
                        </div>
                        <div class="input-group-custom flex items-center rounded-lg px-3 py-2">
                            <i data-lucide="lock" class="w-4 h-4 text-slate-500 mr-2.5"></i>
                            <input type="password" id="passwordInput" name="password" class="bg-transparent text-sm text-white placeholder-slate-600 focus:outline-none w-full" placeholder="••••••••" required>
                            <button type="button" onclick="togglePasswordVisibility()" class="text-slate-500 hover:text-slate-300 focus:outline-none ml-2">
                                <i data-lucide="eye" id="eyeIcon" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn-gradient w-full py-2.5 rounded-lg text-white font-medium text-sm flex items-center justify-center space-x-2 shadow-lg shadow-indigo-500/20">
                        <span>Sign In</span>
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </button>
                </form>

                <!-- Footer Hint inside Card -->
                <div class="mt-6 pt-4 border-t border-slate-800/80 text-center flex items-center justify-center space-x-1.5 text-xs text-slate-500">
                    <i data-lucide="info" class="w-3.5 h-3.5 text-slate-500"></i>
                    <span>Ensure you are using your official department credentials.</span>
                </div>
            </div>
        </div>

    </main>

    <!-- Page Footer -->
    <footer class="w-full px-8 py-6 flex flex-col md:flex-row items-center justify-between text-xs text-slate-500 space-y-4 md:space-y-0">
        <div>
            © <?= date('Y') ?> Nexus Management System. All rights reserved.
        </div>
        <div class="d-flex gap-3 justify-content-end p-3">
            <a href="privacy.html" target="_blank" class="hover:text-slate-400 transition-colors">Privacy </a>
            <a href="terms.html" target="_blank" class="hover:text-slate-400 transition-colors">Terms </a>
            <a href="#" class="hover:text-slate-400 transition-colors" data-bs-toggle="modal" data-bs-target="#supportModal">Support</a>
        </div>
    </footer>

    <!-- FORGOT PASSWORD MODAL -->
    <div class="modal fade" id="forgotModal" tabindex="-1" aria-labelledby="forgotModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-start" style="background-color: #111528; border: 1px solid #1e2540; color: #e2e8f0;">
                <div class="modal-header" style="border-bottom: 1px solid #1e2540;">
                    <h5 class="modal-title fw-bold" id="forgotModalLabel">Reset Password</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-white">Password resets are managed by your department administrator.</p>
                    <p class="mb-0" style="color: #94a3b8; font-size: 0.875rem;">Please contact administrator at <a href="mailto:admin@nexus.edu" style="color: #818cf8; text-decoration: underline;">admin@nexus.edu</a> with your Register/Staff ID to request a password reset.</p>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #1e2540;">
                    <button type="button" class="btn btn-outline-light btn-sm" data-bs-dismiss="modal">Close</button>
                    <a href="mailto:admin@nexus.edu?subject=Password%20Reset%20Request" class="btn btn-primary btn-sm">Contact Admin</a>
                </div>
            </div>
        </div>
    </div>

    <!-- SUPPORT MODAL -->
    <div class="modal fade" id="supportModal" tabindex="-1" aria-labelledby="supportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-start" style="background-color: #111528; border: 1px solid #1e2540; color: #e2e8f0;">
                <div class="modal-header" style="border-bottom: 1px solid #1e2540;">
                    <h5 class="modal-title fw-bold" id="supportModalLabel">System Support</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-white">Need technical assistance or reporting an issue?</p>
                    <div class="p-3 rounded mb-3" style="background-color: #1a2035; border: 1px solid #28304d;">
                        <span class="d-block mb-1" style="color: #94a3b8; font-size: 0.875rem;">Official Admin Email:</span>
                        <strong class="text-white fs-5">admin@nexus.edu</strong>
                    </div>
                    <p class="mb-0" style="color: #94a3b8; font-size: 0.875rem;">Typical response time: Within 24 working hours.</p>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #1e2540;">
                    <button type="button" class="btn btn-outline-light btn-sm" data-bs-dismiss="modal">Close</button>
                    <a href="mailto:admin@nexus.edu?subject=Nexus%20Support%20Inquiry" class="btn btn-primary btn-sm">Email Support</a>
                </div>
            </div>
        </div>
    </div>

    <!-- FORGOT PASSWORD MODAL -->
    <div class="modal fade" id="forgotModal" tabindex="-1" aria-labelledby="forgotModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-start" style="background-color: #111528; border: 1px solid #1e2540; color: #e2e8f0;">
                <div class="modal-header" style="border-bottom: 1px solid #1e2540;">
                    <h5 class="modal-title fw-bold" id="forgotModalLabel">Reset Password</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Password resets are managed by your department administrator.</p>
                    <p class="mb-0 text-muted small">Please contact administrator at <a href="mailto:admin@nexus.edu" style="color: #818cf8;">admin@nexus.edu</a> with your Register/Staff ID to request a password reset.</p>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #1e2540;">
                    <button type="button" class="btn btn-outline-light btn-sm" data-bs-dismiss="modal">Close</button>
                    <a href="mailto:admin@nexus.edu?subject=Password%20Reset%20Request" class="btn btn-primary btn-sm">Contact Admin</a>
                </div>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT DIRECT TRIGGER -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var forgotBtn = document.querySelector('[data-bs-target="#forgotModal"]');
            if (forgotBtn) {
                forgotBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    var forgotModalEl = document.getElementById('forgotModal');
                    var modal = bootstrap.Modal.getInstance(forgotModalEl) || new bootstrap.Modal(forgotModalEl);
                    modal.show();
                });
            }
        });
    </script>

    <script>
        // Initialize Lucide Icons
        lucide.createIcons();

        // Password visibility toggle helper
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('passwordInput');
            const eyeIcon = document.getElementById('eyeIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.setAttribute('data-lucide', 'eye-off');
            } else {
                passwordInput.type = 'password';
                eyeIcon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>