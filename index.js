$(document).ready(() => {

	/*If cookies are not enabled, then nothis is loaded*/
	if (!navigator.cookieEnabled)
		$('body').prepend('<div>Sorry, your browser seems to have Cookies disabled.</div>');
	else {
		$('#box').html('Loading');
		$("#box").css('visibility', 'hidden');

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
								$("#submit").click(() => { formSubmit("login"); });
								/*Register key enter pressed to perform the form submission*/
								registerEnterForm("login");
							}	else {
								showFailed(parsed.msg, false);
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
						success: (result) => { showSuccess("Successfully logged out!", true); }
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
								$("#submit").click(() => { formSubmit("register") });
								/*Register the key enter pressed to perform form submission*/
								registerEnterForm("register");
							} else {
								showFailed(parsed.msg, false);	
							}
						}
					});
				});

				/*Assign action to the update link (Reload the airplane map)*/
				$("#updateLink").click((e) => { 
					e.preventDefault();;
					loadAirplane();
				});

				/*Assign action to the buy link (Perform buy action)*/
				$("#buyLink").click((e) => {
					e.preventDefault();
					$.ajax({
						type: "POST",
						url: "utility/perform.php",
						data: "action=buy",
						success: (result) => {
							var parsed = JSON.parse(result);
							if (parsed.err == 0) {
								showSuccess(parsed.msg, false);
							} else if(parsed.err == -2){
								showFailed(parsed.msg, false);
								return;
							} else {
								showFailed(parsed.msg, false);
							}
							loadAirplane();
						}
					});
				});
			}
		});

		/*Load the airplane map component*/
		loadAirplane();

		/*Load the footer component*/
		$.ajax({
			url: "components/footer.html",
			success: (result) => {
				$('#myFooter').html(result);
			}
		});
	}

	function loadAirplane() {
		$.ajax({
			url: "components/airplane.php",
			success: (result) => {
				$('#content').html(result);
				$('.clickable').each(function() {
					var id = $(this).attr('id');
					$(this).click((e) => {
						var dataString = "action=reserve&id=" + id;
						$.ajax({
							type: "POST",
							url: "utility/perform.php",
							data: dataString,
							success: (result) => {
								var parsed = JSON.parse(result);
								if (parsed.err == 0) {
									$("#" + id).removeClass("available").addClass("myreserved");
									showSuccess(parsed.msg, false);
								} else if(parsed.err == 1){
									$("#" + id).removeClass("myreserved").addClass("available");
									showSuccess(parsed.msg, false);
								} else {
									$("#" + id).removeClass("available").addClass("unavailable");
									showFailed(parsed.msg, false);
								}
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
			showFailed("Non valid data, please fill it correctly.", false);
			return;
		}

		if(action == "register" && $("#password").val() != $("#confirm-password").val()) {
			showFailed("The two password must correspond.", false);
			return;
		}

		var remember = action == "login" && $("#remember").is(":checked")? 1 : 0;
		$.ajax({
			type: "POST",
			url: "utility/process.php",
			data: "action=" + action + "&email=" + $("#email").val() + "&p=" + $("#password").val() + "&remember=" + remember,
			success: function(res) {
				var parsed = JSON.parse(res);
				if (parsed.err == 0) {
					showSuccess(parsed.msg, true);
				} else {
					showFailed(parsed.msg, false);
				}
			}
		});
	}

	function registerEnterForm(type) {
		var form = type == "login" ? "#login-form" : "#register-form";
		$(form).keypress((e) => {
			if ((e.which && e.which == 13) || (e.keyCode && e.keyCode == 13)) {
				$("#submit").click();
				return false;
			} else
				return true;
		});
	}

	function showSuccess(msg, reload) {
		$("#box").html(msg);
		$("#box").removeClass('error').addClass('success');
		$("#box").css('visibility', 'visible');
		setTimeout(function() {
			$("#box").css('visibility', 'hidden');
			if(reload) 
				location.reload();
		}, 1200);
	}

	function showFailed(msg, reload) {
		$("#box").removeClass('success').addClass('error');
		$("#box").html(msg);
		$("#box").css('visibility', 'visible');
		setTimeout(function() {
			$("#box").css('visibility', 'hidden')
			if(reload)
				location.reload();
		}, 1200);
	}

});
