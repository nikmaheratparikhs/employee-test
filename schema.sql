-- Interview & Testing Portal Schema
-- Compatible with MySQL 8.x (XAMPP MariaDB also supported)

CREATE DATABASE IF NOT EXISTS interview_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE interview_portal;

-- Users (Admin and Employees)
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','employee') NOT NULL DEFAULT 'employee',
  interview_date DATETIME NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_users_role (role),
  INDEX idx_users_interview_date (interview_date)
) ENGINE=InnoDB;

-- Password reset tokens
CREATE TABLE IF NOT EXISTS password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token VARCHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  used TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_password_resets_user (user_id),
  INDEX idx_password_resets_token (token)
) ENGINE=InnoDB;

-- Tests/Assessments
CREATE TABLE IF NOT EXISTS tests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  description TEXT NULL,
  category VARCHAR(100) NULL,
  difficulty ENUM('beginner','intermediate','advanced') NOT NULL DEFAULT 'beginner',
  time_limit_minutes INT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_by INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_tests_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_tests_active (is_active)
) ENGINE=InnoDB;

-- Questions for tests
CREATE TABLE IF NOT EXISTS questions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  test_id INT NOT NULL,
  question_text TEXT NOT NULL,
  question_type ENUM('single','multiple','text') NOT NULL DEFAULT 'single',
  points DECIMAL(5,2) NOT NULL DEFAULT 1.00,
  correct_text_answer TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_questions_test FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE CASCADE,
  INDEX idx_questions_test (test_id)
) ENGINE=InnoDB;

-- Answer choices for (single/multiple) questions
CREATE TABLE IF NOT EXISTS choices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  question_id INT NOT NULL,
  choice_text TEXT NOT NULL,
  is_correct TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_choices_question FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
  INDEX idx_choices_question (question_id)
) ENGINE=InnoDB;

-- Assign tests to employees
CREATE TABLE IF NOT EXISTS assignments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  test_id INT NOT NULL,
  employee_id INT NOT NULL,
  assigned_by INT NULL,
  assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  attempt_limit INT NOT NULL DEFAULT 1,
  status ENUM('assigned','in_progress','completed') NOT NULL DEFAULT 'assigned',
  CONSTRAINT fk_assignments_test FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE CASCADE,
  CONSTRAINT fk_assignments_employee FOREIGN KEY (employee_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_assignments_assigned_by FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL,
  UNIQUE KEY uq_assignment_unique (test_id, employee_id),
  INDEX idx_assignments_status (status)
) ENGINE=InnoDB;

-- Attempts at a given assignment
CREATE TABLE IF NOT EXISTS attempts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  assignment_id INT NOT NULL,
  started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  submitted_at DATETIME NULL,
  score_decimal DECIMAL(7,2) NOT NULL DEFAULT 0.00,
  total_points DECIMAL(7,2) NOT NULL DEFAULT 0.00,
  percent DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  CONSTRAINT fk_attempts_assignment FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
  INDEX idx_attempts_assignment (assignment_id)
) ENGINE=InnoDB;

-- Answers per question within an attempt
CREATE TABLE IF NOT EXISTS answers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  attempt_id INT NOT NULL,
  question_id INT NOT NULL,
  answer_text TEXT NULL,
  CONSTRAINT fk_answers_attempt FOREIGN KEY (attempt_id) REFERENCES attempts(id) ON DELETE CASCADE,
  CONSTRAINT fk_answers_question FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
  INDEX idx_answers_attempt (attempt_id),
  INDEX idx_answers_question (question_id)
) ENGINE=InnoDB;

-- Selected choices for (single/multiple) answers
CREATE TABLE IF NOT EXISTS answer_choices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  answer_id INT NOT NULL,
  choice_id INT NOT NULL,
  CONSTRAINT fk_answer_choices_answer FOREIGN KEY (answer_id) REFERENCES answers(id) ON DELETE CASCADE,
  CONSTRAINT fk_answer_choices_choice FOREIGN KEY (choice_id) REFERENCES choices(id) ON DELETE CASCADE,
  UNIQUE KEY uq_answer_choice_unique (answer_id, choice_id)
) ENGINE=InnoDB;
