# Google Sheets Integration - Implementation Summary

## What Has Been Implemented

A complete Google Sheets integration system for your Zalando orders has been successfully implemented. This system automatically syncs all Zalando orders to a Google Sheet every 15 minutes.

## Key Features

1. **Automatic Synchronization**
   - Runs every 15 minutes via Laravel scheduler
   - Fetches orders from the last 30 days
   - Syncs ALL order items (not just returns)
   - Updates existing rows when order status changes

2. **Duplicate Prevention**
   - Intelligent tracking system prevents duplicate entries
   - Each order item is uniquely identified by order_number + order_item_id
   - Database-backed tracking in `synced_order_items` table

3. **Status Updates**
   - Automatically detects when an order is returned or cancelled
   - Updates the corresponding row in Google Sheets
   - Tracks status changes: Sold → zurück → canceled

4. **Admin Settings Page**
   - Easy-to-use interface for connecting Google account
   - Select any Google Sheet from your account
   - Test connection functionality
   - View sync status

## Files Created

### Controllers
- `app/Http/Controllers/GoogleAuthController.php` - Handles Google OAuth authentication
- `app/Http/Controllers/SettingsController.php` - Manages settings page and sheet configuration

### Traits
- `app/Http/Traits/GoogleSheetsAPI.php` - All Google Sheets API operations

### Jobs
- `app/Jobs/SyncOrdersToGoogleSheet.php` - Main sync job that runs every 15 minutes

### Migrations
- `database/migrations/2025_01_15_000001_create_google_settings_table.php`
- `database/migrations/2025_01_15_000002_create_synced_order_items_table.php`

### Views
- `resources/views/settings/index.blade.php` - Settings page UI

### Routes Added
```php
// Settings
GET  /settings
POST /settings/save-spreadsheet
GET  /settings/test-connection

// Google OAuth
GET  /google/redirect
GET  /google/callback
GET  /google/disconnect
```

### Modified Files
- `routes/web.php` - Added new routes
- `app/Console/Kernel.php` - Added scheduled job
- `resources/views/layouts/app.blade.php` - Added Settings menu link
- `.env` - Added Google OAuth credentials placeholders

## Database Tables

### google_settings
Stores Google OAuth tokens and sheet configuration:
```sql
- id
- access_token (text)
- refresh_token (text)
- token_type (varchar)
- expires_in (int)
- token_created_at (timestamp)
- spreadsheet_id (varchar)
- sheet_name (varchar, default: 'database')
- is_active (boolean)
- created_at, updated_at
```

### synced_order_items
Tracks synced items to prevent duplicates:
```sql
- id
- order_number (varchar, indexed)
- order_item_id (varchar, indexed)
- sku (varchar)
- status (varchar)
- sheet_row_number (int)
- synced_at (timestamp)
- updated_at (timestamp)
- UNIQUE KEY (order_number, order_item_id)
```

## Google Sheet Structure

Your Google Sheet must have this exact structure:

| Column | Header | Description |
|--------|--------|-------------|
| A | Month | Month abbreviation (Jan, Feb, Mar...) |
| B | Date | Date in dd/mm/yyyy format |
| C | Name | Customer full name |
| D | Order Number | Zalando order number |
| E | Country | Country name (Österreich, Deutschland, etc.) |
| F | Product | Product SKU (e.g., serena-white-38) |
| G | Price | Price with currency symbol (€ 499) |
| H | Status | Order status (Sold, zurück, canceled) |
| I | Reason for Return | Return/cancellation reason |
| J | Main Product | Main product name extracted from SKU (e.g., serena) |

## Setup Checklist

- [x] ✅ Install Google API Client (`composer require google/apiclient`)
- [x] ✅ Create database migrations
- [x] ✅ Run migrations (`php artisan migrate`)
- [x] ✅ Add routes
- [x] ✅ Configure scheduler
- [ ] ⚠️ **TODO: Get Google OAuth credentials from Google Cloud Console**
- [ ] ⚠️ **TODO: Add credentials to .env file**
- [ ] ⚠️ **TODO: Create/prepare Google Sheet with correct structure**
- [ ] ⚠️ **TODO: Connect Google account via Settings page**
- [ ] ⚠️ **TODO: Select Google Sheet**
- [ ] ⚠️ **TODO: Test connection**

## Next Steps (What You Need to Do)

### 1. Get Google OAuth Credentials

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project (or use existing)
3. Enable APIs:
   - Google Sheets API
   - Google Drive API
4. Create OAuth 2.0 credentials:
   - Type: Web application
   - Authorized redirect URI: `https://irvalda.test/google/callback`
5. Copy Client ID and Client Secret

### 2. Update .env File

```env
GOOGLE_CLIENT_ID=your_client_id_here
GOOGLE_CLIENT_SECRET=your_client_secret_here
```

### 3. Prepare Google Sheet

