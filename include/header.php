<?php 
namespace hidt;
$url= explode('/',$_SERVER['REQUEST_URI'])[2];
(file_exists('config.php'))?require_once('config.php'):require_once('../config.php');
(isset($_COOKIE['username']))?$user=$_COOKIE['username']:$user="";

?>
<!doctype html>
<html lang="en" dir="rtl">
  <head>
    <title>نظام التدريب</title>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="icon" href="favicon.ico">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="http://<?php echo $svr.':'.$port?>/include/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="http://<?php echo $svr.':'.$port?>/include/css/site.css">
    <script src="http://<?php echo $svr.':'.$port?>/include/js/site.js"></script>
  </head>
  <body>
      <!-- Nav tabs -->
      <ul class="nav nav-tabs" id="navId">
        <li class="nav-item">
            <a href="http://<?php echo $svr.':'.$port?>/index.php" class="nav-link <?= ($url=='index.php')?'active':'';?>">الرئيسية</a>
        <?php 
        if($user=='admin'){?>
        </li>
        <li class="nav-item">
            <a href="http://<?php echo $svr.':'.$port?>/ss.php" class="nav-link  <?= ($url=='ss.php')?'active':'';?>">المتدربين</a>
        </li>
        <li class="nav-item">
            <a href="http://<?php echo $svr.':'.$port?>/student/addstd.php" class="nav-link <?= ($url=='student')?'active':'';?>">اضافة متدربين</a>
        </li>
          <?php }else{};?>
        <li class="nav-item"  >
          <div class="nav-link" >   </div>
      </li>
      <div class="dropdown" id="logout">
  <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
  <?php echo getname($user);?>  </button>
  <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
    <a class="dropdown-item" href="#" id="logout2">تسجيل خروج</a>

  </div>
</div>


      </ul>
      
      <!-- Tab panes -->
      <div class="container">
        <div class="row">
          <div class="col-lg-10 shadow p-3 mb-5 bg-white rounded offset-1">
  
      
