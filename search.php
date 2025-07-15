<?php namespace hidt;
 include 'config.php';
?>

            <table class="table table-hover" id="example" width=100%>
                <thead>
                    <tr>
                        <th>م</th>
                        <th>الاسم</th>
                        <th>رقم الهوية </th>
                        <th>الشركة</th>
                        <th>رقم الجوال</th>
                        <th>اعدادات</th>
                    </tr>
                </thead>
                <tbody>
    <?php
    $i=1;
   
        // echo $sql;
        foreach (getstudent() as $row ):
        
            ?>

                    <tr>
                        <?php if($row['stdid']=='admin'){echo'<td style="display:none">1</td><td style="display:none">1</td><td style="display:none">1</td><td style="display:none">1</td><td style="display:none">1</td><td style="display:none">1</td>';}else{?>
                        <td scope="row"><?php echo $i++;?></td>
                        <td><a href="student/index.php?stdid=<?php echo $row['stdid'];?>"><?php echo $row['stdname'];?></a></td>
                        <td><?php echo $row['stdid'];?></td>
                        <td><?php echo trim(getcompany($row['cid']));?></td>
                        <td><?php echo $row['phone'];?></td>
                        <td>
                            <a href="entering/index.php?id=<?php echo $row['stdid'];?>&op=add" class="link-primary btn btn-info">اضافة</a>
                        |   <a href="student/editstd.php?id=<?php echo $row['stdid'];?>&op=add" class="link-primary btn btn-info">تعديل</a>
                        </td>
                        <?php }?>
                    </tr>


        <?php endforeach?>                    
                </tbody>
            </table>

    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
  </body>
</html>
