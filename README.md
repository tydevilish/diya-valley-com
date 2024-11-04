# ระบบจัดการหมู่บ้าน

- ระบบชำระค่าส่วนกลาง
- ระบบแจ้งซ่อม
- ระบบประกาศข่าวสาร
- จัดการโปรไฟล์ผู้ใช้

## Using
- PHP
- HTML/CSS
- Tailwind CSS
- JavaScript

## Installation
- git clone https://github.com/tydevilish/diya-valley.git

## Database Create
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('users', 'admin', 'group') NOT NULL
);
