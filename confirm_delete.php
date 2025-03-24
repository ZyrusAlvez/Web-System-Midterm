<?php


$user_id = $_REQUEST["id"];

include("connections.php");

$query_delete = mysqli_query($connections, "SELECT * FROM mytbl WHERE id = '$user_id'");

    while($row_delete = mysqli_fetch_assoc($query_delete)){
        $user_id = $row_delete["id"];

        $db_name = $row_delete["name"];
        $db_address = $row_delete["address"];
        $db_email = $row_delete["email"];
    
    }

    echo "<h1 class='text-4xl font-bold mb-8'>Are you sure you want to delete $db_name ?</h1>"

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete</title>
</head>
<body class='w-full h-screen flex flex-col justify-center items-center'>
    <form method="POST" action="delete_now.php" class="flex gap-4"> 
        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
        <input type="submit" value="Yes" class="text-white bg-red-500 w-[100px] text-center py-2 rounded-full font-bold">
        <a href="index.php" class="text-white bg-black w-[100px] text-center py-2 rounded-full font-bold">No</a>
    </form>

    <script src="https://cdn.tailwindcss.com"></script>
</body>
</html>