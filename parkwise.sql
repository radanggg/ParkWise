-- ParkWise MySQL Schema & Seed Data
CREATE DATABASE IF NOT EXISTS parkwise CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE parkwise;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  phone VARCHAR(40) DEFAULT NULL,
  password_hash VARCHAR(255) NOT NULL,
  status ENUM('Active','Warned','Suspended','Revoked') NOT NULL DEFAULT 'Active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE user_roles (
  user_id INT NOT NULL,
  role ENUM('customer','owner','guard','superadmin') NOT NULL,
  PRIMARY KEY (user_id, role),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE parking_lots (
  id INT AUTO_INCREMENT PRIMARY KEY,
  owner_id INT NOT NULL,
  name VARCHAR(150) NOT NULL,
  owner_display VARCHAR(150) NOT NULL,
  area VARCHAR(100) NOT NULL,
  address VARCHAR(255) NOT NULL,
  hourly_rate DECIMAL(10,2) NOT NULL DEFAULT 25.00,
  available_spaces INT NOT NULL DEFAULT 0,
  total_spaces INT NOT NULL DEFAULT 0,
  rating DECIMAL(3,2) NOT NULL DEFAULT 4.50,
  review_count INT NOT NULL DEFAULT 0,
  distance VARCHAR(30) DEFAULT '1.0 km',
  hours VARCHAR(80) DEFAULT 'Open 24/7',
  amenities VARCHAR(255) DEFAULT '24/7 CCTV,Guard on duty',
  image_url VARCHAR(500) NOT NULL,
  is_closed TINYINT(1) NOT NULL DEFAULT 0,
  FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE parking_spaces (
  id INT AUTO_INCREMENT PRIMARY KEY,
  lot_id INT NOT NULL,
  space_code VARCHAR(30) NOT NULL,
  zone VARCHAR(80) NOT NULL,
  slot_type VARCHAR(50) NOT NULL DEFAULT 'Standard',
  status ENUM('Available','Occupied','Unavailable') NOT NULL DEFAULT 'Available',
  plate_number VARCHAR(30) DEFAULT NULL,
  time_in VARCHAR(30) DEFAULT NULL,
  FOREIGN KEY (lot_id) REFERENCES parking_lots(id) ON DELETE CASCADE
);

CREATE TABLE vehicles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  make_model VARCHAR(100) NOT NULL,
  plate_number VARCHAR(30) NOT NULL,
  vehicle_type VARCHAR(40) NOT NULL DEFAULT 'Sedan',
  color VARCHAR(40) DEFAULT 'Silver',
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATE NOT NULL DEFAULT (CURRENT_DATE),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE favorites (
  user_id INT NOT NULL,
  lot_id INT NOT NULL,
  PRIMARY KEY (user_id, lot_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (lot_id) REFERENCES parking_lots(id) ON DELETE CASCADE
);

CREATE TABLE guards (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  full_name VARCHAR(120) NOT NULL,
  lot_id INT NOT NULL,
  entrance VARCHAR(100) NOT NULL DEFAULT 'Main entrance',
  shift_hours VARCHAR(80) NOT NULL DEFAULT '8 AM - 4 PM',
  status ENUM('Active','Warned','Suspended','Revoked') NOT NULL DEFAULT 'Active',
  FOREIGN KEY (lot_id) REFERENCES parking_lots(id) ON DELETE CASCADE
);

CREATE TABLE parking_sessions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  lot_id INT NOT NULL,
  plate_number VARCHAR(30) NOT NULL,
  session_date VARCHAR(30) NOT NULL,
  time_in VARCHAR(30) NOT NULL,
  time_out VARCHAR(30) DEFAULT NULL,
  started_ts BIGINT NOT NULL,
  hourly_rate DECIMAL(10,2) NOT NULL,
  hours INT DEFAULT NULL,
  total_amount DECIMAL(10,2) DEFAULT NULL,
  status ENUM('Active','Completed') NOT NULL DEFAULT 'Active',
  is_paid TINYINT(1) NOT NULL DEFAULT 0,
  receipt_no VARCHAR(40) DEFAULT NULL,
  recorded_by VARCHAR(120) NOT NULL,
  FOREIGN KEY (lot_id) REFERENCES parking_lots(id) ON DELETE CASCADE
);

CREATE TABLE reviews (
  id INT AUTO_INCREMENT PRIMARY KEY,
  lot_id INT NOT NULL,
  user_id INT DEFAULT NULL,
  reviewer_name VARCHAR(120) NOT NULL,
  rating INT NOT NULL,
  comment TEXT NOT NULL,
  review_date VARCHAR(30) NOT NULL,
  FOREIGN KEY (lot_id) REFERENCES parking_lots(id) ON DELETE CASCADE
);

CREATE TABLE owner_applications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  applicant_name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL,
  phone VARCHAR(40) DEFAULT NULL,
  business_name VARCHAR(150) DEFAULT NULL,
  submitted_date VARCHAR(40) NOT NULL,
  status ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  rejection_reason TEXT DEFAULT NULL,
  title_matches TINYINT(1) NOT NULL DEFAULT 0
);

