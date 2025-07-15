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
    $DateNow = date("d/m/Y");
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
    <title>Transcript Results</title>
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

<table class="T1" border="0" padding="0" border-spacing="0" align="center" width="90%">
      <tr align="left">
        <td><img width="100" height="100" src="data:image/jpeg;base64,<?php echo base64_encode($Certificate['Photo'])?>"/> </td>
        <th></th>
        <th></th>
    </tr>
    <tr align="left">
         <td><h6><?php echo $Certificate['AdmissionFormNo']; ?>:الرقم الجامعي</h6></td>
         <td clospan="2"></td>
        
     </tr>
     <tr align="center">
        <td colspan="3"><b> كلية <?php echo $Certificate['FacultyName'];?></b></td>
   
     </tr>
      <tr align="center">
        <td colspan="3"><b><?php echo $Certificate['DegreeNameAr'];?></b></td>
   
     </tr>
     <tr align="center">
       
       <td colspan="3"><b>شهـادة تفـاصيـل<hr class="new1"></b></td>
  
    </tr>
        
    
     
     <tr align="right">
        
        <td><b>الجنسية: <u><?php echo $Certificate['StudentNationality'];?></u> </b> </td>
        <td colspan="2"> <b>الاسم:<u> <?php echo $Certificate['StudentName'];?> </u> </b> </td>
        
    </tr>
   
    <tr align="right">
        
        <th><b></b>&nbsp;<u><?php echo $Class.':'.$message;?></u></th>
        <th colspan="2"><b>التخصص :</b>&nbsp;<u><?php echo $Certificate['DepartmentName'];?> </u></th>
    </tr>
   
     <tr align="right">
        
        <th><b></b>&nbsp;<u><?php echo $GradDate;?> :تاريخ  التخرج</u></th>
        <th colspan="2"><b></b>&nbsp;<u><?php echo $AddDate;?> :تاريخ الالتحاق</u></th>
    </tr>
    
     <tr align="right">
       
         <th> <b></b> &nbsp;<u><?php  echo $Certificate['C_Hours']; ?>: الساعات المعتمدة الكلية</u></th>
         <th colspan="2"></th>
    </tr>
   
    <tr>
        <th colspan="3">

        

        
       



<div align="center">
    <?php    $TotalHs=0;
            $TotalGs=0;
             foreach ($data as $semester => $entries): ?>
             
        <table class="T2"  dir="rtl">
        <tr>
                <td colspan="3" align="right" style="border:none;"> الفصل الدراسي <?php echo htmlspecialchars($semester); ?>   :</td>
               
                
            </tr>
            <tr bgcolor="f2f2f2">
                <th width="70%">المقرر الدراسي</th>
                <th width="5%" >السـاعات</th>
                <th width="15%">التقدير</th>
                
            </tr>
            <?php
            $totalHours = 0;
            $gradePointsValue = 0;
          
            $TotalHs += $totalHours;
            $TotalGs += $gradePointsValue;
            foreach ($entries as $entry):
                $totalHours += $entry['Hours'];
                $gradePointsValue += $entry['GradePoints'];
                
                
            ?>
                <tr>
                    <td align="right">&nbsp;&nbsp;<?php echo htmlspecialchars($entry['Subject']); ?></td>
                    <td align="center"><?php echo htmlspecialchars($entry['Hours']); ?></td>
                    <td align="center"><?php echo htmlspecialchars($entry['Grade']); ?></td>
                   
                </tr>
            <?php endforeach; 
            $TotalHs += $totalHours ;
            $TotalGs += $gradePointsValue;
            ?>
            <!-- Total Rows -->
            <tr class="total-row">
                <td>المعدل الفصلي=<?php echo htmlspecialchars(number_format($gradePointsValue/$totalHours,2)); ?></td>
                <td><?php echo htmlspecialchars($totalHours); ?></td>
                <td>المعدل التراكمي=<?php echo htmlspecialchars(number_format($TotalGs/$TotalHs,2)); ?></td>

                
              
            </tr>
        </table>
    <?php endforeach; ?>
            </div>

            </th>
     
     </tr>
     <tr align="right" >
     <th colspan="3"><b>:يتم تحويل التقديرات الى نقاط على النحو التالي </b> <br><center>A=4:00, B+=3.50, B=3.00, C+=2.50, C=2.00, F=0.00 Points.</center></th>
     </tr>
     
     <tr align="center">
     <td colspan="2"><img  width="100" height="100" src="img/<?php echo$Signatures['ImgDeann'];?>"></td>

        <td><img  width="100" height="100" src="img/<?php echo $Signatures['Imgregg'];?>"></td>
        
     </tr>
     <tr align="center">
     <th colspan="2"><b><i><?php echo $Signatures['FacultyDean_NameA'];?></i></b></th>
         <th nowrap> <b><i><?php echo $Signatures['FacultyRegistrar_NameA'];?></i></b></th>
         
        
     </tr>
     <tr align="center">
     <th colspan="2"><b>عميد الكلية</b></th>
         <th><b>مسجل الكلية</b></th>
         
         
     </tr>
    
       <tr>
         <th colspan="3"><br></th>
        
     </tr>
     <tr align="center">
      
         <th colspan="3"><br><br><br><br><b><i><?php echo $Signatures['AcademicAffairsDean_NameA'];?></i></b></th>
      
     </tr>
     <tr align="center">
     
         <th colspan="3"><b>أمين الشؤون العلمية</b></th>
        
     </tr>
  
 </table>
    </body>
</html>
<?php
// Close the connection
//sqlsrv_close($conn);
?>