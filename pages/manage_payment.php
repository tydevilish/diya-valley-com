<?php 
session_start();
require_once '../config/config.php';
require_once '../includes/auth.php';
include '../components/Menu.php';

// ตรวจสอบสิทธิ์การเข้าถึงหน้า dashboard
checkPageAccess(PAGE_MANAGE_PAYMENT,);

// เพิ่ม function สำหรับบันทึกค่าส่วนกลาง
function addPayment($month, $year, $amount) {
    global $conn;
    
    try {
        $conn->beginTransaction();
        
        $sql = "INSERT INTO payments (title, amount, due_date, created_at) 
                VALUES (:title, :amount, :due_date, NOW())";
                
        $stmt = $conn->prepare($sql);
        $title = "ค่าส่วนกลางประจำเดือน " . getThaiMonth($month) . " " . $year;
        $due_date = date('Y-m-d', strtotime($year . '-' . $month . '-01'));
        
        $stmt->execute([
            ':title' => $title,
            ':amount' => $amount,
            ':due_date' => $due_date
        ]);
        
        $payment_id = $conn->lastInsertId();
        
        $sql = "INSERT INTO payment_transactions (payment_id, user_id, amount, status)
                SELECT :payment_id, user_id, :amount, 'pending'
                FROM users WHERE role_id = 2";
                
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':payment_id' => $payment_id,
            ':amount' => $amount
        ]);
        
        $conn->commit();
        return true;
        
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log($e->getMessage());
        return false;
    }
}

