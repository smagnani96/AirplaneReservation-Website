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
$reserved = sizeof($reservedSeats);
$purchased = sizeof($purchasedSeats);
$available = $total - $reserved - $purchased;

$format = "%0" . strlen(("" . $total)) . "d";

echo "<div class='statistic'>
			<div>
				<span>Total Seats: </span><br/>
				<span>Available: </span><br/>
				<span>Reserved: </span><br/>" .
($logged === true ? "<span>Reserved by you: </span><br/>" : "") . "
				<span>Unavailable: </span><br/>
			</div>
			<div>
				<progress class='ptotal' value='$total' max='$total'></progress><span>" . sprintf($format, $total) . "</span><br/>
				<progress class='pavailable' value='$available' max='$total'></progress><span>" . sprintf($format, $available) . "</span><br/>
				<progress class='preserved' value='$reserved' max='$total'></progress><span>" . sprintf($format, $reserved) . "</span><br/> " .
($logged === true ? "<progress class='pmyreserved' value='$myreserved' max='$total'></progress><span>" . sprintf($format, $myreserved) . "</span><br/>" : "") . "
				<progress class='ppurchased' value='$purchased' max='$total'></progress><span>" . sprintf($format, $purchased) . "</span>
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
