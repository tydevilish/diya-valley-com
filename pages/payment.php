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
                <h1 class="text-2xl font-bold text-eva">ชำระค่าส่วนกลาง</h1>
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

        <!-- Payment Table Section -->
        <div class="p-6">
            <div class="bg-white rounded-lg shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ลำดับ</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วันที่</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">รายละเอียด</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">จำนวนเงิน (บาท)</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">การกระทำ</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">1</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">01/03/2024</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">ค่าส่วนกลางประจำเดือนมีนาคม 2567</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">500.00</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">รอชำระเงิน</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="showModal()" class="text-blue-600 hover:text-blue-900 mr-3">ชำระเงิน</button>
                                </td>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">2</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">01/02/2024</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">ค่าส่วนกลางประจำเดือนกุมภาพันธ์ 2567</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">500.00</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">ชำระแล้ว</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="showDetailModal()" class="text-gray-600 hover:text-gray-900">รายละเอียด</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div id="paymentModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
        <div class="relative p-5 border w-full max-w-lg shadow-lg rounded-md bg-white mx-4">
            <div class="mt-3">
                <div class="flex justify-between items-center pb-3">
                    <h3 class="text-lg leading-6 font-bold text-blue-500">ชำระเงิน</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-500">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div>
                    <div class="bg-gray-50 p-3 rounded-lg mb-4">
                        <ul class="space-y-2 text-sm">
                            <li>ธนาคารกสิกรไทย: 123-4-56789-0</li>
                            <li>ธนาคารไทยพาณิชย์: 098-7-65432-1</li>
                            <li>พร้อมเพย์: 089-123-4567</li>
                        </ul>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            อัพโหลดสลิปการโอนเงิน
                        </label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                            <div class="space-y-1 text-center">
                                <img id="previewImage" class="hidden mx-auto h-32 object-cover mb-3">
                                <div class="flex text-sm text-gray-600">
                                    <label for="slip" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500">
                                        <span>อัพโหลดไฟล์</span>
                                        <input id="slip" name="slip" type="file" class="sr-only" accept="image/*" onchange="previewSlip(event)">
                                    </label>
                                </div>
                                <p class="text-xs text-gray-500">PNG, JPG, GIF up to 10MB</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button onclick="closeModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            ปิด
                        </button>
                        <button id="confirmPayment" onclick="submitPayment()" class="px-4 py-2 bg-gray-300 text-gray-500 rounded-md cursor-not-allowed" disabled>
                            ยืนยันการชำระเงิน
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Modal -->
    <div id="detailModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
        <div class="relative p-5 border w-full max-w-lg shadow-lg rounded-md bg-white mx-4">
            <div class="mt-3">
                <div class="flex justify-between items-center pb-3">
                    <h3 class="text-lg leading-6 font-bold text-blue-500">รายละเอียดการชำระเงิน</h3>
                    <button onclick="closeDetailModal()" class="text-gray-400 hover:text-gray-500">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div>
                    <div class="bg-gray-50 p-4 rounded-lg mb-4">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div class="text-gray-600">วันที่ชำระ:</div>
                            <div class="font-medium">01/02/2024 10:30</div>
                            <div class="text-gray-600">จำนวนเงิน:</div>
                            <div class="font-medium">500.00 บาท</div>
                            <div class="text-gray-600">ช่องทางการชำระ:</div>
                            <div class="font-medium">โอนผ่านธนาคารกสิกรไทย</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            สลิปการโอนเงิน
                        </label>
                        <div class="mt-1 flex justify-center">
                            <img src="https://img2.pic.in.th/pic/462554817_573567848662882_2984175964874345892_n.jpg" alt="สลิปการโอนเงิน" class="max-h-64 rounded-lg shadow-sm">
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button onclick="closeDetailModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            ปิด
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showModal() {
            document.getElementById('paymentModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('paymentModal').classList.add('hidden');
            document.getElementById('previewImage').classList.add('hidden');
            document.getElementById('slip').value = '';
            document.getElementById('confirmPayment').disabled = true;
            document.getElementById('confirmPayment').classList.add('bg-gray-300', 'text-gray-500', 'cursor-not-allowed');
            document.getElementById('confirmPayment').classList.remove('bg-blue-600', 'text-white', 'hover:bg-blue-700');
        }

        function previewSlip(event) {
            const file = event.target.files[0];
            const previewImage = document.getElementById('previewImage');
            const confirmButton = document.getElementById('confirmPayment');

            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    previewImage.classList.remove('hidden');
                    confirmButton.disabled = false;
                    confirmButton.classList.remove('bg-gray-300', 'text-gray-500', 'cursor-not-allowed');
                    confirmButton.classList.add('bg-blue-600', 'text-white', 'hover:bg-blue-700');
                }
                reader.readAsDataURL(file);
            }
        }

        function submitPayment() {
            alert('ส่งข้อมูลการชำระเงินเรียบร้อย');
            closeModal();
        }

        function showDetailModal() {
            document.getElementById('detailModal').classList.remove('hidden');
        }

        function closeDetailModal() {
            document.getElementById('detailModal').classList.add('hidden');
        }
    </script>
</body>

</html>