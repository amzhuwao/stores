# Hotel Stores Management System - Installation and Status Checklist

## Phase 1: Foundation

Status: Completed

- Project structure and bootstrap
- Database schema and seed data
- Base controller/model architecture
- Authentication and helper utilities
- Shared responsive layout

## Phase 2: Core Modules

Status: Largely Completed

Completed:

- Products module
- Stock levels module
- GRN module
- Requisition module
- Stock issues module
- Suppliers module
- Stores module
- Departments module
- Categories module
- Users module (admin)
- Settings page (admin)

Pending in this phase:

- Stock adjustment create and approval workflow UI

## Phase 3: Reporting and Compliance

Status: Mostly Completed

Completed:

- Reports dashboard
- Audit log viewer
- CSV export for audit logs
- Print/PDF fallback for key report/document views

Potential enhancements:

- Additional report templates
- Excel export format

## Phase 4: Security and Access Control

Status: Completed baseline, enhancements pending

Completed:

- Route-level permission enforcement
- Role-based menu visibility
- CSRF token usage in forms
- Session timeout handling
- Environment-based debug controls

Pending:

- Forgot/reset password flow
- Optional 2FA

## Phase 5: Quality and Performance

Status: Pending

- Unit tests
- Integration tests
- Pagination for very large tables
- Query/index review and optimization pass

## Installation Steps

### 1) Database

1. Open phpMyAdmin at http://localhost/phpmyadmin
2. Create database named stores
3. Import database/schema.sql

### 2) Configuration

Verify config/config.php:

- DB_HOST = localhost
- DB_USER = root
- DB_PASS =
- DB_NAME = stores

### 3) Services

Start Apache and MySQL from XAMPP Control Panel.

### 4) Login Test

- URL: http://localhost/stores/login.php
- Username: admin
- Password: admin123

## Post-Install Validation Checklist

- Login works
- Dashboard loads
- Products page loads
- GRN page loads
- Requisition page loads
- Stock issues page loads
- Reports page loads
- Users page loads for admin
- Settings page loads for admin

## Current Deployment Notes

- Database name in runtime config is stores
- Access control is enforced server-side by route mapping in app/bootstrap.php
- Audit log visibility is restricted to higher-level roles

---

System Version: 1.0.0  
Status: Operational Core Complete  
Last Updated: April 15, 2026
