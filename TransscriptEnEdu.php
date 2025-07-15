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
      $subject = $row['SubjectNameEng'];
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
    $GradDate = $Certificate['GraduationDate']->format('d/m/Y');
    $AddDate = $Certificate['AdmissionDate']->format('d/m/Y');
    $DateNow = date("d/m/Y");
    // Check if user data exists
   
    
} else {
    die("Invalid user ID");
}

// Devition
function divition($dev){
    switch($dev){
        case $dev>=3.50:
            return 'One';
            break; 
        case $dev>=3.00:
                return 'Two- Devition One';
                break;
        case $dev>=2.50:
                    return 'Two- Devition Two';
                    break; 
        case $dev<2.50:
                        return 'Three';
                        break;
       
      
            }
        }
        // General
function divitionG($devG){
    switch($devG){
        case $devG>=3.50:
            return 'One';
            break; 
         case $devG>=2.50:
                    return 'Two';
                    break; 
        case $devG<2.50:
                        return 'Three';
                        break;
       
      
            }
        }


//General

$General='Honours';
$GG=str_contains($Certificate['DegreeNameEn'],$General);

      if($GG==0){
        $message=divitionG($Certificate['CGPA']);
        $Class='Degree';
      } else
      {
        $message=divition($Certificate['CGPA']);
        $Class='Class';
      }
?>
<!DOCTYPE html>
<html>
<head>
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
         <td><h6>Student No: <?php echo $Certificate['AdmissionFormNo']; ?></h6></td>
         <td clospan="2"></td>
        
     </tr>
     <tr align="center">
        <td colspan="3"><b>Faculty of <?php echo $Certificate['FacultyNameEng'];?></b></td>
   
     </tr>
      <tr align="center">
        <td colspan="3"><b><?php echo $Certificate['DegreeNameEn'];?></b></td>
   
     </tr>
     <tr align="center">
       
       <td colspan="3"><b>ACADEMIC TRANSCRIPT<hr class="new1"></b></td>
  
    </tr>
        
    
     
     <tr align="left">
        <td colspan="2"> <b>Name:<u><?php echo $Certificate['StudentNameEng'];?></u> </b> </td>
        <td><b> Nationality:<u><?php echo $Certificate['StudentNationalityEng'];?></u> </b> </td>
        
        
    </tr>
   
    <tr align="left">
        <th colspan="2"><b>Specialization:</b>&nbsp;<u><?php echo $Certificate['DepartmentNameEng'];?></u></th>
        <th>&nbsp;<u><?php echo $Class.':'.$message;?></u></th>
    </tr>
   
     <tr align="left">
        <th colspan="2"><b>Addmission Date:</b>&nbsp;<u><?php echo $AddDate;?></u></th>
        <th><b>Date of Award :</b>&nbsp;<u><?php echo $GradDate;?></u></th>
    </tr>
    
     <tr align="left">
        <th colspan="2"></th>
         <th> <b>Total Credit Hours:</b> &nbsp;<u><?php  echo $Certificate['C_Hours']; ?></u></th>
      
    </tr>
   
    <tr>
        <th colspan="3">

        

        
       



<div align="center">
    <?php    $TotalHs=0;
            $TotalGs=0;
             foreach ($data as $semester => $entries): ?>
             
        <table class="T2">
        <tr>
                <td colspan="3" align="left" style="border:none;">Semester <?php echo htmlspecialchars($semester); ?>:</td>
               
                
            </tr>
            <tr bgcolor="f2f2f2">
                <th width="70%">Subject</th>
                <th width="5%" >Hours</th>
                <th width="5%">Grade</th>
                
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
                    <td align="left">&nbsp;&nbsp;<?php echo htmlspecialchars($entry['Subject']); ?></td>
                    <td align="center"><?php echo htmlspecialchars($entry['Hours']); ?></td>
                    <td align="center"><?php echo htmlspecialchars($entry['Grade']); ?></td>
                   
                </tr>
            <?php endforeach; 
            $TotalHs += $totalHours ;
            $TotalGs += $gradePointsValue;
            ?>
            <!-- Total Rows -->
            <tr class="total-row">
                <td>GPA=<?php echo htmlspecialchars(number_format($gradePointsValue/$totalHours,2)); ?></td>
                <td><?php echo htmlspecialchars($totalHours); ?></td>
                <td>CGPA=<?php echo htmlspecialchars(number_format($TotalGs/$TotalHs,2)); ?></td>

                
              
            </tr>
        </table>
    <?php endforeach; ?>
            </div>

            </th>
     
     </tr>
     <tr align="left" >
     <th colspan="3"><b>Grades are Converted into Points as follows:</b> <br><center>A=4:00, B+=3.50, B=3.00, C+=2.50, C=2.00, F=0.00 Points.</center></th>
     </tr>
     
     <tr align="center">
        <td><img  width="100" height="100" src="img/<?php echo $Signatures['Imgregg'];?>"></td>
        
         <td colspan="2"><img  width="100" height="100" src="img/<?php echo$Signatures['ImgDeann'];?>"></td>
     </tr>
     <tr align="center">
         <th nowrap> <b><i><?php echo $Signatures['FacultyRegistrar_NameE'];?></i></b></th>
         
         <th colspan="2"><b><i><?php echo $Signatures['FacultyDean_NameE'];?></i></b></th>
     </tr>
     <tr align="center">
         <th><b>Registrar</b></th>
         
         <th colspan="2"><b>Dean of Faculty</b></th>
     </tr>
    
       <tr>
         <th colspan="3"><br></th>
        
     </tr>
     <tr align="center">
      
         <th colspan="3"><br><br><br><br><b><i><?php echo $Signatures['AcademicAffairsDean_NameE'];?></i></b></th>
      
     </tr>
     <tr align="center">
     
         <th colspan="3"><h5>Secretary of Academic Affairs</h5></th>
        
     </tr>
  
 </table>
    </body>
</html>
<?php
// Close the connection
//sqlsrv_close($conn);
?>