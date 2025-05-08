<?php 
session_start();
include("../connections.php");

if (!isset($connections)) {
    die("Database connection error.");
}

// Check if user is logged in - using name directly from session
$user_name = "Guest";
if (isset($_SESSION['name'])) {
    $user_name = $_SESSION['name'];
}

// Define categories
$categories = [
    "all" => "All Categories",
    "cosmetics" => "Cosmetics",
    "men's apparel" => "Men's Apparel",
    "women's apparel" => "Women's Apparel",
    "groceries" => "Groceries",
    "mobile & gadgets" => "Mobile & Gadgets",
    "home appliances" => "Home Appliances",
    "health & personal care" => "Health & Personal Care",
    "sports & travel" => "Sports & Travel"
];

// Get selected category for filtering
$selected_category = isset($_GET['category']) ? urldecode($_GET['category']) : 'all';

// Get search query if it exists
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build the SQL query based on search and category
if ($search_query) {
    // If there's a search query
    if ($selected_category == 'all') {
        // Search across all categories
        $products_query = "SELECT * FROM products WHERE name LIKE ? OR description LIKE ? ORDER BY id DESC";
        $stmt = $connections->prepare($products_query);
        $search_term = "%$search_query%";
        $stmt->bind_param("ss", $search_term, $search_term);
    } else {
        // Search within selected category
        $products_query = "SELECT * FROM products WHERE (name LIKE ? OR description LIKE ?) AND category = ? ORDER BY id DESC";
        $stmt = $connections->prepare($products_query);
        $search_term = "%$search_query%";
        $stmt->bind_param("sss", $search_term, $search_term, $selected_category);
    }
    $stmt->execute();
    $products_result = $stmt->get_result();
} else {
    // No search query, just filter by category
    if ($selected_category == 'all') {
        $products_query = "SELECT * FROM products ORDER BY id DESC";
        $products_result = $connections->query($products_query);
    } else {
        $products_query = "SELECT * FROM products WHERE category = ? ORDER BY id DESC";
        $stmt = $connections->prepare($products_query);
        $stmt->bind_param("s", $selected_category);
        $stmt->execute();
        $products_result = $stmt->get_result();
    }
}
?>
<?php
// ==================== PHP SERVER-SIDE CART FUNCTIONS ====================
// Add these to the top of your PHP files where you need cart functionality

// Check if user is logged in and get user information
$user_name = "Guest";
$user_id = null;
if (isset($_SESSION['name'])) {
    $user_name = $_SESSION['name'];
    // Assuming you have user_id in session
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    }
}