1. Create a new Google Sheet or use existing
2. Create a tab named "database"
3. Add headers in Row 1:
   ```
   Month | Date | Name | Order Number | Country | Product | Price | Status | Reason for Return | Main Product
   ```

### 4. Connect via Settings Page

1. Login to your Laravel app
2. Click "Settings" in navigation
3. Click "Connect Google Account"
4. Authorize the app
5. Select your Google Sheet
6. Enter sheet tab name (default: "database")
7. Click "Save Sheet Configuration"
8. Click "Test Connection" to verify

### 5. Enable Cron Job

Add to crontab:
```bash
* * * * * cd /home/asad-rafique/Sites/irvalda && php artisan schedule:run >> /dev/null 2>&1
```

Or for production:
```bash
* * * * * cd /path/to/production && php artisan schedule:run >> /dev/null 2>&1
```

### 6. Monitor Sync

Check logs:
```bash
tail -f storage/logs/google-sheets-sync.log
tail -f storage/logs/laravel.log
```

## How It Works

```
┌──────────────────────────────────────────────────────────────┐
│                    SYNC FLOW (Every 15 Minutes)              │
└──────────────────────────────────────────────────────────────┘

1. Scheduler triggers SyncOrdersToGoogleSheet job
   ↓
2. Fetch orders from Zalando API (last 30 days)
   ↓
3. For each order:
   ├─ Get detailed order information
   ├─ Extract all order items
   └─ For each item:
      ├─ Check if already synced (query synced_order_items table)
      ├─ If NEW:
      │  ├─ Append row to Google Sheet
      │  └─ Record in synced_order_items table
      └─ If EXISTS:
         ├─ Check if status changed
         └─ If changed:
            ├─ Update row in Google Sheet
            └─ Update status in synced_order_items table
```

## Example Data Flow

**Order from Zalando:**
```json
{
  "order_number": "10603062224223",
  "order_date": "2025-01-02T10:30:00Z",
  "customer": {
    "first_name": "Sanela",
    "last_name": "Rippitsch"
  },
  "items": [{
    "sku": "serena-white-38",
    "price": 49900,
    "currency": "EUR",
    "status": "returned",
    "cancellation_reason": "Artikel gefällt nicht"
  }]
}
```

**Transformed to Google Sheet Row:**
```
Jan | 02/01/2025 | Sanela Rippitsch | 10603062224223 | Österreich | serena-white-38 | € 499 | zurück | Artikel gefällt nicht | serena
```

## Testing

### Manual Sync Test
```bash
php artisan tinker
>>> dispatch(new \App\Jobs\SyncOrdersToGoogleSheet);
>>> exit
```

### Check Scheduled Jobs
```bash
php artisan schedule:list
```

### View Logs
```bash
# Sync logs
tail -f storage/logs/google-sheets-sync.log

# Application logs
tail -f storage/logs/laravel.log

# Queue logs
tail -f storage/logs/queue-worker.log
```

## Troubleshooting

### Issue: "Google account not connected"
**Solution:** Go to Settings → Connect Google Account

### Issue: "Token expired"
**Solution:** System auto-refreshes tokens. If it fails, reconnect Google account.

### Issue: "Permission denied"
**Solution:**
- Ensure Google account has access to the sheet
- Check APIs are enabled in Google Cloud Console
- Verify OAuth credentials are correct

### Issue: "Sheet not found"
**Solution:**
- Check sheet tab name is correct (default: "database")
- Verify spreadsheet ID is correct
- Use Test Connection to diagnose

### Issue: Cron not running
**Solution:**
```bash
# Check crontab
crontab -l

# Check if scheduler sees the job
php artisan schedule:list

# Run scheduler manually
php artisan schedule:run
```

## Security Considerations

- OAuth tokens are stored encrypted in database
- Access tokens auto-refresh using refresh_token
- Settings page requires authentication
- Never commit `.env` file to version control
- Google API credentials should be kept secret

## Performance

- Sync runs every 15 minutes (configurable)
- Fetches only last 30 days of orders
- Uses database-backed duplicate detection (very fast)
- Batch operations minimize API calls
- `withoutOverlapping()` prevents concurrent runs

## Support & Documentation

- Full setup guide: [GOOGLE_SHEETS_SETUP.md](GOOGLE_SHEETS_SETUP.md)
- Implementation details: This file
- Laravel logs: `storage/logs/`
- Google Sheets API: https://developers.google.com/sheets/api
- Google OAuth: https://developers.google.com/identity/protocols/oauth2

## Summary

✅ **What's Done:**
- All code implemented
- Database tables created
- Routes configured
- Scheduler configured
- Navigation menu updated
- Documentation written

⚠️ **What You Need to Do:**
1. Get Google OAuth credentials
2. Update .env file
3. Prepare Google Sheet
4. Connect via Settings page
5. Enable cron job
6. Monitor first sync

**Estimated Time to Complete Setup:** 15-20 minutes

Once you complete the setup steps above, the system will automatically sync orders every 15 minutes!
