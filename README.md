## Database Setup

-   clone project
-   create .env
-   create a database and confirgure to .env
-   php artisan migrate:fresh --seed

## Admin Credentials for Digital Arena BD

-   **Login URL:** `root/login`
-   **Username:** `admin@gmail.com`
-   **Password:** `12345678`

## Cache Clear

-   Visit: `root_url/clear-cache` to clear the cache.

## For HEIC to JPG Package Permission: server_root/public_html/vendor/maestroerror/php-heic-to-jpg/bin/heicToJpg.exe -> Give 744 Permission to this file

## In server custom cron job: /bin/sh /home/u995873212/domains/invoice.b05719531.com/public_html/send-reminder-email.sh

=================================

In Server:
`/opt/alt/php82/usr/bin/php /home/u995873212/domains/invoice.b05719531.com/public_html/artisan queue:work database --queue=whatsapp-marketing --sleep=0 --timeout=120 --tries=3 --stop-when-empty > /dev/null 2>&1`

In Local:
php artisan queue:work database --queue=whatsapp-marketing --sleep=1 --timeout=120 --tries=3

=================================

`/opt/alt/php82/usr/bin/php /home/u995873212/domains/invoice.b05719531.com/public_html/artisan schedule:run >> /dev/null 2>&1`

**Testing (writes to log):**
`/opt/alt/php82/usr/bin/php /home/u995873212/domains/invoice.b05719531.com/public_html/artisan schedule:run >> /home/u995873212/domains/invoice.b05719531.com/public_html/storage/logs/scheduler.log 2>&1`

=================================

