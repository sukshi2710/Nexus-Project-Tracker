<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Student') {
    die("Unauthorized access.");
}

$groupId = $_SESSION['group_id'];

if (!$groupId) {
    die("No project group assigned.");
}

// Fetch group, student, and faculty details
$stmt = $pdo->prepare("
    SELECT g.*, u.name as advisor_name, s.name as student_name, s.register_number
    FROM project_groups g 
    LEFT JOIN users u ON g.faculty_id = u.id
    LEFT JOIN users s ON s.id = ?
    WHERE g.id = ?
");
$stmt->execute([$_SESSION['user_id'], $groupId]);
$data = $stmt->fetch();

// Gatekeeper: Only allow if Phase 5 (Completed) and title approved
if (!$data || $data['current_phase'] < 5) {
    die("Certificate is not available until all project phases are completed.");
}

$certId = "NEXUS-CERT-" . strtoupper(substr(md5($data['id'] . $data['register_number']), 0, 8));
$issueDate = date('F d, Y');

// Build detailed text payload directly into the QR Code
$qrPayload = "--- NEXUS CERTIFICATE VERIFICATION ---\n" .
    "Cert ID: {$certId}\n" .
    "Student Name: {$data['student_name']}\n" .
    "Reg Number: {$data['register_number']}\n" .
    "Project Title: {$data['project_title']}\n" .
    "Group Ref: {$data['group_name']}\n" .
    "Faculty Advisor: {$data['advisor_name']}\n" .
    "Issue Date: {$issueDate}\n" .
    "Status: VERIFIED & COMPLETED";

// Clean API URL for QR Code payload with higher resolution for clear text reading
$qrApiUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($qrPayload);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Certificate of Completion - <?= htmlspecialchars($data['student_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css?v=3">
    <style>
        body {
            background-color: #0b0f19;
            color: #ffffff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 30px 20px;
        }

        /* LANDSCAPE CONTAINER SETUP (SCREEN DISPLAY) */
        .cert-container {
            width: 1050px;
            max-width: 98vw;
            background: #111827;
            border: 10px solid #1f2937;
            outline: 2px solid #6366f1;
            padding: 45px 60px;
            text-align: center;
            position: relative;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.8);
            border-radius: 12px;
            box-sizing: border-box;
        }

        .cert-title {
            font-size: 3rem;
            font-weight: 800;
            letter-spacing: 3px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 5px;
        }

        .cert-subtitle {
            font-size: 1.15rem;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 4px;
            margin-bottom: 25px;
        }

        .student-name {
            font-size: 2.6rem;
            font-weight: 700;
            color: #f9fafb;
            border-bottom: 2px solid #6366f1;
            display: inline-block;
            padding-bottom: 4px;
            margin: 10px 0;
        }

        .cert-body {
            font-size: 1.15rem;
            color: #d1d5db;
            line-height: 1.7;
            margin: 15px auto;
            max-width: 850px;
        }

        .project-title-box {
            font-size: 1.35rem;
            font-weight: 600;
            color: #a5b4fc;
            background: rgba(99, 102, 241, 0.1);
            border: 1px dashed #6366f1;
            padding: 10px 24px;
            border-radius: 8px;
            display: inline-block;
            margin: 10px 0;
        }

        .cert-footer {
            margin-top: 35px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding-top: 20px;
            border-top: 1px solid #1f2937;
        }

        .qr-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .qr-code-img {
            width: 85px;
            height: 85px;
            background: #ffffff;
            padding: 5px;
            border-radius: 6px;
            border: 1px solid #6366f1;
        }

        .sig-box {
            text-align: center;
        }

        .sig-line {
            width: 220px;
            border-bottom: 1px solid #6366f1;
            margin-bottom: 8px;
        }

        .advisor-name-text {
            color: #f9fafb;
        }

        /* STRICT LANDSCAPE PRINT OVERRIDES */
        @page {
            size: A4 landscape;
            margin: 0;
        }

        @media print {
            html, body {
                width: 100vw !important;
                height: 100vh !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
                color: #000000 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                overflow: hidden !important;
            }

            .no-print,
            .navbar,
            .btn,
            footer {
                display: none !important;
            }

            .cert-container {
                width: 100vw !important;
                height: 100vh !important;
                max-width: 100vw !important;
                max-height: 100vh !important;
                border: 12px solid #1f2937 !important;
                outline: none !important;
                background: #ffffff !important;
                color: #000000 !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                padding: 40px 60px !important;
                box-sizing: border-box !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: center !important;
            }

            .cert-title {
                color: #4f46e5 !important;
                background: none !important;
                -webkit-background-clip: unset !important;
                background-clip: unset !important;
                -webkit-text-fill-color: #4f46e5 !important;
            }

            .student-name {
                color: #000000 !important;
                border-bottom-color: #4f46e5 !important;
            }

            .project-title-box {
                color: #4f46e5 !important;
                background: #f3f4f6 !important;
                border: 1px dashed #4f46e5 !important;
            }

            .cert-subtitle,
            .cert-body {
                color: #1f2937 !important;
            }

            .cert-footer {
                border-top-color: #e5e7eb !important;
            }

            .qr-code-img {
                border-color: #1f2937 !important;
            }

            .advisor-name-text {
                color: #111827 !important;
            }

            code {
                background-color: #f3f4f6 !important;
                color: #374151 !important;
                border: 1px solid #d1d5db !important;
            }
        }
    </style>
</head>

<body>
    <div class="no-print mb-4">
        <button onclick="window.print()" class="btn btn-primary px-4 py-2 me-2">Print / Save as PDF</button>
        <a href="student_dashboard.php" class="btn btn-outline-light px-4 py-2">Back to Workspace</a>
    </div>

    <div class="cert-container">
        <div class="cert-title">NEXUS ACADEMY</div>
        <div class="cert-subtitle">Certificate of Project Completion</div>

        <p class="cert-body mb-1">This is to certify that</p>
        <div class="student-name"><?= htmlspecialchars($data['student_name']) ?></div>
        <p class="text-muted small">Register Number: <code><?= htmlspecialchars($data['register_number']) ?></code></p>

        <p class="cert-body">
            has successfully completed all milestone development phases and submitted final deliverables for the mini-project titled:
        </p>

        <div class="project-title-box">
            "<?= htmlspecialchars($data['project_title']) ?>"
        </div>

        <p class="cert-body mt-2">
            Group Reference: <strong><?= htmlspecialchars($data['group_name']) ?></strong>
        </p>

        <div class="cert-footer">
            <div class="qr-section text-start">
                <img src="<?= $qrApiUrl ?>" alt="Verification QR Code" class="qr-code-img">
                <div>
                    <small class="text-muted d-block fw-bold" style="letter-spacing: 0.5px;">SCAN TO VERIFY</small>
                    <small class="text-muted d-block">Issue Date: <?= $issueDate ?></small>
                    <small class="text-muted d-block">Verification ID: <code><?= $certId ?></code></small>
                </div>
            </div>

            <div class="sig-box">
                <div class="sig-line"></div>
                <strong class="d-block small advisor-name-text"><?= htmlspecialchars($data['advisor_name']) ?></strong>
                <small class="text-muted">Faculty Project Advisor</small>
            </div>
        </div>
    </div>
</body>

</html>