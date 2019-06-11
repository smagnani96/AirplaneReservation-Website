<?php

require_once '../utility/utility.php';
if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'off') || $_SERVER['SERVER_PORT'] != 443) {
	echo json_encode(ErrorObject::HTTPS_ENFORCE);
} else {
	if (isset($_COOKIE['email'], $_COOKIE['password'])) {
		$email = $_COOKIE['email'];
		$password = $_COOKIE['password'];
	}
	echo json_encode(array('err' => 0, 'content' => "
		<!--Login Form-->
		<span class='formname'>Login Form</span>
		<form name='login-form' id='login-form' method='post' autocomplete='on'>
			<div>
				<label for='email'>Email</label><br/>
				<input type='email' name='email' id='email' pattern='^[a-zA-Z0-9.!#$%&’*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$' value='$email' placeholder='username1@gmail.com' required autocomplete='on'><br/>
				<span>(Must be an email address)</span><br/>
			</div>
			<div>
				<label for='password'>Password</label><br/>
				<input type='password' name='password' id='password' pattern='^(?=.*[a-z])(?=.*[A-Z0-9]).*$' placeholder='****' value='$password' required autocomplete='off'><br/>
				<span>(Must contain 1 lowcase character and 1 uppercase or a number)</span><br/>
			</div>
			<div>
				<label for='remember'>Remember me</label>
				<input type='checkbox' name='remember' id='remember'>
			</div>
			<input type='button' name='submit' value='Login' id='submit'/>
	</form>"));
}

?>
