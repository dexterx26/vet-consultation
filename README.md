# Veterinary Teleconsultation Platform

A modern veterinary teleconsultation platform built with Laravel 12, Alpine.js, Tailwind CSS, and Reverb WebSockets.

---

## 🔄 Dual Environment: Development vs Production

This application is configured with a 1-click switch between **Local Development (SQLite)** and **Production Cloud (PostgreSQL on Render)**.

### Environment Profiles

| Feature | 💻 Development (`dev`) | ☁️ Production (`prod`) |
| :--- | :--- | :--- |
| **Database** | **SQLite** (`database/database.sqlite`) | **PostgreSQL** (`vet_consultation` on Render) |
| **Environment** | `APP_ENV=local` | `APP_ENV=production` |
| **Debug Mode** | `APP_DEBUG=true` (Detailed error pages) | `APP_DEBUG=false` (Production error pages) |
| **App URL** | `http://localhost:8000` | `https://vet-consultation-app.onrender.com` |
| **WebSockets** | `http://127.0.0.1:8001` (Local HTTP/WS) | `wss://...:443` (Cloud HTTPS/WSS via Nginx) |
| **Logging** | `storage/logs/laravel.log` | `stderr` (Render Cloud Logs) |

---

### How to Switch Environments

#### Option 1: 1-Click Windows Batch Scripts (Easiest)
- **Switch to Local Dev (SQLite):** Double-click `switch-to-dev.bat`
- **Switch to Production (PostgreSQL):** Double-click `switch-to-prod.bat`
- **Interactive Menu:** Double-click `switch-env.bat`

#### Option 2: Artisan CLI Command
```bash
# Switch to Development (SQLite)
php artisan env:switch dev

# Switch to Production (PostgreSQL)
php artisan env:switch prod

# Check current active environment status
php artisan env:switch status
```

---

## 🚀 Running Locally on your Laptop / PC

1. Ensure you are in development mode:
   ```bash
   php artisan env:switch dev
   ```
2. Double-click `start-dev.bat` or run:
   ```bash
   # Starts HTTP server, Reverb WebSockets, and Vite asset compiler
   start-dev.bat
   ```
3. Open your browser:
   - **Web Application:** `http://localhost:8000`
   - **Demo Pet Owner:** `client@gmail.com` / `password`
   - **Demo Veterinarian:** `dr.maria@vetconsult.com` / `password`
   - **Demo Admin:** `admin@vetconsult.com` / `password`

---

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
