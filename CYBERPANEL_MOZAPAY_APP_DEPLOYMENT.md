# MozaPay CyberPanel Deployment Guide

This guide deploys the MozaPay Laravel backend, Next.js merchant frontend, and
mobile API on `https://mozapay.app` using CyberPanel and OpenLiteSpeed.

## Production layout

| Component | Public URL | Server directory |
| --- | --- | --- |
| Next.js frontend | `https://mozapay.app` | `/home/mozapay.app/frontend` |
| Laravel backend/admin | `https://mozapay.app/backend` | `/home/mozapay.app/backend` |
| Flutter API base | `https://mozapay.app/backend/api` | Laravel API routes |

Use normal OpenLiteSpeed when creating the website. Leave the optional premium
`OpenLiteSpeed + Apache Backend` checkbox unchecked.

## 1. Point the domain to the VPS

Create these DNS records at the DNS provider:

```text
A    @      YOUR_VPS_IP
A    www    YOUR_VPS_IP
```

Allow DNS time to propagate before issuing SSL.

## 2. Create the CyberPanel website

1. Go to **Websites > Create Website**.
2. Use `mozapay.app` as the domain.
3. Select PHP 8.2.
4. Do not select the premium Apache backend option.
5. Create the website.
6. Go to **SSL > Manage SSL** and issue SSL for `mozapay.app` and
   `www.mozapay.app`.

Only ports 80 and 443 should be public for website traffic. Do not expose Node
port 3000, MySQL port 3306, or Redis port 6379. Restrict SSH, CyberPanel port
8090, and OpenLiteSpeed administration port 7080 to trusted IP addresses.

## 3. Upload the applications

Extract the Laravel package so these files exist:

```text
/home/mozapay.app/backend/artisan
/home/mozapay.app/backend/composer.json
```

Extract the Next.js package so these files exist:

```text
/home/mozapay.app/frontend/package.json
/home/mozapay.app/frontend/next.config.mjs
```

Do not upload local `vendor`, `node_modules`, `.next`, `.dart_tool`, or build
folders.

## 4. Create and import the database

Use **Databases > Create Database** in CyberPanel. Record the database name,
username, and password, then import the existing MozaPay production database
through phpMyAdmin.

Never run `php artisan migrate:fresh` against the production database.

## 5. Configure Laravel

Create `/home/mozapay.app/backend/.env`. When moving an existing installation,
copy its existing production `.env` and preserve its `APP_KEY`. Update these
values:

```env
APP_NAME=MozaPay
APP_ENV=production
APP_DEBUG=false
APP_URL=https://mozapay.app/backend
FRONTEND_URL=https://mozapay.app

LOG_CHANNEL=stack
LOG_LEVEL=info

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=YOUR_DATABASE
DB_USERNAME=YOUR_DATABASE_USER
DB_PASSWORD=YOUR_DATABASE_PASSWORD

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database
```

Restore the production mail, RayPlusMoney, Pusher, Firebase, and other gateway
credentials. Do not commit `.env` to Git or include it in a public archive.

Only a completely new installation with no existing key should run:

```bash
/usr/local/lsws/lsphp82/bin/php artisan key:generate
```

## 6. Install and prepare Laravel

```bash
cd /home/mozapay.app/backend
/usr/local/lsws/lsphp82/bin/php -v
composer --version
composer install --no-dev --prefer-dist --optimize-autoloader
/usr/local/lsws/lsphp82/bin/php artisan optimize:clear
/usr/local/lsws/lsphp82/bin/php artisan migrate --force
/usr/local/lsws/lsphp82/bin/php artisan storage:link
/usr/local/lsws/lsphp82/bin/php artisan optimize
```

If the `composer` command uses a different PHP version, locate Composer with
`command -v composer` and execute it through
`/usr/local/lsws/lsphp82/bin/php`.

Laravel is served by OpenLiteSpeed. Do not run `php artisan serve` in
production.

The backend is safe to boot during Composer's `package:discover` step even on
a new or partially imported database where core configuration tables have not
been migrated yet. It uses the bundled default theme and defers database-backed
settings, gateway, and plugin configuration until their tables exist. Do not
use `--no-scripts` as a permanent workaround; if Composer still reports a
missing core table, confirm that this updated source is the version deployed.
The migration set also protects legacy column migrations from running before
their imported base tables. This allows `php artisan migrate --force` to finish
on a fresh database without requiring `migrate:fresh`.
Imported raw foreign keys are installed in a post-schema repair migration after
all referenced permission, role, user, order, and courier tables exist. A
previously interrupted pivot-table migration can therefore be resumed safely.

## 7. Expose Laravel under `/backend`

Ensure `/home/mozapay.app/public_html/backend` does not already contain
important files, then create a link to Laravel's public directory:

```bash
cd /home/mozapay.app/public_html
ln -s ../backend/public backend
ls -la /home/mozapay.app/public_html/backend
```

