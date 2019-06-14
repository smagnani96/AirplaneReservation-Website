$(document).ready(() => {

	//If cookies are not enabled, then nothis is loaded
	if (!navigator.cookieEnabled)
		$('#box').html('Sorry, your browser seems to have Cookies disabled.');
	else {

		//Load the Header component
		$.ajax({
			url: "components/header.php",
			success: (result) => {
				var parsed = JSON.parse(result);
				if(parsed.err == 0) {
					$('#myHeader').html(parsed.msg);
				} else {
					showResult(parsed.err, parsed.msg, false);
				}
			}
		});

		//Load the navbar component and assign all actions to each of its component
		$.ajax({
			url: "components/navbar.php",
			success: (result) => {
				//Set the content of the navbar
				var parsed = JSON.parse(result);
				if(parsed.err == 0) {
					$('#myNavbar').html(parsed.msg);
					navBarRegisterClick();
				} else {
					showResult(parsed.err, parsed.msg, false);
				}
			}
		});

		//Load the airplane map component
		loadAirplane();

		$("#box").css('visibility', 'hidden');
	}

});
