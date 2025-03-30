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
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $product['name']; ?> | Chopee</title>
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

    .breadcrumb {
      display: flex;
      align-items: center;
      font-size: 14px;
      margin-bottom: 16px;
      color: #666;
    }

    .breadcrumb a {
      color: #666;
      text-decoration: none;
      transition: color 0.2s;
    }

    .breadcrumb a:hover {
      color: #ee4d2d;
    }

    .breadcrumb .separator {
      margin: 0 8px;
      color: #999;
    }
    
    .product-detail {
      background-color: white;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      display: flex;
      flex-direction: column;
    }

    @media (min-width: 768px) {
      .product-detail {
        flex-direction: row;
      }
    }
    
    .product-image-container {
      flex: 1;
      min-width: 300px;
      padding: 24px;
      display: flex;
      align-items: center;
      justify-content: center;
      background-color: #f9f9f9;
    }
    
    .product-image {
      max-width: 100%;
      max-height: 400px;
      object-fit: contain;
    }
    
    .product-info {
      flex: 1;
      padding: 24px;
      display: flex;
      flex-direction: column;
    }
    
    .product-title {
      font-size: 24px;
      font-weight: 600;
      margin-bottom: 8px;
      color: #333;
    }
    
    .product-category-badge {
      display: inline-block;
      background-color: #ee4d2d;
      color: white;
      padding: 4px 12px;
      border-radius: 16px;
      font-size: 14px;
      margin-bottom: 16px;
    }
    
    .product-price-container {
      background-color: #fffaf7;
      padding: 16px;
      border-radius: 8px;
      margin-bottom: 16px;
    }
    
    .product-price {
      font-size: 28px;
      font-weight: 700;
      color: #ee4d2d;
    }
    
    .product-meta {
      margin-bottom: 24px;
    }
    
    .meta-item {
      display: flex;
      align-items: center;
      margin-bottom: 8px;
      color: #666;
    }
    
    .meta-item i {
      margin-right: 8px;
      color: #999;
      width: 20px;
    }
    
    .product-description-title {
      font-size: 18px;
      font-weight: 600;
      margin-bottom: 12px;
      color: #333;
    }
    
    .product-description {
      color: #555;
      line-height: 1.6;
      margin-bottom: 24px;
      white-space: pre-line; /* Preserve line breaks */
    }
    
    .action-buttons {
      display: flex;
      gap: 16px;
      margin-top: auto;
    }
    
    .btn {
      padding: 12px 24px;
      border-radius: 4px;
      font-weight: 500;
      font-size: 16px;
      cursor: pointer;
      text-align: center;
      border: none;
      transition: all 0.3s;
    }
    
    .btn-primary {
      background-color: #ee4d2d;
      color: white;
      flex: 2;
    }
    
    .btn-primary:hover {
      background-color: #d23f21;
    }
    
    .btn-secondary {
      background-color: #fff0eb;
      color: #ee4d2d;
      border: 1px solid #ee4d2d;
      flex: 1;
    }
    
    .btn-secondary:hover {
      background-color: #ffdfd3;
    }
    
    .similar-products {
      margin-top: 32px;
    }
    
    .similar-products-title {
      font-size: 20px;
      font-weight: 600;
      margin-bottom: 16px;
      color: #333;
    }
    
    .back-button {
      display: inline-flex;
      align-items: center;
      color: #666;
      text-decoration: none;
      margin-bottom: 16px;
      font-weight: 500;
      transition: color 0.2s;
    }
    
    .back-button:hover {
      color: #ee4d2d;
    }
    
    .back-button i {
      margin-right: 8px;
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
      text-decoration: none;
      color: inherit;
      display: block;
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
    
    .product-price-sm {
      font-weight: 600;
      color: #ee4d2d;
      font-size: 16px;
      margin-bottom: 6px;
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
      
      .action-buttons {
        flex-direction: column;
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
    <!-- Breadcrumb -->
    <div class="breadcrumb">
      <a href="index.php">Home</a>
      <span class="separator">/</span>
      <a href="index.php?category=<?php echo urlencode($selected_category); ?>">
        <?php echo isset($categories[$selected_category]) ? $categories[$selected_category] : 'Products'; ?>
      </a>
      <span class="separator">/</span>
      <span><?php echo $product['name']; ?></span>
    </div>

    <!-- Back button -->
    <a href="index.php?category=<?php echo urlencode($selected_category); ?>" class="back-button">
      <i class="fas fa-arrow-left"></i> Back to products
    </a>

    <!-- Product Detail -->
    <div class="product-detail">
      <div class="product-image-container">
        <img src="../<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" class="product-image">
      </div>
      
      <div class="product-info">
        <h1 class="product-title"><?php echo $product['name']; ?></h1>
        
        <span class="product-category-badge">
          <?php echo isset($categories[$product['category']]) ? $categories[$product['category']] : $product['category']; ?>
        </span>
        
        <div class="product-price-container">
          <div class="product-price">₱<?php echo number_format($product['price'], 2); ?></div>
        </div>
        
        <div class="product-meta">
          <div class="meta-item">
            <i class="fas fa-box"></i>
            <span><?php echo $product['quantity']; ?> items in stock</span>
          </div>
          <div class="meta-item">
            <i class="fas fa-id-card"></i>
            <span>Product ID: <?php echo $product['id']; ?></span>
          </div>
        </div>
        
        <h2 class="product-description-title">Product Description</h2>
        <div class="product-description">
          <?php echo nl2br(htmlspecialchars($product['description'])); ?>
        </div>
        
        <div class="action-buttons">
          <button class="btn btn-secondary">
            <i class="fas fa-heart"></i> Add to Wishlist
          </button>
          <button class="btn btn-primary">
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
    <div class="similar-products">
      <h2 class="similar-products-title">Similar Products</h2>
      <div class="products-grid">
        <?php
        while ($similar_product = $similar_result->fetch_assoc()) {
        ?>
        <a href="product_detail.php?id=<?php echo $similar_product['id']; ?>&category=<?php echo urlencode($selected_category); ?>" class="product-card">
          <div class="product-img-container">
            <span class="product-id">#<?php echo $similar_product['id']; ?></span>
            <img src="../<?php echo $similar_product['image']; ?>" alt="<?php echo $similar_product['name']; ?>" class="product-img">
          </div>
          <div class="product-details">
            <h3 class="product-name"><?php echo $similar_product['name']; ?></h3>
            <div class="product-price-sm">₱<?php echo number_format($similar_product['price'], 2); ?></div>
            <div class="product-meta">
              <span><i class="fas fa-box"></i> <?php echo $similar_product['quantity']; ?> in stock</span>
            </div>
          </div>
        </a>
        <?php } ?>
      </div>
    </div>
    <?php } ?>
  </div>

  <script src="https://cdn.tailwindcss.com"></script>
</body>
</html>