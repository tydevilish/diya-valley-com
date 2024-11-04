<?php 
session_start();
require_once '../config/config.php';
include '../components/Menu.php';
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>| ระบบจัดการหมู่บ้าน </title>
    <link rel="icon" href="https://devcm.info/img/favicon.png">
    <link rel="stylesheet" href="../src/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-modern">
    <div class="flex">
        <div id="sidebar" class="fixed top-0 left-0 h-full w-20 transition-all duration-300 ease-in-out bg-gradient-to-b from-blue-600 to-blue-500 shadow-xl">
            <!-- ย้ายปุ่ม toggle ไปด้านล่าง -->
            <button id="toggleSidebar" class="absolute -right-3 bottom-24 bg-blue-800 text-white rounded-full p-1 shadow-lg hover:bg-blue-900">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
                <!-- Menu Section -->
                <?php renderMenu(); ?>
            </div>
        </div>

    </div>

    <div class="flex-1 ml-20">
        <!-- Top Navigation -->
        <nav class="bg-white shadow-sm px-6 py-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <h1 class="text-2xl font-bold text-eva ml-4">จัดการสิทธิ์การใช้งาน</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <button class="p-2 rounded-full hover:bg-gray-100 relative" onclick="toggleNotifications()">
                            <!-- จุดแจ้งเตือนสีแดง -->
                            <div class="absolute top-2 right-2.5 w-2 h-2 bg-red-500 rounded-full flex items-center justify-center">
                            </div>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </button>

                        <!-- เพิ่มกล่องแจ้งเตือนใต้กระดิ่ง -->
                        <div id="notificationDropdown" class="hidden absolute right-0 top-full mt-2 w-80 bg-white rounded-lg shadow-xl z-50">
                            <div class="p-4">
                                <div class="space-y-4">
                                    <!-- รายการแจ้งเตือน -->
                                    <a href="payment.php" class="block p-3 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0">
                                                <svg class="w-6 h-6 text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <rect x="2" y="5" width="20" height="14" rx="2" />
                                                    <line x1="2" y1="10" x2="22" y2="10" />
                                                </svg>
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-sm font-medium text-gray-800">ค่าส่วนกลางประจำเดือนมีนาคม 2567</p>
                                                <p class="text-xs text-gray-500">รอการชำระเงิน 500 บาท</p>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- เพิ่ม JavaScript ก่อน closing body tag -->
                    <script>
                        function toggleNotifications() {
                            const dropdown = document.getElementById('notificationDropdown');
                            dropdown.classList.toggle('hidden');

                            // ปิดเมื่อคลิกที่อื่น
                            document.addEventListener('click', function closeDropdown(e) {
                                if (!e.target.closest('#notificationDropdown') && !e.target.closest('button')) {
                                    dropdown.classList.add('hidden');
                                    document.removeEventListener('click', closeDropdown);
                                }
                            });
                        }
                    </script>
                    <a href="https://devcm.info" class="p-2 rounded-full hover:bg-gray-100">
                        <img src="https://devcm.info/img/favicon.png" class="h-6 w-6" alt="User icon">
                    </a>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="p-6">
            <!-- หัวข้อและปุ่มเพิ่มสิทธิ์ -->
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold text-gray-800">รายการสิทธิ์การใช้งาน</h2>
                <button onclick="showAddModal()"
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors duration-200 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    เพิ่มสิทธิ์ใหม่
                </button>
            </div>

            <!-- ตารางิทธิ์ -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ชื่อสิทธิ์</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">เมนูที่เข้าถึงได้</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">จำนวนเมนู</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php
                        $stmt = $conn->query("
                            SELECT r.*, 
                            GROUP_CONCAT(DISTINCT m.menu_name) as menu_names,
                            COUNT(DISTINCT rp.menu_id) as menu_count
                            FROM roles r
                            LEFT JOIN role_permissions rp ON r.role_id = rp.role_id
                            LEFT JOIN menus m ON rp.menu_id = m.menu_id
                            GROUP BY r.role_id
                        ");
                        while ($role = $stmt->fetch(PDO::FETCH_ASSOC)):
                        ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($role['role_name']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-2">
                                        <?php
                                        $menus = explode(',', $role['menu_names']);
                                        foreach ($menus as $menu) {
                                            if ($menu) {
                                                echo '<span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded">' .
                                                    htmlspecialchars($menu) . '</span>';
                                            }
                                        }
                                        ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo $role['menu_count']; ?> เมนู
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button onclick="showEditModal(<?php echo $role['role_id']; ?>)" 
                                            class="text-blue-600 hover:text-blue-900 mr-4">แก้ไข</button>
                                    <button onclick="deleteRole(<?php echo $role['role_id']; ?>)" 
                                            class="text-red-600 hover:text-red-900">ลบ</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal เพิ่ม/แก้ไขสิทธิ์ -->
    <div id="permissionModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 p-5 border w-full max-w-xl shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center pb-3">
                <h3 class="text-xl font-bold text-gray-900" id="modalTitle">เพิ่มสิทธิ์ใหม่</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-500">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="permissionForm" class="mt-4">
                <input type="hidden" id="roleId" name="role_id">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        ชื่อสิทธิ์ <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="permissionName" name="permissionName" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        เมนูที่สามารถเข้าถึง <span class="text-red-500">*</span>
                    </label>
                    <div class="space-y-2">
                        <?php
                        $stmt = $conn->query("SELECT * FROM menus ORDER BY menu_order");
                        while ($menu = $stmt->fetch(PDO::FETCH_ASSOC)):
                        ?>
                        <label class="flex items-center">
                            <input type="checkbox" name="menus[]" value="<?php echo $menu['menu_id']; ?>" 
                                   class="rounded text-blue-600">
                            <span class="ml-2 text-sm text-gray-700"><?php echo htmlspecialchars($menu['menu_name']); ?></span>
                        </label>
                        <?php endwhile; ?>
                    </div>
                </div>

                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeModal()"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                        ยกเลิก
                    </button>
                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        บันทึก
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- เพิ่ม Script -->
    <script>
        function closeModal() {
            document.getElementById('permissionModal').classList.add('hidden');
            document.getElementById('permissionForm').reset();
        }

        async function showAddModal() {
            document.getElementById('modalTitle').textContent = 'เพิ่มสิทธิ์ใหม่';
            document.getElementById('permissionForm').reset();
            document.getElementById('roleId').value = '';
            document.getElementById('permissionModal').classList.remove('hidden');
        }

        async function showEditModal(id) {
            try {
                const response = await fetch(`../api/get_role.php?id=${id}`);
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('modalTitle').textContent = 'แก้ไขสิทธิ์';
                    document.getElementById('roleId').value = id;
                    document.getElementById('permissionName').value = data.role_name;
                    
                    // รีเซ็ตและเลือก checkbox ตามสิทธิ์ที่มี
                    const checkboxes = document.querySelectorAll('input[name="menus[]"]');
                    checkboxes.forEach(cb => {
                        cb.checked = data.menu_access.includes(parseInt(cb.value));
                    });
                    
                    document.getElementById('permissionModal').classList.remove('hidden');
                } else {
                    alert(data.message || 'เกิดข้อผิดพลาดในการโหลดข้อมูล');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาดในการโหลดข้อมูล');
            }
        }

        async function deleteRole(id) {
            if (confirm('คุณต้องการลบสิทธิ์นี้ใช่หรือไม่?')) {
                try {
                    const response = await fetch('../api/delete_role.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ role_id: id })
                    });
                    
                    const result = await response.json();
                    if (result.success) {
                        alert('ลบข้อมูลเรียบร้อยแล้ว');
                        location.reload();
                    } else {
                        alert(result.message || 'เกิดข้อผิดพลาดในการลบข้อมูล');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('เกิดข้อผิดพลาดในการลบข้อมูล');
                }
            }
        }

        document.getElementById('permissionForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const selectedMenus = document.querySelectorAll('input[name="menus[]"]:checked');
            if (selectedMenus.length === 0) {
                alert('กรุณาเลือกเมนูที่สามารถเข้าถึงอย่างน้อย 1 รายการ');
                return;
            }

            try {
                const formData = new FormData(this);
                const response = await fetch('../api/save_role.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                if (result.success) {
                    alert('บันทึกข้อมูลเรียบร้อย');
                    location.reload();
                } else {
                    alert(result.message || 'เกิดข้อผิดพลาดในการบันทึกข้อมูล');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาดในการบันทึกข้อมูล');
            }
        });

        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('toggleSidebar');
        const toggleIcon = toggleBtn.querySelector('svg path');
        const textElements = document.querySelectorAll('.opacity-0');
        let isExpanded = false;

        toggleBtn.addEventListener('click', () => {
            isExpanded = !isExpanded;
            if (isExpanded) {
                sidebar.classList.remove('w-20');
                sidebar.classList.add('w-64');
                toggleIcon.setAttribute('d', 'M15 19l-7-7 7-7');
                textElements.forEach(el => el.classList.remove('opacity-0'));
            } else {
                sidebar.classList.remove('w-64');
                sidebar.classList.add('w-20');
                toggleIcon.setAttribute('d', 'M9 5l7 7-7 7');
                textElements.forEach(el => el.classList.add('opacity-0'));
            }
        });
    </script>
</body>

</html>