<?php 
session_start();
include("../connections.php");

if (!isset($connections)) {
    die("Database connection error.");
}

// Check if the user is logged in and their account_type
if (!isset($_SESSION['account_type'])) {
  // Not logged in? Redirect to login or index page
  header("Location: /php/index.php");
  exit();
}

// If the user is of type 2 (normal user), redirect them away from admin
if ($_SESSION['account_type'] == "2") {
  header("Location: /php/user/index.php");
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Chopee Admin Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
  <style>
    /* Custom mobile navigation styles */
    .mobile-nav-transition {
      transition: transform 0.3s ease-in-out;
    }
    
    /* Dashboard welcome message styles */
    .welcome-container {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      height: 70vh;
      text-align: center;
      padding: 1rem;
    }
    
    .welcome-icon {
      font-size: 4rem;
      color: #ee4d2d;
      margin-bottom: 1.5rem;
    }
    
    .welcome-title {
      font-size: 2rem;
      font-weight: bold;
      margin-bottom: 1rem;
      color: #333;
    }
    
    .welcome-text {
      font-size: 1.1rem;
      color: #666;
      max-width: 600px;
      line-height: 1.6;
    }
  </style>
</head>
<body class="w-full min-h-screen bg-gray-50 flex flex-col">
  <!-- Mobile Navigation Bar -->
  <header class="bg-[#ee4d2d] text-white p-4 sticky top-0 z-30 md:hidden flex justify-between items-center shadow-md">
    <div class="flex items-center gap-2">
      <img src="../assets/logo.jpg" class="w-10 h-10 rounded-full object-cover">
      <h1 class="text-xl font-bold">Chopee</h1>
    </div>
    <button id="mobileMenuBtn" class="text-2xl focus:outline-none">
      <i class="fa-solid fa-bars"></i>
    </button>
  </header>

  <!-- Mobile Side Navigation (Hidden by default) -->
  <div id="mobileSidebar" class="fixed inset-y-0 left-0 transform -translate-x-full mobile-nav-transition w-64 bg-[#ee4d2d] z-40 md:hidden">
    <div class="flex flex-col h-full">
      <div class="flex items-center justify-between p-4 border-b border-[#ff6347]">
        <div class="flex items-center gap-2">
          <img src="../assets/logo.jpg" class="w-12 h-12 rounded-full object-cover">
          <h1 class="text-white text-xl font-bold">Chopee Admin</h1>
        </div>
        <button id="closeMenuBtn" class="text-white text-xl">
          <i class="fa-solid fa-times"></i>
        </button>
      </div>
      
      <nav class="flex flex-col gap-1 mt-6 p-2">
        <a href="products.php" class="flex items-center gap-3 p-3 rounded-lg text-white font-medium bg-[#ff6347] hover:bg-[#ff7e6b] transition-colors">
          <i class="fa-solid fa-list-check w-6 text-center"></i>
          <span>Products</span>
        </a>
        <a href="orders.php" class="flex items-center gap-3 p-3 rounded-lg text-white hover:bg-[#ff6347] transition-colors">
          <i class="fa-solid fa-cart-shopping w-6 text-center"></i>
          <span>Orders</span>
        </a>
        <a href="users.php" class="flex items-center gap-3 p-3 rounded-lg text-white hover:bg-[#ff6347] transition-colors">
          <i class="fa-solid fa-users w-6 text-center"></i>
          <span>Users</span>
        </a>
      </nav>
      
      <div class="mt-auto border-t border-[#ff6347] p-4">
        <a href="../index.php" class="flex items-center gap-3 p-3 rounded-lg text-white hover:bg-[#ff6347] transition-colors">
          <i class="fa-solid fa-right-from-bracket w-6 text-center"></i>
          <span>Logout</span>
        </a>
      </div>
    </div>
  </div>
  
  <!-- Backdrop when mobile menu is open -->
  <div id="mobileBackdrop" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden md:hidden"></div>

  <div class="flex flex-1">
    <!-- Desktop Sidebar Navigation -->
    <aside class="hidden md:flex flex-col w-64 bg-gradient-to-b from-[#ee4d2d] to-[#d03a1b] min-h-screen fixed left-0 top-0 shadow-lg">
      <div class="flex items-center justify-center p-4 border-b border-[#ff6347]">
        <img src="../assets/logo.jpg" class="w-12 h-12 rounded-full object-cover">
        <h1 class="text-white text-xl font-bold ml-3">Chopee Admin</h1>
      </div>
      
      <nav class="flex flex-col gap-1 mt-6 p-2">
        <a href="products.php" class="flex items-center gap-3 p-3 rounded-lg text-white hover:bg-[#ff6347] transition-colors">
          <i class="fa-solid fa-list-check w-6 text-center"></i>
          <span>Products</span>
        </a>
        <a href="orders.php" class="flex items-center gap-3 p-3 rounded-lg text-white hover:bg-[#ff6347] transition-colors">
          <i class="fa-solid fa-cart-shopping w-6 text-center"></i>
          <span>Orders</span>
        </a>
        <a href="users.php" class="flex items-center gap-3 p-3 rounded-lg text-white hover:bg-[#ff6347] transition-colors">
          <i class="fa-solid fa-users w-6 text-center"></i>
          <span>Users</span>
        </a>
      </nav>
      
      <div class="mt-auto border-t border-[#ff6347] p-4">
        <a href="../index.php" class="flex items-center gap-3 p-3 rounded-lg text-white hover:bg-[#ff6347] transition-colors">
          <i class="fa-solid fa-right-from-bracket w-6 text-center"></i>
          <span>Logout</span>
        </a>
      </div>
    </aside>

    <!-- Main Content Area - Added pl-64 for md screens -->
    <div class="w-full md:pl-64">
      <div class="bg-[#faf9f6] w-full h-full p-8 overflow-y-auto">
        <!-- Welcome Message -->
        <div class="welcome-container">
          <div class="welcome-icon">
            <i class="fa-solid fa-shop"></i>
          </div>
          <h2 class="welcome-title">Welcome to Chopee Admin Dashboard</h2>
          <p class="welcome-text">
            Select an option from the sidebar to manage your products, orders, and users.
            This central hub provides you with all the tools needed to administer your Chopee store efficiently.
          </p>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://kit.fontawesome.com/d5b7a13861.js" crossorigin="anonymous"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
  
  <!-- JavaScript for mobile navigation toggle -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const mobileMenuBtn = document.getElementById('mobileMenuBtn');
      const closeMenuBtn = document.getElementById('closeMenuBtn');
      const mobileSidebar = document.getElementById('mobileSidebar');
      const mobileBackdrop = document.getElementById('mobileBackdrop');
      
      function openMobileMenu() {
        mobileSidebar.classList.remove('-translate-x-full');
        mobileSidebar.classList.add('translate-x-0');
        mobileBackdrop.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
      }
      
      function closeMobileMenu() {
        mobileSidebar.classList.remove('translate-x-0');
        mobileSidebar.classList.add('-translate-x-full');
        mobileBackdrop.classList.add('hidden');
        document.body.style.overflow = '';
      }
      
      mobileMenuBtn.addEventListener('click', openMobileMenu);
      closeMenuBtn.addEventListener('click', closeMobileMenu);
      mobileBackdrop.addEventListener('click', closeMobileMenu);
    });
  </script>
</body>
</html>