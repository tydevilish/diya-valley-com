<?php
session_start();
require_once '../config/config.php';
require_once '../includes/auth.php';
include '../components/Menu.php';

// ตรวจสอบสิทธิ์การเข้าถึงหน้า dashboard
checkPageAccess(PAGE_MANAGE_PAYMENT);

// เพิ่มโค้ดนี้ก่อนส่วนแสดงผล (ประมาณบรรทัด 80)
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT t.user_id) as pending_users
    FROM transactions t
    WHERE t.status = 'pending'
");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$pending_users = $result['pending_users'];
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <style>
        @keyframes slideIn {
            from { transform: translateY(-100px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .modal-content {
            animation: slideIn 0.3s ease-out;
        }
        
        .status-badge {
            transition: all 0.2s ease-in-out;
        }
        
        .status-badge:hover {
            transform: scale(1.05);
        }
    </style>
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

    <div class="flex-1 ml-20">
        <!-- ปรับแต่ง Top Navigation ให้เหมือนกับ payment.php -->
        <nav class="bg-white shadow-sm px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">จัดการค่าส่วนกลาง</h1>
                    <p class="text-sm text-gray-500 mt-1">จัดการการชำระค่าส่วนกลางของสมาชิก</p>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-600">จำนวนผู้ที่รออนุมัติ</p>
                        <p class="text-2xl font-bold text-blue-600"><?php echo $pending_users; ?> คน</p>
                    </div>
                </div>
            </div>
        </nav>

        <!-- ปรับแต่งส่วนของตาราง -->
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800">รายการค่าส่วนกลางทั้งมด</h2>
                </div>
                <button onclick="showAddPaymentModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-all duration-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>เพิ่มค่าส่วนกลาง</span>
                </button>
            </div>

            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <!-- ตารางยังคงเหมือนเดิม แต่ปรับ style ให้เข้ากับ theme -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ลำดับ</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">เดือน ปี</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">รายละเอียด</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">จำนวนเงิน (บาท)</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">การกระทำ</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            // ดึงข้อมูลค่าส่วนกลางทั้งหมด
                            $stmt = $conn->prepare("
                                SELECT p.*, 
                                    COUNT(DISTINCT t.user_id) as total_paid,
                                    COUNT(DISTINCT pu.user_id) as total_assigned_users
                                FROM payments p
                                LEFT JOIN transactions t ON p.payment_id = t.payment_id AND t.status = 'approved'
                                LEFT JOIN payment_users pu ON p.payment_id = pu.payment_id
                                GROUP BY p.payment_id
                                ORDER BY p.year DESC, p.month DESC
                            ");
                            $stmt->execute();
                            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            foreach ($payments as $index => $payment) {
                                echo "<tr>";
                                echo "<td class='px-6 py-4 whitespace-nowrap text-sm text-gray-500'>" . ($index + 1) . "</td>";
                                echo "<td class='px-6 py-4 whitespace-nowrap text-sm text-gray-500'>" . 
                                    sprintf("%02d/%04d", $payment['month'], $payment['year']) . 
                                "</td>";
                                echo "<td class='px-6 py-4 text-sm text-gray-500'>" . $payment['description'] . "</td>";
                                echo "<td class='px-6 py-4 whitespace-nowrap text-sm text-gray-500'>" . number_format($payment['amount'], 2) . "</td>";
                                echo "<td class='px-6 py-4 whitespace-nowrap'>";
                                echo "<span class='text-sm text-gray-600'>{$payment['total_paid']}/{$payment['total_assigned_users']} ชำระแล้ว</span>";
                                echo "</td>";
                                echo "<td class='px-6 py-4 whitespace-nowrap text-sm font-medium'>";
                                echo "<button onclick='viewPaymentDetails({$payment['payment_id']})' class='text-blue-600 hover:text-blue-900 mr-3'>ดูรายละเอียด</button>";
                                echo "<button onclick='deletePayment({$payment['payment_id']})' class='text-red-600 hover:text-red-900'>ลบ</button>";
                                echo "</td>";
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ปรับแต่ง Modal ให้มี style เหมือนกับ payment.php -->
    <div id="addPaymentModal" class="hidden fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-8 border w-[600px] shadow-2xl rounded-xl bg-white">
            <div class="absolute top-4 right-4">
                <button onclick="closeAddPaymentModal()" class="text-gray-400 hover:text-gray-600 transition-colors duration-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="mt-2">
                <h3 class="text-xl font-semibold text-gray-900 mb-6">เพิ่มค่าส่วนกลาง</h3>
                <form id="addPaymentForm" action="../actions/add_payment.php" method="POST">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">เดือน</label>
                            <select name="month" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                                <?php for($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $i == date('n') ? 'selected' : ''; ?>>
                                        <?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">ปี</label>
                            <select name="year" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                                <?php 
                                $currentYear = (int)date('Y');
                                for($i = $currentYear - 1; $i <= $currentYear + 1; $i++): 
                                ?>
                                    <option value="<?php echo $i; ?>" <?php echo $i == $currentYear ? 'selected' : ''; ?>>
                                        <?php echo $i + 543; // แปลงเป็นปี พ.ศ. ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">รายละเอียด</label>
                        <textarea name="description" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">จำนวนเงิน (บาท)</label>
                        <input type="number" name="amount" step="0.01" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">เลือกผู้ใช้</label>
                        <div class="flex items-center mb-2">
                            <input type="checkbox" id="selectAll" class="mr-2">
                            <label for="selectAll">เลือกทั้งหมด</label>
                        </div>
                        <div class="max-h-40 overflow-y-auto border rounded p-2">
                            <?php
                            $stmt = $conn->prepare("SELECT user_id, username FROM users WHERE role_id = 2");
                            $stmt->execute();
                            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($users as $user) {
                                echo "<div class='flex items-center mb-1'>";
                                echo "<input type='checkbox' name='selected_users[]' value='{$user['user_id']}' class='user-checkbox mr-2'>";
                                echo "<label>{$user['username']}</label>";
                                echo "</div>";
                            }
                            ?>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg mr-2">บันทึก</button>
                        <button type="button" onclick="closeAddPaymentModal()" class="bg-gray-300 text-gray-800 px-4 py-2 rounded-lg">ยกเลิก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal แสดงรายละเอียดการชำระเงิน -->
    <div id="paymentDetailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="relative bg-white rounded-lg shadow-xl w-11/12 max-w-4xl max-h-[90vh]">
                <!-- ส่วนหัว modal -->
                <div class="sticky top-0 bg-white px-6 py-4 border-b flex justify-between items-center rounded-t-lg z-10">
                    <h3 class="text-xl font-semibold text-gray-900">รายละเอียดการชำระเงิน</h3>
                    <button onclick="document.getElementById('paymentDetailsModal').classList.add('hidden')" 
                            class="text-gray-400 hover:text-gray-500">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <!-- เนื้อหา modal -->
                <div id="paymentDetailsContent" class="overflow-y-auto">
                    <!-- ข้อมูลจะถูกใส่ที่นี่โดย JavaScript -->
                </div>
            </div>
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
                toggleIcon.setAttribute('d', 'M15 19l-7-7 7-7');
                textElements.forEach(el => el.classList.remove('opacity-0'));
            } else {
                sidebar.classList.remove('w-64');
                sidebar.classList.add('w-20');
                toggleIcon.setAttribute('d', 'M9 5l7 7-7 7');
                textElements.forEach(el => el.classList.add('opacity-0'));
            }
        });

        function showAddPaymentModal() {
            document.getElementById('addPaymentModal').classList.remove('hidden');
        }

        function closeAddPaymentModal() {
            document.getElementById('addPaymentModal').classList.add('hidden');
        }

        function viewPaymentDetails(paymentId) {
            fetch(`../actions/get_payment_details.php?payment_id=${paymentId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('paymentDetailsContent').innerHTML = html;
                    document.getElementById('paymentDetailsModal').classList.remove('hidden');
                });
        }

        function closePaymentDetailsModal() {
            document.getElementById('paymentDetailsModal').classList.add('hidden');
        }

        function togglePaymentStatus(paymentId, newStatus) {
            if (confirm('คุณต้องการเปลี่ยนสถานะค่าส่วนกลางนี้ใช่หรือไม่?')) {
                fetch('../actions/toggle_payment_status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `payment_id=${paymentId}&status=${newStatus}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('เกิดข้อผิดพลาด: ' + data.message);
                        }
                    });
            }
        }

        function updateTransactionStatus(transactionId, status) {
            let reason = '';
            if (status === 'rejected') {
                reason = prompt('กรุณาระบุเหตุผลที่ไม่อนุมัติ:');
                if (!reason) return;
            }

            fetch('../actions/update_transaction_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `transaction_id=${transactionId}&status=${status}&reason=${encodeURIComponent(reason)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // รีโหลดข้อมูลใน modal
                    viewPaymentDetails(data.payment_id);
                } else {
                    alert('เกิดข้อผิดพลาด: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาดในกรอัพเดทสถานะ');
            });
        }

        function refreshPaymentDetails(paymentId) {
            fetch(`../actions/get_payment_details.php?payment_id=${paymentId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('paymentDetailsContent').innerHTML = html;
                });
        }

        function updateCounters() {
            const pendingCount = document.querySelector('.bg-yellow-50 .space-y-2').children.length;
            const approvedCount = document.querySelector('.bg-green-50 .space-y-2').children.length;
            const notPaidCount = document.querySelector('.bg-gray-50 .space-y-2').children.length;

            document.querySelector('.bg-yellow-50 h3').textContent = `รอตรวจสอบ (${pendingCount})`;
            document.querySelector('.bg-green-50 h3').textContent = `ชำระแล้ว (${approvedCount})`;
            document.querySelector('.bg-gray-50 h3').textContent = `ยังไม่ชำระ (${notPaidCount})`;
        }

        function deletePayment(paymentId) {
            if (confirm('คุณต้องการลบค่าส่นกลางนี้ใช่หรือไม่? การดำเนินการนี้ไม่สามารถย้อนกลับได้')) {
                fetch('../actions/delete_payment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `payment_id=${paymentId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('เกิดข้อผิดพลาด: ' + data.message);
                        }
                    });
            }
        }

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

        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.getElementsByClassName('user-checkbox');
            for (let checkbox of checkboxes) {
                checkbox.checked = this.checked;
            }
        });

        // ตรวจสอบการ submit form
        document.getElementById('addPaymentForm').addEventListener('submit', function(e) {
            const checkboxes = document.getElementsByClassName('user-checkbox');
            let checked = false;
            for (let checkbox of checkboxes) {
                if (checkbox.checked) {
                    checked = true;
                    break;
                }
            }
            if (!checked) {
                e.preventDefault();
                alert('กรุณาลือกผู้ใช้อย่างน้อย 1 คน');
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('paymentDetailsModal');
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.classList.add('hidden');
                }
            });
        });

        function showPaymentTab(tabName) {
            // ซ่อนทุก tab content
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.add('opacity-0');
                setTimeout(() => {
                    tab.classList.add('hidden');
                }, 150);
            });

            // แดง tab ที่เลือก
            setTimeout(() => {
                const selectedTab = document.getElementById(`tab-${tabName}`);
                if (selectedTab) {
                    selectedTab.classList.remove('hidden');
                    requestAnimationFrame(() => {
                        selectedTab.classList.remove('opacity-0');
                    });
                }
            }, 160);

            // อัพเดทสถานะปุ่ม
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('text-blue-600', 'border-blue-600');
                btn.classList.add('text-gray-500', 'border-transparent');
            });

            // ไฮไลท์ปุ่มที่เลือก
            const activeBtn = document.querySelector(`[data-tab="${tabName}"]`);
            if (activeBtn) {
                activeBtn.classList.remove('text-gray-500', 'border-transparent');
                activeBtn.classList.add('text-blue-600', 'border-blue-600');
            }
        }
    </script>
</body>

</html>