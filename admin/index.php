<?php 
session_start();
include("../connections.php");

if (!isset($connections)) {
    die("Database connection error.");
}

// Define categories
$categories = [
    "all" => "All Categories",
    "men's apparel" => "Men's Apparel",
    "women's apparel" => "Women's Apparel",
    "groceries" => "Groceries",
    "mobile & gadgets" => "Mobile & Gadgets",
    "home appliances" => "Home Appliances",
    "health & personal care" => "Health & Personal Care",
    "sports & travel" => "Sports & Travel"
];

// Get selected category for filtering - add urldecode to properly handle special characters
$selected_category = isset($_GET['category']) ? urldecode($_GET['category']) : 'all';

// Process delete action
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // First, get the image path to delete the file
    $image_query = "SELECT image FROM products WHERE id = ?";
    $stmt = $connections->prepare($image_query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $image_path = "../" . $row['image'];
        // Delete the image file if it exists
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    
    // Delete the product from database
    $delete_query = "DELETE FROM products WHERE id = ?";
    $stmt = $connections->prepare($delete_query);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $success_message = "Product deleted successfully!";
    } else {
        $error_message = "Error deleting product: " . $stmt->error;
    }
    
    $stmt->close();
    
    // Redirect to remove the action from URL and maintain category filter - use urlencode for the redirect
    header("Location: index.php?category=" . urlencode($selected_category));
    exit();
}

// Process edit action
if (isset($_POST['edit_product'])) {
    $id = $_POST['edit_id'];
    $name = $_POST['edit_name'];
    $price = $_POST['edit_price'];
    $quantity = $_POST['edit_quantity'];
    $description = $_POST['edit_description'];
    $category = $_POST['edit_category'];
    
    // Check if a new image was uploaded
    if (!empty($_FILES['edit_image']['name'])) {
        // Handle file upload
        $target_dir = "../uploads/";
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES["edit_image"]["name"], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        // Check if image file is valid
        $valid_extensions = array("jpg", "jpeg", "png", "gif");
        
        if (in_array($file_extension, $valid_extensions)) {
            // Get old image path to delete
            $get_old_image = "SELECT image FROM products WHERE id = ?";
            $stmt = $connections->prepare($get_old_image);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $old_image_path = "../" . $row['image'];
                // Delete the old image file if it exists
                if (file_exists($old_image_path)) {
                    unlink($old_image_path);
                }
            }
            
            // Upload new file
            if (move_uploaded_file($_FILES["edit_image"]["tmp_name"], $target_file)) {
                // File uploaded successfully, now update database with new image
                $image_path = "uploads/" . $new_filename;
                
                // Prepare SQL statement with correct parameter order
                $stmt = $connections->prepare("UPDATE products SET name = ?, price = ?, quantity = ?, description = ?, image = ?, category = ? WHERE id = ?");
                
                // Bind parameters - Fix the parameter order to match the SQL statement
                $stmt->bind_param("sdisssi", $name, $price, $quantity, $description, $image_path, $category, $id);
            } else {
                $error_message = "Error uploading file.";
            }
        } else {
            $error_message = "Invalid file format. Only JPG, JPEG, PNG, and GIF are allowed.";
        }
    } else {
        // No new image, just update the other fields
        $stmt = $connections->prepare("UPDATE products SET name = ?, price = ?, quantity = ?, description = ?, category = ? WHERE id = ?");
        $stmt->bind_param("sdissi", $name, $price, $quantity, $description, $category, $id);
    }
    
    // Execute query
    if (!isset($error_message) && $stmt->execute()) {
        // Success message
        $success_message = "Product updated successfully!";
    } else if (!isset($error_message)) {
        // Error message
        $error_message = "Error updating product: " . $stmt->error;
    }
    
    // Close statement
    $stmt->close();
}

// Process add product form submission
if (isset($_POST['add_product'])) {
    // Get form data
    $name = $_POST['name'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    
    // Handle file upload
    $target_dir = "../uploads/";
    
    // Create directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    // Check if image file is valid
    $valid_extensions = array("jpg", "jpeg", "png", "gif");
    
    if (in_array($file_extension, $valid_extensions)) {
        // Upload file
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            // File uploaded successfully, now insert into database
            $image_path = "uploads/" . $new_filename;
            
            // Prepare SQL statement
            $stmt = $connections->prepare("INSERT INTO products (name, price, quantity, description, image, category) VALUES (?, ?, ?, ?, ?, ?)");
            
            // Bind parameters
            $stmt->bind_param("sdisss", $name, $price, $quantity, $description, $image_path, $category);
            
            // Execute query
            if ($stmt->execute()) {
                // Success message
                $success_message = "Product added successfully!";
            } else {
                // Error message
                $error_message = "Error adding product: " . $stmt->error;
            }
            
            // Close statement
            $stmt->close();
        } else {
            $error_message = "Error uploading file.";
        }
    } else {
        $error_message = "Invalid file format. Only JPG, JPEG, PNG, and GIF are allowed.";
    }
}

