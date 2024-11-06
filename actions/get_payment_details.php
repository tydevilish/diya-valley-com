<?php
session_start();
require_once '../config/config.php';
require_once '../includes/auth.php';

// ตรวจสอบสิทธิ์
checkPageAccess(PAGE_MANAGE_PAYMENT);

if (isset($_GET['payment_id'])) {
    $payment_id = $_GET['payment_id'];
    
    // ดึงข้อมูลการชำระเงินทั้งหมด
    $stmt = $conn->prepare("
        SELECT 
            p.amount as payment_amount,
            p.description,
            p.month,
            p.year,
            t.transaction_id,
            t.status,
            t.created_at,
            t.slip_image,
            t.reject_reason,
            u.username,
            u.user_id
        FROM payments p
        LEFT JOIN payment_users pu ON p.payment_id = pu.payment_id
        LEFT JOIN users u ON pu.user_id = u.user_id
        LEFT JOIN transactions t ON (p.payment_id = t.payment_id AND u.user_id = t.user_id)
        WHERE p.payment_id = ?
        ORDER BY u.username ASC
    ");
    $stmt->execute([$payment_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // จัดกลุ่มข้อมูล
    $payment_info = null;
    $users = [
        'not_paid' => [],
        'pending' => [],
        'approved' => [],
        'rejected' => []
    ];
    
    $total_amount = 0;
    $paid_amount = 0;

    foreach ($results as $row) {
        if (!$payment_info) {
            $payment_info = [
                'amount' => $row['payment_amount'],
                'description' => $row['description'],
                'month' => $row['month'],
                'year' => $row['year'],
                'month_year_display' => sprintf("%02d/%04d", $row['month'], $row['year'])
            ];
            $total_amount = $row['payment_amount'] * count($results);
        }

        $user_data = [
            'username' => $row['username'],
            'user_id' => $row['user_id'],
            'transaction_id' => $row['transaction_id'],
            'created_at' => $row['created_at'],
            'slip_image' => $row['slip_image'],
            'reject_reason' => $row['reject_reason']
        ];

        if (!$row['status']) {
            $users['not_paid'][] = $user_data;
        } else {
            $users[$row['status']][] = $user_data;
            if ($row['status'] === 'approved') {
                $paid_amount += $row['payment_amount'];
            }
        }
    }
    ?>

    <style>
        .tab-content {
            opacity: 1;
            transition: all 150ms ease-in-out;
        }
        .tab-content.opacity-0 {
            opacity: 0;
        }
        .tab-content.hidden {
            display: none;
        }
        .tab-btn {
            transition: all 150ms ease-in-out;
        }
        .tab-btn .absolute {
            transition: transform 200ms ease-in-out;
        }
    </style>

    <div class="bg-white">
        <!-- ส่วนหัว -->
        <div class="px-6 py-4">
            <div class="flex justify-between items-center">
                <h3 class="text-xl font-semibold text-gray-900">
                    <?php echo htmlspecialchars($payment_info['description']); ?>
                </h3>
                <span class="text-sm text-gray-500">
                    <?php echo sprintf("%02d/%04d", $payment_info['month'], $payment_info['year']); ?>
                </span>
            </div>
            
            <!-- สรุปยอดเงิน -->
            <div class="mt-4 grid grid-cols-2 gap-4">
                <div class="bg-green-50 p-4 rounded-lg">
                    <p class="text-sm text-gray-600">ยอดชำระแล้ว</p>
                    <p class="text-2xl font-bold text-green-600"><?php echo number_format($paid_amount, 2); ?> บาท</p>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="text-sm text-gray-600">ยอดรวมทั้งหมด</p>
                    <p class="text-2xl font-bold text-gray-600"><?php echo number_format($total_amount, 2); ?> บาท</p>
                </div>
            </div>
        </div>

        <!-- แท็บเมนู -->
        <div class="sticky top-0 bg-white border-t border-b z-10">
            <div class="flex space-x-4 px-6">
                <button onclick="showPaymentTab('not_paid')" 
                        class="tab-btn px-4 py-3 border-b-2 border-transparent text-gray-500 hover:text-blue-600 transition-colors" 
                        data-tab="not_paid">
                    ยังไม่ชำระ (<?php echo count($users['not_paid']); ?>)
                </button>
                
                <button onclick="showPaymentTab('pending')" 
                        class="tab-btn px-4 py-3 border-b-2 border-transparent text-gray-500 hover:text-blue-600 transition-colors" 
                        data-tab="pending">
                    รอตรวจสอบ (<?php echo count($users['pending']); ?>)
                </button>
                
                <button onclick="showPaymentTab('approved')" 
                        class="tab-btn px-4 py-3 border-b-2 border-transparent text-gray-500 hover:text-blue-600 transition-colors" 
                        data-tab="approved">
                    ชำระแล้ว (<?php echo count($users['approved']); ?>)
                </button>
                
                <button onclick="showPaymentTab('rejected')" 
                        class="tab-btn px-4 py-3 border-b-2 border-transparent text-gray-500 hover:text-blue-600 transition-colors" 
                        data-tab="rejected">
                    ไม่อนุมัติ (<?php echo count($users['rejected']); ?>)
                </button>
            </div>
        </div>

        <!-- เนื้อหาแท็บ -->
        <div class="p-6">
            <?php foreach ($users as $status => $user_list): ?>
                <div id="tab-<?php echo $status; ?>" class="tab-content <?php echo $status === 'not_paid' ? '' : 'hidden opacity-0'; ?>">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        ผู้ใช้งาน
                                    </th>
                                    <?php if ($status !== 'not_paid'): ?>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            วันที่
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            หลักฐาน
                                        </th>
                                    <?php endif; ?>
                                    <?php if ($status === 'rejected'): ?>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            เหตุผล
                                        </th>
                                    <?php endif; ?>
                                    <?php if ($status === 'pending'): ?>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            การกระทำ
                                        </th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (!empty($user_list)): ?>
                                    <?php foreach ($user_list as $user): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo htmlspecialchars($user['username']); ?>
                                            </td>
                                            <?php if ($status !== 'not_paid'): ?>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?php if (!empty($user['slip_image'])): ?>
                                                        <a href="../uploads/slips/<?php echo htmlspecialchars($user['slip_image']); ?>" 
                                                           target="_blank" 
                                                           class="text-blue-600 hover:text-blue-900">
                                                            ดูหลักฐาน
                                                        </a>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                            <?php endif; ?>
                                            <?php if ($status === 'rejected'): ?>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?php echo htmlspecialchars($user['reject_reason']); ?>
                                                </td>
                                            <?php endif; ?>
                                            <?php if ($status === 'pending'): ?>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    <button onclick="updateTransactionStatus(<?php echo $user['transaction_id']; ?>, 'approved')" 
                                                            class="text-green-600 hover:text-green-900 mr-2">
                                                        อนุมัติ
                                                    </button>
                                                    <button onclick="updateTransactionStatus(<?php echo $user['transaction_id']; ?>, 'rejected')"
                                                            class="text-red-600 hover:text-red-900">
                                                        ไม่อนุมัติ
                                                    </button>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                            ไม่พบข้อมูล
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        function showPaymentTab(tabName) {
            // ซ่อนทุก tab content
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.add('opacity-0');
            });
            
            // รอให้ animation เสร็จก่อนซ่อน
            setTimeout(() => {
                document.querySelectorAll('.tab-content').forEach(tab => {
                    tab.classList.add('hidden');
                });
                
                // แสดง tab ที่เลือก
                const selectedTab = document.getElementById('tab-' + tabName);
                if (selectedTab) {
                    selectedTab.classList.remove('hidden');
                    // รอให้ browser render การแสดงผลก่อน
                    requestAnimationFrame(() => {
                        selectedTab.classList.remove('opacity-0');
                    });
                }
            }, 150);
            
            // อัพเดทสถานะปุ่ม
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('border-blue-500', 'text-blue-600', 'font-medium');
                btn.classList.add('border-transparent');
                const indicator = btn.querySelector('.absolute');
                if (indicator) {
                    indicator.classList.add('scale-x-0');
                }
            });
            
            // ไฮไลท์ปุ่มที่เลือก
            const activeBtn = document.querySelector(`[data-tab="${tabName}"]`);
            if (activeBtn) {
                activeBtn.classList.remove('border-transparent');
                activeBtn.classList.add('border-blue-500', 'text-blue-600', 'font-medium');
                const indicator = activeBtn.querySelector('.absolute');
                if (indicator) {
                    indicator.classList.remove('scale-x-0');
                }
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
                alert('เกิดข้อผิดพลาดในการอัพเดทสถานะ');
            });
        }

        function viewPaymentDetails(paymentId) {
            fetch(`../actions/get_payment_details.php?payment_id=${paymentId}`)
                .then(response => response.text())
                .then(html => {
                    const modalContent = document.getElementById('paymentDetailsContent');
                    if (modalContent) {
                        modalContent.innerHTML = html;
                        // แสดง tab แรกหลังจากโหลดข้อมูล
                        showPaymentTab('not_paid');
                    }
                    // แสดง modal
                    const modal = document.getElementById('paymentDetailsModal');
                    if (modal) {
                        modal.classList.remove('hidden');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('เกิดข้อผิดพลาดในการโหลดข้อมูล');
                });
        }

        // เรียกฟังก์ชันเมื่อโหลดหน้าเสร็จ
        document.addEventListener('DOMContentLoaded', function() {
            showPaymentTab('not_paid');
        });
    </script>
    <?php
} 