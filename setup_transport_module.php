<?php
echo "<h2>Setting up Transport Details Module</h2>";

echo "<h3>Step 1: Creating transport_details table</h3>";
include 'create_transport_table.php';

echo "<br><br><h3>Step 2: Adding transport privileges</h3>";
include 'add_transport_privileges.php';

echo "<br><br><h3>Setup Complete!</h3>";
echo "<p>The Transport Details module has been successfully set up. You can now:</p>";
echo "<ul>";
echo "<li>Access Transport Details from the left sidebar menu</li>";
echo "<li>Add new transport providers</li>";
echo "<li>Edit existing transport details</li>";
echo "<li>Delete transport records</li>";
echo "</ul>";
echo "<p><a href='transport_details.php'>Go to Transport Details</a></p>";
?>