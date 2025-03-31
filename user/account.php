<?php
session_start();
include("../connections.php");

if (!isset($connections)) {
    die("Database connection error.");
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];

// Handle logout
if (isset($_POST['action']) && $_POST['action'] === 'logout') {
    session_destroy();
    header("Location: ../index.php");
    exit;
}

// Handle order cancel action
if (isset($_POST['action']) && $_POST['action'] === 'cancel_order' && isset($_POST['order_id'])) {
    $order_id = $_POST['order_id'];
    
    // Update order status to cancelled
    $cancel_query = "UPDATE orders SET status = 'cancelled' WHERE order_id = ? AND user_id = ? AND status = 'pending'";
    $stmt = $connections->prepare($cancel_query);
    $stmt->bind_param("ii", $order_id, $user_id);
    
    if ($stmt->execute()) {
        // Set success message
        $_SESSION['toast_message'] = 'Order successfully cancelled.';
    } else {
        $_SESSION['toast_message'] = 'Failed to cancel order. Please try again.';
    }
    
    // Redirect to refresh the page
    header("Location: account.php");
    exit;
}

// Handle re-order action
if (isset($_POST['action']) && $_POST['action'] === 'reorder' && isset($_POST['order_id'])) {
    $order_id = $_POST['order_id'];
    
    // Update order status to pending
    $reorder_query = "UPDATE orders SET status = 'pending' WHERE order_id = ? AND user_id = ? AND status = 'cancelled'";
    $stmt = $connections->prepare($reorder_query);
    $stmt->bind_param("ii", $order_id, $user_id);
    
    if ($stmt->execute()) {
        // Set success message
        $_SESSION['toast_message'] = 'Order successfully placed again.';
    } else {
        $_SESSION['toast_message'] = 'Failed to re-order. Please try again.';
    }
    
    // Redirect to refresh the page
    header("Location: account.php");
    exit;
}

// Fetch user details
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt = $connections->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

// Fetch user's orders with product details
$orders_query = "SELECT o.*, o.quantity, p.name as product_name, p.price, p.image FROM orders o 
                JOIN products p ON o.product_id = p.id 
                WHERE o.user_id = ? 
                ORDER BY o.order_id DESC";
$stmt = $connections->prepare($orders_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders_result = $stmt->get_result();
$orders = [];

while ($order = $orders_result->fetch_assoc()) {
    $orders[] = $order;
}

// Group orders by status
$pending_orders = array_filter($orders, function($order) { return $order['status'] === 'pending'; });
$completed_orders = array_filter($orders, function($order) { return $order['status'] === 'completed'; });
$cancelled_orders = array_filter($orders, function($order) { return $order['status'] === 'cancelled'; });

// Fetch user's cart items count
$cart_count = 0;
$cart_query = "SELECT SUM(quantity) as total FROM cart WHERE user_id = ?";
$stmt = $connections->prepare($cart_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_result = $stmt->get_result();
$cart_data = $cart_result->fetch_assoc();
if ($cart_data && $cart_data['total']) {
    $cart_count = $cart_data['total'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Account | Chopee</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            'chopee': {
              '50': '#fff0eb',
              '100': '#ffdfd3',
              '500': '#ee4d2d',
              '600': '#d23f21',
            }
          }
        }
      }
    }
  </script>
