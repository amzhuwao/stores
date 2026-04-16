# Quick Start Guide

## 5-Minute Setup

### Step 1: Database

1. Open http://localhost/phpmyadmin
2. Create database named stores
3. Import database/schema.sql into stores

### Step 2: Configuration

Open config/config.php and verify:

- DB_HOST = localhost
- DB_USER = root
- DB_PASS =
- DB_NAME = stores

### Step 3: Services

Start Apache and MySQL in XAMPP.

### Step 4: Login

Open http://localhost/stores/login.php

Demo credentials:

- Username: admin
- Password: admin123

## Smoke Test Checklist

After login, quickly test these routes:

- Dashboard: /dashboard.php
- Products: /pages/products/index.php
- Stock Levels: /pages/stock/view.php
- GRN: /pages/grn/index.php
- Requisitions: /pages/requisition/index.php
- Stock Issues: /pages/stock-issues/index.php
- Adjustments: /pages/adjustments/index.php
- Suppliers: /pages/suppliers/index.php
- Stores: /pages/stores/index.php
- Departments: /pages/departments/index.php
- Categories: /pages/categories/index.php
- Reports: /pages/reports/index.php

Admin-only:

- Users: /pages/users/index.php
- Settings: /pages/settings/index.php

Higher-level access only:

- Audit Log: /pages/audit/index.php

## Current Build Status

Implemented:

- Authentication and session timeout handling
- Role and route-based authorization
- Full CRUD for products, suppliers, stores, departments, categories, users
- Inventory transactions (GRN, requisition, stock issue)
- Reporting dashboard and export support
- Audit log with improved readability and CSV export
- Admin settings overview page

Still pending:

- Stock adjustment create/approval workflow UI
- Password reset flow
- Automated tests and performance tuning pass

## Common Issues

### Login fails

1. Ensure MySQL is running
2. Confirm schema was imported into stores
3. Retry admin/admin123

### Database connection error

1. Verify DB settings in config/config.php
2. Confirm database name is stores

### Page not found

1. Ensure URL starts with /stores/
2. Confirm Apache document root includes xampp/htdocs

---

System: Hotel Stores Management System v1.0.0  
Status: Operational Core Complete  
Last Updated: April 15, 2026
