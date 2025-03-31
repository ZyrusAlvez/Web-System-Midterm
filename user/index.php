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
        <a href="index.php" class="flex flex-col items-center text-gray-600 no-underline text-xs transition-all duration-200 hover:text-chopee">
          <i class="fas fa-home text-lg mb-1"></i>
          <span>Home</span>
        </a>
        <a href="#" class="flex flex-col items-center text-gray-600 no-underline text-xs transition-all duration-200 hover:text-chopee">
          <i class="fas fa-shopping-cart text-lg mb-1"></i>
          <span>Cart</span>
        </a>
        <a href="#" class="flex flex-col items-center text-gray-600 no-underline text-xs transition-all duration-200 hover:text-chopee">
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
      <h3 class="font-medium mb-2 text-gray-800 line-clamp-2 h-10"><?php echo $product['name']; ?></h3>
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