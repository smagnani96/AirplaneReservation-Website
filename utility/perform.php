<?php

require_once "db.php";
require_once "utility.php";
require_once "airconf.php";

sec_session_start();

if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'off') || $_SERVER['SERVER_PORT'] != 443) {
	echo json_encode(ErrorObject::HTTPS_ENFORCE);
	return;
}

/*Check if the user is logged*/
$ret = login_check($conn);
if ($ret !== true) {
	echo json_encode($ret);
	return;
}

/*Updating last action timestamp*/
$_SESSION['timestamp'] = time();

if (!isset($_POST['action']) || !in_array($_POST['action'], array('buy', 'reserve'))
	|| ($_POST['action'] == 'reserve' && !isset($_POST['id']))) {
	echo json_encode(ErrorObject::MISSING_DATA);
	return;
}

$seat = $_POST['id'];
$_POST['id'] = filter_var($seat, FILTER_SANITIZE_STRING);

if ($seat != $_POST['id']) {
	echo json_encode(ErrorObject::CODE_INJECTION);
	return;
}

/*Check if the action is reserve and there's the id of the seat*/
if ($_POST["action"] == "reserve") {
	/*Check if the seat is correct in the domain*/
	$maxseat = chr(ord('A') + AIRPLANE_LENGTH - 1) . strval(AIRPLANE_WIDTH);
	if ($_POST["id"] < 'A0' || $_POST["id"] > $maxseat) {
		echo json_encode(ErrorObject::SEAT_OUT_DOMAIN);
	} else {
		echo json_encode(reserveSeat($_SESSION['username'], $_POST['id'], $conn));
	}
	return;
}

/*Check if the action is BUY all the user current reserved tickets*/
if ($_POST["action"] == "buy") {
	echo json_encode(purchaseSeat($_SESSION['username'], $conn));
	return;
}

