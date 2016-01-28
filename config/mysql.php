<?php
$bill_link = mysqli_connect("us-cdbr-iron-east-03.cleardb.net", "b6a59b96") or die ("cannot connect to server!");
mysqli_select_db($bill_link, "heroku_5c7020ebfa7ce45") or die ("cannot select database!!");
?>