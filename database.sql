-- NTTN Portal MVP database schema (MySQL 8)
CREATE DATABASE IF NOT EXISTS nttn_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nttn_portal;

CREATE TABLE roles (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) UNIQUE NOT NULL,
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL
);

CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(190) UNIQUE NOT NULL,
  phone VARCHAR(30),
  password VARCHAR(255) NOT NULL,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  FOREIGN KEY (role_id) REFERENCES roles(id)
);

CREATE TABLE resellers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  reseller_code VARCHAR(50) UNIQUE NOT NULL,
  company_name VARCHAR(200) NOT NULL,
  address TEXT,
  phone VARCHAR(30),
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE customers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reseller_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(200) NOT NULL,
  phone VARCHAR(30),
  address TEXT,
  district VARCHAR(100),
  thana VARCHAR(100),
  latitude DECIMAL(10,7),
  longitude DECIMAL(10,7),
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  FOREIGN KEY (reseller_id) REFERENCES resellers(id)
);

CREATE TABLE survey_requests (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  request_no VARCHAR(40) UNIQUE NOT NULL,
  reseller_id BIGINT UNSIGNED NOT NULL,
  customer_id BIGINT UNSIGNED NOT NULL,
  bandwidth_mbps INT NOT NULL,
  service_type VARCHAR(80),
  preferred_nttn VARCHAR(120),
  description TEXT,
  status VARCHAR(50) NOT NULL DEFAULT 'SURVEY_REQUESTED',
  requested_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  FOREIGN KEY (reseller_id) REFERENCES resellers(id),
  FOREIGN KEY (customer_id) REFERENCES customers(id)
);

CREATE TABLE survey_feedback (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  survey_request_id BIGINT UNSIGNED NOT NULL,
  provider VARCHAR(120),
  feasible TINYINT(1),
  route TEXT,
  fiber_distance_km DECIMAL(10,3),
  nearest_pop VARCHAR(200),
  port VARCHAR(100),
  vlan VARCHAR(100),
  installation_cost DECIMAL(14,2) DEFAULT 0,
  monthly_cost DECIMAL(14,2) DEFAULT 0,
  expected_delivery_days INT,
  feedback TEXT,
  created_by BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  FOREIGN KEY (survey_request_id) REFERENCES survey_requests(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE approvals (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entity_type VARCHAR(80) NOT NULL,
  entity_id BIGINT UNSIGNED NOT NULL,
  approval_stage VARCHAR(50) NOT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'PENDING',
  remarks TEXT,
  approved_by BIGINT UNSIGNED NULL,
  approved_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  FOREIGN KEY (approved_by) REFERENCES users(id)
);

CREATE TABLE work_orders (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  work_order_no VARCHAR(40) UNIQUE NOT NULL,
  survey_request_id BIGINT UNSIGNED NOT NULL,
  assigned_to BIGINT UNSIGNED NULL,
  status VARCHAR(50) NOT NULL DEFAULT 'ISSUED',
  deadline DATE NULL,
  instructions TEXT,
  completed_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  FOREIGN KEY (survey_request_id) REFERENCES survey_requests(id),
  FOREIGN KEY (assigned_to) REFERENCES users(id)
);

CREATE TABLE links (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  link_id VARCHAR(60) UNIQUE NOT NULL,
  customer_id BIGINT UNSIGNED NOT NULL,
  reseller_id BIGINT UNSIGNED NOT NULL,
  work_order_id BIGINT UNSIGNED NULL,
  bandwidth_mbps INT NOT NULL,
  nttn_provider VARCHAR(120),
  circuit_id VARCHAR(120),
  vlan VARCHAR(100),
  port VARCHAR(100),
  status VARCHAR(40) NOT NULL DEFAULT 'PENDING',
  activation_date DATE NULL,
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  FOREIGN KEY (customer_id) REFERENCES customers(id),
  FOREIGN KEY (reseller_id) REFERENCES resellers(id),
  FOREIGN KEY (work_order_id) REFERENCES work_orders(id)
);

CREATE TABLE noc_handovers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  work_order_id BIGINT UNSIGNED NOT NULL,
  link_id BIGINT UNSIGNED NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'RECEIVED',
  remarks TEXT,
  handed_over_at TIMESTAMP NULL,
  verified_at TIMESTAMP NULL,
  verified_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  FOREIGN KEY (work_order_id) REFERENCES work_orders(id),
  FOREIGN KEY (link_id) REFERENCES links(id),
  FOREIGN KEY (verified_by) REFERENCES users(id)
);

CREATE TABLE noc_tests (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  noc_handover_id BIGINT UNSIGNED NOT NULL,
  physical_link TINYINT(1) DEFAULT 0,
  optical_power TINYINT(1) DEFAULT 0,
  vlan_ok TINYINT(1) DEFAULT 0,
  bandwidth_ok TINYINT(1) DEFAULT 0,
  ping_ok TINYINT(1) DEFAULT 0,
  latency_ok TINYINT(1) DEFAULT 0,
  packet_loss_ok TINYINT(1) DEFAULT 0,
  monitoring_ok TINYINT(1) DEFAULT 0,
  result VARCHAR(30) DEFAULT 'PENDING',
  remarks TEXT,
  tested_by BIGINT UNSIGNED,
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  FOREIGN KEY (noc_handover_id) REFERENCES noc_handovers(id),
  FOREIGN KEY (tested_by) REFERENCES users(id)
);

CREATE TABLE billing (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  link_id BIGINT UNSIGNED NOT NULL,
  installation_charge DECIMAL(14,2) DEFAULT 0,
  monthly_charge DECIMAL(14,2) DEFAULT 0,
  nttn_cost DECIMAL(14,2) DEFAULT 0,
  billing_start_date DATE NULL,
  due_date DATE NULL,
  invoice_no VARCHAR(80),
  status VARCHAR(40) DEFAULT 'PENDING',
  completed_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  FOREIGN KEY (link_id) REFERENCES links(id),
  FOREIGN KEY (completed_by) REFERENCES users(id)
);

CREATE TABLE tickets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ticket_no VARCHAR(50) UNIQUE NOT NULL,
  link_id BIGINT UNSIGNED NOT NULL,
  created_by BIGINT UNSIGNED NOT NULL,
  assigned_to BIGINT UNSIGNED NULL,
  type VARCHAR(60) NOT NULL,
  priority VARCHAR(30) DEFAULT 'NORMAL',
  description TEXT,
  status VARCHAR(40) DEFAULT 'OPEN',
  resolved_at TIMESTAMP NULL,
  closed_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  FOREIGN KEY (link_id) REFERENCES links(id),
  FOREIGN KEY (created_by) REFERENCES users(id),
  FOREIGN KEY (assigned_to) REFERENCES users(id)
);

