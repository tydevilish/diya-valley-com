<?php
session_start();
require_once '../config/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    // ตรวจสอบว่ามีการ login หรือไม่
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('กรุณาเข้าสู่ระบบ');
    }

    $user_id = $_SESSION['user_id'];
    
    // รับค่าจากฟอร์ม
    $fullname = $_POST['fullname'];
    $phone = $_POST['phone'];
    $house_no = $_POST['house_no'];
    $village = $_POST['village'];
    $road = $_POST['road'];
    $subdistrict = $_POST['subdistrict'];
    $district = $_POST['district'];
    $province = $_POST['province'];
    $postal_code = $_POST['postal_code'];

    // จัดการอัพโหลดรูปภาพ (ถ้ามี)
    $profile_image = null;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/profiles/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $new_filename = $user_id . '_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;

        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
            $profile_image = $new_filename;
        }
    }

    // อัพเดทข้อมูลในฐานข้อมูล
    $sql = "UPDATE users SET 
            fullname = ?, 
            phone = ?,
            house_no = ?,
            village = ?,
            road = ?,
            subdistrict = ?,
            district = ?,
            province = ?,
            postal_code = ?";
    
    $params = [$fullname, $phone, $house_no, $village, $road, $subdistrict, $district, $province, $postal_code];

    // เพิ่ม profile_image เข้าไปในคำสั่ง SQL ถ้ามีการอัพโหลดรูป
    if ($profile_image) {
        $sql .= ", profile_image = ?";
        $params[] = $profile_image;
    }

    $sql .= " WHERE user_id = ?";
    $params[] = $user_id;

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    echo json_encode([
        'status' => 'success',
        'message' => 'บันทึกข้อมูลเรียบร้อยแล้ว'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 