# NodeNet

## Overview

`NodeNet` is a browser-based offline lab network file manager built with PHP, MySQL, and vanilla JavaScript. It provides authenticated users with a personal node-based folder/file system, live public network file browsing, file editing, uploads, downloads, and simple permission controls.

The UI is styled as a desktop-like app and runs from the project root with the API exposed under `api/`.

## Features

- User registration and login
- Session-based authentication
- Personal file manager with nested folders
- File creation, renaming, moving, deletion, and download
- Built-in text editor for file content
- File permissions: private, public, read-only
- Public network file browser for shared files
- Drag-and-drop and folder upload support
- Zip download for folders
- Active node counter based on recent user activity
- Image preview support for image files

## Tech Stack

- PHP 7+ / 8+ (runs under XAMPP)
- MySQL / MariaDB
- PDO for database access
- Vanilla JavaScript for frontend logic
- HTML/CSS for the UI

## Project Structure

- `index.html` - main web interface
- `assets/css/style.css` - styling
- `assets/js/app.js` - frontend application logic
- `api/index.php` - main API router
- `api/Config/Database.php` - database singleton configuration
- `api/Controllers/` - controller classes for auth, files, network
- `api/Models/` - models for users and file nodes
- `storage/` - user file content storage directory
- `database.sql` - database schema

## Setup

1. Install XAMPP and start Apache + MySQL.
2. Place the project in `htdocs/NodeNet`.
3. Create the database and tables:
   - Open phpMyAdmin or use MySQL CLI.
   - Run `database.sql`.
4. Update database credentials if needed in `api/Config/Database.php`:
   - `$host`
   - `$user`
   - `$pass`
   - `$name`
5. Ensure the `storage/` folder exists and is writable by the web server.
6. Open the site in your browser:
   - `http://localhost/NodeNet/index.html`

## Usage

### Authentication

- Register a new username and password.
- Login to access your personal file manager.
- Logout when done.

### File Manager

- Browse files and folders in your root or nested directories.
- Create a folder or file.
- Rename, move, or delete items.
- Upload files or complete folders using the upload dropdown.
- Drag and drop files/folders into the file manager area to upload them.
- Search for files using the search box.
- Click a file to open it in the editor.
- Save changes for text files.
- Download files or folders as ZIP.

### Permissions and Network Sharing

- `private`: only you can view the file.
- `public`: anyone browsing network public files can view and edit.
- `read_only`: anyone browsing public files can view but not edit.
- The network public view shows files flagged as `public` or `read_only` from all users.

## API Endpoints

The application uses the following API endpoints under `api/`:

- `auth/register` POST - register a new user
- `auth/login` POST - login existing user
- `auth/logout` GET - logout
- `auth/session` GET - get current session status
- `nodes/active` GET - return recently active node count
- `files/list` POST - list files/folders in a directory
- `files/public` GET - list public/shared files
- `files/create` POST - create a folder or file node
- `files/read` POST - read a file's content
- `files/save` POST - update a file's content
- `files/permission` POST - update file permission
- `files/delete` POST - delete a file or folder
- `files/rename` POST - rename a file or folder
- `files/move` POST - move an item to another folder
- `files/upload` POST - upload file data
- `files/download` GET - download a file or folder ZIP

## Database Schema

The schema is defined in `database.sql`.

### `users`

- `id` INT AUTO_INCREMENT PRIMARY KEY
- `username` VARCHAR(255) UNIQUE
- `password_hash` VARCHAR(255)
- `last_seen` DATETIME
- `created_at` DATETIME

### `files`

- `id` INT AUTO_INCREMENT PRIMARY KEY
- `user_id` INT NOT NULL
- `parent_id` INT NULL
- `name` VARCHAR(255)
- `type` ENUM('file','folder')
- `path` VARCHAR(1000) NULL
- `permission` ENUM('private','public','read_only')
- `size` INT
- `created_at` DATETIME
- `updated_at` DATETIME

## Important Notes

- The PHP API uses session cookies to manage auth.
- File contents are stored on disk inside `storage/<user_id>/`.
- `api/index.php` currently expects the app to be served from `/NodeNet/`.
  - If you deploy the app to a different folder or virtual host, update `assets/js/app.js` `API_BASE` accordingly.
- Uploaded folders preserve subfolder structure when using drag & drop or folder upload.
- The app is a demo-style lab network and should not be used as-is in a production environment.

## Customization

- Change the branding and UI text in `index.html`.
- Adjust styling in `assets/css/style.css`.
- Extend backend logic or permission rules in `api/Controllers/FileController.php`.
- Add additional API routes in `api/index.php`.

## Troubleshooting

- If database connection fails, verify MySQL is running and credentials in `api/Config/Database.php` are correct.
- If uploads fail, ensure PHP `file_uploads` is enabled and `storage/` is writable.
- If API requests return 404, confirm the app path and `API_BASE` are aligned.

---

Built as an offline lab network file manager experience for XAMPP-based PHP development.