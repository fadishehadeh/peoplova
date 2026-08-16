# Peoplova HR - Project Reference

**Last updated:** 2026-08-16
**Live URL:** https://peoplova.com
**GitHub:** https://github.com/fadishehadeh/peoplova
**Owner:** Fadi Shehadeh (fshehadeh@gmail.com)

---

## Quick Links

| What | URL |
|------|-----|
| HR Portal (live) | https://peoplova.com |
| Login | https://peoplova.com/login |
| Test Guide | https://peoplova.com/guide.html |
| GitHub | https://github.com/fadishehadeh/peoplova |

---

## Test Accounts (Live)

| Role | Username | Password |
|------|----------|----------|
| Admin (Super Admin) | admin@peoplova.com | Peoplova!234 |
| Manager | manager@peoplova.com | Peoplova!234 |
| Employee | employee@peoplova.com | Peoplova!234 |

---

## SSH Access

| Field | Value |
|-------|-------|
| Host | 68.65.120.179 |
| Port | 21098 |
| User | clanumsr |
| SSH Key | ~/.ssh/peoplova_deploy3 |
| Web root | /home/clanumsr/peoplova/public-hr/ |
| Git repo | /home/clanumsr/peoplova/ |

```bash
# Connect
ssh -i ~/.ssh/peoplova_deploy3 -o StrictHostKeyChecking=no -o BatchMode=yes -p 21098 clanumsr@68.65.120.179

# Deploy (run from local repo)
git push origin main
ssh -i ~/.ssh/peoplova_deploy3 -o StrictHostKeyChecking=no -o BatchMode=yes -p 21098 clanumsr@68.65.120.179 "cd /home/clanumsr/peoplova && git pull origin main"

# Upload a single file
scp -i ~/.ssh/peoplova_deploy3 -o StrictHostKeyChecking=no -P 21098 local/file.ext clanumsr@68.65.120.179:/home/clanumsr/peoplova/public-hr/path/
```

---

## Database (Live)

| Field | Value |
|-------|-------|
| DB name | clanumsr_peoplova |
| DB user | clanumsr_peoplovausr |
| DB password | rX6)%WMrrD4Vx]5, |
| Careers DB | clanumsr_peoplovacareers |

```bash
# Run a query on live server
ssh -i ~/.ssh/peoplova_deploy3 -o StrictHostKeyChecking=no -o BatchMode=yes -p 21098 clanumsr@68.65.120.179 \
  "mysql -u clanumsr_peoplovausr -prX6\)%WMrrD4Vx\]5, clanumsr_peoplova -e \"YOUR QUERY;\" 2>/dev/null"
```

---

## Email (Mailjet)

| Field | Value |
|-------|-------|
| API Key | cbc5bc665edc291096025dc9ee5ea4b0 |
| API Secret | 3d57a3168f71357dc93ba28037954119 |
| Transport | mailjet (set in .env on live server) |
| From name | Peoplova HR |

---

## Architecture

### Stack
- **Language:** PHP 8.x (no framework - custom MVC)
- **Database:** MySQL (PDO, prepared statements only)
- **Frontend:** Bootstrap 5, DataTables, TCPDF, PhpSpreadsheet
- **Email:** Mailjet API v3.1 (MAIL_TRANSPORT=mailjet on live server)
- **Hosting:** Namecheap shared hosting, LiteSpeed web server
- **Assets:** Static files only, no npm/webpack

### Request Lifecycle
```
public-hr/index.php -> bootstrap.php -> Router -> Middleware -> Controller -> View
```

### Entry Points
- `public-hr/index.php` - HR portal (peoplova.com)
- `public-careers/index.php` - Careers portal (careers.peoplova.com)

### Databases
- `hr_system` (local) / `clanumsr_peoplova` (live) - main app
- `hr_systemcareers` (local) / `clanumsr_peoplovacareers` (live) - careers portal

### Key Directories
```
app/
  Core/           - Router, Controller, Auth, Database, Middleware
  Modules/        - Feature modules (each has Controllers/, Models/, Views/)
  Views/          - Shared layouts and partials
  Support/        - Helpers, Encryption, Mailer, Branding
config/           - database.php, app.php, careers_db.php
routes/           - One file per module (admin.php, leaves.php, employees.php...)
public-hr/        - Document root for HR portal
  assets/css/     - app.css (all custom styles + CSS variables)
  assets/images/  - Logo SVG files
  assets/uploads/ - Company logos uploaded via UI
database/         - Schema SQL files and migrations
scripts/          - Cron job scripts
```

---

## Roles & Permissions

### Role Codes

| Code | Name | Access Level |
|------|------|-------------|
| `super_admin` | Super Admin | Everything including system settings, roles, backups |
| `hr_only` | HR Only | Full HR access, no system settings |
| `hr_admin` | HR Admin | HR + user management + structure, NO roles/permissions/settings |
| `manager` | Manager | Own team: leave approvals, letter requests, org chart |
| `employee` | Employee | Self-service: leave requests, letters, profile, documents |

