<?php
include 'mysqlconn.php';
?>

<!DOCTYPE html>
<html lang="en" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الخريجون</title>
   

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="include/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="include/css/site.css">
    
    <link rel="stylesheet" href="include/css/jquery.dataTables.css">
    <link rel="stylesheet" href="include/css/fixedColumns.dataTables.min.css">
   
</head>
<body class="navbar-fixed sidebar-nav fixed-nav">

 <header class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
  <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="#">جامعة البطانة</a>
  <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="عرض/إخفاء لوحة التنقل">
    <span class="navbar-toggler-icon"></span>
  </button>
  <div class="navbar-nav">
    <div class="nav-item text-nowrap">
      <a class="nav-link px-3" href="logout.php">تسجيل الخروج</a>
    </div>
  </div>
</header>

<div class="container-fluid">
  <div class="row">
    <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
      <div class="position-sticky pt-3">
        <ul class="nav flex-column">
          <li class="nav-item">
            <a class="nav-link active" aria-current="page" href="student.php">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-home" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
              طباعة الشهادات</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-file" aria-hidden="true"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline></svg>
              طباعة الافادات
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="graduatestd.php">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" class="main-grid-item-icon" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2">
            <rect height="8" rx="2" ry="2" width="20" x="2" y="2" />
            <rect height="8" rx="2" ry="2" width="20" x="2" y="14" />
            <line x1="6" x2="6.01" y1="6" y2="6" />
            <line x1="6" x2="6.01" y1="18" y2="18" />
          </svg>              قوائم الخريجين
            </a>
          </li>
       
        
        </ul>

        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
          <span>التقارير </span>
          <a class="link-secondary" href="#" aria-label="إضافة تقرير جديد">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-plus-circle" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
          </a>
        </h6>
        <ul class="nav flex-column mb-2">
          <li class="nav-item">
            <a class="nav-link" href="#">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-file-text" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
احصائيات الكلية            </a>
          </li>
         
          
         
        </ul>
      </div>
    </nav>

    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4"><div class="chartjs-size-monitor"><div class="chartjs-size-monitor-expand"><div class=""></div></div><div class="chartjs-size-monitor-shrink"><div class=""></div></div></div>
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1>الخريجون</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
        
         
        </div>
      </div>

       
        
      <div class="card">
      
  </div>
  <div class="dataTables_wrapper dt-bootstrap5 no-footer">
         <h2>قائمة الطلاب الخريجين بالجامعة</h2>

      <table class="table table-striped table-inverse table-responsive table-hover"  id="example">
      <thead>
          <tr>
              <th>الرقم الجامعي</th>
              <th>اسم الطالب</th>
              <th>تاريخ التخرج</th>
              <th>رقم مجلس الاساتذة</th>
          </tr>
          </thead>
          <tbody>
        <?php  // Check if any rows were returned
if ($result->num_rows > 0) {
    // Output data of each row
    while($row = $result->fetch_assoc()) {?>
              <tr>
        <td><?php echo $row["UNI_ID"]; ?></td>
        <td><?php echo $row["name"]; ?></td>
        <td><?php echo $row["DAT_GRAD"]; ?></td>
        <td><?php echo $row["SENATE"]; ?></td>
        
        
    </tr>
  <?php  }
} else {
    echo "0 results";
}?>
                  </tbody>
      </table>
  </div>
  
      
      </div>
    </main>
  </div>
</div>


<script src="include/js/jq.js"></script>

     <script src="include/js/site.js"></script>
    <script src="include/js/popper.min.js"></script>
    <script src="include/js/jquery.dataTables.min.js"></script>
    <script src="include/js/dataTables.bootstrap5.min.js"></script>
    <script src="include/js/bootstrap.min.js"></script>
   <script  >
   new DataTable('#example', {
    language: {
      url: 'include/js/ar.json',
    },
  });
   </script>
  
</body>
</html>
