# Hotel Stores Management System - File Index

## 📂 Project Structure Overview

```
stores/
├── app/
│   ├── bootstrap.php                   # App initialization & autoloader
│   ├── controllers/
│   │   └── Controller.php              # Base controller class
│   ├── models/
│   │   ├── Model.php                   # Base model with CRUD
│   │   ├── Product.php                 # Product queries
│   │   ├── Stock.php                   # Stock management
│   │   ├── Requisition.php             # Requisitions with approval
│   │   ├── GRN.php                     # Goods Received Notes
│   │   └── Store.php                   # Store, Department, Supplier
│   ├── views/
│   │   ├── layout-header.php           # Navigation & sidebar
│   │   └── layout-footer.php           # Footer & scripts
│   └── helpers/
│       ├── Database.php                # DB singleton
│       ├── Auth.php                    # Authentication
│       └── Helpers.php                 # Validation, Security, Response
├── config/
│   └── config.php                      # Database & app config
├── database/
│   └── schema.sql                      # Complete DB schema
├── public/
│   ├── css/
│   │   └── style.css                   # Main stylesheet
│   ├── js/
│   │   └── main.js                     # JavaScript utilities
│   └── img/                            # Images folder
├── login.php                           # Login page
├── dashboard.php                       # Main dashboard
├── logout.php                          # Logout handler
├── README.md                           # Project documentation
└── INSTALLATION.md                     # Setup guide
```

## 📄 File Descriptions

### Configuration Files

#### `config/config.php`
- Database connection parameters
- Site URL and app name
- Session timeout settings
- Upload directory configuration
- Error reporting and security headers

### Core Application Files

#### `app/bootstrap.php`
- Autoloader for classes
- Helper functions (isAuthenticated, getCurrentUser, etc.)
- CSRF token generation
- Currency and date formatting
- Flash message management

### Database Files

#### `database/schema.sql`
**Tables Created (19)**:
1. `roles` - User roles (Admin, Storekeeper, Kitchen, Bar, etc.)
2. `users` - User accounts with passwords (hashed)
3. `stores` - Physical store locations
4. `departments` - Hotel departments
5. `suppliers` - Supplier information
6. `categories` - Product categories
7. `products` - Inventory items
8. `stock` - Current stock levels per store
9. `grn` - Goods Received Notes (supplier deliveries)
10. `grn_items` - Individual items in GRN
11. `requisitions` - Department stock requests
12. `requisition_items` - Items in requisitions
13. `stock_issues` - Stock distribution to departments
14. `stock_issue_items` - Items issued
15. `stock_transactions` - Complete audit trail
16. `batch_tracking` - Batch numbers and expiry dates
17. `stock_adjustments` - Stock corrections
18. `adjustment_items` - Items in adjustments
19. `reorder_alerts` - Low stock alerts
20. `audit_log` - User activity tracking (bonus)

**Initial Data**:
- 8 roles configured
- 4 stores (Kitchen, Bar, Housekeeping, Maintenance)
- 5 departments
- 7 categories
- 12 sample products
- Demo user: admin / admin123

### Helper Classes

#### `app/helpers/Database.php`
**Singleton Database Connection**
- `getInstance()` - Get DB instance
- `query()` - Execute SELECT
- `prepare()` - Prepared statements (safe)
- `getLastInsertId()` - Get inserted ID
- `beginTransaction()`, `commit()`, `rollback()` - Transactions

#### `app/helpers/Auth.php`
**Authentication & User Management**
- `register()` - Create new user
- `login()` - Authenticate user
- `logout()` - Destroy session
- `isLoggedIn()` - Check auth status
- `checkSessionTimeout()` - Prevent session hijacking
- `updatePassword()` - Change password
- `hashPassword()`, `verifyPassword()` - Password utilities

#### `app/helpers/Helpers.php`
**Three utility classes**:

**Validation Class**:
- `required()` - Check non-empty
- `email()` - Email validation

### Account and Admin Pages

#### `pages/profile.php`
- View current user profile details

#### `pages/change-password.php`
- Update the logged-in user's password

