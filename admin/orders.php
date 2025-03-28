<?php 
  include("../connections.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin</title>

  <!-- Toastr.js -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
</head>
<body class="w-full h-screen flex">
  
  <aside class="flex flex-col gap-8 items-center justify-between w-[300px] h-full bg-[#ee4d2d]">

    <div class="flex items-center justify-center gap-2 mt-8">
      <img src="../assets/logo.jpg" class="w-[70px] h-[70px]">
      <h1 class="text-white text-3xl mt-[10px]">Chopee</h1>
    </div>

    <div class="flex flex-col gap-4 text-start w-[80%] items-start ml-12">
      <div class="flex gap-4 items-center text-start hover:cursor-pointer">
        <i class="fa-solid fa-list-check text-white"></i>
        <a href="index.php" class="text-white text-xl">Products</a>
      </div>
      <div class="flex gap-4 items-center text-start hover:cursor-pointer font-bold text-2xl">
        <i class="fa-solid fa-cart-shopping text-white"></i>
        <a href="orders.php" class="text-white">Orders</a>
      </div>
      <div class="flex gap-4 items-center text-start hover:cursor-pointer">
        <i class="fa-solid fa-users text-white"></i>
        <a href="users.php" class="text-white text-xl">Users</a>
      </div>
    </div>

    <div class="flex items-center justify-center gap-2 mb-8">
      <i class="fa-solid fa-right-from-bracket text-white font-xl"></i>
      <a href="../index.php" class="text-white text-xl">Logout</a>
    </div>

  </aside>

  <div class="bg-[#faf9f6] w-full h-full"></div>  
  
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- FontAwesome -->
  <script src="https://kit.fontawesome.com/d5b7a13861.js" crossorigin="anonymous"></script>

</body>
</html>