// Handle cart actions
if (isset($_POST['action'])) {
    // Make sure user is logged in
    if ($user_id === null) {
        echo json_encode(['success' => false, 'message' => 'Please login to add items to cart']);
        exit;
    }

    $action = $_POST['action'];
    
    if ($action === 'add_to_cart') {
        $product_id = $_POST['product_id'];
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
        
        // Check if product already in cart
        $check_query = "SELECT * FROM cart WHERE user_id = ? AND product_id = ?";
        $stmt = $connections->prepare($check_query);
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing cart item
            $cart_item = $result->fetch_assoc();
            $new_quantity = $cart_item['quantity'] + $quantity;
            
            $update_query = "UPDATE cart SET quantity = ? WHERE cart_id = ?";
            $stmt = $connections->prepare($update_query);
            $stmt->bind_param("ii", $new_quantity, $cart_item['cart_id']);
            $stmt->execute();
        } else {
            // Insert new cart item
            $insert_query = "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)";
            $stmt = $connections->prepare($insert_query);
            $stmt->bind_param("iii", $user_id, $product_id, $quantity);
            $stmt->execute();
        }
        
        echo json_encode(['success' => true, 'message' => 'Item added to cart']);
        exit;
    }
    
    if ($action === 'update_quantity') {
        $cart_id = $_POST['cart_id'];
        $quantity = intval($_POST['quantity']);
        
        if ($quantity <= 0) {
            // Remove item if quantity is 0 or less
            $delete_query = "DELETE FROM cart WHERE cart_id = ? AND user_id = ?";
            $stmt = $connections->prepare($delete_query);
            $stmt->bind_param("ii", $cart_id, $user_id);
            $stmt->execute();
        } else {
            // Update quantity
            $update_query = "UPDATE cart SET quantity = ? WHERE cart_id = ? AND user_id = ?";
            $stmt = $connections->prepare($update_query);
            $stmt->bind_param("iii", $quantity, $cart_id, $user_id);
            $stmt->execute();
        }
        
        echo json_encode(['success' => true, 'message' => 'Cart updated']);
        exit;
    }
    
    if ($action === 'remove_item') {
        $cart_id = $_POST['cart_id'];
        
        $delete_query = "DELETE FROM cart WHERE cart_id = ? AND user_id = ?";
        $stmt = $connections->prepare($delete_query);
        $stmt->bind_param("ii", $cart_id, $user_id);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Item removed from cart']);
        exit;
    }
    
    if ($action === 'checkout') {
        // Make sure user is logged in
        if ($user_id === null) {
            echo json_encode(['success' => false, 'message' => 'Please login to checkout']);
            exit;
        }

        // Fetch user's cart items
        $cart_query = "SELECT product_id, quantity FROM cart WHERE user_id = ?";
        $stmt = $connections->prepare($cart_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $cart_result = $stmt->get_result();
        
        if ($cart_result->num_rows == 0) {
            echo json_encode(['success' => false, 'message' => 'Your cart is empty']);
            exit;
        }
        
        // Begin transaction
        $connections->begin_transaction();
        
        try {
            // Create orders for each cart item
            $order_query = "INSERT INTO orders (user_id, product_id, quantity, status) VALUES (?, ?, ?, 'pending')";
            $stmt = $connections->prepare($order_query);
            
            while ($cart_item = $cart_result->fetch_assoc()) {
                $product_id = $cart_item['product_id'];
                $quantity = $cart_item['quantity'];
                
                // Check if product has enough stock
                $product_query = "SELECT quantity FROM products WHERE id = ?";
                $product_stmt = $connections->prepare($product_query);
                $product_stmt->bind_param("i", $product_id);
                $product_stmt->execute();
                $product_result = $product_stmt->get_result();
                $product = $product_result->fetch_assoc();
                
                if ($product['quantity'] < $quantity) {
                    throw new Exception("Not enough stock for one or more products");
                }
                
                // Update product quantity
                $new_quantity = $product['quantity'] - $quantity;
                $update_query = "UPDATE products SET quantity = ? WHERE id = ?";
                $update_stmt = $connections->prepare($update_query);
                $update_stmt->bind_param("ii", $new_quantity, $product_id);
                $update_stmt->execute();
                
                // Insert order entry with quantity
                $stmt->bind_param("iii", $user_id, $product_id, $quantity);
                $stmt->execute();
            }
            
            // Clear the cart
            $clear_cart_query = "DELETE FROM cart WHERE user_id = ?";
            $clear_stmt = $connections->prepare($clear_cart_query);
            $clear_stmt->bind_param("i", $user_id);
            $clear_stmt->execute();
            
            // Commit the transaction
            $connections->commit();
            
            echo json_encode(['success' => true, 'message' => 'Order placed successfully']);
            exit;
        } catch (Exception $e) {
            // Rollback the transaction if an error occurs
            $connections->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
}

// Fetch user's cart items - Add this where you need to display cart data
$cart_items = [];
$cart_total = 0;
$cart_count = 0;

if ($user_id !== null) {
    $cart_query = "SELECT c.cart_id, c.product_id, c.quantity, p.name, p.price, p.image 
                  FROM cart c 
                  JOIN products p ON c.product_id = p.id 
                  WHERE c.user_id = ?";
    $stmt = $connections->prepare($cart_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $cart_result = $stmt->get_result();
    
    while ($cart_item = $cart_result->fetch_assoc()) {
        $cart_items[] = $cart_item;
        $cart_total += $cart_item['price'] * $cart_item['quantity'];
        $cart_count += $cart_item['quantity'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Chopee - Online Shopping</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            'chopee': '#ee4d2d',
          }
        }
      }
    }
  </script>
  <style>
  /* Cart Drawer Styles */
  .cart-drawer {
    position: fixed;
    top: 0;
    right: -400px;
    width: 100%;
    max-width: 400px;
    height: 100vh;
    background-color: white;
    z-index: 1000;
    transition: right 0.3s ease-in-out;
    box-shadow: -2px 0 10px rgba(0,0,0,0.1);
    overflow-y: auto;
  }
  
  .cart-drawer.open {
    right: 0;
  }
  
  .overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    z-index: 999;
    display: none;
  }
  
  .overlay.open {
    display: block;
  }
  
  .cart-item-enter {
    opacity: 0;
    transform: translateX(20px);
  }
  
  .cart-item-enter-active {
    opacity: 1;
    transform: translateX(0);
    transition: opacity 300ms, transform 300ms;
  }
  
  .cart-item-exit {
    opacity: 1;
  }
  
  .cart-item-exit-active {
    opacity: 0;
    transform: translateX(20px);
    transition: opacity 300ms, transform 300ms;
  }
</style>
</head>
<body class="font-sans m-0 p-0 bg-gray-100 text-gray-800">
  <!-- Header -->
  <header class="bg-white shadow-md py-3 px-6 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto flex justify-between items-center md:flex-row flex-col md:items-stretch">
      <div class="flex items-center justify-center md:mb-0 mb-3">
        <a href="index.php" class="text-decoration-none flex items-center">
          <img src="../assets/logo_no_bg.webp" alt="Chopee" class="w-10 h-10">
          <span class="text-chopee font-semibold text-2xl ml-2">Chopee</span>
        </a>
      </div>
      
      <div class="flex-1 max-w-xl mx-0 md:mx-6 relative md:my-0 my-3 w-full">
        <form action="index.php" method="GET">
          <?php if ($selected_category != 'all'): ?>
          <input type="hidden" name="category" value="<?php echo htmlspecialchars($selected_category); ?>">
          <?php endif; ?>
          <input type="text" name="search" class="w-full py-2.5 px-4 rounded border-2 border-chopee text-sm outline-none transition-all duration-200" 
                 placeholder="Search products..." value="<?php echo htmlspecialchars($search_query); ?>">
          <button type="submit" class="absolute right-0 top-0 h-full bg-chopee border-none text-white px-4 rounded-r cursor-pointer">
            <i class="fas fa-search"></i>
          </button>
        </form>
      </div>
      
      <div class="flex gap-4 items-center justify-center">
        <a href="#" class="flex flex-col items-center text-gray-600 no-underline text-xs transition-all duration-200 hover:text-chopee">
        <button id="cart-button" class="flex flex-col items-center text-gray-600 no-underline text-xs hover:text-chopee-500 transition-all duration-200 border-none bg-transparent cursor-pointer relative">
        <i class="fas fa-shopping-cart text-lg mb-1"></i>
          <span>Cart</span>
          <?php if ($cart_count > 0): ?>
          <span class="absolute -top-1 -right-1 bg-chopee-500 text-white rounded-full text-xs w-5 h-5 flex items-center justify-center">
            <?php echo $cart_count; ?>
          </span>
          <?php endif; ?>
      </button>
        </a>
        <a href="account.php" class="flex flex-col items-center text-gray-600 no-underline text-xs transition-all duration-200 hover:text-chopee">
          <i class="fas fa-user text-lg mb-1"></i>
          <span><?php echo htmlspecialchars($user_name); ?></span>
        </a>
      </div>
    </div>
  </header>

  <!-- Main Content -->
  <div class="max-w-7xl mx-auto my-6 px-4">
    <h1 class="mb-4 text-xl font-semibold text-gray-800">
      <?php 
      if ($search_query) {
        echo 'Search results for "' . htmlspecialchars($search_query) . '"';
        if ($selected_category != 'all') {
          echo ' in ' . (isset($categories[$selected_category]) ? $categories[$selected_category] : $selected_category);
        }
      } else {
        if ($selected_category == 'all') {
          echo "All Products";
        } else {
          echo isset($categories[$selected_category]) ? $categories[$selected_category] : $selected_category;
        }
      }
      ?>
    </h1>

    <!-- Category Filters -->
    <div class="flex overflow-x-auto gap-2 pb-2 mb-4 scrollbar-hide">
      <?php foreach($categories as $key => $value): ?>
        <a href="index.php?category=<?php echo urlencode($key); ?><?php echo $search_query ? '&search='.urlencode($search_query) : ''; ?>" 
           class="whitespace-nowrap py-2 px-4 rounded-full text-sm cursor-pointer transition-all duration-200 no-underline
           <?php echo ($selected_category == $key) 
             ? 'bg-chopee text-white font-medium' 
             : 'bg-gray-100 text-gray-800 border border-gray-300 hover:bg-gray-200'; ?>">
          <?php echo $value; ?>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Products Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
      <?php 
      if ($products_result && $products_result->num_rows > 0) {
        while($product = $products_result->fetch_assoc()) {
          displayProductCard($product, $categories, $selected_category);
        }
      } else {
      ?>
        <div class="col-span-full py-8 text-center text-gray-500 bg-white rounded-lg shadow-sm">
          <i class="fas fa-search text-5xl text-gray-300 mb-4 block"></i>
          <p>
            <?php 
            if ($search_query) {
              echo 'No products found matching "' . htmlspecialchars($search_query) . '"';
              if ($selected_category != 'all') {
                echo ' in ' . (isset($categories[$selected_category]) ? $categories[$selected_category] : $selected_category);
              }
            } else {
              echo 'No products found in this category';
            }
            ?>
          </p>
        </div>
      <?php } ?>
    </div>
  </div>

  <div class="overlay" id="overlay"></div>
<div class="cart-drawer" id="cart-drawer">
  <div class="p-4 border-b">
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Your Cart</h2>
      <button id="close-cart" class="text-gray-600 hover:text-chopee-500 transition-colors duration-200">
        <i class="fas fa-times text-xl"></i>
      </button>
    </div>
  </div>
  
  <div class="p-4">
    <?php if (empty($cart_items)): ?>
      <div class="text-center py-8">
        <i class="fas fa-shopping-cart text-gray-300 text-5xl mb-4"></i>
        <p class="text-gray-500">Your cart is empty</p>
        <a href="index.php" class="inline-block mt-4 bg-chopee-500 text-white py-2 px-4 rounded">Start Shopping</a>
      </div>
    <?php else: ?>
      <div class="mb-4">
        <div class="space-y-4" id="cart-items-container">
          <?php foreach ($cart_items as $item): ?>
            <div class="flex border-b pb-4 cart-item" data-cart-id="<?php echo $item['cart_id']; ?>">
              <a href="product_detail.php?id=<?php echo $item['product_id']; ?>" class="w-20 h-20 bg-gray-100 rounded overflow-hidden block">
                <img src="../<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>" class="w-full h-full object-cover">
              </a>
              <div class="ml-4 flex-1">
                <a href="product_detail.php?id=<?php echo $item['product_id']; ?>" class="font-medium text-sm mb-1 line-clamp-2 block hover:text-chopee-500 transition-colors duration-200"><?php echo $item['name']; ?></a>
                <div class="text-chopee-500 font-semibold">₱<?php echo number_format($item['price'], 2); ?></div>
                <div class="flex items-center justify-between mt-2">
                  <div class="flex items-center border rounded">
                    <button class="px-2 py-1 bg-gray-100 quantity-btn" data-action="decrease">
                      <i class="fas fa-minus text-xs"></i>
                    </button>
                    <input type="number" value="<?php echo $item['quantity']; ?>" class="w-10 text-center border-none quantity-input" min="1">
                    <button class="px-2 py-1 bg-gray-100 quantity-btn" data-action="increase">
                      <i class="fas fa-plus text-xs"></i>
                    </button>
                  </div>
                  <button class="text-gray-400 hover:text-red-500 transition-colors duration-200 remove-item">
                    <i class="fas fa-trash-alt"></i>
                  </button>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      
      <div class="border-t pt-4 mt-4">
        <div class="flex justify-between items-center mb-4">
          <span class="text-gray-600">Subtotal</span>
          <span class="font-semibold" id="cart-subtotal">₱<?php echo number_format($cart_total, 2); ?></span>
        </div>
        <div class="flex justify-between items-center mb-4">
          <span class="text-gray-600">Shipping</span>
          <span class="font-semibold" id="shipping-cost">₱50.00</span>
        </div>
        <div class="flex justify-between items-center mb-4 text-lg">
          <span class="font-semibold">Total</span>
          <span class="font-semibold text-chopee-500" id="cart-total">₱<?php echo number_format($cart_total + 50, 2); ?></span>
        </div>
        <button id="checkout" class="w-full bg-chopee-500 text-white py-3 px-6 rounded font-medium text-base cursor-pointer transition-all duration-300 hover:bg-chopee-600">
          Checkout
        </button>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Toast Notification -->
<div id="toast" class="fixed bottom-4 right-4 bg-gray-800 text-white px-4 py-2 rounded shadow-lg transition-opacity duration-300 opacity-0 pointer-events-none z-50">
  <span id="toast-message"></span>
</div>

  <script>
  document.addEventListener('DOMContentLoaded', function() {
    // Cart drawer functionality
    const cartButton = document.getElementById('cart-button');
    const cartDrawer = document.getElementById('cart-drawer');
    const closeCart = document.getElementById('close-cart');
    const overlay = document.getElementById('overlay');
    
    cartButton.addEventListener('click', function() {
      cartDrawer.classList.add('open');
      overlay.classList.add('open');
      document.body.style.overflow = 'hidden';
    });
    
    closeCart.addEventListener('click', function() {
      cartDrawer.classList.remove('open');
      overlay.classList.remove('open');
      document.body.style.overflow = 'auto';
    });
    
    overlay.addEventListener('click', function() {
      cartDrawer.classList.remove('open');
      overlay.classList.remove('open');
      document.body.style.overflow = 'auto';
    });
    
    // Add checkout button functionality
    const checkoutButton = document.querySelector('#checkout');
    
    if (checkoutButton) {
      checkoutButton.addEventListener('click', function() {
        // Send AJAX request to process checkout
        fetch(window.location.href, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: 'action=checkout'
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            showToast(data.message);
            
            // Get the parent element that contains the cart items and checkout section
            const cartContentArea = document.getElementById('cart-items-container').parentElement.parentElement;
            
            // Replace the entire content with the empty cart message
            cartContentArea.innerHTML = `
              <div class="text-center py-8">
                <i class="fas fa-shopping-cart text-gray-300 text-5xl mb-4"></i>
                <p class="text-gray-500">Your cart is empty</p>
                <a href="index.php" class="inline-block mt-4 bg-chopee-500 text-white py-2 px-4 rounded">Start Shopping</a>
              </div>
            `;
            
            // Reset cart count badge
            const cartButton = document.getElementById('cart-button');
            const countBadge = cartButton.querySelector('span.bg-chopee-500');
            if (countBadge) countBadge.remove();
          } else {
            showToast(data.message);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          showToast('Failed to process checkout');
        });
      });
    }
    
    // Cart item quantity update
    document.querySelectorAll('.cart-item').forEach(item => {
      const cartId = item.dataset.cartId;
      const quantityBtns = item.querySelectorAll('.quantity-btn');
      const quantityInput = item.querySelector('.quantity-input');
      const removeBtn = item.querySelector('.remove-item');
      
      quantityBtns.forEach(btn => {
        btn.addEventListener('click', function() {
          let value = parseInt(quantityInput.value);
          if (this.dataset.action === 'decrease') {
            value = Math.max(1, value - 1);
          } else {
            value += 1;
          }
          quantityInput.value = value;
          
          // Update cart item quantity
          updateCartItemQuantity(cartId, value);
        });
      });
      
      quantityInput.addEventListener('change', function() {
        let value = parseInt(this.value);
        if (value < 1) {
          value = 1;
          this.value = value;
        }
        
        // Update cart item quantity
        updateCartItemQuantity(cartId, value);
      });
      
      removeBtn.addEventListener('click', function() {
        // Remove item from cart
        removeCartItem(cartId, item);
      });
    });
    
    // Function to update cart item quantity
    function updateCartItemQuantity(cartId, quantity) {
      fetch(window.location.href, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=update_quantity&cart_id=${cartId}&quantity=${quantity}`
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Reload the page to update cart contents
          location.reload();
        } else {
          showToast(data.message);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showToast('Failed to update cart');
      });
    }
    
    // Function to remove cart item
    function removeCartItem(cartId, itemElement) {
      fetch(window.location.href, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=remove_item&cart_id=${cartId}`
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Reload the page to update cart contents
          location.reload();
        } else {
          showToast(data.message);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showToast('Failed to remove item');
      });
    }
    
    // Toast notification function
    function showToast(message) {
      const toast = document.getElementById('toast');
      const toastMessage = document.getElementById('toast-message');
      
      toastMessage.textContent = message;
      toast.classList.add('opacity-100');
      
      setTimeout(() => {
        toast.classList.remove('opacity-100');
      }, 3000);
    }
  });
