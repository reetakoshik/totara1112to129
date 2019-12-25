function Header() {

}

function Alerts() {

}

function Menu() {

}

Header.prototype = {
	init: function() {
		new Alerts().init();
		new Menu().collapsibleMobileMenu();
		// new Menu().init();
	}
}

Alerts.prototype = {
	init: function() {
		$('action-totara-alerts').on('click', function(e) {
		    e.stoppropagation();
		});

		$('body > header .action-totara-alerts form.remove-form [type="submit"]').click(
			this.deleteAlert.bind(this)
		);

		// Set focus for Search menu input
        $('.menu-search').unbind('click').on('click', function(e) {
            setTimeout(function(){
                $('input[name="activityname"]').focus();
            }, 500);
        });

    },

	deleteAlert: function(e) {
		e.preventDefault();
		var $form = $(e.target).closest('form');
		var $inputs = $form.find('input');
		var formData = {};
		$inputs.each(function(n, input) {
			var $input = $(input);
			formData[$input.attr('name')] = $input.val();
		});

		formData.Action = 'Dismiss';
		formData.Target = 'Messages';
		formData.msgids = '';

		$.postJSON($form.attr('action')+'?'+$.param(formData), {}, function(res) {
			if (res.Status) {
				var $dropdownCounter = $form.closest('.alerts-list').find('.count');
				var $buttonCounter = $form.closest('.action-totara-alerts').find('.items-count');

				$dropdownCounter.text($dropdownCounter.text() - 1);
				
				if ($buttonCounter.text() > 1) {
					$buttonCounter.text($buttonCounter.text() - 1);
				} else {
					$buttonCounter.remove();
				}
				
				$form.closest('li').remove();
			}
		});

		return false;
	}
}

Menu.prototype = {
	init: function() {
		var $header = $('body > header');
		var $footer = $('body > footer');
		var $page = $('body #page');
		var $ul = $header.find('ul ul.totara-menu-nav-list.navbar.navbar-nav');
		$ul.prepend('<div class="triangle-left"></div><div class="triangle-right"></div>');
		$header.show();
		$footer.show();
		$page.css('visibility', 'visible').show();
		
	},
    collapsibleMobileMenu: function () {
		if($(window).width() <= 769) {
			// Collapse all menus except active one on initialisation and set proper toggle icon
			$(".totara-menu ul li.haschildren").each(function( index ) {
				if(!$(this).hasClass('selected')) {
					$(this).find('ul.navbar-nav').toggle();
				} else {
					$(this).addClass('mshown')
				}
			});

			// Click on toggle icon
			$('.totara-menu-arrow').on('click', function(e) {
				// Open clicked menu and change it toggle icon
				$(this).siblings('ul.navbar-nav').toggle();
				$(this).closest('li').toggleClass('mshown');
				// Close other menus and change their toggle icon
				$('li ul.navbar-nav').not($(this).siblings('ul.navbar-nav')).hide();
				$('.totara-menu ul li').not($(this).closest('li')).removeClass('mshown');
			});
		}
	}
};