CREATE DATABASE IF NOT EXISTS nursing_discharge CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nursing_discharge;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(30) NOT NULL DEFAULT 'doctor',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nursing_home_name VARCHAR(200) NOT NULL,
    establishment_type VARCHAR(200),
    registration_no VARCHAR(100),
    address TEXT,
    phone VARCHAR(100),
    email VARCHAR(150),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE discharge_records (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    record_number VARCHAR(30) NOT NULL UNIQUE,
    patient_registration_no VARCHAR(100),
    patient_name VARCHAR(150) NOT NULL,
    age VARCHAR(20),
    gender VARCHAR(20),
    guardian_name VARCHAR(150),
    mobile VARCHAR(30),
    address TEXT,
    village VARCHAR(150),
    post_office VARCHAR(150),
    police_station VARCHAR(150),
    district VARCHAR(150),
    block VARCHAR(150),
    gp_municipality VARCHAR(150),
    pin VARCHAR(20),
    admission_date DATE,
    admission_time TIME,
    discharge_date DATE NOT NULL,
    discharge_time TIME NOT NULL,
    case_type VARCHAR(50),
    treated_by_doctor VARCHAR(150),
    reference_doctor VARCHAR(150),
    diagnosis_summary TEXT,
    other_details TEXT,
    created_by INT UNSIGNED,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE discharge_record_medicines (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    discharge_record_id INT UNSIGNED NOT NULL,
    medicine_name VARCHAR(255) NOT NULL,
    dosage VARCHAR(150),
    frequency VARCHAR(150),
    duration VARCHAR(150),
    instructions TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (discharge_record_id) REFERENCES discharge_records(id) ON DELETE CASCADE
);

INSERT INTO settings (nursing_home_name, establishment_type, registration_no, address, phone, email)
VALUES ('New Life Nursing Home', 'Clinical Establishment', '50339074', 'Uttar Darua, Darua, Contai, Purba Medinipur', '8918782920 / 6294264974', 'newlifenursinghome777@gmail.com');

-- Initial password for both accounts: change-me
INSERT INTO users (username, password, role) VALUES
('admin', '$2y$12$/4NyOuUSkphvRbx.7LYWc.rBTl7YiZHdbqRt6D8DnOkdPcQjWbMC2', 'admin'),
('doctor', '$2y$12$/4NyOuUSkphvRbx.7LYWc.rBTl7YiZHdbqRt6D8DnOkdPcQjWbMC2', 'doctor');
