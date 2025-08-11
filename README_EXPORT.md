# PDF and Excel Export Functionality

The export functionality has been implemented using native PHP features without requiring any external libraries.

## Features

### PDF Export
- Generates a printable HTML page that can be saved as PDF using the browser's print function
- Includes all cost sheet details with proper formatting
- Has a print button at the top of the page

### Excel Export
- Generates a CSV file that can be opened in Excel or any spreadsheet application
- Includes all cost sheet details organized in sections
- Uses UTF-8 encoding with BOM for proper character display in Excel

## Usage

The export functionality is available in the cost sheets page:
- Click on the "PDF" button to view a printable version of the cost sheet that can be saved as PDF
- Click on the "Excel" button to download a cost sheet as CSV file that can be opened in Excel

## Troubleshooting

If you encounter any issues:

1. Make sure your browser allows pop-ups from the site for the PDF view
2. For Excel files, if special characters don't display correctly, try opening the CSV file in Excel using the Data > From Text/CSV import option