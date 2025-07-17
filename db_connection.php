<?php
// Database configurations for different servers
$server1 = array(
    'server' => '.\computer',
    'database' => 'RRS_MANAGEMENT',
    'username' => 'sa',
    'password' => 'P@ssw0rd'
);

$server2 = array(
    'server' => '.\eco',
    'database' => 'RRS_MANAGEMENT',
    'username' => 'sa',
    'password' => 'P@ssw0rd'
);
$server3 = array(
    'server' => 'educations.database.windows.net',
    'database' => 'RRS_MANAGEMENT',
    'username' => 'edu',
    'password' => 'P@ssw0rd'
);
$server4 = array(
    'server' => '.\law',
    'database' => 'RRS_MANAGEMENT',
    'username' => 'sa',
    'password' => 'P@ssw0rd'
);
$server5 = array(
    'server' => '.\lms',
    'database' => 'RRS_MANAGEMENT',
    'username' => 'sa',
    'password' => 'P@ssw0rd'
);
$server6 = array(
    'server' => '.\nurs',
    'database' => 'RRS_MANAGEMENT',
    'username' => 'sa',
    'password' => 'P@ssw0rd'
);
$server7 = array(
    'server' => '.\vet',
    'database' => 'RRS_MANAGEMENT',
    'username' => 'sa',
    'password' => 'P@ssw0rd'
);
$server8 = array(
    'server' => 'med.database.windows.net',
    'database' => 'RRS_MANAGEMENT',
    'username' => 'med',
    'password' => 'P@ssw0rd'
);
$server9 = array(
    'server' => '.\eco',
    'database' => 'RRS_Diploma',
    'username' => 'sa',
    'password' => 'P@ssw0rd'
);
$server10 = array(
    'server' => 'educations.database.windows.net',
    'database' => 'RRS_Diploma',
    'username' => 'edu',
    'password' => 'P@ssw0rd'
);

// Function to establish SQL Server connection
function connectToDatabase($selected_server) {
    global $server1, $server2,$server3, $server4,$server5,$server6,$server7, $server8,$server9, $server10;

    // Select appropriate server credentials
    switch ($selected_server) {
        case '1':
            $config = $server1;
            break;
        case '2':
            $config = $server2;
            break;
            case '3':
                $config = $server3;
                break;
                case '4':
                    $config = $server4;
                    break;
                    case '5':
                        $config = $server5;
                        break;
                        case '6':
                            $config = $server6;
                            break;
                            case '7':
                                $config = $server7;
                                break;
                                case '8':
                                    $config = $server8;
                                    break;
                                    case '9':
                                        $config = $server9;
                                        break;
                                        case '10':
                                            $config = $server10;
                                            break;
        // Add more cases for additional servers if needed
        default:
            // Default to server 1 if none selected
            $config = $server1;
            break;
    }

    // SQL Server connection options
    $connectionInfo = array(
        "Database" => $config['database'],
        "UID" => $config['username'],
        "PWD" => $config['password'],
        "CharacterSet" => "UTF-8"
    );

    // Establish SQL Server connection
    $conn = sqlsrv_connect($config['server'], $connectionInfo);

   if ($conn === false) {
    //die(print_r(sqlsrv_errors(), true));
    echo "راجع الاتصال بالسيرفر";
   }

    return $conn;
}



// Function to retrieve user data by ID from a specified server
function getUserById($selected_server, $id) {
    $conn = connectToDatabase($selected_server);
    $sql = "SELECT * FROM Students s,Nationalities N  WHERE StudentID = ? and   s.NationalityID=N.NationalityID ";
    $params = array($id);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    sqlsrv_close($conn);

    return $row;


}

// Function to get all data from a table
// Function to get a single row from a table based on an ID
function getRowById($selected_server, $table, $id) {
    $conn = connectToDatabase($selected_server);
    $sql = "SELECT * FROM $table";
    $params = array($id);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    return $row;
}

// Specific function for the faculty table
function getFacultyById($selected_server, $id) {
    return getRowById($selected_server, 'Faculties', $id);
}
function getAllFaculty($selected_server) {
    $conn = connectToDatabase($selected_server);
    $sql = "select* from Faculties";
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $Faculties = array();
    while ($fac = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $Faculties[] = $fac;
    }
}
//Signatures
function getAllSignatures($selected_server,$id) {
    return getRowById($selected_server, 'Signatures',$id);
}

