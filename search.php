<?php

$search = $searchErr = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){

    if(empty($_POST["search"])){
        $searchErr = "Required!";
    }else{
        $search = $_POST["search"];
    }
}

if($search){
    echo "<script>window.location.href='results.php?search=$search';</script>";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search</title>
</head>
<body class="w-full h-screen flex items-center justify-cente">
    <form method="POST" class="flex w-full gap-2 items-center justify-center">

    <div class="flex flex-col">
        <input type="text" name="search" value="<?php echo $search; ?>" class="rounded-lg border border-black outline-none pl-1 py-1">
        <span class="text-red-600"><?php echo $searchErr ?></span>
    </div>

    <input type="submit" value="Search 🔎" class="px-4 py-2 text-white rounded-lg bg-black font-bold">

    </form>

    <script src="https://cdn.tailwindcss.com"></script>
</body>
</html>