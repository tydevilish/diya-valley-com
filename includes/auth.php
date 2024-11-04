<?php
function checkPageAccess($page_id) {
    // ตรวจสอบว่ามี session และ menu_access หรือไม่
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['menu_access'])) {
        header('Location: ../logout.php');
        exit();
    }

    // ตรวจสอบว่ามีสิทธิ์เข้าถึงหน้านี้หรือไม่
    if (!in_array($page_id, $_SESSION['menu_access'])) {
        header('Location: ../logout.php');
        exit();
    }
}

// กำหนด ID ของแต่ละหน้า (ต้องตรงกับ ID ในฐานข้อมูล)
define('PAGE_DASHBOARD', 1);
define('PAGE_PAYMENT', 2);
define('PAGE_REQUEST', 3);
define('PAGE_VIEW_REQUEST', 4);
define('PAGE_MANAGE_PAYMENT', 5);
define('PAGE_MANAGE_REQUEST', 6);
define('PAGE_MANAGE_USERS', 7);
define('PAGE_PERMISSION', 8);