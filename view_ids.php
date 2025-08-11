<?php
// Include header
require_once "includes/header.php";

// Check if user has admin privileges
if($_SESSION["role_id"] != 1) {
    header("location: index.php");
    exit;
}

// Get departments
$departments_sql = "SELECT id, name FROM departments ORDER BY id";
$departments = mysqli_query($conn, $departments_sql);

// Get sources
$sources_sql = "SELECT id, name FROM sources ORDER BY id";
$sources = mysqli_query($conn, $sources_sql);

// Get destinations
$destinations_sql = "SELECT id, name FROM destinations ORDER BY id";
$destinations = mysqli_query($conn, $destinations_sql);

// Get file managers (users)
$file_managers_sql = "SELECT id, username, full_name FROM users ORDER BY id";
$file_managers = mysqli_query($conn, $file_managers_sql);
?>

<div class="pd-20 card-box mb-30">
    <div class="clearfix mb-20">
        <div class="pull-left">
            <h4 class="text-blue h4">Database IDs Reference</h4>
        </div>
    </div>

    <div class="row">
        <!-- Departments -->
        <div class="col-md-6 mb-30">
            <div class="pd-20 card-box height-100-p">
                <h5 class="mb-15">Department IDs</h5>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Department Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($departments)): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Sources -->
        <div class="col-md-6 mb-30">
            <div class="pd-20 card-box height-100-p">
                <h5 class="mb-15">Source IDs</h5>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Source Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($sources)): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Destinations -->
        <div class="col-md-6 mb-30">
            <div class="pd-20 card-box height-100-p">
                <h5 class="mb-15">Destination IDs</h5>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Destination Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($destinations)): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- File Managers -->
        <div class="col-md-6 mb-30">
            <div class="pd-20 card-box height-100-p">
                <h5 class="mb-15">File Manager IDs (Users)</h5>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Full Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($file_managers)): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once "includes/footer.php";
?>