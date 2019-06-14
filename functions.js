//Function called to load the airplane map
function loadAirplane() {
	$.ajax({
		url: "components/airplane.php",
		success: (result) => {
			let parsed = JSON.parse(result);
			if(parsed.err === 0) {
				$('#content').html(parsed.msg);
				seatsRegisterClick();
			} else {
				showResult(parsed.err, parsed.msg, false);
			}
		}
	});
}

//Function to assign to each navbar link its action
function navBarRegisterClick() {
	//Assign action to the login link (Load the login form)
	$("#loginLink").click((e) => {
		e.preventDefault();
		$.ajax({
			url: "components/loginForm.php",
			success: (result) => {
				let parsed = JSON.parse(result);
				if(parsed.err === 0) {
					//Set the content of the central div
					$('#content').html(parsed.msg);
					//Set the action to the submit button (Perform formSubmit)
					$("#submit").click((e) => { 
						e.preventDefault();
						formSubmit("login"); 
					});
					//Register key enter pressed to perform the form submission
					registerEnterForm("login");
				}	else {
					showResult(parsed.err, parsed.msg, false);
				}
			}
		});
	});

	//Assign action to the logout link (Logout and destroy session)
	$("#logoutLink").click((e) => {
		e.preventDefault();
		$.ajax({
			type: "POST",
			url: "utility/process.php",
			data: "action=logout",
			success: (result) => { 
				let parsed = JSON.parse(result);
				showResult(parsed.err, parsed.msg, true);
			}
		});
	});

	//Assign action to the register link (Load the register form)
	$("#registerLink").click((e) => {
		e.preventDefault();
		$.ajax({
			url: "components/registrationForm.php",
			success: (result) => {
				let parsed = JSON.parse(result);
				if(parsed.err === 0) {
					//Set the content of the main div
					$('#content').html(parsed.msg);
					//Set the action to be performed when submit is clicked (formSubmit)
					$("#submit").click((e) => { 
						e.preventDefault();
						formSubmit("register");
					});
					//Register the key enter pressed to perform form submission
					registerEnterForm("register");
				} else {
					showResult(parsed.err, parsed.msg, false);	
				}
			}
		});
	});

	//Assign action to the update link (Reload the airplane map)
	$("#updateLink").click((e) => { 
		e.preventDefault();
		loadAirplane();
	});

	//Assign action to the buy link (Perform buy action)
	$("#buyLink").click((e) => {
		e.preventDefault();
		if($(".myreserved").length > 1) {
			$.ajax({
			type: "POST",
			url: "utility/perform.php",
			data: "action=buy",
			success: (result) => {
				let parsed = JSON.parse(result);
				if(parsed.err !== -2) {
					//Buy sent since at least 1 seat => reload map
					loadAirplane();
				}
				showResult(parsed.err, parsed.msg, false);
			}});
		} else {
			showResult(-1, "To perform that action you need to reserve at least 1 seat.", false);
		}
	});
}

//Function to assign to each seat the action performed
function seatsRegisterClick() {
	//The object in the statistic map
	let avail = $("#available");
	let unavail = $("#purchased");
	let myres = $("#myreserved");
	let res = $("#reserved");
	//To each clickable assign the action
	$('.clickable').each(function() {
		let id = $(this).attr('id');
		//Perform the action to the server (Reserve/Unreserve)
		$(this).click((e) => {
			e.preventDefault();
			$.ajax({
				type: "POST",
				url: "utility/perform.php",
				data: "action=reserve&id=" + id,
				success: (result) => {
					let parsed = JSON.parse(result);
					let seat = $("#" + id);
					//Update correctly the statistic map
					if (parsed.err === 0) {
						seat.removeClass("available").addClass("myreserved");
						myres.html(parseInt(myres.html()) + 1);
						if(seat.hasClass("reserved")) {
							seat.removeClass("reserved");
							res.html(parseInt(res.html()) - 1);
						} else {
							avail.html(parseInt(avail.html()) - 1);
						}
					} else if(parsed.err === 1){
						seat.removeClass("myreserved").addClass("available");
						avail.html(parseInt(avail.html()) + 1);
						myres.html(parseInt(myres.html()) - 1);
					} else {
						seat.removeClass("available").addClass("unavailable");
						avail.html(parseInt(avail.html()) - 1);
						unavail.html(parseInt(unavail.html()) + 1);
					}
					showResult(parsed.err, parsed.msg, false);
				}
			});
		});
	});
}

//Function assigned to the form's submit buttons
function formSubmit(action) {
	//Check for inputs validity
	if ((action === "login" && !$("#login-form")[0].checkValidity()) ||
		(action === "register" && !$("#register-form")[0].checkValidity())) {
		showResult(-1, "Non valid data, please fill it correctly.", false);
		return;
	}
	let email = $("#email").val();
	let password = $("#password").val();
	//Check for password correspondance
	if(action === "register" && password !== $("#confirm-password").val()) {
		showResult(-1, "The two password must correspond.", false);
		return;
	}

	//Perform server query
	let remember = (action === "login" && $("#remember").is(":checked"))? 1 : 0;
	$.ajax({
		type: "POST",
		url: "utility/process.php",
		data: "action=" + action + "&email=" + email + "&p=" + password + "&remember=" + remember,
		success: function(res) {
			let parsed = JSON.parse(res);
			showResult(parsed.err, parsed.msg, parsed.err === 0);
		}
	});
}

//Function to register key enter to submit the form
function registerEnterForm(type) {
	let form = type === "login" ? $("#login-form") : $("#register-form");
	form.keypress((e) => {
		if ((e.which && e.which === 13) || (e.keyCode && e.keyCode === 13)) {
			$("#submit").click();
		}
	});
}

//Function to show in the div box the colored message and reload or not the page
function showResult(err, msg, reload) {
	let box = $("#box");
	box.html(msg);
	if(err >= 0) {
		box.removeClass('error').addClass('success');	
	} else {
		box.removeClass('success').addClass('error');
	}
	box.css('visibility', 'visible');
	setTimeout(function() {
		box.css('visibility', 'hidden');
		if(reload){ 
			location.reload();
		}
	}, 1000);
}
