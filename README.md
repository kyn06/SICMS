# SICMS — Student Information and Case Management System

SICMS is a web-based case management system built for the Office of Student Affairs — **Student Discipline and Reformation Unit (SDRU)** of Central Luzon State University (CLSU). It digitizes the end-to-end handling of student complaints and disciplinary matters: filing, verification, revision, coordinator assignment, hearings, resolution, and archiving.

## Key Features

- **Complaint Filing** — Students submit complaints online with complainant details, respondents, witnesses, incident information, and supporting evidence uploads.
- **Complaint Revision Workflow** — Reviewers can return complaints for revision with remarks; complainants resubmit only the highlighted sections.
- **Case Management** — Cases progress through statuses (_Submitted → Verified → Resolved → Archived_, plus _Returned for Revision_ / _Rejected_) with a complete case history trail.
- **Coordinator Assignment** — Staff assign coordinators to verified cases; coordinators get their own workspace limited to assigned cases.
- **Hearing Scheduling** — Schedule hearings with venue or Google Meet link; includes a calendar view and hearing status tracking.
- **Chat & Messaging** — Secure, per-case messaging between students and SDRU personnel with attachments and hide/unhide conversations.
- **Notifications** — Role-aware in-app notifications for case updates, assignments, revisions, and hearings.
- **Reports & PDF Export** — Filterable reports (date range, status, classification, coordinator, college) exported as branded PDFs.
- **Audit Logs** — Records of user actions with IP address and user agent, viewable by administrators.
- **User Management** — Create/manage accounts and roles.
- **SDRU Assistant Chatbot** — Public, rule-based chatbot answering common questions about complaint procedures, requirements, and workflows.
- **Google Sign-In** — Optional "Sign in with Google" OAuth 2.0 authentication.

## User Roles

| Role                      | Access                                                          |
| ------------------------- | --------------------------------------------------------------- |
| Student                   | Submit complaints, track own cases, chat, receive notifications |
| Coordinator               | Handle assigned cases, schedule hearings, chat                  |
| SDRU Staff (`sdru-staff`) | Case migration, hearings, reports                               |
| Admin / Super Admin       | Full access including user management and audit logs            |
| Head SDRU                 | Full access including user management and audit logs            |

## Tech Stack

- **PHP 8+** (no framework) — custom MVC-style structure
- **MySQL / MariaDB** — schema in [`sicms.sql`](sicms.sql)
- **FPDF** — bundled PDF generation ([`web/vendor/fpdf`](web/vendor/fpdf))
- **Bootstrap Icons** + custom CSS
- **XAMPP (Apache)** — target deployment environment

## Project Structure

```
SICMS/
├── index.php              # Entry point (dashboard redirect)
├── routes.php             # Named routes + URL helper functions
├── sicms.sql              # Complete database schema + seed data
├── LICENSE                # Apache License 2.0
├── public/assets/         # Static assets (logos, images)
├── storage/               # Protected uploads (.htaccess restricted)
│   ├── evidence/
│   └── message_attachments/
└── web/
    ├── chatbot/           # SDRU Assistant chatbot (API + config)
    ├── config/            # Database connection, Google OAuth settings
    ├── controllers/       # Business logic per module
    ├── helpers/           # Security, Colleges, Courses helpers
    ├── models/            # Database access layer
    ├── services/          # PDF report service
    ├── vendor/            # Bundled third-party libs (FPDF)
    └── views/             # Pages: auth, dashboard, complaints, cases,
                           # hearings, messages, notifications, reports,
                           # audit_logs, accounts, settings, layout
```

## Getting Started

### Prerequisites

- [XAMPP](https://www.apachefriends.org/) (or any Apache + PHP 8.0+ + MySQL stack)
- A browser

### Installation

1. **Clone or copy the project** into your web root:

   ```
   C:\xampp\htdocs\SICMS
   ```

2. **Start Apache and MySQL** from the XAMPP Control Panel.

3. **Create the database** by importing the schema:
   - Via phpMyAdmin: open <http://localhost/phpmyadmin> → _Import_ → select `sicms.sql` → _Go_, or
   - Via CLI:

     ```bash
     mysql -u root -h 127.0.0.1 -P 3307 < sicms.sql
     ```

   > **Note:** The app connects to MySQL on host `127.0.0.1`, **port `3306`**, user `root`, password `1234`. If your MySQL uses a different port or password, update the credentials in [`web/config/Database.php`](web/config/Database.php).

4. **Open the system** at:

   ```
   http://localhost/SICMS/
   ```

5. **Log in** using a seeded account below, or register as a student.

### Default Seed Accounts

| Account              | Email                  | Password        | Role         |
| -------------------- | ---------------------- | --------------- | ------------ |
| System Administrator | `admin@sicms.local`    | `Admin@1234`    | super-admin  |
| Head SDRU            | `headsdru@sicms.local` | `HeadSdru@1234` | head-of-sdru |

> ⚠️ Change these passwords immediately before deploying beyond local development.

### Configuration (Optional)

- **Database** — edit [`web/config/Database.php`](web/config/Database.php) (host, port, username, password).
- **Google Sign-In** — edit [`web/config/google_config.php`](web/config/google_config.php):
  1. Create an OAuth client ID at [Google Cloud Console](https://console.cloud.google.com/apis/credentials).
  2. Add `http://localhost/SICMS/web/views/auth/google_signin.php` as an authorized redirect URI.
  3. Paste your client ID and secret into the config.
- **Timezone** — defaults to `Asia/Manila` (set in `routes.php` and `Database.php`).

## Case Workflow

1. **Submitted** — Complainant files a complaint with evidence.
2. **Verified / Returned for Revision / Rejected** — SDRU reviews the complaint; returned complaints can be revised and resubmitted.
3. **Coordinator Assigned** — Verified cases are assigned to a coordinator for investigation.
4. **Hearing / Resolution** — Hearings are scheduled (on-site or via Google Meet); the case is marked **Resolved** with remarks.
5. **Archived** — Resolved cases can be archived (and reopened if needed).

All transitions are recorded in `case_history` and visible to authorized users.

## Security Notes

- Passwords are hashed with bcrypt (`password_hash`).
- CSRF tokens are enforced on all POST requests.
- Session cookies are `HttpOnly`, `SameSite=Lax`, and `Secure` over HTTPS.
- Login attempts are rate-limited with temporary lockout.
- Evidence and message attachments live under `storage/` behind `.htaccess` deny rules — files are served only through authenticated controllers with role checks.
- Do **not** commit production OAuth client secrets or database credentials to version control.

## License

Released under the [Apache License 2.0](LICENSE).