#### `pages/settings/index.php`
- Read-only system settings overview
- Environment and security status
- Role permission matrix
- `minLength()`, `maxLength()` - String length
- `numeric()` - Number validation
- `date()` - Date format validation
- `unique()` - Database uniqueness
- `passes()`, `fails()` - Check validation result

**Security Class**:
- `sanitize()` - Clean XSS
- `generateCSRFToken()`, `verifyCSRFToken()` - CSRF protection
- `encrypt()`, `decrypt()` - Data encryption
- `generateToken()` - Random tokens

**Response Class**:
- `json()` - JSON HTTP response
- `success()` - Success message JSON
- `error()` - Error message JSON
- `validationError()` - Validation errors JSON

### Base Classes

#### `app/controllers/Controller.php`
**Base Controller for All Controllers**
- `checkAuth()` - Force authentication
- `loadCurrentUser()` - Load user data
- `getCurrentUser()` - Get current user
- `checkPermission()` - Role-based access
- `model()` - Load model
- `view()` - Load and render view
- `redirect()` - HTTP redirect
- `setFlash()` - Session flash message
- `logAudit()` - Record user actions

#### `app/models/Model.php`
**Base Model with ORM Methods**
- `findById()` - Get by ID
- `getAll()` - Get all with WHERE
- `insert()` - Create record
- `update()` - Update record
- `delete()` - Delete record
- `count()` - Count records
- `prepare()` - Prepared statements

### Models

#### `app/models/Product.php`
- `getWithCategory()` - Product with category info
- `getAllWithStock()` - All products with stock levels
- `search()` - Search by name/code
- `getLowStockItems()` - Items below reorder level
- `getOutOfStockItems()` - Zero inventory items

#### `app/models/Stock.php`
- `getWithDetails()` - Stock with product/store info
- `getByProductAndStore()` - Find stock record
- `getByStore()` - All stock in a store
- `updateQuantity()` - Increment/decrement
- `setQuantity()` - Set exact quantity
- `getTotalValue()` - Inventory valuation

#### `app/models/Requisition.php`
- `getWithDetails()` - Requisition with relationships
- `getItems()` - Line items in requisition
- `getPending()` - Pending approvals
- `getByDepartment()` - Department's requisitions
- `generateRequisitionNumber()` - Auto-generate REQ-YYYYMMDD-XXXX
- `addItem()` - Add product to requisition
- `approve()` - Approve requisition
- `reject()` - Reject with reason

#### `app/models/GRN.php`
- `getWithDetails()` - GRN with supplier/store info
- `getItems()` - Items received
- `getBySupplier()` - Supplier's deliveries
- `getByStore()` - Store's received orders
- `generateGRNNumber()` - Auto-generate GRN-YYYYMMDD-XXXX
- `addItem()` - Add line item
- `verify()` - Verify GRN + Update Stock + Record Transaction

#### `app/models/Store.php`
**Store Class**:
- `getWithResponsible()` - Store with manager info
- `getActive()` - Active stores only
- `getStockSummary()` - Stock counts, values

**Department Class**:
- `getWithHead()` - Department with head person
- `getActive()` - Active departments
- `getConsumptionSummary()` - Department usage analysis

**Supplier Class**:
- `getActive()` - Active suppliers
- `getPerformance()` - Delivery metrics
- `search()` - Supplier search

### Views

#### `app/views/layout-header.php`
**HTML Structure**:
- Sidebar navigation with sections
- Topbar with user profile dropdown
- Flash message display
- All pages inherit this header
- Navigation features:
  - Dashboard
  - Inventory (Products, Stock Levels)
  - Transactions (GRN, Requisitions, Issues, Adjustments)
  - Configuration (Stores, Suppliers, Departments, Categories)
  - Reporting (Reports, Audit Log)
  - Admin (Users)

#### `app/views/layout-footer.php`
- Close main-content div
- Footer copyright
- Script includes:
  - Bootstrap JS
  - Chart.js (for reports)
  - Main application JS
  - Optional module-specific JS

### Frontend Files

#### `public/css/style.css`
**CSS Structure** (500+ lines):
- CSS variables for consistency
- Sidebar styling
  - Navigation active states
  - Hover effects
  - Section titles
- Topbar (header)
  - User profile dropdown
  - Responsive layout
- Main content area
  - Page headers
  - Padding and spacing
