<?php
// ==========================
// Database Server Configurations
// ==========================

$servers = [
    '1' => ['server' => 'computer.database.windows.net',   'database' => 'RRS_MANAGEMENT', 'username' => 'computer', 'password' => 'P@ssw0rd'],
    '2' => ['server' => 'econamic.database.windows.net',    'database' => 'RRS_MANAGEMENT', 'username' => 'eco',      'password' => 'P@ssw0rd'],
    '3' => ['server' => 'educations.database.windows.net',  'database' => 'RRS_MANAGEMENT', 'username' => 'edu',      'password' => 'P@ssw0rd'],
    '4' => ['server' => 'lawbut.database.windows.net',      'database' => 'RRS_MANAGEMENT', 'username' => 'law',      'password' => 'P@ssw0rd'],
    '5' => ['server' => 'laborator.database.windows.net',   'database' => 'RRS_MANAGEMENT', 'username' => 'lms',      'password' => 'P@ssw0rd'],
    '6' => ['server' => 'nursing.database.windows.net',     'database' => 'RRS_MANAGEMENT', 'username' => 'nurs',     'password' => 'P@ssw0rd'],
    '7' => ['server' => 'veterinary.database.windows.net',  'database' => 'RRS_MANAGEMENT', 'username' => 'vet',      'password' => 'P@ssw0rd'],
    '8' => ['server' => 'med.database.windows.net',         'database' => 'RRS_MANAGEMENT', 'username' => 'med',      'password' => 'P@ssw0rd'],
    '9' => ['server' => 'econamic.database.windows.net',    'database' => 'RRS_Diploma',    'username' => 'eco',      'password' => 'P@ssw0rd'],
    '10'=> ['server' => 'educations.database.windows.net',  'database' => 'RRS_Diploma',    'username' => 'edu',      'password' => 'P@ssw0rd'],
];

// ==========================
// Database Connection Function
// ==========================

function connectToDatabase($selected_server) {
    global $servers;
    $config = $servers[$selected_server] ?? $servers['1'];

    $connectionInfo = [
        "Database" => $config['database'],
        "UID" => $config['username'],
        "PWD" => $config['password'],
        "CharacterSet" => "UTF-8"
    ];

    $conn = sqlsrv_connect($config['server'], $connectionInfo);

    if ($conn === false) {
        die("فشل الاتصال بالخادم [{$config['server']}]:<br>" . print_r(sqlsrv_errors(), true));
    }

    return $conn;
}

// ==========================
// Data Fetch Functions
// ==========================

function getUserByUsername($selectedServer, $username) {
    $conn = connectToDatabase($selectedServer);
    $sql = "SELECT * FROM Users WHERE UserLogIn = ?";
    $stmt = sqlsrv_query($conn, $sql, [$username]);

    if ($stmt === false) die(print_r(sqlsrv_errors(), true));

    $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    sqlsrv_close($conn);
    return $user;
}

function getUserById($selected_server, $id) {
    $conn = connectToDatabase($selected_server);
    $sql = "SELECT * FROM Students s JOIN Nationalities n ON s.NationalityID = n.NationalityID WHERE StudentID = ?";
    $stmt = sqlsrv_query($conn, $sql, [$id]);

    if ($stmt === false) die(print_r(sqlsrv_errors(), true));

    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    sqlsrv_close($conn);
    return $row;
}

function getRowById($selected_server, $table, $column, $value) {
    $conn = connectToDatabase($selected_server);
    $sql = "SELECT * FROM $table WHERE $column = ?";
    $stmt = sqlsrv_query($conn, $sql, [$value]);

    if ($stmt === false) die(print_r(sqlsrv_errors(), true));

    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
    return $row;
}

function getFacultyById($selected_server, $facultyId) {
    return getRowById($selected_server, 'Faculties', 'FacultyID', $facultyId);
}

function getAllFaculty($selected_server) {
    $conn = connectToDatabase($selected_server);
    $sql = "SELECT * FROM Faculties";
    $stmt = sqlsrv_query($conn, $sql);

    if ($stmt === false) die(print_r(sqlsrv_errors(), true));

    $faculties = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $faculties[] = $row;
    }

    sqlsrv_close($conn);
    return $faculties;
}

