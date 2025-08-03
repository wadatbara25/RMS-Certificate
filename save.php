<?php
// إعداد الاتصال بقاعدة البيانات
$serverName = "nursing.database.windows.net";
$connectionOptions = [
    "Database" => "RRS_MANAGEMENT",
    "Uid" => "nurs",
    "PWD" => "P@ssw0rd",
    "CharacterSet" => "UTF-8"
];

// الاتصال
$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) {
    die("❌ فشل الاتصال: " . print_r(sqlsrv_errors(), true));
}

// استعلام لجلب الصور
$sql = "SELECT StudentID, Photo FROM StudentsPhoto";
$stmt = sqlsrv_query($conn, $sql, [], ["Scrollable" => SQLSRV_CURSOR_KEYSET]);

if ($stmt === false) {
    die("❌ خطأ في الاستعلام: " . print_r(sqlsrv_errors(), true));
}

// إنشاء مجلد لحفظ الصور
$folder = __DIR__ . "/saved_images";
if (!is_dir($folder)) {
    if (!mkdir($folder, 0777, true)) {
        die("❌ تعذر إنشاء المجلد: $folder");
    }
}

// حفظ الصور
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $id = $row['StudentID'];
    $imageData = $row['Photo'];

    echo "معرف الطالب: $id<br>";

    if ($imageData !== null) {
        $safeId = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $id);
        $filename = $folder . "/" . $safeId . ".jpg";
        if (file_put_contents($filename, $imageData) !== false) {
            echo "✅ تم حفظ الصورة: " . basename($filename) . "<br>";
        } else {
            echo "⚠️ فشل في حفظ الصورة: " . basename($filename) . "<br>";
        }
    } else {
        echo "⚠️ لا توجد بيانات صورة للمعرّف: $id<br>";
    }
}

// تنظيف وإغلاق الاتصال
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>
