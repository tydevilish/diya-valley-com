<?php
include '../config/config.php';
header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['role_id'])) {
        throw new Exception('ไม่พบ ID ที่ต้องการลบ');
    }

    $roleId = $data['role_id'];

    // ตรวจสอบว่ามี role นี้อยู่หรือไม่
    $stmt = $conn->prepare("SELECT role_id FROM roles WHERE role_id = ?");
    $stmt->execute([$roleId]);
    if (!$stmt->fetch()) {
        throw new Exception('ไม่พบข้อมูลสิทธิ์ที่ต้องการลบ');
    }

    $conn->beginTransaction();

    // ลบข้อมูลการอนุญาต
    $stmt = $conn->prepare("DELETE FROM role_permissions WHERE role_id = ?");
    $stmt->execute([$roleId]);

    // ลบข้อมูลสิทธิ์
    $stmt = $conn->prepare("DELETE FROM roles WHERE role_id = ?");
    $stmt->execute([$roleId]);

    $conn->commit();
    echo json_encode([
        'success' => true,
        'message' => 'ลบข้อมูลสำเร็จ'
    ]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 