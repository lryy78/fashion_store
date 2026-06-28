# HypeThread - Modern Fashion E-Commerce Platform

HypeThread is a full-stack fashion e-commerce platform built with PHP and MySQL, featuring a custom "Anthropic Editorial" design system. The platform supports multiple user roles, robust inventory management, and a comprehensive profitability analysis tool for business owners.

## ✨ Features

- **Multi-Role System**: Dedicated modules for Buyers, Managers, Admins, and Owners.
- **Product Profitability**: Advanced tool for owners to set cost prices and monitor margins.
- **Media Library**: Integrated image management with database-stored BLOBs for high portability.
- **Dynamic Dashboards**: Real-time business insights and revenue reporting.
- **Voucher System**: Marketing tools for creating and distributing targeted vouchers.

## 🛠️ Tech Stack

- **Backend**: PHP 8.x
- **Database**: MySQL / MariaDB
- **Frontend**: Vanilla CSS (Anthropic Editorial Design System), Javascript
- **Icons/Graphics**: Custom SVG & Unsplash integration

## 🚀 Getting Started

1. **Clone the repository using VS Code**:
   - Open **VS Code**.
   - Press `Ctrl + Shift + P` and type `Git: Clone`.
   - Paste the repository URL: `https://github.com/lryy78/fashion_store.git`.
   - Select **`C:\xampp\htdocs\`** as the destination folder.
2. **Setup XAMPP**:
   - Open the **XAMPP Control Panel**.
   - Start **Apache** and **MySQL**.
3. **Setup Database**:
   - You can set up the database instantly by visiting: **[http://127.0.0.1/fashion_store/setup_db.php](http://127.0.0.1/fashion_store/setup_db.php)** – this script will create the `fashion_store` database and import all seed data.
   - Or do it manually:
     - Open **phpMyAdmin** at `http://localhost/phpmyadmin` (or use the MySQL command line).
     - Create a new database named **`fashion_store`** (if it does not already exist):
       ```sql
       CREATE DATABASE IF NOT EXISTS fashion_store;
       ```
     - Import the seed data:
       * Using phpMyAdmin: select the `fashion_store` database → **Import** → choose `database/data.sql` and click **Go**.
       * Or from the command line:
         ```bash
         mysql -u root -p fashion_store < c:/xampp/htdocs/fashion_store/database/data.sql
         ```
4. **Run the Application**:
   - Open your browser and navigate to: **`http://localhost/fashion_store/`**.

## 🔑 Demo Credentials

| Role | Username | Password |
| :--- | :--- | :--- |
| **Owner** | `owner_demo` | `password123` |
| **Buyer** | `buyer_demo` | `password123` |
| **Manager** | `manager_demo` | `password123` |
| **Admin** | `admin_demo` | `password123` |

## 📐 Design Philosophy

HypeThread follows the **Anthropic Editorial** system:
- **Canvas**: Cream surfaces (`#faf9f5`)
- **Accent**: Coral/Sienna (`#cc785c`)
- **Ink**: Dark Navy/Charcoal (`#141413`)
- **Typography**: Cormorant Garamond for display, Inter for body.

---

*Developed for the HypeThread Retail Modernization project.*
