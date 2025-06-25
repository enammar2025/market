<?php
session_start();

// مسح جميع متغيرات الجلسة
session_unset();

// تدمير الجلسة
session_destroy();

// إعادة توجيه للصفحة الرئيسية
header('Location: index.php');
exit();
?>