</script>

<!-- ==================== SQL DATABASE TABLES NEEDED ==================== -->
<!--
CREATE TABLE `cart` (
  `cart_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`cart_id`),
  KEY `user_id` (`user_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `status` enum('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`order_id`),
  KEY `user_id` (`user_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-->

<!-- ==================== ADD PRODUCT TO CART BUTTON ==================== -->
<!-- Add this where you want to display an "Add to Cart" button for a product -->

<button id="add-to-cart" class="bg-chopee-500 text-white py-3 px-6 rounded font-medium text-base cursor-pointer transition-all duration-300 hover:bg-chopee-600">
  <i class="fas fa-shopping-cart"></i> Add to Cart
</button>

<script>
  // Add to cart functionality
  document.getElementById('add-to-cart').addEventListener('click', function() {
    const productId = <?php echo $product_id; ?>; // Make sure $product_id is defined
    const quantity = 1; // Or get from an input field
    
    <?php if ($user_id === null): ?>
      showToast('Please login to add items to cart');
    <?php else: ?>
      // Send AJAX request to add item to cart
      fetch(window.location.href, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=add_to_cart&product_id=${productId}&quantity=${quantity}`
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          showToast('Item added to cart');
          // Reload the page to update cart count
          location.reload();
        } else {
          showToast(data.message);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showToast('Failed to add item to cart');
      });
    <?php endif; ?>
  });
