<?php
$showform = true;
$errors = '';
$myemail = 'youremail@gmail.com';

if (isset($_POST))
{
	// SETS VARIABLES FROM USER INPUT
	$name = $_POST['name2'];
	$email = $_POST['email2'];
	$message = $_POST['message2'];
	$security = $_POST['security2'];

		// CHECKS SECURITY QUESTION	
		if ($security != '54032')
		{
			$errors .= "<div style='font: Arial; color:#FF0000; font-weight:bold;'>Error: Invalid security answer.</div>";
		}
		
		if ( empty($errors) )
		{
			$to = $myemail;
		
			$email_subject = "CONTACT FORM";
			$email_body = "You have received a new message. ".
			"Here are the details:\n Name: $name \n Email: $email \n Message: \n $message";
		
			$headers = "From: $myemail\n";
			$headers .= "Reply-To: $email_address";
		
			mail($to, $email_subject, $email_body, $headers);
			
			$success = "<h3 style='color:green;'>THANK YOU!</h3><p style='color:green;'>We'll return your message shortly.</p>";
			
			$showform = false;
			
			unset($_POST);
			echo '<meta http-equiv="refresh" content="5;url=contact.php" />';
			//redirect to the 'thank you' page
			//header('Location: contact_success.html');
		}
	}

?>

<!DOCTYPE html>
<head>
<title>Simple Contact Form</title>
</head>


                    <?php
						if (isset($showform))
							{
								echo nl2br($errors);
					?>
         <div id="form" class="contact">
        <form name="contactform" method="post" action="?s=1">
            
            <p id="name2">Name</p> <input type="text" name="name2" value="<?php echo $name; ?>" id="name2" /><br /><br />
            
            <p id="email2">Email</p> <input type="text" name="email2" value="<?php echo $email; ?>" id="email2" /><br /><br />
            
            <p id="message2">Questions/Comments:</p><textarea name="message2" rows="6" cols="30" class="message2"><?php echo $message; ?></textarea><br /><br />
            
            <p id="security2">Enter the code in the box below:
            <span class="security-image"><img src="http://alabamacomfort.com/images/security-image.jpg" width="73px" height="17px" style="margin-bottom:10px;"/></span><input type="text" name="security2" id="security2" />
            
            <div style="clear:left;">&nbsp;</div>
            
            <input type="submit" class="submit_button"value="SEND MY MESSAGE" id="send_message" border="0" />
        </form>
        </div>

    <?php 
	} 
	else
	{
		echo nl2br($success);	
	}
	?>
    

</body>
</html>
