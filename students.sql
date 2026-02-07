CREATE DATABASE IF NOT EXISTS uap_verification;
USE uap_verification;

CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) UNIQUE NOT NULL,
    registration_no VARCHAR(30) NOT NULL,
    name VARCHAR(100) NOT NULL,
    date_of_birth VARCHAR(20) DEFAULT NULL,
    department VARCHAR(100) NOT NULL,
    degree VARCHAR(150) NOT NULL,
    cgpa DECIMAL(3,2) NOT NULL,
    passing_year VARCHAR(10) NOT NULL,
    certificate_no VARCHAR(20) NOT NULL,
    photo VARCHAR(255) DEFAULT NULL
);

INSERT INTO students
(student_id, registration_no, name, date_of_birth, department, degree, cgpa, passing_year, certificate_no, photo)
VALUES
('2181011017', 'UU18108521', 'RANA AHMED', '10.02.2001', 'Civil Engineering', 'Bachelor of Science in Civil Engineering', 3.70, '2023', '22429', 'uploads/students/2181011017.jpg'),
('2181011018', 'UU18108522', 'RAHAT ISLAM', '25.08.2001', 'Computer Science & Engineering', 'Bachelor of Science in CSE', 3.45, '2022', '22430', 'uploads/students/63718627.jpg'),
('63718627', '63718627', 'Habibur Rahman', '12.03.2000', 'Computer Science & Engineering', 'Bachelor of Science in Computer Science & Engineering', 3.85, '2023', '5734', 'uploads/students/63718627.jpg');
