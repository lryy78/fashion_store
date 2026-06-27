# Database Setup

This folder is the source of truth for the HypeThread database.

- `schema.sql`: complete schema for a fresh database.
- `migrations/001_compatibility.sql`: upgrades older copies without deleting data.
- `migrations/002_catalog_images.sql`: installs the local 17-item image catalogue.
- `seed.sql`: repeatable demo accounts, products, images, vouchers, settings, and FAQs.
- `fashion_store_full.sql`: full snapshot of the shared development database, including current sample data.
- `legacy/`: older SQL scripts retained for reference; the installer does not use them.

## XAMPP setup

Start Apache and MySQL, then open:

`http://127.0.0.1/fashion_store/setup_db.php`

The installer runs the schema, migration, and seed files in order.

## Restore the shared database snapshot

To see the same sample products, stock, orders, vouchers, and accounts as the contributor who exported it, import `fashion_store_full.sql` using phpMyAdmin or DBeaver. The snapshot drops and recreates the `fashion_store` database, so export any local data you need before importing it.

With XAMPP's command line client:

```powershell
Get-Content .\database\fashion_store_full.sql | C:\xampp\mysql\bin\mysql.exe -u root
```

## Demo accounts

All use password `password123`:

- `buyer_demo`
- `manager_demo`
- `admin_demo`
- `owner_demo`

These credentials are for local development only.
