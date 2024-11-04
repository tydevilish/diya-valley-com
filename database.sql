-- ตารางบทบาท
CREATE TABLE roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ตารางเมนู
CREATE TABLE menus (
    menu_id INT PRIMARY KEY AUTO_INCREMENT,
    menu_name VARCHAR(100) NOT NULL,
    menu_path VARCHAR(255) NOT NULL,
    menu_icon VARCHAR(255),
    menu_order INT DEFAULT 0,
    menu_section VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ตารางสิทธิ์ของบทบาท
CREATE TABLE role_permissions (
    role_id INT,
    menu_id INT,
    can_access BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (role_id, menu_id),
    FOREIGN KEY (role_id) REFERENCES roles(role_id) ON DELETE CASCADE,
    FOREIGN KEY (menu_id) REFERENCES menus(menu_id) ON DELETE CASCADE
);

-- เพิ่มบทบาท
INSERT INTO roles (role_id, role_name) VALUES
(1, 'ผู้ดูแลระบบ'),
(2, 'ผู้ใช้งานทั่วไป');

-- เพิ่มเมนู
-- เพิ่มเมนู
INSERT INTO menus (menu_id, menu_name, menu_path, menu_icon, menu_order, menu_section, created_at) VALUES
(1, 'หน้าหลัก', 'dashboard.php', '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" /><polyline points="9 22 9 12 15 12 15 22" />', 1, 'Menu', '2024-11-04 23:42:16'),
(2, 'ชำระค่าส่วนกลาง', 'payment.php', '<rect x="2" y="5" width="20" height="14" rx="2" /><line x1="2" y1="10" x2="22" y2="10" />', 2, 'Menu', '2024-11-04 23:42:16'),
(3, 'การแจ้งซ่อม', 'request.php', '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" />', 3, 'Menu', '2024-11-04 23:42:16'),
(4, 'รายละเอียดการแจ้งซ่อม', 'view_request.php', '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" /><polyline points="14 2 14 8 20 8" /><line x1="16" y1="13" x2="8" y2="13" /><line x1="16" y1="17" x2="8" y2="17" /><polyline points="10 9 9 9 8 9" />', 4, 'Menu', '2024-11-04 23:42:16'),
(5, 'จัดการค่าส่วนกลาง', 'manage_payment.php', '<circle cx="9" cy="7" r="4" /><path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2" />', 5, 'Menu', '2024-11-04 23:42:16'),
(6, 'จัดการแจ้งซ่อม', 'manage_request.php', '<circle cx="12" cy="12" r="3" /><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" />', 6, 'Menu', '2024-11-04 23:42:16'),
(7, 'จัดการข้อมูลผู้ใช้', 'manage_users.php', '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M23 21v-2a4 4 0 0 0-3-3.87" /><path d="M16 3.13a4 4 0 0 1 0 7.75" />', 7, 'Menu', '2024-11-04 23:42:16'),
(8, 'จัดการสิทธิ์การใช้งาน', 'permission.php', '<rect x="3" y="11" width="18" height="11" rx="2" ry="2" /><path d="M7 11V7a5 5 0 0 1 10 0v4" /><circle cx="12" cy="16" r="1" />', 8, 'Menu', '2024-11-04 23:42:16');

-- กำหนดสิทธิ์สำหรับผู้ดูแลระบบ (เข้าถึงทุกเมนู)
INSERT INTO role_permissions (role_id, menu_id, can_access) 
SELECT 1, menu_id, TRUE FROM menus;

-- สร้างตาราง users
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(role_id)
);

-- เพิ่มข้อมูลผู้ใช้
INSERT INTO users (user_id, username, password, role_id, created_at) VALUES
(1, 'admin', 'admin', 1, '2024-11-04 23:48:22'),
(2, 'users', 'users', 2, '0000-00-00 00:00:00');

#######################################################################

