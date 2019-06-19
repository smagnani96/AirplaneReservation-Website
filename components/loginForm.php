<?php

require_once "../utility/utility.php";
require_once "../utility/checkerAccess.php";
require_once "../utility/checkerHttps.php";

if (isset($_COOKIE['email'], $_COOKIE['password'])) {
	$email = $_COOKIE['email'];
	$password = $_COOKIE['password'];
}
echo json_encode(array('err' => 0, 'msg' => "
		<!--Login Form-->
		<span class='formName'>Login Form</span>
		<form name='login-form' id='login-form' method='post' autocomplete='on'>
			<div>
				<label for='email'>Email</label><br/>
				<input title='Insert an email address' type='email' name='email' id='email' pattern='^[a-zA-Z0-9.!#$%&’*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$' value='$email' placeholder='username1@gmail.com' required autocomplete='on'><br/>
			</div>
			<div>
				<label for='password'>Password</label><br/>
				<input title='The password must contain 1 lowcase character and 1 uppercase or a number' type='password' name='password' id='password' pattern='^(?=.*[a-z])(?=.*[A-Z0-9]).*$' placeholder='****' value='$password' required autocomplete='off'><br/>
			</div>
			<div>
				<label for='remember'>Remember me</label>
				<input type='checkbox' name='remember' id='remember'>
			</div>
			<input type='button' name='submit' value='Login' id='submit'/>
	</form>"));
