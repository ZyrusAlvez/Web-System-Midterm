<?php 
  include("../connections.php");

  // Handle Delete Request
  if(isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];
    $delete_query = "DELETE FROM users WHERE id = '$user_id'";
    
    if(mysqli_query($connections, $delete_query)) {
      echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
          toastr.success('User has been deleted successfully!');
        });
      </script>";
    } else {
      echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
          toastr.error('Failed to delete user!');
        });
      </script>";
    }
  }

  // Handle Update Request
  if(isset($_POST['update_user'])) {
    $user_id = $_POST['user_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $account_type = $_POST['account_type'];
    
    $update_query = "UPDATE users SET 
                    name = '$name', 
                    email = '$email', 
                    password = '$password', 
                    account_type = '$account_type' 
                    WHERE id = '$user_id'";
    
    if(mysqli_query($connections, $update_query)) {
      echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
          toastr.success('User has been updated successfully!');
        });
      </script>";
    } else {
      echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
          toastr.error('Failed to update user!');
        });
      </script>";
    }
  }

  // Fetch all users
  $query = "SELECT * FROM users";
  $result = mysqli_query($connections, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin - Users</title>

  <!-- Toastr.js -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
  <style>
    /* Reset and base styles */
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    
    body {
      width: 100%;
      overflow-x: hidden;
    }
    
    /* Form styles */
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

    /* Mobile navigation styles */
    .mobile-nav-transition {
      transition: transform 0.3s ease-in-out;
    }
    
    /* Table responsive fixes */
    .table-container {
      width: full;
      overflow-x: auto;
    }
    
    .table-container::-webkit-scrollbar {
      height: 6px;
    }
    
    .table-container::-webkit-scrollbar-thumb {
      background-color: #ee4d2d;
      border-radius: 3px;
    }
    
    .table-container::-webkit-scrollbar-track {
      background-color: #f1f1f1;
    }
    
    /* Main content container fixes */
    .main-content {
      width: 100%;
      max-width: 100%;
      overflow-x: hidden;
    }
    
    /* Responsive layout improvements */
    @media (max-width: 768px) {
      .px-6 {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
      }
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
        <a href="orders.php" class="flex items-center gap-3 p-3 rounded-lg text-white hover:bg-[#ff6347] transition-colors">
          <i class="fa-solid fa-cart-shopping w-6 text-center"></i>
          <span>Orders</span>
        </a>
        <a href="users.php" class="flex items-center gap-3 p-3 rounded-lg text-white font-medium bg-[#ff6347] hover:bg-[#ff7e6b] transition-colors">
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
      <a href="orders.php" class="flex items-center gap-3 p-3 rounded-lg text-white hover:bg-[#ff6347] transition-colors">
        <i class="fa-solid fa-cart-shopping w-6 text-center"></i>
        <span>Orders</span>
      </a>
      <a href="users.php" class="flex items-center gap-3 p-3 rounded-lg text-white font-medium bg-[#ff6347] hover:bg-[#ff7e6b] transition-colors">
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
  <main class="w-full md:w-[80%] md:ml-64 p-4 md:p-6 main-content">
    <div class="bg-[#faf9f6] w-full p-4 md:p-6 overflow-hidden">
      <h2 class="text-2xl md:text-3xl font-bold mb-6">User Management</h2>
    
      <div class="bg-white rounded-lg shadow-md p-4 md:p-6 overflow-hidden">
        <div class="table-container">
          <table class="w-full bg-white">
            <thead>
              <tr class="bg-[#ee4d2d] text-white uppercase text-sm leading-normal">
                <th class="py-3 px-4 md:px-2 text-left">ID</th>
                <th class="py-3 px-4 md:px-2 text-left">Name</th>
                <th class="py-3 px-4 md:px-2 text-left">Email</th>
                <th class="py-3 px-4 md:px-2 text-left">Password</th>
                <th class="py-3 px-4 md:px-2 text-left">Account Type</th>
                <th class="py-3 px-4 md:px-2 text-center">Actions</th>
              </tr>
            </thead>
            <tbody class="text-gray-600 text-sm">
              <?php while($row = mysqli_fetch_assoc($result)): ?>
                <tr class="border-b border-gray-200 hover:bg-gray-100">
                  <td class="py-3 px-4 md:px-2 text-left"><?php echo $row['id']; ?></td>
                  <td class="py-3 px-4 md:px-2 text-left user-data" data-field="name" data-id="<?php echo $row['id']; ?>">
                    <span class="display-value"><?php echo $row['name']; ?></span>
                    <input type="text" class="edit-input hidden w-full border rounded px-2 py-1" value="<?php echo $row['name']; ?>">
                  </td>
                  <td class="py-3 px-4 md:px-2 text-left user-data" data-field="email" data-id="<?php echo $row['id']; ?>">
                    <span class="display-value"><?php echo $row['email']; ?></span>
                    <input type="email" class="edit-input hidden w-full border rounded px-2 py-1" value="<?php echo $row['email']; ?>">
                  </td>
                  <td class="py-3 px-4 md:px-2 text-left user-data" data-field="password" data-id="<?php echo $row['id']; ?>">
                    <span class="display-value">••••••••</span>
                    <input type="text" class="edit-input hidden w-full border rounded px-2 py-1" value="<?php echo $row['password']; ?>">
                  </td>
                  <td class="py-3 px-4 md:px-2 text-left user-data" data-field="account_type" data-id="<?php echo $row['id']; ?>">
                    <span class="display-value">
                      <?php if($row['account_type'] == 1): ?>
                        <span class="bg-[#ee4d2d] text-white py-1 px-3 rounded-full text-xs">Admin</span>
                      <?php else: ?>
                        <span class="bg-green-200 text-green-800 py-1 px-3 rounded-full text-xs">User</span>
                      <?php endif; ?>
                    </span>
                    <select class="edit-input hidden w-full border rounded px-2 py-1">
                      <option value="1" <?php echo ($row['account_type'] == 1) ? 'selected' : ''; ?>>Admin</option>
                      <option value="2" <?php echo ($row['account_type'] == 2) ? 'selected' : ''; ?>>User</option>
                    </select>
                  </td>
                  <td class="py-3 px-4 md:px-2 text-center">
                    <div class="flex item-center justify-center gap-2">
                      <div class="edit-actions">
                        <button class="edit-btn transform hover:text-[#ee4d2d] hover:scale-110">
                          <i class="fa-solid fa-pen-to-square"></i>
                        </button>
                        <button class="delete-btn transform hover:text-red-500 hover:scale-110 ml-2" 
                                onclick="confirmDelete(<?php echo $row['id']; ?>, '<?php echo $row['name']; ?>')">
                          <i class="fa-solid fa-trash"></i>
                        </button>
                      </div>
                      <div class="save-actions hidden">
                        <button class="save-btn transform hover:text-green-500 hover:scale-110">
                          <i class="fa-solid fa-check"></i>
                        </button>
                        <button class="cancel-btn transform hover:text-red-500 hover:scale-110 ml-2">
                          <i class="fa-solid fa-times"></i>
                        </button>
                      </div>
                    </div>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>

  <!-- Delete Confirmation Modal -->
  <div id="deleteModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center hidden z-50">
    <div class="bg-white p-6 rounded-lg shadow-xl max-w-md w-full mx-4">
      <h3 class="text-xl font-bold mb-4">Confirm Deletion</h3>
      <p class="mb-6">Are you sure you want to delete user: <span id="userName" class="font-semibold"></span>?</p>
      <div class="flex justify-end gap-4">
        <form method="POST">
          <input type="hidden" name="user_id" id="deleteUserId">
          <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400">Cancel</button>
          <button type="submit" name="delete_user" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">Delete</button>
        </form>
      </div>
    </div>
  </div>

  <!-- Update Form (Hidden) -->
  <form id="updateForm" method="POST" class="hidden">
    <input type="hidden" name="user_id" id="updateUserId">
    <input type="hidden" name="name" id="updateName">
    <input type="hidden" name="email" id="updateEmail">
    <input type="hidden" name="password" id="updatePassword">
    <input type="hidden" name="account_type" id="updateAccountType">
    <input type="hidden" name="update_user" value="1">
  </form>

  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- FontAwesome -->
  <script src="https://kit.fontawesome.com/d5b7a13861.js" crossorigin="anonymous"></script>
  <!-- jQuery -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  <!-- Toastr.js -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

  <script>
    // Configure Toastr
    toastr.options = {
      closeButton: true,
      progressBar: true,
      positionClass: "toast-top-right",
      timeOut: 3000
    };

    // Delete Confirmation Modal
    function confirmDelete(userId, userName) {
      document.getElementById('deleteUserId').value = userId;
      document.getElementById('userName').textContent = userName;
      document.getElementById('deleteModal').classList.remove('hidden');
    }

    function closeDeleteModal() {
      document.getElementById('deleteModal').classList.add('hidden');
    }

    // Inline Editing
    document.addEventListener('DOMContentLoaded', function() {
      const editButtons = document.querySelectorAll('.edit-btn');
      
      editButtons.forEach(button => {
        button.addEventListener('click', function() {
          const row = this.closest('tr');
          
          // Toggle display/edit modes
          row.querySelectorAll('.display-value').forEach(span => {
            span.classList.add('hidden');
          });
          
          row.querySelectorAll('.edit-input').forEach(input => {
            input.classList.remove('hidden');
          });
          
          // Toggle action buttons
          row.querySelector('.edit-actions').classList.add('hidden');
          row.querySelector('.save-actions').classList.remove('hidden');
        });
      });

      // Cancel Editing
      document.querySelectorAll('.cancel-btn').forEach(button => {
        button.addEventListener('click', function() {
          const row = this.closest('tr');
          
          // Restore display mode
          row.querySelectorAll('.display-value').forEach(span => {
            span.classList.remove('hidden');
          });
          
          row.querySelectorAll('.edit-input').forEach(input => {
            input.classList.add('hidden');
          });
          
          // Toggle action buttons
          row.querySelector('.edit-actions').classList.remove('hidden');
          row.querySelector('.save-actions').classList.add('hidden');
        });
      });

      // Save Edited User
      document.querySelectorAll('.save-btn').forEach(button => {
        button.addEventListener('click', function() {
          const row = this.closest('tr');
          const userId = row.querySelector('.user-data').getAttribute('data-id');
          
          // Get updated values
          const nameInput = row.querySelector('[data-field="name"] .edit-input').value;
          const emailInput = row.querySelector('[data-field="email"] .edit-input').value;
          const passwordInput = row.querySelector('[data-field="password"] .edit-input').value;
          const accountTypeInput = row.querySelector('[data-field="account_type"] .edit-input').value;
          
          // Confirm before saving
          toastr.info(
            `<div>
              <p>Are you sure you want to update this user?</p>
              <button id="confirmUpdate" class="bg-[#ee4d2d] text-white px-2 py-1 rounded mr-2 mt-2">Yes, Update</button>
              <button id="cancelUpdate" class="bg-gray-300 px-2 py-1 rounded mt-2">Cancel</button>
            </div>`,
            'Confirm Update',
            {
              closeButton: false,
              timeOut: 0,
              extendedTimeOut: 0,
              tapToDismiss: false
            }
          );
          
          // Handle confirmation
          document.getElementById('confirmUpdate').addEventListener('click', function() {
            // Set form values and submit
            document.getElementById('updateUserId').value = userId;
            document.getElementById('updateName').value = nameInput;
            document.getElementById('updateEmail').value = emailInput;
            document.getElementById('updatePassword').value = passwordInput;
            document.getElementById('updateAccountType').value = accountTypeInput;
            document.getElementById('updateForm').submit();
            
            toastr.clear();
          });
          
          document.getElementById('cancelUpdate').addEventListener('click', function() {
            toastr.clear();
          });
        });
      });
    });
  </script>
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
</body>
</html>