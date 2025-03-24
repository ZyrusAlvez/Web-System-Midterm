<?php

include("connections.php");

$name = $address = $email = $password = $cpassword =  "";
$nameErr = $addressErr = $emailErr = $passwordErr = $cpasswordErr = "" ; 

if($_SERVER["REQUEST_METHOD"] == "POST" ){
	
	if(empty($_POST["name"])){
		$nameErr = "Name is required!";
	}else{
		$name = $_POST["name"];
	}
	
	if(empty($_POST["address"])){
		$addressErr = "Address is required!";
	}else{
		$address = $_POST["address"];
	}
	
	if(empty($_POST["email"])){
		$emailErr = "Email is required!";
	}else{
		$email = $_POST["email"];
	}

	if (empty($_POST["password"])) {
        $passwordErr = "Password is required";
    } else {
        $password = $_POST["password"];
    }

    if (empty($_POST["cpassword"])) {
        $cpasswordErr = "Confirm Password is required";
    } else {
        $cpassword = $_POST["cpassword"];
    }

	if($name && $address && $email && $password && $cpassword){

		$check_email = mysqli_query($connections, "SELECT * FROM mytbl WHERE email='$email'");
		$check_email_row = mysqli_num_rows($check_email);

		if($check_email_row > 0){
			$emailErr = "Email is already registered!";
		}else{
			$query = mysqli_query($connections, "INSERT INTO mytbl (name,address,email,password,account_type) VALUES ('$name','$address','$email','$cpassword', '2')");
			echo "<script language = 'javascript'>alert('New record has been inserted!')</script>";
			echo "<script>window.location.href='index.php'</script>";
		}
		
		// $query = mysqli_query($connections, "INSERT INTO mytbl(name,address,email) VALUES('$name','$address','$email')");
		// echo "<script language='javascript'>alert('New Record has been inserted!')</script>";
		// echo "<script>window.location.href='index.php';</script>";
	}
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Document</title>
</head>
<body class="flex flex-col items-center justify-center mt-8 gap-4 w-full">

	<?php include("nav.php"); ?>
	
	<form method="POST" action="" class="flex flex-col gap-2 w-[30%]">	
		<input type="text" name="name" value="<?php echo $name; ?>" class="rounded-xl border-[1.5px] border-gray-600 outline-none py-2 pl-2" placeholder="Name"> 
		<span class="text-red-900"><?php echo $nameErr; ?></span>
		<input type="text" name="address" value="<?php echo $address; ?>" class="rounded-xl border-[1.5px] border-gray-600 outline-none py-2 pl-2" placeholder="Address"> 
		<span class="text-red-900"><?php echo $addressErr; ?></span>
		<input type="text" name="email" value="<?php echo $email; ?>" class="rounded-xl border-[1.5px] border-gray-600 outline-none py-2 pl-2" placeholder="Email"> 
		<span class="text-red-900"><?php echo $emailErr; ?></span>
		<input type="password" name = "password" value = "<?php echo $password;?>" class="rounded-xl border-[1.5px] border-gray-600 outline-none py-2 pl-2" placeholder="Password">
        <span class= "text-red-900"> <?php echo $passwordErr ;?></span>
    	<input type="password" name = "cpassword" value = "<?php echo $cpassword;?>" class="rounded-xl border-[1.5px] border-gray-600 outline-none py-2 pl-2" placeholder="Confirm Password">
        <span class= "text-red-900"> <?php echo $cpasswordErr ;?></span>

		<input type="submit" value="Submit" class="rounded-xl border-[1.5px] border-gray-600 outline-none bg-black text-white py-1 font-bold"> 
	</form>

	<hr class="border-[1px] border-black w-[95%] rounded-full">

	<?php

		$view_query = mysqli_query($connections, "SELECT * FROM mytbl");

		
		echo "<table class='grid gap-2'>";
		echo "<tr>
				<td class='px-4 py-2 text-lg text-white bg-black text-center font-bold'>Name</td>
				<td class='px-4 py-2 text-lg text-white bg-black text-center font-bold'>Address</td>
				<td class='px-4 py-2 text-lg text-white bg-black text-center font-bold'>Email</td>
	
				<td class='px-4 py-2 text-lg text-white bg-black text-center font-bold'>Option</td>
	
			</tr>";

		while($row = mysqli_fetch_assoc($view_query)){

			$user_id = $row["id"];
	
			$db_name = $row["name"];
			$db_address = $row["address"];
			$db_email = $row["email"];
	
			echo "<tr>
					<td class='px-4 py-2 border border-black'>$db_name</td>
					<td class='px-4 py-2 border border-black'>$db_address</td>
					<td class='px-4 py-2 border border-black'>$db_email</td>
	
					<td class='px-4 py-2 border border-black'>
					<a href='edit.php?id=$user_id' class='rounded-full bg-black text-white px-2 py-1'>🖊 Update</a>
					&nbsp;
					<a href='confirm_delete.php?id=$user_id' class='rounded-full bg-black text-white px-2 py-1'>🗑 Delete</a>
					</td>
					</tr>";
		}
		echo "</table>";
	?>

	<hr>

	<?php 
    
    $Paul = "Paul";
    $Mica = "Mica";
    $Kaye = "Kaye";
    $names = array($Paul, $Mica, $Kaye);
    foreach($names as $display_names){
        echo $display_names . "<br>";
    }

	?>

	<script src="https://cdn.tailwindcss.com"></script>
</body>
</html>