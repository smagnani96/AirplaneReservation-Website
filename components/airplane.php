<?php

require_once "../utility/db.php";
require_once "../utility/utility.php";
require_once "../utility/config.php";
require_once "../utility/checkerAccess.php";

sec_session_start();

$purchasedSeats = [];
$reservedSeats = [];
$mineReserved = [];

$logged = login_check($conn);

/*Check if session expired*/
if ($logged === ErrorObject::EXPIRED_SESSION) {
	echo json_encode($logged);
	return;
}

/*If logged update timestamp and empty Reserved since they are reloaded*/
if ($logged === true) {
	$_SESSION['myReserved'] = array();
	$_SESSION['timestamp'] = time();
}

/*Retrieve the seats including the user specific ones if it is logged*/
if ($sql = $conn->prepare("SELECT email, seat, purchased from reservation")) {
	$sql->execute();
	$sql->bind_result($email, $seat, $ispurchase);
	$sql->store_result();
	while ($sql->fetch()) {
		if ($ispurchase) {
			array_push($purchasedSeats, $seat);
		} else if ($logged === true && $email == $_SESSION['username']) {
			array_push($mineReserved, $seat);
			array_push($_SESSION['myReserved'], $seat);
		} else {
			array_push($reservedSeats, $seat);
		}
	}
} else {
	echo json_encode(ErrorObject::DB_INTERNAL_ERROR);
	return;
}

/*Print the statistic*/
$airplane = "<div class='statistic'>
			<div>
				<span>Total Seats: </span><br/>
				<span>Available: </span><br/>
				<span>Unavailable: </span><br/>
				<span>Reserved: </span><br/>" .
	($logged === true ? "<span>Reserved by you: </span>" : "") . "
			</div>
			<div>
				<span id='total'></span><br/>
				<span id='available'></span><br/>
				<span id='purchased'></span><br/>
				<span id='reserved'></span><br/>" .
	($logged === true ? "<span id='myReserved'></span>" : "") . "
			</div>
			<div>
				<div class='color-box total'>.</div><br/>
				<div class='color-box available'>...</div><br/>
				<div class='color-box unavailable'>...</div><br/>
				<div class='color-box reserved'>...</div><br/>" .
	($logged === true ? "<div class='color-box myReserved'>...</div>" : "") . "
			</div>
			</div>";

/*Print the airplane map*/
$airplane .= "<div class='map'>";
foreach (range(1, AIRPLANE_LENGTH) as $number) {
	foreach (range('A', chr(ord('A') + AIRPLANE_WIDTH - 1)) as $letter) {
		/*Assign class coherently (is clickable, is reserved, etc..)*/
		$class = "seat " . ($logged === true ? "clickable " : "");
		$seat = "" . $letter . $number;
		if (in_array($seat, $purchasedSeats)) {
			$class = "seat unavailable";
		} else if (in_array($seat, $mineReserved)) {
			$class .= "myReserved";
		} else if (in_array($seat, $reservedSeats)) {
			$class .= "reserved";
		} else {
			$class .= "available";
		}
		$airplane .= "<div id='$seat' class='$class'>
							<span>$seat</span>
							<img src=res/Seat.png alt=AirplaneSeat />
					</div>";
	}
	$airplane .= "<br/>";
}
$airplane .= "</div>";

echo json_encode(array('err' => 0, 'msg' => $airplane));
