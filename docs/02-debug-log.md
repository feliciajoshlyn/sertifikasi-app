# Debugging & Error Resolution Log (J.620100.025.02)

## 1. Debugging Tools Used
- **Application Logs:** `storage/logs/laravel.log`
- **CLI & Test Runner:** `php artisan test`, `php artisan migrate`, `dd()`
- **Database Inspection:** TablePlus & MySQL CLI (`brew services list`)

## 2. Error & Fix Log

| Bug ID | Phase | Error Code / Message | File / Location | Root Cause Analysis | Fix Applied | Status |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **ERR-001** | Setup | `ERROR 2002 (HY000): Can't connect to local MySQL server through socket '/tmp/mysql.sock'` | MySQL Service / `.env` | MySQL background daemon was still starting up when the database creation command ran. | Checked `brew services list` until `mysql` showed `started`, then re-ran `CREATE DATABASE sertifikasi_db;`. | **RESOLVED** |
| **ERR-002** | D-Day Build | `SQLSTATE[23000]: Integrity constraint violation: 1451 Cannot delete or update a parent row` | `ItemService.php:48` | Tried to delete a Category that still had linked Items (`ON DELETE RESTRICT` triggered). | Added a check in `ItemService::deleteCategory()` to throw a clean validation message before attempting deletion. | **RESOLVED** |
| **ERR-003** | D-Day Testing | `TypeError: Return value must be of type array, Collection returned` | `ReportService.php:31` | `declare(strict_types=1);` rejected an Eloquent `Collection` where an `array` return type was declared. | Converted the query result using `->toArray()` before returning. | **RESOLVED** |
| **ERR-004** | D-Day Build | `Class "App\Http\Controllers\Item" not found` | `routes/web.php:12` | Typo in controller namespace. | Changed `\App\Http\Controllers\Item` to `\App\Http\Controllers\ItemController`. | **RESOLVED** |
| **ERR-005** | D-Day Build | `Call to undefined method App\Models\Transaction::item()` | `ItemService.php:38` | The `Transaction` model was missing the required Eloquent relationship definition. | Added `public function item(): BelongsTo { ... }` to `app/Models/Transaction.php`. | **RESOLVED** |