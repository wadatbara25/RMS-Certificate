var svr = "http://uofb:8080/";

function printp() {
  // $("#add").hide();
  // $("#print").hide()
  print();
}

///$("#navdiv").load( "header.php");

document.addEventListener("DOMContentLoaded", () => {
  checkCookie()
  $("th").css("text-align", "right")
  $("button#submit").click(function () {

    if ($("#username").val() == "" || $("#password").val() == "")
      $("div#ack").html("الرجاء ادخال اسم المستخدم وكلمة المرور ");
    else
      $.post($("#myForm").attr("action"),
        $("#myForm :input").serializeArray(),
        function (data) {
          $("div#ack").html(data);
          if (data.trim() !== "Failed To Login") {
            setCookie("username", $("#username").val(), 365);

          };
        });

    $("#myForm").submit(function () {
      return false;
    });

  });

  function setCookie(cname, cvalue, exdays) {
    const d = new Date();
    d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
    let expires = "expires=" + d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";

    $(location).attr('href', svr + 'index.php');
  }


  $("#logout2").click(function () {

    setCookie("username", "", -365);
    $(location).attr('href',
      svr + 'login.php');

  })


  function checkCookie() {
    let username = getCookie("username");
    if (username != "") {
      $("#navId").show();
      $("#logout").show();
    } else {
      $("#logout").hide();
      $("#navId").hide();
    }
  }


  function getCookie(cname) {
    let name = cname + "=";
    let ca = document.cookie.split(';');
    for (let i = 0; i < ca.length; i++) {
      let c = ca[i];
      while (c.charAt(0) == ' ') {
        c = c.substring(1);
      }
      if (c.indexOf(name) == 0) {
        return c.substring(name.length, c.length);
      }
    }
    return "";
  }



  $("#divmsg").fadeOut(2000);


  new DataTable('#myTable', {
    language: {
      url: svr + 'include/js/ar.json',
    },
  });


});

function topt() {
  $("#topt").empty();

  $.ajax({
    type: 'get',
    url: 'top.php?tid=' + $('#cmp').val(),
    contentType: '*',
    dataType: '*',
    success: function (data) {
      $("#topt").empty();
      $("#topt").html(data)

    },
    complete: function () { }


  });

}



$("#tid").change(function () {
  tids = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 57];
  tid = +this.value

  if (tids.indexOf(tid) < 0) {

    $("#speeed").html(' السرعة/التقدير<input type="text" readonly name="speed" id="speed" value ="اتقن" class="form-control" placeholder="">')

  } else {
    $("#speeed").html(' السرعة/التقدير<input type="text" name="speed" id="speed" class="form-control" placeholder="">')


  }
})
