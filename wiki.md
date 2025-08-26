# Project Summary
The Lost & Found website is a platform built using PHP Native, TailwindCSS, and MySQL, designed to help users report and search for lost or found items. It facilitates user authentication, item reporting, and searching functionalities, enhancing community engagement in recovering lost belongings.

# Project Module Description
- **User Authentication:** 
  - **Register (register.php):** Users can create an account with their name, email, password, and phone number.
  - **Login (login.php):** Users can log in with their email and password.
  - **Logout (logout.php):** Users can log out of their accounts.

- **Home Page (index.php):** Displays the latest lost and found items in a card grid format.

- **Report Item (laporan.php):** Users can report lost or found items through a multi-step form, including category selection, item details, and photo upload.

- **Search Items (cari.php):** Users can search for items based on keywords, categories, locations, and statuses.

- **Item Details (detail.php):** Provides detailed information about a specific item, including contact options for the owner/founder.

- **User Profile (profil.php):** Displays user information, their reported items, and options to edit or delete their reports.

# Directory Tree
```
/
├── config/
│   └── db.php              # Database configuration
├── partials/
│   ├── navbar.php          # Navigation bar
│   └── footer.php          # Footer
├── assets/css/
│   └── tailwind.css        # Custom CSS with TailwindCSS
├── uploads/                # Folder for item photos
├── index.php               # Home page
├── login.php               # Login page
├── register.php            # Registration page
├── logout.php              # Logout handler
├── laporan.php             # Report item form
├── cari.php                # Search page
├── detail.php              # Item detail page
├── profil.php              # User profile page
├── report_listing.php      # Content reporting handler
├── .htaccess               # Apache configuration
└── README.md               # Documentation
```

# File Description Inventory
- **db.php:** Contains the database connection settings and initializes the database schema.
- **navbar.php:** Reusable navigation bar component for the website.
- **footer.php:** Reusable footer component for the website.
- **tailwind.css:** Custom styling using TailwindCSS for the frontend.
- **index.php:** The main landing page displaying the latest reports.
- **login.php:** User login functionality.
- **register.php:** User registration functionality.
- **logout.php:** Handles user logout.
- **laporan.php:** Form for users to report lost or found items.
- **cari.php:** Search functionality for items.
- **detail.php:** Displays detailed information about a specific item.
- **profil.php:** User profile management page.
- **report_listing.php:** Handles reporting of inappropriate content.
- **.htaccess:** Server configuration for security and performance.
- **README.md:** Documentation for the project.

# Technology Stack
- **Backend:** PHP Native with PDO for database interactions.
- **Frontend:** HTML5, TailwindCSS, JavaScript.
- **Database:** MySQL.
- **Security:** Includes session management, password hashing, and input validation.

# Usage
1. **Register/Login:** Users can register with their name, email, password, and phone number. After registration, they can log in.
2. **Report Item:** Users can report items through a form that includes details and photo uploads.
3. **Search Items:** Users can search for items using various filters.
4. **Contact:** Users can contact item owners through a messaging system.
5. **Manage Reports:** Users can view and manage their reported items in their profile.

## Installation Steps
1. **Setup Database:**
   - Create a new MySQL database.
   - Update the database configuration in `config/db.php`.
   - Run the website to automatically create necessary tables.

2. **Setup Web Server:**
   - Ensure PHP >= 7.4 and MySQL are installed.
   - Copy all files to the web server directory.
   - Ensure the `uploads/` folder is writable.

3. **Configure:**
   - Edit `config/db.php` with your database settings.
   - Ensure TailwindCSS CDN is accessible.
