<?php
require_once 'config/db.php';

$hash = password_hash('password123', PASSWORD_BCRYPT);

try {
    // Clean up existing faculty records and insert fresh ones
    $pdo->exec("DELETE FROM users WHERE role = 'Faculty'");

    $stmt = $pdo->prepare("INSERT INTO users (register_number, name, email, role, password, group_id) VALUES (?, ?, ?, 'Faculty', ?, NULL)");
    
    $stmt->execute(['FAC001', 'Dr. Alan Turing', 'alan@nexus.edu', $hash]);
    $stmt->execute(['FAC002', 'Prof. Grace Hopper', 'grace@nexus.edu', $hash]);

    echo "<h3>Faculty accounts updated successfully!</h3>";
    echo "<p>Log in with: <strong>alan@nexus.edu</strong> or <strong>grace@nexus.edu</strong></p>";
    echo "<p>Password: <strong>password123</strong></p>";
    echo "<p><a href='index.php'>Return to Login</a></p>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>