# 🚀 NEXUS: Automated Academic Project Management Software

A web-based portal designed for academic institutions to manage team allocations, streamline project workflows, track Software Development Life Cycle (SDLC) sprint milestones, and facilitate faculty-led pitch evaluations in real-time.

---

## 📌 Key Features

### ⚙️ Faculty & Admin Panel
* **Bulk Roster Ingestion:** Parse raw text input line-by-line (`RegisterNumber, Name, Email`) and run automated round-robin team allocation.
* **Solo Project Deployment:** Assign individual students to standalone tracks when custom project boundaries are required.
* **Active SDLC Evaluation Feed:** Real-time feed (`get_faculty_feed.php`) allowing faculty to review submitted project pitches, approve/reject abstract proposals, and evaluate sprint milestones.
* **Master Project Lifecycle Archive:** Comprehensive grid view for searching, tracking, and archiving production artifacts across all project teams.

### 🎓 Student Dashboard
* **Abstract & Phase Tracking:** Submit project ideas and monitor progress through phase gates (Phase 0 through final deployment).
* **Real-time Status Updates:** Receive instant evaluation status and feedback from assigned faculty members.
* **Sprint Milestone Submissions:** Upload progress updates, repository links, and build artifacts for faculty review.

---

## 📁 Repository Structure

```text
project_tracker/
├── backend/
│   ├── add_faculty.php
│   ├── add_solo_student.php
│   ├── admin_modify_processor.php
│   ├── auth_process.php
│   ├── bulk_ingest.php
│   ├── get_admin_search.php
│   ├── get_archive_grid.php
│   ├── get_faculty_feed.php       # Dynamic faculty evaluation feed script
│   ├── process_faculty_action.php # AJAX endpoint for phase approvals
│   ├── submit_final_gate.php
│   └── submit_justification.php
├── config/
│   └── db.php                     # Database connection settings
├── css/
│   └── style.css                  # UI layout styling
├── js/
│   ├── data.js
│   └── main.js                    # AJAX routing and UI dynamic updates
├── admin_dashboard.php            # Administrative control dashboard
├── faculty_dashboard.php          # Faculty portal interface
├── student_dashboard.php          # Student portal interface
├── index.php                      # Main login page
└── logout.php                     # Session destroy handler
```
## 🛠️ Tech Stack

- **Frontend:** HTML5, CSS3, JavaScript (ES6+ / AJAX)
- **Backend:** PHP 8.x
- **Database:** MySQL / MariaDB (via MySQLi or PDO)
- **Local Server:** XAMPP / WAMP / LAMP stack

---

## 🚀 Local Installation & Setup Guide

### 1. Prerequisites
- Install **XAMPP** (or an equivalent PHP + MySQL local server environment).
- Install **Git**.

### 2. Clone the Repository
Clone this repository into your local web server root directory (e.g., `C:\xampp\htdocs\` for XAMPP):

```bash
cd C:\xampp\htdocs
git clone https://github.com/YOUR_GITHUB_USERNAME/project_tracker.git
```

### 3. Database Configuration
1. Open phpMyAdmin (`http://localhost/phpmyadmin/`).
2. Create a new database named **`project_tracker`**.
3. Import your SQL schema into the `project_tracker` database.
4. Verify or update connection parameters inside `config/db.php`:

```php
<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "project_tracker";

// Update according to your connection driver (MySQLi/PDO)
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
```

### 4. Run the Application
Start Apache and MySQL services in your XAMPP Control Panel, then open your browser and navigate to:

```text
http://localhost/project_tracker/
```

---

## 🔒 Security Practices Implemented

- **Role-Based Access Control (RBAC):** Restricts endpoints like `get_faculty_feed.php` and `process_faculty_action.php` strictly to authorized session privileges.
- **SQL Injection Prevention:** Utilizes prepared statements across database queries.
- **XSS Defense:** Sanitizes dynamically rendered content using `htmlspecialchars()`.

---

## 🤝 Contributing

1. Fork the repository.
2. Create your Feature Branch (`git checkout -b feature/NewFeature`).
3. Commit your changes (`git commit -m 'Add NewFeature'`).
4. Push to the Branch (`git push origin feature/NewFeature`).
5. Open a Pull Request.

---

## 📜 License

Distributed under the MIT License.
