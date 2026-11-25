# StudyPlanner

A native PHP and MySQL study planner organized with MVC. It includes account registration and login, subject management, study task and exam scheduling, and progress tracking. User data is private to each account; admins can review and manage records across accounts.

## Requirements

- Laragon with Apache, PHP 8.1 or newer, and MySQL/MariaDB
- PHP PDO MySQL extension enabled

## Run locally

### Option A: One-Click Launcher (Recommended for Windows)
Simply double-click `start.bat` or run in PowerShell:
```powershell
.\start.ps1
```
This automatically verifies MariaDB is running, runs database setup/migrations with demo data, starts the PHP server on `http://127.0.0.1:8000/`, and opens your browser.

### Option B: Manual Setup
1. Ensure MySQL/MariaDB is running on `127.0.0.1:3306`.
2. Run database setup and demo seed from this directory:
   ```bash
   php setup.php
   ```
   *(To re-seed or perform a clean install, use `php setup.php --seed` or `php setup.php --reset`)*
3. Database credentials can be customized in `.env` (copy from `.env.example`).
4. Start the built-in PHP server:
   ```bash
   php -S 127.0.0.1:8000 index.php
   ```
5. Open `http://127.0.0.1:8000/` in your browser.

### Default Accounts Ready for Testing:
- **Admin Account**: `admin@studyplanner.test` / `AdminPassword123!`
- **Student Account**: `user@studyplanner.test` / `Student123!`
*(You can also sign up with your own new account anytime)*

## Deploy to Render (Cloud Hosting)

1. Push your repository to GitHub.
2. Go to **[dashboard.render.com](https://dashboard.render.com/)** and click **New +** > **Web Service**.
3. Select your **StudyPlanner** GitHub repository.
4. Set **Runtime** (or Environment) to **Docker** (Render will automatically detect the included `Dockerfile` and `render.yaml`).
5. Click **Create Web Service**.
6. Render builds the PHP 8.3 container, automatically sets up SQLite / MySQL, runs `setup.php`, and provides your live public URL!

*(Optional: To connect an external MySQL database on Render, simply add `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` under the **Environment** tab in your Render dashboard).*

## MVC map

- `app/Controllers/` handles requests and selects responses.
- `app/Models/` contains database operations and domain data.
- `app/Views/` contains PHP templates for rendered HTML.
- `app/Core/` contains the router, base controller, and PDO connection.
- `routes/web.php` maps HTTP methods and paths to controller actions.
- `public/assets/` contains frontend CSS and JavaScript.
- `config/database.php` reads DB settings from environment variables with Laragon defaults.
- `db/schema.sql` creates the users, subjects, tasks, exams, and activity log tables.
- `setup.php` applies the full schema from the command line for a fresh install; it is not accessible over HTTP.
- Existing databases can add exams with `db/migrations/003_add_exams.sql`.
- `db/migrations/` upgrades existing database schemas without deleting existing rows.
- `db/create_admin.php` creates an admin using environment-provided credentials and is CLI-only.
- Admins can open `/admin` for the account directory, `/admin/manage-users` to add or delete regular accounts, `/admin/activity` to review recent activity, and `/admin/users/{id}` to review that account's subjects and tasks.

## Add a route

Register a route in `routes/web.php`, implement the action in a controller, keep database queries in a model, and render a template through the base controller. Dynamic route values use braces, such as `/tasks/{id}/toggle`.

Signup and admin-created accounts default to the `user` role. Account passwords must be 8-72 characters and include uppercase and lowercase letters, a number, and a symbol; the signup and admin account forms require password confirmation. CLI-created admins require at least 12 characters with the same strength rules. Users can schedule exams for their subjects and update exam status. Admins can create and delete regular accounts and review recent authentication, account, subject, task, and exam events; deleting an account also deletes its study data. Regular users can only view and manage their own data. Passwords are hashed, forms use CSRF tokens, and task, subject, and exam queries enforce ownership. This starter still needs production deployment hardening, HTTPS, rate limiting, and a robust error/logging setup before public use.