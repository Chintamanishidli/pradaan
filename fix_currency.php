<?php
$mysqli = new mysqli('localhost', 'root', '', 'crm_database');

if ($mysqli->connect_error) {
    die('Connect Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}

// Set INR as default
$mysqli->query("UPDATE tblcurrencies SET isdefault = 1 WHERE name = 'INR'");
echo "Updated INR to be default currency.\n";

// Verify
$result = $mysqli->query("SELECT * FROM tblcurrencies WHERE isdefault = 1");
$base_currency = $result->fetch_assoc();
if ($base_currency) {
    echo "Base currency is now: " . $base_currency['name'] . "\n";
} else {
    echo "Still no base currency!\n";
}