// แก้ไขฟังก์ชันสำหรับดึงรายชื่อผู้ที่ยังไม่ได้ชำระเงิน
function getUnpaidUsers($payment_id) {
    global $conn;
    
    $sql = "SELECT u.user_id, u.house_no, u.fullname, pt.amount, pt.slip_image, pt.payment_date 
            FROM users u
            INNER JOIN payment_transactions pt ON u.user_id = pt.user_id
            WHERE pt.payment_id = :payment_id AND pt.status = 'pending'
            ORDER BY u.house_no";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute([':payment_id' => $payment_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// เพิ่ม AJAX endpoint สำหรับบันทึกค่าส่วนกลาง
if(isset($_POST['action']) && $_POST['action'] == 'add_payment') {
    $response = array();
    
    if(addPayment(
        $_POST['month'],
        $_POST['year'],
        $_POST['amount']
    )) {
        $response['status'] = 'success';
        $response['message'] = 'บันทึกข้อมูลสำเร็จ';
    } else {
        $response['status'] = 'error';
        $response['message'] = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล';
    }
    
    echo json_encode($response);
    exit;
}

// เพิ่มฟังก์ชันสำหรับคำนวณยอดรวม
function calculateTotalAmount($users) {
    return array_reduce($users, function($total, $user) {
        return $total + floatval($user['amount']);
    }, 0);
}

// แก้ไข AJAX endpoint สำหรับดึงรายชื่อผู้ที่ยังไม่ได้ชำระเงิน
if(isset($_GET['action']) && $_GET['action'] == 'get_unpaid_users') {
    try {
        $payment_id = $_GET['payment_id'];
        
        $sql = "SELECT pt.transaction_id, pt.payment_id, pt.user_id, pt.amount, pt.slip_image, pt.payment_date, pt.status,
                       u.house_no, u.fullname 
                FROM payment_transactions pt
                INNER JOIN users u ON pt.user_id = u.user_id
                WHERE pt.payment_id = :payment_id
                ORDER BY pt.status ASC, u.house_no ASC";
                
        $stmt = $conn->prepare($sql);
        $stmt->execute([':payment_id' => $payment_id]);
        
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $totalAmount = calculateTotalAmount($users);
        
        header('Content-Type: application/json');
        echo json_encode([
            'users' => $users,
            'totalAmount' => $totalAmount
        ]);
        
    } catch (PDOException $e) {
        error_log($e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(['error' => 'เกิดข้อผิดพลาดในการดึงข้อมูล']);
    }
    exit;
}

// เพิ่มฟังก์ชันสำหรับดึงข้อมูลค่าส่วนกลางทั้งหมด
function getAllPayments() {
    global $conn;
    
    $sql = "SELECT p.*, 
            (SELECT COUNT(*) FROM payment_transactions pt 
             WHERE pt.payment_id = p.payment_id AND pt.status = 'pending') as unpaid_count,
            (SELECT COUNT(*) FROM payment_transactions pt 
             WHERE pt.payment_id = p.payment_id AND pt.status = 'approved') as paid_count
            FROM payments p 
            ORDER BY p.created_at DESC";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt;
}

// เพิ่มฟังก์ชันสำหรับแปลงเลขเดือนเป็นชื่อเดือนภาษาไทย
function getThaiMonth($month) {
    $thaiMonths = [
        1 => 'มกราคม',
        2 => 'กุมภาพันธ์',
        3 => 'มีนาคม',
        4 => 'เมษายน',
        5 => 'พฤษภาคม',
        6 => 'มิถุนายน',
        7 => 'กรกฎาคม',
        8 => 'สิงหาคม',
        9 => 'กันยายน',
        10 => 'ตุลาคม',
        11 => 'พฤศจิกายน',
        12 => 'ธันวาคม'
    ];
    return $thaiMonths[$month];
}

// แก้ไข endpoint สำหรับยกเลิกค่าส่วนกลาง
if(isset($_POST['action']) && $_POST['action'] == 'cancel_payment') {
    $response = array();
    
    try {
        $payment_id = $_POST['payment_id'];
        
        $conn->beginTransaction();
        
        // ลบข้อมูลในตาราง payment_transactions ก่อน (เพราะเป็น foreign key)
        $sql = "DELETE FROM payment_transactions WHERE payment_id = :payment_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':payment_id' => $payment_id]);
        
        // จากนั้นลบข้อมูลในตาราง payments
        $sql = "DELETE FROM payments WHERE payment_id = :payment_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':payment_id' => $payment_id]);
        
        $conn->commit();
        
        $response['status'] = 'success';
        $response['message'] = 'ยกเลิกรายการสำเร็จ';
    } catch (PDOException $e) {
        $conn->rollBack();
        $response['status'] = 'error';
        $response['message'] = 'เกิดข้อผิดพลาดในการยกเลิกรายการ';
        error_log($e->getMessage());
    }
    
    echo json_encode($response);
    exit;
}

// เพิ่มฟังก์ชันสำหรับนับจำนวนผู้ที่ยังไม่ได้ชำระ
function getTotalUnpaidCount() {
    global $conn;
    
    $sql = "SELECT COUNT(*) as unpaid_count 
            FROM payment_transactions 
            WHERE status = 'pending'";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC)['unpaid_count'];
}

// ดึข้อมูลจำนวนผู้ที่ยังไม่ได้ชำระ
$unpaidCount = getTotalUnpaidCount();

// ดึงรายละเอียดการชำระเงิน
if (isset($_GET['action']) && $_GET['action'] == 'get_payment_detail') {
    $transaction_id = $_GET['transaction_id'];
    
    try {
        $sql = "SELECT pt.*, p.title, p.amount, u.house_no, u.fullname,
                       pt.payment_date, pt.slip_image, pt.status
                FROM payment_transactions pt
                INNER JOIN payments p ON pt.payment_id = p.payment_id
                INNER JOIN users u ON pt.user_id = u.user_id
                WHERE pt.transaction_id = :transaction_id";
                
        $stmt = $conn->prepare($sql);
        $stmt->execute([':transaction_id' => $transaction_id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // เพิ่ม URL เต็มของสลิป
        if ($data['slip_image']) {
            $data['slip_image'] = '../uploads/slips/' . $data['slip_image'];
        }
        
        echo json_encode($data);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        echo json_encode(['error' => 'เกิดข้อผิดพลาดในการดึงข้อมูล']);
    }
    exit;
}

// อนุมัติการชำระเงิน
if (isset($_POST['action']) && $_POST['action'] == 'approve_payment') {
    $response = ['status' => 'error', 'message' => ''];
    
    try {
        $conn->beginTransaction();
        
        $sql = "UPDATE payment_transactions 
                SET status = 'approved', 
                    approved_at = NOW(), 
                    approved_by = :admin_id 
                WHERE transaction_id = :transaction_id";
                
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':admin_id' => $_SESSION['user_id'],
            ':transaction_id' => $_POST['transaction_id']
        ]);
        
        $conn->commit();
        $response['status'] = 'success';
        $response['message'] = 'อนุมัติการชำระเงินเรียบร้อย';
    } catch (PDOException $e) {
        $conn->rollBack();
        $response['message'] = 'เกิดข้อผิดพลาดในการอนุมัติ';
        error_log($e->getMessage());
    }
    
    echo json_encode($response);
    exit;
}

// ไม่อนุมัติการชำระเงิน
if (isset($_POST['action']) && $_POST['action'] == 'reject_payment') {
    $response = ['status' => 'error', 'message' => ''];
    
    try {
        $conn->beginTransaction();
        
        $sql = "UPDATE payment_transactions 
                SET status = 'rejected', 
                    reject_reason = :reason,
                    rejected_at = NOW(), 
                    rejected_by = :admin_id 
                WHERE transaction_id = :transaction_id";
                
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':reason' => $_POST['reason'],
            ':admin_id' => $_SESSION['user_id'],
            ':transaction_id' => $_POST['transaction_id']
        ]);
        
        $conn->commit();
        $response['status'] = 'success';
        $response['message'] = 'ปฏิเสธการชำระเงินเรียบร้อย';
    } catch (PDOException $e) {
        $conn->rollBack();
        $response['message'] = 'เกิดข้อผิดพลาดในการปฏิเสธ';
        error_log($e->getMessage());
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

    </div>

    <div class="flex-1 ml-20">
        <!-- Top Navigation -->
        <nav class="bg-white shadow-sm px-6 py-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <button id="toggleSidebar" class="p-2 hover:bg-gray-100 rounded-lg">
                        <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <h1 class="text-2xl font-bold text-eva ml-4">จัดการค่าส่วนกลาง</h1>
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

                        <!-- เ่มกล่องแจ้งเตือนใต้กระดิ่ง -->
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
                                                <p class="text-sm font-medium text-gray-800">ค่ส่วนกลางประจำเดือนมีนาคม 2567</p>
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

                            // ปิดเื่อคลิที่อื่น
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
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">รายการค่าส่วนกลางทั้งหมด</h2>
                    <?php if ($unpaidCount > 0): ?>
                        <p class="text-sm text-red-500 mt-1">
                            ยังไม่ได้ชำระ: <?= number_format($unpaidCount) ?> ราย
                        </p>
                    <?php else: ?>
                        <p class="text-sm text-green-500 mt-1">
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                ชำระเงินครบทุกรายการแล้ว
                            </span>
                        </p>
                    <?php endif; ?>
                </div>
                <button onclick="showAddModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    เพิ่มค่าส่วนกลาง
                </button>
            </div>

            <!-- ตารางแสดงข้อมูล -->
            <div class="flex flex-col mt-6">
                <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                    <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                        <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ลำดับ</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วันที่สร้าง</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">รายการ</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">จำนวนเงิน</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php 
                                    $stmt = getAllPayments();
                                    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    if (empty($payments)): ?>
                                        <tr>
                                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                                <div class="flex flex-col items-center">
                                                    <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    <p class="text-lg font-medium">ไม่พบรายการ</p>
                                                    <p class="text-sm text-gray-400">ยังไม่มีการเพิ่มรายการค่าส่วนกลาง</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: 
                                        $i = 1;
                                        foreach($payments as $row): 
                                            $status_class = $row['unpaid_count'] > 0 ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800';
                                            $status_text = $row['unpaid_count'] > 0 ? "รอชำระเงิน ({$row['unpaid_count']} ราย)" : 'ชำระแล้ว';
                                    ?>
                                    <tr class="hover:bg-gray-50" data-payment-id="<?= $row['payment_id'] ?>">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= $i++ ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= date('d/m/Y', strtotime($row['created_at'])) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($row['title']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= number_format($row['amount'], 2) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $status_class ?>">
                                                <?= $status_text ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-3">
                                                <button onclick="showUnpaidModal(<?= $row['payment_id'] ?>)" 
                                                        class="text-blue-600 hover:text-blue-900 font-medium">
                                                    ดูรายละเอียด
                                                </button>
                                                <?php if ($row['unpaid_count'] > 0): ?>
                                                <button onclick="showCancelModal(<?= $row['payment_id'] ?>, '<?= htmlspecialchars($row['title']) ?>')" 
                                                        class="text-red-600 hover:text-red-900 font-medium">
                                                    ยกเลิก
                                                </button>
                                                <?php endif; ?>
                                            </div>
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
        </div>
    </div>

    <!-- Payment Modal -->
    <div id="paymentModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
        <div class="relative p-5 border w-full max-w-lg shadow-lg rounded-md bg-white mx-4">
            <div class="mt-3">
                <div class="flex justify-between items-center pb-3">
                    <h3 class="text-lg leading-6 font-bold text-blue-500">ชระเงิน</h3>
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
                            <li>พร้อมเพ์: 089-123-4567</li>
                        </ul>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            อัพโหลดสลิปการโนเงิน
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
    <div id="detailModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="relative p-8 bg-white w-full max-w-lg rounded-lg shadow-xl">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-gray-900">รยชือู้ท่ชำระล้ว</h3>
                    <button onclick="closeDetailModal()" class="text-gray-400 hover:text-gray-500">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <!-- รายการผู้ที่ชำระแล้ว -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="font-medium">บ้านเลขที่ 123/9</p>
                                    <p class="text-sm text-gray-600">นายมานะ ตั้งใจ</p>
                                    <p class="text-xs text-gray-500">ชำระเมื่อ: 01/02/2024 10:30</p>
                                </div>
                                <button onclick="showSlipModal('slip1.jpg', 'บ้านเลขที่ 123/9')" class="text-blue-600 hover:text-blue-800 text-sm">
                                    ดูสลิป
                                </button>
                            </div>
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="font-medium">บ้านเลขที่ 123/10</p>
                                    <p class="text-sm text-gray-600">นางสาวรักดี มีสุข</p>
                                    <p class="text-xs text-gray-500">ชำระเมื่อ: 01/02/2024 11:15</p>
                                </div>
                                <button onclick="showSlipModal('slip2.jpg', 'บ้านเลขที่ 123/10')" class="text-blue-600 hover:text-blue-800 text-sm">
                                    ดูสลิป
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="border-t pt-4">
                        <div class="flex justify-between items-center font-medium">
                            <span>รวมทั้งหมด</span>
                            <span class="text-green-600">3,500 บาท</span>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button onclick="closeDetailModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                        ปิด
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal เิ่มค่าส่วนกลาง -->
    <div id="addModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl w-full max-w-md mx-4 shadow-2xl transform transition-all">
                <!-- หัวข้อ Modal -->
                <div class="px-6 py-4 border-b flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="bg-blue-100 rounded-lg p-2">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900">เพิ่มค่าส่วนกลาง</h3>
                    </div>
                    <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- เนื้อหา Modal -->
                <form id="addPaymentForm" class="p-6 space-y-6">
                    <div class="grid grid-cols-2 gap-6">
                        <!-- เดือน -->
                        <div class="relative">
                            <label class="block text-sm font-medium text-gray-700 mb-2">เดือน</label>
                            <select id="paymentMonth" 
                                    class="block w-full pl-3 pr-10 py-2.5 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 rounded-lg shadow-sm" 
                                    onchange="validateForm()">
                                <option value="">เลือกเดือน</option>
                                <?php for($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?= $i ?>"><?= getThaiMonth($i) ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <!-- ปี -->
                        <div class="relative">
                            <label class="block text-sm font-medium text-gray-700 mb-2">ปี</label>
                            <select id="paymentYear" 
                                    class="block w-full pl-3 pr-10 py-2.5 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 rounded-lg shadow-sm" 
                                    onchange="validateForm()">
                                <option value="">เลือกปี</option>
                                <?php 
                                $currentYear = date('Y');
                                for($i = $currentYear - 1; $i <= $currentYear + 1; $i++): 
                                ?>
                                    <option value="<?= $i ?>"><?= $i + 543 ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>

                    <!-- จำนวนเงิน -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">จำนวนเงิน</label>
                        <div class="relative rounded-lg shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">฿</span>
                            </div>
                            <input type="number" 
                                   id="paymentAmount" 
                                   class="block w-full pl-8 pr-12 py-2.5 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 rounded-lg" 
                                   placeholder="0.00"
                                   onchange="validateForm()" 
                                   onkeyup="validateForm()">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">บาท</span>
                            </div>
                        </div>
                    </div>

                    <!-- ปุ่มกดด้านล่าง -->
                    <div class="flex justify-end space-x-3 pt-4 border-t">
                        <button type="button" 
                                onclick="closeAddModal()" 
                                class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            ยกเลิก
                        </button>
                        <button type="button" 
                                onclick="submitAdd()" 
                                id="submitButton"
                                class="px-4 py-2.5 text-sm font-medium text-gray-500 bg-gray-300 rounded-lg cursor-not-allowed transition-all duration-200" 
                                disabled>
                            บันทึก
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- เพิ่ม Modal แสดงรายชื่อผู้ที่ยังไม่ได้ชำระ -->
    <div id="unpaidModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="relative p-8 bg-white w-full max-w-lg rounded-lg shadow-xl">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-gray-900">รายชื่อผู้ที่ยังไม่ได้ชำระ</h3>
                    <button onclick="closeUnpaidModal()" class="text-gray-400 hover:text-gray-500">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <!-- รายการผู้ที่ยังไม่ไ้ชำระ -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="font-medium">บ้านเลขที่ 123/1</p>
                                    <p class="text-sm text-gray-600">นายสมชาย ใจดี</p>
                                </div>
                                <span class="text-red-600 font-medium">500 บาท</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="font-medium">บ้านเลขที่ 123/2</p>
                                    <p class="text-sm text-gray-600">นางสาวสมหญิง รักดี</p>
                                </div>
                                <span class="text-red-600 font-medium">500 บาท</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="font-medium">บ้านเลขที่ 123/3</p>
                                    <p class="text-sm text-gray-600">นายวิชัย มั่งมี</p>
                                </div>
                                <span class="text-red-600 font-medium">500 บาท</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="font-medium">บ้านเลขที่ 123/4</p>
                                    <p class="text-sm text-gray-600">นางนิภา สุขใจ</p>
                                </div>
                                <span class="text-red-600 font-medium">500 บาท</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="font-medium">บ้านเลขที่ 123/5</p>
                                    <p class="text-sm text-gray-600">นายธีรศักดิ์ ดงาม</p>
                                </div>
                                <span class="text-red-600 font-medium">500 บาท</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="font-medium">บ้านเลขที่ 123/6</p>
                                    <p class="text-sm text-gray-600">นางสาวพิมพ์ใจ รักเรียน</p>
                                </div>
                                <span class="text-red-600 font-medium">500 บาท</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="font-medium">บ้านเลขที่ 123/7</p>
                                    <p class="text-sm text-gray-600">นายภาคภูมิ ภูมิใจ</p>
                                </div>
                                <span class="text-red-600 font-medium">500 บาท</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="font-medium">บ้านเลขที่ 123/8</p>
                                    <p class="text-sm text-gray-600">นางสาวรักษ์ รักษ์ดี</p>
                                </div>
                                <span class="text-red-600 font-medium">500 บาท</span>
                            </div>
                        </div>
                    </div>

                    <div class="border-t pt-4">
                        <div class="flex justify-between items-center font-medium">
                            <span>รวมทั้งหมด</span>
                            <span class="text-red-600">4,000 บาท</span>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button onclick="closeUnpaidModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                        ปิด
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal แสดงรายชื่อผู้ที่ชำระแล้ว -->
    <div id="paidModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="relative p-8 bg-white w-full max-w-lg rounded-lg shadow-xl">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-gray-900">รายชื่อผู้ที่ชำระแล้ว</h3>
                    <button onclick="closePaidModal()" class="text-gray-400 hover:text-gray-500">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <!-- รายการผู้ที่ชำระแล้ว -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="font-medium">บ้านเลขที่ 123/9</p>
                                    <p class="text-sm text-gray-600">นายมานะ ตั้งใจ</p>
                                    <p class="text-xs text-gray-500">ชำระเมื่อ: 01/02/2024 10:30</p>
                                </div>
                                <button onclick="showSlipModal('slip1.jpg', 'บ้านเลขที่ 123/9')" class="text-blue-600 hover:text-blue-800 text-sm">
                                    ดูสลิป
                                </button>
                            </div>
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="font-medium">บ้านเลขที่ 123/10</p>
                                    <p class="text-sm text-gray-600">นางสาวรักดี มีสุข</p>
                                    <p class="text-xs text-gray-500">ชำระเมื่อ: 01/02/2024 11:15</p>
                                </div>
                                <button onclick="showSlipModal('slip2.jpg', 'บ้านเลขที่ 123/10')" class="text-blue-600 hover:text-blue-800 text-sm">
                                    ดูสลิป
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="border-t pt-4">
                        <div class="flex justify-between items-center font-medium">
                            <span>รวมท้งหมด</span>
                            <span class="text-green-600">1,000 บาท</span>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button onclick="closePaidModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                        ปิด
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal แสดงสลิป -->
    <div id="slipModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="relative p-8 bg-white w-full max-w-md rounded-lg shadow-xl">
                <div class="flex justify-between items-center mb-6">
                    <h3 id="slipModalTitle" class="text-xl font-bold text-gray-900">สลิปการโอนเงิน</h3>
                    <button onclick="closeSlipModal()" class="text-gray-400 hover:text-gray-500">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="flex justify-center">
                    <img id="slipImage" src="https://img2.pic.in.th/pic/462554817_573567848662882_2984175964874345892_n.jpg"
                        alt="สลิปการโอนเงิน"
                        class="max-h-96 rounded-lg shadow-sm">
                </div>

                <div class="mt-6 flex justify-end">
                    <button onclick="closeSlipModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                        ปิด
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal ยืนยันการยกเลิก -->
    <div id="cancelModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl w-full max-w-md mx-4 shadow-2xl transform transition-all">
                <!-- หัวข้อ Modal -->
                <div class="px-6 py-4 border-b flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="bg-red-100 rounded-lg p-2">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900">ยืนยันการยกเลิก</h3>
                    </div>
                    <button onclick="closeCancelModal()" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- เนื้อหา Modal -->
                <div class="p-6">
                    <input type="hidden" id="cancelPaymentId">
                    <div class="bg-red-50 rounded-lg p-4 mb-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">คุณกำลังจะยกเลิกรายการ</h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <p id="cancelPaymentTitle" class="font-medium"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <p class="text-sm text-gray-500 mb-6">การดำเนินการนี้ไม่สามารถเรียกคืนได้ และข้อมูลทั้งหมดที่เกี่ยวข้องจะถูกลบออกจากระบบ</p>
                
                    <!-- ปุ่มกดด้านล่าง -->
                    <div class="flex justify-end space-x-3 pt-4 border-t">
                        <button type="button" 
                                onclick="closeCancelModal()" 
                                class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            ยกเลิก
                        </button>
                        <button type="button" 
                                onclick="confirmCancel()" 
                                class="px-4 py-2.5 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            ยืนยันการยกเลิก
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal แสดงรายละเอียดการชำระเงิน -->
    <div id="paymentDetailModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="relative p-8 bg-white w-full max-w-lg rounded-lg shadow-xl">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-gray-900">รายละเอียดการชำระเงิน</h3>
                    <button onclick="closePaymentDetailModal()" class="text-gray-400 hover:text-gray-500">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <!-- ข้อมูลกาชำระเงิน -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="grid grid-cols-2 gap-4 text-sm mb-4">
                            <div class="text-gray-600">บ้านเลขที่:</div>
                            <div class="font-medium" id="modalHouseNo"></div>
                            <div class="text-gray-600">ชื่อผู้ชำระ:</div>
                            <div class="font-medium" id="modalFullname"></div>
                            <div class="text-gray-600">จำนวนเงิน:</div>
                            <div class="font-medium" id="modalAmount"></div>
                            <div class="text-gray-600">วันที่ชำระ:</div>
                            <div class="font-medium" id="modalPaymentDate"></div>
                        </div>

                        <!-- รูปสลิป -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                สลิปการโอนเงิน
                            </label>
                            <div class="mt-1 flex justify-center">
                                <img id="modalSlipImage" class="max-h-64 rounded-lg shadow-sm cursor-pointer" 
                                     onclick="openFullImage(this.src)" alt="สลิปการโอนเงิน">
                            </div>
                        </div>

                        <!-- ปุ่มอนุมัติ/ไม่อนุมัติ -->
                        <div class="flex justify-end space-x-3 mt-6">
                            <input type="hidden" id="modalPaymentId">
                            <input type="hidden" id="modalTransactionId">
                            <button onclick="rejectPayment()" 
                                    class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                                ไม่อนุมัติ
                            </button>
                            <button onclick="approvePayment()" 
                                    class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                                อนุมัติ
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
    /* เพิ่ม Animation เมื่อ Modal แสดง */
    @keyframes modalFadeIn {
        from {
            opacity: 0;
            transform: scale(0.95);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    #addModal .transform {
        animation: modalFadeIn 0.3s ease-out;
    }

    /* ปรับแต่ง Input Number ให้ไม่แสดงปุ่มเพิ่ม/ลด */
    input[type=number]::-webkit-inner-spin-button, 
    input[type=number]::-webkit-outer-spin-button { 
        -webkit-appearance: none;
        margin: 0;
    }
    input[type=number] {
        -moz-appearance: textfield;
    }
    </style>

    <script>
    // แก้ไขฟังก์ชัน validateForm
    function validateForm() {
        const month = document.getElementById('paymentMonth').value;
        const year = document.getElementById('paymentYear').value;
        const amount = document.getElementById('paymentAmount').value;
        const submitButton = document.getElementById('submitButton');

        if (month && year && amount > 0) {
            submitButton.disabled = false;
            submitButton.classList.remove('bg-gray-300', 'text-gray-500', 'cursor-not-allowed');
            submitButton.classList.add('bg-blue-600', 'text-white', 'hover:bg-blue-700', 'shadow-sm');
        } else {
            submitButton.disabled = true;
            submitButton.classList.add('bg-gray-300', 'text-gray-500', 'cursor-not-allowed');
            submitButton.classList.remove('bg-blue-600', 'text-white', 'hover:bg-blue-700', 'shadow-sm');
        }
    }

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

    function showAddModal() {
        document.getElementById('addModal').classList.remove('hidden');
        // รีเซ็ตฟอร์มเมื่อเปิด Modal
        document.getElementById('addPaymentForm').reset();
        validateForm(); // เรียกใช้เพื่อรีเซ็ตสถานะปุ่ม
    }

    function closeAddModal() {
        document.getElementById('addModal').classList.add('hidden');
        // รีเซ็ตฟอร์มเมือปิด Modal
        document.getElementById('addPaymentForm').reset();
        validateForm(); // เรียกใช้เพื่อรีเซ็ตสถานะปุ่ม
    }

    function submitAdd() {
        const month = document.getElementById('paymentMonth').value;
        const year = document.getElementById('paymentYear').value;
        const amount = document.getElementById('paymentAmount').value;

        if (month && year && amount > 0) {
            fetch('manage_payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=add_payment&month=${month}&year=${year}&amount=${amount}`
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if(data.status === 'success') {
                    closeAddModal();
                    location.reload();
                }
            });
        }
    }

    function showUnpaidModal(payment_id) {
        fetch(`manage_payment.php?action=get_unpaid_users&payment_id=${payment_id}`)
        .then(response => response.json())
        .then(data => {
            const container = document.querySelector('#unpaidModal .bg-gray-50');
            container.innerHTML = data.users.map(user => {
                console.log('User data:', user); // Debug
                
                const slipButton = user.slip_image && user.slip_image !== 'NULL'
                    ? `<button onclick="showSlipModal('${user.slip_image}', '${user.house_no}')" class="text-blue-600 hover:text-blue-800 text-sm">ดูสลิป</button>`
                    : `<span class="text-red-600 font-medium">${user.amount} บาท</span>`;
                
                return `
                    <div class="flex justify-between items-center p-3 border-b">
                        <div>
                            <p class="font-medium">บ้านเลขที่ ${user.house_no}</p>
                            <p class="text-sm text-gray-600">${user.fullname}</p>
                            ${user.payment_date ? `<p class="text-xs text-gray-500">ชำระเมื่อ: ${formatDate(user.payment_date)}</p>` : ''}
                        </div>
                        ${slipButton}
                    </div>
                `;
            }).join('');
            
            // อัพเดทยอดรวม
            const totalElement = document.querySelector('#unpaidModal .border-t .text-red-600');
            totalElement.textContent = `${Number(data.totalAmount).toLocaleString()} บาท`;
            
            document.getElementById('unpaidModal').classList.remove('hidden');
        });
    }

    function closeUnpaidModal() {
        document.getElementById('unpaidModal').classList.add('hidden');
    }

    function showSlipModal(slipImage, houseNumber) {
        console.log('Opening slip:', slipImage); // Debug
        
        const modal = document.getElementById('slipModal');
        const title = document.getElementById('slipModalTitle');
        const image = document.getElementById('slipImage');

        title.textContent = `สลิปการโอนเงิน - บ้านเลขที่ ${houseNumber}`;
        
        // ถ้า slip_image เป็น NULL ให้แสดงข้อความ
        if (slipImage === 'NULL' || !slipImage) {
            alert('ไม่พบรูปสลิปการโอนเงิน');
            return;
        }
        
        // สร้าง URL เต็มสำหรับรูปภาพ โดยเพิ่ม .. เพื่อย้อนกลับไปหนึ่งระดับ
        const slipUrl = `../uploads/slips/${slipImage}`;
        console.log('Full URL:', slipUrl); // Debug
        
        image.src = slipUrl;
        
        image.onload = function() {
            console.log('Image loaded successfully'); // Debug
            modal.classList.remove('hidden');
        };
        
        image.onerror = function() {
            console.error('Failed to load image:', slipUrl); // Debug
            alert('ไม่สามารถโหลดรูปสลิปได้');
        };
    }

    function closeSlipModal() {
        document.getElementById('slipModal').classList.add('hidden');
    }

    function closePaidModal() {
        document.getElementById('paidModal').classList.add('hidden');
    }

    const sidebar = document.getElementById('sidebar');
    const toggleSidebarButton = document.getElementById('toggleSidebar');
    let isSidebarOpen = false;

    toggleSidebarButton.addEventListener('click', () => {
        isSidebarOpen = !isSidebarOpen;
        if (isSidebarOpen) {
            sidebar.classList.remove('w-20');
            sidebar.classList.add('w-64');
        } else {
            sidebar.classList.remove('w-64');
            sidebar.classList.add('w-20');
        }
    });

    // ปิด sidebar เมื่อคลิกนอกพื้นที่
    document.addEventListener('click', (e) => {
        if (isSidebarOpen && !sidebar.contains(e.target) && e.target !== toggleSidebarButton) {
            isSidebarOpen = false;
            sidebar.classList.remove('w-64');
            sidebar.classList.add('w-20');
        }
    });

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

    function showCancelModal(paymentId, title) {
        document.getElementById('cancelPaymentId').value = paymentId;
        document.getElementById('cancelPaymentTitle').textContent = title;
        document.getElementById('cancelModal').classList.remove('hidden');
    }

    function closeCancelModal() {
        document.getElementById('cancelModal').classList.add('hidden');
    }

    function confirmCancel() {
        const paymentId = document.getElementById('cancelPaymentId').value;
        
        // แสดง loading state
        const confirmButton = event.target;
        const originalContent = confirmButton.innerHTML;
        confirmButton.disabled = true;
        confirmButton.innerHTML = `
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            กำลังดำเนินการ...
        `;
        
        fetch('manage_payment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=cancel_payment&payment_id=${paymentId}`
        })
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
                alert(data.message);
                closeCancelModal();
                // รีเฟรชหน้าเว็บ
                window.location.reload();
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('เกิดข้อผิดพลาดในการดำเนินการ');
        })
        .finally(() => {
            // คืนค่าปุ่มกลับสู่สถานะปกติ
            confirmButton.disabled = false;
            confirmButton.innerHTML = originalContent;
        });
    }

    function showPaymentDetailModal(transactionId) {
    fetch(`manage_payment.php?action=get_payment_detail&transaction_id=${transactionId}`)
    .then(response => response.json())
    .then(data => {
        document.getElementById('modalHouseNo').textContent = data.house_no;
        document.getElementById('modalFullname').textContent = data.fullname;
        document.getElementById('modalAmount').textContent = `${Number(data.amount).toLocaleString()} บาท`;
        document.getElementById('modalPaymentDate').textContent = new Date(data.payment_date).toLocaleString('th-TH');
        
        const slipImage = document.getElementById('modalSlipImage');
        if (data.slip_image) {
            slipImage.src = data.slip_image;
            slipImage.classList.remove('hidden');
        } else {
            slipImage.classList.add('hidden');
        }
        
        document.getElementById('modalTransactionId').value = data.transaction_id;
        
        // แสดง/ซ่อนปุ่มตามสถานะ
        const approveBtn = document.getElementById('approveButton');
        const rejectBtn = document.getElementById('rejectButton');
        if (data.status === 'pending') {
            approveBtn.classList.remove('hidden');
            rejectBtn.classList.remove('hidden');
        } else {
            approveBtn.classList.add('hidden');
            rejectBtn.classList.add('hidden');
        }
        
        document.getElementById('paymentDetailModal').classList.remove('hidden');
    });
}

    function closePaymentDetailModal() {
        document.getElementById('paymentDetailModal').classList.add('hidden');
    }

    function openFullImage(src) {
        window.open(src, '_blank');
    }

    function approvePayment() {
        const paymentId = document.getElementById('modalPaymentId').value;
        const transactionId = document.getElementById('modalTransactionId').value;
        
        if (confirm('ยืนยันการอนุมัติการชำระงิน?')) {
            fetch('manage_payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=approve_payment&payment_id=${paymentId}&transaction_id=${transactionId}`
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.status === 'success') {
                    closePaymentDetailModal();
                    window.location.reload();
                }
            });
        }
    }

    function rejectPayment() {
        const paymentId = document.getElementById('modalPaymentId').value;
        const transactionId = document.getElementById('modalTransactionId').value;
        
        const reason = prompt('กรุณาระบุเหตุผลที่ไม่อนุมัติ:');
        if (reason) {
            fetch('manage_payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=reject_payment&payment_id=${paymentId}&transaction_id=${transactionId}&reason=${encodeURIComponent(reason)}`
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.status === 'success') {
                    closePaymentDetailModal();
                    window.location.reload();
                }
            });
        }
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        const day = date.getDate().toString().padStart(2, '0');
        const month = (date.getMonth() + 1).toString().padStart(2, '0');
        const year = date.getFullYear();
        const hours = date.getHours().toString().padStart(2, '0');
        const minutes = date.getMinutes().toString().padStart(2, '0');
        return `${day}/${month}/${year} ${hours}:${minutes}`;
    }
    </script>
</body>

</html>