// Fetch all products for display with category filter
if ($selected_category == 'all') {
    $products_query = "SELECT * FROM products ORDER BY category, id DESC";
} else {
    $products_query = "SELECT * FROM products WHERE category = ? ORDER BY id DESC";
}

if ($selected_category == 'all') {
    $products_result = $connections->query($products_query);
} else {
    $stmt = $connections->prepare($products_query);
    $stmt->bind_param("s", $selected_category);
    $stmt->execute();
    $products_result = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
  <style>
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
        <a href="index.php" class="flex items-center gap-3 p-3 rounded-lg text-white font-medium bg-[#ff6347] hover:bg-[#ff7e6b] transition-colors">
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
        <a href="index.php" class="flex items-center gap-3 p-3 rounded-lg text-white font-medium bg-[#ff6347] hover:bg-[#ff7e6b] transition-colors">
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
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-2xl font-bold">Products Management</h2>
          <button id="showAddForm" class="px-4 py-2 bg-[#ee4d2d] text-white rounded flex items-center">
            <i class="fa-solid fa-plus mr-2"></i> Add New Product
          </button>
        </div>

        <!-- Add Product Form (initially hidden) -->
        <div id="addFormContainer" class="add-form-container bg-white p-6 rounded shadow-md mb-8">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold">Add New Product</h3>
            <button id="hideAddForm" class="text-gray-500 hover:text-gray-700">
              <i class="fa-solid fa-times"></i>
            </button>
          </div>
          
          <form action="" method="POST" enctype="multipart/form-data" id="addProductForm">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="mb-4">
                <label for="name" class="block mb-2">Product Name:</label>
                <input type="text" name="name" required class="border p-2 rounded w-full">
              </div>
              <div class="mb-4">
                <label for="price" class="block mb-2">Price:</label>
                <input type="number" step="0.01" name="price" required class="border p-2 rounded w-full">
              </div>
              <div class="mb-4">
                <label for="quantity" class="block mb-2">Quantity:</label>
                <input type="number" name="quantity" required class="border p-2 rounded w-full">
              </div>
              <div class="mb-4">
                <label for="category" class="block mb-2">Category:</label>
                <select name="category" required class="border p-2 rounded w-full">
                  <?php 
                  // Skip the "all" option when adding a product
                  foreach($categories as $key => $value) {
                    if($key != 'all') {
                      echo "<option value=\"$key\">$value</option>";
                    }
                  }
                  ?>
                </select>
              </div>
              <div class="mb-4 md:col-span-2">
                <label for="description" class="block mb-2">Description:</label>
                <textarea name="description" required class="border p-2 rounded w-full" rows="4"></textarea>
              </div>
              <div class="mb-4 md:col-span-2">
                <label for="image" class="block mb-2">Product Image:</label>
                <input type="file" name="image" accept="image/*" required class="border p-2 rounded w-full">
              </div>
            </div>
            <div class="flex justify-end gap-2 mt-4">
              <button type="button" id="cancelAddForm" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded">
                Cancel
              </button>
              <button type="submit" name="add_product" class="px-4 py-2 bg-[#ee4d2d] text-white rounded">
                Add Product
              </button>
            </div>
          </form>
        </div>

        <!-- Category Filters - Modified to use URL encoding -->
        <div class="category-filters mb-6">
          <?php foreach($categories as $key => $value): ?>
            <a href="index.php?category=<?php echo urlencode($key); ?>" 
               class="category-filter <?php echo ($selected_category == $key) ? 'active' : ''; ?>">
              <?php echo $value; ?>
            </a>
          <?php endforeach; ?>
        </div>

        <!-- Products Table -->
        <div class="bg-white rounded shadow-md overflow-hidden">
          <div class="grid grid-cols-11 gap-2 bg-[#ee4d2d] p-4 font-semibold text-white text-sm uppercase text-center">
            <div class="col-span-1">ID</div>
            <div class="col-span-1">Image</div>
            <div class="col-span-2">Name</div>
            <div class="col-span-1">Price</div>
            <div class="col-span-1">Qty</div>
            <div class="col-span-3">Description</div>
            <div class="col-span-2">Actions</div>
          </div>
          
          <!-- Grid Content -->
          <?php 
          if ($products_result && $products_result->num_rows > 0) {
            $current_category = '';
            
            // For 'all' view, group by category
            if ($selected_category == 'all') {
              while($product = $products_result->fetch_assoc()) {

                
                // Display product row
                displayProductRow($product, $categories);
              }
            } else {
              // Just show products for the selected category
              while($product = $products_result->fetch_assoc()) {
                displayProductRow($product, $categories);
              }
            }
          } else {
          ?>
            <div class="p-8 text-center text-gray-500">No products found</div>
          <?php } ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div id="deleteModal" class="modal-overlay">
    <div class="modal-container p-6">
      <div class="flex flex-col items-center text-center">
        <div class="text-red-500 mb-4">
          <i class="fa-solid fa-circle-exclamation text-5xl"></i>
        </div>
        <h3 class="text-xl font-bold mb-2">Confirm Delete</h3>
        <p class="text-gray-600 mb-6">Are you sure you want to delete <span id="deleteProductName" class="font-semibold"></span>? This action cannot be undone.</p>
        <div class="flex gap-3 w-full">
          <button id="cancelDelete" class="flex-1 py-2 px-4 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded">
            Cancel
          </button>
          <a id="confirmDelete" href="#" class="flex-1 py-2 px-4 bg-red-500 hover:bg-red-600 text-white rounded text-center">
            Delete
          </a>
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
  <script>
    // Configure toastr options
    toastr.options = {
      closeButton: true,
      progressBar: true,
      positionClass: "toast-top-right",
      timeOut: 3000,
      extendedTimeOut: 1000,
      showEasing: "swing",
      hideEasing: "linear",
      showMethod: "fadeIn",
      hideMethod: "fadeOut"
    };
    
    <?php if(isset($success_message)): ?>
    toastr.success('<?php echo $success_message; ?>');
    <?php endif; ?>
    
    <?php if(isset($error_message)): ?>
    toastr.error('<?php echo $error_message; ?>');
    <?php endif; ?>
    
    $(document).ready(function() {
      const deleteModal = document.getElementById('deleteModal');
      const confirmDeleteBtn = document.getElementById('confirmDelete');
      const cancelDeleteBtn = document.getElementById('cancelDelete');
      const deleteProductNameSpan = document.getElementById('deleteProductName');
      
      // Show add form
      $('#showAddForm').click(function() {
        $('#addFormContainer').slideDown();
      });
      
      // Hide add form
      $('#hideAddForm, #cancelAddForm').click(function() {
        $('#addFormContainer').slideUp();
        // Optional: Reset the form
        $('#addProductForm')[0].reset();
      });
      
      // Show edit form
      $('.edit-btn').click(function() {
        var productId = $(this).data('id');
        $('#edit-form-' + productId).slideDown();
      });
      
      // Hide edit form
      $('.cancel-edit-btn').click(function() {
        var productId = $(this).data('id');
        $('#edit-form-' + productId).slideUp();
      });
      
      // Delete confirmation modal
      $('.delete-btn').click(function() {
        const productId = $(this).data('id');
        const productName = $(this).data('name');
        
        // Update modal with product details
        deleteProductNameSpan.textContent = productName;
        // Use urlencode for the category parameter in the URL
        confirmDeleteBtn.href = 'index.php?action=delete&id=' + productId + '&category=<?php echo urlencode($selected_category); ?>';
        
        // Show modal with fade effect
        deleteModal.style.display = 'flex';
        setTimeout(() => {
          deleteModal.style.opacity = '1';
        }, 10);
      });
      
      // Hide modal when cancel is clicked
      cancelDeleteBtn.addEventListener('click', function() {
        deleteModal.style.display = 'none';
      });
      
      // Close modal when clicking outside
      deleteModal.addEventListener('click', function(e) {
        if (e.target === deleteModal) {
          deleteModal.style.display = 'none';
        }
      });
    });
  </script>
</body>
</html>

<?php
// Helper function to display product row and edit form
function displayProductRow($product, $categories) {
?>
  <div class="grid grid-cols-11 gap-2 p-4 items-center border-b border-gray-200 hover:bg-gray-50 product-row" id="product-<?php echo $product['id']; ?>">
    <div class="col-span-1 text-center"><?php echo $product['id']; ?></div>
    <div class="col-span-1">
      <img src="../<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" class="w-16 h-16 object-cover rounded">
    </div>
    <div class="col-span-2 font-medium"><?php echo $product['name']; ?></div>
    <div class="col-span-1 text-center">₱<?php echo number_format($product['price'], 2); ?></div>
    <div class="col-span-1 text-center"><?php echo $product['quantity']; ?></div>
    <div class="col-span-3">
      <div class="max-w-xs overflow-hidden text-ellipsis whitespace-nowrap" title="<?php echo htmlspecialchars($product['description']); ?>">
        <?php echo htmlspecialchars(substr($product['description'], 0, 50)) . (strlen($product['description']) > 50 ? '...' : ''); ?>
      </div>
    </div>
    <div class="col-span-2 flex gap-2 items-center justify-center">
      <button type="button" class="px-3 py-1 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 flex items-center edit-btn" data-id="<?php echo $product['id']; ?>">
        <i class="fa-solid fa-pen-to-square mr-1"></i> Edit
      </button>
      <button type="button" class="px-3 py-1 bg-red-100 text-red-600 rounded hover:bg-red-200 flex items-center delete-btn" 
             data-id="<?php echo $product['id']; ?>" 
             data-name="<?php echo htmlspecialchars($product['name']); ?>">
        <i class="fa-solid fa-trash mr-1"></i> Delete
      </button>
    </div>
  </div>
  
  <!-- Inline Edit Form -->
  <div class="edit-form p-4 bg-gray-50 border-b border-gray-200" id="edit-form-<?php echo $product['id']; ?>">
    <form action="" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <input type="hidden" name="edit_id" value="<?php echo $product['id']; ?>">
      
      <div class="mb-2">
        <label class="block text-sm font-medium mb-1">Current Image:</label>
        <img src="../<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" class="w-24 h-24 object-cover rounded">
      </div>
      
      <div class="mb-2">
        <label class="block text-sm font-medium mb-1">New Image (optional):</label>
        <input type="file" name="edit_image" accept="image/*" class="border p-1 rounded w-full text-sm">
      </div>
      
      <div class="mb-2">
        <label class="block text-sm font-medium mb-1">Product Name:</label>
        <input type="text" name="edit_name" value="<?php echo htmlspecialchars($product['name']); ?>" required class="border p-1 rounded w-full">
      </div>
      
      <div class="mb-2">
        <label class="block text-sm font-medium mb-1">Price:</label>
        <input type="number" step="0.01" name="edit_price" value="<?php echo $product['price']; ?>" required class="border p-1 rounded w-full">
      </div>
      
      <div class="mb-2">
        <label class="block text-sm font-medium mb-1">Quantity:</label>
        <input type="number" name="edit_quantity" value="<?php echo $product['quantity']; ?>" required class="border p-1 rounded w-full">
      </div>
      
      <div class="mb-2">
        <label class="block text-sm font-medium mb-1">Category:</label>
        <select name="edit_category" required class="border p-1 rounded w-full">
          <?php 
          // Skip the "all" option when editing a product
          foreach($categories as $key => $value) {
            if($key != 'all') {
              $selected = ($key == $product['category']) ? 'selected' : '';
              echo "<option value=\"$key\" $selected>$value</option>";
            }
          }
          ?>
        </select>
      </div>
      
      <div class="mb-2 md:col-span-2">
        <label class="block text-sm font-medium mb-1">Description:</label>
        <textarea name="edit_description" required class="border p-1 rounded w-full" rows="3"><?php echo htmlspecialchars($product['description']); ?></textarea>
      </div>
      
      <div class="md:col-span-2 flex gap-2 justify-end">
        <button type="button" class="px-3 py-1 bg-gray-100 text-gray-600 rounded hover:bg-gray-200 flex items-center cancel-edit-btn" data-id="<?php echo $product['id']; ?>">
          <i class="fa-solid fa-times mr-1"></i> Cancel
        </button>
        <button type="submit" name="edit_product" class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 flex items-center">
          <i class="fa-solid fa-save mr-1"></i> Save Changes
        </button>
      </div>
    </form>
  </div>
<?php
}
?>