<?php

require_once "../utility/utility.php";

if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'off') || $_SERVER['SERVER_PORT'] != 443) {
	echo json_encode(ErrorObject::HTTPS_ENFORCE);
} else {
	echo json_encode(array('err' => 0, 'msg' => "
    <!--Register Form-->
    <span class='formname'>Registration Form</span>
    <form name='register-form' id='register-form' method='post' autocomplete='on'>
      	<div>
        	<label for='email' >Email</label><br/>
            <input type='email' name='email' id='email' pattern='^[a-zA-Z0-9.!#$%&’*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$' placeholder='esempio@prova.com' required><br/>
            <span>(Must be an email address)</span><br/>
        </div>
        <div>
            <label for='password'>Password</label><br/>
            <input type='password' name='password' id='password' placeholder='**' pattern='^(?=.*[a-z])(?=.*[A-Z0-9]).*$' required autocomplete='off'><br/>
            <span>(Must contain 1 lowcase character and 1 uppercase or a number)</span><br/>
        </div>
        <div>
            <label for='confirm-password'>Confirm</label><br/>
            <input type='password' name='confirm-password' id='confirm-password' placeholder='**' pattern='^(?=.*[a-z])(?=.*[A-Z0-9]).*$' required autocomplete='off'><br/>
            <span>(Must correspond to the password inserted)</span><br/>
        </div>
        <input type='button' value='Register' id='submit' />
    </form>", ));
}

?>