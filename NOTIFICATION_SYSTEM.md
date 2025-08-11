# File Manager Notification System

## Overview
This system provides real-time notifications to file managers when new leads are assigned to them. The notifications appear in the top header with an alert sound and include the enquiry number and lead number.

## Features
- **Real-time notifications**: Notifications appear immediately when leads are assigned
- **Alert sound**: Pleasant notification sound plays when new notifications arrive
- **Auto-refresh**: Notifications are automatically refreshed every 30 seconds
- **Visual indicators**: Unread notifications are highlighted with red color and "New" badge
- **Detailed information**: Shows enquiry number, lead number, customer name, and timestamp

## How it works

### 1. Notification Creation
Notifications are automatically created when:
- A file manager is assigned to a lead in `edit_enquiry.php`
- A file manager is assigned during enquiry upload in `upload_enquiries.php`
- A file manager is assigned via CSV bulk upload

### 2. Notification Display
- Notifications appear in the header dropdown (bell icon)
- Unread notifications show a red badge with count
- Each notification shows:
  - Customer name or "Lead Assignment"
  - Message: "New lead is assigned to you with enquiry number X and lead number Y"
  - Date and time of assignment

### 3. Notification Sound
- Plays automatically when new notifications arrive (after login)
- Uses Web Audio API for a pleasant notification tone
- Only plays for new notifications, not existing ones

### 4. Mark as Read
- Clicking on a notification marks it as read
- Redirects to the leads view page with the specific lead highlighted

## Files Modified/Created

### New Files:
- `create_lead_assignment_notification.php` - Function to create notifications
- `test_notification.php` - Test page for the notification system

### Modified Files:
- `includes/header_deskapp.php` - Enhanced notification UI and sound
- `edit_enquiry.php` - Added notification creation when file manager assigned
- `upload_enquiries.php` - Added notification creation for manual and CSV uploads
- `get_notifications.php` - Improved notification data handling

## Database Structure
The system uses the existing `notifications` table with these fields:
- `id` - Primary key
- `user_id` - File manager user ID
- `enquiry_id` - Related enquiry ID
- `enquiry_number` - Enquiry/lead number
- `lead_number` - Original lead number
- `message` - Notification message
- `is_read` - Read status (0 = unread, 1 = read)
- `created_at` - Timestamp

## Testing
Use `test_notification.php` to create test notifications:
1. Select an enquiry from the dropdown
2. Select a file manager
3. Click "Create Test Notification"
4. Login as the selected file manager to see the notification

## Usage Instructions
1. **For Admins/Managers**: When assigning leads to file managers, notifications are automatically created
2. **For File Managers**: 
   - Check the bell icon in the header for new notifications
   - Red badge shows unread count
   - Click notifications to view the assigned lead
   - Notifications auto-refresh every 30 seconds

## Browser Compatibility
- Modern browsers with Web Audio API support
- Fallback for browsers without audio support
- Responsive design for mobile devices