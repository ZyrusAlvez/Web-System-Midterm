<?php

$user_id = $_REQUEST["id"];

include("connections.php");

$get_record = mysqli_query($connections, "SELECT * FROM mytbl WHERE id='$user_id'");

while($row_edit = mysqli_fetch_assoc($get_record)){

	$db_name = $row_edit["name"];
	$db_address = $row_edit["address"];
	$db_email = $row_edit["email"];

}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit mode</title>
</head>
<body class="flex items-center justify-center w-full h-screen">
    <form method="POST" action="update_record.php" class="grid grid-cols-[auto,auto] gap-4">

        <h1 class="font-bold text-end hidden">ID :</h1>
        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>" class="rounded-lg border border-black outline-none py-1 pl-2">
        <h1 class="font-bold text-end">Name :</h1>
        <input type="text" name="new_name" value="<?php echo $db_name; ?>" class="rounded-lg border border-black outline-none py-1 pl-2">
        <h1 class="font-bold text-end">Address :</h1>
        <input type="text" name="new_address" value="<?php echo $db_address; ?>" class="rounded-lg border border-black outline-none py-1 pl-2">
        <h1 class="font-bold text-end">Email :</h1>
        <input type="text" name="new_email" value="<?php echo $db_email; ?>" class="rounded-lg border border-black outline-none py-1 pl-2">

        <input type="submit" value="Update" class="rounded-2xl text-white bg-black px-4 py-2 col-span-2 font-bold">
    </form>

    <script src="https://cdn.tailwindcss.com"></script>
</body>
</html>
