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
          // Validate product quantity BEFORE updating
          if ($new_status == 'completed' && $old_status != 'completed') {
              $check_query = "SELECT quantity FROM products WHERE id = ?";
              $stmt_check = mysqli_prepare($connections, $check_query);
              mysqli_stmt_bind_param($stmt_check, "i", $product_id);
              mysqli_stmt_execute($stmt_check);
              $check_result = mysqli_stmt_get_result($stmt_check);
              $product = mysqli_fetch_assoc($check_result);

              if ($product['quantity'] < $quantity) {
                  throw new Exception("Not enough product quantity available!");
              }
          }

          // Update order status
          $update_query = "UPDATE orders SET status = ? WHERE order_id = ?";
          $stmt_update = mysqli_prepare($connections, $update_query);
          mysqli_stmt_bind_param($stmt_update, "si", $new_status, $order_id);
          
          // Execute the update and check for success
          if (!mysqli_stmt_execute($stmt_update)) {
              throw new Exception("Failed to update order status: " . mysqli_stmt_error($stmt_update));
          }

          // If status is being changed to completed, reduce product quantity
          if ($new_status == 'completed' && $old_status != 'completed') {
              $update_product_query = "UPDATE products SET quantity = quantity - ? WHERE id = ?";
              $stmt_update_product = mysqli_prepare($connections, $update_product_query);
              mysqli_stmt_bind_param($stmt_update_product, "ii", $quantity, $product_id);
              
              if (!mysqli_stmt_execute($stmt_update_product)) {
                  throw new Exception("Failed to update product quantity: " . mysqli_stmt_error($stmt_update_product));
              }
          }

          // If status is changed from completed to something else, add back quantity
          if ($old_status == 'completed' && $new_status != 'completed') {
              $restore_product_query = "UPDATE products SET quantity = quantity + ? WHERE id = ?";
              $stmt_restore_product = mysqli_prepare($connections, $restore_product_query);
              mysqli_stmt_bind_param($stmt_restore_product, "ii", $quantity, $product_id);
              
              if (!mysqli_stmt_execute($stmt_restore_product)) {
                  throw new Exception("Failed to restore product quantity: " . mysqli_stmt_error($stmt_restore_product));
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

  <div class="bg-[#faf9f6] w-full h-full p-8 overflow-y-auto">
    <h1 class="text-3xl font-bold mb-6">Order Management</h1>
    
    <div class="bg-white rounded-lg shadow-md p-6">
      <h2 class="text-xl font-semibold mb-4">Orders List</h2>
      
      <!-- Improved Status filters -->
      <div class="flex gap-4 mb-6">
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
  
  <!-- jQuery (for Toastr) -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  <!-- Toastr.js -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- FontAwesome -->
  <script src="https://kit.fontawesome.com/d5b7a13861.js" crossorigin="anonymous"></script>

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