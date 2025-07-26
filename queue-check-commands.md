# Queue Worker Commands for Cloudways

## 1. Check if supervisor is running queue workers:
```bash
sudo supervisorctl status
```

## 2. Check Laravel queue status:
```bash
php artisan queue:monitor
```

## 3. Check failed jobs:
```bash
php artisan queue:failed
```

## 4. Check if jobs are being processed:
```bash
php artisan queue:work --stop-when-empty
```

## 5. View Laravel logs for queue activity:
```bash
tail -f storage/logs/laravel.log | grep -i queue
```