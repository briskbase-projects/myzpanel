# Google Sheets Integration - Setup Guide

## Overview

This integration allows you to automatically sync Zalando orders to a Google Sheet. The system runs every 15 minutes and syncs all order items (not just returned orders) from the last 30 days.

## Features

- **Automatic Sync**: Runs every 15 minutes via cron job
- **Complete Order Data**: Syncs ALL order items, not just returns
- **Status Updates**: Automatically updates order status when items are returned or cancelled
- **No Duplicates**: Intelligent duplicate detection prevents the same order from being added twice
- **Multi-Country Support**: Works with all Zalando sales channels

## Installation & Setup

### Step 1: Google Cloud Console Configuration

1. **Create a Google Cloud Project**:
   - Go to [Google Cloud Console](https://console.cloud.google.com/)
   - Create a new project or select an existing one
   - Note your project ID

2. **Enable Google Sheets and Drive APIs**:
   - In the Google Cloud Console, go to "APIs & Services" > "Library"
   - Search for and enable:
     - **Google Sheets API**
     - **Google Drive API**

3. **Create OAuth 2.0 Credentials**:
   - Go to "APIs & Services" > "Credentials"
   - Click "Create Credentials" > "OAuth client ID"
   - Application type: **Web application**
   - Name: `Irvalda Zalando Integration`
   - Authorized redirect URIs:
     - `https://irvalda.test/google/callback` (for local testing)
     - `https://yourdomain.com/google/callback` (for production)
   - Click "Create"
   - Copy the **Client ID** and **Client Secret**

4. **Configure OAuth Consent Screen**:
   - Go to "APIs & Services" > "OAuth consent screen"
   - User Type: External (or Internal if using Google Workspace)
   - App name: `Irvalda Zalando Integration`
   - User support email: Your email
   - Developer contact information: Your email
   - Add scopes:
     - `.../auth/spreadsheets`
     - `.../auth/drive.readonly`
   - Add test users (if in testing mode)

### Step 2: Environment Configuration

Add the following to your `.env` file:

```env
# Google OAuth Credentials
GOOGLE_CLIENT_ID=your_google_client_id_here
GOOGLE_CLIENT_SECRET=your_google_client_secret_here
```

**Replace the placeholders with your actual credentials from Step 1.**

### Step 3: Create Google Sheet

1. **Create a new Google Sheet** or use an existing one
2. **Create a tab named "database"** (or any other name you prefer)
3. **Add the following headers in Row 1**:

| A | B | C | D | E | F | G | H | I | J |
|---|---|---|---|---|---|---|---|---|
| Month | Date | Name | Order Number | Country | Product | Price | Status | Reason for Return | Main Product |

**Example Data Row:**
```
Jan | 02/01/2025 | Sanela Rippitsch | 10603062224223 | Österreich | serena-white-38 | € 499 | zurück | Artikel gefällt nicht | serena
```

### Step 4: Connect Google Account

1. **Login to your Laravel application**
2. **Navigate to Settings**:
   - Go to `https://yourdomain.com/settings`
3. **Connect Google Account**:
   - Click "Connect Google Account"
   - Sign in with your Google account
   - Grant permissions to access Google Sheets and Drive
4. **Select Google Sheet**:
   - After connecting, select your spreadsheet from the dropdown
   - Enter the sheet tab name (default: "database")
   - Click "Save Sheet Configuration"
5. **Test Connection**:
   - Click "Test Connection" to verify everything is working

### Step 5: Cron Configuration

The scheduler is already configured in `app/Console/Kernel.php`. You just need to ensure Laravel's scheduler is running.

**Add this cron entry to your server**:
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

**For Valet/Local Development**:
```bash
crontab -e
```
Add:
```
* * * * * cd /home/asad-rafique/Sites/irvalda && php artisan schedule:run >> /dev/null 2>&1
```

The Google Sheets sync job will automatically run every 15 minutes.

## How It Works

### Data Flow

1. **Every 15 minutes**, the `SyncOrdersToGoogleSheet` job runs
2. **Fetches orders** from Zalando API (last 30 days)
3. **For each order**:
   - Gets detailed order information
   - Extracts all order items
   - Maps data to Google Sheet format
   - Checks if item already exists (using order_number + order_item_id)
   - If new: Appends to sheet and records in `synced_order_items` table
   - If exists: Checks if status changed (sold → zurück → canceled)
   - If status changed: Updates the row in Google Sheet

### Status Mapping

| Zalando Status | Sheet Status |
|---------------|--------------|
| shipped | Sold |
| returned | zurück |
| cancelled | canceled |

### Column Mapping

| Google Sheet Column | Source Data |
|-------------------|-------------|
| Month | Order date (Jan, Feb, Mar...) |
| Date | Order date (dd/mm/yyyy) |
| Name | Customer first + last name |
| Order Number | Zalando order number |
| Country | Sales channel country |
| Product | SKU (e.g., serena-white-38) |
| Price | Item price with currency symbol |
| Status | Order line status |
| Reason for Return | Cancellation/return reason |
| Main Product | Extracted from SKU (e.g., serena) |

### Duplicate Prevention

The system uses the `synced_order_items` table to track which order items have been synced. Each order item is uniquely identified by:
- **order_number**: Zalando order number
- **order_item_id**: Unique item ID within the order

A composite unique index ensures no duplicates can be inserted.

## Database Tables

### google_settings

Stores Google OAuth tokens and spreadsheet configuration:
- `access_token`: Current access token
- `refresh_token`: Refresh token for regenerating access token
- `spreadsheet_id`: Selected Google Sheet ID
- `sheet_name`: Sheet tab name (default: "database")
- `is_active`: Whether this configuration is active

### synced_order_items

Tracks synced order items to prevent duplicates:
- `order_number`: Zalando order number
- `order_item_id`: Order item ID
- `sku`: Product SKU
- `status`: Current status (sold/zurück/canceled)
- `sheet_row_number`: Row number in Google Sheet
- `synced_at`: When first synced
- `updated_at`: When last updated

## Manual Sync

To manually trigger a sync (for testing):

```bash
php artisan tinker
```

Then run:
```php
dispatch(new \App\Jobs\SyncOrdersToGoogleSheet);
```

Or check the logs:
```bash
tail -f storage/logs/google-sheets-sync.log
```

## Troubleshooting

### Token Expired Error

If you see "token expired" errors:
1. The system automatically refreshes tokens using the refresh_token
2. If refresh fails, go to Settings and reconnect your Google account

### Permission Denied Error

Make sure:
1. The Google account you connected has access to the spreadsheet
2. The spreadsheet is not deleted or moved
3. The APIs are enabled in Google Cloud Console

### Duplicate Rows Appearing

This shouldn't happen due to the unique constraint, but if it does:
1. Check `synced_order_items` table for duplicates
2. Manually remove duplicate rows from Google Sheet
3. Update `sheet_row_number` in database if needed

### Cron Not Running

Check if Laravel scheduler is running:
```bash
php artisan schedule:list
```

Check cron logs:
```bash
grep CRON /var/log/syslog
```

### Testing Sync Job

Test the job manually:
```bash
php artisan tinker
>>> dispatch(new \App\Jobs\SyncOrdersToGoogleSheet);
>>> exit
```

Then check:
```bash
tail -f storage/logs/google-sheets-sync.log
tail -f storage/logs/laravel.log
```

## Files Created/Modified

### New Files
- `app/Http/Traits/GoogleSheetsAPI.php`
- `app/Http/Controllers/GoogleAuthController.php`
- `app/Http/Controllers/SettingsController.php`
- `app/Jobs/SyncOrdersToGoogleSheet.php`
- `database/migrations/2025_01_15_000001_create_google_settings_table.php`
- `database/migrations/2025_01_15_000002_create_synced_order_items_table.php`
- `resources/views/settings/index.blade.php`

### Modified Files
- `routes/web.php` (added Google OAuth and Settings routes)
- `app/Console/Kernel.php` (added scheduled job)
- `composer.json` (added google/apiclient dependency)

## Support

For issues or questions:
1. Check logs: `storage/logs/google-sheets-sync.log` and `storage/logs/laravel.log`
2. Verify Google Cloud Console configuration
3. Ensure database migrations ran successfully
4. Test connection from Settings page

## Security Notes

- OAuth tokens are stored encrypted in the database
- Access tokens are automatically refreshed
- Only authenticated admin users can access Settings
- Google API credentials should never be committed to version control
- Add `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET` to `.env.example` but not `.env`