### hr_admin Specific Access
Can access: Employees, Leave, Documents, Recruitment, Onboarding, Offboarding,
Announcements, Reports, Structure (companies/branches/departments), User management

Cannot access: System Settings, Roles & Permissions, Resilience/Backups

### User Visibility Rules
- `super_admin` users are hidden from the User Access list for all non-super-admin roles
- Only a logged-in super_admin can see other super_admin accounts

### RBAC Implementation
- Permissions stored in: `roles` -> `role_permissions` -> `permissions` tables
- Route-level: `RoleMiddleware` checks role codes
- View-level: `has_role(['super_admin', 'hr_only', 'hr_admin'])` helper
- Action-level: `can('module.action')` helper

---

## Modules

| Module | Path | Description |
|--------|------|-------------|
| Dashboard | `app/Modules/Dashboard/` | Role-specific dashboards with stats |
| Employees | `app/Modules/Employees/` | Employee records, org chart, profile |
| Leave | `app/Modules/Leave/` | Requests, approvals, balances, calendar, holidays |
| Documents | `app/Modules/Documents/` | Upload, categories, types, expiry tracking |
| Letters | `app/Modules/Letters/` | Templates, requests, PDF generation, email delivery |
| Announcements | `app/Modules/Announcements/` | Company-wide notices |
| Onboarding | `app/Modules/Onboarding/` | Checklists and templates for new hires |
| Offboarding | `app/Modules/Offboarding/` | Exit checklists |
| Recruitment | `app/Modules/Recruitment/` | Job postings, applications |
| Reports | `app/Modules/Reports/` | Headcount, leave, department reports + Excel/PDF export |
| Admin | `app/Modules/Admin/` | Users, roles, permissions |
| Structure | `app/Modules/Structure/` | Companies, branches, departments, job titles |
| Settings | `app/Modules/Settings/` | System config (super_admin only) |
| Resilience | `app/Modules/Resilience/` | Backups (super_admin only) |
| Profile | `app/Modules/Profile/` | Self-service profile and password change |

---

## Email System

- **Transport:** Mailjet API v3.1 (configured via `MAIL_TRANSPORT=mailjet` in .env)
- **Key class:** `app/Support/Mailer.php`
- **Raw MIME emails** (salary certificates with PDF attachment): handled by `sendRaw()` which routes through `sendRawViaMailjetApi()` - parses HTML from MIME, extracts base64 PDF, sends via Mailjet Attachments field
- **Queue:** Emails are queued in `email_queue` table and processed by `scripts/process-email-queue.php` (runs every minute via cron)

---

## Branding

- **Brand color:** `#FF3D33` (red/orange-red)
- **CSS variables:** `--brand-primary`, `--brand-primary-dark`, `--brand-primary-soft`
- **Defined in:** `public-hr/assets/css/app.css` (line 1, `:root {}`)
- **Logo (color):** `public-hr/assets/images/peoplova-mark.svg`
- **Logo (white):** `public-hr/assets/images/peoplova-mark-white.svg`
- **Branding class:** `app/Support/Branding.php` - reads company name, tagline, logo from `companies` table where `is_main_tenant=1`; falls back to SVG files

---

## Security Notes

- Passwords: bcrypt hashed
- PII: AES-256-CBC encrypted at rest via `encrypt_field()` / `decrypt_field()`
- Session: regenerated on login
- Lockout: 5 failed attempts -> 15 min lockout
- CSRF: `csrf_field()` on all forms
- XSS: `e()` helper for output escaping
- SQL: PDO prepared statements only - never raw interpolation
- OTP: optional 2FA via Mailjet before completing login

---

## Environment Variables (.env)

| Key | Description |
|-----|-------------|
| `DB_HOST` / `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | Main database |
| `ENCRYPTION_KEY` | 64-char hex - NEVER change after data exists |
| `APP_URL` | Overridden per entry point; also set in `settings` table |
| `MAIL_TRANSPORT` | `mailjet` on live, `smtp` or `mail` locally |
| `MAIL_FROM_NAME` / `MAIL_FROM_ADDRESS` | Email sender identity |
| `MAILJET_API_KEY` / `MAILJET_API_SECRET` | Mailjet credentials |
| `RECAPTCHA_SITE_KEY` / `RECAPTCHA_SECRET_KEY` | Google reCAPTCHA |

---

## Cron Jobs

```bash
# Every minute - processes the email queue
php /home/clanumsr/peoplova/scripts/process-email-queue.php

