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

1. **Clone the repository**:
   ```bash
   git clone https://github.com/YOUR_USERNAME/fashion_store.git
   ```
2. **Setup Database**:
   - Create a database named `fashion_store`.
   - Import `schema.sql` into your MySQL instance.
3. **Configure Connection**:
   - Update `config/db.php` with your database credentials.
4. **Deploy**:
   - Place the folder in your web server root (e.g., `htdocs` for XAMPP).
   - Navigate to `http://localhost/fashion_store`.

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
