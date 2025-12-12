<?php
// recalculate_invoice_totals.php

$mysqli = new mysqli('localhost', 'root', '', 'crm_database');

if ($mysqli->connect_error) {
    die('Connect Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}

echo "=== Recalculating Invoice Totals ===\n";

// Get all invoices with 0 total (or all, to be safe? Let's do all with 0 total for now)
$res = $mysqli->query("SELECT id FROM tblinvoices WHERE total = 0");

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $id = $row['id'];
        
        // Calculate total from items
        $sql_items = "SELECT qty, rate FROM tblitemable WHERE rel_id = $id AND rel_type = 'invoice'";
        $res_items = $mysqli->query($sql_items);
        
        $sum = 0;
        if ($res_items) {
            while ($item = $res_items->fetch_assoc()) {
                $sum += ($item['qty'] * $item['rate']);
            }
        }
        
        if ($sum > 0) {
            // Update invoice
            // Setting subtotal and total to sum (ignoring tax/discount for this fix as they are likely 0 too if total is 0)
            $update_sql = "UPDATE tblinvoices SET total = $sum, subtotal = $sum WHERE id = $id";
            if ($mysqli->query($update_sql)) {
                echo "Updated Invoice ID $id: Total set to $sum\n";
            } else {
                echo "Error updating Invoice ID $id: " . $mysqli->error . "\n";
            }
        } else {
            echo "Invoice ID $id has no items or items sum to 0. Skipping.\n";
        }
    }
} else {
    echo "Error querying invoices: " . $mysqli->error . "\n";
}
