# Visitor Management System - Enhanced Features Implementation Guide

## Overview
This document outlines all the enhancements made to the Visitor Management System to support Excel imports, member dashboard functionality, and event-specific dashboards.

## Database Schema Changes

### 1. Updated tbl_visitors Table
Added three new columns to support enhanced functionality:

```sql
ALTER TABLE tbl_visitors 
ADD COLUMN IF NOT EXISTS added_by INT NULL COMMENT 'User ID who added this visitor',
ADD COLUMN IF NOT EXISTS relation VARCHAR(100) NULL COMMENT 'Relation to existing guest',
ADD COLUMN IF NOT EXISTS visitor_type ENUM('regular', 'spot_entry', 'additional_member') DEFAULT 'regular',
ADD CONSTRAINT fk_visitors_added_by FOREIGN KEY (added_by) REFERENCES tbl_members(id) ON DELETE SET NULL;
```

**Column Descriptions:**
- `added_by`: Tracks which member/admin added the visitor
- `relation`: Stores relationship information for additional members (e.g., "Family member", "Friend")
- `visitor_type`: Categorizes visitors as regular, spot_entry, or additional_member

### 2. Excel Imports Table (Already Exists)
The `tbl_excel_imports` table stores uploaded Excel data before importing to main dashboard:

```sql
CREATE TABLE IF NOT EXISTS tbl_excel_imports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(150) NULL,
    mobile VARCHAR(30) NULL,
    address TEXT NULL,
    department VARCHAR(100) NULL,
    gender ENUM('Male', 'Female', 'Other') NULL,
    year_of_graduation YEAR NULL,
    excel_file_name VARCHAR(255) NOT NULL,
    import_status ENUM('pending', 'imported', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES tbl_events(event_id) ON DELETE CASCADE
);
```

## New Features Implemented

### 1. Excel Import Functionality

#### Files Created/Modified:
- **excel-import.php**: Upload Excel files with visitor data
- **search-excel.php**: Search and import individual records from uploaded Excel
- **add_spot_entry.php**: Backend for spot entry additions
- **add_additional_member.php**: Backend for additional member additions

#### Excel Format Requirements:
Your Excel file should have these columns in order:
1. Name (Required)
2. Email
3. Mobile
4. Address
5. Department
6. Gender (Male/Female/Other)
7. Year of Graduation

#### Workflow:
1. **Upload**: Admin uploads Excel file via `excel-import.php`
2. **Storage**: Data stored in `tbl_excel_imports` with status 'pending'
3. **Search**: Use `search-excel.php` to find specific guests
4. **Import**: Click "Import" button to move selected guest to main `tbl_visitors` table
5. **Tracking**: System records in_time and marks guest as imported

### 2. Member Dashboard Enhancements

#### Key Features:
**For Subordinates/Members (not outsiders):**

1. **Spot Entry Registration**
   - Manually register walk-in guests
   - Records in `tbl_visitors` with `visitor_type = 'spot_entry'`
   - Tracks which member added the entry via `added_by` field

2. **Additional Members**
   - Add family/friends of existing guests
   - Records relationship information
   - Tracks member who added them

#### Access Control:
- Members must be logged in (session check)
- Only members in `tbl_members` table can access
- Activity is tracked by member ID

### 3. Event-Specific Dashboards

#### Example Implementation: Nostalgia Dashboard

**File**: `nostalgia_dashboard.php`

**Key Features:**
- Isolates data by event_id
- Shows only visitors for specific event
- Event-specific statistics
- Separate goodies distribution tracking
- Event-specific notes and actions

**Query Pattern:**
```php
$event_query = mysqli_query($conn, "SELECT event_id FROM tbl_events WHERE event_name = 'Nostalgia'");
$visitors = mysqli_query($conn, "SELECT * FROM tbl_visitors WHERE event_id = $event_id");
```

#### Creating New Event Dashboards:

To create a dashboard for another event (e.g., "Alumni Talks"):

1. Copy `nostalgia_dashboard.php`
2. Rename to `alumni_talks_dashboard.php`
3. Update event name:
   ```php
   $event_name = 'Alumni Talks';
   ```
4. Update page title and branding
5. Add link in sidebar navigation

## File Structure

### New Files Created:
```
/add_spot_entry.php          # Backend for spot entries
/add_additional_member.php   # Backend for additional members
/excel-import.php            # Excel upload interface
/search-excel.php            # Excel search and import
/nostalgia_dashboard.php     # Event-specific dashboard example
/update_schema.sql           # Database migration script
/IMPLEMENTATION_GUIDE.md     # This documentation
```

