<?php
// Enable all errors
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Load Perfex framework
require_once('application/config/config.php');

// Get CI instance
$CI =& get_instance();

// Load database
$CI->load->database();

// Enable query logging
$CI->db->save_queries = TRUE;

// Try to load the Clients model
echo "Loading Clients model...<br>";
$CI->load->model('clients_model');

echo "Attempting to execute query...<br><hr>";

try {
    // Try to get clients - this will trigger the error
    $clients = $CI->clients_model->get([], [], 1);
    echo "<h3>SUCCESS!</h3>";
    echo "Query executed successfully.<br>";
    
} catch (Exception $e) {
    echo "<h3 style='color:red;'>ERROR CAUGHT</h3>";
    echo "Error message: " . $e->getMessage() . "<br><br>";
    
    // Try to get the last query
    echo "<h4>Last Query:</h4>";
    $queries = $CI->db->queries;
    if (!empty($queries)) {
        echo "<pre>" . htmlspecialchars(end($queries)) . "</pre>";
    } else {
        echo "No queries logged.<br>";
    }
    
    echo "<h4>All Queries:</h4>";
    foreach ($queries as $i => $query) {
        echo "<strong>Query #" . ($i+1) . ":</strong><br>";
        echo "<pre>" . htmlspecialchars($query) . "</pre><hr>";
    }
}

// Also try to get the query from the general log
echo "<h4>Alternative: Check database general log</h4>";
$CI->db->query("SET GLOBAL general_log = 'ON'");
$CI->db->query("SET GLOBAL log_output = 'TABLE'");

// Give it a moment
sleep(1);

// Try a direct query to see structure
echo "<h4>Testing direct table structure:</h4>";
$result = $CI->db->query("SHOW COLUMNS FROM tblclients LIKE 'custemer_name'");
if ($result->num_rows() > 0) {
    echo "Column 'custemer_name' EXISTS in table.<br>";
} else {
    echo "Column 'custemer_name' DOES NOT EXIST in table.<br>";
}
?>