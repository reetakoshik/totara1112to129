function PanelWrapper() {
	
}

PanelWrapper.prototype = {
	getContainer: function() {
		return $('div[role="main"]');
	},

	getPanel: function() {
		//var $panel = this.getContainer().find('.panel');
		return $('<div class="panel"></div>');
		return $panel;
	},

	getPage: function() {
		return $('#page');
	},

	init: function() {
		try {
			var $container =  this.getContainer();
			var $tab = $container.find('.tabtree');
			var $header = $container.find('h2:not([class])');
			var $panel = this.getPanel();
			if ($tab.length) {
				var $nodes = $tab.nextAll();
				$panel.append($nodes);
				$tab.after($panel);
			} else if ($header.length) {
				var self = this;
				$header.each(function(n, header) {
					var $panel = self.getPanel();
					var $thisHeader = $(header);
					var $nodes = $thisHeader.nextAll();
					/* 
						The next code line is a little hack for my/reports.php page.
						The reason for this is that moodle renders this page not properly. 
						The second header is not on the same level as the first one 
							and it placed inside one of the first headers next siblings.
						Need to exclude it from rest of the siblings.
					*/
					$nodes = $nodes.filter(':not(#scheduledreports_section)');
					$nodes = $nodes.length ? $nodes : $thisHeader.parent().nextAll();
					if ($nodes.length) {
						$panel.html('').append($nodes);
						$thisHeader.after($panel);
					}
				});
			} else {
				var $nodes = $container.children();
				$panel.append($nodes);
				$container.append($panel);
			}
		} catch (e) {
			console.log(e);
		}
		
		// this.initToggles();
		this.getPage().show();
	},

	initToggles: function() {
		$('.ftoggler').parent().addClass('collapsed');
		$('fieldset.collapsible .form-required')
			.closest('fieldset.collapsible')
			.removeClass('collapsed');
	}
}
