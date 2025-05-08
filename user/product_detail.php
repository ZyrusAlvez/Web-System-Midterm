<?php
session_start();
include("../connections.php");

if (!isset($connections)) {
    die("Database connection error.");
}

// Check if user is logged in - using name directly from session
$user_name = "Guest";
$user_id = null;
if (isset($_SESSION['name'])) {
    $user_name = $_SESSION['name'];
    // Assuming you have user_id in session
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    }
}

// Handle checkout process
if (isset($_POST['action']) && $_POST['action'] === 'checkout') {
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
      $order_query = "INSERT INTO orders (user_id, product_id, quantity, status) VALUES (?, ?, ?, 'pending')";      $stmt = $connections->prepare($order_query);
      
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
    "sports & travel" => "Sports & Travel",
];

// Get product ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$product_id = $_GET['id'];
$selected_category = isset($_GET['category']) ? $_GET['category'] : 'all';

// Fetch product details
$product_query = "SELECT * FROM products WHERE id = ?";
$stmt = $connections->prepare($product_query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product_result = $stmt->get_result();

if ($product_result->num_rows == 0) {
    header("Location: index.php");
    exit;
}

$product = $product_result->fetch_assoc();

// Fetch user's cart items
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
  <title><?php echo $product['name']; ?> | Chopee</title>
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
          <?php if ($selected_category != 'all'): ?>
          <input type="hidden" name="category" value="<?php echo htmlspecialchars($selected_category); ?>">
          <?php endif; ?>
          <input type="text" name="search" class="w-full px-4 py-2 rounded border-2 border-chopee-500 text-sm outline-none transition-all duration-200" placeholder="Search products...">
          <button type="submit" class="absolute right-0 top-0 h-full bg-chopee-500 border-none text-white px-4 rounded-r cursor-pointer">
            <i class="fas fa-search"></i>
          </button>
        </form>
      </div>
      
      <div class="flex gap-4 items-center justify-center mt-3 md:mt-0">
        <button id="cart-button" class="flex flex-col items-center text-gray-600 no-underline text-xs hover:text-chopee-500 transition-all duration-200 border-none bg-transparent cursor-pointer relative">
          <i class="fas fa-shopping-cart text-lg mb-1"></i>
          <span>Cart</span>
          <?php if ($cart_count > 0): ?>
          <span class="absolute -top-1 -right-1 bg-chopee-500 text-white rounded-full text-xs w-5 h-5 flex items-center justify-center">
            <?php echo $cart_count; ?>
          </span>
          <?php endif; ?>
        </button>
        <a href="account.php" class="flex flex-col items-center text-gray-600 no-underline text-xs hover:text-chopee-500 transition-all duration-200">
          <i class="fas fa-user text-lg mb-1"></i>
          <span><?php echo htmlspecialchars($user_name); ?></span>
        </a>
      </div>
    </div>
  </header>

  <!-- Cart Drawer -->
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

  <!-- Main Content -->
  <div class="max-w-7xl mx-auto my-6 px-4">
    <!-- Breadcrumb -->
    <div class="flex items-center text-sm mb-4 text-gray-600">
      <a href="index.php" class="text-gray-600 no-underline hover:text-chopee-500 transition-colors duration-200">Home</a>
      <span class="mx-2 text-gray-400">/</span>
      <a href="index.php?category=<?php echo urlencode($selected_category); ?>" class="text-gray-600 no-underline hover:text-chopee-500 transition-colors duration-200">
        <?php echo isset($categories[$selected_category]) ? $categories[$selected_category] : 'Products'; ?>
      </a>
      <span class="mx-2 text-gray-400">/</span>
      <span><?php echo $product['name']; ?></span>
    </div>

    <!-- Back button -->
    <a href="index.php?category=<?php echo urlencode($selected_category); ?>" class="inline-flex items-center text-gray-600 no-underline mb-4 font-medium hover:text-chopee-500 transition-colors duration-200">
      <i class="fas fa-arrow-left mr-2"></i> Back to products
    </a>

    <!-- Product Detail -->
    <div class="bg-white rounded-lg overflow-hidden shadow flex flex-col md:flex-row">
      <div class="flex-1 min-w-[300px] max-w-[42%] p-6 flex items-center justify-center bg-gray-50">
        <img src="../<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" class="max-w-full max-h-full object-cover">
      </div>
      
      <div class="flex-1 p-6 flex flex-col">
        <h1 class="text-2xl font-semibold mb-2 text-gray-800"><?php echo $product['name']; ?></h1>
        
        <span class="inline-block bg-chopee-500 text-white py-1 px-3 rounded-full text-sm mb-4">
          <?php echo isset($categories[$product['category']]) ? $categories[$product['category']] : $product['category']; ?>
        </span>
        
        <div class="bg-chopee-50 p-4 rounded-lg mb-4">
          <div class="text-chopee-500 text-3xl font-bold">₱<?php echo number_format($product['price'], 2); ?></div>
        </div>
        
        <div class="mb-6">
          <div class="flex items-center mb-2 text-gray-600">
            <i class="fas fa-box mr-2 text-gray-400 w-5"></i>
            <span><?php echo $product['quantity']; ?> items in stock</span>
          </div>
          <div class="flex items-center mb-2 text-gray-600">
            <i class="fas fa-id-card mr-2 text-gray-400 w-5"></i>
            <span>Product ID: <?php echo $product['id']; ?></span>
          </div>
        </div>
        
        <div class="flex items-center mb-6">
          <div class="mr-4">
            <label for="product-quantity" class="block text-sm text-gray-600 mb-1">Quantity:</label>
            <div class="flex items-center border rounded">
              <button class="px-2 py-1 bg-gray-100" id="decrease-quantity">
                <i class="fas fa-minus text-xs"></i>
              </button>
              <input type="number" id="product-quantity" value="1" class="w-16 text-center border-none" min="1">
              <button class="px-2 py-1 bg-gray-100" id="increase-quantity">
                <i class="fas fa-plus text-xs"></i>
              </button>
            </div>
          </div>
        </div>
        
        <h2 class="text-lg font-semibold mb-1 text-gray-800">Product Description</h2>
        <div class="text-gray-600 leading-relaxed mb-3 whitespace-pre-line">
          <?php echo nl2br(htmlspecialchars($product['description'])); ?>
        </div>
        
        <div class="flex gap-4 mt-auto flex-col md:flex-row">
          <button id="add-to-cart" class="w-full bg-chopee-500 text-white py-3 px-6 rounded font-medium text-base cursor-pointer transition-all duration-300 hover:bg-chopee-600 flex-2">
            <i class="fas fa-shopping-cart"></i> Add to Cart
          </button>
        </div>
      </div>
    </div>

    <!-- Similar Products Section -->
    <?php
    // Fetch similar products based on category
    $similar_query = "SELECT * FROM products WHERE category = ? AND id != ? LIMIT 4";
    $stmt = $connections->prepare($similar_query);
    $stmt->bind_param("si", $product['category'], $product_id);
    $stmt->execute();
    $similar_result = $stmt->get_result();
    
    if ($similar_result->num_rows > 0) {
    ?>
    <div class="mt-8">
      <h2 class="text-xl font-semibold mb-4 text-gray-800">Similar Products</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        <?php
        while ($similar_product = $similar_result->fetch_assoc()) {
        ?>
        <a href="product_detail.php?id=<?php echo $similar_product['id']; ?>&category=<?php echo urlencode($selected_category); ?>" class="bg-white rounded-lg overflow-hidden shadow transition-all duration-200 hover:translate-y-[-4px] hover:shadow-lg cursor-pointer no-underline text-inherit block">
          <div class="relative pt-[100%] bg-gray-50 overflow-hidden">
            <span class="absolute top-2 left-2 bg-black/50 text-white py-0.5 px-2 rounded text-xs">#<?php echo $similar_product['id']; ?></span>
            <img src="../<?php echo $similar_product['image']; ?>" alt="<?php echo $similar_product['name']; ?>" class="absolute top-0 left-0 w-full h-full object-cover">
          </div>
          <div class="p-3">
            <h3 class="font-medium mb-2 text-gray-800 line-clamp-2 h-10 overflow-hidden"><?php echo $similar_product['name']; ?></h3>
            <div class="font-semibold text-chopee-500 text-base mb-1.5">₱<?php echo number_format($similar_product['price'], 2); ?></div>
            <div class="text-gray-600 text-sm">
              <span><i class="fas fa-box"></i> <?php echo $similar_product['quantity']; ?> in stock</span>
            </div>
          </div>
        </a>
        <?php } ?>
      </div>
    </div>
    <?php } ?>
  </div>
  
  <!-- Toast Notification -->
  <div id="toast" class="fixed bottom-4 right-4 bg-gray-800 text-white px-4 py-2 rounded shadow-lg transition-opacity duration-300 opacity-0 pointer-events-none z-50">
    <span id="toast-message"></span>
  </div>

  <!-- JavaScript -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {

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
        // Show an alert when there's not enough stock
        if (data.message && data.message.includes("Not enough stock")) {
            alert("Not enough stock for one or more products in your cart!");
        }
        showToast(data.message);
    }
          })
            .catch(error => {
                console.error('Error:', error);
                showToast('Failed to process checkout');
            });
        });
    }

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
      
      // Product quantity functionality
      const decreaseBtn = document.getElementById('decrease-quantity');
      const increaseBtn = document.getElementById('increase-quantity');
      const quantityInput = document.getElementById('product-quantity');
      
      decreaseBtn.addEventListener('click', function() {
        let value = parseInt(quantityInput.value);
        if (value > 1) {
          quantityInput.value = value - 1;
        }
      });
      
      increaseBtn.addEventListener('click', function() {
        let value = parseInt(quantityInput.value);
        quantityInput.value = value + 1;
      });
      
      quantityInput.addEventListener('change', function() {
        if (parseInt(this.value) < 1) {
          this.value = 1;
        }
      });
      
      // Add to cart functionality
      const addToCartBtn = document.getElementById('add-to-cart');
      
      addToCartBtn.addEventListener('click', function() {
        const quantity = parseInt(quantityInput.value);
        const productId = <?php echo $product_id; ?>;
        
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
              
              // If the cart drawer is currently open, update it
              if (cartDrawer.classList.contains('open')) {
                // Reload the page to update cart contents
                location.reload();
              } else {
                // Just reload the page to update cart count
                location.reload();
              }
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
</body>
</html>