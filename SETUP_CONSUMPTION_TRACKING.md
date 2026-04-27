# Consumption Tracking - Database Setup Guide

## Issue Fixed
The consumption tracking system was showing a fatal error because the required database tables had not been created yet.

**Error:** 
```
Fatal error: Uncaught Error: Call to a member function bind_param() on bool
```

**Root Cause:** The `consumption_permissions` and `consumption_records` tables don't exist in your database.

---

## How to Fix

### Step 1: Backup Your Database
Always backup your database before running schema updates!

```bash
# In phpMyAdmin or via command line:
mysqldump -u root -p stores > stores_backup_$(date +%Y%m%d_%H%M%S).sql
```

### Step 2: Run the Database Schema Update

**Option A: Using phpMyAdmin**
1. Open phpMyAdmin
2. Select your database (`stores`)
3. Go to **Import** tab
4. Select the file: `database/schema.sql`
5. Click **Import**

**Option B: Using MySQL Command Line**
```bash
mysql -u root -p stores < database/schema.sql
```

**Option C: Using a MySQL GUI**
1. Open your MySQL client
2. Connect to your `stores` database
3. Open `database/schema.sql`
4. Execute the file

### Step 3: Verify the Installation
- Log in to your application
- Go to **Settings → Consumption Permissions**
- If no error appears, the setup is complete! ✓

---

## What Gets Created

When you run the schema.sql update, three new database elements are added:

### 1. **consumption_permissions Table**
Tracks which users can log consumption for which departments:
- `permission_id` - Unique identifier
- `user_id` - Link to users table
- `department_id` - Link to departments table
- `assigned_by` - User who granted permission
- `status` - active/revoked
- Timestamps

### 2. **consumption_records Table**
Stores each consumption log entry:
- `consumption_id` - Unique identifier
- `issue_item_id` - Link to stock_issue_items
- `quantity_consumed` - How much was consumed
- `logged_by` - User who logged it
- `log_date` - When it was logged
- `notes` - Optional notes

### 3. **Updated stock_issue_items Table**
Modified to track consumption:
- `quantity_consumed` - New column (INT)
- `quantity_returned` - New column (INT)
- `updated_at` - New column (TIMESTAMP)

---

## Testing the Feature

After setup, test the consumption tracking:

1. **As an Admin:**
   - Go to **Settings → Consumption Permissions**
   - Select a department
   - Assign a user permission to log consumption

2. **As Department Staff:**
   - Go to **Transactions → Consumption Logging**
   - Click "Log Consumption" on a pending issue
   - Enter consumption quantity and submit
   - Verify it was recorded

3. **Verify Data:**
   - Check the consumption history
   - View consumption records in database:
     ```sql
     SELECT * FROM consumption_records;
     SELECT * FROM consumption_permissions;
     ```

---

## Troubleshooting

### Still Getting Errors?

**Error: "Table doesn't exist"**
- Verify the schema.sql was imported successfully
- Check in phpMyAdmin if the tables are there
- Look in the database error logs

**Error: "Permission denied"**
- Make sure you're logged in as an admin
- Check user role has `settings.manage` permission

**Error: "No users appear in dropdown"**
- Add users first: Admin panel → Users
- Make sure they have active status

---

## Database Queries to Verify Setup

Run these in phpMyAdmin SQL tab:

```sql
-- Check if tables exist
SHOW TABLES LIKE 'consumption_%';

-- View all permissions
SELECT cp.*, u.full_name, d.dept_name 
FROM consumption_permissions cp
JOIN users u ON cp.user_id = u.user_id
JOIN departments d ON cp.department_id = d.dept_id;

-- View all consumption records
SELECT cr.*, p.product_name, u.full_name 
FROM consumption_records cr
JOIN stock_issue_items sii ON cr.issue_item_id = sii.issue_item_id
JOIN products p ON sii.product_id = p.product_id
JOIN users u ON cr.logged_by = u.user_id
LIMIT 10;
```

---

## What's Next?

After successful setup:

1. ✅ Go to Settings → Consumption Permissions
2. ✅ Assign department staff who can log consumption
3. ✅ Staff members can now log consumption via Transactions → Consumption Logging
4. ✅ All consumption is tracked and audited automatically

---

## Support

If you encounter issues:

1. Check the error message on screen
2. Review the database error logs
3. Verify all database tables were created
4. Check that your user has proper permissions
5. Review the CONSUMPTION_TRACKING_GUIDE.md for more details

---

**Last Updated:** April 27, 2026
