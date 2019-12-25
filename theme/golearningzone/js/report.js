function Report() {
	
}

Report.prototype = {
	getContainer: function() {
		return $('#dp-plan-content');
	},

	getPanel: function() {
		var $panel = this.getContainer().find('.panel');
		$panel = $panel.length ? $panel : $('<div class="panel"></div>');
		return $panel;
	},

	getPage: function() {
		return $('#page');
	},

	init: function() {
		//this.preparePanel();
		// this.prepareSearch();
		this.prepareElements();
		// this.getPage().show();
	},

	preparePanel: function() {
		var $container = this.getContainer();
		var $panel = this.getPanel();
		$panel.append($container.children());
		var $header = $panel.find('> h2');
		var $tabs = $panel.find('> .tabtree');
		$container.children().remove();
		$container.append($header);
		$container.append($tabs);
		$container.append($panel);
	},

	// prepareSearch: function() {
	// 	var $topContainer = $('#fgroup_id_course-courselink_grp');
	// 	var $legend = $topContainer.find('legend');
	// 	var $select = $topContainer.find('select');
	// 	var $input = $topContainer.find('input');
	// 	$topContainer.html(`
	// 			<div class="row">
	// 				<div class="col-sm-4">${$('<div>').append($legend.clone()).html()}</div>
	// 				<div class="col-sm-4">${$('<div>').append($select.clone()).html()}</div>
	// 				<div class="col-sm-4">${$('<div>').append($input.clone()).html()}</div>
	// 			</div>
	// 	`);

	// 	var $middleContainer = $('#fgroup_id_plan-name_grp');
	// 	var $legend = $middleContainer.find('legend');
	// 	var $select = $middleContainer.find('select');
	// 	var $input = $middleContainer.find('input');
	// 	$middleContainer.html(`
	// 			<div class="row">
	// 				<div class="col-sm-4">${$('<div>').append($legend.clone()).html()}</div>
	// 				<div class="col-sm-4">${$('<div>').append($select.clone()).html()}</div>
	// 				<div class="col-sm-4">${$('<div>').append($input.clone()).html()}</div>
	// 			</div>
	// 	`);

	// 	var $bottomContainer = $('#fgroup_id_plan-courseduedate_grp fieldset');
	// 	var $legend = $bottomContainer.find('legend');
	// 	var $felement = $bottomContainer.find('> .felement');
	// 	var $newContent = $(`
	// 			<div class="row">
	// 				<div class="col-sm-4"></div>
	// 				<div class="col-sm-8"></div>
	// 			</div>
	// 	`);
	// 	$newContent.find('.col-sm-4').append($legend);
	// 	$newContent.find('.col-sm-8').append($felement);
	// 	var elements = $felement.find('.fdate_selector').contents().filter(function() {
	// 	    if (this.nodeType === 3) {
	// 	    	$(this).remove();
	// 	    } 
	// 	    return false;
	// 	});
	// 	$bottomContainer.html($newContent);

	// 	$lastContainer = $('#fgroup_id_course_completion_history-course_completion_history_count_grp');
	// 	var $legend = $lastContainer.find('legend');
	// 	var $select = $lastContainer.find('select');
	// 	var $input = $lastContainer.find('input');
	// 	$lastContainer.html(`
	// 			<div class="row">
	// 				<div class="col-sm-4">${$('<div>').append($legend.clone()).html()}</div>
	// 				<div class="col-sm-4">${$('<div>').append($select.clone()).html()}</div>
	// 				<div class="col-sm-4">${$('<div>').append($input.clone()).html()}</div>
	// 			</div>
	// 	`);

	// 	$('#id_newfilterstandard').addClass('collapsed');
	// },

	prepareElements: function() {
		setTimeout(function() {
			$('.moreless-toggler')
				.addClass('btn')
				.css('display', 'inline-block')
				.show();
		}, 1000);
	},

	prepareModal: function() {

	}
}