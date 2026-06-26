# HypeThread - Modern Fashion E-Commerce Platform

HypeThread is a full-stack fashion e-commerce platform built with PHP and MySQL, featuring a custom "Anthropic Editorial" design system. The platform supports multiple user roles, robust inventory management, and a comprehensive profitability analysis tool for business owners.

## ✨ Features

- **Multi-Role System**: Dedicated modules for Buyers, Managers, Admins, and Owners.
- **Product Profitability**: Advanced tool for owners to set cost prices and monitor margins.
- **Media Library**: Integrated image management with database-stored BLOBs for high portability.
- **Dynamic Dashboards**: Real-time business insights and revenue reporting.
- **Voucher System**: Marketing tools for creating and distributing targeted vouchers.
- **Buyer Wishlist**: Save products, remove saved items, and move available items to the cart.
- **Professional Catalogue Sorting**: Sort by newest, popularity, rating, price, or product name.
- **Personalized Discovery**: Related products and session-based recently viewed products.
- **Improved Product Decisions**: Dynamic stock indicators, detailed size guides, and review summaries.

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
   - Open `http://localhost/fashion_store/setup_db.php` in your browser.
   - The installer creates/updates the **`fashion_store`** database and loads the demo data automatically.
   - Existing databases are upgraded safely through `database/migrations/001_compatibility.sql`.
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
