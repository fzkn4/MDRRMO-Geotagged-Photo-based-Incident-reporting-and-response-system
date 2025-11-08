# MDRRMO Geotagged Incident Reporting System

A web-based incident reporting system for Municipal Disaster Risk Reduction and Management Office (MDRRMO) with geotagged photo capabilities.

## Features

- **Secure Login System**: Session-based authentication with multiple user roles
- **Incident Reporting**: Create and manage emergency incidents with photos
- **Geolocation**: Automatic GPS tagging and manual location selection
- **Real-time Map**: Interactive map for location visualization
- **Status Management**: Track incident status (New, Dispatched, Resolved, Cancelled)
- **Export Functionality**: Export incident data
- **Responsive Design**: Works on desktop and mobile devices

## Authentication System

The application includes a comprehensive authentication system with user registration and role-based access control.

### User Registration

- **Sign-up Form**: Complete registration with role selection (Admin/Client)
- **Form Validation**: Client-side and server-side validation
- **Password Security**: Password strength indicator and secure hashing
- **User Data**: Full name, email, organization, phone number

### User Roles

- **Admin**: Full system access, user management, incident management
- **Client**: Incident reporting and viewing capabilities

### Demo Users

- **Admin**: `admin` / `mdrrmo2024`
- **Client**: `client` / `client2024`

### Security Features

- Session-based authentication
- Password hashing with PHP's `password_hash()`
- Automatic redirect to login for unauthorized access
- Secure logout functionality
- Form validation and error handling
- Role-based access control

## File Structure

```
├── index.php          # Main application (protected by login)
├── login.php          # Login page
├── signup.php         # User registration page
├── users.php          # User management (admin only)
├── logout.php         # Logout handler
├── auth.php           # Authentication helper functions
├── users.json         # User data storage (auto-generated)
├── style.css          # Custom styles
├── script.js          # Application JavaScript
└── README.md          # This file
```

## Setup Instructions

### Docker (Development)

See `README-Docker.md` for the complete Docker Compose workflow that replicates the container environment bundled with the project.

### XAMPP (Windows)

Follow `docs/xampp-setup.md` for a detailed guide to installing the system on XAMPP, including Apache/PHP configuration, `.env` variable management, database provisioning, and troubleshooting practices adapted from the ZDSPGC Event & Inventory Management System deployment notes.[^zdspgc]

[^zdspgc]: https://github.com/fzkn4/ZDSPGC-EVENT-AND-INVENTORY-MANAGEMENT-SYSTEM

## Security Notes

- This is a demo application with hardcoded credentials
- For production use, implement proper database authentication
- Consider adding password hashing, CSRF protection, and rate limiting
- Session timeout and secure cookie settings should be configured

## Browser Compatibility

- Modern browsers with ES6+ support
- Geolocation API support for GPS functionality
- File API support for photo uploads
- LocalStorage support for data persistence
