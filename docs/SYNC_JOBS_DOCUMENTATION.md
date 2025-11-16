# Google Sheets Sync Jobs Documentation

## Overview
Yeh system do jobs use karta hai orders ko Google Sheets mein sync karne ke liye:

1. **SyncAllOrdersToGoogleSheet** - One-time job (1 saal ke orders)
2. **SyncOrdersToGoogleSheet** - Incremental job (har 15 min)

## Features

### 1. Duplicate Prevention
- Database level: `unique_order_item` index on `(order_number, order_item_id)`
- Ensures Excel sheet mein duplicate entries nahi aayengi
- Insert aur update dono mein duplicate prevention hai

### 2. Product Title Display
- `shopify_products` table se actual product title fetch hoti hai
- SKU ke basis pe product match hota hai (`zalando_sizes` JSON column mein)
- Google Sheet mein product title show hoti hai (SKU extract ki jagah)

### 3. Failsafe Mechanism
- Agar incremental job fail ho jaye, to next run mein 15 minutes ka buffer add hota hai
- Isse koi bhi order ki update skip nahi hogi
- `sync_tracking` table mein last sync time track hota hai

## Jobs Detail

### Job 1: SyncAllOrdersToGoogleSheet (One-Time)
**Purpose**: Pehli baar saal bhar ke orders sync karne ke liye

**How it works**:
- `created_after` filter use karta hai
- Last 365 days ke orders fetch karta hai
- Sirf ek baar chalana hai
- Agar already chala hua hai to dobara nahi chalega

**Run kaise karein**:
```bash
php artisan tinker
\App\Jobs\SyncAllOrdersToGoogleSheet::dispatch();
exit;
```

### Job 2: SyncOrdersToGoogleSheet (Incremental - Every 15 min)
**Purpose**: Updated orders ko sync karne ke liye

**How it works**:
- `last_updated_after` filter use karta hai
- Sirf wo orders fetch karta hai jo update hue hain
- Response chota hota hai, job tezi se chalti hai
- Har 15 minutes mein chalni chahiye

**Run kaise karein (Manually)**:
```bash
php artisan tinker
\App\Jobs\SyncOrdersToGoogleSheet::dispatch();
exit;
```

**Schedule mein add karein** (app/Console/Kernel.php):
```php
protected function schedule(Schedule $schedule)
{
    // Har 15 minutes mein incremental sync
    $schedule->job(new \App\Jobs\SyncOrdersToGoogleSheet())
             ->everyFifteenMinutes();
}
```

## Failsafe Mechanism Detail

### Normal Flow:
1. Job chalta hai at 10:00 AM
2. `last_updated_after` = last successful sync time (e.g., 9:45 AM)
3. Orders fetch hote hain jo 9:45 AM ke baad update hue
4. Success: `last_successful_sync` = 10:00 AM save hota hai

### Failure Flow:
1. Job chalta hai at 10:00 AM
2. `last_updated_after` = 9:45 AM
3. **Job fails** (network error, API timeout, etc.)
4. `last_successful_sync` = 9:45 AM hi rahega (no update)
5. `last_sync_time` = 10:00 AM save hoga (failed attempt)

### Next Run After Failure:
1. Job chalta hai at 10:15 AM
2. System detect karta hai: last sync failed
3. **15 min buffer add hota hai**: `last_updated_after` = 9:45 AM - 15 min = **9:30 AM**
4. Ab 9:30 AM se 10:15 AM tak ke orders fetch honge
5. Koi order skip nahi hoga!

## Database Tables

### sync_tracking
Tracks last sync time and status:

| Column | Description |
|--------|-------------|
| sync_type | 'full' or 'incremental' |
| last_sync_time | Last time job tried to run |
| last_successful_sync | Last time job successfully completed |
| is_running | Currently running status |
| error_message | Error if job failed |

### synced_order_items
Stores synced order items:

| Column | Description |
|--------|-------------|
| order_number | Order number (unique with order_item_id) |
| order_item_id | Order item ID (unique with order_number) |
| sku | Product SKU |
| product_title | **NEW**: Product title from shopify_products |
| status | Order status (Sold, zurück, canceled) |
| sheet_row_number | Row number in Google Sheet |

## Testing

### Step 1: Clear existing data
```bash
php artisan tinker
DB::table('synced_order_items')->truncate();
DB::table('sync_tracking')->truncate();
exit;
```

### Step 2: Run one-time full sync
```bash
php artisan tinker
\App\Jobs\SyncAllOrdersToGoogleSheet::dispatch();
exit;

# Process the job
php artisan queue:work --once
```

### Step 3: Verify data
```bash
php artisan tinker
echo "Synced items: " . DB::table('synced_order_items')->count() . "\n";
echo "Sample items:\n";
DB::table('synced_order_items')->limit(5)->get()->each(function($item) {
    echo "SKU: {$item->sku} | Title: {$item->product_title}\n";
});
exit;
```

### Step 4: Test incremental sync
```bash
# Wait 15 minutes or manually update some orders in Zalando
# Then run:
php artisan tinker
\App\Jobs\SyncOrdersToGoogleSheet::dispatch();
exit;

php artisan queue:work --once
```

## Important Notes

1. **Duplicates**: System automatically prevents duplicates via database constraint
2. **Order Skip Prevention**: Failsafe mechanism ensures no orders are skipped
3. **Product Titles**: Automatically fetched from shopify_products table
4. **One-time Job**: SyncAllOrdersToGoogleSheet should run only once
5. **Recurring Job**: SyncOrdersToGoogleSheet should run every 15 minutes

## Monitoring

Check sync status:
```bash
php artisan tinker
DB::table('sync_tracking')->get();
exit;
```

Check for errors:
```bash
tail -f storage/logs/laravel.log | grep "SyncOrders"
```