</script>
</body>
</html>

<?php
// Helper function to display product card
function displayProductCard($product, $categories, $selected_category) {
  $category_display_name = isset($categories[$product['category']]) ? $categories[$product['category']] : $product['category'];
?>
  <a href="product_detail.php?id=<?php echo $product['id']; ?>&category=<?php echo urlencode($selected_category); ?>" 
     class="bg-white rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-all duration-200 hover:-translate-y-1 cursor-pointer no-underline text-inherit block" 
     id="product-<?php echo $product['id']; ?>">
    <div class="relative pt-[100%] bg-gray-50 overflow-hidden">
      <span class="absolute top-2 left-2 bg-black/50 text-white py-0.5 px-2 rounded text-xs">#<?php echo $product['id']; ?></span>
      <?php if ($selected_category == 'all'): ?>
        <span class="absolute bottom-2 right-2 bg-chopee/80 text-white py-0.5 px-2 rounded text-xs max-w-[70%] whitespace-nowrap overflow-hidden text-ellipsis"><?php echo $category_display_name; ?></span>
      <?php endif; ?>
      <img src="../<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" class="absolute top-0 left-0 w-full h-full object-cover">
    </div>
    <div class="p-3">
      <h3 class="font-medium mb-2 text-gray-800 line-clamp-2"><?php echo $product['name']; ?></h3>
      <div class="font-semibold text-chopee text-base mb-1.5">₱<?php echo number_format($product['price'], 2); ?></div>
      <div class="flex justify-between text-gray-500 text-xs">
        <span><i class="fas fa-box"></i> <?php echo $product['quantity']; ?> in stock</span>
      </div>
      <div class="text-gray-600 text-xs mt-2 overflow-hidden text-ellipsis whitespace-nowrap" title="<?php echo htmlspecialchars($product['description']); ?>">
        <?php echo htmlspecialchars(substr($product['description'], 0, 50)) . (strlen($product['description']) > 50 ? '...' : ''); ?>
      </div>
    </div>
  </a>
<?php
}
?>