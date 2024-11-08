<?php 
session_start();
require_once '../config/config.php';
require_once '../includes/auth.php';
include '../components/Menu.php';

// ตรวจสอบสิทธิ์การเข้าถึงหน้า dashboard
checkPageAccess(PAGE_MANAGE_USERS);
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
                <h1 class="text-2xl font-bold text-eva">จัดการข้อมูลผู้ใช้</h1>
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

        <!-- Users Table Section -->
        <div class="p-6">
            <?php
            // ดึงข้อมูลผู้ใช้ทั้งหมด
            $stmt = $conn->prepare("SELECT u.*, r.role_name 
                                   FROM users u 
                                   LEFT JOIN roles r ON u.role_id = r.role_id 
                                   ORDER BY u.user_id");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $total_users = count($users);

            // ดึงข้อมูล roles ทั้งหมด
            $stmt = $conn->query("SELECT * FROM roles ORDER BY role_id");
            $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">รายชื่อผู้ใช้ทั้งหมด</h2>
                    <p class="text-sm text-gray-500 mt-1">จำนวนผู้ใช้: <?php echo $total_users; ?> คน</p>
                </div>
                <button onclick="openAddUserModal()" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    เพิ่มผู้ใช้
                </button>
            </div>

            <!-- ตารางแสดงข้อมูล -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ถนน</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ชื่อ-นามสกุล</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ชื่อผู้ใช้</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">รหัสผ่าน</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">สถานะ</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">สิทธิ์การใช้งาน</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">การกระทำ</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($users as $user): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo htmlspecialchars($user['street']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo htmlspecialchars($user['fullname']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo htmlspecialchars($user['username']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo htmlspecialchars($user['password']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        ปกติ
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">
                                        <?php echo htmlspecialchars($user['role_name']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="openEditUserModal(<?php echo $user['user_id']; ?>)" 
                                            class="text-blue-600 hover:text-blue-900 mr-3 inline-flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                        </svg>
                                        แก้ไข
                                    </button>
                                    <button onclick="confirmDelete(<?php echo $user['user_id']; ?>)" 
                                            class="text-red-600 hover:text-red-900 inline-flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                        ลบ
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal สำหรับเพิ่ม/แก้ไขผู้ใช้ -->
    <div id="userModal" class="hidden fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative w-full max-w-6xl bg-white rounded-lg shadow-2xl">
                <!-- ส่วนหัว Modal -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-t-lg">
                    <div class="flex justify-between items-center p-6">
                        <h3 class="text-2xl font-semibold text-white" id="modalTitle">จัดการข้อมูลผู้ใช้</h3>
                        <button onclick="closeModal()" class="text-white hover:text-gray-200 transition-colors">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- ส่วนเนื้อหา Modal -->
                <div class="p-8">
                    <form id="userForm" class="space-y-6">
                        <input type="hidden" name="action" id="formAction" value="add">
                        <input type="hidden" name="user_id" id="userId">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- ข้อมูลพื้นฐาน -->
                            <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 space-y-4">
                                <h4 class="text-lg font-medium text-blue-600 border-b pb-2">ข้อมูลพื้นฐาน</h4>
                                
                                <div class="relative">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อผู้ใช้ *</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        </span>
                                        <input type="text" name="username" required maxlength="10"
                                               class="pl-10 w-full rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 transition-all duration-200 bg-gray-50 hover:bg-white">
                                    </div>
                                </div>
                                
                                <div class="relative">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">รหัสผ่าน *</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                            </svg>
                                        </span>
                                        <input type="text" name="password" id="password" maxlength="255"
                                               class="pl-10 w-full rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 transition-all duration-200 bg-gray-50 hover:bg-white">
                                    </div>
                                </div>

                                <div class="relative">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อ-นามสกุล</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </span>
                                        <input type="text" name="fullname" maxlength="255"
                                               class="pl-10 w-full rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 transition-all duration-200 bg-gray-50 hover:bg-white">
                                    </div>
                                </div>

                                <div class="relative">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">เบอร์โทรศัพท์</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                            </svg>
                                        </span>
                                        <input type="tel" name="phone" maxlength="255"
                                               class="pl-10 w-full rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 transition-all duration-200 bg-gray-50 hover:bg-white">
                                    </div>
                                </div>
                            </div>

                            <!-- ข้อมูลที่อยู่และสิทธิ์ -->
                            <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 space-y-4">
                                <h4 class="text-lg font-medium text-blue-600 border-b pb-2">ข้อมูลที่อยู่และสิทธิ์</h4>
                                
                                <div class="relative">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">ถนน</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </span>
                                        <input type="text" name="street" maxlength="10"
                                               class="pl-10 w-full rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 transition-all duration-200 bg-gray-50 hover:bg-white">
                                    </div>
                                </div>

                                <div class="relative">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">สิทธิ์การใช้งาน</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                            </svg>
                                        </span>
                                        <select name="role_id"
                                                class="pl-10 w-full rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 transition-all duration-200 bg-gray-50 hover:bg-white">
                                            <option value="">-- เลือกสิทธิ์การใช้งาน --</option>
                                            <?php foreach ($roles as $role): ?>
                                            <option value="<?php echo $role['role_id']; ?>">
                                                <?php echo htmlspecialchars($role['role_name']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="relative">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">ที่อยู่ที่ไม่สามารถติดต่อได้</label>
                                    <textarea name="non_contact_address" rows="3"
                                              class="w-full rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 transition-all duration-200 bg-gray-50 hover:bg-white p-3"></textarea>
                                </div>

                                <div class="relative">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">ที่อยู่ติดต่อ</label>
                                    <textarea name="contact_address" rows="3"
                                              class="w-full rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 transition-all duration-200 bg-gray-50 hover:bg-white p-3"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- ปุ่มดำเนินการ -->
                        <div class="flex justify-end space-x-4 pt-6 border-t">
                            <button type="button" onclick="closeModal()"
                                    class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-white border-2 border-gray-300 rounded-lg hover:bg-gray-50 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                                ยกเลิก
                            </button>
                            <button type="submit"
                                    class="px-6 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-lg">
                                บันทึก
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    function openAddUserModal() {
        document.getElementById('modalTitle').textContent = 'เพิ่มผู้ใช้ใหม่';
        document.getElementById('formAction').value = 'add';
        document.getElementById('userId').value = '';
        document.getElementById('password').required = true;
        document.getElementById('userForm').reset();
        document.getElementById('userModal').classList.remove('hidden');
    }

    function openEditUserModal(userId) {
        document.getElementById('modalTitle').textContent = 'แก้ไขข้อมูลผู้ใช้';
        document.getElementById('formAction').value = 'edit';
        document.getElementById('userId').value = userId;
        document.getElementById('password').required = false;
        
        // ดึงข้อมูลผู้ใช้
        fetch(`get_user.php?id=${userId}`)
            .then(response => response.json())
            .then(data => {
                const form = document.getElementById('userForm');
                // กำหนดค่าให้กับฟอร์ม
                form.username.value = data.username || '';
                form.fullname.value = data.fullname || '';
                form.street.value = data.street || '';
                form.phone.value = data.phone || '';
                form.role_id.value = data.role_id || '';
                form.non_contact_address.value = data.non_contact_address || '';
                form.contact_address.value = data.contact_address || '';
                document.getElementById('userModal').classList.remove('hidden');
            })
            .catch(error => {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาดในการดึงข้อมูล');
            });
    }

    function closeModal() {
        document.getElementById('userModal').classList.add('hidden');
        document.getElementById('userForm').reset();
    }

    function confirmDelete(userId) {
        if (confirm('คุณต้องการลบข้อมูลผู้ใช้นี้ใช่หรือไม่?')) {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('user_id', userId);
            
            fetch('process_user.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('ลบข้อมูลเรียบร้อย');
                    location.reload();
                } else {
                    alert('เกิดข้อผิดพลาด: ' + data.message);
                }
            });
        }
    }

    // เพิ่ม event listener สำหรับฟอร์ม
    document.getElementById('userForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('process_user.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alert(data.message);
                location.reload();
            } else {
                alert('เกิดข้อผิดพลาด: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('เกิดข้อผิดพลาดในการส่งข้อมูล');
        });
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