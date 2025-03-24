<?php

include("connections.php");

if(empty($_GET["search"])){
    echo "Walang laman si Get ...";
}else{

    $check = $_GET["search"];

    $terms = explode(" ", $check);
    $q = "SELECT * FROM mytbl WHERE ";
    $i = 0;

    foreach($terms as $each){
        $i++;

        if($i == 1){
            $q .= "name LIKE '%$each%' ";
        }else{
            $q .= "OR name LIKE '%$each%' ";
        }
    }

    $query = mysqli_query($connections, $q);
    $c_q = mysqli_num_rows($query);

    if($c_q > 0 && $check != ""){

        while($row = mysqli_fetch_assoc($query)){
            echo $name = $row["name"] . "<br>";
        }

    }else{
        echo "No result.";
    }

}       

?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Results</title>
</head>
<body>
    
</body>
</html>