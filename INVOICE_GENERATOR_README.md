# Invoice Generator Documentation

## Overview
The Invoice Generator is a comprehensive tool that creates professional invoices from cost sheet data with PDF download functionality.

## Features

### 1. **Industry Standard Design**
- Professional invoice layout with company branding
- Clean, organized structure with proper sections
- Print-friendly CSS for PDF generation

### 2. **Complete Service Details**
- **VISA / Flight Booking**: Detailed breakdown with suppliers, dates, passengers, and costs
- **Accommodation**: Hotel details, check-in/out dates, room types, nights, and rates
- **Transportation**: Vehicle types, daily rates, extra charges, and totals
- **Cruise Hire**: Boat types, cruise details, and pricing
- **Agent Package Services**: Destination-wise package details with passenger counts
- **Medical Tourism**: Hospital details, treatment information, and GST calculations
- **Extras/Miscellaneous**: Additional services and charges

### 3. **Payment Information**
- Payment history and details
- Balance calculations
- Payment method information
- Due amounts

### 4. **Company Branding**
- Company logo and details
- Professional header design
- Contact information
- Terms and conditions

### 5. **PDF Generation**
- Browser-based PDF generation (Print to PDF)
- Optimized for printing
- Professional formatting
- Auto-print functionality

## File Structure

```
gleesire/
├── generate_invoice.php          # Main invoice generator
├── assets/css/invoice.css        # Invoice-specific styles
└── INVOICE_GENERATOR_README.md   # This documentation
```

## Usage

### Accessing the Invoice Generator
1. Navigate to **Cost Sheets** in the admin panel
2. Find the desired cost sheet
3. Click the **Invoice** button or select "Generate Invoice" from the dropdown menu

### Generating PDF
1. Click the "Download PDF" button in the invoice preview
2. The system will open a new window with print-optimized layout
3. Use browser's "Print" function and select "Save as PDF"

## Configuration

### Company Details
Edit the company information in `generate_invoice.php`:

```php
$company_name = "Gleesire Travel & Tourism";
$company_address = "123 Business Street, City, State 12345";
$company_phone = "+1 (555) 123-4567";
$company_email = "info@gleesire.com";
$company_website = "www.gleesire.com";
$company_logo = "assets/deskapp/vendors/images/custom-logo.svg";
```

### Styling Customization
Modify `assets/css/invoice.css` to customize:
- Colors and branding
- Layout and spacing
- Font sizes and styles
- Print-specific formatting

## Technical Details

### Dependencies
- PHP 7.0+
- MySQL database
- Existing cost sheet system
- Bootstrap CSS framework

### Database Integration
The invoice generator reads from:
- `tour_costings` table (main cost sheet data)
- `enquiries` table (customer information)
- `converted_leads` table (travel details)
- Related service tables

### Security
- User privilege checking
- SQL injection prevention
- Input sanitization
- Session validation

## Customization Options

### Adding New Services
To add new service types:
1. Update the service detection logic
2. Add new service section in the template
3. Include appropriate database fields

### Modifying Layout
- Edit the HTML structure in `generate_invoice.php`
- Update CSS in `assets/css/invoice.css`
- Adjust responsive breakpoints as needed

### Currency Support
The system supports multiple currencies:
- USD, EUR, GBP, INR
- Middle Eastern currencies (BHD, KWD, OMR, QAR, SAR, AED)
- Asian currencies (THB, SGD, RM)

## Troubleshooting

### Common Issues
1. **PDF not generating**: Check browser print settings
2. **Missing data**: Verify cost sheet completeness
3. **Styling issues**: Clear browser cache
4. **Permission errors**: Check user privileges

### Debug Mode
Add `?debug=1` to the URL for additional debugging information.

## Future Enhancements

### Planned Features
- Email invoice functionality
- Multiple invoice templates
- Automated invoice numbering
- Payment integration
- Digital signatures

### API Integration
- Payment gateway integration
- Email service integration
- Cloud storage for invoices
- Automated backup system

## Support

For technical support or feature requests, contact the development team or refer to the main system documentation.

## Version History

- **v1.0**: Initial release with basic invoice generation
- **v1.1**: Added comprehensive service details
- **v1.2**: Enhanced PDF formatting and company branding
- **v1.3**: Added payment information and balance calculations