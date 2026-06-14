# Database Setup

This folder is the source of truth for the HypeThread database.

- `schema.sql`: complete schema for a fresh database.
- `migrations/001_compatibility.sql`: upgrades older copies without deleting data.
- `seed.sql`: repeatable demo accounts, products, images, vouchers, settings, and FAQs.
- `legacy/`: older SQL scripts retained for reference; the installer does not use them.

## XAMPP setup

Start Apache and MySQL, then open:

`http://127.0.0.1/fashion_store/setup_db.php`

The installer runs the schema, migration, and seed files in order.

## Demo accounts

All use password `password123`:

- `buyer_demo`
- `manager_demo`
- `admin_demo`
- `owner_demo`

These credentials are for local development only.
