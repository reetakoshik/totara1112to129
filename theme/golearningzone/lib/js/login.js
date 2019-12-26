function Login() {
	
}

Login.prototype = {
	getContainer: function() {
		return $('.loginbox');
	},

	getLoginPanel: function() {
		return $('.loginpanel');
	},

	getPage: function() {
		return $('#page');
	},

	getLoginForm: function() {
		return $('#login');
	},

	init: function() {
		var $form = this.getLoginForm();
		var $inputs = $form.find('input[type="text"], input[type="password"]');
		$inputs.each(function(n, input) {
			var $input = $(input);
			var id = $input.attr('id');
			var $lable = $('label[for="'+id+'"]')
			var placeholder = $lable.text();
			$lable.html('');
			$input.attr('placeholder', placeholder.trim());
		});
		// $inputs.blur(function(e) {
		// 	console.log('blur');
		// });
		this.fixBlockSizes();
		this.saml();
	},

	saml: function() {
		var $potentialidps = $('.potentialidps');
		var $loginpanel = $('.loginpanel');
		var text = $potentialidps.find('h6');
		var links = $potentialidps.find('a');
		links.addClass('btn pull-right');
		var $subcontent = $('<div class="guestsub text-center subcontent"></div>');
		var $container = $('<div style="max-width:400px; margin:auto;"></div>');
		$subcontent.append($container);
		$container.append(text);
		$container.append(links);
		$loginpanel.append($subcontent);
	},

	fixBlockSizes: function() {
		var $page = this.getPage();
		var $loginPanel = $page.find('.loginpanel');
		var $signupPanel = $page.find('.signuppanel');
		setInterval(function() {
			if ($loginPanel.length && $signupPanel.find('h2').length) {
				maxHeight = Math.max($loginPanel.outerHeight(), $signupPanel.outerHeight());
				$loginPanel.css('min-height', maxHeight + 'px');
				$signupPanel.css('min-height', maxHeight + 'px');
			}
		}, 300);
	}
}