# Periodic - escalation workflows
php /home/clanumsr/peoplova/scripts/process-escalations.php
```

---

## Client Forks

| Client | Repo | Local path | Status |
|--------|------|------------|--------|
| Byblos Printing SAL | https://github.com/fadishehadeh/peoplova-byblos | C:\xampp\htdocs\byblos-hr | In progress - branding done, hosting TBD |

### Byblos Brand Colors
- Primary: `#5B8DB8` (steel blue)
- Dark: `#4A7BA6`
- Soft: `#E8F0F7`

### Pulling upstream fixes into a client fork
```bash
cd C:\xampp\htdocs\byblos-hr
git fetch upstream
git merge upstream/main
```

---

## Coding Rules (enforced in all sessions)

- Never use em dash character (causes encoding corruption on live server)
- Never use smart/curly quotes in PHP files (causes silent runtime errors)
- Always use HTML entities in HTML files (no special Unicode characters)
- All DB queries via PDO prepared statements only
- No raw query interpolation ever

---

## Changelog

All changes in reverse chronological order.

---

### 2026-08-16

**Update test guide credentials (peoplova.com/guide.html)**
- All three accounts updated to use @peoplova.com emails
- Admin: admin@peoplova.com / Peoplova!234
- Manager: manager@peoplova.com / Peoplova!234
- Employee: employee@peoplova.com / Peoplova!234
- All inline credential hints inside test steps updated to match
- Uploaded directly to live server via SCP

**Add PROJECT_REFERENCE.md**
- Created living reference document covering architecture, roles, modules, SSH, DB, changelog
- Rule: update this file in every commit going forward

**Byblos Printing SAL client fork**
- Created `C:\xampp\htdocs\byblos-hr\` from peoplova codebase
- GitHub repo: https://github.com/fadishehadeh/peoplova-byblos
- Brand colors changed to Byblos blue (#5B8DB8) throughout app.css
- App name changed to "Byblos HR" in all view files
- `upstream` remote added pointing to peoplova for pulling future fixes
- CLAUDE.md created in byblos repo with full client context

---

### 2026-08-09

**Hide super_admin from user list for non-super-admin roles**
- `app/Modules/Admin/AdminRepository.php` - added `$isSuperAdmin` param to `listUsers()`; filters `r.code != 'super_admin'` unless caller is super_admin
- `app/Modules/Admin/AdminController.php` - passes `$isSuperAdmin` flag from current session user

**Add hr_admin to sidebar so HR Admin sees full HR menu**
- `app/Views/partials/sidebar.php` - changed all `has_role(['super_admin', 'hr_only'])` to `has_role(['super_admin', 'hr_only', 'hr_admin'])` for People, Leave, Documents, Recruitment, Access & Org sections

**Grant hr_admin role full HR access with limited admin scope**
- `routes/admin.php` - split middleware into `$superAdminMiddleware` (super_admin + hr_only) and `$userManagementMiddleware` (+ hr_admin); user routes use the latter, roles routes use the former
- `app/Modules/Dashboard/DashboardController.php` - added `hr_admin` to dashboard view match arm and pending stats queries
- DB: removed `settings.manage` from hr_admin role permissions; kept `structure.manage` and all HR module permissions

---

### 2026-08-07 (approx)

**Fix salary certificate email arriving as raw MIME text**
- `app/Support/Mailer.php` - added `sendRawViaMailjetApi()` private method; `sendRaw()` now routes through Mailjet API when `MAIL_TRANSPORT=mailjet` instead of falling through to `mail()`; parses HTML from MIME with regex, extracts base64 PDF attachment

**Show pending letter requests count on dashboards**
- `app/Modules/Dashboard/DashboardController.php` - added `pendingLetters` stat for super_admin, hr_only, hr_admin, manager roles

**Disable LiteSpeed proxy caching for all PHP pages**
- `bootstrap.php` - added `Cache-Control: no-store` header

**Various leave module fixes**
- Auto-seed leave balances on employee creation from `leave_types.default_days`
- Fix leave balances page showing 0 employees
- Make `work_email` optional on employee form
- Exclude self from replacement employee dropdown
- Show all non-archived employees in manager dropdown

**Structure module improvements**
- Add delete for companies, branches, departments, teams, job titles, designations, reporting lines
- Fix branch options in edit modal causing 500 on departments page
- Friendly error on duplicate name instead of raw SQL error

**UI fixes**
- Fix topbar dropdown clipping behind content cards (z-index)
- Sidebar: solid color background, rail/panel CSS, logo and icon sizing

---

### Initial Release

- Full HR portal: employees, leave, documents, letters, announcements, onboarding, offboarding, recruitment, reports
- Careers portal (separate DB)
- Role-based access: super_admin, hr_only, manager, employee
- Mailjet email integration with OTP 2FA option
- AES-256-CBC encryption for PII fields
- TCPDF for letter generation, PhpSpreadsheet for Excel exports
- Landing/marketing page with GCC-focused messaging
