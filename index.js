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
						url: "components/loginForm.html",
						success: (result) => {
							/*Set the content of the central div*/
							$('#content').html(result);
							/*Set the action to the submit button (Perform formhash)*/
							$("#submit").click(() => { formhash("login"); });
							/*Check if `remember me` is selected: if yes then set a cookie for the username*/
							var username = getCookie("email");
							if (username != "")
								$("#email").val(username);
							/*Register key enter pressed to perform the form submission*/
							registerEnterForm("login");
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
						url: "components/registrationForm.html",
						success: (result) => {
							/*Set the content of the main div*/
							$('#content').html(result);
							/*Set the action to be performed when submit is clicked (formhash)*/
							$("#submit").click(() => { formhash("register") });
							/*Register the key enter pressed to perform form submission*/
							registerEnterForm("register");
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
							$('.myreserved').each(function () {
								$(this).removeClass('myreserved clickable').addClass('unavailable').unbind();
							});
							if (parsed.err >= 0) showSuccess(parsed.msg, false);
							else if(parsed.err == -100) showFailed(parsed.msg, true);
							else showFailed(parsed.msg, false)
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
					$(this).click((e) => {
						var dataString = "action=reserve&id=" + e.target.id;
						$.ajax({
							type: "POST",
							url: "utility/perform.php",
							data: dataString,
							success: (result) => {
								var parsed = JSON.parse(result);
								if (parsed.err == 0)
									$("#" + e.target.id).removeClass("available reserved").addClass("myreserved");
								else if (parsed.err == 1)
									$("#" + e.target.id).removeClass("myreserved reserved").addClass("available");
								else if (parsed.err == 2)
									$("#" + e.target.id).removeClass("available reserved").addClass("unavailable");
								if (parsed.err >= 0) showSuccess(parsed.msg, false);
								else if(parsed.err === -100) showFailed(parsed.msg, true);
								else showFailed(parsed.msg, false);
							}
						});
					});
				});
			}
		});
	}

	function formhash(action) {
		if ((action == "login" && !$("#login-form")[0].checkValidity()) ||
			(action == "register" && !$("#register-form")[0].checkValidity())) {
			showFailed("Non valid data, please fill it correctly (password must have 1 lower case and 1 upper case or 1 number)", false);
			return;
		}

		$.ajax({
			type: "POST",
			url: "utility/process.php",
			data: "action=" + action + "&email=" + $("#email").val() + "&p=" + $("#password").val(),
			success: function(res) {
				var parsed = JSON.parse(res);
				if (parsed.err == 0) {
					if (action == "login" && $("#remember").is(":checked")) {
						setCookie("email", $("#email").val(), 2);
					}
					showSuccess(parsed.msg, true);
				} else {
					showFailed(parsed.msg, false);
				}
			}
		});
	}

	function getCookie(cname) {
		var name = cname + "=";
		var ca = document.cookie.split(';');
		for (var i = 0; i < ca.length; i++) {
			var c = ca[i];
			while (c.charAt(0) == ' ') {
				c = c.substring(1);
			}
			if (c.indexOf(name) == 0)
				return c.substring(name.length, c.length);
		}
		return "";
	}

	function setCookie(cname, cvalue, exdays) {
		var d = new Date();
		d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
		var expires = "expires=" + d.toUTCString();
		document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
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
		}, 1500);
	}

	function showFailed(msg, reload) {
		$("#box").removeClass('success').addClass('error');
		$("#box").html(msg);
		$("#box").css('visibility', 'visible');
		setTimeout(function() {
			$("#box").css('visibility', 'hidden')
			if(reload)
				location.reload();
		}, 2500);
	}

});
