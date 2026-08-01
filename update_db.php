<?php
require_once 'config/db.php';
try {
    $pdo->exec("ALTER TABLE reviews ADD COLUMN images TEXT DEFAULT NULL AFTER comment;");
    echo "Successfully altered table.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>
