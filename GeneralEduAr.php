<?php
// Database connection parameters
// $serverName = "."; // Replace with your server name
// $connectionOptions = [
//     "Database" => "RRS_Diploma", // Replace with your database name
//     "Uid" => "sa", // Replace with your database username
//     "PWD" => "123" // Replace with your database password
// ];
session_start();
include 'db_connection.php';

if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}
$selectedServer = $_SESSION["server"];
$id = isset($_GET["id"]) ? $_GET["id"] : null;

  //Transscript call

  $conn = connectToDatabase($selectedServer);
  if ($conn === false) {
      die(print_r(sqlsrv_errors(), true));
  }
  
  // Grade points mapping
  
  
  
      $sql = "select  * from TranscriptF('$id')    
      order by SemesterID,SubjectNameEng
   ";
      
   $TRRR = sqlsrv_query($conn, $sql);
   
   if ($TRRR === false) {
       die(print_r(sqlsrv_errors(), true));
   }
   
   // Initialize arrays to hold data by semester and grand total calculation
   $data = [];
   
   while ($row = sqlsrv_fetch_array($TRRR, SQLSRV_FETCH_ASSOC)) {
          $semester = $row['SemesterID'];
      $subject = $row['SubjectName'];
      $hours = $row['SubjectHours'];
      $grade = $row['SubjectGradeEng'];
      $gradePointsValue = $row['GradePoint'];
   
  
      if (!isset($data[$semester])) {
          $data[$semester] = [];
      }
  
      $data[$semester][] = [
          'Subject' => $subject,
          'Hours' => $hours,
          'Grade' => $grade,
          'GradePoints' => $gradePointsValue,
         
      ];
      //sqlsrv_close($conn);
  
  //return $Transe;
  }



  if ($id) {
    $Certificate=getCertificte($selectedServer, $id);
    $row = getUserById($selectedServer, $id);
    $Signatures = getAllSignatures($selectedServer, $id);
    // Check if faculty data exists
    if ($Certificate === null||$row === null||$Signatures === null) {
        echo "No Result Found";
    }
    $GradDate = $Certificate['GraduationDate']->format('Y/m/d');
    $AddDate = $Certificate['AdmissionDate']->format('Y/m/d');
    $DateNow = date("Y/m/d");
    // Check if user data exists
   
    
} else {
    die("Invalid user ID");
}

// Devition
function divition($dev){
    switch($dev){
        case $dev>=3.50:
            return 'الأولى';
            break; 
        case $dev>=3.00:
                    return 'الثانية - القسم الأول';
                    break; 
        case $dev>=2.50:
                        return 'الثانية - القسم الثاني';
                        break;
        case $dev<2.50:
                        return 'الثالثة';
                        break;
       
      
            }
        }

        
        // General
function divitionG($devG){
    switch($devG){
        case $devG>=3.50:
            return 'الأولى';
            break; 
         case $devG>=2.50:
                    return 'الثانية';
                    break; 
        case $devG<2.50:
                        return 'الثالثة';
                        break;
       
      
            }
        }


//General

$General='شرف';
$GG=str_contains($Certificate['DegreeNameAr'],$General);

      if($GG== 0){
        $message=divitionG($Certificate['CGPA']);
        $Class='الدرجة';
      } else
      {
        $message=divition($Certificate['CGPA']);
        $Class='المرتبة';
      }
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8"/>
    <title>شهادة عامة عربي</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Almarai|Sofia|Trirong">
                
    <style>
       table.T1{
           border: 0px solid black;
            padding: 0px;
            background-color: #ffffff;
            text-align: center;       
        }
      table.T2 {
          
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0px;
            text-align:center;
            font-size:12px;
          
        }
        table.T2, th.T2,td.T2 {
            border: 1px solid black;
            padding: 0px;
            text-align: center;
        }
        table.T2,th.T2{
           
        }
        
       
        .total-row {
            font-weight: bold;
            background-color: #e0e0e0;
        }

        hr.new1 {
    border-top: 1px dashed red;
  }
  
    </style>
</head>
<body>

<table class="T2" border="0" padding="0" border-spacing="0" align="center" width="90%">
      <tr align="left">
        <td><img width="120" height="120" src="data:image/jpeg;base64,<?php echo base64_encode($Certificate['Photo'])?>"/> </td>
        <th></th>
        <th></th>
    </tr>
    <tr align="left">
         <td><h5><?php echo $Certificate['AdmissionFormNo']; ?>:الرقم الجامعي</h5></td>
         <td clospan="2"></td>
        
     </tr>
     
     
     <tr align="center">
       
       <td colspan="3"><b style="font-family: 'Almarai', sans-serif; font-size:40px;" >شهـادة </b></td>
       
    </tr>
    <tr align="center">
       
       <td colspan="3"><br></td>
       
    </tr>
   
    <tr align="right">
       
       <td colspan="3"><b style="font-family: 'Almarai', sans-serif; font-size:24px;"">: نشهد يأن مجلس الأساتذة قد منح</b></td>
  
    </tr>
     
     <tr align="right">
        
        <td><h2>الجنسية: <u><?php echo $Certificate['StudentNationality'];?></u> </h2> </td>
        <td colspan="2"> <h2><u> <?php echo $Certificate['StudentName'];?> </u> </h2> </td>
        
    </tr>
    <tr align="center">
        <td colspan="3"><h2><?php echo $Certificate['DegreeNameAr'];?></h2></td>
   
     </tr>
     <tr align="right">
        <td colspan="3"><h2>&nbsp;<u><?php echo $Class.':'.$message;?></u></h2></td>
   
     </tr>
     <tr align="right">
        <td colspan="3"><h2>الكلية:<?php echo $Certificate['FacultyName'];?></h2></td>
   
     </tr>
    <tr align="right">
        
     
        <th colspan="3"><h2>التخصص :&nbsp;<u><?php echo $Certificate['DepartmentName'];?> </u></h2></th>
    </tr>
   
     <tr align="right">
        
        <th colspan="3"><h2>&nbsp;<u><?php echo $GradDate;?> :تاريخ  منح الدرجة</u></h2></th>
       
    </tr>
    <tr align="right">
        
        <th colspan="3"><h2>&nbsp;<u><?php echo $DateNow;?> :تاريخ  اصدار الشهادة</u></h2></th>
       
    </tr>
    
     <tr align="center">
     <td colspan="2"><img  width="100" height="100" src="img/<?php echo$Signatures['ImgDeann'];?>"></td>

        <td><img  width="100" height="100" src="img/<?php echo $Signatures['Imgregg'];?>"></td>
        
     </tr>
     <tr align="center">
     <th colspan="2"><h2><i><?php echo $Signatures['FacultyDean_NameA'];?></i></h2></th>
         <th nowrap> <h2><i><?php echo $Signatures['FacultyRegistrar_NameA'];?></i></h2></th>
         
        
     </tr>
     <tr align="center">
     <th colspan="2"><h2>عميد الكلية</h2></th>
         <th><h2>مسجل الكلية</h2></th>
         
         
     </tr>
    
      
     <tr align="center">
      
         <th colspan="3"><h2><i><?php echo $Signatures['AcademicAffairsDean_NameA'];?></i></h2></th>
      
     </tr>
     <tr align="center">
     
         <th colspan="3"><h2>أمين الشؤون العلمية</h2></th>
        
     </tr>
  
 </table>
    </body>
</html>
<?php
// Close the connection
//sqlsrv_close($conn);
?>