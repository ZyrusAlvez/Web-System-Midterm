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
      <div class="flex gap-4 items-center text-start hover:cursor-pointer">
        <i class="fa-solid fa-cart-shopping text-white"></i>
        <a href="orders.php" class="text-white text-xl">Orders</a>
      </div>
      <div class="flex gap-4 items-center text-start hover:cursor-pointer font-bold text-2xl">
        <i class="fa-solid fa-users text-white"></i>
        <a href="users.php" class="text-white">Users</a>
      </div>
    </div>

    <div class="flex items-center justify-center gap-2 mb-8">
      <i class="fa-solid fa-right-from-bracket text-white font-xl"></i>
      <a href="../index.php" class="text-white text-xl">Logout</a>
    </div>

  </aside>

  <div class="bg-[#faf9f6] w-full h-full p-8 overflow-auto">
    <h2 class="text-3xl font-bold mb-6">User Management</h2>
    
    <div class="bg-white rounded-lg shadow-md p-6 overflow-x-auto">
      <table class="min-w-full bg-white">
        <thead>
          <tr class="bg-gray-100 text-gray-600 uppercase text-sm leading-normal">
            <th class="py-3 px-6 text-left">ID</th>
            <th class="py-3 px-6 text-left">Name</th>
            <th class="py-3 px-6 text-left">Email</th>
            <th class="py-3 px-6 text-left">Password</th>
            <th class="py-3 px-6 text-left">Account Type</th>
            <th class="py-3 px-6 text-center">Actions</th>
          </tr>
        </thead>
        <tbody class="text-gray-600 text-sm">
          <?php while($row = mysqli_fetch_assoc($result)): ?>
            <tr class="border-b border-gray-200 hover:bg-gray-100">
              <td class="py-3 px-6 text-left"><?php echo $row['id']; ?></td>
              <td class="py-3 px-6 text-left user-data" data-field="name" data-id="<?php echo $row['id']; ?>">
                <span class="display-value"><?php echo $row['name']; ?></span>
                <input type="text" class="edit-input hidden w-full border rounded px-2 py-1" value="<?php echo $row['name']; ?>">
              </td>
              <td class="py-3 px-6 text-left user-data" data-field="email" data-id="<?php echo $row['id']; ?>">
                <span class="display-value"><?php echo $row['email']; ?></span>
                <input type="email" class="edit-input hidden w-full border rounded px-2 py-1" value="<?php echo $row['email']; ?>">
              </td>
              <td class="py-3 px-6 text-left user-data" data-field="password" data-id="<?php echo $row['id']; ?>">
                <span class="display-value">••••••••</span>
                <input type="text" class="edit-input hidden w-full border rounded px-2 py-1" value="<?php echo $row['password']; ?>">
              </td>
              <td class="py-3 px-6 text-left user-data" data-field="account_type" data-id="<?php echo $row['id']; ?>">
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
              <td class="py-3 px-6 text-center">
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

  <!-- Delete Confirmation Modal -->
  <div id="deleteModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center hidden z-50">
    <div class="bg-white p-6 rounded-lg shadow-xl max-w-md w-full">
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

</body>
</html>