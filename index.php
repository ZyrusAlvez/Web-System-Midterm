<?php 
session_start();
include("connections.php");

$errorMessage = ""; // Store the error message

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    $stmt = $connections->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $db_password = $row["password"];
        $db_account_type = $row["account_type"];

        if ($password === $db_password) {
            $_SESSION["email"] = $email;
            $_SESSION["account_type"] = $db_account_type;
            
            if ($db_account_type == "1") {
                header("Location: admin/index.php");
                exit();
            } else {
                header("Location: user/index.php");
                exit();
            }
        } else {
            $errorMessage = "Incorrect password!";
        }
    } else {
        $errorMessage = "Email is not registered!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- FontAwesome -->
    <script src="https://kit.fontawesome.com/d5b7a13861.js" crossorigin="anonymous"></script>

    <!-- Toastr.js for Notifications -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
</head>
<body class="w-full h-screen flex items-center justify-evenly bg-[#ee4d2d]">
    <div class="flex flex-col gap-8 items-center justify-center">
        <img src="assets/logo_w_name.webp" class="w-80 h-80">
        <div class="flex flex-col">
            <h1 class="text-white text-2xl text-center">The leading online shopping platform</h1>
            <h1 class="text-white text-2xl text-center">in the Whole Wide Universe</h1>
        </div>
    </div>
    
    <form class="bg-white w-[400px] h-auto flex flex-col gap-y-6 items-center py-4" action="" method="POST">
        <h1 class="text-start w-[340px] text-xl">Log In</h1>

        <input name="email" class="outline-none border-gray-300 px-3 py-2 border w-[338px]" placeholder="Email" required>

        <div class="relative w-[338px]">
            <input id="password" name="password" class="outline-none border-gray-300 px-3 py-2 border w-full" placeholder="Password" type="password" required>
            <i id="eyeOpen" class="fa-solid fa-eye text-[#ee4d2d] absolute right-3 top-1/2 transform -translate-y-1/2 cursor-pointer hidden"></i>
            <i id="eyeClosed" class="fa-solid fa-eye-slash text-[#ee4d2d] absolute right-3 top-1/2 transform -translate-y-1/2 cursor-pointer"></i>
        </div>

        <input type="submit" class="w-[340px] bg-[#ee4d2d] py-[10px] text-white" value="LOG IN">
        <h1 class="text-gray-400">New to Chopee? <a class="text-[#ee4d2d] font-bold" href="register.php">Sign Up</a></h1>
    </form>

    <!-- jQuery (required for Toastr.js) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    
    <!-- Toastr.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <!-- Toastr Configuration -->
    <script>
        toastr.options = {
            "closeButton": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "timeOut": "3000"
        };

        <?php if (!empty($errorMessage)) : ?>
            toastr.error("<?= $errorMessage ?>");
        <?php endif; ?>
    </script>

    <!-- Password Toggle -->
    <script>
        const passwordInput = document.getElementById('password');
        const eyeOpen = document.getElementById('eyeOpen');
        const eyeClosed = document.getElementById('eyeClosed');

        eyeClosed.addEventListener('click', () => {
            passwordInput.type = 'text';
            eyeClosed.classList.add('hidden');
            eyeOpen.classList.remove('hidden');
        });

        eyeOpen.addEventListener('click', () => {
            passwordInput.type = 'password';
            eyeOpen.classList.add('hidden');
            eyeClosed.classList.remove('hidden');
        });
    </script>
</body>
</html>