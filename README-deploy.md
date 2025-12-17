Deployment notes

Goal: Deploy frontend static site to Vercel and host PHP backend separately.

1) Frontend (Vercel)
- This repository contains the static frontend under `frontend-ecowaste`.
- `vercel.json` routes all requests to `frontend-ecowaste` so Vercel serves that folder as the site root.
- To deploy using the Vercel Git integration: push to GitHub and connect the repository in Vercel; the root `vercel.json` will handle serving `frontend-ecowaste`.

2) Backend (PHP)
- The backend is PHP and located across controllers, `Config/db.php`, `Models/`, and `View/`.
- Vercel does not host native PHP. Deploy the PHP backend to a PHP-supporting host (DigitalOcean App Platform, Render, a shared host, or a VPS).
- Update environment variables on the PHP host: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_CHARSET`, `APP_ENV`.

3) Connecting Frontend -> Backend
- The frontend currently contains PHP pages and server-side session checks. For a static deployment, replace or adapt any server-side logic to call the backend API (AJAX / fetch) where appropriate.
- Add a Vercel environment variable or use the Vercel dashboard to set `BACKEND_BASE_URL` to point to your PHP backend (e.g. `https://api.example.com`). Update frontend JS to use that variable if needed.

4) Quick local test
- You can preview the static frontend locally with a static server. Example using `npx serve`:

```bash
npm install -g serve
serve frontend-ecowaste
```

5) Next steps I can do for you
- Convert PHP-only pages in `frontend-ecowaste` to static HTML/JS that call the API.
- Prepare a minimal PHP deployment package and Heroku/Render/DigitalOcean deployment guide.
- Add simple fetch-based API wrappers and a central `config.js` that reads `BACKEND_BASE_URL`.

If you want me to proceed, tell me whether to (A) convert frontend PHP pages to static + API calls, or (B) prepare deployment for the PHP backend to Render/DigitalOcean.
