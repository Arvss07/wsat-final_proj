<h1 align="center">WSAT Final Project</h1>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.1%2B-blue?logo=php" alt="PHP">
  <img src="https://img.shields.io/badge/license-MIT-green" alt="License">
  <img src="https://img.shields.io/badge/XAMPP-Compatible-orange?logo=apache" alt="XAMPP Compatible">
  <img src="https://img.shields.io/badge/build-passing-brightgreen" alt="Build Status">
  <img src="https://img.shields.io/github/issues-raw/Arvss07/wsat-final_proj" alt="Issues">
  <img src="https://img.shields.io/badge/Responsive-Yes-red" alt="Responsive">
  <img src="https://img.shields.io/badge/Database-MySQL-informational" alt="Database">
  <img src="https://img.shields.io/badge/Email-SMTP%20Ready-yellow" alt="Email">
</p>

---

## Features

- User authentication (register, login, password reset)
- Role-based access control (Shopper, Seller, Admin)
- Product catalog and detail pages
- Shopping cart and checkout system
- Order management for shoppers and sellers
- Seller dashboard for product and order management
- Admin dashboard for user and product oversight
- Responsive design for desktop and mobile
- Email notifications (welcome, password reset, etc.)
- Secure password hashing
- Environment-based configuration
- Modular code structure for easy maintenance

---

## Prerequisites

- **XAMPP (or WAMP/LAMP/MAMP)**: Ensure you have a local server environment installed that includes Apache, MySQL (or MariaDB), and PHP.
- **Composer**: Make sure Composer is installed globally on your system. You can download it from [getcomposer.org](https://getcomposer.org/) (Needed if you want to update dependencies later, but the project includes a `vendor` directory).

## Project Structure

Below is the top-level project structure with a brief explanation of each directory and file:

```
├── Accounts.txt
├── admin/
├── api/
├── assets/
├── composer.json
├── composer.lock
├── config/
├── db.sql
├── includes/
├── index.php
├── pages/
├── README.md
├── seller/
├── templates/
├── uploads/
├── utils/
└── vendor/
```

### Explanation of Each Item

- **Accounts.txt**: (Optional) May be used for storing test/demo account credentials or notes.
- **admin/**: Contains admin dashboard and management pages for users, products, and settings.
- **api/**: Backend API endpoints for AJAX and client-server communication (e.g., authentication, cart, products, orders).
- **assets/**: Static files such as CSS, JavaScript, and images used by the frontend.
- **composer.json / composer.lock**: PHP Composer dependency files. `composer.json` lists dependencies, `composer.lock` locks their versions.
- **config/**: Configuration files, such as database connection settings and certificates.
- **db.sql**: SQL file to set up the database schema and initial data.
- **includes/**: Reusable PHP components like header, footer, and shared functions.
- **index.php**: Main entry point and router for the application.
- **pages/**: Main user-facing pages (home, login, register, cart, etc.).
- **README.md**: Project setup instructions and documentation.
- **seller/**: Seller dashboard and product/order management pages.
- **templates/**: Email templates and other reusable HTML/PHP templates.
- **uploads/**: Uploaded files, such as product images and user profile pictures.
- **utils/**: Utility/helper PHP scripts (e.g., hashing, mail, UUID generation).
- **vendor/**: Composer-managed PHP dependencies (do not edit manually).

---
