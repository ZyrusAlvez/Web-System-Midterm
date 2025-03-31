<?php
include("connections.php");

$name = $address = $email = $password = $cpassword = "";
$errorMessage = ""; // Store the error message

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input
    $name = trim($_POST["name"]);
    $address = trim($_POST["address"]);
    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $password = trim($_POST["password"]);
    $cpassword = trim($_POST["confirm_password"]);

    // Validate password match
    if ($password !== $cpassword) {
        $errorMessage = "Passwords do not match!";
    }

    if (empty($errorMessage)) {
        // Check if email exists
        $stmt = $connections->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errorMessage = "Email is already registered!";
        } else {
            // Insert user data here (commented out as it was missing in original)
            /*
            $stmt = $connections->prepare("INSERT INTO users (name, address, email, password, account_type) VALUES (?, ?, ?, ?, '0')");
            $stmt->bind_param("ssss", $name, $address, $email, $password);
            $stmt->execute();
            */
            
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
    <title>Chopee - Sign Up</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'chopee': {
                            '50': '#fff0eb',
                            '100': '#ffe4d9',
                            '200': '#ffc9b3',
                            '300': '#ffa985',
                            '400': '#ff7e4c',
                            '500': '#ee4d2d',
                            '600': '#d63b1c',
                            '700': '#b22e15',
                            '800': '#8c2714',
                            '900': '#6e2013'
                        }
                    },
                    fontFamily: {
                        'sans': ['Inter', 'ui-sans-serif', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'Helvetica Neue', 'Arial', 'sans-serif']
                    },
                    boxShadow: {
                        'chopee': '0 4px 12px rgba(238, 77, 45, 0.15)'
                    }
                }
            }
        }
    </script>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- FontAwesome -->
    <script src="https://kit.fontawesome.com/d5b7a13861.js" crossorigin="anonymous"></script>

    <!-- Toastr.js for Notifications -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
</head>
<body class="bg-gray-100 font-sans h-screen">
    <div class="flex flex-col lg:flex-row h-screen bg-chopee-500">
        <!-- Left side - Background and branding -->
        <div class="lg:w-1/2 flex items-center justify-center p-6 lg:p-12 relative">
            <div class="flex flex-col gap-6 items-center justify-center z-10 text-center">
                <img src="assets/logo_w_name.webp" class="w-52 h-52 lg:w-64 lg:h-64 mb-4 transition-all duration-300 hover:scale-105">
                <div class="flex flex-col">
                    <h1 class="text-white text-xl lg:text-2xl font-medium">The leading online shopping platform</h1>
                    <h1 class="text-white text-xl lg:text-2xl font-medium">in the Whole Wide Universe</h1>
                </div>
                
                <!-- Benefits -->
                <div class="hidden lg:flex flex-col gap-4 mt-4 text-white">
                    <div class="flex items-center gap-3">
                        <div class="bg-white/20 rounded-full p-2">
                            <i class="fas fa-shipping-fast text-white"></i>
                        </div>
                        <span>Fast & Reliable Shipping</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="bg-white/20 rounded-full p-2">
                            <i class="fas fa-shield-alt text-white"></i>
                        </div>
                        <span>Secure Payment Options</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="bg-white/20 rounded-full p-2">
                            <i class="fas fa-tag text-white"></i>
                        </div>
                        <span>Exclusive Deals & Discounts</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right side - Registration form -->
        <div class="lg:w-1/2 flex items-center justify-center p-6 lg:p-12 bg-chopee-500">
            <div class="bg-white w-full max-w-md rounded-lg shadow-lg p-8">
                <div class="flex justify-between items-center mb-8">
                    <h1 class="text-2xl font-bold text-gray-800">Create Account</h1>
                    <div class="text-chopee-500">
                        <i class="fas fa-user-plus text-3xl"></i>
                    </div>
                </div>
                
                <form class="flex flex-col gap-5" action="" method="POST">
                    <!-- Name Field -->
                    <div class="flex flex-col gap-2">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <input 
                                id="name" 
                                name="name" 
                                type="text" 
                                value="<?= htmlspecialchars($name); ?>"
                                class="w-full py-3 pl-10 pr-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-chopee-500 focus:border-chopee-500 transition-all duration-200" 
                                placeholder="Enter your full name"
                                required
                            >
                        </div>
                    </div>
                    
                    <!-- Address Field -->
                    <div class="flex flex-col gap-2">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <i class="fas fa-map-marker-alt text-gray-400"></i>
                            </div>
                            <input 
                                id="address" 
                                name="address" 
                                type="text" 
                                value="<?= htmlspecialchars($address); ?>"
                                class="w-full py-3 pl-10 pr-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-chopee-500 focus:border-chopee-500 transition-all duration-200" 
                                placeholder="Enter your address"
                                required
                            >
                        </div>
                    </div>
                    
                    <!-- Email Field -->
                    <div class="flex flex-col gap-2">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <i class="fas fa-envelope text-gray-400"></i>
                            </div>
                            <input 
                                id="email" 
                                name="email" 
                                value="<?= htmlspecialchars($email); ?>"
                                class="w-full py-3 pl-10 pr-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-chopee-500 focus:border-chopee-500 transition-all duration-200" 
                                placeholder="Enter your email"
                                required
                            >
                        </div>
                    </div>
                    
                    <!-- Password Field -->
                    <div class="flex flex-col gap-2">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input 
                                id="password" 
                                name="password" 
                                type="password" 
                                class="w-full py-3 pl-10 pr-10 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-chopee-500 focus:border-chopee-500 transition-all duration-200" 
                                placeholder="Create a password"
                                required
                            >
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                <i class="togglePassword fas fa-eye-slash text-gray-400 cursor-pointer hover:text-chopee-500 transition-colors duration-200"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Confirm Password Field -->
                    <div class="flex flex-col gap-2">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input 
                                id="confirm_password" 
                                name="confirm_password" 
                                type="password" 
                                class="w-full py-3 pl-10 pr-10 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-chopee-500 focus:border-chopee-500 transition-all duration-200" 
                                placeholder="Confirm your password"
                                required
                            >
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                <i class="togglePassword fas fa-eye-slash text-gray-400 cursor-pointer hover:text-chopee-500 transition-colors duration-200"></i>
                            </div>
                        </div>
                    </div>
                    
                    <button 
                        type="submit" 
                        class="bg-chopee-500 text-white py-3 rounded-lg font-medium mt-2 transition-all duration-300 hover:bg-chopee-600 focus:outline-none focus:ring-2 focus:ring-chopee-500 focus:ring-offset-2 active:bg-chopee-700"
                    >
                        SIGN UP
                    </button>
                    
                    <div class="flex items-center justify-center gap-2">
                        <span class="text-gray-500">Already have an account?</span>
                        <a href="index.php" class="text-chopee-500 font-medium hover:text-chopee-600 transition-colors duration-200">Log In</a>
                    </div>
                </form>

            </div>
        </div>
    </div>

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
        document.querySelectorAll('.togglePassword').forEach(icon => {
            icon.addEventListener('click', () => {
                const input = icon.closest('div').querySelector('input');
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                
                // Toggle icon
                if (type === 'text') {
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                } else {
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                }
            });
        });
    </script>
</body>
</html>