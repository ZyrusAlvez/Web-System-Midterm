<?php 
session_start();
include("../connections.php");

if (!isset($connections)) die("Database connection error.");

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

// Get selected category for filtering
$selected_category = isset($_GET['category']) ? urldecode($_GET['category']) : 'all';

// Process delete action
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // First, get the image path to delete the file
    $stmt = $connections->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $image_path = "../" . $row['image'];
        if (file_exists($image_path)) unlink($image_path);
    }
    
    // Delete the product from database
    $stmt = $connections->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $success_message = "Product deleted successfully!";
    } else {
        $error_message = "Error deleting product: " . $stmt->error;
    }
    
    $stmt->close();
    header("Location: products.php?category=" . urlencode($selected_category));
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
        $target_dir = "../uploads/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
        
        $file_extension = strtolower(pathinfo($_FILES["edit_image"]["name"], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;
        $valid_extensions = ["jpg", "jpeg", "png", "gif"];
        
        if (in_array($file_extension, $valid_extensions)) {
            // Get old image to delete
            $stmt = $connections->prepare("SELECT image FROM products WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $old_image_path = "../" . $row['image'];
                if (file_exists($old_image_path)) unlink($old_image_path);
            }
            
            // Upload new file
            if (move_uploaded_file($_FILES["edit_image"]["tmp_name"], $target_file)) {
                $image_path = "uploads/" . $new_filename;
                $stmt = $connections->prepare("UPDATE products SET name = ?, price = ?, quantity = ?, description = ?, image = ?, category = ? WHERE id = ?");
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
        $success_message = "Product updated successfully!";
    } else if (!isset($error_message)) {
        $error_message = "Error updating product: " . $stmt->error;
    }
    
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
    if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
    
    $file_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    $valid_extensions = ["jpg", "jpeg", "png", "gif"];
    
    if (in_array($file_extension, $valid_extensions)) {
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image_path = "uploads/" . $new_filename;
            $stmt = $connections->prepare("INSERT INTO products (name, price, quantity, description, image, category) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sdisss", $name, $price, $quantity, $description, $image_path, $category);
            
            if ($stmt->execute()) {
                $success_message = "Product added successfully!";
            } else {
                $error_message = "Error adding product: " . $stmt->error;
            }
            
            $stmt->close();
        } else {
            $error_message = "Error uploading file.";
        }
    } else {
        $error_message = "Invalid file format. Only JPG, JPEG, PNG, and GIF are allowed.";
    }
}

// Fetch products with category filter
if ($selected_category == 'all') {
    $products_result = $connections->query("SELECT * FROM products ORDER BY category, id DESC");
} else {
    $stmt = $connections->prepare("SELECT * FROM products WHERE category = ? ORDER BY id DESC");
    $stmt->bind_param("s", $selected_category);
    $stmt->execute();
    $products_result = $stmt->get_result();
}

// Display product card function
function displayProductCard($product, $categories) {
    // Determine stock status for styling
    $stockClass = $product['quantity'] <= 0 ? "text-red-500" : ($product['quantity'] <= 10 ? "text-yellow-500" : "text-green-500");
    $stockText = $product['quantity'] <= 0 ? "Out of Stock" : ($product['quantity'] <= 10 ? "Low Stock: " . $product['quantity'] : "In Stock: " . $product['quantity']);
    
    // Category name and price formatting
    $categoryName = isset($categories[$product['category']]) ? $categories[$product['category']] : $product['category'];
    $formattedPrice = number_format($product['price'], 2);
    ?>
    <div class="bg-white rounded-lg overflow-hidden shadow hover:shadow-md transition-transform hover:-translate-y-1">
        <div class="h-44 overflow-hidden relative">
            <img src="../<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="w-full h-full object-cover transition-transform hover:scale-105">
        </div>
        <div class="p-4">
            <h3 class="font-semibold text-base truncate"><?= htmlspecialchars($product['name']) ?></h3>
            <div class="text-gray-600 text-xs mb-2"><?= htmlspecialchars($categoryName) ?></div>
            <div class="font-bold text-[#ee4d2d] text-lg mb-1">$<?= $formattedPrice ?></div>
            <div class="<?= $stockClass ?> text-xs mb-2"><?= $stockText ?></div>
            <div class="text-gray-700 text-sm h-14 overflow-hidden line-clamp-3 mb-3"><?= htmlspecialchars($product['description']) ?></div>
            <div class="flex gap-2">
                <button class="edit-product-btn flex-1 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded text-xs flex justify-center items-center gap-1"
                    data-id="<?= $product['id'] ?>"
                    data-name="<?= htmlspecialchars($product['name']) ?>"
                    data-price="<?= $product['price'] ?>"
                    data-quantity="<?= $product['quantity'] ?>"
                    data-description="<?= htmlspecialchars($product['description']) ?>"
                    data-category="<?= htmlspecialchars($product['category']) ?>"
                    data-image="<?= htmlspecialchars($product['image']) ?>">
                    <i class="fa-solid fa-pen-to-square"></i> Edit
                </button>
                <button class="delete-product-btn flex-1 py-2 bg-red-500 hover:bg-red-600 text-white rounded text-xs flex justify-center items-center gap-1"
                    data-id="<?= $product['id'] ?>"
                    data-name="<?= htmlspecialchars($product['name']) ?>">
                    <i class="fa-solid fa-trash"></i> Delete
                </button>
            </div>
        </div>
    </div>
    <?php
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
  <!-- Mobile Nav -->
  <header class="bg-[#ee4d2d] text-white p-4 sticky top-0 z-30 md:hidden flex justify-between items-center shadow-md">
    <div class="flex items-center gap-2">
      <img src="../assets/logo.jpg" class="w-10 h-10 rounded-full object-cover">
      <h1 class="text-xl font-bold">Chopee</h1>
    </div>
    <button id="mobileMenuBtn" class="text-2xl"><i class="fa-solid fa-bars"></i></button>
  </header>

  <!-- Mobile Sidebar -->
  <div id="mobileSidebar" class="fixed inset-y-0 left-0 -translate-x-full transition-transform w-64 bg-[#ee4d2d] z-40 md:hidden">
    <div class="flex flex-col h-full">
      <div class="flex items-center justify-between p-4 border-b border-[#ff6347]">
        <div class="flex items-center gap-2">
          <img src="../assets/logo.jpg" class="w-12 h-12 rounded-full object-cover">
          <h1 class="text-white text-xl font-bold">Chopee Admin</h1>
        </div>
        <button id="closeMenuBtn" class="text-white text-xl"><i class="fa-solid fa-times"></i></button>
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
  
  <!-- Backdrop -->
  <div id="mobileBackdrop" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden md:hidden"></div>

  <div class="flex">
    <!-- Desktop Sidebar -->
    <aside class="hidden md:flex flex-col w-64 bg-gradient-to-b from-[#ee4d2d] to-[#d03a1b] min-h-screen fixed left-0 top-0 shadow-lg">
      <div class="flex items-center justify-center p-4 border-b border-[#ff6347]">
        <img src="../assets/logo.jpg" class="w-12 h-12 rounded-full object-cover">
        <h1 class="text-white text-xl font-bold ml-3">Chopee Admin</h1>
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
    </aside>

    <!-- Main Content -->
    <div class="w-full md:pl-64">
      <div class="bg-[#faf9f6] p-6 min-h-screen">
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-2xl font-bold">Products Management</h2>
          <button id="showAddForm" class="px-4 py-2 bg-[#ee4d2d] text-white rounded flex items-center">
            <i class="fa-solid fa-plus mr-2"></i> Add New Product
          </button>
        </div>

        <!-- Add Product Form -->
        <div id="addFormContainer" class="hidden bg-white p-6 rounded shadow-md mb-8">
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
                  <?php foreach($categories as $key => $value): ?>
                    <?php if($key != 'all'): ?>
                      <option value="<?= $key ?>"><?= $value ?></option>
                    <?php endif; ?>
                  <?php endforeach; ?>
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
              <button type="button" id="cancelAddForm" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded">Cancel</button>
              <button type="submit" name="add_product" class="px-4 py-2 bg-[#ee4d2d] text-white rounded">Add Product</button>
            </div>
          </form>
        </div>

        <!-- Category Filters -->
        <div class="flex overflow-x-auto gap-2 mb-6 pb-2">
          <?php foreach($categories as $key => $value): ?>
            <a href="products.php?category=<?= urlencode($key) ?>" 
               class="whitespace-nowrap px-4 py-2 rounded-full text-sm transition-colors
                      <?= ($selected_category == $key) ? 'bg-[#ee4d2d] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
              <?= $value ?>
            </a>
          <?php endforeach; ?>
        </div>

        <!-- Products Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
          <?php 
          if ($products_result && $products_result->num_rows > 0) {
            while($product = $products_result->fetch_assoc()) {
              displayProductCard($product, $categories);
            }
          } else { ?>
            <div class="col-span-full p-8 text-center text-gray-500 bg-white rounded shadow">
              <i class="fa-solid fa-box-open text-4xl mb-3 text-gray-400"></i>
              <p class="text-lg">No products found</p>
              <p class="text-sm mt-2">Try selecting a different category or add new products</p>
            </div>
          <?php } ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Delete Modal -->
  <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-center">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-sm p-6 animate-fade-in-down">
      <div class="text-center">
        <div class="text-red-500 mb-4"><i class="fa-solid fa-circle-exclamation text-5xl"></i></div>
        <h3 class="text-xl font-bold mb-2">Confirm Delete</h3>
        <p class="text-gray-600 mb-6">Are you sure you want to delete <span id="deleteProductName" class="font-semibold"></span>? This action cannot be undone.</p>
        <div class="flex gap-3">
          <button id="cancelDelete" class="flex-1 py-2 px-4 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded">Cancel</button>
          <a id="confirmDelete" href="#" class="flex-1 py-2 px-4 bg-red-500 hover:bg-red-600 text-white rounded text-center">Delete</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Edit Modal -->
  <div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-center">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-lg p-6 animate-fade-in-down">
      <div class="flex justify-between items-center mb-4 border-b pb-3">
        <h3 class="text-xl font-bold">Edit Product</h3>
        <button id="closeEditModal" class="text-gray-500 hover:text-gray-700"><i class="fa-solid fa-times"></i></button>
      </div>
      <div id="editFormContainer"><!-- Form loaded via JS --></div>
    </div>
  </div>

  <script src="https://kit.fontawesome.com/d5b7a13861.js" crossorigin="anonymous"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
  <script>
    // Mobile navigation
    document.addEventListener('DOMContentLoaded', function() {
      const mobileMenuBtn = document.getElementById('mobileMenuBtn');
      const closeMenuBtn = document.getElementById('closeMenuBtn');
      const mobileSidebar = document.getElementById('mobileSidebar');
      const mobileBackdrop = document.getElementById('mobileBackdrop');
      
      const toggleMobileMenu = (show) => {
        mobileSidebar.classList.toggle('translate-x-0', show);
        mobileSidebar.classList.toggle('-translate-x-full', !show);
        mobileBackdrop.classList.toggle('hidden', !show);
        document.body.style.overflow = show ? 'hidden' : '';
      };
      
      mobileMenuBtn.addEventListener('click', () => toggleMobileMenu(true));
      closeMenuBtn.addEventListener('click', () => toggleMobileMenu(false));
      mobileBackdrop.addEventListener('click', () => toggleMobileMenu(false));
    });

    // Toastr configuration
    toastr.options = {
      closeButton: true,
      progressBar: true,
      positionClass: "toast-top-right",
      timeOut: 3000
    };
    
    <?php if(isset($success_message)): ?>
    toastr.success('<?= $success_message ?>');
    <?php endif; ?>
    
    <?php if(isset($error_message)): ?>
    toastr.error('<?= $error_message ?>');
    <?php endif; ?>
    
    $(document).ready(function() {
      const deleteModal = document.getElementById('deleteModal');
      const editModal = document.getElementById('editModal');
      const confirmDeleteBtn = document.getElementById('confirmDelete');
      const deleteProductNameSpan = document.getElementById('deleteProductName');
      const editFormContainer = document.getElementById('editFormContainer');
      
      // Add form toggle
      $('#showAddForm').click(() => $('#addFormContainer').slideDown());
      $('#hideAddForm, #cancelAddForm').click(() => {
        $('#addFormContainer').slideUp();
        $('#addProductForm')[0].reset();
      });
      
      // Edit product
      $('.edit-product-btn').click(function() {
        const p = $(this).data();
        
        // Generate edit form HTML
        const editFormHtml = `
          <form action="" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <input type="hidden" name="edit_id" value="${p.id}">
            
            <div class="mb-2">
              <label class="block text-sm font-medium mb-1">Current Image:</label>
              <img src="../${p.image}" alt="${p.name}" class="w-32 h-32 object-cover rounded border">
            </div>
            
            <div class="mb-2">
              <label class="block text-sm font-medium mb-1">New Image (Optional):</label>
              <input type="file" name="edit_image" accept="image/*" class="border p-2 rounded w-full">
              <p class="text-xs text-gray-500 mt-1">Leave empty to keep current image</p>
            </div>
            
            <div class="mb-2 md:col-span-2">
              <label class="block text-sm font-medium mb-1">Product Name:</label>
              <input type="text" name="edit_name" value="${p.name}" required class="border p-2 rounded w-full">
            </div>
            
            <div class="mb-2">
              <label class="block text-sm font-medium mb-1">Price:</label>
              <input type="number" step="0.01" name="edit_price" value="${p.price}" required class="border p-2 rounded w-full">
            </div>
            
            <div class="mb-2">
              <label class="block text-sm font-medium mb-1">Quantity:</label>
              <input type="number" name="edit_quantity" value="${p.quantity}" required class="border p-2 rounded w-full">
            </div>
            
            <div class="mb-2 md:col-span-2">
              <label class="block text-sm font-medium mb-1">Category:</label>
              <select name="edit_category" required class="border p-2 rounded w-full">
                <?php foreach($categories as $key => $value): ?>
                  <?php if($key != 'all'): ?>
                    <option value="<?= $key ?>"><?= $value ?></option>
                  <?php endif; ?>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div class="mb-2 md:col-span-2">
              <label class="block text-sm font-medium mb-1">Description:</label>
              <textarea name="edit_description" required class="border p-2 rounded w-full" rows="4">${p.description}</textarea>
            </div>
            
            <div class="md:col-span-2 flex justify-end gap-2 mt-2">
              <button type="button" class="cancel-edit px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded">Cancel</button>
              <button type="submit" name="edit_product" class="px-4 py-2 bg-[#ee4d2d] text-white rounded">Update Product</button>
            </div>
          </form>
        `;
        
        // Insert form and show modal
        editFormContainer.innerHTML = editFormHtml;
        $('select[name="edit_category"]').val(p.category);
        editModal.style.display = 'flex';
        
        // Cancel button
        $('.cancel-edit').click(() => editModal.style.display = 'none');
      });
      
      // Close edit modal
      document.getElementById('closeEditModal').addEventListener('click', () => editModal.style.display = 'none');
      
      // Delete product
      $('.delete-product-btn').click(function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        
        deleteProductNameSpan.textContent = name;
        confirmDeleteBtn.href = `products.php?action=delete&id=${id}&category=<?= urlencode($selected_category) ?>`;
        deleteModal.style.display = 'flex';
      });
      
      // Cancel delete
      document.getElementById('cancelDelete').addEventListener('click', () => deleteModal.style.display = 'none');
      
      // Close modals on outside click
      window.addEventListener('click', (e) => {
        if (e.target === deleteModal) deleteModal.style.display = 'none';
        if (e.target === editModal) editModal.style.display = 'none';
      });
    });
  </script>
</body>
</html>