- Card components
  - Hover effects
  - Header styling
- Stat cards (dashboard)
  - Color variants (success, danger, warning)
  - Icon integration
- Tables
  - Header styling
  - Hover rows
  - Responsive
- Button styles
  - Primary, Success, Danger variants
  - Outline variations
  - Sizes (sm, lg)
- Form elements
  - Focus states
  - Validation states
  - Select styling
- Alert components
  - Different severity colors
  - Dismissible option
- Badges
  - Various colors
  - Small/compact size
- Responsive design
  - Breakpoints for mobile/tablet
  - Sidebar collapse
- Utility classes
  - Margins, padding
  - Display, flexbox
  - Text colors and weights

#### `public/js/main.js`
**JavaScript Functions** (400+ lines):
- Event initialization
  - Tooltips
  - Popovers
  - Form handlers
- AJAX form submission
- Delete confirmations
- Alert messaging system
- Currency and date formatting
- API request helper
- Form validation
- Debounce utility
- CSV export
- Print functionality
- Dynamic row cloning
- Row removal with validation

### Pages

#### `login.php`
- Login form with HTML/CSS styling
- POST handler for authentication
- Bootstrap integration
- Demo credentials display
- Redirect to dashboard on success
- Error message display

#### `dashboard.php`
**Dashboard Features**:
- Statistics cards:
  - Total Products
  - Stock Value (calculated)
  - Low Stock Items
  - Out of Stock
- Recent GRNs table
- Pending Requisitions table
- Recent Stock Transactions table
- Data pulled from various tables
- Links to detail pages

#### `logout.php`
- Session destruction
- Redirect to login

### Documentation

#### `README.md`
**Comprehensive guide covering**:
- Project overview
- Feature list with descriptions
- Tech stack
- Project structure
- Database schema overview
- User roles and permissions
- Key workflows:
  - Stock Receipt (GRN)
  - Stock Requisition
  - Stock Adjustment
- System features breakdown
- Default stores and categories
- Smart alerts list
- Data backup instructions
- Troubleshooting guide

#### `INSTALLATION.md`
**Setup instructions including**:
- Prerequisites
- Database setup steps
- Configuration update
- Access instructions
- Checklist format showing:
  - Completed Phase 1 (Foundation)
  - Phase 2-5 (TODO items)
  - All files created
  - Next steps
  - Setup instructions

## 🔄 Key Relationships

```
users
├── roles (many-to-one)
└── stores (responsible)

products
├── categories (many-to-one)
└── stock (one-to-many)

stock
├── products (many-to-one)
├── stores (many-to-one)
└── batch_tracking (one-to-many)

grn
├── suppliers (many-to-one)
├── stores (many-to-one)
├── users (received_by)
└── grn_items (one-to-many)

requisitions
├── departments (many-to-one)
├── stores (many-to-one)
├── users (requested_by, approved_by)
└── requisition_items (one-to-many)

stock_issues
├── requisitions (many-to-one)
├── stores (many-to-one)
├── departments (many-to-one)
└── stock_issue_items (one-to-many)
```

## 📊 Data Flow Examples

### GRN to Stock Update
1. Supplier delivers goods
2. Storekeeper records GRN
3. Admin/Manager verifies
4. System automatically:
   - Updates stock quantities
   - Records batch info
   - Creates transaction log
   - Triggers reorder alerts if needed

### Requisition to Issue
1. Department creates requisition
2. Stores multiple items
3. Manager approves
4. Storekeeper creates issue
5. System reduces stock

## 🎯 Using These Files

### For Quick Start
1. Import `database/schema.sql`
2. Update `config/config.php`
3. Access `login.php`
4. View `dashboard.php`

### For Development
1. Study `Model.php` for ORM pattern
2. Extend `Controller.php` for new modules
3. Create controllers in `app/controllers/`
4. Create views in `app/views/`
5. Use provided models as reference

### For Integration
1. Use `Auth` helper for authentication
2. Use `Validation` helper for input
3. Use `Response` class for JSON APIs
4. Extend models for custom queries

---

**Version**: 1.0.0 (Foundation)  
**Created**: April 15, 2026  
**Status**: Ready for Module Development