CREATE TABLE service_requests (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  request_no VARCHAR(50) UNIQUE NOT NULL,
  link_id BIGINT UNSIGNED NOT NULL,
  type VARCHAR(30) NOT NULL, -- UPGRADE / DOWNGRADE
  old_bandwidth_mbps INT NOT NULL,
  requested_bandwidth_mbps INT NOT NULL,
  reason TEXT,
  status VARCHAR(40) DEFAULT 'PENDING',
  created_by BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  FOREIGN KEY (link_id) REFERENCES links(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE chat_rooms (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  link_id BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  FOREIGN KEY (link_id) REFERENCES links(id)
);

CREATE TABLE chat_messages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  chat_room_id BIGINT UNSIGNED NOT NULL,
  sender_id BIGINT UNSIGNED NOT NULL,
  message TEXT NOT NULL,
  attachment_path VARCHAR(500),
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  FOREIGN KEY (chat_room_id) REFERENCES chat_rooms(id),
  FOREIGN KEY (sender_id) REFERENCES users(id)
);

CREATE TABLE notifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(200) NOT NULL,
  message TEXT NOT NULL,
  entity_type VARCHAR(80),
  entity_id BIGINT UNSIGNED,
  read_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE activity_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  entity_type VARCHAR(80) NOT NULL,
  entity_id BIGINT UNSIGNED NOT NULL,
  action VARCHAR(100) NOT NULL,
  from_status VARCHAR(50),
  to_status VARCHAR(50),
  remarks TEXT,
  created_at TIMESTAMP NULL,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

INSERT INTO roles (name, created_at, updated_at) VALUES
('super_admin', NOW(), NOW()),
('md', NOW(), NOW()),
('reseller', NOW(), NOW()),
('transmission', NOW(), NOW()),
('noc', NOW(), NOW()),
('billing', NOW(), NOW());
