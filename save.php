<?php
set_time_limit(0);

// الاتصال بقاعدة البيانات
$serverName = "veterinary.database.windows.net";
$connectionOptions = [
    "Database" => "RRS_MANAGEMENT",
    "Uid" => "vet",
    "PWD" => "P@ssw0rd",
    "CharacterSet" => "UTF-8"
];
$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) {
    die("❌ فشل الاتصال: " . print_r(sqlsrv_errors(), true));
}

// مجلد الصور
$folder = __DIR__ . "/saved_images";
if (!is_dir($folder) && !mkdir($folder, 0777, true)) {
    die("❌ تعذر إنشاء المجلد: $folder");
}

$batchSize = 1000; // حجم الدفعة
$offset = 0;

while (true) {
    $sql = "
        SELECT StudentID, Photo
        FROM StudentsPhoto
        ORDER BY StudentID
        OFFSET ? ROWS FETCH NEXT ? ROWS ONLY
    ";
    $stmt = sqlsrv_query($conn, $sql, [$offset, $batchSize]);
    if ($stmt === false) {
        die("❌ خطأ في الاستعلام: " . print_r(sqlsrv_errors(), true));
    }

    $rowsFetched = 0;

    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $rowsFetched++;
        $id = $row['StudentID'];
        $imageData = $row['Photo'];

        echo "معرف الطالب: $id — ";

        if ($imageData !== null) {
            $safeId = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $id);
            $filename = $folder . "/" . $safeId . ".jpg";
            if (file_put_contents($filename, $imageData) !== false) {
                echo "✅ تم الحفظ: " . basename($filename) . "<br>";
            } else {
                echo "⚠️ فشل الحفظ<br>";
            }
        } else {
            echo "⚠️ لا توجد صورة<br>";
        }
    }

    sqlsrv_free_stmt($stmt);

    if ($rowsFetched < $batchSize) {
        echo "✅ انتهى التحميل<br>";
        break;
    }

    $offset += $batchSize;
}

sqlsrv_close($conn);
?>