This keeps `.env`, source code, and Composer packages outside the public web
directory.

## 8. Set permissions

Find the current CyberPanel website owner:

```bash
stat -c '%U:%G' /home/mozapay.app/public_html
```

Replace `WEBSITE_USER` with that owner:

```bash
chown -R WEBSITE_USER:WEBSITE_USER /home/mozapay.app/backend
chown -R WEBSITE_USER:WEBSITE_USER /home/mozapay.app/frontend
chmod -R 775 /home/mozapay.app/backend/storage
chmod -R 775 /home/mozapay.app/backend/bootstrap/cache
```

Do not use permission mode 777.

## 9. Install Node.js and PM2

Use Node.js 22 LTS for the Next.js 15 frontend.

Check the operating system first:

```bash
cat /etc/os-release
```

For Ubuntu:

```bash
curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
apt-get install -y nodejs
```

For AlmaLinux or Rocky Linux:

```bash
curl -fsSL https://rpm.nodesource.com/setup_22.x | bash -
dnf install -y nodejs
```

Then install PM2:

```bash
node -v
npm -v
npm install -g pm2
```

## 10. Configure and build Next.js

Create `/home/mozapay.app/frontend/.env.production` before building:

```env
NEXT_PUBLIC_API_URL=https://mozapay.app/backend/api
```

Build and start the production application:

```bash
cd /home/mozapay.app/frontend
npm ci
npm run build
pm2 start ./node_modules/next/dist/bin/next --name mozapay-frontend -- start -H 127.0.0.1 -p 3000
```

Do not use `npm run dev` in production.

Check the local Node application:

```bash
curl -I http://127.0.0.1:3000
pm2 status
pm2 logs mozapay-frontend --lines 100
```

## 11. Configure the OpenLiteSpeed reverse proxy

In CyberPanel, open **Websites > List Websites > mozapay.app > Manage > vHost
Conf**. Keep the existing configuration and add:

```text
extprocessor mozapay_next {
  type                    proxy
  address                 127.0.0.1:3000
  maxConns                100
  pcKeepAliveTimeout      60
  initTimeout             60
  retryTimeout            0
  respBuffer              0
}
```

Open **Manage > Rewrite Rules** and configure:

```apache
RewriteEngine On

RewriteCond %{REQUEST_URI} !^/backend(?:/|$) [NC]
RewriteCond %{REQUEST_URI} !^/\.well-known/acme-challenge/ [NC]
RewriteRule ^(.*)$ HTTP://mozapay_next/$1 [P,L]
```

The `/backend` path remains with Laravel; all other requests are sent to the
Next.js process.

Restart OpenLiteSpeed:

```bash
/usr/local/lsws/bin/lswsctrl restart
```

## 12. Run the Laravel queue

The application uses queued jobs for notifications, mail, payment processing,
and other background work. PM2 can supervise the PHP queue worker:

```bash
pm2 start /usr/local/lsws/lsphp82/bin/php --name mozapay-queue --interpreter none -- /home/mozapay.app/backend/artisan queue:work --sleep=3 --tries=3 --timeout=120 --queue=notifications,default
pm2 startup
```

Run the system command printed by `pm2 startup`, then save the process list:

```bash
pm2 save
```

Always manage these PM2 processes under the same Linux user.

## 13. Add the Laravel scheduler

Create a CyberPanel cron job that runs every minute:

```cron
* * * * * cd /home/mozapay.app/backend && /usr/local/lsws/lsphp82/bin/php artisan schedule:run >> /dev/null 2>&1
```

## 14. Verify the deployment

```bash
curl -fsS https://mozapay.app/backend/up
curl -fsS https://mozapay.app/backend/api/get-settings
curl -I https://mozapay.app
pm2 status
```

Check logs when troubleshooting:

```bash
tail -n 100 /home/mozapay.app/backend/storage/logs/laravel.log
pm2 logs mozapay-frontend --lines 100
pm2 logs mozapay-queue --lines 100
tail -n 100 /usr/local/lsws/logs/error.log
```

Test login, signup, OTP-disabled signup, forgot password, Add Money, RayPlus
callbacks, logout, and the global suspension response from both the merchant
frontend and a newly built mobile app.

## 15. Service suspension commands

```bash
cd /home/mozapay.app/backend
/usr/local/lsws/lsphp82/bin/php artisan service:access status
/usr/local/lsws/lsphp82/bin/php artisan service:access suspend --message="Payment has not been made. Please contact the Developer to restore access."
/usr/local/lsws/lsphp82/bin/php artisan service:access restore
```

## 16. Earlier mobile builds

The newly regenerated Flutter source uses
`https://mozapay.app/backend/api`. Previously installed builds still use the
old domain compiled into those APKs. Keep the previous domain active and proxy
its `/backend/*` requests to `https://mozapay.app/backend/*` until users have
updated to the new mobile release.
