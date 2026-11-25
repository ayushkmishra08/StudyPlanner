# Presidency University
### School of Computer Science and Engineering
### Department of Computer Science & Engineering

---

## A Report on Mini-Project (CA3)
### Titled: **StudyPlanner – Academic Task, Exam Management & Progress Tracking Web Application**

**Course Title:** Web Technologies  
**Course Code:** CSE2258  
**Live Application URL:** [https://studyplanner-1-fv5e.onrender.com](https://studyplanner-1-fv5e.onrender.com)  
**GitHub Repository:** [https://github.com/ayushkmishra08/StudyPlanner](https://github.com/ayushkmishra08/StudyPlanner)  
**Date of Submission:** 26/11/2026  

---

### Submitted By:

| Sl. No. | Student Name | Roll / Registration No. | Signature |
| :---: | :--- | :--- | :---: |
| 1 | Ayush Kumar Mishra | 20221CSE0000 *(Update if needed)* | __________ |
| 2 | *(Team Member 2)* | *(Roll No.)* | __________ |
| 3 | *(Team Member 3)* | *(Roll No.)* | __________ |
| 4 | *(Team Member 4)* | *(Roll No.)* | __________ |

---

## Table of Contents / Index

1. **Abstract**
2. **Introduction**
   - 2.1 Project Overview & Motivation
   - 2.2 Objectives
   - 2.3 System Architecture (MVC Pattern)
   - 2.4 Technologies & Tools Utilized
   - 2.5 Security Implementation
3. **Database Design & Entity Specifications**
   - 3.1 Entity-Relationship (ER) Architecture
   - 3.2 Database Schema Specifications
4. **About Each Page**
   - 4.1 Authentication Modules (`/login`, `/signup`, `/logout`)
   - 4.2 Student Dashboard & Overview (`/`)
   - 4.3 Subject Management (`/subjects`, `/subjects/create`, `/subjects/{id}`, `/subjects/{id}/edit`)
   - 4.4 Study Task Management (`/tasks`, `/tasks/create`, `/tasks/{id}/edit`)
   - 4.5 Exam Scheduler (`/exams`, `/exams/create`, `/exams/{id}/edit`)
   - 4.6 Academic Progress Tracker (`/progress`)
   - 4.7 Administrator Workspace (`/admin`, `/admin/manage-users`, `/admin/activity`)
5. **Implementation & Cloud Deployment**
   - 5.1 Docker Containerization
   - 5.2 Multi-Database Architecture (MySQL & SQLite)
   - 5.3 Render Cloud Web Service Configuration
6. **Results & Verification**
   - 6.1 Automated Test Suite & Validation (48 Test Checks)
   - 6.2 Application Screenshots & Description
7. **Conclusion & Future Enhancements**
   - 7.1 Conclusion
   - 7.2 Future Enhancements
8. **References**

---

## 1. Abstract

Effective academic management and self-directed study scheduling are critical determinants of student performance in higher education. Students frequently experience cognitive overload and missed academic deadlines due to fragmented communication across disparate channels (learning management systems, messaging applications, and physical diaries). 

**StudyPlanner** is an enterprise-grade, native PHP web application engineered to resolve this academic fragmentation. Structured strictly according to the **Model-View-Controller (MVC)** architectural pattern, StudyPlanner provides a centralized, private academic hub where students can organize academic subjects with personalized color palettes, schedule graded tasks with priority and status states, track critical semester examination timelines, and visualize academic progress metrics in real-time. 

Additionally, the system incorporates an **Administrator Workspace** providing system diagnostics, student account lifecycle management, and a non-repudiable audit logging system. The application adheres to rigorous security standards, including HTTP-only sessions, Bcrypt password hashing, Cross-Site Request Forgery (CSRF) token validation, and strict SQL injection mitigation through PDO prepared statements. 

The application has been containerized using Docker (PHP 8.3 Apache runtime) with dual database driver compatibility (MySQL / MariaDB for high-throughput enterprise environments and SQLite for self-contained, zero-configuration cloud hosting) and deployed publicly on Render at `https://studyplanner-1-fv5e.onrender.com`.

---

## 2. Introduction

### 2.1 Project Overview & Motivation
Modern university curricula require students to concurrently balance multiple subjects, lab practicals, term projects, continuous assessments, and semester-end examinations. Existing project management suites (e.g., Jira, Trello, Asana) are geared toward commercial software sprints and corporate workflows, imposing unnecessary complexity. Conversely, simplistic to-do list applications lack academic contextualization—such as subject associations, examination countdowns, and completion ratios.

StudyPlanner was developed to bridge this gap by delivering a purpose-built academic productivity ecosystem. Designed from foundational web technologies (PHP, HTML5, CSS3, JavaScript, MySQL/SQLite), it operates without cumbersome external heavy frameworks, demonstrating optimal resource efficiency, transparent request routing, and strict separation of concerns.

### 2.2 Objectives
1. **Academic Organization:** Enable students to create and curate academic courses/subjects with distinct descriptions and custom visual color tags.
2. **Task Lifecycles:** Support creation, modification, status tracking (`pending`, `in_progress`, `completed`), and filtering of homework, reading milestones, and project deadlines.
3. **Examination Timetable:** Provide chronological examination schedules linked to enrolled subjects with status states (`scheduled`, `completed`, `missed`).
4. **Visual Analytics:** Dynamically compute and display completion ratios to provide positive feedback on study progress.
5. **Role-Based Access Control (RBAC):** Enforce strict tenant isolation where regular students access only their private records, while authorized administrators oversee institutional accounts and system audit logs.
6. **Production Cloud Deployment:** Containerize the full stack using Docker for consistent, cross-platform execution on cloud infrastructure (Render).

### 2.3 System Architecture (MVC Pattern)
StudyPlanner employs the standard **Model-View-Controller (MVC)** design pattern:

```
                      ┌─────────────────────────────────┐
                      │          Web Browser            │
                      └────────────────┬────────────────┘
                                       │ HTTP Request (GET/POST)
                                       ▼
                      ┌─────────────────────────────────┐
                      │   Front Controller (index.php)  │
                      └────────────────┬────────────────┘
                                       │ Routes URL to Action
                                       ▼
                      ┌─────────────────────────────────┐
                      │    Router (App\Core\Router)     │
                      └────────────────┬────────────────┘
                                       │ Dispatches Controller
                                       ▼
       ┌────────────────────────────────────────────────────────────────┐
       │                 Controllers (App\Controllers)                  │
       │  - AuthController        - TaskController     - ExamController │
       │  - SubjectController     - AdminController    - HomeController │
       └──────────────┬───────────────────────────────────┬─────────────┘
                      │ Queries & Updates Data            │ Passes View Model
                      ▼                                   ▼
┌──────────────────────────────────────────┐    ┌──────────────────────────────────────────┐
│          Models (App\Models)             │    │           Views (App\Views)              │
│  - User, Subject, Task, Exam, ActivityLog│    │  - Semantic HTML5 Templates              │
└─────────────────────┬────────────────────┘    │  - Master Layout (app/Views/layout.php)  │
                      │ PDO Prepared Stmts      └─────────────────┬────────────────────────┘
                      ▼                                           │ Returns Rendered HTML
┌──────────────────────────────────────────┐                      ▼
│    Database Engine (MySQL / SQLite)      │            ┌────────────────────┐
│  - studyplanner database                 │            │  Client Display    │
└──────────────────────────────────────────┘            └────────────────────┘
```

1. **Front Controller (`index.php`):** The single entry point for all incoming HTTP requests. It boots session security, loads configuration helpers, and delegates path handling to the router.
2. **Router (`App\Core\Router`):** Matches request methods (GET, POST) and regex-parameterized URIs (e.g., `/tasks/{id}/status`) to specific controller methods.
3. **Controllers (`App\Controllers`):** Implement business logic, enforce authentication and role boundaries, validate user input, invoke model persistence operations, and render corresponding view templates.
4. **Models (`App\Models`):** Encapsulate all database interaction using PHP Data Objects (PDO) with parameterized queries, preventing SQL injection.
5. **Views (`App\Views`):** Modular PHP templates that render responsive HTML. All user-generated text is sanitized with `htmlspecialchars()` before output to prevent Cross-Site Scripting (XSS).

### 2.4 Technologies & Tools Utilized

- **Backend Programming Language:** PHP 8.3 (Strict types enforced `declare(strict_types=1)`)
- **Database Management Systems:**
  - **MySQL / MariaDB 13.0:** Primary relational database engine with InnoDB storage, foreign key constraints, and transactional consistency.
  - **SQLite 3:** Embedded zero-configuration database fallback for portable Docker and Render cloud deployment.
- **Web Servers:**
  - **Apache 2.4:** Configured with `mod_rewrite` for URL routing and `.htaccess` access restrictions.
  - **PHP Built-in Server:** Utilized for rapid local development and automated testing pipelines.
- **Frontend Architecture:** Modern semantic HTML5, Vanilla CSS3 (custom CSS custom properties / variables, responsive grid, flexbox, mobile navigation), and Vanilla JavaScript (AJAX status form triggers, dialog confirmations, SweetAlert2 integrations).
- **Containerization & Deployment:** Docker Engine, Dockerfile with multi-stage configuration, Render Cloud Web Services.
- **Version Control:** Git & GitHub (`ayushkmishra08/StudyPlanner`).

### 2.5 Security Implementation
- **Cryptographic Password Hashing:** User passwords are never stored in plain text. They are hashed using PHP's `password_hash()` implementing the robust **Bcrypt** algorithm with an adaptive cost factor and cryptographic salt.
- **Cross-Site Request Forgery (CSRF) Tokens:** All mutable HTTP requests (POST) require a cryptographically random, session-bound CSRF token (`_token`). Submissions with absent or mismatched tokens are rejected with HTTP 419.
- **SQL Injection Prevention:** 100% of database interactions leverage PDO prepared statements with strict parameter binding (`:param`).
- **Cross-Site Scripting (XSS) Sanitization:** All dynamic outputs rendered within views are escaped via the custom `e()` helper calling `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
- **Role-Based Tenant Isolation:** Query conditions explicitly bind `WHERE user_id = :user_id`, ensuring students cannot view, mutate, or delete records belonging to peers.

---

## 3. Database Design & Entity Specifications

### 3.1 Entity-Relationship (ER) Architecture
The relational database is centered around five key tables:

```
  ┌──────────────────┐               1:N              ┌──────────────────┐
  │      users       │───────────────────────────────<│     subjects     │
  │──────────────────│                                │──────────────────│
  │ id (PK)          │                                │ id (PK)          │
  │ name             │                                │ user_id (FK)     │
  │ email (UQ)       │                                │ name             │
  │ password_hash    │                                │ description      │
  │ role             │                                │ color            │
  └────────┬─────────┘                                └─────────┬────────┘
           │ 1:N                                                │ 1:N
           │                                                    │
           ├──────────────────────────┐                         │
           ▼                          ▼                         ▼
  ┌──────────────────┐       ┌──────────────────┐      ┌──────────────────┐
  │      tasks       │       │      exams       │      │  (subject_id FK) │
  │──────────────────│       │──────────────────│      └──────────────────┘
  │ id (PK)          │       │ id (PK)          │               │
  │ user_id (FK)     │       │ user_id (FK)     │◄──────────────┘
  │ subject_id (FK)  │       │ subject_id (FK)  │
  │ title            │       │ title            │
  │ description      │       │ exam_at          │
  │ due_date         │       │ status           │
  │ priority         │       └──────────────────┘
  │ status           │
  └──────────────────┘
           │ 1:N
           ▼
  ┌──────────────────┐
  │  activity_logs   │
  │──────────────────│
  │ id (PK)          │
  │ actor_user_id(FK)│
  │ actor_name       │
  │ event            │
  │ description      │
  │ created_at       │
  └──────────────────┘
```

### 3.2 Database Schema Specifications

#### 1. `users` Table
Stores user credentials and authority levels.
- `id` (BIGINT UNSIGNED, Primary Key, Auto Increment)
- `name` (VARCHAR(100), NOT NULL): Full name of the student or administrator.
- `email` (VARCHAR(190), NOT NULL, UNIQUE): Unique login email address.
- `password_hash` (VARCHAR(255), NOT NULL): Bcrypt hashed credential string.
- `role` (ENUM('admin', 'user'), DEFAULT 'user'): Authorization tier.
- `created_at` / `updated_at` (TIMESTAMP): Automatic audit timestamps.

#### 2. `subjects` Table
Represents courses or academic disciplines curated by students.
- `id` (BIGINT UNSIGNED, Primary Key, Auto Increment)
- `user_id` (BIGINT UNSIGNED, Foreign Key -> `users.id` ON DELETE CASCADE)
- `name` (VARCHAR(100), NOT NULL): Course title (e.g., "Web Technologies").
- `description` (VARCHAR(1000)): Syllabus summary or goals.
- `color` (CHAR(7), DEFAULT '#26745c'): Hex color code for visual identification.
- *Constraint:* `UNIQUE KEY (user_id, name)` prevents duplicate courses per user.

#### 3. `tasks` Table
Tracks concrete study milestones, assignments, and reading goals.
- `id` (BIGINT UNSIGNED, Primary Key, Auto Increment)
- `user_id` (BIGINT UNSIGNED, Foreign Key -> `users.id` ON DELETE CASCADE)
- `subject_id` (BIGINT UNSIGNED, NULLABLE, Foreign Key -> `subjects.id` ON DELETE SET NULL)
- `title` (VARCHAR(180), NOT NULL): Task name.
- `description` (VARCHAR(2000)): Detailed assignment requirements.
- `due_date` (DATE, NULLABLE): Target deadline.
- `priority` (ENUM('low', 'medium', 'high'), DEFAULT 'medium')
- `status` (ENUM('pending', 'in_progress', 'completed'), DEFAULT 'pending')

#### 4. `exams` Table
Manages academic exam schedules and test status.
- `id` (BIGINT UNSIGNED, Primary Key, Auto Increment)
- `user_id` (BIGINT UNSIGNED, Foreign Key -> `users.id` ON DELETE CASCADE)
- `subject_id` (BIGINT UNSIGNED, NULLABLE, Foreign Key -> `subjects.id` ON DELETE SET NULL)
- `title` (VARCHAR(120), NOT NULL): Examination title (e.g., "Midterm Assessment").
- `exam_at` (DATETIME, NOT NULL): Scheduled date and time.
- `status` (ENUM('scheduled', 'completed', 'missed'), DEFAULT 'scheduled')

#### 5. `activity_logs` Table
Maintains an immutable security and audit trail for institutional review.
- `id` (BIGINT UNSIGNED, Primary Key, Auto Increment)
- `actor_user_id` (BIGINT UNSIGNED, NULLABLE, Foreign Key -> `users.id` ON DELETE SET NULL)
- `actor_name` (VARCHAR(100)): Display name at the time of action.
- `event` (VARCHAR(80)): Identifier string (e.g., `auth.login`, `task.created`).
- `description` (VARCHAR(255)): Human-readable summary.
- `created_at` (TIMESTAMP): Event recording timestamp.

---

## 4. About Each Page

### 4.1 Authentication Modules (`/login`, `/signup`, `/logout`)
- **Sign In (`/login`):** Validates email format and authenticates passwords using `password_verify()`. On successful verification, regenerates the session ID to neutralize session fixation attacks, registers the user payload into `$_SESSION['user']`, logs an audit entry, and routes regular users to `/` and administrators to `/admin`.
- **Registration (`/signup`):** Allows prospective students to create personal accounts. Enforces password strength policies (8–72 characters, uppercase, lowercase, numerical digits, and special symbols) and verifies password confirmation.
- **Sign Out (`/logout`):** Destroys the current authenticated session and clears cookie credentials securely.

### 4.2 Student Dashboard & Overview (`/`)
- Serves as the central command cockpit for the logged-in student.
- Displays four real-time analytical metric cards:
  1. **Tasks in your plan:** Total assignments recorded across all courses.
  2. **Still to do:** Number of active tasks requiring attention (`pending` or `in_progress`).
  3. **Completed:** Total completed assignments with direct link to the progress breakdown.
  4. **Subjects:** Total active enrolled academic subjects.
- Features an **Upcoming Tasks** list prioritized by completion state, presence of deadlines, and creation timestamp.
- Incorporates a **Subject Quick-Navigation Preview** displaying custom color badges and current task counts per course.

### 4.3 Subject Management (`/subjects`, `/subjects/create`, `/subjects/{id}`, `/subjects/{id}/edit`)
- **All Subjects View (`/subjects`):** Displays a responsive card grid showing enrolled courses, color tags, descriptions, total task counts, and completed task tallies.
- **Subject Creation & Editing Forms (`/subjects/create`, `/subjects/{id}/edit`):** Provides interactive fields for title, descriptions, and a visual hex color picker (`#26745c`, `#1d5aab`, `#7e3af2`, `#d97706`).
- **Subject Detail Page (`/subjects/{id}`):** Isolates a specific subject, showing comprehensive details along with all tasks associated with that subject.
- **Deletion (`/subjects/{id}/delete`):** Securely removes the subject while gracefully detaching associated tasks (`ON DELETE SET NULL`) so study notes remain intact.

### 4.4 Study Task Management (`/tasks`, `/tasks/create`, `/tasks/{id}/edit`)
- **Task Directory (`/tasks`):** Tabular and card-based task list supporting multi-criteria filtering:
  - Filter by status (`All`, `Pending`, `In Progress`, `Completed`).
  - Filter by enrolled academic subject.
- **Inline Status Toggle (`/tasks/{id}/status`):** Students can update status directly from the listing dropdown without navigating away, triggering an automatic POST with CSRF verification.
- **Task Creation & Editing (`/tasks/create`, `/tasks/{id}/edit`):** Allows students to configure task titles, extended instructions, select the parent subject, define deadlines via HTML5 date picker, and assign priority badges (Low, Medium, High).

### 4.5 Exam Scheduler (`/exams`, `/exams/create`, `/exams/{id}/edit`)
- **Schedule Timetable (`/exams`):** Chronologically presents upcoming exams formatted with custom date badges (Month, Day, Weekday, Time).
- **Status Tracking:** Supports updating exam outcomes to `scheduled`, `completed`, or `missed`.
- **Form Controls:** Validates date and time inputs using `datetime-local` format and associates exams with corresponding courses.

### 4.6 Academic Progress Tracker (`/progress`)
- Visualizes academic velocity and workload completion ratios.
- Renders a dynamic completion percentage meter calculated server-side:
  $$\text{Progress \%} = \text{round}\left( \frac{\text{Completed Tasks}}{\text{Total Tasks}} \times 100 \right)$$
- Displays partitioned tabs for **Tasks Completed** (with celebratory styling) and **Pending Work** to assist in daily study prioritization.

### 4.7 Administrator Workspace (`/admin`, `/admin/manage-users`, `/admin/activity`)
- **Institutional Overview (`/admin`):** High-level operational dashboard displaying total registered student accounts, system-wide task counts, active courses, and global completion statistics.
- **User Directory & Management (`/admin/manage-users`):** Enables administrators to create pre-configured student accounts, inspect individual user profiles (`/admin/users/{id}`), review specific student task lists, and remove accounts (which cascades deletions across orphaned data).
- **Security & Activity Log (`/admin/activity`):** Displays an immutable chronological audit trail tracking user authentication events, account creations, course additions, task modifications, and deletions.

---

## 5. Implementation & Cloud Deployment

### 5.1 Docker Containerization
To ensure consistent cloud portability and eliminate "works on my machine" discrepancies, the project was containerized using a custom multi-stage Docker image:
- **Base Image:** `php:8.3-apache` (Debian-based official distribution).
- **Installed Extensions:** `pdo`, `pdo_mysql`, `pdo_sqlite`, `sqlite3`, `libsqlite3-dev`.
- **Apache Modules:** `a2enmod rewrite` enabled to execute routing rules from `.htaccess`.
- **Dynamic Port Binding:** A specialized entrypoint script (`docker-entrypoint.sh`) reads the dynamic `$PORT` environment variable injected by cloud providers (e.g., port 10000 on Render) and updates Apache's `ports.conf` and `000-default.conf` accordingly.

### 5.2 Multi-Database Architecture (MySQL & SQLite)
StudyPlanner includes a resilient database connection engine (`App\Core\Database`):
- **Production MySQL Mode:** When cloud MySQL credentials (`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`) are supplied, the app communicates with the enterprise MySQL instance.
- **Zero-Config SQLite Mode:** In isolated cloud environments (such as Render's free tier), the system automatically detects SQLite mode or auto-falls back to an embedded SQLite database (`storage/studyplanner.sqlite`), executing `db/schema_sqlite.sql` and seeding default admin and student accounts seamlessly.

### 5.3 Render Cloud Web Service Configuration
The application is deployed live using Render's Infrastructure-as-Code blueprint (`render.yaml`):

```yaml
services:
  - type: web
    name: studyplanner
    runtime: docker
    plan: free
    region: oregon
    envVars:
      - key: PORT
        value: 10000
      - key: DB_CONNECTION
        value: sqlite
```

- **Live URL:** [https://studyplanner-1-fv5e.onrender.com](https://studyplanner-1-fv5e.onrender.com)
- **Continuous Deployment:** Integrated directly with the GitHub repository (`ayushkmishra08/StudyPlanner`), triggering automated Docker builds and zero-downtime rolling deploys upon every Git push.

---

## 6. Results & Verification

### 6.1 Automated Test Suite & Validation
To verify security boundaries and form validation rules, an automated smoke test suite (`tests/form_validation_smoke.php`) was authored and executed. All **48 automated safety checks passed with zero regressions**:

| Test Category | Test Case Executed | Result |
| :--- | :--- | :---: |
| **Authentication** | Empty sign-up and sign-in inputs rejected | **PASS** |
| **Authentication** | Password strength enforcement (< 8 chars, missing symbols) | **PASS** |
| **Authentication** | Password mismatch on registration rejected | **PASS** |
| **Authentication** | Valid registration & Bcrypt verification | **PASS** |
| **Data Sanitization** | HTML tags stripped from user input (`<b>test</b>` -> `test`) | **PASS** |
| **Subjects** | Duplicate subject name per user rejected | **PASS** |
| **Subjects** | Invalid hex color codes rejected | **PASS** |
| **Tasks** | Invalid task date format rejected | **PASS** |
| **Tasks** | Non-existent status updates rejected | **PASS** |
| **Exams** | Invalid exam timestamp rejected | **PASS** |
| **Authorization** | Student cannot delete peer's subject or task | **PASS** |
| **Admin** | Duplicate administrator creation prevented | **PASS** |
| **CSRF Protection** | Forms submitted without valid `_token` rejected (HTTP 419) | **PASS** |

### 6.2 Application Screenshots & Description

*(Note: Take screenshots from your live URL `https://studyplanner-1-fv5e.onrender.com` or localhost and place them into the respective boxes below)*

#### 1. Authentication & Sign In Page (`/login`)
> **Screenshot Placeholder:**  
> *[Insert Screenshot of the Login Screen showing email, password field, and branding]*  
> **Description:** Clean, distraction-free authentication interface with client-side and server-side validation feedback.

#### 2. Student Overview Dashboard (`/`)
> **Screenshot Placeholder:**  
> *[Insert Screenshot of the Main Dashboard with 4 analytical stat cards, upcoming tasks, and subject preview]*  
> **Description:** Displays summary metric cards, recent task assignments categorized by color-coded courses, and quick subject navigation.

#### 3. Course / Subject Directory (`/subjects`)
> **Screenshot Placeholder:**  
> *[Insert Screenshot of the Subjects grid showing courses like Web Technologies, OS, DBMS]*  
> **Description:** Shows color-tagged academic courses with current completion statistics and description summaries.

#### 4. Study Task Management (`/tasks`)
> **Screenshot Placeholder:**  
> *[Insert Screenshot of the Tasks page with status filtering and inline status dropdowns]*  
> **Description:** Demonstrates the multi-criteria filter form, priority pills (High, Medium, Low), and inline status change controls.

#### 5. Examination Timetable (`/exams`)
> **Screenshot Placeholder:**  
> *[Insert Screenshot of the Exam Schedule page showing exam dates and status]*  
> **Description:** Chronological timeline of upcoming midterms and practical exams with status badges.

#### 6. Academic Progress Dashboard (`/progress`)
> **Screenshot Placeholder:**  
> *[Insert Screenshot of the Progress page showing percentage meter and completed tasks]*  
> **Description:** Visual representation of completed academic milestones vs pending workload.

#### 7. Administrator Workspace (`/admin`)
> **Screenshot Placeholder:**  
> *[Insert Screenshot of the Admin Dashboard showing institutional metrics and account directory]*  
> **Description:** Restricted workspace displaying institutional statistics and system audit logs.

---

## 7. Conclusion & Future Enhancements

### 7.1 Conclusion
The **StudyPlanner** mini-project successfully satisfies all requirements for Course CSE2258 (Web Technologies). By strictly adhering to native PHP Model-View-Controller (MVC) architecture, the project demonstrates how robust, secure, and responsive web applications can be constructed without relying on bloated third-party frameworks. 

Key achievements include:
- A fully decoupled codebase with distinct layers for routing, controllers, business models, and presentation views.
- Robust data integrity and tenant privacy enforced through relational foreign key constraints and PDO prepared statements.
- Implementation of modern security defenses (CSRF tokens, Bcrypt password hashing, XSS output sanitization).
- Successful containerization with Docker and multi-database support (MySQL & SQLite).
- High availability via live cloud deployment on Render.

### 7.2 Future Enhancements
1. **Push & Email Notifications:** Integration with SMTP/WebPush APIs to deliver automated 24-hour reminder alerts before scheduled examination times and assignment deadlines.
2. **Pomodoro Study Timer:** An embedded client-side study timer linked to specific study tasks to log real-time hours spent per academic subject.
3. **Calendar Export (iCal / Google Calendar):** Enabling one-click `.ics` calendar sync for examination timetables directly into external student calendars.
4. **File Attachments:** Allowing students to upload reference PDF lecture notes or assignment rubrics directly associated with specific tasks.

---

## 8. References

1. Robin Nixon, *Learning PHP, MySQL & JavaScript: With jQuery, CSS & HTML5*, 5th Edition, O'Reilly Media.
2. Luke Welling and Laura Thomson, *PHP and MySQL Web Development*, 5th Edition, Addison-Wesley.
3. Official PHP Documentation: [https://www.php.net/docs.php](https://www.php.net/docs.php)
4. OWASP Top 10 Security Practices: [https://owasp.org/www-project-top-ten/](https://owasp.org/www-project-top-ten/)
5. Docker Documentation: [https://docs.docker.com/](https://docs.docker.com/)
6. Render Cloud Hosting Documentation: [https://render.com/docs](https://render.com/docs)