CREATE TABLE application_documents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  application_id INT NOT NULL,
  doc_type VARCHAR(60) NOT NULL,
  name_on_doc VARCHAR(120) DEFAULT NULL,
  file_path VARCHAR(255) DEFAULT NULL,
  file_name VARCHAR(255) DEFAULT NULL,
  file_size INT DEFAULT NULL,
  status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  reject_reason TEXT DEFAULT NULL,
  FOREIGN KEY (application_id) REFERENCES owner_applications(id) ON DELETE CASCADE
);

CREATE TABLE activity_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  actor VARCHAR(120) NOT NULL,
  action VARCHAR(160) NOT NULL,
  target VARCHAR(160) NOT NULL,
  note TEXT DEFAULT NULL,
  logged_when VARCHAR(60) NOT NULL
);

-- Seed Demo Users (Password for all demo accounts: password123)
INSERT INTO users (id, full_name, email, phone, password_hash, status) VALUES
(1, 'Jordan Davis', 'jordan@email.com', '+63 917 111 2233', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Active'),
(2, 'Alex Morgan', 'alex@email.com', '+63 917 222 3344', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Active'),
(3, 'Taylor Smith', 'admin@parkwise.ph', '+63 917 333 4455', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Active'),
(4, 'Chris Reyes', 'guard@parkwise.ph', '+63 917 444 5566', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Active'),
(5, 'Liza M.', 'liza@email.com', '+63 917 555 6677', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Active');

INSERT INTO user_roles (user_id, role) VALUES
(1, 'customer'),
(2, 'customer'),
(2, 'owner'),
(3, 'superadmin'),
(4, 'guard'),
(5, 'customer');

INSERT INTO parking_lots (id, owner_id, name, owner_display, area, address, hourly_rate, available_spaces, total_spaces, rating, review_count, distance, hours, amenities, image_url, is_closed) VALUES
(1, 2, 'Northgate Parking Hub', 'Northgate Mobility', 'Iligan City', 'Quezon Avenue, Iligan City', 25.00, 6, 40, 4.80, 126, '0.8 km', 'Open 24/7', '24/7 CCTV,Covered Bays,Guard on Duty', 'https://images.unsplash.com/photo-1590674899484-d5640e854abe?auto=format&fit=crop&w=900&q=80', 0),
(2, 2, 'Central Square Parking', 'CS Property Group', 'Downtown', 'Aguinaldo Street, Iligan City', 30.00, 2, 28, 4.60, 89, '1.2 km', '6:00 AM - 11:00 PM', 'Well-lit,EV Ready,Quick Exit', 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=900&q=80', 0),
(3, 2, 'Riverside Parking Deck', 'Riverside Holdings', 'Tibanga', 'Tibanga Highway, Iligan City', 20.00, 0, 32, 4.40, 54, '2.4 km', '7:00 AM - 10:00 PM', '24/7 CCTV,Motorcycle Zone', 'https://images.unsplash.com/photo-1573348722427-f1d6819fdf98?auto=format&fit=crop&w=900&q=80', 0);

INSERT INTO parking_spaces (lot_id, space_code, zone, slot_type, status, plate_number, time_in) VALUES
(1, 'A-01', 'Main entrance', 'Covered', 'Occupied', 'ABC 1234', '08:42 AM'),
(1, 'A-02', 'Main entrance', 'Covered', 'Available', NULL, NULL),
(1, 'A-03', 'Main entrance', 'Standard', 'Unavailable', NULL, NULL),
(1, 'A-04', 'Main entrance', 'Standard', 'Available', NULL, NULL),
(1, 'B-01', 'East wing', 'Standard', 'Occupied', 'KLM 9081', '09:15 AM'),
(1, 'B-02', 'East wing', 'Motorcycle', 'Available', NULL, NULL),
(1, 'B-03', 'East wing', 'Standard', 'Available', NULL, NULL),
(1, 'B-04', 'East wing', 'Standard', 'Available', NULL, NULL),
(1, 'C-01', 'West wing', 'Standard', 'Occupied', 'DEF 4512', '10:05 AM'),
(1, 'C-02', 'West wing', 'Covered', 'Available', NULL, NULL),
(1, 'C-03', 'West wing', 'Standard', 'Unavailable', NULL, NULL),
(1, 'C-04', 'West wing', 'Standard', 'Available', NULL, NULL);

INSERT INTO vehicles (user_id, make_model, plate_number, vehicle_type, color, is_default, created_at) VALUES
(1, 'Toyota Vios', 'ABC 1234', 'Sedan', 'Silver', 1, '2026-10-20'),
(1, 'Honda Click 125i', 'XYZ 5678', 'Motorcycle', 'Matte Black', 0, '2026-10-25');

INSERT INTO favorites (user_id, lot_id) VALUES (1, 1);

INSERT INTO guards (user_id, full_name, lot_id, entrance, shift_hours, status) VALUES
(4, 'Chris Reyes', 1, 'Main entrance', '8 AM - 4 PM', 'Active'),
(NULL, 'Pat Santos', 1, 'East entrance', '4 PM - 12 AM', 'Active');

INSERT INTO parking_sessions (id, lot_id, plate_number, session_date, time_in, time_out, started_ts, hourly_rate, hours, total_amount, status, is_paid, receipt_no, recorded_by) VALUES
(1, 2, 'ABC 1234', '11/11/2026', '09:10 AM', '11:05 AM', 1731000000, 30.00, 2, 60.00, 'Completed', 1, 'PW-000001', 'Chris Reyes');

INSERT INTO reviews (lot_id, user_id, reviewer_name, rating, comment, review_date) VALUES
(1, 5, 'Liza M.', 5, 'Guard is quick and the lot is well lit.', '11/10/2026');

INSERT INTO owner_applications (id, user_id, applicant_name, email, phone, business_name, submitted_date, status, title_matches) VALUES
(1, NULL, 'Morgan Lee', 'morgan@leesparking.ph', '+63 912 888 1001', 'Lee''s Parking', 'Nov 12, 2026', 'Pending', 1),
(2, NULL, 'Sam Rivera', 'sam@riversidedeck.ph', '+63 912 888 2002', 'Riverside Deck', 'Nov 11, 2026', 'Pending', 0);

INSERT INTO application_documents (application_id, doc_type, name_on_doc, status) VALUES
(1, 'Land Title', 'Morgan Lee', 'pending'),
(1, 'Business Permit', 'Lee''s Parking', 'pending'),
(1, 'Valid ID', 'Morgan Lee', 'pending'),
(2, 'Land Title', 'Samuel R. Rivera', 'pending'),
(2, 'Business Permit', 'Riverside Deck', 'pending'),
(2, 'Valid ID', 'Sam Rivera', 'pending');

INSERT INTO activity_logs (actor, action, target, note, logged_when) VALUES
('Taylor Smith', 'Approved owner application', 'JC Parking', '', 'Nov 12 · 8:20 AM'),
('Alex Morgan', 'Added parking space', 'Northgate · A-12', '', 'Nov 12 · 8:05 AM');