function getAllStudents($selected_server) {
    $conn = connectToDatabase($selected_server);
    $sql = "SELECT * FROM Students";
    $stmt = sqlsrv_query($conn, $sql);

    if ($stmt === false) die(print_r(sqlsrv_errors(), true));

    $students = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $students[] = $row;
    }

    sqlsrv_close($conn);
    return $students;
}

function searchUsers($selected_server, $searchQuery) {
    $conn = connectToDatabase($selected_server);
    $sql = "SELECT * FROM Students WHERE StudentName LIKE ? OR AdmissionFormNo LIKE ?";
    $params = ["%$searchQuery%", "%$searchQuery%"];
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) die(print_r(sqlsrv_errors(), true));

    $results = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $results[] = $row;
    }

    sqlsrv_close($conn);
    return $results;
}

function getCertificte($selected_server, $id) {
    $conn = connectToDatabase($selected_server);
    $sql = "SELECT * FROM StudentInfo(?)";
    $stmt = sqlsrv_query($conn, $sql, [$id]);

    if ($stmt === false) die(print_r(sqlsrv_errors(), true));

    $certificate = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    sqlsrv_close($conn);
    return $certificate;
}

// ==========================
// New Function: Get Signatures by FacultyID
// ==========================

function getAllSignatures($selected_server, $facultyId) {
    $conn = connectToDatabase($selected_server);

    $sql = "SELECT * FROM Signatures WHERE FacultyID = ?";
    $stmt = sqlsrv_query($conn, $sql, [$facultyId]);

    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $signatures = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
    return $signatures;
}

// ==========================
// Helper Functions
// ==========================

function getsm($smid) {
    $labels = [
        1 => 'Semester One:', 2 => 'Semester Two:', 3 => 'Semester Three:',
        4 => 'Semester Four:', 5 => 'Semester Five:', 6 => 'Semester Six:',
        7 => 'Semester Seven:', 8 => 'Semester Eight:', 9 => 'Semester Nine:',
        10 => 'Semester Ten:'
    ];
    return $labels[$smid] ?? 'Semester Unknown';
}

// ==========================
// Faculty Page Routers
// ==========================

function getFacilityLink(string $lnk, string $lang = 'en', string $type = 'general'): string {
    // قاعدة روابط الملفات
    $files = [
        'general' => [
            'en' => [
                'FMAS' => 'GeneralNursEn.php',
                'FEDU' => 'GeneralEduEcoEn.php',
                'FERD' => 'GeneralEduEcoEn.php',
                'FCS'  => 'GeneralComEn.php',
                'FMED' => 'GeneralMedVetEn.php',
                'FVM'  => 'GeneralMedVetEn.php',
                'default' => 'GeneralMedVetEn.php',
            ],
            'ar' => [
                'FMAS' => 'GeneralComAr.php',
                'FEDU' => 'GeneralEduEcoAr.php',
                'FERD' => 'GeneralEduEcoAr.php',
                'FCS'  => 'GeneralComAr.php',
                'FMED' => 'GeneralMedVetAr.php',
                'FVM'  => 'GeneralMedVetAr.php',
                'default' => 'GeneralLmsAr.php',
            ],
        ],
        'transcript' => [
            'en' => [
                'FMAS' => 'TransscriptComEn.php',
                'FEDU' => 'TransEduEcoEn.php',
                'FERD' => 'TransEduEcoEn.php',
                'FCS'  => 'TransscriptComEn.php',
                'FMED' => 'TransMedVetEn.php',
                'FVM'  => 'TransMedVetEn.php',
                'default' => 'TransMedVetEn.php',
            ],
            'ar' => [
                'FMAS' => 'TransscriptComAr.php',
                'FEDU' => 'TransEduEcoAr.php',
                'FERD' => 'TransEduEcoAr.php',
                'FCS'  => 'TransscriptComAr.php',
                'FMED' => 'TransMedVetAr.php',
                'FVM'  => 'TransMedVetAr.php',
                'default' => 'TransEduEcoAr.php',
            ],
        ]
    ];

    $lnk = strtoupper($lnk); // توحيد الحروف
    $lang = strtolower($lang);
    $type = strtolower($type);

    return $files[$type][$lang][$lnk] ?? $files[$type][$lang]['default'];
}

?>