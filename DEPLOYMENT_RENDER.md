# Deploying Veterinary Teleconsultation App to Render (render.com)

This repository is fully configured and ready for production deployment to [Render](https://render.com/).

---

## What We Configured For Render

1. **Multi-Stage Dockerfile (`Dockerfile`)**:
   - **Frontend stage**: Automatically compiles Vite and Tailwind CSS assets (`npm run build`).
   - **Production stage**: Alpine-based PHP 8.2-FPM + Nginx + Composer + Supervisor.
   - Includes essential PHP extensions: `pdo_pgsql`, `pgsql`, `pdo_mysql`, `bcmath`, `pcntl`, `posix`, `sockets`, `zip`, `gd`, `intl`, `opcache`.

2. **Unified Web Server & Reverb WebSockets (`docker/nginx.conf.template` & `docker/supervisord.conf`)**:
   - Supervisor manages Nginx, PHP-FPM, Laravel Reverb WebSockets, and Queue Worker in a single container.
   - Nginx reverse-proxies `/app` and `/apps` traffic internally to Reverb on `127.0.0.1:8080`, allowing real-time chat, timer countdowns, and consultation events to work over Render's public HTTPS/WSS port `443` without requiring extra services or extra cost.
   - Dynamic port binding via Render's `$PORT`.

3. **HTTPS & Proxy Auto-Detection**:
   - `AppServiceProvider.php` automatically forces HTTPS for all generated asset and action URLs in production, preventing mixed-content warnings and enabling WebRTC camera/microphone access.
   - `config/app.php` automatically uses Render's `RENDER_EXTERNAL_URL`.
   - `config/database.php` supports Render's native PostgreSQL `DATABASE_URL`.
   - `resources/js/echo.js` auto-detects browser HTTPS/WSS protocol and uses port 443 with TLS on Render.

4. **1-Click Infrastructure Blueprint (`render.yaml`)**:
   - Provisions both a **Managed PostgreSQL database** and the **Docker Web Service** with all environment variables wired up.

---

## Deployment Option A: Using Render Blueprints (Recommended — 1 Click)

1. Push your code to your **GitHub** or **GitLab** repository:
   ```bash
   git add .
   git commit -m "Prepare Laravel app for Render deployment"
   git push origin main
   ```
2. Log in to [Render Dashboard](https://dashboard.render.com/).
3. Click **New +** at the top right and select **Blueprint**.
4. Connect your Git repository.
5. Render will automatically detect `render.yaml` and display:
   - **Database**: `vet-consultation-db` (PostgreSQL)
   - **Web Service**: `vet-consultation-app` (Docker)
6. Click **Apply**.
7. Render will provision the PostgreSQL database, build the Docker container (install dependencies, compile Vite assets, run database migrations & seeders), and deploy your live site!

---

## Deployment Option B: Manual Setup via Render Dashboard

If you prefer setting up services manually in the dashboard:

### 1. Create PostgreSQL Database
1. In Render, click **New +** > **PostgreSQL**.
2. **Name**: `vet-consultation-db`
3. **Database**: `vet_consultation`
4. **User**: `vet_user`
5. **Plan**: `Free` (or `Starter` for production)
6. Click **Create Database**.
7. Once created, copy the **Internal Database URL** (e.g. `postgres://vet_user:xxxx@dpg-xxxx-a:5432/vet_consultation`).

### 2. Create Web Service
1. Click **New +** > **Web Service**.
2. Connect your repository.
3. Set the following settings:
   - **Name**: `vet-consultation-app`
   - **Region**: Choose the same region as your database (e.g., Singapore, Oregon, Frankfurt).
   - **Language / Runtime**: **Docker**
   - **Branch**: `main` (or your active branch)
   - **Plan**: `Free` (or `Starter` to prevent spin-down/sleep)
   - **Health Check Path**: `/up`

4. Add the following **Environment Variables**:

| Variable | Value | Notes |
| :--- | :--- | :--- |
| `APP_NAME` | `Veterinary Teleconsultation` | Application Title |
| `APP_ENV` | `production` | Production mode |
| `APP_DEBUG` | `false` | Disable debug stack traces |
| `APP_KEY` | *(Click "Generate")* or run `php artisan key:generate --show` | 32-character base64 key |
| `LOG_CHANNEL` | `stderr` | Streams logs directly to Render log viewer |
| `DB_CONNECTION` | `pgsql` | PostgreSQL connection |
| `DATABASE_URL` | *(Paste Internal Database URL)* | Connection string from Step 1 |
| `SESSION_DRIVER` | `database` | Retains user logins across restarts |
| `CACHE_STORE` | `database` | High performance database cache |
| `QUEUE_CONNECTION` | `database` | Background jobs processing |
| `BROADCAST_CONNECTION` | `reverb` | Real-time WebSocket broadcasting |
| `REVERB_APP_ID` | `vet_teleconsult_app` | Reverb App Identifier |
| `REVERB_APP_KEY` | `vet_teleconsult_key` | Public WebSocket Key |
| `REVERB_APP_SECRET` | *(Click "Generate")* | Secret auth key |
| `REVERB_SERVER_HOST` | `127.0.0.1` | Internal server listener |
| `REVERB_SERVER_PORT` | `8080` | Internal Reverb port |
| `REVERB_PORT` | `443` | External HTTPS/WSS port |
| `REVERB_SCHEME` | `https` | External SSL scheme |
| `REVERB_API_HOST` | `127.0.0.1` | Backend HTTP event dispatcher |
| `REVERB_API_PORT` | `8080` | Backend HTTP event port |
| `REVERB_API_SCHEME` | `http` | Internal communication scheme |
| `VITE_REVERB_APP_KEY`| `vet_teleconsult_key` | Frontend client key |
| `VITE_REVERB_PORT` | `443` | Frontend WSS port |
| `VITE_REVERB_SCHEME`| `https` | Frontend WSS scheme |
| `RUN_MIGRATIONS` | `true` | Automatically executes `php artisan migrate --force` on deploy |
| `RUN_SEEDER` | `true` | Runs initial database seed (admin, vets, pets, settings). Set to `false` after first successful deploy! |

5. Click **Deploy Web Service**.

---

## Seeded Default Accounts

If `RUN_SEEDER=true` is enabled on initial deploy, the following accounts are available immediately:

| Role | Email | Password |
| :--- | :--- | :--- |
| **System Admin** | `admin@vetconsult.com` | `password` |
| **Veterinarian (Approved)** | `dr.maria@vetconsult.com` | `password` |
| **Veterinarian (Pending)** | `dr.angela@vetconsult.com` | `password` |
| **Pet Owner / Client** | `client@gmail.com` | `password` |

> [!NOTE]
> After your initial deployment has succeeded and seeded the database, switch `RUN_SEEDER` to `false` in the Render dashboard so future code deployments do not re-run seeders.

---

## Persistent Storage for File Uploads (Pet Photos, Licenses)

On Render's standard web services, the container filesystem is ephemeral (files reset when rebuilding). 

### Option 1: Attach a Render Disk
1. In your Web Service settings on Render, navigate to **Disks**.
2. Click **Add Disk**:
   - **Name**: `storage-disk`
   - **Mount Path**: `/var/www/html/storage/app/public`
   - **Size**: 1 GB+
3. This persists all client uploads and veterinary documents across deploys.

### Option 2: Cloud Object Storage (AWS S3 or Cloudflare R2)
If you prefer S3 or Cloudflare R2 (10GB free on R2):
- Set `FILESYSTEM_DISK=s3`
- Add `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET`, and `AWS_ENDPOINT` in your Render Environment Variables.

---

## Verifying Deployment Health & WebSockets

- **Health check**: Visit `https://your-service.onrender.com/up`. You should receive an HTTP 200 with Laravel's uptime indicator.
- **WebSockets / Reverb**: Join a chat or video room. The top indicator will display a glowing bolt with **"Reverb"** (green), confirming the WebSocket connection is active through the Nginx reverse proxy.
