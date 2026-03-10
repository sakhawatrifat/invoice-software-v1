# Queue cron for Hostinger cPanel (no Supervisor)

On Hostinger shared hosting you cannot run a long-lived `queue:work` process. Use **one cron job** so Laravel’s scheduler runs every minute and processes the queue (and other scheduled tasks).

## 1. Add a single cron job in cPanel

1. Log in to **Hostinger cPanel**.
2. Open **Cron Jobs** (under “Advanced” or “Software”).
3. Create a new cron:
   - **Frequency:** Every minute  
     - Choose “Every minute (* * * * *)” or set: `* * * * *`
   - **Command:** (replace with your actual path and PHP if needed)

```bash
cd /home/u995873212/domains/invoice.b05719531.com/public_html && php artisan schedule:run >> /dev/null 2>&1
```

**Replace:**

- `YOUR_CPANEL_USERNAME` – your cPanel username (e.g. `u123456789`).
- `YOUR_LARAVEL_FOLDER` – the folder that **contains** `artisan` (same level as `app`, `config`, `routes`).  
  Examples:
  - If your app is in `public_html/invoice-generator`, use:  
    `public_html/invoice-generator` or `domains/yourdomain.com/invoice-generator` (check in File Manager).
  - So the full path might be:  
    `/home/u123456789/public_html/invoice-generator`

**If Hostinger requires a specific PHP version**, use the full path to that PHP binary, for example:

```bash
cd /home/YOUR_CPANEL_USERNAME/YOUR_LARAVEL_FOLDER && /usr/local/bin/ea-php81 artisan schedule:run >> /dev/null 2>&1
```

(Check in cPanel → “Select PHP Version” or “MultiPHP Manager” for the exact path; version might be `ea-php82`, etc.)

## 2. What this does

- Every minute, cron runs `php artisan schedule:run`.
- The scheduler runs:
  - **Sticky note reminders** (existing).
  - **Queue processing**: `queue:work --stop-when-empty --max-time=300` (processes all pending jobs, then exits; max 5 minutes per run).

So when a user clicks “Check All Upcoming Flight”, the job is pushed to the queue and will be processed within about a minute by the next cron run. No Supervisor or long-running worker is required.

## 3. Optional: run queue less often

To run the scheduler (and thus the queue) every 5 minutes instead of every minute, set the cron to:

```bash
*/5 * * * *
```

and keep the same command. Jobs may then take up to 5 minutes to be processed after the button is clicked.
