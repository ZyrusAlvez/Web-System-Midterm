<?php
include("connections.php");

$name = $address = $email = $password = $cpassword = "";
$cpasswordErr = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input
    $name = trim($_POST["name"]);
    $address = trim($_POST["address"]);
    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $password = trim($_POST["password"]);
    $cpassword = trim($_POST["confirm_password"]);

    // Validate password match
    if ($password !== $cpassword) {
        $cpasswordErr = "Passwords do not match!";
    }

    if (empty($cpasswordErr)) {
        // Check if email exists
        $stmt = $connections->prepare("SELECT * FROM mytbl WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $cpasswordErr = "Email is already registered!";
        } else {
            // Redirect if credentials are valid
            header("Location: user/index.php");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://kit.fontawesome.com/d5b7a13861.js" crossorigin="anonymous"></script>

    <!-- Toastr.js -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
</head>
<body class="w-full h-screen flex items-center justify-evenly bg-[#ee4d2d]">
    <div class="flex flex-col gap-8 items-center justify-center">
        <img src="assets/logo.webp" class="w-80 h-80">
        <div class="flex flex-col">
            <h1 class="text-white text-2xl text-center">The leading online shopping platform</h1>
            <h1 class="text-white text-2xl text-center">in the Whole Wide Universe</h1>
        </div>
    </div>
    
    <form class="bg-white w-[400px] h-auto flex flex-col gap-y-6 items-center py-4" action="" method="POST">
      <h1 class="text-start w-[340px] text-xl">Sign Up</h1>

      <input name="name" value="<?= htmlspecialchars($name); ?>" class="outline-none border-gray-300 px-3 py-2 border w-[338px]" placeholder="Name" required>

      <input name="address" value="<?= htmlspecialchars($address); ?>" class="outline-none border-gray-300 px-3 py-2 border w-[338px]" placeholder="Address" required>

      <input name="email" value="<?= htmlspecialchars($email); ?>" class="outline-none border-gray-300 px-3 py-2 border w-[338px]" placeholder="Email" required>

      <div class="relative w-[338px]">
          <input id="password" name="password" class="outline-none border-gray-300 px-3 py-2 border w-full" placeholder="Password" type="password" required>
          <i class="togglePassword fa-solid fa-eye-slash text-[#ee4d2d] absolute right-3 top-1/2 transform -translate-y-1/2 cursor-pointer"></i>
      </div>

      <div class="relative w-[338px]">
          <input id="confirmPassword" name="confirm_password" class="outline-none border-gray-300 px-3 py-2 border w-full" placeholder="Confirm Password" type="password" required>
          <i class="togglePassword fa-solid fa-eye-slash text-[#ee4d2d] absolute right-3 top-1/2 transform -translate-y-1/2 cursor-pointer"></i>
      </div>

      <input type="submit" class="w-[340px] bg-[#ee4d2d] py-[10px] text-white" value="SIGN UP">
      <h1 class="text-gray-400">Already have an account? <a class="text-[#ee4d2d] font-bold" href="index.php">Log In</a></h1>
    </form>

    <!-- jQuery (needed for Toastr.js) -->
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

        <?php if (!empty($cpasswordErr)) : ?>
            toastr.error("<?= $cpasswordErr ?>");
        <?php endif; ?>
    </script>

    <!-- Password Toggle -->
    <script>
        document.querySelectorAll('.togglePassword').forEach(icon => {
            icon.addEventListener('click', () => {
                let input = icon.previousElementSibling;
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                }
            });
        });
    </script>
</body>
</html>