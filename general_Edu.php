<?php
require_once('tcpdf/tcpdf.php'); // Path to your TCPDF library

// Include database connection and functions
include 'db_connection.php';

session_start();

if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}
$selectedServer = $_SESSION["server"];


// Retrieve user data based on ID passed from index.php
$id = isset($_GET["id"]) ? $_GET["id"] : null;
$row = null;
$Signature=null;


if ($id) {
    $Certificate=getCertificte($selectedServer, $id);
    $row = getUserById($selectedServer, $id);
    $Signatures = getAllSignatures($selectedServer, $id);
    // Check if faculty data exists
    if ($Certificate === null||$row === null||$Signatures === null) {
        echo "No Result Found";
    }
    $Date = $Certificate['GraduationDate']->format('d/m/Y');
    $DateNow = date("d/m/Y");
    // Check if user data exists
   
    
} else {
    die("Invalid user ID");
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
// Devition
function divition($dev){
    switch($dev){
        case $dev>=3.50:
            return 'One';
            break; 
        case $dev>=3.00:
                return 'Two - Division One';
                break;
        case $dev>=2.50:
                    return 'Two - Division Two';
                    break; 
        case $dev<2.50:
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

// Extend TCPDF
class MYPDF extends TCPDF {
    public function Header() {
        // Header content
    }

    public function Footer() {
             
// Position at 15 mm from bottom
$this->SetY(-15);
// Set font
$this->SetFont('helvetica', 'I', 8);
// Page number
$this->Cell(0, 10, 'University of Al-Butana - Website: http://www.albutana.edu.sd  - Email: daa@albutana.edu.sd   Tel: 00249157904611  P.O.Box: Rufaa 200', 1, false, 'C', 0, '', 0, false, 'T', 'M');                 
               
    }
}

// Create new PDF instance
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
//$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('BashirOsman');
$pdf->SetTitle('شهادة عامة');
$pdf->SetSubject('English');
$pdf->SetKeywords('TCPDF, PDF, user, information');

//$pdf->setPrintHeader(false);
$pdf->setPrintFooter(true);
// Set font to Times New Roman, size 12 for the first row
$pdf->SetFont('times', '', 16);
// Add a page
$pdf->SetMargins(10, 5, 10, true);

// Example of Image from data stream ('PHP rules')
$imgdata = base64_decode('iVBORw0KGgoAAAANSUhEUgAAABwAAAASCAMAAAB/2U7WAAAABlBMVEUAAAD///+l2Z/dAAAASUlEQVR4XqWQUQoAIAxC2/0vXZDrEX4IJTRkb7lobNUStXsB0jIXIAMSsQnWlsV+wULF4Avk9fLq2r8a5HSE35Q3eO2XP1A1wQkZSgETvDtKdQAAAABJRU5ErkJggg==');

// The '@' character is used to indicate that follows an image data stream and not an image file name
$pdf->Image('@'.$imgdata);

$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
$pdf->AddPage();
//footer
//$pdf->Footer();
// Set some content to display

$content = '
<table border="0">
      <tr align="left">
        <td><img width="100" height="110" src="data:image/jpeg;base64,'.base64_encode($Certificate['Photo']).'"/> </td>
        <th></th>
        <th></th>
    </tr>
    <tr align="left">
         <td><h6>Student No: ' . $Certificate['AdmissionFormNo'] . '</h6></td>
        <td></td>
        <td></td>
     </tr>
     <tr>
        <th></th>
        <th></th>
        <th></th>
    </tr>
    
      <tr align="center">
         <td></td>
        <td><h2>CERTIFICATE</h2></td>
        <td></td>
     </tr>
     <tr>
        <th></th>
        <th></th>
        <th></th>
    </tr>
      <tr align="center">
         <td colspan="3">This is to Certify that the University Senate has Awarded:</td>
     </tr>
    
     <tr align="center">
         
        <th colspan="3"> <h5><u>'.$Certificate['StudentNameEng'].'</u>  -  <b> Nationality:</b><u>' . $Certificate['StudentNationalityEng'] . '</u>  </h5></th>
        
       
    </tr>
     <tr>
        <th></th>
        <th></th>
        <th></th>
    </tr>
      <tr>
   
        <th colspan="3">
     
        <table width="100%" border="0">

 <tr>
         <th align="left"  width="25%"><b>&nbsp;&nbsp;&nbsp;&nbsp;The Degree of</b></th>
         <th  width="1%">:</th>
        <th width="75%"><u>' . $Certificate['DegreeNameEn']. '</u></th>
    </tr>
    
    <tr>
  
         <th align="left"><b>&nbsp;&nbsp;&nbsp;&nbsp;'.$Class.' </b></th>
          <th>:</th>
        <th>&nbsp;<u>'.$message.'</u></th>
    </tr>
     <tr>
        <th align="left"><b>&nbsp;&nbsp;&nbsp;&nbsp;Faculty of </b></th>
         <th>:</th>
        <th>&nbsp;<u>' . $Certificate['FacultyNameEng'] . '</u></th>
      
    </tr>
     <tr>
        <th align="left"><b>&nbsp;&nbsp;&nbsp;&nbsp;Specialization </b></th>
         <th>:</th>
        <th>&nbsp;<u>' . $Certificate['DepartmentNameEng'] . '</u></th>
      
    </tr>
     <tr>
        <th align="left"><b>&nbsp;&nbsp;&nbsp;&nbsp;Date of Award </b></th>
         <th>:</th>
        <th>&nbsp;&nbsp;<u>' .$Date. '</u></th>
       
    </tr>
 
     <tr>
        <th align="left"><b>&nbsp;&nbsp;&nbsp;&nbsp;Date of Issue </b></th>
         <th>:</th>
         <th>&nbsp;<u>' .$DateNow. '</u></th>
        
    </tr>

        </table>
        
        
        
        
        
        </th>
    </tr>
  
    <tr>
        <th></th>
        <th></th>
        <th></th>
    </tr>
    <tr>
        <th></th>
        <th></th>
        <th></th>
    </tr>
    
    
    <tr align="center">
       <td><img  width="100" height="100" src="img/'.$Signatures['Imgregg'].'"></td>
        <th></th>
        <td><img  width="100" height="100" src="img/'.$Signatures['ImgDeann'].'"></td>
    </tr>
    <tr align="center">
        <th nowrap="nowrap"><h5><i>'.$Signatures['FacultyRegistrar_NameE'].'</i></h5></th>
        <th></th>
        <th nowrap="nowrap"><h5><i>'.$Signatures['FacultyDean_NameE'].'</i></h5></th>
    </tr>
    <tr align="center">
        <th><h5>Registrar</h5></th>
        <th></th>
        <th><h5>Dean of Faculty</h5></th>
    </tr>
     <tr>
        <th></th>
        <th></th>
        <th></th>
    </tr>
     <tr>
        <th></th>
        <th></th>
        <th></th>
    </tr>
      <tr>
        <th></th>
        <th></th>
        <th></th>
    </tr>
    <tr align="center">
       
        <th colspan="3"><h5><i>'.$Signatures['AcademicAffairsDean_NameE'].'</i></h5></th>
       
    </tr>
    <tr align="center">
        
        <th colspan="3"><h5>Secretary of Academic Affairs</h5></th>
       
    </tr>

   
</table>';


// Output the HTML content
$pdf->writeHTML($content, true, false, true, false, '');

// Close and output PDF document
$pdf->Output('information.pdf', 'I'); // I: inline view, D: download file
?>

