<?php   
function renderMenu($currentPage = '') {
    global $conn;

    // เช็คว่ามีการ login หรือไม่
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../logout.php');
        exit();
    }

    // ดึงข้อมูลผู้ใช้และบทบาท
    try {
        $stmt = $conn->prepare("
            SELECT u.fullname, u.profile_image, r.role_name 
            FROM users u 
            LEFT JOIN roles r ON u.role_id = r.role_id 
            WHERE u.user_id = :user_id
        ");
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // ถ้าไม่มีรูปโปรไฟล์ ใช้รูปเริ่มต้น
        $profileImage = !empty($user['profile_image']) ? $user['profile_image'] : 'https://img5.pic.in.th/file/secure-sv1/user_avatar.png';
        
    } catch(PDOException $e) {
        // ถ้าเกิดข้อผิดพลาด ใช้ค่าเริ่มต้น
        $user = [
            'fullname' => 'ไม่พบข้อมูล',
            'role_name' => 'ไม่พบข้อมูล',
            'profile_image' => 'https://img5.pic.in.th/file/secure-sv1/user_avatar.png'
        ];
    }

    $menuItems = [
        [
            'href' => 'dashboard.php',
            'icon' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" /><polyline points="9 22 9 12 15 12 15 22" />',
            'text' => 'หน้าหลัก'
        ],
        [
            'href' => 'payment.php',
            'icon' => '<rect x="2" y="5" width="20" height="14" rx="2" /><line x1="2" y1="10" x2="22" y2="10" /><path d="M12 15a2 2 0 1 0 0-4 2 2 0 0 0 0 4z" />',
            'text' => 'ชำระค่าส่วนกลาง'
        ],
        [
            'href' => 'request.php',
            'icon' => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" /><path d="M15 7l-8 8" />',
            'text' => 'การแจ้งซ่อม'
        ],
        [
            'href' => 'view_request.php', 
            'icon' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" /><polyline points="14 2 14 8 20 8" /><line x1="16" y1="13" x2="8" y2="13" /><line x1="16" y1="17" x2="8" y2="17" /><polyline points="10 9 9 9 8 9" />',
            'text' => 'รายละเอียดการแจ้งซ่อม'
        ],
        [
            'href' => 'manage_payment.php',
            'icon' => '<circle cx="9" cy="7" r="4" /><path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2" /><line x1="19" y1="8" x2="19" y2="14" /><line x1="22" y1="11" x2="16" y2="11" />',
            'text' => 'จัดการค่าส่วนกลาง'
        ],
        [
            'href' => 'manage_request.php',
            'icon' => '<circle cx="12" cy="12" r="3" /><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" />',
            'text' => 'จัดการแจ้งซ่อม'
        ],
        [
            'href' => 'manage_users.php',
            'icon' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M23 21v-2a4 4 0 0 0-3-3.87" /><path d="M16 3.13a4 4 0 0 1 0 7.75" />',
            'text' => 'จัดการข้อมูลผู้ใช้'
        ],
        [
            'href' => 'permission.php',
            'icon' => '<rect x="3" y="11" width="18" height="11" rx="2" ry="2" /><path d="M7 11V7a5 5 0 0 1 10 0v4" /><circle cx="12" cy="16" r="1" />',
            'text' => 'จัดการสิทธิ์การใช้งาน'
        ]
    ];

    echo '<div class="px-3">
            <!-- Profile Section -->
            <div class="py-4 pl-1 mb-6">
                <div class="flex items-center">
                    <div class="relative flex-shrink-0">
                        <img src="'.$profileImage.'"
                            alt="Profile"
                            class="w-12 h-12 rounded-full border-2 border-white shadow-md hover:scale-105 transition-transform duration-200">
                    </div>
                    <div class="ml-4">
                        <h3 class="text-white font-semibold text-sm opacity-0 transition-opacity duration-500 ease-in-out whitespace-nowrap">'.htmlspecialchars($user['fullname']).'</h3>
                        <p class="text-blue-100 text-xs opacity-0 transition-opacity duration-500 ease-in-out whitespace-nowrap">'.htmlspecialchars($user['role_name']).'</p>
                    </div>
                </div>
            </div>';

    echo '<div class="mb-4">
            <h2 class="text-xs font-bold text-white/80 px-4 mb-2">Menu</h2>
            <nav class="space-y-2">';

    // ดึงสิทธิ์การเข้าถึงเมนูจาก session
    $menuAccess = $_SESSION['menu_access'] ?? [];

    foreach ($menuItems as $index => $item) {
        // เช็คว่ามีสิทธิ์เข้าถึงเมนูนี้หรือไม่
        if (in_array($index + 1, $menuAccess)) {
            $activeClass = basename($_SERVER['PHP_SELF']) === $item['href'] ? 'bg-white/10' : '';
            echo '<a href="'.$item['href'].'" class="flex items-center px-4 py-2.5 text-white hover:bg-white/10 rounded-lg transition-all duration-200 '.$activeClass.'">
                    <svg class="w-5 h-5 flex-shrink-0 text-white/80 transition-colors" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        '.$item['icon'].'
                    </svg>
                    <span class="ml-3 opacity-0 transition-opacity duration-500 ease-in-out text-sm whitespace-nowrap">'.$item['text'].'</span>
                  </a>';
        }
    }

    echo '</nav></div>';

    // Others Section
    echo '<div class="mb-4">
            <h2 class="text-xs font-bold text-white/80 px-4 mb-2">Others</h2>
            <nav class="space-y-2">
                <a href="profile.php" class="flex items-center px-4 py-2.5 text-white hover:bg-white/10 rounded-lg transition-all duration-200">
                    <svg class="w-5 h-5 flex-shrink-0 text-white/80 transition-colors" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                        <circle cx="12" cy="7" r="4" />
                    </svg>
                    <span class="ml-3 opacity-0 transition-opacity duration-500 ease-in-out text-sm whitespace-nowrap">แก้ไขโปรไฟล์</span>
                </a>

                <a href="../logout.php" class="flex items-center px-4 py-2.5 text-white bg-red-400 hover:bg-red-500 rounded-lg transition-all duration-200">
                    <svg class="w-5 h-5 flex-shrink-0 text-white transition-colors" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                        <polyline points="16 17 21 12 16 7" />
                        <line x1="21" y1="12" x2="9" y2="12" />
                    </svg>
                    <span class="ml-3 opacity-0 transition-opacity duration-500 ease-in-out text-sm whitespace-nowrap">ออกจากระบบ</span>
                </a>
            </nav>
          </div>';

    echo '</div>';
}
?> 