<?php

require_once "../utility/db.php";
require_once "../utility/utility.php";
require_once "../utility/airconf.php";

if (session_status() == PHP_SESSION_NONE) {
	sec_session_start();
}

$purchasedSeats = [];
$reservedSeats = [];
$minereserved = [];

$logged = login_check($conn);

if ($sql = $conn->prepare("SELECT email, seat, purchased from reservation")) {
	$sql->execute();
	$sql->bind_result($email, $seat, $ispurchase);
	$sql->store_result();
	while ($sql->fetch()) {
		if ($ispurchase) {
			array_push($purchasedSeats, $seat);
		} else if ($logged === true && $email == $_SESSION["username"]) {
			array_push($minereserved, $seat);
		} else {
			array_push($reservedSeats, $seat);
		}
	}
} else {
	echo json_encode(ErrorObject::DB_INTERNAL_ERROR);
	return;
}

$total = $width * $length;
$reserved = sizeof($reservedSeats) + sizeof($minereserved);
$purchased = sizeof($purchasedSeats);
$available = $total - $reserved - $purchased;

echo "<div class='statistic'>
			<div>
				<span>Total Seats: " . $total . "</span><br/>
				<span>Available: " . $available . "</span><br/>
				<span>Reserved: " . $reserved . "</span><br/>
				<span>Unavailable: " . $purchased . "</span><br/>
			</div>
			<div>
				<progress class='ptotal' value='$total' max='$total'></progress><br/>
				<progress class='pavailable' value='$available' max='$total'></progress><br/>
				<progress class='preserved' value='$reserved' max='$total'></progress><br/>
				<progress class='ppurchased' value='$purchased' max='$total'></progress>
			</div>
		</div>";

echo "<div class='map'>";
foreach (range('A', chr(ord('A') + $length - 1)) as $letter) {
	foreach (range(1, $width) as $number) {
		$class;
		if (in_array("" . $letter . $number, $purchasedSeats)) {
			$class = "unavailable";
		} else if (in_array("" . $letter . $number, $reservedSeats)) {
			$class = $logged === true ? "clickable reserved" : "reserved";
		} else if (in_array("" . $letter . $number, $minereserved)) {
			$class = $logged === true ? "clickable myreserved" : "myreserved";
		} else {
			$class = $logged === true ? "clickable available" : "available";
		}
		echo "<img src='res/Seat.png' alt='AirplaneSeat' id='" . $letter . $number . "' class='seat " . $class . "'>";
	}
	echo "<br/>";
}
echo "</div>";
?>
