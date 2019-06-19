<?php

/*Check for direct access*/
if ($_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest') {
	die('Direct access is forbidden.');
}
