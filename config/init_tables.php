<?php
// Include database connection
require_once 'database.php';

// Create roles table first
$sql = "CREATE TABLE IF NOT EXISTS roles (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if(mysqli_query($conn, $sql)){
    echo "Table roles created successfully.<br>";
} else {
    echo "ERROR: Could not create table roles " . mysqli_error($conn) . "<br>";
}

// Insert default roles immediately
$sql = "INSERT INTO roles (role_name) VALUES ('Admin'), ('Manager'), ('User')";
if(mysqli_query($conn, $sql)){
    echo "Default roles inserted successfully.<br>";
} else {
    echo "ERROR: Could not insert default roles " . mysqli_error($conn) . "<br>";
}

// Now create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    role_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
)";

if(mysqli_query($conn, $sql)){
    echo "Table users created successfully.<br>";
} else {
    echo "ERROR: Could not create table users " . mysqli_error($conn) . "<br>";
}

// Insert default admin user
$hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
$sql = "INSERT INTO users (username, password, full_name, email, role_id) 
        VALUES ('admin', '$hashed_password', 'Administrator', 'admin@example.com', 1)";
if(mysqli_query($conn, $sql)){
    echo "Default admin user created successfully.<br>";
} else {
    echo "ERROR: Could not insert default admin user " . mysqli_error($conn) . "<br>";
}

// Create departments table
$sql = "CREATE TABLE IF NOT EXISTS departments (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if(mysqli_query($conn, $sql)){
    echo "Table departments created successfully.<br>";
} else {
    echo "ERROR: Could not create table departments " . mysqli_error($conn) . "<br>";
}

// Create sources table
$sql = "CREATE TABLE IF NOT EXISTS sources (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if(mysqli_query($conn, $sql)){
    echo "Table sources created successfully.<br>";
} else {
    echo "ERROR: Could not create table sources " . mysqli_error($conn) . "<br>";
}

// Create lead_status table
$sql = "CREATE TABLE IF NOT EXISTS lead_status (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if(mysqli_query($conn, $sql)){
    echo "Table lead_status created successfully.<br>";
} else {
    echo "ERROR: Could not create table lead_status " . mysqli_error($conn) . "<br>";
}

// Create destinations table
$sql = "CREATE TABLE IF NOT EXISTS destinations (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if(mysqli_query($conn, $sql)){
    echo "Table destinations created successfully.<br>";
} else {
    echo "ERROR: Could not create table destinations " . mysqli_error($conn) . "<br>";
}

// Insert default data for other tables
// Insert default lead statuses
$sql = "INSERT INTO lead_status (name) VALUES 
        ('New'), ('In Progress'), ('Converted'), ('Closed'), ('Lost')";
if(mysqli_query($conn, $sql)){
    echo "Default lead statuses inserted successfully.<br>";
} else {
    echo "ERROR: Could not insert default lead statuses " . mysqli_error($conn) . "<br>";
}

// Insert some default departments
$sql = "INSERT INTO departments (name) VALUES 
        ('Sales'), ('Marketing'), ('Operations'), ('Customer Service')";
if(mysqli_query($conn, $sql)){
    echo "Default departments inserted successfully.<br>";
} else {
    echo "ERROR: Could not insert default departments " . mysqli_error($conn) . "<br>";
}

// Insert some default sources
$sql = "INSERT INTO sources (name) VALUES 
        ('Website'), ('Social Media'), ('Referral'), ('Direct'), ('Email Campaign')";
if(mysqli_query($conn, $sql)){
    echo "Default sources inserted successfully.<br>";
} else {
    echo "ERROR: Could not insert default sources " . mysqli_error($conn) . "<br>";
}

// Insert some default destinations
$sql = "INSERT INTO destinations (name) VALUES 
        ('Paris'), ('London'), ('New York'), ('Tokyo'), ('Sydney'), ('Dubai'), ('Singapore')";
