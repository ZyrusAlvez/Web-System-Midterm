<?php 
$email = $password = "";
$emailErr = $passwordErr = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(empty($_POST["email"])){
        $emailErr = "Email is required!";
    } else{
        $email = $_POST["email"];
    }

    if(empty($_POST["password"])){
        $passwordErr = "Password is required!";
    } else{
       $password = $_POST["password"];
    }
    
    if ($email && $password){
        include("connections.php");
        $check_email = mysqli_query($connections, "SELECT * FROM mytbl WHERE email = '$email'");
        $check_email_row = mysqli_num_rows($check_email);
        if($check_email_row > 0){
            while($row = mysqli_fetch_assoc($check_email)){
                $db_password = $row["password"];
                $db_account_type = $row["account_type"];
                if ($password == $db_password){
                    if ($db_account_type == "1"){
                        echo "<script>window.location.href='admin'</script>";
                    }else{
                        echo "<script>window.location.href='user'</script>";
                    }
                }else{
                    $passwordErr = "Password is incorrect!";
                }
            }
        } else{
            $emailErr = "Email is not registered!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body class="bg-[#faf9f6] flex w-full h-screen justify-center items-center rounded-lg">
    <form method = "POST" action="<?php htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="w-[50%] bg-white h-[50%] flex flex-col justify-center items-center">
    <input type="text" name="email" value= "<?php echo $email;?>" class="rounded-lg border border-black outline-none py-2 pl-1 w-[90%]" placeholder="Email">
    <br>
    <span class = "text-red-900"><?php echo $emailErr; ?></span><br>

    <input type="password" name="password" value= "<?php echo $password;?>" class="rounded-lg border border-black outline-none py-2 pl-1 w-[90%]" placeholder="Password">
    <br>
    <span class = "text-red-900"><?php echo $passwordErr; ?></span><br>
    <input type="submit" value="Login" class="px-20 py-2 text-white rounded-full bg-black font-bold">
    </form>
    <script src="https://cdn.tailwindcss.com"></script>
</body>
</html>