</head>
<body class="bg-gray-100 text-gray-800 font-sans m-0 p-0">
  <!-- Header -->
  <header class="bg-white shadow-md py-3 px-6 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto flex justify-between items-center flex-col md:flex-row">
      <div class="flex items-center justify-center mb-3 md:mb-0">
        <a href="index.php" class="flex items-center no-underline">
          <img src="../assets/logo_no_bg.webp" alt="Chopee" class="w-10 h-10">
          <span class="text-chopee-500 font-semibold text-2xl ml-2">Chopee</span>
        </a>
      </div>
      
      <div class="flex-1 max-w-xl mx-0 md:mx-6 relative w-full">
        <form action="index.php" method="GET" class="w-full">
          <input type="text" name="search" class="w-full px-4 py-2 rounded border-2 border-chopee-500 text-sm outline-none transition-all duration-200" placeholder="Search products...">
          <button type="submit" class="absolute right-0 top-0 h-full bg-chopee-500 border-none text-white px-4 rounded-r cursor-pointer">
            <i class="fas fa-search"></i>
          </button>
        </form>
      </div>
      
      <div class="flex gap-4 items-center justify-center mt-3 md:mt-0">
        <a href="cart.php" class="flex flex-col items-center text-gray-600 no-underline text-xs hover:text-chopee-500 transition-all duration-200 border-none bg-transparent cursor-pointer relative">
          <i class="fas fa-shopping-cart text-lg mb-1"></i>
          <span>Cart</span>
          <?php if ($cart_count > 0): ?>
          <span class="absolute -top-1 -right-1 bg-chopee-500 text-white rounded-full text-xs w-5 h-5 flex items-center justify-center">
            <?php echo $cart_count; ?>
          </span>
          <?php endif; ?>
        </a>
        <div class="flex flex-col items-center text-chopee-500 no-underline text-xs transition-all duration-200">
          <i class="fas fa-user text-lg mb-1"></i>
          <span><?php echo htmlspecialchars($user_name); ?></span>
        </div>
      </div>
    </div>
  </header>

  <!-- Main Content -->
  <div class="max-w-7xl mx-auto my-6 px-4">
    <!-- Breadcrumb -->
    <div class="flex items-center text-sm mb-4 text-gray-600">
      <a href="index.php" class="text-gray-600 no-underline hover:text-chopee-500 transition-colors duration-200">Home</a>
      <span class="mx-2 text-gray-400">/</span>
      <span>My Account</span>
    </div>

    <!-- Account Details -->
    <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
      <div class="p-6 border-b">
        <div class="flex justify-between items-center">
          <h1 class="text-2xl font-semibold text-gray-800">My Account</h1>
          <form method="POST" onsubmit="return confirm('Are you sure you want to logout?');">
            <input type="hidden" name="action" value="logout">
            <button type="submit" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded transition-colors duration-200">
              <i class="fas fa-sign-out-alt mr-2"></i> Logout
            </button>
          </form>
        </div>
      </div>
      
      <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <h2 class="text-lg font-semibold mb-3 text-gray-800">Account Information</h2>
            <div class="bg-gray-50 p-4 rounded">
              <div class="mb-2">
                <span class="text-gray-600 font-medium">Name:</span>
                <span class="ml-2"><?php echo htmlspecialchars($user['name']); ?></span>
              </div>
              <div class="mb-2">
                <span class="text-gray-600 font-medium">Email:</span>
                <span class="ml-2"><?php echo htmlspecialchars($user['email']); ?></span>
              </div>
              <div class="mb-2">
                <span class="text-gray-600 font-medium">Address:</span>
                <span class="ml-2"><?php echo htmlspecialchars($user['address']); ?></span>
              </div>
              <div>
                <span class="text-gray-600 font-medium">Account ID:</span>
                <span class="ml-2">#<?php echo $user_id; ?></span>
              </div>
            </div>
          </div>
          
          <div>
            <h2 class="text-lg font-semibold mb-3 text-gray-800">Account Summary</h2>
            <div class="bg-gray-50 p-4 rounded">
              <div class="mb-2">
                <span class="text-gray-600 font-medium">Total Orders:</span>
                <span class="ml-2"><?php echo count($orders); ?></span>
              </div>
              <div class="mb-2">
                <span class="text-gray-600 font-medium">Pending Orders:</span>
                <span class="ml-2"><?php echo count($pending_orders); ?></span>
              </div>
              <div>
                <span class="text-gray-600 font-medium">Completed Orders:</span>
                <span class="ml-2"><?php echo count($completed_orders); ?></span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Orders Tracking -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <div class="p-6 border-b">
        <h2 class="text-xl font-semibold text-gray-800">My Orders</h2>
      </div>
      
      <div class="p-6">
        <?php if (empty($orders)): ?>
          <div class="text-center py-8">
            <i class="fas fa-shopping-bag text-gray-300 text-5xl mb-4"></i>
            <p class="text-gray-500">You haven't placed any orders yet</p>
            <a href="index.php" class="inline-block mt-4 bg-chopee-500 text-white py-2 px-4 rounded">Start Shopping</a>
          </div>
        <?php else: ?>
          <!-- Order Tabs -->
          <div class="border-b mb-6">
            <ul class="flex flex-wrap -mb-px" id="order-tabs" role="tablist">
              <li class="mr-2" role="presentation">
                <button class="inline-block p-4 border-b-2 border-chopee-500 text-chopee-500 font-medium text-sm" id="all-tab" data-target="all-orders" type="button" role="tab" aria-selected="true">
                  All Orders
                </button>
              </li>
              <li class="mr-2" role="presentation">
                <button class="inline-block p-4 border-b-2 border-transparent hover:border-gray-300 text-gray-600 hover:text-gray-700 font-medium text-sm" id="pending-tab" data-target="pending-orders" type="button" role="tab" aria-selected="false">
                  Pending
                </button>
              </li>
              <li class="mr-2" role="presentation">
                <button class="inline-block p-4 border-b-2 border-transparent hover:border-gray-300 text-gray-600 hover:text-gray-700 font-medium text-sm" id="completed-tab" data-target="completed-orders" type="button" role="tab" aria-selected="false">
                  Completed
                </button>
              </li>
              <li role="presentation">
                <button class="inline-block p-4 border-b-2 border-transparent hover:border-gray-300 text-gray-600 hover:text-gray-700 font-medium text-sm" id="cancelled-tab" data-target="cancelled-orders" type="button" role="tab" aria-selected="false">
                  Cancelled
                </button>
              </li>
            </ul>
          </div>
          
          <!-- All Orders Tab Content -->
          <div id="all-orders" class="tab-content block">
            <div class="space-y-4">
              <?php foreach ($orders as $order): ?>
                <div class="border rounded-lg overflow-hidden">
                  <div class="bg-gray-50 p-4 flex justify-between items-center border-b">
                    <div>
                      <div class="font-medium">Order ID: <?php echo $order['order_id']; ?></div>
                    </div>
                    <div class="flex items-center">
                      <?php if ($order['status'] === 'pending'): ?>
                        <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-medium">Pending</span>
                        <form method="POST" class="ml-2" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                          <input type="hidden" name="action" value="cancel_order">
                          <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                          <button type="submit" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-2 py-1 rounded text-xs font-medium">Cancel</button>
                        </form>
                      <?php elseif ($order['status'] === 'completed'): ?>
                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-medium">Completed</span>
                      <?php elseif ($order['status'] === 'cancelled'): ?>
                        <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-xs font-medium">Cancelled</span>
                        <form method="POST" class="ml-2">
                          <input type="hidden" name="action" value="reorder">
                          <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                          <button type="submit" class="bg-chopee-500 hover:bg-chopee-600 text-white px-2 py-1 rounded text-xs font-medium">Order Again</button>
                        </form>
                      <?php endif; ?>
                    </div>
                  </div>
                  <div class="p-4 flex items-center">
                    <a href="product_detail.php?id=<?php echo $order['product_id']; ?>" class="w-20 h-20 bg-gray-100 rounded overflow-hidden block">
                      <img src="../<?php echo $order['image']; ?>" alt="<?php echo $order['product_name']; ?>" class="w-full h-full object-cover">
                    </a>
                    <div class="ml-4 flex-1">
                      <a href="product_detail.php?id=<?php echo $order['product_id']; ?>" class="font-medium text-sm mb-1 hover:text-chopee-500 transition-colors duration-200">
                        <?php echo $order['product_name']; ?>
                      </a>
                      <div class="text-chopee-500 font-semibold">₱<?php echo number_format($order['price'], 2); ?></div>
                      <div class="text-gray-500 text-sm">Quantity: <?php echo $order['quantity']; ?></div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
          
          <!-- Pending Orders Tab Content -->
          <div id="pending-orders" class="tab-content hidden">
            <?php if (empty($pending_orders)): ?>
              <div class="text-center py-6">
                <p class="text-gray-500">No pending orders</p>
              </div>
            <?php else: ?>
              <div class="space-y-4">
                <?php foreach ($pending_orders as $order): ?>
                  <div class="border rounded-lg overflow-hidden">
                    <div class="bg-gray-50 p-4 flex justify-between items-center border-b">
                      <div>
                        <div class="font-medium">Order #<?php echo $order['order_id']; ?></div>
                      </div>
                      <div class="flex items-center">
                        <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-medium">Pending</span>
                        <form method="POST" class="ml-2" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                          <input type="hidden" name="action" value="cancel_order">
                          <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                          <button type="submit" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-2 py-1 rounded text-xs font-medium">Cancel</button>
                        </form>
                      </div>
                    </div>
                    <div class="p-4 flex items-center">
                      <a href="product_detail.php?id=<?php echo $order['product_id']; ?>" class="w-20 h-20 bg-gray-100 rounded overflow-hidden block">
                        <img src="../<?php echo $order['image']; ?>" alt="<?php echo $order['product_name']; ?>" class="w-full h-full object-cover">
                      </a>
                      <div class="ml-4 flex-1">
                        <a href="product_detail.php?id=<?php echo $order['product_id']; ?>" class="font-medium text-sm mb-1 hover:text-chopee-500 transition-colors duration-200">
                          <?php echo $order['product_name']; ?>
                        </a>
                        <div class="text-chopee-500 font-semibold">₱<?php echo number_format($order['price'], 2); ?></div>
                        <div class="text-gray-500 text-sm">Quantity: <?php echo $order['quantity']; ?></div>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
          
          <!-- Completed Orders Tab Content -->
          <div id="completed-orders" class="tab-content hidden">
            <?php if (empty($completed_orders)): ?>
              <div class="text-center py-6">
                <p class="text-gray-500">No completed orders</p>
              </div>
            <?php else: ?>
              <div class="space-y-4">
                <?php foreach ($completed_orders as $order): ?>
                  <div class="border rounded-lg overflow-hidden">
                    <div class="bg-gray-50 p-4 flex justify-between items-center border-b">
                      <div>
                        <div class="font-medium">Order #<?php echo $order['order_id']; ?></div>
                      </div>
                      <div class="flex items-center">
                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-medium">Completed</span>
                      </div>
                    </div>
                    <div class="p-4 flex items-center">
                      <a href="product_detail.php?id=<?php echo $order['product_id']; ?>" class="w-20 h-20 bg-gray-100 rounded overflow-hidden block">
                        <img src="../<?php echo $order['image']; ?>" alt="<?php echo $order['product_name']; ?>" class="w-full h-full object-cover">
                      </a>
                      <div class="ml-4 flex-1">
                        <a href="product_detail.php?id=<?php echo $order['product_id']; ?>" class="font-medium text-sm mb-1 hover:text-chopee-500 transition-colors duration-200">
                          <?php echo $order['product_name']; ?>
                        </a>
                        <div class="text-chopee-500 font-semibold">₱<?php echo number_format($order['price'], 2); ?></div>
                        <div class="text-gray-500 text-sm">Quantity: <?php echo $order['quantity']; ?></div>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
          
          <!-- Cancelled Orders Tab Content -->
          <div id="cancelled-orders" class="tab-content hidden">
            <?php if (empty($cancelled_orders)): ?>
              <div class="text-center py-6">
                <p class="text-gray-500">No cancelled orders</p>
              </div>
            <?php else: ?>
              <div class="space-y-4">
                <?php foreach ($cancelled_orders as $order): ?>
                  <div class="border rounded-lg overflow-hidden">
                    <div class="bg-gray-50 p-4 flex justify-between items-center border-b">
                      <div>
                        <div class="font-medium">Order #<?php echo $order['order_id']; ?></div>
                      </div>
                      <div class="flex items-center">
                        <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-xs font-medium">Cancelled</span>
                        <form method="POST" class="ml-2">
                          <input type="hidden" name="action" value="reorder">
                          <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                          <button type="submit" class="bg-chopee-500 hover:bg-chopee-600 text-white px-2 py-1 rounded text-xs font-medium">Order Again</button>
                        </form>
                      </div>
                    </div>
                    <div class="p-4 flex items-center">
                      <a href="product_detail.php?id=<?php echo $order['product_id']; ?>" class="w-20 h-20 bg-gray-100 rounded overflow-hidden block">
                        <img src="../<?php echo $order['image']; ?>" alt="<?php echo $order['product_name']; ?>" class="w-full h-full object-cover">
                      </a>
                      <div class="ml-4 flex-1">
                        <a href="product_detail.php?id=<?php echo $order['product_id']; ?>" class="font-medium text-sm mb-1 hover:text-chopee-500 transition-colors duration-200">
                          <?php echo $order['product_name']; ?>
                        </a>
                        <div class="text-chopee-500 font-semibold">₱<?php echo number_format($order['price'], 2); ?></div>
                        <div class="text-gray-500 text-sm">Quantity: <?php echo $order['quantity']; ?></div>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  
  <!-- Toast Notification -->
  <div id="toast" class="fixed bottom-4 right-4 bg-gray-800 text-white px-4 py-2 rounded shadow-lg transition-opacity duration-300 opacity-0 pointer-events-none z-50">
    <span id="toast-message"></span>
  </div>

  <!-- JavaScript -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Tab functionality
      const tabs = document.querySelectorAll('#order-tabs button');
      const tabContents = document.querySelectorAll('.tab-content');
      
      tabs.forEach(tab => {
        tab.addEventListener('click', function() {
          // Remove active state from all tabs
          tabs.forEach(t => {
            t.classList.remove('border-chopee-500', 'text-chopee-500');
            t.classList.add('border-transparent', 'text-gray-600', 'hover:border-gray-300', 'hover:text-gray-700');
          });
          
          // Add active state to clicked tab
          this.classList.remove('border-transparent', 'text-gray-600', 'hover:border-gray-300', 'hover:text-gray-700');
          this.classList.add('border-chopee-500', 'text-chopee-500');
          
          // Hide all tab contents
          tabContents.forEach(content => {
            content.classList.add('hidden');
            content.classList.remove('block');
          });
          
          // Show clicked tab content
          const targetContent = document.getElementById(this.dataset.target);
          if (targetContent) {
            targetContent.classList.remove('hidden');
            targetContent.classList.add('block');
          }
        });
      });
      
      // Display toast notification if message exists
      <?php if (isset($_SESSION['toast_message'])): ?>
        showToast("<?php echo $_SESSION['toast_message']; ?>");
        <?php unset($_SESSION['toast_message']); ?>
      <?php endif; ?>
      
      // Toast notification function
      function showToast(message) {
        const toast = document.getElementById('toast');
        const toastMessage = document.getElementById('toast-message');
        
        toastMessage.textContent = message;
        toast.classList.remove('opacity-0');
        toast.classList.add('opacity-100');
        
        setTimeout(() => {
          toast.classList.remove('opacity-100');
          toast.classList.add('opacity-0');
        }, 3000);
      }
    });
  </script>
</body>
</html>