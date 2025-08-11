<?php
/**
 * Timezone Configuration for Lead Management System
 * This file ensures all date/time operations use Indian Standard Time (IST)
 * regardless of server location or local timezone settings
 */

// Set PHP timezone to IST
date_default_timezone_set('Asia/Kolkata');
ini_set('date.timezone', 'Asia/Kolkata');

// Function to get current IST datetime
function getCurrentISTDateTime($format = 'Y-m-d H:i:s') {
    return date($format);
}

// Function to get current IST date
function getCurrentISTDate($format = 'Y-m-d') {
    return date($format);
}

// Function to get current IST time
function getCurrentISTTime($format = 'H:i:s') {
    return date($format);
}

// Function to format any datetime to IST
function formatToIST($datetime, $format = 'Y-m-d H:i:s') {
    if (empty($datetime)) return '';
    
    $dt = new DateTime($datetime);
    $dt->setTimezone(new DateTimeZone('Asia/Kolkata'));
    return $dt->format($format);
}

// Function to get IST timestamp
function getISTTimestamp() {
    return time();
}

// Function to convert any timezone to IST
function convertToIST($datetime, $fromTimezone = 'UTC') {
    if (empty($datetime)) return '';
    
    $dt = new DateTime($datetime, new DateTimeZone($fromTimezone));
    $dt->setTimezone(new DateTimeZone('Asia/Kolkata'));
    return $dt->format('Y-m-d H:i:s');
}
?>