-- =========================================================
--  Dire Dawa Schools Portal - MySQL database  (SCHEMA v2)
--  This REPLACES the old database/schema.sql.
--  Import this file fresh in phpMyAdmin — it drops and recreates
--  direschool_db, so any old data will be lost. Export first if
--  you need to keep it.
-- =========================================================

DROP DATABASE IF EXISTS direschool_db;
CREATE DATABASE direschool_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE direschool_db;

-- =========================================================
-- 1. SCHOOLS  (now with ban / block support)
-- =========================================================
CREATE TABLE schools (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(150) NOT NULL,
  slug        VARCHAR(150) NOT NULL UNIQUE,
  logo_url    VARCHAR(255) DEFAULT NULL,
  description TEXT DEFAULT NULL,        -- short blurb shown on the school's card
  cover_image VARCHAR(255) DEFAULT NULL, -- hero/cover photo for the card + school website
  -- School website builder: 'none' = no website, 'template' = pick a built-in
  -- design, 'html' = paste your own HTML document.
  website_mode    ENUM('none','template','html') NOT NULL DEFAULT 'none',
  website_template VARCHAR(50) DEFAULT NULL,
  website_html    LONGTEXT DEFAULT NULL,
  status      ENUM('active','blocked') NOT NULL DEFAULT 'active',
  ban_reason  TEXT DEFAULT NULL,
  banned_by   INT DEFAULT NULL,          -- admins.id (a superadmin)
  banned_at   TIMESTAMP NULL DEFAULT NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE sections (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  school_id   INT NOT NULL,
  grade       VARCHAR(10) NOT NULL,
  section     VARCHAR(10) NOT NULL,
  UNIQUE KEY uniq_section (school_id, grade, section),
  FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 2. STAFF ACCOUNTS  (admins table = every staff-type login)
--    role covers: superadmin, admin, subadmin, teacher, librarian, staff
-- =========================================================
CREATE TABLE admins (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  email          VARCHAR(150) NOT NULL UNIQUE,
  password_hash  VARCHAR(255) NOT NULL,
  full_name      VARCHAR(150) DEFAULT NULL,
  role           ENUM('superadmin','admin','subadmin','teacher','librarian','staff')
                 NOT NULL DEFAULT 'admin',
  school_id      INT DEFAULT NULL,   -- NULL for superadmin = every school
  grade          VARCHAR(10) DEFAULT NULL,   -- optional scope (mainly for teachers)
  section        VARCHAR(10) DEFAULT NULL,
  is_banned      TINYINT(1) NOT NULL DEFAULT 0,   -- used for banning a Teacher account
  registration_no VARCHAR(50) DEFAULT NULL,       -- teacher's registration number
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
  -- CASCADE: if a school is deleted, its staff accounts (admin/subadmin/
  -- teacher/librarian/staff) go with it rather than being left dangling.
) ENGINE=InnoDB;

-- schools.banned_by references admins(id), but the schools table above was
-- created before admins existed, so the FK is added here instead.
ALTER TABLE schools
  ADD CONSTRAINT fk_schools_banned_by
  FOREIGN KEY (banned_by) REFERENCES admins(id) ON DELETE SET NULL;

CREATE TABLE staff_attendance (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  staff_id      INT NOT NULL,
  school_id     INT NOT NULL,
  attend_date   DATE NOT NULL,
  status        ENUM('present','absent') NOT NULL,
  recorded_by   INT DEFAULT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_staff_day (staff_id, attend_date),
  FOREIGN KEY (staff_id) REFERENCES admins(id) ON DELETE CASCADE,
  FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
  FOREIGN KEY (recorded_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =========================================================
-- 3. STUDENTS  (own login + registration/payment workflow)
-- =========================================================
CREATE TABLE students (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  school_id       INT NOT NULL,
  -- Globally unique across the WHOLE system (not just within one school) —
  -- a Student ID always identifies exactly one student, system-wide, so a
  -- transfer can look a student up by this ID alone, unambiguously.
  student_no      VARCHAR(30) DEFAULT NULL,
  full_name       VARCHAR(150) NOT NULL,
  english_name    VARCHAR(150) DEFAULT NULL,
  gender          ENUM('M','F') DEFAULT NULL,
  age             INT DEFAULT NULL,
  hide_age        TINYINT(1) NOT NULL DEFAULT 0,
  grade           VARCHAR(10) NOT NULL,
  section         VARCHAR(10) NOT NULL,
  kebele          VARCHAR(100) DEFAULT NULL,
  house_no        VARCHAR(50) DEFAULT NULL,
  image_url       VARCHAR(255) DEFAULT NULL,
  password_hash   VARCHAR(255) DEFAULT NULL,
  -- 'left' = approved by their (former) school's Staff to leave; the
  -- student's report card/attendance/conduct history stays exactly as it
  -- was — nothing is deleted or edited when a student leaves.
  reg_status      ENUM('pending_review','pending_payment','active','rejected','banned','left')
                   NOT NULL DEFAULT 'pending_review',
  ban_reason      TEXT DEFAULT NULL,
  banned_at       TIMESTAMP NULL DEFAULT NULL,
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_student_no (student_no),
  INDEX idx_student_school (school_id),
  FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- A student's request to leave their current school. Their former school's
-- Staff must approve this BEFORE the student can be accepted anywhere else
-- — this is what stops someone from just quietly vanishing from one
-- school's roster and popping up as "new" at another.
CREATE TABLE student_leave_requests (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  student_id    INT NOT NULL,
  school_id     INT NOT NULL,
  reason        TEXT DEFAULT NULL,
  status        ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  reviewed_by   INT DEFAULT NULL,
  reviewed_at   TIMESTAMP NULL DEFAULT NULL,
  requested_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
  -- admins already exists by this point in the file (created in section 2,
  -- before students in section 3), so this FK can be declared inline.
  FOREIGN KEY (reviewed_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE payments (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  student_id    INT NOT NULL,
  status        ENUM('pending','confirmed') NOT NULL DEFAULT 'pending',
  confirmed_by  INT DEFAULT NULL,
  confirmed_at  TIMESTAMP NULL DEFAULT NULL,
  note          VARCHAR(255) DEFAULT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (confirmed_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =========================================================
-- 4. PARENTS
-- =========================================================
CREATE TABLE parents (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  full_name      VARCHAR(150) NOT NULL,
  email          VARCHAR(150) NOT NULL UNIQUE,
  password_hash  VARCHAR(255) NOT NULL,
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE parent_students (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  parent_id   INT NOT NULL,
  student_id  INT NOT NULL,
  UNIQUE KEY uniq_link (parent_id, student_id),
  FOREIGN KEY (parent_id) REFERENCES parents(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 5. SCHOOL TRANSFERS  (anti-fraud: SuperAdmin must verify)
-- =========================================================
CREATE TABLE school_transfer_requests (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  student_id      INT NOT NULL,
  from_school_id  INT NOT NULL,
  to_school_id    INT NOT NULL,
  reason          TEXT DEFAULT NULL,
  requested_by    INT DEFAULT NULL,
  status          ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  reviewed_by     INT DEFAULT NULL,
  review_note     TEXT DEFAULT NULL,
  -- The RECEIVING school's own Staff/Admin can leave a note here after
  -- checking the student's old-school status, attendance, conduct and
  -- report card themselves — a second set of eyes before the Super Admin's
  -- own verification actually moves the student.
  staff_note      TEXT DEFAULT NULL,
  staff_reviewed_by INT DEFAULT NULL,
  requested_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  reviewed_at     TIMESTAMP NULL DEFAULT NULL,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (from_school_id) REFERENCES schools(id) ON DELETE CASCADE,
  FOREIGN KEY (to_school_id) REFERENCES schools(id) ON DELETE CASCADE,
  FOREIGN KEY (requested_by) REFERENCES admins(id) ON DELETE SET NULL,
  FOREIGN KEY (reviewed_by) REFERENCES admins(id) ON DELETE SET NULL,
  FOREIGN KEY (staff_reviewed_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =========================================================
-- 6. REPORT CARDS
-- =========================================================
CREATE TABLE report_cards (
  id                   INT AUTO_INCREMENT PRIMARY KEY,
  school_id            INT NOT NULL,
  student_id           INT NOT NULL,
  grade                VARCHAR(10) NOT NULL,
  section              VARCHAR(10) NOT NULL,
  school_year          VARCHAR(10) NOT NULL,
  teacher_name         VARCHAR(150) DEFAULT NULL,
  sex                  ENUM('M','F') DEFAULT NULL,
  age                  INT DEFAULT NULL,
  kebele               VARCHAR(100) DEFAULT NULL,
  house_no             VARCHAR(50) DEFAULT NULL,
  subjects             JSON DEFAULT NULL,
  conduct              JSON DEFAULT NULL,
  days_present         JSON DEFAULT NULL,
  days_absent          JSON DEFAULT NULL,
  times_tardy          JSON DEFAULT NULL,
  total_academic_days  JSON DEFAULT NULL,
  remarks              TEXT,
  card_password        VARCHAR(50) DEFAULT NULL,
  promotion_status     ENUM('promoted','detained','pending') NOT NULL DEFAULT 'pending',
  created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_rc_student (student_id),
  INDEX idx_rc_cohort (school_id, grade, section, school_year),
  FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 7. MINISTRY RESULTS (Grade 8) + import batch log
-- =========================================================
CREATE TABLE ministry_results (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  school_id         INT DEFAULT NULL,
  student_id        INT DEFAULT NULL,
  student_no        VARCHAR(30) NOT NULL,
  student_name      VARCHAR(150) DEFAULT NULL,
  grade             VARCHAR(10) NOT NULL DEFAULT '8',
  school_year       VARCHAR(10) DEFAULT NULL,
  subjects          JSON DEFAULT NULL,
  total             DECIMAL(6,2) DEFAULT NULL,
  average            DECIMAL(6,2) DEFAULT NULL,
  promotion_status  VARCHAR(30) DEFAULT NULL,
  promotion_label   VARCHAR(100) DEFAULT NULL,
  photo_url         VARCHAR(255) DEFAULT NULL,
  source            ENUM('manual','import','machine') NOT NULL DEFAULT 'manual',
  created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_ministry_no (student_no),
  FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE ministry_result_imports (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  imported_by   INT DEFAULT NULL,
  filename      VARCHAR(255) DEFAULT NULL,
  row_count     INT DEFAULT 0,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (imported_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =========================================================
-- 8. MID / FINAL EXAM RESULT LOOKUP
-- =========================================================
CREATE TABLE exam_results (
  id                 INT AUTO_INCREMENT PRIMARY KEY,
  school_id          INT DEFAULT NULL,
  student_id         INT DEFAULT NULL,
  student_no         VARCHAR(30) NOT NULL,
  student_name       VARCHAR(150) DEFAULT NULL,
  exam_type          ENUM('mid','final') NOT NULL,
  subject            VARCHAR(100) DEFAULT NULL,
  grade_group        VARCHAR(50) DEFAULT NULL,
  result_image_url   VARCHAR(255) DEFAULT NULL,
  answer_image_url   VARCHAR(255) DEFAULT NULL,
  student_password   VARCHAR(50) DEFAULT NULL,
  created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_exam_no (student_no),
  FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 9. STUDENT CONDUCT & ATTENDANCE
--    3 "signature" entries in a school year = force a family meeting
-- =========================================================
CREATE TABLE student_conduct (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  student_id    INT NOT NULL,
  school_id     INT NOT NULL,
  school_year   VARCHAR(10) DEFAULT NULL,
  type          ENUM('note','warning','signature') NOT NULL,
  note          TEXT DEFAULT NULL,
  recorded_by   INT DEFAULT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
  FOREIGN KEY (recorded_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE student_attendance (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  student_id    INT NOT NULL,
  school_id     INT NOT NULL,
  school_year   VARCHAR(10) DEFAULT NULL,
  quarter       ENUM('1','2','3','4') DEFAULT NULL,
  attend_date   DATE NOT NULL,
  status        ENUM('present','absent') NOT NULL,
  recorded_by   INT DEFAULT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_student_day (student_id, attend_date),
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
  FOREIGN KEY (recorded_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE student_ban_reports (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  student_id    INT NOT NULL,
  school_id     INT NOT NULL,
  reason        TEXT NOT NULL,
  banned_by     INT DEFAULT NULL,
  reviewed      TINYINT(1) NOT NULL DEFAULT 0,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
  FOREIGN KEY (banned_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =========================================================
-- 10. LIBRARY (folders by grade, books inside)
-- =========================================================
CREATE TABLE textbook_folders (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  school_id   INT NOT NULL,
  grade       VARCHAR(10) NOT NULL,
  UNIQUE KEY uniq_folder (school_id, grade),
  FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE textbooks (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  folder_id     INT NOT NULL,
  school_id     INT NOT NULL,
  name          VARCHAR(200) NOT NULL,
  subject       VARCHAR(100) DEFAULT NULL,
  grade         VARCHAR(10) DEFAULT NULL,
  drive_link    VARCHAR(500) DEFAULT NULL,
  uploaded_by   INT DEFAULT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (folder_id) REFERENCES textbook_folders(id) ON DELETE CASCADE,
  FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
  -- CASCADE: a book entry is the librarian's own saved content — if their
  -- staff account is deleted, the books they added are removed too.
  FOREIGN KEY (uploaded_by) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 11. NEWS / PORTAL POSTS + STUDENT COMMENTS
-- =========================================================
CREATE TABLE news (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  school_id   INT DEFAULT NULL,
  posted_by   INT DEFAULT NULL,
  title       VARCHAR(200) NOT NULL,
  body        TEXT NOT NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
  -- CASCADE: news posts (and their comments, via news_comments -> news
  -- CASCADE below) are the poster's own saved content.
  FOREIGN KEY (posted_by) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE news_comments (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  news_id     INT NOT NULL,
  student_id  INT NOT NULL,
  comment     TEXT NOT NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (news_id) REFERENCES news(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 12. MESSAGES (school -> SuperAdmin) + CHAT (everyone else)
-- =========================================================
CREATE TABLE school_messages (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  school_id     INT NOT NULL,
  sender_id     INT DEFAULT NULL,
  subject       VARCHAR(200) NOT NULL,
  body          TEXT NOT NULL,
  status        ENUM('unread','read') NOT NULL DEFAULT 'unread',
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
  -- CASCADE: this is the message the sender saved/sent — if their staff
  -- account is deleted, delete the message they sent along with it.
  FOREIGN KEY (sender_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- chat_messages is polymorphic (sender/receiver can be an admins.id, a
-- students.id, or a parents.id depending on *_type), so a single MySQL
-- FOREIGN KEY can't be declared on sender_id/receiver_id. Cleanup for this
-- table is instead handled in application code every time an account is
-- deleted — see the "delete" handlers in admins.php, students.php and
-- any future parents.php, which explicitly DELETE FROM chat_messages for
-- that exact (type, id) pair as both sender and receiver before removing
-- the account itself.
CREATE TABLE chat_messages (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  sender_type     ENUM('admin','subadmin','teacher','librarian','staff','superadmin','parent','student') NOT NULL,
  sender_id       INT NOT NULL,
  receiver_type   ENUM('admin','subadmin','teacher','librarian','staff','superadmin','parent','student') NOT NULL,
  receiver_id     INT NOT NULL,
  body            TEXT NOT NULL,
  is_read         TINYINT(1) NOT NULL DEFAULT 0,
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_thread (sender_type, sender_id, receiver_type, receiver_id)
) ENGINE=InnoDB;

-- =========================================================
-- 13. CLEANUP TRIGGERS
--    chat_messages is polymorphic (sender/receiver can point at admins,
--    students, or parents depending on *_type), so MySQL can't enforce a
--    normal FOREIGN KEY ... ON DELETE CASCADE on it. These triggers give
--    the same guarantee at the database level: no matter HOW a row in
--    admins / students / parents gets deleted — through the app's own
--    delete buttons, straight from phpMyAdmin, or as a side effect of a
--    school being deleted and its rows cascading — any chat messages that
--    account sent or received are removed as part of the same delete.
-- =========================================================
DELIMITER $$

CREATE TRIGGER trg_admins_after_delete
AFTER DELETE ON admins
FOR EACH ROW
BEGIN
  DELETE FROM chat_messages
  WHERE (sender_type = OLD.role AND sender_id = OLD.id)
     OR (receiver_type = OLD.role AND receiver_id = OLD.id);
END$$

CREATE TRIGGER trg_students_after_delete
AFTER DELETE ON students
FOR EACH ROW
BEGIN
  DELETE FROM chat_messages
  WHERE (sender_type = 'student' AND sender_id = OLD.id)
     OR (receiver_type = 'student' AND receiver_id = OLD.id);
END$$

CREATE TRIGGER trg_parents_after_delete
AFTER DELETE ON parents
FOR EACH ROW
BEGIN
  DELETE FROM chat_messages
  WHERE (sender_type = 'parent' AND sender_id = OLD.id)
     OR (receiver_type = 'parent' AND receiver_id = OLD.id);
END$$

DELIMITER ;

-- =========================================================
-- 14. APP SETTINGS  (site-wide branding, shared by every site)
--    A single row (id=1). SuperAdmin is the only role that can change
--    it (see /direschool-superadmin/settings.php); every other site's
--    header.php just reads logo_path so a new logo appears everywhere
--    the moment SuperAdmin uploads one.
-- =========================================================
CREATE TABLE app_settings (
  id          INT PRIMARY KEY DEFAULT 1,
  site_name   VARCHAR(150) NOT NULL DEFAULT '/direschool',
  logo_path   VARCHAR(255) DEFAULT NULL,
  hero_image  VARCHAR(255) DEFAULT NULL,
  about_image VARCHAR(255) DEFAULT NULL,
  cta_image   VARCHAR(255) DEFAULT NULL,
  hero_tagline VARCHAR(255) DEFAULT NULL,
  updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO app_settings (id, site_name, logo_path) VALUES
(1, '/direschool', '/shared-uploads/logo.png');

-- =========================================================
-- SEED DATA
-- =========================================================
INSERT INTO schools (name, slug, description, website_mode, website_template, status) VALUES
('Kidist Theresa School', 'kidist-theresa-school', 'A community school in Dire Dawa nurturing young learners from Grade 1 to Grade 8 with caring teachers, modern classrooms and a strong focus on reading, character and creativity.', 'template', 'classic', 'active'),
('Sabian Secondary School', 'sabian-secondary-school', 'Sabian Secondary prepares Grade 9-10 students for national examinations and beyond, with experienced teachers, science labs, and a proud record of verified transfers and exam success.', 'template', 'classic', 'active');

INSERT INTO sections (school_id, grade, section) VALUES
(1,'9','A'), (1,'9','B'), (1,'8','A'),
(2,'9','A'), (2,'8','A');

INSERT INTO students
  (school_id, student_no, full_name, english_name, gender, age, grade, section, kebele, house_no, reg_status)
VALUES
(1, '101', 'Betelhem Alemu', 'Betelhem Alemu', 'F', 15, '9', 'A', 'Kebele 05', '123', 'active'),
(1, '102', 'Yohannes Girma', 'Yohannes Girma', 'M', 15, '9', 'A', 'Kebele 02', '456', 'active'),
(1, '219339', 'Abel Tesfaye', 'Abel Tesfaye', 'M', 14, '8', 'A', 'Kebele 01', '12', 'active');

INSERT INTO report_cards
  (school_id, student_id, grade, section, school_year, teacher_name, sex, age, kebele, house_no,
   subjects, conduct, days_present, days_absent, times_tardy, total_academic_days, remarks, card_password, promotion_status)
VALUES
(1, 1, '9', 'A', '2018', 'Mr. Dawit Bekele', 'F', 15, 'Kebele 05', '123',
 JSON_OBJECT(
   'Amharic', JSON_OBJECT('1',88,'2',90,'3',85,'4',92),
   'English', JSON_OBJECT('1',80,'2',82,'3',85,'4',88),
   'Mathematics', JSON_OBJECT('1',75,'2',78,'3',80,'4',85),
   'General Science', JSON_OBJECT('1',90,'2',88,'3',91,'4',93),
   'Social Studies', JSON_OBJECT('1',84,'2',86,'3',88,'4',90),
   'Citizenship Education', JSON_OBJECT('1',95,'2',94,'3',96,'4',97),
   'Performing & Visual Arts', JSON_OBJECT('1',89,'2',90,'3',91,'4',92),
   'Information Technology', JSON_OBJECT('1',92,'2',91,'3',93,'4',94),
   'Health & Physical Education', JSON_OBJECT('1',96,'2',95,'3',97,'4',98),
   'Career & Technical Education', JSON_OBJECT('1',87,'2',88,'3',89,'4',90)
 ),
 JSON_OBJECT('1','A','2','A','3','A','4','A'),
 JSON_OBJECT('1',60,'2',58,'3',59,'4',60),
 JSON_OBJECT('1',0,'2',2,'3',1,'4',0),
 JSON_OBJECT('1',0,'2',1,'3',0,'4',0),
 JSON_OBJECT('1',60,'2',60,'3',60,'4',60),
 'Excellent performance throughout the year.', '1234', 'promoted');

INSERT INTO ministry_results
  (school_id, student_id, student_no, student_name, grade, school_year, subjects, total, average, promotion_status, promotion_label, source)
VALUES
(1, 3, '219339', 'Abel Tesfaye', '8', '2018',
 JSON_OBJECT('Amharic',82,'English',75,'Mathematics',68,'General Science',80,'Social Studies',77,'HPE & Arts',90),
 472.00, 78.67, 'promoted', 'Promoted to Grade 9', 'manual');

INSERT INTO exam_results
  (school_id, student_id, student_no, student_name, exam_type, subject, grade_group, result_image_url, student_password)
VALUES
(1, 1, '101', 'Betelhem Alemu', 'mid', 'Mathematics', 'Grade 9', 'uploads/sample-result.jpg', '1234');

INSERT INTO textbook_folders (school_id, grade) VALUES (1,'9'), (1,'8');
INSERT INTO textbooks (folder_id, school_id, name, subject, grade, drive_link) VALUES
(1, 1, 'Grade 9 Mathematics Textbook', 'Mathematics', '9', 'https://drive.google.com/'),
(1, 1, 'Grade 9 English Textbook', 'English', '9', 'https://drive.google.com/');

-- NOTE: no admin/superadmin password is inserted here — run
-- /direschool-manage/setup-admin.php once after import (see README).
