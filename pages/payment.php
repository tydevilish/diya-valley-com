<?php 
session_start();
require_once '../config/config.php';
require_once '../includes/auth.php';
include '../components/Menu.php';

// ตรวจสอบสิทธิ์การเข้าถึงหน้า dashboard
checkPageAccess(PAGE_PAYMENT);

// เพิ่มฟังก์ชันสำหรับดึงข้อมูลการชำระเงินของผู้ใช้
function getUserPayments($user_id) {
    global $conn;
    
    $sql = "SELECT pt.*, p.title, p.created_at 
            FROM payment_transactions pt
            INNER JOIN payments p ON pt.payment_id = p.payment_id
            WHERE pt.user_id = :user_id
            ORDER BY p.created_at DESC";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute([':user_id' => $user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// เพิ่มฟังก์ชันสำหรับนับจำนวนรายการที่ยังไม่ได้ชำระ
function getUnpaidCount($user_id) {
    global $conn;
    
    $sql = "SELECT COUNT(*) as count 
            FROM payment_transactions 
            WHERE user_id = :user_id 
            AND status = 'pending'";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute([':user_id' => $user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

// ดึงข้อมูลการชำระเงินของผู้ใช้
$user_id = $_SESSION['user_id'];
$payments = getUserPayments($user_id);
$unpaid_count = getUnpaidCount($user_id);

// ย้ายไปไว้ด้านบนสุดของไฟล์ หลัง session_start()
if (isset($_POST['action']) && $_POST['action'] == 'submit_payment') {
    header('Content-Type: application/json');
    $response = ['status' => 'error', 'message' => ''];
    
    try {
        if (!isset($_POST['payment_id'])) {
            throw new Exception('ไม่พบข้อมูล payment_id');
        }
        
        $payment_id = $_POST['payment_id'];
        $user_id = $_SESSION['user_id'];
        
        // Debug
        error_log("Payment ID: " . $payment_id);
        error_log("User ID: " . $user_id);
        error_log("Files: " . print_r($_FILES, true));
        
        // ตรวจสอบไฟล์
        if (!isset($_FILES['slip']) || $_FILES['slip']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('กรุณาอัพโหลดสลิปการอนเงิน');
        }

        $file = $_FILES['slip'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('รองรับเฉพาะไฟล์รูปภาพ (JPG, PNG, GIF)');
        }

        // สร้างชื่อไฟล์ใหม่
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newFileName = uniqid() . '_' . time() . '.' . $extension;
        $uploadPath = '../uploads/slips/' . $newFileName;

        // สร้างโฟลเดอร์
        if (!file_exists('../uploads/slips/')) {
            if (!mkdir('../uploads/slips/', 0777, true)) {
                throw new Exception('ไม่สามารถสร้างโฟลเดอร์สำหรับเก็บไฟล์ได้');
            }
        }

        // อัพโหลดไฟล์
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            throw new Exception('ไม่สามารถอัพโหลดไฟล์ได้');
        }

        // อัพเดทฐานข้อมูล
        $sql = "UPDATE payment_transactions 
                SET slip_image = :slip_image,
                    payment_date = NOW(),
                    status = 'approved'
                WHERE payment_id = :payment_id 
                AND user_id = :user_id";
                
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute([
            ':slip_image' => $newFileName,
            ':payment_id' => $payment_id,
            ':user_id' => $user_id
        ]);

        if (!$result) {
            throw new Exception('ไม่สามารถบันทึกข้อมูลลงฐานข้อมูลได้');
        }

        if ($stmt->rowCount() === 0) {
            throw new Exception('ไม่พบรายการชำระเงินที่ต้องการอัพเดท');
        }

        $response['status'] = 'success';
        $response['message'] = 'ชำระเงินเรียบร้อยแล้ว';
        
    } catch (Exception $e) {
        error_log("Error in submit_payment: " . $e->getMessage());
        $response['message'] = $e->getMessage();
    }
    
    echo json_encode($response);
    exit;
}

// เพิ่มที่ด้านบนของไฟล์ ก่อนส่วน HTML
if (isset($_GET['action']) && $_GET['action'] == 'get_payment_detail') {
    header('Content-Type: application/json');
    $response = ['status' => 'error', 'message' => ''];
    
    try {
        if (!isset($_GET['payment_id'])) {
            throw new Exception('ไม่พบข้อมูล payment_id');
        }
        
        $payment_id = $_GET['payment_id'];
        $user_id = $_SESSION['user_id'];
        
        $sql = "SELECT pt.*, p.title 
                FROM payment_transactions pt
                INNER JOIN payments p ON pt.payment_id = p.payment_id
                WHERE pt.payment_id = :payment_id 
                AND pt.user_id = :user_id";
                
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':payment_id' => $payment_id,
            ':user_id' => $user_id
        ]);
        
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$payment) {
            throw new Exception('ไม่พบข้อมูลการชำระเงิน');
        }

        // แปลงวันที่ให้อยู่ในรูปแบบที่ต้องการ
        $payment['payment_date'] = date('d/m/Y H:i', strtotime($payment['payment_date']));
        
        $response['status'] = 'success';
        $response['payment'] = $payment;
        
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
    
    echo json_encode($response);
    exit;
}
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
                                    <?php if ($unpaid_count > 0): ?>
                                        <?php foreach($payments as $payment): ?>
                                            <?php if ($payment['status'] == 'pending'): ?>
                                            <a href="payment.php" class="block p-3 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0">
                                                        <svg class="w-6 h-6 text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <rect x="2" y="5" width="20" height="14" rx="2" />
                                                            <line x1="2" y1="10" x2="22" y2="10" />
                                                        </svg>
                                                    </div>
                                                    <div class="ml-3">
                                                        <p class="text-sm font-medium text-gray-800"><?= htmlspecialchars($payment['title']) ?></p>
                                                        <p class="text-xs text-gray-500">รอการชำระเงิน <?= number_format($payment['amount'], 2) ?> บาท</p>
                                                    </div>
                                                </div>
                                            </a>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="p-3 text-center text-gray-500">
                                            ไม่มีรายการที่ต้องชำระ
                                        </div>
                                    <?php endif; ?>
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
                            <?php if (empty($payments)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                        <div class="flex flex-col items-center">
                                            <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <p class="text-lg font-medium">ไม่พบรายการ</p>
                                            <p class="text-sm text-gray-400">ยังไม่มีรายการค่าสวนกลาง</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: 
                                $i = 1;
                                foreach($payments as $payment): 
                                    $status_class = $payment['status'] == 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800';
                                    $status_text = $payment['status'] == 'pending' ? 'รอชำระเงิน' : 'ชำระแล้ว';
                            ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= $i++ ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= date('d/m/Y', strtotime($payment['created_at'])) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($payment['title']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= number_format($payment['amount'], 2) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $status_class ?>">
                                            <?= $status_text ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <?php if ($payment['status'] == 'pending'): ?>
                                            <button onclick="showModal(<?= $payment['payment_id'] ?>)" 
                                                    class="text-blue-600 hover:text-blue-900 mr-3">
                                                ชำระเงิน
                                            </button>
                                        <?php else: ?>
                                            <button onclick="showDetailModal(<?= $payment['payment_id'] ?>)" 
                                                    class="text-gray-600 hover:text-gray-900">
                                                รายละเอียด
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php 
                                endforeach;
                            endif; 
                            ?>
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

                    <!-- เพิ่ม input hidden สำหรับเก็บ payment_id -->
                    <input type="hidden" id="currentPaymentId">

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
                            <div id="payment_date" class="font-medium"></div>
                            <div class="text-gray-600">จำนวนเงิน:</div>
                            <div id="payment_amount" class="font-medium"></div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            สลิปการโอนเงิน
                        </label>
                        <div class="mt-1 flex justify-center">
                            <img id="slip_image" src="" alt="สลิปการโอนเงิน" class="max-h-64 rounded-lg shadow-sm">
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
        function showModal(paymentId) {
            document.getElementById('currentPaymentId').value = paymentId;
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
            const paymentId = document.getElementById('currentPaymentId').value;
            const slipFile = document.getElementById('slip').files[0];
            
            if (!slipFile) {
                alert('กรุณาอัพโหลดสลิปการโอนเงิน');
                return;
            }

            // Debug
            console.log('Payment ID:', paymentId);
            console.log('File:', slipFile);

            const formData = new FormData();
            formData.append('action', 'submit_payment');
            formData.append('payment_id', paymentId);
            formData.append('slip', slipFile);

            // แสดง loading state
            const confirmButton = document.getElementById('confirmPayment');
            const originalContent = confirmButton.innerHTML;
            confirmButton.disabled = true;
            confirmButton.innerHTML = `
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                กำลังส่งข้อมูล...
            `;

            fetch('payment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // Debug
                console.log('Response:', response);
                return response.json();
            })
            .then(data => {
                // Debug
                console.log('Data:', data);
                if(data.status === 'success') {
                    alert('ส่งข้อมูลการชำระเงินเรียบร้อย');
                    closeModal();
                    window.location.reload();
                } else {
                    alert(data.message || 'เกิดข้อผิดพลาดในการส่งข้อมูล');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาดในการส่งข้อมูล');
            })
            .finally(() => {
                confirmButton.disabled = false;
                confirmButton.innerHTML = originalContent;
            });
        }

        function showDetailModal(paymentId) {
            // เรียกข้อมูลจาก API
            fetch(`payment.php?action=get_payment_detail&payment_id=${paymentId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        const payment = data.payment;
                        document.getElementById('payment_date').textContent = payment.payment_date;
                        document.getElementById('payment_amount').textContent = Number(payment.amount).toLocaleString('th-TH', {minimumFractionDigits: 2}) + ' บาท';
                        document.getElementById('slip_image').src = '../uploads/slips/' + payment.slip_image;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('เกิดข้อผิดพลาดในการโหลดข้อมูล');
                });
            
            document.getElementById('detailModal').classList.remove('hidden');
        }

        function closeDetailModal() {
            document.getElementById('detailModal').classList.add('hidden');
        }
    </script>
</body>

</html>