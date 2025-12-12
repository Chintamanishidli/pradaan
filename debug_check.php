<?php
define('BASEPATH', 'c:/xampp/htdocs/perfex_crm/');
define('APPPATH', 'c:/xampp/htdocs/perfex_crm/application/');
require_once(BASEPATH . 'system/core/CodeIgniter.php');

// We can't easily standalone boot CI, but we can query DB if we load config manually or just use raw mysqli/pdo for a quick check.
// actually, easier to make a controller method temporarily accessible or just put a file in root that acts as a test.
// But perfex structure might prevent direct access without valid session/CI.
// Let's modify Misc.php again to add a debug logging.
