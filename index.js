$(document).ready(() => {

	/*If cookies are not enabled, then nothis is loaded*/
	if (!navigator.cookieEnabled)
		$('#box').html('Sorry, your browser seems to have Cookies disabled.');
	else {

		/*Load the Header component*/
		$.ajax({
			url: "components/header.php",
			success: (result) => {
				$('#myHeader').html(result);
			}
		});

		/*Load the navbar component and assign all actions to each of its component*/
		$.ajax({
			url: "components/navbar.php",
			success: (result) => {
				/*Set the content of the navbar*/
				$('#myNavbar').html(result);

				/*Assign action to the login link (Load the login form)*/
				$("#loginLink").click((e) => {
					e.preventDefault();
					$.ajax({
						url: "components/loginForm.php",
						success: (result) => {
							var parsed = JSON.parse(result);
							if(parsed.err == 0) {
								/*Set the content of the central div*/
								$('#content').html(parsed.content);
								/*Set the action to the submit button (Perform formSubmit)*/
								$("#submit").click(() => { 
									e.preventDefault();
									formSubmit("login"); 
								});
								/*Register key enter pressed to perform the form submission*/
								registerEnterForm("login");
							}	else {
								showResult(parsed.err, parsed.msg, false);
							}
						}
					});
				});

				/*Assign action to the logout link (Logout and destroy session)*/
				$("#logoutLink").click((e) => {
					e.preventDefault();
					$.ajax({
						type: "POST",
						url: "utility/process.php",
						data: "action=logout",
						success: (result) => { 
							var parsed = JSON.parse(result);
							showResult(parsed.err, parsed.msg, true);
						}
					});
				});

				/*Assign action to the register link (Load the register form)*/
				$("#registerLink").click((e) => {
					e.preventDefault();
					$.ajax({
						url: "components/registrationForm.php",
						success: (result) => {
							var parsed = JSON.parse(result);
							if(parsed.err == 0) {
								/*Set the content of the main div*/
								$('#content').html(parsed.content);
								/*Set the action to be performed when submit is clicked (formSubmit)*/
								$("#submit").click(() => { 
									e.preventDefault();
									formSubmit("register");
								});
								/*Register the key enter pressed to perform form submission*/
								registerEnterForm("register");
							} else {
								showResult(parsed.err, parsed.msg, false);	
							}
						}
					});
				});

				/*Assign action to the update link (Reload the airplane map)*/
				$("#updateLink").click((e) => { 
					e.preventDefault();
					loadAirplane();
				});

				/*Assign action to the buy link (Perform buy action)*/
				$("#buyLink").click((e) => {
					e.preventDefault();
					if($(".myreserved").length > 0) {
						$.ajax({
						type: "POST",
						url: "utility/perform.php",
						data: "action=buy",
						success: (result) => {
							var parsed = JSON.parse(result);
							if(parsed.err != -2) {
								loadAirplane();
							}
							showResult(parsed.err, parsed.msg, false);
						}
					});
					} else {
						showResult(-1, "To perform that action you need to reserve at least 1 seat.", false);
					}
				});
			}
		});

		/*Load the airplane map component*/
		loadAirplane();
		$('#box').html('Loading');
		$("#box").css('visibility', 'hidden');
	}

	function loadAirplane() {
		$.ajax({
			url: "components/airplane.php",
			success: (result) => {
				$('#content').html(result);
				$('.clickable').each(function() {
					var id = $(this).attr('id');
					$(this).click((e) => {
						e.preventDefault();
						var dataString = "action=reserve&id=" + id;
						$.ajax({
							type: "POST",
							url: "utility/perform.php",
							data: dataString,
							success: (result) => {
								var parsed = JSON.parse(result);
								var seat = $("#" + id);
								var avail = $("#available");
								var unavail = $("#purchased");
								var myres = $("#myreserved");
								if (parsed.err == 0) {
									seat.removeClass("available").addClass("myreserved");
									avail.html(parseInt(avail.html()) - 1);
									myres.html(parseInt(myres.html()) + 1);
								} else if(parsed.err == 1){
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
		});
	}

	function formSubmit(action) {
		if ((action == "login" && !$("#login-form")[0].checkValidity()) ||
			(action == "register" && !$("#register-form")[0].checkValidity())) {
			showResult(-1, "Non valid data, please fill it correctly.", false);
			return;
		}

		if(action == "register" && $("#password").val() != $("#confirm-password").val()) {
			showResult(-1, "The two password must correspond.", false);
			return;
		}

		var remember = (action == "login" && $("#remember").is(":checked"))? 1 : 0;
		$.ajax({
			type: "POST",
			url: "utility/process.php",
			data: "action=" + action + "&email=" + $("#email").val() + "&p=" + $("#password").val() + "&remember=" + remember,
			success: function(res) {
				var parsed = JSON.parse(res);
				showResult(parsed.err, parsed.msg, parsed.err == 0? true : false);
			}
		});
	}

	function registerEnterForm(type) {
		var form = type == "login" ? $("#login-form") : $("#register-form");
		form.keypress((e) => {
			if ((e.which && e.which == 13) || (e.keyCode && e.keyCode == 13)) {
				$("#submit").click();
			}
		});
	}

	function showResult(err, msg, reload) {
		var box = $("#box");
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

});
