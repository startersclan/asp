$(document).ready(function() {
	$.validator.addMethod("placeholder", function(value, element) {
	  return value != $(element).attr("placeholder");
	}, $.validator.messages.required);
	
	$("#mws-login-form form").validate({
		rules: {
			username: {required: true, placeholder: true}, 
			password: {required: true, placeholder: true}
		}, 
		errorPlacement: function(error, element) {  
		}
	});
	
	if($.fn.placeholder) {
		$('[placeholder]').placeholder();
	}
});
