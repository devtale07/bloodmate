# BloodMate - Blood Donation Management System

BloodMate is a comprehensive web-based blood donation management system that connects blood donors with recipients, manages blood inventory, and provides administrative tools for blood banks and hospitals.

## Features

### For Donors
- Easy donor registration with comprehensive profile management
- Blood donation history tracking
- Eligibility validation (age, last donation date)
- Notification system for urgent blood requests
- Donation center locator

### For Recipients
- Blood request submission with urgency levels
- Blood type compatibility information
- Hospital/location specification
- Request status tracking
- Emergency contact system

### Blood Inventory Management
- Real-time blood inventory tracking
- Stock level indicators (Critical, Low, Moderate, Good)
- Blood type availability display
- Automated low-stock alerts
- Inventory search and filtering

### Administrative Features
- Secure admin authentication
- Comprehensive dashboard with statistics
- Donor management (view, edit, approve)
- Blood request management (approve, decline, track)
- Inventory management (add/remove stock)
- Contact message management
- Reporting and analytics

### Contact & Support
- Multi-channel contact system
- 24/7 emergency helpline
- FAQ section
- Support ticket system
- Newsletter subscription

## Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Styling**: Custom CSS with responsive design
- **Icons**: Font Awesome 6.0
- **Security**: Password hashing, input validation, SQL injection prevention

## Installation & Setup

### Prerequisites
- Web server (Apache/Nginx)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Modern web browser

### Database Setup

1. Create a MySQL database named `bloodmate`
2. Import the database schema:
   ```bash
   mysql -u your_username -p bloodmate < database/bloodmate_schema.sql
   ```

3. Update database configuration in `config/database.php`:
   ```php
   private $host = 'localhost';
   private $db_name = 'bloodmate';
   private $username = 'your_username';
   private $password = 'your_password';
   ```

### Web Server Configuration

1. Copy all files to your web server document root
2. Ensure PHP has write permissions for error logging
3. Configure virtual host (optional but recommended)

### Default Admin Account
- **Username**: admin
- **Password**: admin123
- **Note**: Change this password immediately after first login

## File Structure

```
BloodMate/
├── index.html                 # Homepage
├── donor-registration.html    # Donor registration form
├── recipient-request.html     # Blood request form
├── blood-inventory.html       # Blood inventory display
├── contact.html              # Contact page
├── admin/
│   ├── login.html            # Admin login
│   └── dashboard.html        # Admin dashboard
├── api/                      # PHP backend files
│   ├── register-donor.php
│   ├── submit-request.php
│   ├── get-inventory.php
│   ├── admin-login.php
│   └── ...
├── css/
│   └── style.css            # Main stylesheet
├── js/                      # JavaScript files
│   ├── main.js
│   ├── donor-form.js
│   ├── recipient-form.js
│   └── ...
├── config/
│   └── database.php         # Database configuration
├── database/
│   └── bloodmate_schema.sql # Database schema
└── README.md               # This file
```

## Usage Guide

### For Blood Donors

1. **Registration**:
   - Visit the "Become a Donor" page
   - Fill out the registration form with personal details
   - Provide blood group and contact information
   - Agree to terms and conditions

2. **Eligibility Requirements**:
   - Age: 18-65 years
   - Weight: Minimum 50kg
   - Hemoglobin: Minimum 12.5g/dl
   - Wait 56 days between donations

### For Blood Recipients

1. **Requesting Blood**:
   - Visit the "Request Blood" page
   - Fill out patient information
   - Specify blood group needed and urgency level
   - Provide hospital/location details

2. **Urgency Levels**:
   - **Critical**: Immediate need (within hours)
   - **High**: Urgent need (within 24 hours)
   - **Medium**: Scheduled procedure (2-7 days)
   - **Low**: Planned surgery (7+ days)

### For Administrators

1. **Login**: Access admin panel at `/admin/login.html`
2. **Dashboard**: View statistics and recent activities
3. **Manage Donors**: Review and approve donor registrations
4. **Manage Requests**: Process blood requests
5. **Inventory**: Update blood stock levels
6. **Messages**: Respond to contact inquiries

## API Endpoints

### Public Endpoints
- `GET /api/get-inventory.php` - Get blood inventory
- `GET /api/get-urgent-requests.php` - Get urgent blood requests
- `POST /api/register-donor.php` - Register new donor
- `POST /api/submit-request.php` - Submit blood request
- `POST /api/submit-contact.php` - Submit contact message

### Admin Endpoints (Require Authentication)
- `POST /api/admin-login.php` - Admin authentication
- `GET /api/admin-dashboard-data.php` - Dashboard data
- `POST /api/admin-add-stock.php` - Add blood stock
- `POST /api/admin-approve-request.php` - Approve blood request

## Security Features

- Password hashing using PHP's `password_hash()`
- SQL injection prevention with prepared statements
- Input validation and sanitization
- XSS protection
- CSRF protection (recommended to implement)
- Secure session management

## Browser Compatibility

- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+
- Mobile browsers (iOS Safari, Chrome Mobile)

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is licensed under the MIT License. See LICENSE file for details.

## Support

For technical support or questions:
- Email: support@bloodmate.com
- Emergency Helpline: +91 123 456 7890
- Documentation: See this README file

## Changelog

### Version 1.0.0 (Initial Release)
- Complete blood donation management system
- Donor registration and management
- Blood request processing
- Inventory management
- Admin dashboard
- Responsive design
- Security implementations

## Future Enhancements

- SMS notifications for urgent requests
- Email automation system
- Mobile app development
- Integration with hospital systems
- Advanced reporting and analytics
- Multi-language support
- Donation appointment scheduling
- Blood drive event management
