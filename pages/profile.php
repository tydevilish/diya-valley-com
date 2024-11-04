<?php 
session_start();
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

            <div class="flex flex-col h-full">
                <!-- Profile Section -->
                <div class="p-4 mb-6">
                    <div class="flex items-center">
                        <div class="relative flex-shrink-0">
                            <img src="https://img5.pic.in.th/file/secure-sv1/user_avatar.png"
                                alt="Profile"
                                class="w-12 h-12 rounded-full border-2 border-white shadow-md hover:scale-105 transition-transform duration-200">
                        </div>
                        <div class="ml-4">
                            <h3 class="text-white font-semibold text-sm opacity-0 transition-opacity duration-500 ease-in-out whitespace-nowrap">คุณทวีศักดิ์ นำมา</h3>
                            <p class="text-blue-100 text-xs opacity-0 transition-opacity duration-500 ease-in-out whitespace-nowrap">Users</p>
                        </div>
                    </div>
                </div>

                <!-- Menu Section -->
                <?php renderMenu(); ?>
            </div>
        </div>

        <script>
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
                    toggleIcon.setAttribute('d', 'M15 19l-7-7 7-7'); // ลูกศรชี้ซ้าย
                    textElements.forEach(el => el.classList.remove('opacity-0'));
                } else {
                    sidebar.classList.remove('w-64');
                    sidebar.classList.add('w-20');
                    toggleIcon.setAttribute('d', 'M9 5l7 7-7 7'); // ลูกศรชี้ขวา
                    textElements.forEach(el => el.classList.add('opacity-0'));
                }
            });
        </script>
    </div>

    <div class="flex-1 ml-20">
        <!-- Top Navigation -->
        <nav class="bg-white shadow-sm px-6 py-3">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-eva">แก้ไขโปรไฟล์</h1>
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

        <!-- Profile Edit Form -->
        <div class="container mx-auto px-4 py-5 lg:py-36">
            <div class="bg-white rounded-xl shadow-lg p-6 max-w-7xl mx-auto">
                <form action="update_profile.php" method="POST" enctype="multipart/form-data">
                    <!-- Profile Section -->
                    <div class="flex flex-col lg:flex-row gap-8">
                        <!-- Left Side - Profile Image -->
                        <div class="lg:w-1/4">
                            <div class="relative">
                                <img src="https://img5.pic.in.th/file/secure-sv1/user_avatar.png"
                                    class="w-full aspect-square rounded-xl object-cover shadow-lg border-4 border-blue-500"
                                    id="preview-image"
                                    alt="Profile picture">
                                <label class="absolute bottom-2 right-2 cursor-pointer bg-blue-500 rounded-full p-2 shadow-lg hover:bg-blue-600 transition-colors">
                                    <svg class="w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pen-fill" viewBox="0 0 16 16">
                                        <path d="m13.498.795.149-.149a1.207 1.207 0 1 1 1.707 1.708l-.149.148a1.5 1.5 0 0 1-.059 2.059L4.854 14.854a.5.5 0 0 1-.233.131l-4 1a.5.5 0 0 1-.606-.606l1-4a.5.5 0 0 1 .131-.232l9.642-9.642a.5.5 0 0 0-.642.056L6.854 4.854a.5.5 0 1 1-.708-.708L9.44.854A1.5 1.5 0 0 1 11.5.796a1.5 1.5 0 0 1 1.998-.001" />
                                    </svg>
                                    <input type="file" name="profile_image" class="hidden" accept="image/*"
                                        onchange="document.getElementById('preview-image').src = window.URL.createObjectURL(this.files[0])">
                                </label>
                            </div>
                        </div>

                        <!-- Right Side - Form Fields -->
                        <div class="lg:w-3/4">
                            <!-- Personal Info -->
                            <div class="mb-8">
                                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                    </svg>
                                    ข้อมูลส่วนตัว
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อ-นามสกุล</label>
                                        <input type="text" name="fullname"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            value="ทวีศักดิ์ นำมา">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">เบอร์โทรศัพท์</label>
                                        <input type="tel" name="phone"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            value="081-234-5678">
                                    </div>
                                </div>
                            </div>

                            <!-- Address -->
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                                    </svg>
                                    ข้อมูลที่อยู่
                                </h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">บ้านเลขที่</label>
                                        <input type="text" name="house_no"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            value="123/456">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">หมู่บ้าน/อาคาร</label>
                                        <input type="text" name="village"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            value="Diya Valley">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">ถนน</label>
                                        <input type="text" name="road"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            value="พหลโยธิน">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">ตำบล/แขวง</label>
                                        <input type="text" name="subdistrict"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            value="คลองหนึ่ง">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">อำเภอ/เขต</label>
                                        <input type="text" name="district"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            value="คลองหลวง">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">จังหวัด</label>
                                        <input type="text" name="province"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            value="ปทุมธานี">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">รหัสไปรษณีย์</label>
                                        <input type="text" name="postal_code"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            value="12120">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="mt-8 flex justify-end">
                        <button type="submit"
                            class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transform hover:scale-105 transition-all duration-200 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            บันทึกข้อมูล
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- เพิ่ม script ก่อน closing body tag -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                e.preventDefault(); // ป้องกันการ submit แบบปกติ

                // สร้าง FormData object
                const formData = new FormData(this);

                // ส่งข้อมูลด้วย fetch API
                fetch('update_profile.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            alert('บันทึกข้อมูลเรียบร้อยแล้ว');
                        } else {
                            alert('เกิดข้อผิดพลาด: ' + data.message);
                        }
                    })
                    .catch(error => {
                        alert('เกิดข้อผิดพลาดในการบันทึกข้อมูล');
                        console.error('Error:', error);
                    });
            });
        });
    </script>
</body>

</html>