<?php
require_once 'config/db.php';

$email = 'admin@nexus.edu';
$new_pass = 'password123';
$hash = password_hash($new_pass, PASSWORD_BCRYPT);

try {
    // Check if admin exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // Update password
        $update = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $update->execute([$hash, $email]);
        echo "<h3>Success! Admin password has been forcefully reset.</h3>";
        echo "<p>You can now <a href='index.php'>Go back to Login</a> and use: <strong>admin@nexus.edu</strong> / <strong>password123</strong></p>";
    } else {
        // Insert admin if missing entirely
        $insert = $pdo->prepare("INSERT INTO users (register_number, name, email, role, password) VALUES ('ADM001', 'System Administrator', ?, 'Admin', ?)");
        $insert->execute([$email, $hash]);
        echo "<h3>Success! Admin user was missing and has now been created.</h3>";
        echo "<p><a href='index.php'>Go back to Login</a></p>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>