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

// Fetch all products for display with category filter
if ($selected_category == 'all') {
    $products_query = "SELECT * FROM products ORDER BY id DESC"; // Removed category sorting
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
  <title>Chopee - Online Shopping</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <style>
    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
      margin: 0;
      padding: 0;
      background-color: #f5f5f5;
      color: #333;
    }
    
    .header {
      background-color: white;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      padding: 12px 24px;
      position: sticky;
      top: 0;
      z-index: 100;
    }
    
    .header-content {
      max-width: 1200px;
      margin: 0 auto;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .logo-container {
      display: flex;
      align-items: center;
    }
    
    .logo-text {
      color: #ee4d2d;
      font-weight: 600;
      font-size: 26px;
      margin-left: 10px;
    }
    
    .search-container {
      flex: 1;
      max-width: 600px;
      margin: 0 24px;
      position: relative;
    }
    
    .search-input {
      width: 100%;
      padding: 10px 16px;
      border-radius: 4px;
      border: 2px solid #ee4d2d;
      font-size: 14px;
      outline: none;
      transition: all 0.2s;
    }
    
    .search-btn {
      position: absolute;
      right: 0;
      top: 0;
      height: 100%;
      background: #ee4d2d;
      border: none;
      color: white;
      padding: 0 16px;
      border-top-right-radius: 4px;
      border-bottom-right-radius: 4px;
      cursor: pointer;
    }
    
    .nav-links {
      display: flex;
      gap: 16px;
      align-items: center;
    }
    
    .nav-link {
      display: flex;
      flex-direction: column;
      align-items: center;
      color: #555;
      text-decoration: none;
      font-size: 12px;
      transition: all 0.2s;
    }
    
    .nav-link:hover {
      color: #ee4d2d;
    }
    
    .nav-link i {
      font-size: 18px;
      margin-bottom: 4px;
    }
    
    .container {
      max-width: 1200px;
      margin: 24px auto;
      padding: 0 16px;
    }
    
    .page-title {
      margin-bottom: 16px;
      font-size: 20px;
      font-weight: 600;
      color: #333;
    }
    
    .category-filters {
      display: flex;
      overflow-x: auto;
      gap: 8px;
      padding-bottom: 8px;
      margin-bottom: 16px;
      -ms-overflow-style: none;  /* Hide scrollbar for IE and Edge */
      scrollbar-width: none;  /* Hide scrollbar for Firefox */
    }
    
    .category-filters::-webkit-scrollbar {
      display: none; /* Hide scrollbar for Chrome, Safari and Opera */
    }
    
    .category-filter {
      white-space: nowrap;
      padding: 8px 16px;
      border-radius: 20px;
      font-size: 14px;
      cursor: pointer;
      transition: all 0.2s;
      text-decoration: none;
    }
    
    .category-filter.active {
      background-color: #ee4d2d;
      color: white;
      font-weight: 500;
    }
    
    .category-filter:not(.active) {
      background-color: #f5f5f5;
      color: #333;
      border: 1px solid #ddd;
    }
    
    .category-filter:hover:not(.active) {
      background-color: #e0e0e0;
    }
    
    .products-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
      gap: 16px;
    }
    
    .product-card {
      background-color: white;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      transition: all 0.2s;
      cursor: pointer;
      text-decoration: none; /* Add this for the anchor tags */
      color: inherit; /* Add this to prevent color change on anchor */
      display: block; /* Make sure anchor takes full space */
    }
    
    .product-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }
    
    .product-img-container {
      position: relative;
      padding-top: 100%; /* 1:1 Aspect Ratio */
      background-color: #f9f9f9;
      overflow: hidden;
    }
    
    .product-img {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    
    .product-id {
      position: absolute;
      top: 8px;
      left: 8px;
      background-color: rgba(0,0,0,0.5);
      color: white;
      padding: 2px 8px;
      border-radius: 4px;
      font-size: 12px;
    }
    
    .product-category {
      position: absolute;
      bottom: 8px;
      right: 8px;
      background-color: rgba(238, 77, 45, 0.8);
      color: white;
      padding: 2px 8px;
      border-radius: 4px;
      font-size: 12px;
      max-width: 70%;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    
    .product-details {
      padding: 12px;
    }
    
    .product-name {
      font-weight: 500;
      margin-bottom: 8px;
      color: #333;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
      height: 40px;
    }
    
    .product-price {
      font-weight: 600;
      color: #ee4d2d;
      font-size: 16px;
      margin-bottom: 6px;
    }
    
    .product-meta {
      display: flex;
      justify-content: space-between;
      color: #999;
      font-size: 12px;
    }
    
    .product-desc {
      color: #666;
      font-size: 12px;
      margin-top: 8px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    
    .empty-state {
      grid-column: 1 / -1;
      padding: 32px;
      text-align: center;
      color: #888;
      background-color: white;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    @media (max-width: 768px) {
      .search-container {
        max-width: 100%;
        margin: 12px 0;
      }
      
      .header-content {
        flex-direction: column;
        align-items: stretch;
      }
      
      .logo-container {
        justify-content: center;
        margin-bottom: 12px;
      }
      
      .nav-links {
        justify-content: center;
        margin-top: 12px;
      }
      
      .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
      }
    }
  </style>
</head>
<body>
  <!-- Header -->
  <header class="header">
    <div class="header-content">
      <div class="logo-container">
        <a href="index.php" style="text-decoration: none; display: flex; align-items: center;">
          <img src="../assets/logo_no_bg.webp" alt="Chopee" class="w-10 h-10">
          <span class="logo-text">Chopee</span>
        </a>
      </div>
      
      <div class="search-container">
        <input type="text" class="search-input" placeholder="Search products...">
        <button class="search-btn">
          <i class="fas fa-search"></i>
        </button>
      </div>
      
      <div class="nav-links">
        <a href="index.php" class="nav-link">
          <i class="fas fa-home"></i>
          <span>Home</span>
        </a>
        <a href="#" class="nav-link">
          <i class="fas fa-shopping-cart"></i>
          <span>Cart</span>
        </a>
        <a href="#" class="nav-link">
          <i class="fas fa-user"></i>
          <span>Account</span>
        </a>
      </div>
    </div>
  </header>

  <!-- Main Content -->
  <div class="container">
    <h1 class="page-title">
      <?php 
      if ($selected_category == 'all') {
        echo "All Products";
      } else {
        echo isset($categories[$selected_category]) ? $categories[$selected_category] : $selected_category;
      }
      ?>
    </h1>

    <!-- Category Filters -->
    <div class="category-filters">
      <?php foreach($categories as $key => $value): ?>
        <a href="index.php?category=<?php echo urlencode($key); ?>" 
           class="category-filter <?php echo ($selected_category == $key) ? 'active' : ''; ?>">
          <?php echo $value; ?>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Products Grid -->
    <div class="products-grid">
      <?php 
      if ($products_result && $products_result->num_rows > 0) {
        while($product = $products_result->fetch_assoc()) {
          displayProductCard($product, $categories, $selected_category);
        }
      } else {
      ?>
        <div class="empty-state">
          <i class="fas fa-shopping-bag" style="font-size: 48px; color: #ddd; margin-bottom: 16px; display: block;"></i>
          <p>No products found in this category</p>
        </div>
      <?php } ?>
    </div>
  </div>

  <script src="https://cdn.tailwindcss.com"></script>
</body>
</html>

<?php
// Helper function to display product card
function displayProductCard($product, $categories, $selected_category) {
  $category_display_name = isset($categories[$product['category']]) ? $categories[$product['category']] : $product['category'];
?>
  <a href="product_detail.php?id=<?php echo $product['id']; ?>&category=<?php echo urlencode($selected_category); ?>" class="product-card" id="product-<?php echo $product['id']; ?>">
    <div class="product-img-container">
      <span class="product-id">#<?php echo $product['id']; ?></span>
      <?php if ($selected_category == 'all'): ?>
        <span class="product-category"><?php echo $category_display_name; ?></span>
      <?php endif; ?>
      <img src="../<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" class="product-img">
    </div>
    <div class="product-details">
      <h3 class="product-name"><?php echo $product['name']; ?></h3>
      <div class="product-price">₱<?php echo number_format($product['price'], 2); ?></div>
      <div class="product-meta">
        <span><i class="fas fa-box"></i> <?php echo $product['quantity']; ?> in stock</span>
      </div>
      <div class="product-desc" title="<?php echo htmlspecialchars($product['description']); ?>">
        <?php echo htmlspecialchars(substr($product['description'], 0, 50)) . (strlen($product['description']) > 50 ? '...' : ''); ?>
      </div>
    </div>
  </a>
<?php
}
?>