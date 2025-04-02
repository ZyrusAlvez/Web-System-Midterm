<?php 
  include("../connections.php");
  
  // Initialize messages array
  $messages = [];

  // Handle status updates
  if (isset($_POST['update_status'])) {
      $order_id = intval($_POST['order_id']);
      $new_status = $_POST['new_status'];
      $old_status = $_POST['old_status'];
      $product_id = intval($_POST['product_id']);
      $quantity = intval($_POST['quantity']);

      // Start transaction
      mysqli_begin_transaction($connections);

      try {
          // Update order status
          $update_query = "UPDATE orders SET status = ? WHERE order_id = ?";
          $stmt_update = mysqli_prepare($connections, $update_query);
          mysqli_stmt_bind_param($stmt_update, "si", $new_status, $order_id);
          
          // Execute the update and check for success
          if (!mysqli_stmt_execute($stmt_update)) {
              throw new Exception("Failed to update order status: " . mysqli_stmt_error($stmt_update));
          }

          // If status is being changed to cancelled, add back product quantity
          if ($new_status == 'cancelled' && $old_status != 'cancelled') {
              $update_product_query = "UPDATE products SET quantity = quantity + ? WHERE id = ?";
              $stmt_update_product = mysqli_prepare($connections, $update_product_query);
              mysqli_stmt_bind_param($stmt_update_product, "ii", $quantity, $product_id);
              
              if (!mysqli_stmt_execute($stmt_update_product)) {
                  throw new Exception("Failed to update product quantity: " . mysqli_stmt_error($stmt_update_product));
              }
          }

          // Commit transaction
          mysqli_commit($connections);
          $messages['success'] = "Order status updated successfully!";
      } catch (Exception $e) {
          // Rollback transaction on error
          mysqli_rollback($connections);
          $messages['error'] = $e->getMessage();
      }
  }

  // Get the current filter
  $current_status = isset($_GET['status']) ? $_GET['status'] : 'all';
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin - Orders</title>

  <!-- Toastr.js -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
  <style>
    /* Your existing styles here */
    .edit-form, .add-form-container {
      display: none;
    }
    
    /* Custom modal styles */
    .modal-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: 1000;
      justify-content: center;
      align-items: center;
    }
    
    .modal-container {
      background-color: white;
      border-radius: 8px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
      width: 90%;
      max-width: 400px;
      animation: fadeIn 0.3s;
    }
    
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .category-filters {
      display: flex;
      overflow-x: auto;
      gap: 8px;
      padding-bottom: 8px;
      margin-bottom: 16px;
    }
    
    .category-filter {
      white-space: nowrap;
      padding: 8px 16px;
      border-radius: 20px;
      font-size: 14px;
      cursor: pointer;
      transition: all 0.2s;
    }
    
    .category-filter.active {
      background-color: #ee4d2d;
      color: white;
    }
    
    .category-filter:not(.active) {
      background-color: #f5f5f5;
      color: #333;
    }
    
    .category-filter:hover:not(.active) {
      background-color: #e0e0e0;
    }
    
    .category-header {
      font-size: 18px;
      font-weight: bold;
      padding: 12px;
      background-color: #f9f9f9;
      border-top: 1px solid #eee;
      border-bottom: 1px solid #eee;
      margin-top: 20px;
      color: #333;
    }
    
    .category-header:first-of-type {
      margin-top: 0;
    }

    /* New mobile navigation styles */
    .mobile-nav-transition {
      transition: transform 0.3s ease-in-out;
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
        <a href="index.php" class="flex items-center gap-3 p-3 rounded-lg text-white hover:bg-[#ff6347] transition-colors">
          <i class="fa-solid fa-list-check w-6 text-center"></i>
          <span>Products</span>
        </a>
        <a href="orders.php" class="flex items-center gap-3 p-3 rounded-lg text-white font-medium bg-[#ff6347] hover:bg-[#ff7e6b] transition-colors">
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
        <a href="index.php" class="flex items-center gap-3 p-3 rounded-lg text-white hover:bg-[#ff6347] transition-colors">
          <i class="fa-solid fa-list-check w-6 text-center"></i>
          <span>Products</span>
        </a>
        <a href="orders.php" class="flex items-center gap-3 p-3 rounded-lg text-white font-medium bg-[#ff6347] hover:bg-[#ff7e6b] transition-colors">
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

    <!-- Main Content Area -->
    <div class="w-full md:ml-64 flex-1">
      <div class="bg-[#faf9f6] w-full min-h-screen p-8 overflow-y-auto">
        <h1 class="text-3xl font-bold mb-6">Order Management</h1>
        
        <div class="bg-white rounded-lg shadow-md p-6">
          <h2 class="text-xl font-semibold mb-4">Orders List</h2>
          
          <!-- Improved Status filters -->
          <div class="flex flex-wrap gap-4 mb-6">
            <a href="?status=all" class="<?= $current_status == 'all' ? 'bg-gray-300' : 'bg-gray-200' ?> px-4 py-2 rounded-md hover:bg-gray-300 transition-colors duration-200 flex items-center gap-2">
              <span class="w-3 h-3 rounded-full bg-blue-500"></span>
              All Orders
            </a>
            <a href="?status=pending" class="<?= $current_status == 'pending' ? 'bg-yellow-200' : 'bg-yellow-100' ?> px-4 py-2 rounded-md hover:bg-yellow-200 transition-colors duration-200 flex items-center gap-2">
              <span class="w-3 h-3 rounded-full bg-yellow-500"></span>
              Pending
            </a>
            <a href="?status=completed" class="<?= $current_status == 'completed' ? 'bg-green-200' : 'bg-green-100' ?> px-4 py-2 rounded-md hover:bg-green-200 transition-colors duration-200 flex items-center gap-2">
              <span class="w-3 h-3 rounded-full bg-green-500"></span>
              Completed
            </a>
            <a href="?status=cancelled" class="<?= $current_status == 'cancelled' ? 'bg-red-200' : 'bg-red-100' ?> px-4 py-2 rounded-md hover:bg-red-200 transition-colors duration-200 flex items-center gap-2">
              <span class="w-3 h-3 rounded-full bg-red-500"></span>
              Cancelled
            </a>
          </div>
          
          <div class="overflow-x-auto">
            <table class="min-w-full bg-white rounded-lg">
              <thead>
                <tr class="bg-gray-100">
                  <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Order ID</th>
                  <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">User</th>
                  <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Product</th>
                  <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Quantity</th>
                  <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Status</th>
                  <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <?php
                  // Build the query based on filter
                  $status_filter = isset($_GET['status']) && $_GET['status'] != 'all' ? $_GET['status'] : '';
                  
                  $query = "SELECT o.order_id, o.user_id, o.product_id, o.quantity, o.status, 
                  u.name AS user_name, p.name AS product_name 
                  FROM orders o
                  INNER JOIN users u ON o.user_id = u.id
                  INNER JOIN products p ON o.product_id = p.id";

                  if (!empty($status_filter)) {
                    // Use prepared statement to prevent SQL injection
                    $query .= " WHERE o.status = ?";
                  }

                  $query .= " ORDER BY o.order_id DESC";

                  if (!empty($status_filter)) {
                    $stmt = mysqli_prepare($connections, $query);
                    mysqli_stmt_bind_param($stmt, "s", $status_filter);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                  } else {
                    $result = mysqli_query($connections, $query);
                  }

                  
                  if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                      // Determine status badge color
                      $status_class = '';
                      switch ($row['status']) {
                        case 'pending':
                          $status_class = 'bg-yellow-100 text-yellow-800';
                          break;
                        case 'completed':
                          $status_class = 'bg-green-100 text-green-800';
                          break;
                        case 'cancelled':
                          $status_class = 'bg-red-100 text-red-800';
                          break;
                        default:
                          $status_class = 'bg-gray-100 text-gray-800';
                      }
                ?>
                <tr class="hover:bg-gray-50">
                  <td class="px-6 py-4 text-sm text-gray-800"><?= $row['order_id'] ?></td>
                  <td class="px-6 py-4 text-sm text-gray-800"><?= htmlspecialchars($row['user_name']) ?></td>
                  <td class="px-6 py-4 text-sm text-gray-800"><?= htmlspecialchars($row['product_name']) ?></td>
                  <td class="px-6 py-4 text-sm text-gray-800"><?= $row['quantity'] ?></td>
                  <td class="px-6 py-4">
                    <span class="px-2 py-1 rounded-full text-xs font-medium <?= $status_class ?>">
                      <?= ucfirst($row['status']) ?>
                    </span>
                  </td>
                  <td class="px-6 py-4">
                    <form method="POST" class="flex items-center space-x-2">
                      <input type="hidden" name="order_id" value="<?= $row['order_id'] ?>">
                      <input type="hidden" name="product_id" value="<?= $row['product_id'] ?>">
                      <input type="hidden" name="quantity" value="<?= $row['quantity'] ?>">
                      <input type="hidden" name="old_status" value="<?= $row['status'] ?>">
                      <select name="new_status" class="text-sm border rounded px-2 py-1">
                        <option value="pending" <?= $row['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="completed" <?= $row['status'] == 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="cancelled" <?= $row['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                      </select>
                      <button type="submit" name="update_status" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm">
                        Update
                      </button>
                    </form>
                  </td>
                </tr>
                <?php
                    }
                  } else {
                ?>
                <tr>
                  <td colspan="6" class="px-6 py-4 text-sm text-center text-gray-500">No orders found</td>
                </tr>
                <?php
                  }
                ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- jQuery (for Toastr) -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  <!-- Toastr.js -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- FontAwesome -->
  <script src="https://kit.fontawesome.com/d5b7a13861.js" crossorigin="anonymous"></script>
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
  <script>
    // Display messages with Toastr
    <?php if (isset($messages['success'])): ?>
      toastr.success('<?= $messages['success'] ?>');
    <?php endif; ?>
    
    <?php if (isset($messages['error'])): ?>
      toastr.error('<?= $messages['error'] ?>');
    <?php endif; ?>
  </script>
</body>
</html>