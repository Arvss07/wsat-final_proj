# Project Setup Guide

This guide will walk you through the steps to set up and run this project on your local machine using XAMPP (or any similar Apache, MySQL, PHP stack).

## Prerequisites

- **XAMPP (or WAMP/LAMP/MAMP)**: Ensure you have a local server environment installed that includes Apache, MySQL (or MariaDB), and PHP.
- **Composer**: Make sure Composer is installed globally on your system. You can download it from [getcomposer.org](https://getcomposer.org/) (Needed if you want to update dependencies later, but the project includes a `vendor` directory).

## Setup Steps

1.  **Extract Project Files**

    Extract the project files to your web server's document root (e.g., `d:\xampp\htdocs\`). Let's assume the project folder is named `wsat-final_proj`.
    Navigate to the project directory:

    ```bash
    cd d:\xampp\htdocs\wsat-final_proj
    ```

2.  **Install PHP Dependencies (Vendor folder included)**

    The `vendor` folder with PHP dependencies is already included. If you need to update them in the future or if the `vendor` folder is missing, you would run:

    ```bash
    composer install
    ```

3.  **Set Up the Database**

    - Open phpMyAdmin (usually accessible via `http://localhost/phpmyadmin`).
    - Create a new database. Let's assume you name it `wsat_final_proj_db`.
    - Select the newly created database.
    - Go to the "Import" tab.
    - Click "Choose File" and select the `db.sql` file located in the root of this project.
    - Click "Go" or "Import" to execute the SQL script. This will create the necessary tables and populate them if there's initial data.

4.  **Configure Environment Variables (Database Connection)**

    Ensure your database credentials are correctly set in the configuration file (`config/database.php` or `.env`).

5.  **Configure Apache for `.htaccess` (URL Rewriting)**

    To ensure that URL rewriting (if used by the project, typically via an `.htaccess` file) works correctly, you may need to adjust your main Apache configuration.

    *   **Open Apache Configuration File (`httpd.conf`):**
        Find your main Apache configuration file. In XAMPP, this is typically located at:
        `d:\xampp\apache\conf\httpd.conf`

    *   **Locate the DocumentRoot Directory Block:**
        Search within `httpd.conf` for a section that looks similar to this (the path to `htdocs` might vary based on your XAMPP installation directory):

        ```apache
        # Example: Default XAMPP htdocs directory configuration
        <Directory "D:/xampp/htdocs">
            #
            # Possible values for the Options directive are "None", "All",
            # or any combination of:
            #   Indexes Includes FollowSymLinks SymLinksifOwnerMatch ExecCGI MultiViews
            #
            # Note that "MultiViews" must be named *explicitly* --- "Options All"
            # doesn't give it to you.
            #
            # The Options directive is both complicated and important.  Please see
            # http://httpd.apache.org/docs/2.4/mod/core.html#options
            # for more information.
            #
            Options Indexes FollowSymLinks Includes ExecCGI

            #
            # AllowOverride controls what directives may be placed in .htaccess files.
            # It can be "All", "None", or any combination of the keywords:
            #   Options FileInfo AuthConfig Limit
            #
            AllowOverride None  <-- YOU MIGHT SEE THIS OR SOMETHING LIKE FileInfo

            #
            # Controls who can get stuff from this server.
            #
            Require all granted
        </Directory>
        ```

    *   **Change `AllowOverride` Directive:**
        In the `<Directory "d:/xampp/htdocs">` block (or the equivalent for your web server's main document root), change the `AllowOverride` directive from whatever it is (e.g., `None`, `FileInfo`) to `All`.

        It should look like this after your change:
        ```apache
        <Directory "D:/xampp/htdocs">
            # ... other options ...
            AllowOverride All
            Require all granted
        </Directory>
        ```
        This change allows directives in `.htaccess` files within your `htdocs` folder (and its subdirectories, like your project folder) to override server configuration settings, which is often necessary for routing and other features.

    *   **Restart Apache:**
        Open the XAMPP Control Panel and **stop** and then **start** the Apache module for the changes to take effect.

6.  **Access the Project**

    Navigate to `http://localhost/wsat-final_proj/` (or the subfolder you placed it in).

## Troubleshooting

- **PHP Version:** Ensure your XAMPP PHP version meets the project's requirements (check `composer.json` for `require.php` if specified).
- **Apache Modules:** Make sure `mod_rewrite` is enabled in Apache if the project uses `.htaccess` for routing. In XAMPP, you can usually enable this by uncommenting `LoadModule rewrite_module modules/mod_rewrite.so` in `d:\xampp\apache\conf\httpd.conf` and ensuring `AllowOverride All` is set for your project directory.
- **File Permissions:** Ensure the web server has write permissions for directories like `uploads/` or any cache/log directories if the application needs them.
- **Database Connection Errors:** Double-check your database credentials in the configuration file (`config/database.php` or `.env`). Ensure the MySQL server is running.

---

This `README.md` provides a comprehensive guide to setting up the project.
