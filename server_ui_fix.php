<?php
// Complete server UI fix - replaces missing DeskApp CSS framework

$css_content = '
/* DeskApp Framework CSS - Server Fix */
body {
    font-family: "Roboto", sans-serif;
    background-color: #f8f9fa;
    margin: 0;
    padding: 0;
}

.main-container {
    padding: 20px;
}

/* Card Box Styling */
.card-box {
    background: #ffffff;
    border-radius: 10px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    margin-bottom: 30px;
    border: 1px solid #e9ecef;
}

.pd-20 {
    padding: 20px;
}

.pb-20 {
    padding-bottom: 20px;
}

.mb-30 {
    margin-bottom: 30px;
}

/* Typography */
.text-blue {
    color: #1b00ff;
}

.h4 {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 15px;
    color: #333;
}

/* Layout */
.clearfix::after {
    content: "";
    display: table;
    clear: both;
}

.pull-left {
    float: left;
}

.row {
    display: flex;
    flex-wrap: wrap;
    margin: 0 -15px;
}

.col-md-12 {
    flex: 0 0 100%;
    max-width: 100%;
    padding: 0 15px;
}

/* Table Styling - DeskApp Style */
.data-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.data-table thead {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.data-table thead th {
    color: white;
    font-weight: 600;
    padding: 15px 12px;
    text-align: left;
    border: none;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.data-table tbody td {
    padding: 12px;
    border-bottom: 1px solid #f0f0f0;
    vertical-align: middle;
    color: #333;
}

.data-table tbody tr:hover {
    background-color: #f8f9ff;
}

.data-table tbody tr:last-child td {
    border-bottom: none;
}

/* Form Elements - DeskApp Style */
.custom-select,
.form-control {
    background-color: #ffffff;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    color: #495057;
    font-size: 0.9rem;
    padding: 10px 15px;
    transition: all 0.3s ease;
    min-height: 45px;
}

.custom-select:focus,
.form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    outline: none;
}

/* Button Styling - DeskApp Style */
.btn {
    border-radius: 8px;
    font-weight: 600;
    padding: 10px 20px;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
    font-size: 0.9rem;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    color: white;
    text-decoration: none;
}

.btn-secondary {
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
    color: white;
}

.btn-secondary:hover {
    transform: translateY(-2px);
    color: white;
    text-decoration: none;
}

.btn-link {
    background: none;
    border: none;
    color: #667eea;
    padding: 5px;
}

.btn-link:hover {
    color: #5a67d8;
    text-decoration: none;
}

/* Dropdown Styling - DeskApp Style */
.dropdown {
    position: relative;
    display: inline-block;
}

.dropdown-toggle {
    background: none;
    border: none;
    cursor: pointer;
    padding: 8px;
    color: #6c757d;
    font-size: 1.2rem;
    border-radius: 50%;
    transition: all 0.3s ease;
}

.dropdown-toggle:hover {
    background-color: #f8f9fa;
    color: #667eea;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border: none;
    border-radius: 10px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
    min-width: 200px;
    z-index: 1000;
    display: none;
    padding: 10px 0;
    margin-top: 5px;
}

.dropdown-menu.show {
    display: block;
}

.dropdown-item {
    display: flex;
    align-items: center;
    padding: 10px 20px;
    text-decoration: none;
    color: #333;
    transition: all 0.3s ease;
    border: none;
    background: none;
}

.dropdown-item:hover {
    background-color: #f8f9ff;
    color: #667eea;
    text-decoration: none;
}

.dropdown-item i {
    margin-right: 10px;
    width: 16px;
    text-align: center;
}

/* Alert Styling - DeskApp Style */
.alert {
    padding: 15px 20px;
    margin-bottom: 20px;
    border: none;
    border-radius: 10px;
    font-weight: 500;
}

.alert-success {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    color: #155724;
    border-left: 4px solid #28a745;
}

.alert-danger {
    background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
    color: #721c24;
    border-left: 4px solid #dc3545;
}

/* Filter Section - DeskApp Style */
.filter-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    align-items: end;
    margin-bottom: 25px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-weight: 600;
    margin-bottom: 8px;
    color: #495057;
    font-size: 0.9rem;
}

.custom-date-range {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.filter-buttons {
    display: flex;
    gap: 10px;
    justify-content: flex-start;
}

/* Icons */
.text-success {
    color: #28a745;
}

.text-muted {
    color: #6c757d;
}

.font-24 {
    font-size: 24px;
}

/* Responsive */
@media (max-width: 768px) {
    .filter-row {
        grid-template-columns: 1fr;
    }
    
    .custom-date-range {
        grid-template-columns: 1fr;
    }
    
    .data-table {
        font-size: 0.8rem;
    }
    
    .data-table th,
    .data-table td {
        padding: 8px 6px;
    }
}

/* Highlight row */
.highlight-row {
    background-color: #fff3cd !important;
}

/* Status form styling */
.d-flex {
    display: flex;
}

.align-items-center {
    align-items: center;
}

.ml-2 {
    margin-left: 8px;
}

.p-0 {
    padding: 0;
}

/* Make sure select in status looks good */
form select.custom-select {
    min-width: 200px;
    margin-right: 10px;
}
';

// Write the CSS file
file_put_contents('assets/css/deskapp-server-fix.css', $css_content);

echo "✅ DeskApp server fix CSS created successfully!<br>";
echo "File created: assets/css/deskapp-server-fix.css<br>";
echo "<br>Next step: Add this line to your view_leads.php head section:<br>";
echo '<code>&lt;link rel="stylesheet" href="assets/css/deskapp-server-fix.css"&gt;</code>';
?>