<?php
require_once __DIR__ . '/config/database.php';

$result = mysqli_query($connection, "SHOW TABLES");
if ($result) {
    echo "Tables in " . $DB_NAME . ":\n";
    while ($row = mysqli_fetch_row($result)) {
        echo "- " . $row[0] . "\n";
    }
} else {
    echo "Error listing tables: " . mysqli_error($connection) . "\n";
}
?>