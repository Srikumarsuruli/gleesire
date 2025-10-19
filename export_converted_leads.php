<?php
session_start();
require_once "config/database.php";

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="converted_leads.csv"');

echo "id,enquiry_id,enquiry_number\n";
echo "1,2,test\n";
?>
