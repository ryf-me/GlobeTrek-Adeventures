<?php
require_once __DIR__ . '/../config/database.php';
$db = getDB();

// Delete existing test users and recreate
$db->exec("DELETE FROM users WHERE email IN ('testuser@testing.com', 'testadmin@testing.com')");

$hash = password_hash('password', PASSWORD_DEFAULT);
echo "Generated hash: " . $hash . "\n";

// Verify the hash
echo "Verify 'password' against hash: " . (password_verify('password', $hash) ? 'TRUE' : 'FALSE') . "\n";

$users = [
    ['Test User', 'testuser@testing.com', $hash, 'user'],
    ['Test Admin', 'testadmin@testing.com', $hash, 'admin'],
];

foreach ($users as [$name, $email, $pw, $role]) {
    $stmt = $db->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $email, $pw, $role]);
    echo "Inserted: $email ($role)\n";
}

// Verify the stored hash
$stmt = $db->prepare("SELECT email, password FROM users WHERE email = ?");
$stmt->execute(['testuser@testing.com']);
$row = $stmt->fetch();
echo "\nStored hash for testuser: " . $row['password'] . "\n";
echo "Verify: " . (password_verify('password', $row['password']) ? 'TRUE' : 'FALSE') . "\n";
