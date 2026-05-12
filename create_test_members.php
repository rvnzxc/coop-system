<?php

// Direct MySQL connection
$host = '127.0.0.1';
$dbname = 'coopos';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    
    // Check if members table exists and has data
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM members");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $memberCount = $result['count'];
    
    echo "Current member count: $memberCount\n";
    
    if ($memberCount == 0) {
        // Create test members
        $members = [
            ['John', 'Doe', 'john@example.com', '123-456-7890'],
            ['Jane', 'Smith', 'jane@example.com', '098-765-4321'],
            ['Bob', 'Johnson', 'bob@example.com', '555-123-4567']
        ];
        
        foreach ($members as $member) {
            $stmt = $pdo->prepare("INSERT INTO members (first_name, last_name, email, phone, member_number, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
            $memberNumber = 'MEM' . str_pad($pdo->lastInsertId() + 1, 5, '0', STR_PAD_LEFT);
            $stmt->execute([$member[0], $member[1], $member[2], $member[3], $memberNumber]);
            echo "Created member: {$member[0]} {$member[1]} (ID: $memberNumber)\n";
        }
        
        echo "\nTest members created successfully!\n";
    } else {
        echo "Members already exist in database.\n";
    }
    
    // Show existing members
    $stmt = $pdo->query("SELECT id, member_number, first_name, last_name FROM members LIMIT 5");
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nSample members for testing:\n";
    foreach ($members as $member) {
        echo "- {$member['first_name']} {$member['last_name']} (ID: {$member['member_number']})\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
