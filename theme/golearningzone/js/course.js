function Course() {
	
}

Course.prototype = {
	getPage: function() {
		return $('#page');
	},

	getContainer: function() {
		return $('.custom-blocks');
	},

	getBlocks: function() {
		return this.getContainer().find('.block');
	},

	init: function() {
		this.getPage().show();
		this.formatBlocks();
        this.hideShowActionExtended();

		var oldImageUrl = M.util.image_url;

		M.util.image_url = function(imagename, component) {
			var lang = $(document.body).hasClass('lang-he') ? 'he' : 'en';
			if (imagename.indexOf('i/completion') === 0) {
				imagename = imagename + '-' + lang;
			}
		    return oldImageUrl(imagename, component);
		};
	},

	formatBlocks: function() {
		var $blocks = this.getBlocks();

		var maxHeight = 0;
		
		$blocks.each(function(n, block) {
			var $block = $(block);
			var height = $block.find('.content').outerHeight();
			maxHeight = maxHeight > height ? maxHeight : height;
		});

		$blocks.find('.content').css('min-height', maxHeight+'px');
	},

    hideShowActionExtended: function () {
        $('a[data-action="show"], a[data-action="hide"]').click(function () {
            $(this).closest('.mod-indent-outer').toggleClass('text-muted');
        })
    },

}
