# Hotel Stores Management System

Hotel inventory management platform for multi-store operations, approvals, stock control, cost visibility, and audit compliance.

## Overview

The system supports day-to-day stores operations across departments such as Kitchen, Bar, Housekeeping, and Maintenance.

Core capabilities include:

- Multi-store inventory management
- Product, category, supplier, store, and department master data management
- GRN (goods received note) workflow with verification
- Requisition and approval workflow
- Stock issue workflow against approved requisitions
- Stock adjustments register
- Batch and expiry tracking data model
- Reporting dashboard and exports
- Audit log and compliance history
- Role/permission-based access control
- Manual and scheduled database backups with restore controls

## Tech Stack

- Frontend: HTML5, Bootstrap 5, JavaScript
- Backend: PHP 7.4+
- Database: MySQL/MariaDB
- Runtime: Apache on XAMPP

## Current Module Status

Implemented and usable:

- Authentication and session handling
- Dashboard
- Products (list/create/edit/status)
- Stock levels
- GRN (list/create/view/verify)
- Requisitions (list/create/view/approve/reject)
- Stock issues (list/create/view)
- Stock adjustments (list)
- Suppliers (list/create/edit/status)
- Stores (list/create/edit)
- Departments (list/create/edit)
- Categories (list/create/edit)
- Reports dashboard
- Audit log with CSV export
- Users management (admin)
- Settings page (admin)
- Backup settings (admin): manual backup, restore, and automated scheduling

Partially implemented or pending enhancement:

- Full stock adjustment creation/approval workflow UI
- Password reset flow (forgot password)
- Automated tests (unit/integration)
- Performance pass (pagination/caching/query tuning)
- Optional 2FA and advanced integrations

## Quick Setup

1. Open phpMyAdmin at http://localhost/phpmyadmin
2. Create database named stores
3. Import database/schema.sql
4. Confirm config/config.php points to DB_NAME = stores
5. Start Apache and MySQL in XAMPP
6. Open http://localhost/stores/login.php

Demo login:

- Username: admin
- Password: admin123

## Important URLs

- Login: http://localhost/stores/login.php
- Dashboard: http://localhost/stores/dashboard.php
- Audit Log: http://localhost/stores/pages/audit/index.php
- Users Management: http://localhost/stores/pages/users/index.php
- Settings: http://localhost/stores/pages/settings/index.php
- Backup Settings: http://localhost/stores/pages/settings/backup.php

## Automated Backup Scheduler

Admins can configure backup schedules in Backup Settings, trigger manual backups on demand, run dry-run validation for restore scripts, restore from stored or uploaded SQL backups, and roll back using linked safety backups.

To run automated backups, schedule this script every 5-15 minutes:

- `php C:\xampp\htdocs\stores\scripts\run_scheduled_backup.php`

Optional HTTP mode is also supported using the generated token shown on the Backup Settings page.

## Roles and Access

- Admin: full access, user administration, settings
- Manager: operational and reporting access including audit log
- Storekeeper: inventory operations without audit access
- Accounts: reporting and selected operational visibility
- Department roles: requisition-focused access

Permissions are enforced by route mapping and server-side checks in app/bootstrap.php.

## Database Notes

- Schema is in database/schema.sql
- Seed data includes roles, stores, departments, categories, products, and demo users
- Primary database name expected by runtime config: stores

## Documentation Index

- README.md: project overview and status
- QUICK_START.md: fast setup and run checks
- INSTALLATION.md: detailed install checklist and roadmap
- FILE_INDEX.md: file-by-file reference

## Next Recommended Milestones

1. Build adjustment create/approval UI
2. Add password reset flow
3. Add pagination to heavy tables (audit, reports, stock)
4. Add automated tests for controller/service-critical paths
5. Add deployment profile and production hardening checklist

---

Last Updated: April 15, 2026  
System Version: 1.0.0
