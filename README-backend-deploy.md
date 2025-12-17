Render / DigitalOcean quick guide for PHP backend

Option A — Render (recommended for simplicity)

1) Create a new Web Service on Render and connect your repository (you can use the same repo but set "Root Directory" to the backend folder, or create a separate repo containing just PHP backend).

2) Build & Start
- Environment: PHP
- Start Command: `php -S 0.0.0.0:8000 -t .`
  - This uses PHP built-in server; fine for small apps. For production, use Docker with Apache/Nginx.

3) Environment Variables (set in Render dashboard)
- `DB_HOST` - database host
- `DB_NAME` - database name
- `DB_USER` - database user
- `DB_PASS` - database password
- `DB_CHARSET` - utf8mb4
- `APP_ENV` - production

4) Database
- Use Render managed Postgres/MySQL or an external DB. Update `database.sql` and import to the DB.
- Ensure network access: if database is hosted separately, allow Render's host IPs or use a cloud DB with public access and strong credentials.

5) CORS
- If your frontend runs on Vercel, ensure backend adds CORS headers allowing your Vercel domain.

6) Domain
- Configure a custom domain or use the service URL, then set `BACKEND_BASE_URL` in Vercel to that URL.

Option B — Docker (recommended for production)
- Create a `Dockerfile` (example below) and deploy as a Docker service on Render or DigitalOcean App Platform.

Example Dockerfile (simple Apache + PHP):

```
FROM php:8.2-apache
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html
EXPOSE 80
CMD ["apache2-foreground"]
```

Notes:
- Ensure `Config/db.php` reads environment variables (already done).
- Use HTTPS in production and strong DB credentials.
- Validate and sanitize all inputs on server-side before DB operations.

If you want, I can:
- Add a `Dockerfile` to this repo (I can add the Apache/PHP example above).
- Create sample API endpoints wrappers (e.g., `api/login.php`, `api/signup.php`) to match frontend expectations.
- Prepare the `BACKEND_BASE_URL` environment variable instructions for Vercel.
