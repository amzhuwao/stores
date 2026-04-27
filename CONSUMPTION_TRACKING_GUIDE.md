# Consumption Tracking System - Implementation Summary

## Overview
A complete consumption tracking system has been implemented to monitor when items issued to departments are consumed or returned. This allows admins to:
- Track which items have been consumed vs. returned
- Monitor inventory accountability at the department level
- Manage who has permission to log consumption
- Generate consumption reports and analytics

---

## Database Changes

### New Tables

#### 1. **consumption_permissions**
Manages which users can log consumption for specific departments.

```sql
- permission_id (PK)
- user_id (FK to users)
- department_id (FK to departments)
- assigned_by (FK to users)
- can_log_consumption (boolean)
- can_view_reports (boolean)
- assigned_at (timestamp)
- revoked_at (nullable datetime)
- status (ENUM: active, revoked)
```

#### 2. **consumption_records**
Stores each consumption log entry.

```sql
- consumption_id (PK)
- issue_item_id (FK to stock_issue_items)
- quantity_consumed (int)
- logged_by (FK to users)
- log_date (datetime)
- notes (text)
- created_at (timestamp)
```

### Modified Tables

#### 1. **stock_issue_items**
Added consumption tracking columns:
- `quantity_consumed` (INT, DEFAULT 0) - Tracks consumed quantity
- `quantity_returned` (INT, DEFAULT 0) - Tracks returned/unused quantity
- `updated_at` (TIMESTAMP) - Track when record was last updated

---

## New Models

### Consumption Model (`app/models/Consumption.php`)

Provides methods for:

#### Permission Management
- `getPermissionsForUser($userId)` - Get all departments user can log consumption for
- `hasPermission($userId, $departmentId)` - Check if user has permission
- `assignPermission($userId, $departmentId, $assignedByUserId)` - Assign permission
- `revokePermission($userId, $departmentId)` - Revoke permission
- `getPermissionedUsersForDepartment($departmentId)` - Get all users with permission

#### Consumption Logging
- `logConsumption($issueItemId, $quantityConsumed, $loggedByUserId, $notes)` - Log consumption
- `logReturn($issueItemId, $quantityReturned, $loggedByUserId, $reason, $notes)` - Log return/damage
- `getConsumptionRecordsForIssue($issueId)` - Get all consumption logs for an issue
- `getConsumptionRecordsForDepartment($departmentId, $startDate, $endDate)` - Get department consumption history

#### Reporting
- `getItemConsumptionSummary($issueItemId)` - Get consumption summary for an item

---

## Controller Updates

### StockIssueController New Methods

- `getIssueItemsForConsumption($issueId, $userId)` - Get items ready for consumption logging
- `getConsumableIssuesForDepartment($departmentId, $includeCompleted)` - Get pending issues
- `getPendingConsumptionItems($departmentId)` - Get all items needing consumption logged
- `getConsumptionSummary($departmentId, $startDate, $endDate)` - Get consumption analytics

---

## New Pages

### 1. **pages/stock-issues/consumption.php**
Main dashboard for consumption logging.
- Lists all pending issues for the user's department
- Shows quantity issued, consumed, returned, and pending
- Links to log consumption or view history

**Access**: Users with `stock-issues.view` permission or consumption logging permission

### 2. **pages/stock-issues/log-consumption.php**
Form to log consumption and returns.
- Table of items in an issue with remaining quantities
- Modal dialog to log consumption for each item
- Option to simultaneously log returns/damage
- Displays consumption history
- Real-time summary of completion

**Features**:
- Input validation (prevents over-consumption)
- Optional notes field
- Track both consumption AND returns in one action
- Success/error messaging

### 3. **pages/stock-issues/consumption-history.php**
View consumption history for an issue.
- Complete audit trail of all consumption entries
- Shows who logged consumption and when
- Item-by-item summary
- Overall completion percentage

**Access**: Users viewing an issue they have access to

### 4. **pages/settings/consumption-permissions.php**
Admin panel to manage consumption logging permissions.
- Tabbed interface (one tab per department)
- View all users with permission for each department
- Assign new permissions to users
- Revoke permissions with confirmation
- System overview stats

**Access**: Users with `settings.manage` permission

---

## Workflow

### For Department Staff (Consumption Logging)

1. **Navigate to Consumption Logging**
   - Go to: Transactions → Consumption Logging
   - See all pending issues requiring consumption tracking

