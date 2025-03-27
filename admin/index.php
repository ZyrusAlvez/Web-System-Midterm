<?php 
session_start();
include("../connections.php");

// Check if the user is logged in and is an admin
if (!isset($_SESSION["email"]) || $_SESSION["account_type"] != "1") {
    header("Location: ../login.php"); // Redirect non-admin users to login page
    exit();
}
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
<body>
  
  <h1>Welcome to the Admin Dashboard</h1>

  <!-- jQuery -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  
  <!-- Toastr.js -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

  <!-- Toastr Notification -->
  <script>
      toastr.options = {
          "closeButton": true,
          "progressBar": true,
          "positionClass": "toast-top-right",
          "timeOut": "3000"
      };

      toastr.success("Logged in as Admin");
  </script>

</body>
</html>