// Function to retrieve all users from a specified server
function getAllStudents($selected_server) {
    $conn = connectToDatabase($selected_server);
    $sql = "select* from Students";
    $stmt = sqlsrv_query($conn, $sql);

    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $users = array();
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $users[] = $row;
    }

    sqlsrv_close($conn);
    return $users;
   
}



// Function to search for users by username or email on a specified server
function searchUsers($selected_server, $searchQuery) {
    $conn = connectToDatabase($selected_server);
    $sql = "SELECT * FROM Students WHERE StudentName LIKE ? OR AdmissionFormNo LIKE ?";
    $params = array("%$searchQuery%", "%$searchQuery%");
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $users = array();
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $users[] = $row;
    }

    sqlsrv_close($conn);
    return $users;
}

// certificte
$selectedServer = isset($_SESSION["server"]) ? $_SESSION["server"] : null;

function getCertificte($selected_server,$id) {
    $conn = connectToDatabase($selected_server);
    $sql = "SELECT *  FROM StudentInfo(?)";
   $params = array($id);
    $certifi = sqlsrv_query($conn, $sql,$params);
    if ($certifi === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $Certificate = sqlsrv_fetch_array($certifi, SQLSRV_FETCH_ASSOC);
                sqlsrv_close($conn);

        return $Certificate;
    
}
//semster
function getsm($smid){
    switch($smid){
        case 1:
            return 'Semster One:';
            break; 
        case 2:
                return 'Semster Two:';
                break;
        case 3:
                    return 'Semster Three:';
                    break;

        case 4:
                        return 'Semster Four:';
                        break;
                        
        case 5:
            return 'Semster Five:';
            break;
        
            case 6:
                return 'Semster Six:';
                break;
                
        case 7:
            return 'Semster Seven:';
            break;
            
        case 8:
            return 'Semster Eight:';
            break;
            
        case 9:
            return 'Semster Nine:';
            break;
            
        case 10:
            return 'Semster Ten:';
            break;
    }

}

// faculty
function faclitylink($lnk){
    switch($lnk){
        case 'FMAS':
            return 'general_Nurs.php';
            break; 
        case 'FEDU':
                return 'general_Edu.php';
                break;
             case 'FERD':
                    return 'general_Edu.php';
                    break;
        case 'FMED':
                    return 'generate_pdf.php';
                    break; 
                    case 'FVM':
                        return 'generate_pdf.php';
                        break; 
                 
             
            }
        }
        // faculty_Ar
function faclitylinkAr($lnk){
    switch($lnk){
        case 'FMAS':
            return 'general_NursAr.php';
            break; 
        case 'FEDU':
                return 'general_EduAr.php';
                break;
             case 'FERD':
                    return 'general_EduAr.php';
                    break;
        case 'FMED':
                    return 'generateAr.php';
                    break; 
                    case 'FVM':
                        return 'generateAr.php';
                        break; 
                 
             
            }
        }

        // faculty Trans
function faclitylinkT($lnk){
    switch($lnk){
        case 'FMAS':
            return 'TransscriptEnNurs.php';
            break; 
        case 'FEDU':
                return 'TransscriptEnEdu.php';
                break;
             case 'FERD':
                    return 'TransscriptEnEdu.php';
                    break;
        case 'FMED':
                    return 'TransscriptEn.php';
                    break; 
                    case 'FVM':
                        return 'TransscriptEn.php';
                        break; 
                 
             
            }
        }
    
        // faculty TransAr
function faclitylinkTAr($lnk){
    switch($lnk){
        case 'FMAS':
            return 'TransscriptArNurs.php';
            break; 
        case 'FEDU':
                return 'TransscriptArEdu.php';
                break;
             case 'FERD':
                    return 'TransscriptArEdu.php';
                    break;
        case 'FMED':
                    return 'TransscriptAr.php';
                    break; 
                    case 'FVM':
                        return 'TransscriptAr.php';
                        break; 
                 
             
            }
        }
?>