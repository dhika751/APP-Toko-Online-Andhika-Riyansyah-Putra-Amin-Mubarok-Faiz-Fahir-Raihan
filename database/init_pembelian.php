<?php
require_once __DIR__ . '/../config/database.php';

$sql = file_get_contents(__DIR__ . '/create_pembelian_tables.sql');

if ($connection->multi_query($sql)) {
    do {
        // store first result set
        if ($result = $connection->store_result()) {
            $result->free();
        }
        // print divider
        if ($connection->more_results()) {
            printf("-----------------\n");
        }
    } while ($connection->next_result());
    echo "Tables created successfully.";
} else {
    echo "Error creating tables: " . $connection->error;
}
?>