if(mysqli_query($conn, $sql)){
    echo "Default destinations inserted successfully.<br>";
} else {
    echo "ERROR: Could not insert default destinations " . mysqli_error($conn) . "<br>";
}

// Create ad_campaigns table
$sql = "CREATE TABLE IF NOT EXISTS ad_campaigns (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    platform VARCHAR(50) NOT NULL,
    department_id INT NOT NULL,
    planned_days INT NOT NULL,
    budget DECIMAL(10,2) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id)
)";

if(mysqli_query($conn, $sql)){
    echo "Table ad_campaigns created successfully.<br>";
} else {
    echo "ERROR: Could not create table ad_campaigns " . mysqli_error($conn) . "<br>";
}

// Create enquiries table
$sql = "CREATE TABLE IF NOT EXISTS enquiries (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    lead_number VARCHAR(50) NOT NULL UNIQUE,
    received_datetime DATETIME NOT NULL,
    attended_by INT NOT NULL,
    department_id INT NOT NULL,
    source_id INT NOT NULL,
    ad_campaign_id INT,
    referral_code VARCHAR(50),
    customer_name VARCHAR(100) NOT NULL,
    mobile_number VARCHAR(20) NOT NULL,
    social_media_link VARCHAR(255),
    email VARCHAR(100),
    status_id INT NOT NULL,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (attended_by) REFERENCES users(id),
    FOREIGN KEY (department_id) REFERENCES departments(id),
    FOREIGN KEY (source_id) REFERENCES sources(id),
    FOREIGN KEY (ad_campaign_id) REFERENCES ad_campaigns(id),
    FOREIGN KEY (status_id) REFERENCES lead_status(id)
)";

if(mysqli_query($conn, $sql)){
    echo "Table enquiries created successfully.<br>";
} else {
    echo "ERROR: Could not create table enquiries " . mysqli_error($conn) . "<br>";
}

// Create converted_leads table
$sql = "CREATE TABLE IF NOT EXISTS converted_leads (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    enquiry_id INT NOT NULL,
    enquiry_number VARCHAR(20) NOT NULL,
    customer_location VARCHAR(255),
    secondary_contact VARCHAR(20),
    destination_id INT,
    other_details TEXT,
    travel_month DATE,
    travel_start_date DATE,
    travel_end_date DATE,
    adults_count INT,
    children_count INT,
    infants_count INT,
    customer_available_timing VARCHAR(100),
    file_manager_id INT,
    booking_confirmed BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (enquiry_id) REFERENCES enquiries(id),
    FOREIGN KEY (destination_id) REFERENCES destinations(id),
    FOREIGN KEY (file_manager_id) REFERENCES users(id)
)";

if(mysqli_query($conn, $sql)){
    echo "Table converted_leads created successfully.<br>";
} else {
    echo "ERROR: Could not create table converted_leads " . mysqli_error($conn) . "<br>";
}

// Create comments table
$sql = "CREATE TABLE IF NOT EXISTS comments (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    enquiry_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (enquiry_id) REFERENCES enquiries(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

if(mysqli_query($conn, $sql)){
    echo "Table comments created successfully.<br>";
} else {
    echo "ERROR: Could not create table comments " . mysqli_error($conn) . "<br>";
}

// Create user_privileges table
$sql = "CREATE TABLE IF NOT EXISTS user_privileges (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    role_id INT NOT NULL,
    menu_name VARCHAR(50) NOT NULL,
    can_view BOOLEAN DEFAULT FALSE,
    can_add BOOLEAN DEFAULT FALSE,
    can_edit BOOLEAN DEFAULT FALSE,
    can_delete BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
)";

if(mysqli_query($conn, $sql)){
    echo "Table user_privileges created successfully.<br>";
} else {
    echo "ERROR: Could not create table user_privileges " . mysqli_error($conn) . "<br>";
}

echo "Database setup completed successfully!";
?>