### Modified Files:
```
/setup.sql                   # Updated tbl_visitors schema
/member_dashboard.php        # Added spot entry & additional member forms
```

## Usage Instructions

### For Admins

#### 1. Excel Import Process:
1. Navigate to "Manage Visitors" → "Import Excel"
2. Select target event from dropdown
3. Upload Excel file (.xlsx, .xls, .csv)
4. System imports data to tbl_excel_imports
5. Go to "Search Excel" to review and import

#### 2. Search and Import:
1. Navigate to "Search Excel"
2. Filter by event and/or search term
3. Review guest details
4. Click "Import" to add to main dashboard
5. Guest appears in main visitors list with in_time set

### For Members (Subordinates)

#### 1. Spot Entry:
1. Log in to Member Dashboard
2. Scroll to "Spot Entry" section
3. Select event and enter guest details
4. Submit - guest added immediately

#### 2. Additional Members:
1. Log in to Member Dashboard
2. Scroll to "Additional Members" section
3. Select event and enter details
4. Optionally add relationship (e.g., "Brother of John Doe")
5. Submit - member added with relation tracked

### Event-Specific Dashboards

#### Accessing:
- Navigate via sidebar: "Event Dashboards" section
- Each event has dedicated dashboard
- Shows only data for that specific event

#### Creating New Event Dashboard:
1. Ensure event exists in `tbl_events` table
2. Copy template from `nostalgia_dashboard.php`
3. Update event name variable
4. Customize branding/title as needed
5. Add to navigation sidebar

## Database Migration

### For Existing Databases:
Run the migration script to add new columns:

```bash
mysql -u root -p vms_db < update_schema.sql
```

Or execute manually:
```sql
ALTER TABLE tbl_visitors 
ADD COLUMN added_by INT NULL COMMENT 'User ID who added this visitor',
ADD COLUMN relation VARCHAR(100) NULL COMMENT 'Relation to existing guest',
ADD COLUMN visitor_type ENUM('regular', 'spot_entry', 'additional_member') DEFAULT 'regular',
ADD CONSTRAINT fk_visitors_added_by FOREIGN KEY (added_by) REFERENCES tbl_members(id) ON DELETE SET NULL;
```

### For Fresh Installations:
Simply run `setup.sql` - all enhancements are included.

## Security Considerations

1. **Session Validation**: All pages check for valid user session
2. **SQL Injection Prevention**: Prepared statements used throughout
3. **Input Sanitization**: All user inputs are sanitized
4. **Role-Based Access**: Members can only access member dashboard features
5. **File Upload Validation**: Only specific Excel formats allowed
6. **XSS Prevention**: htmlspecialchars() used for all output

## Testing Checklist

### Excel Import:
- [ ] Upload valid Excel file
- [ ] Upload invalid file format (should reject)
- [ ] Search imported data
- [ ] Import single record
- [ ] Verify in_time is set
- [ ] Check import_status changes to 'imported'

### Member Dashboard:
- [ ] Add spot entry as member
- [ ] Add additional member with relation
- [ ] Verify added_by field is set
- [ ] Check visitor_type is correct
- [ ] Ensure member cannot access admin features

### Event Dashboards:
- [ ] View Nostalgia dashboard
- [ ] Verify data isolation (only Nostalgia events shown)
- [ ] Check statistics are event-specific
- [ ] Test filtering and search

## Troubleshooting

### Issue: Excel Import Not Working
**Solution**: 
- Verify Composer dependencies installed: `composer install`
- Check PhpSpreadsheet library is present
- Verify file permissions on upload directory

### Issue: Member Dashboard Forms Not Submitting
**Solution**:
- Check browser console for JavaScript errors
- Verify PHP files (add_spot_entry.php, add_additional_member.php) exist
- Confirm database columns exist

### Issue: Event Dashboard Shows No Data
**Solution**:
- Verify event exists in tbl_events table
- Check event_id in tbl_visitors matches
- Ensure SQL queries use correct event_id

## Future Enhancements

Potential improvements:
1. Bulk import from Excel (import all at once)
2. Export event-specific data to Excel
3. Email notifications for members
4. Mobile-responsive member dashboard
5. Dashboard analytics and reports
6. QR code check-in for visitors
7. Real-time dashboard updates

## Support

For issues or questions:
1. Check this guide first
2. Review database logs
3. Check PHP error logs
4. Verify all files are present
5. Ensure database migrations ran successfully

---

**Version**: 1.0  
**Last Updated**: 2025-10-02  
**Author**: VMS Development Team