2. **Log Consumption**
   - Click "Log Consumption" button on an issue
   - View all items in the issue with their details
   - For each item: enter consumed quantity and optional notes
   - Optionally log returns/damage in same action
   - Submit to record the transaction

3. **View History**
   - Click "History" to see all past consumption entries
   - View detailed consumption records with timestamps

### For Admins (Permission Management)

1. **Manage Permissions**
   - Go to: Settings → Consumption Permissions
   - Select department (tabbed interface)
   - View all users with permission for that department
   - Add new users by selecting from dropdown
   - Revoke permissions as needed

2. **Audit Trail**
   - All consumption entries are logged in `consumption_records` table
   - `stock_transactions` table captures the actual inventory change
   - Can be audited and reported on

---

## Data Flow

```
Department Issues Item (stock_issue_items)
    ↓
User logs consumption (consumption_records table)
    ↓
stock_issue_items updated (quantity_consumed incremented)
    ↓
stock_transactions created (transaction_type = 'consumption')
    ↓
Inventory decremented from department store
```

---

## Security & Permissions

### Permission Levels

1. **Department Staff**
   - Need explicit `consumption_permissions` assignment
   - Can only log for their assigned department(s)
   - Cannot access other departments

2. **Store Keeper**
   - Has `stock-issues.view` permission
   - Can log consumption for any department
   - Can view consumption reports

3. **Admin**
   - Can assign/revoke permissions
   - Can view all consumption records
   - Can manage permissions for all departments

### Access Control

- Consumption logging pages check for permission
- Users redirected if unauthorized
- Audit logs capture all actions
- HTTP 403 returned for unauthorized access

---

## Database Queries

### Create the Tables
```sql
-- Run the updated schema.sql file which includes:
-- - Modified stock_issue_items table
-- - New consumption_permissions table
-- - New consumption_records table
```

### Example: Get Pending Consumption Items
```php
$controller = new StockIssueController();
$items = $controller->getPendingConsumptionItems($departmentId);
// Returns items still needing consumption logged
```

### Example: Log Consumption
```php
$consumption = new Consumption();
$result = $consumption->logConsumption(
    $issueItemId,
    $quantityConsumed,
    $userId,
    'Optional notes'
);
// Creates consumption_records entry and stock_transactions entry
```

---

## Reports & Analytics

The system enables reporting on:
- Consumption by department
- Consumption by product
- Return/damage analysis
- Usage trends over time
- Staff consumption logging activity
- Inventory accountability

---

## Testing Checklist

- [ ] Database schema updated successfully
- [ ] Consumption model loads without errors
- [ ] Admin can assign permissions to users
- [ ] Department staff sees assigned department in consumption page
- [ ] User can log consumption for their department
- [ ] Cannot consume more than issued quantity
- [ ] Can simultaneously log returns/damage
- [ ] Consumption records saved to database
- [ ] Stock transactions created for consumption
- [ ] History page shows all consumption entries
- [ ] Permissions can be revoked
- [ ] Unauthorized users cannot access pages
- [ ] Sidebar navigation links work

---

## File Changes Summary

### New Files Created
1. `app/models/Consumption.php` - Consumption model class
2. `pages/stock-issues/consumption.php` - Main consumption dashboard
3. `pages/stock-issues/log-consumption.php` - Consumption logging form
4. `pages/stock-issues/consumption-history.php` - Consumption history view
5. `pages/settings/consumption-permissions.php` - Permission management

### Modified Files
1. `database/schema.sql` - Updated with new tables and columns
2. `app/controllers/StockIssueController.php` - Added consumption methods
3. `app/views/partials/sidebar-nav.php` - Added navigation links

---

## Next Steps (Optional Enhancements)

1. **Consumption Reports**
   - Create department consumption reports
   - Monthly consumption summaries
   - Product-level analytics

2. **Batch Operations**
   - Log consumption for multiple items at once
   - Bulk permission assignment

3. **Notifications**
   - Alert when consumption logging not done
   - Department reminders for pending items

4. **Export Functionality**
   - Export consumption history to CSV/PDF
   - Audit report exports

5. **Mobile Support**
   - Mobile-friendly consumption logging form
   - Barcode scanning for items

---

## Support

For issues or questions:
- Check database logs in `stock_transactions` table
- Review consumption_records for audit trail
- Check user permissions in consumption_permissions table
- Review error messages in consumption logging form

---

**Implementation Date**: April 27, 2026
**Version**: 1.0
