<?php
require_once 'config/database.php';

try {
    $sql = "ALTER TABLE users ADD COLUMN photo VARCHAR(255) DEFAULT NULL AFTER role";
    if (mysqli_query($connection, $sql)) {
        echo "Successfully added 'photo' column to 'users' table.\n";
    } else {
        // Check if error is "Duplicate column name"
        if (mysqli_errno($connection) == 1060) {
            echo "Column 'photo' already exists. Skipping.\n";
        } else {
            echo "Error adding column: " . mysqli_error($connection) . "\n";
        }
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
?>