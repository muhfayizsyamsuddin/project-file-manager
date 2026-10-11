<?php

$connection = mysqli_connect(
    "localhost",
    "YOUR_DB_USERNAME",
    "YOUR_DB_PASSWORD",
    "YOUR_DB_NAME"
);

if (!$connection) {
    error_log("Database connection failed.");
    die("Database connection failed.");
}

mysqli_set_charset($connection, "utf8mb4");