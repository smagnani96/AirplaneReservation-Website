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

$total = AIRPLANE_WIDTH * AIRPLANE_LENGTH;
$myreserved = sizeof($minereserved);
$reserved = sizeof($reservedSeats) - sizeof($minereserved);
$purchased = sizeof($purchasedSeats);
$available = $total - $reserved - $purchased;

echo "<div class='statistic'>
			<div>
				<span>Total Seats: </span><br/>
				<span>Available: </span><br/>
				<span>Unavailable: </span><br/>
				<span>Reserved: </span><br/>" .
	($logged === true ? "<span>Reserved by you: </span>" : "") . "
			</div>
			<div>
				<span id='total'>$total</span><br/>
				<span id='available'>$available</span><br/>
				<span id='purchased'>$purchased</span><br/>
				<span id='reserved'>$reserved</span><br/>" .
	($logged === true ? "<span id='myreserved'>$myreserved</span>" : "") . "
			</div>
			<div>
				<div class='color-box total'>.</div><br/>
				<div class='color-box available'>...</div><br/>
				<div class='color-box unavailable'>...</div><br/>
				<div class='color-box reserved'>...</div><br/>" .
	($logged === true ? "<div class='color-box myreserved'>...</div>" : "") . "
			</div>
			</div>";

echo "<div class='map'>";
foreach (range('A', chr(ord('A') + AIRPLANE_LENGTH - 1)) as $letter) {
	foreach (range(1, AIRPLANE_WIDTH) as $number) {
		$class;
		$seat = "" . $letter . $number;
		if (in_array($seat, $purchasedSeats)) {
			$class = "unavailable";
		} else if (in_array($seat, $minereserved)) {
			$class = $logged === true ? "clickable myreserved" : "myreserved";
		} else if (in_array($seat, $reservedSeats)) {
			$class = $logged === true ? "clickable reserved" : "reserved";
		} else {
			$class = $logged === true ? "clickable available" : "available";
		}
		echo "<div id='$seat' class='seat $class'>
					<span>$seat</span>
					<img src=res/Seat.png alt=AirplaneSeat />
				</div>";
	}
	echo "<br/>";
}
echo "</div>";

?>
