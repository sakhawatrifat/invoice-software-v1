# Queue & scheduler cron (cPanel / Hostinger)

Use this when Supervisor is not available. Add one cron job in cPanel → **Cron Jobs** (e.g. every minute).

**Note:** Before using the commands below, check (1) the **PHP version** for this domain/subdomain in cPanel and use the matching path (e.g. `php82` → `php83` if needed), and (2) your **server username** — replace `u995873212` in the paths if yours is different.

---

**Production (no log):**

```bash
/opt/alt/php82/usr/bin/php /home/u995873212/domains/invoice.b05719531.com/public_html/artisan schedule:run >> /dev/null 2>&1
```

**Testing (writes to log):**

```bash
/opt/alt/php82/usr/bin/php /home/u995873212/domains/invoice.b05719531.com/public_html/artisan schedule:run >> /home/u995873212/domains/invoice.b05719531.com/public_html/storage/logs/scheduler.log 2>&1
```

Check `storage/logs/scheduler.log` to confirm runs.

