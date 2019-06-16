<?php

require_once "../utility/db.php";
require_once "../utility/utility.php";
require_once "../utility/airconf.php";

sec_session_start();

$purchasedSeats = [];
$reservedSeats = [];
$minereserved = [];

$logged = login_check($conn);
if ($logged === true) {
	$minereserved = retrieveUserReserved($_SESSION['username'], $conn);
	$_SESSION['myreserved'] = $minereserved;
}

if ($sql = $conn->prepare("SELECT email, seat, purchased from reservation")) {
	$sql->execute();
	$sql->bind_result($email, $seat, $ispurchase);
	$sql->store_result();
	while ($sql->fetch()) {
		if ($ispurchase) {
			array_push($purchasedSeats, $seat);
		} else if (!in_array($minereserved, $seat)) {
			array_push($reservedSeats, $seat);
		}
	}
} else {
	echo json_encode(ErrorObject::DB_INTERNAL_ERROR);
	return;
}
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
	($logged === true ? "<span id='myreserved'></span>" : "") . "
			</div>
			<div>
				<div class='color-box total'>.</div><br/>
				<div class='color-box available'>...</div><br/>
				<div class='color-box unavailable'>...</div><br/>
				<div class='color-box reserved'>...</div><br/>" .
	($logged === true ? "<div class='color-box myreserved'>...</div>" : "") . "
			</div>
			</div>";

$airplane .= "<div class='map'>";
foreach (range(1, AIRPLANE_LENGTH) as $number) {
	foreach (range('A', chr(ord('A') + AIRPLANE_WIDTH - 1)) as $letter) {
		$class = "seat " . ($logged === true? "clickable " : "");
		$seat = "" . $letter . $number;
		if (in_array($seat, $purchasedSeats)) {
			$class = "seat unavailable";
		} else if (in_array($seat, $minereserved)) {
			$class .= "myreserved";
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

