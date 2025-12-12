<?php
$mysqli = new mysqli('localhost', 'root', '', 'crm_database');

if ($mysqli->connect_error) {
    die('Connect Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}

echo "Currencies table content:\n";
$result = $mysqli->query("SELECT id, name, isdefault FROM tblcurrencies");
while ($row = $result->fetch_assoc()) {
    print_r($row);
}

echo "\nChecking get_base_currency logic:\n";
$result = $mysqli->query("SELECT * FROM tblcurrencies WHERE isdefault = 1");
$base_currency = $result->fetch_assoc();
if ($base_currency) {
    echo "Base currency found: " . $base_currency['name'] . "\n";
} else {
    echo "NO BASE CURRENCY FOUND!\n";
}
