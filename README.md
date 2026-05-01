# 🌐 NodeNet – Offline Lab Network File Manager

A sleek, browser-based **offline network file manager** built with **PHP, MySQL, and vanilla JavaScript**. NodeNet simulates a decentralized lab-style file system with user-based storage, public sharing, and a desktop-like UI experience.

---

## ✨ Features

| Category           | Features                                           |
| ------------------ | -------------------------------------------------- |
| 🔐 Authentication  | User registration, login, logout, session handling |
| 📁 File Management | Create, rename, move, delete files & folders       |
| 🧭 Navigation      | Nested folder structure with intuitive browsing    |
| ✍️ Editor          | Built-in text editor for file content              |
| 📤 Uploads         | Drag & drop files & full folders                   |
| 📥 Downloads       | Download files or folders as ZIP                   |
| 🌍 Sharing         | Public & read-only file sharing across users       |
| 👥 Network         | Live public file browser + active node counter     |
| 🖼️ Preview        | Image file preview support                         |

---

## 🛠️ Tech Stack

| Layer       | Technology         |
| ----------- | ------------------ |
| Backend     | PHP 7+ / 8+        |
| Database    | MySQL / MariaDB    |
| DB Access   | PDO                |
| Frontend    | Vanilla JavaScript |
| UI          | HTML + CSS         |
| Environment | XAMPP              |

---

## 📂 Project Structure

```
NodeNet/
│
├── index.html                # Main UI
├── assets/
│   ├── css/style.css        # Styling
│   └── js/app.js            # Frontend logic
│
├── api/
│   ├── index.php            # API router
│   ├── Config/
│   │   └── Database.php     # DB config
│   ├── Controllers/         # Controllers (Auth, Files, Network)
│   └── Models/              # Data models
│
├── storage/                 # User file storage
├── database.sql             # DB schema
└── README.md
```

---

## ⚙️ Setup Guide

### 1️⃣ Install Environment

* Install **XAMPP**
* Start **Apache** and **MySQL**

### 2️⃣ Add Project

```bash
htdocs/NodeNet
```

### 3️⃣ Setup Database

* Open phpMyAdmin or MySQL CLI
* Import:

```sql
database.sql
```

### 4️⃣ Configure Database

Edit:

```
api/Config/Database.php
```

```php
$host = 'localhost';
$user = 'root';
$pass = '';
$name = 'nodenet';
```

### 5️⃣ Storage Permissions

Ensure:

```
storage/
```

✔ Exists
✔ Writable

### 6️⃣ Run App

Open in browser:

```
http://localhost/NodeNet/index.html
```

---

## 🚀 Usage

### 🔐 Authentication

* Register new account
* Login to access your file system
* Logout securely

### 📁 File Manager

* Create files/folders
* Drag & drop uploads
* Rename / Move / Delete items
* Search files instantly
* Open and edit text files
* Download as ZIP

### 🌍 Network Sharing

| Permission    | Description            |
| ------------- | ---------------------- |
| 🔒 Private    | Only visible to you    |
| 🌐 Public     | Anyone can view & edit |
| 👁️ Read-only | Anyone can view only   |

Public files appear in the **Network Explorer**.

---

## 🔌 API Endpoints

| Endpoint         | Method | Description    |
| ---------------- | ------ | -------------- |
| `auth/register`  | POST   | Register user  |
| `auth/login`     | POST   | Login          |
| `auth/logout`    | GET    | Logout         |
| `auth/session`   | GET    | Session status |
| `nodes/active`   | GET    | Active users   |
| `files/list`     | POST   | List directory |
| `files/public`   | GET    | Public files   |
| `files/create`   | POST   | Create node    |
| `files/read`     | POST   | Read file      |
| `files/save`     | POST   | Save file      |
| `files/delete`   | POST   | Delete node    |
| `files/rename`   | POST   | Rename         |
| `files/move`     | POST   | Move           |
| `files/upload`   | POST   | Upload         |
| `files/download` | GET    | Download ZIP   |

---

## 🗄️ Database Schema

### 👤 `users`

| Field         | Type     |
| ------------- | -------- |
| id            | INT (PK) |
| username      | VARCHAR  |
| password_hash | VARCHAR  |
| last_seen     | DATETIME |
| created_at    | DATETIME |

### 📁 `files`

| Field      | Type                         |
| ---------- | ---------------------------- |
| id         | INT (PK)                     |
| user_id    | INT                          |
| parent_id  | INT                          |
| name       | VARCHAR                      |
| type       | file / folder                |
| path       | VARCHAR                      |
| permission | private / public / read_only |
| size       | INT                          |
| created_at | DATETIME                     |
| updated_at | DATETIME                     |

---

## ⚠️ Important Notes

* 🔐 Uses **session-based authentication**

* 💾 Files stored in:

  ```
  storage/<user_id>/
  ```

* ⚙️ Default base path:

  ```
  /NodeNet/
  ```

  Update `API_BASE` in:

  ```
  assets/js/app.js
  ```

  if changed

* 🧪 Designed for **offline lab use only**

* ❌ Not production-ready

---

## 🎨 Customization

* ✏️ Edit UI → `index.html`
* 🎨 Modify styles → `assets/css/style.css`
* ⚙️ Extend backend → `api/Controllers/FileController.php`
* ➕ Add APIs → `api/index.php`

---

## 🧯 Troubleshooting

| Issue               | Solution                            |
| ------------------- | ----------------------------------- |
| DB connection fails | Check MySQL + credentials           |
| Upload not working  | Enable `file_uploads` & permissions |
| API 404 errors      | Verify `API_BASE` path              |
| Storage errors      | Ensure folder is writable           |

---

## 💡 Future Improvements

* 🔐 Role-based permissions
* 📡 Real-time updates (WebSockets)
* 📊 Storage usage dashboard
* 🔎 Advanced search filters
* 🌙 Dark mode UI

---

## 📜 License

This project is built for **educational and lab experimentation purposes**.

---

## 🙌 Author

Built with 💻 + ☕ for learning and experimentation.

---

⭐ If you like this project, consider giving it a star!
