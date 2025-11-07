DROP DATABASE IF EXISTS vms_db;
CREATE DATABASE vms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vms_db;

-- Admin table
CREATE TABLE IF NOT EXISTS tbl_admin (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_name VARCHAR(100) NOT NULL,
  emailid VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL
);

-- Members table
CREATE TABLE IF NOT EXISTS tbl_members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  member_name VARCHAR(100) NOT NULL,
  emailid VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  department VARCHAR(100) NULL,
  graduation_year YEAR NULL,
  linkedin VARCHAR(255) NULL,
  instagram VARCHAR(255) NULL,
  whatsapp VARCHAR(30) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Events table
CREATE TABLE IF NOT EXISTS tbl_events (
  event_id INT AUTO_INCREMENT PRIMARY KEY,
  event_name VARCHAR(150) NOT NULL,
  event_date DATE NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Department table
CREATE TABLE IF NOT EXISTS tbl_department (
  id INT AUTO_INCREMENT PRIMARY KEY,
  department VARCHAR(100) NOT NULL UNIQUE,
  status TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Visitors table
CREATE TABLE IF NOT EXISTS tbl_visitors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  event_id INT NOT NULL,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(150) NULL,
  mobile VARCHAR(30) NULL,
  address TEXT NULL,
  department VARCHAR(100) NULL,
  gender ENUM('Male', 'Female', 'Other') NULL,
  year_of_graduation YEAR NULL,
  in_time DATETIME NULL,
  out_time DATETIME NULL,
  status TINYINT(1) DEFAULT 1,
  added_by INT NULL COMMENT 'User ID who added this visitor',
  relation VARCHAR(100) NULL COMMENT 'Relation to existing guest',
  visitor_type ENUM('regular', 'spot_entry', 'additional_member') DEFAULT 'regular',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_visitors_event FOREIGN KEY (event_id)
    REFERENCES tbl_events(event_id) ON DELETE CASCADE,
  CONSTRAINT fk_visitors_added_by FOREIGN KEY (added_by)
    REFERENCES tbl_members(id) ON DELETE SET NULL
);

-- Event registrations table
CREATE TABLE IF NOT EXISTS event_registrations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL,
  event VARCHAR(100) NOT NULL,
  event_date DATE NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_registrations_event (event),
  INDEX idx_registrations_email (email),
  CONSTRAINT fk_event_reg_user FOREIGN KEY (user_id)
    REFERENCES tbl_members(id) ON DELETE CASCADE
);

-- Inventory table
CREATE TABLE IF NOT EXISTS tbl_inventory (
  id INT AUTO_INCREMENT PRIMARY KEY,
  item_name VARCHAR(100) NOT NULL,
  total_stock INT NOT NULL DEFAULT 0,
  used_count INT NOT NULL DEFAULT 0,
  status VARCHAR(50) DEFAULT 'Available',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Goodies distribution table
CREATE TABLE IF NOT EXISTS tbl_goodies_distribution (
  id INT AUTO_INCREMENT PRIMARY KEY,
  visitor_id INT NULL,
  goodie_name VARCHAR(100) NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  distribution_time DATETIME DEFAULT CURRENT_TIMESTAMP,
  remarks TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_goodies_visitor FOREIGN KEY (visitor_id)
    REFERENCES tbl_visitors(id) ON DELETE SET NULL
);