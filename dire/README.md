# /direschool — 9 Separate Sites, One Shared Database

Plain HTML / CSS / JavaScript / PHP / MySQL, built for XAMPP. No frameworks,
no third-party sign-in. Every role gets its own separate website/folder;
all 9 read and write the same `/direschool_db` database.

| Folder | Who logs in | Login page |
|---|---|---|
| `/direschool-portal` | Nobody (public) | — browse schools, look up results, marketing homepage |
| `/direschool-superadmin` | Super Admin | login.php |
| `/direschool-admin` | School Admin | login.php |
| `/direschool-teacher` | Teacher | login.php |
| `/direschool-librarian` | Librarian | login.php |
| `/direschool-subadmin` | Sub Admin | login.php |
| `/direschool-staff` | Staff | login.php |
| `/direschool-student` | Student | login.php |
| `/direschool-parent` | Parent | login.php |

## Setup on XAMPP

1. Copy **all 9 `/direschool-*` folders AND the `shared-uploads` folder** into
   `htdocs`, so they sit side by side (e.g. `C:\xampp\htdocs\/direschool-portal`,
   `C:\xampp\htdocs\/direschool-superadmin`, `C:\xampp\htdocs\shared-uploads`, ...).
   `shared-uploads/logo.png` is the site-wide logo every site displays.
2. Start Apache and MySQL in XAMPP.
3. In phpMyAdmin, **Import** `database/schema.sql` (drops and recreates
   `/direschool_db` fresh, with sample data and the logo path already set).
4. Visit `http://localhost/direschool-superadmin/setup-admin.php` once to
   create the Super Admin login (`admin@/direschool.com` / `admin123`), then
   delete that file.
5. Public site: `http://localhost/direschool-portal/`

## Role hierarchy

- **Super Admin**: manages Schools (create, ban/unblock with a reason + password
  confirm), verifies school-transfer requests (sees a student's promotion/
  detention history first), reads messages from schools, enters/imports
  Ministry results, and creates **only** School Admin accounts (one per
  school) — cannot touch a school's students, library, teachers, etc.
- **Admin**: runs their one school — registrations, students, report cards,
  conduct/attendance, library, news. Creates Sub Admin/Teacher (unlimited)
  and **Librarian/Staff (capped at one each per school)**.
- **Teacher / Librarian / Sub Admin / Staff**: scoped tools for their job
  (see each site's dashboard for what they can do).
- **Student**: registers → school reviews + confirms payment → logs in →
  sees only their own school (no school picker), their report card
  (Marks/Conduct/Attendance tabs), textbooks by grade, and — only if
  Grade 8 — their Ministry result. Can hide their age from classmates.
- **Parent**: registers, links a child with that child's Student ID +
  password, views that child's attendance/conduct/report card, and chats
  with the child's school staff.

## Branding & theme

- The logo shown on every site comes from the `app_settings` table
  (`logo_path`) — change it any time from **Super Admin → Site Settings**,
  and it updates everywhere instantly since all 9 sites share one database.
- Every site has a 🌙/☀️ toggle for light ("white and blue") / dark
  ("black and blue") mode, saved per-browser in `localStorage`.

## Try it out

- Report Card: Student ID `101`, Password `1234` (student account itself
  needs registering fresh through `/direschool-student/register.php`, since
  sample students don't have login passwords set — the *lookup* pages in
  `/direschool-portal` work with the sample data directly)
- Ministry Result: Registration Number `219339`
- Super Admin: `admin@/direschool.com` / `admin123`

## Known gaps

- Images/results are stored as **URLs**, not uploaded files (except the
  site logo, which now supports real file upload).
- Payments are confirmed manually by staff — no payment gateway.
- CSV import stands in for the future Ministry-result counting machine.
