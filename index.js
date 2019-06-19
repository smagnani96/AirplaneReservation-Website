$('document').ready(function() {

	//If cookies are not enabled, then nothis is loaded
	if (!navigator.cookieEnabled)
		$('#box').html('Sorry, your browser seems to have Cookies disabled.');
	else {

		//Load the Header component
		$.ajax({
			url: "components/header.php",
			success: function(result) {
				let parsed = JSON.parse(result);
				if(parsed.err === 0) {
					$('#header').html(parsed.msg);
				} else {
					showResult(parsed.err, parsed.msg, false);
				}
			}
		});

		//Load the navBar component and assign all actions to each of its component
		$.ajax({
			url: "components/navbar.php",
			success: function(result) {
				//Set the content of the navBar
				let parsed = JSON.parse(result);
				if(parsed.err === 0) {
					$('#navBar').html(parsed.msg);
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
