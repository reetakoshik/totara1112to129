function Layout() {
	
}

Layout.prototype = {
	init: function() {
				
		var $totaraMyBlockLeft = $('#block-region-second-left .block_totara_my');
		var $totaraMyBlockRight = $('#block-region-second-right .block_totara_my');
		if ($totaraMyBlockLeft.length || $totaraMyBlockRight.length) {
			this.defineBlockMinHeightCssClass();
		}
		this.modifyLastCourseAccessedBlock();
		this.myTeamTableLayoutFix();
		this.myTeamTableImageFix();
		this.programFix();
		this.progressFix();
		this.progCertTableFix();
		this.fixCurrentLearningBlockInIE();
	},

	defineBlockMinHeightCssClass: function() {
		try {
			var style = document.createElement('style');
			style.type = 'text/css';
			style.innerHTML = 
				'#block-region-second-left .block .content,'+
				'#block-region-second-right .block .content {'+
				   'min-height: 341px;'+
				'}'+
				'#block-region-second-left .block .header + .content,'+
				'#block-region-second-right .block .header + .content {'+
				  'min-height: 267px;'+
				'}'
			;
			document.getElementsByTagName('head')[0].appendChild(style);
		} catch (e) {
			console.log(e);
		}
	},

	modifyLastCourseAccessedBlock: function() {
		try {
			var $block = $('.block_last_course_accessed.block');
			var $content = $block.find('.content');
			var $link = $block.find('.course_name_large a').attr('href');
			var linkText = M.util.get_string('block_last_course_accessed_resume', 'theme_golearningzone');
			$content.append(
				'<div class="text-center block_last_course_accessed_theme_additional">'+
					'<a href="'+$link+'" class="btn">'+linkText+'</a>'+
				'</div>'
			);
		} catch (e) {
			console.log(e);
		}
	},

    fixCurrentLearningBlockInIE: function() {
		var clb = $('.block_current_learning');

		if(clb.length && ieDetected()) {
            try {
            	// Make 'expand' buttons visible at initialization
                clb.find('.collapsed-icon').each(function () {
					$(this).show();
                });
				// Toggle 'expand' button depending on content visibility
            	$('.expand-collapse-icon-wrap').on("click", function () {
					if($($(this).attr('data-target')).is(':visible')) {
                        $(this).find('.collapsed-icon').show();
					} else {
                        $(this).find('.collapsed-icon').hide();
					}
                });
            } catch (e) {
                console.log(e);
            }
		}
    },

	myTeamTableLayoutFix: function() {
		try {
			var $td = $('#page-my-teammembers table#team_members td.user_namewithlinks');
			$td.each(function(i, td) {
				var $name = $(td).find('a.name');
				var $links = $(td).find('ul');
				var $wrapper = $('<div class="text-wrapper pull-left"></div>');
				$(td).append($wrapper);
				$wrapper.append($name);
				$wrapper.append($links);
			});
		} catch (e) {
			console.log(e);
		}
	},

	myTeamTableImageFix: function() {
		try {
			var $td = $('#page-my-teammembers table#team_members td.user_namewithlinks');
			if ($td.length === 0) {
				return;
			}
			$td.each(function(i, td) {
				var $img = $(td).find('img');
				var src = $img.attr('src');
				var newSrc = src.replace('\/f2', '\/f1');
				$img.attr('src', newSrc);
			});
		} catch (e) {
			console.log(e);
		}
	},

	programFix: function() {
		var $coreProgressBars = $('.totara_progress_bar_medium');
		$coreProgressBars.each(function(n, coreProgressBar) {
			try {
				var $coreProgressBar = $(coreProgressBar);
				var title = $coreProgressBar.attr('title');
				var status = '';
				var style = '';
				if (title === '0%') {
					status = 'completion-notyetstarted';
				} else if (title === '100%') {
					status = 'completion-complete';
				} else {
					status = 'completion-inprogress with-percents';
					var percents = parseInt(title);
					var progressBarWidthPx = 92;
					var offset = Math.floor(progressBarWidthPx * percents / 100);
					var margin = $('body.dir-ltr').length === 1 ? 'margin-left' : 'margin-right';
					style = 'style="'+margin+':'+offset+'px;"';
				}

				if (status) {
					var $newProgresBar = $(
						'<span class="coursecompletionstatus">'+
			        		'<span class="'+status+'" title="'+title+'">'+title+''+
			        			'<div class="hover" '+style+'"></div>'+
			        		'</span>'+
			    		'</span>'
			    	);
			    	$coreProgressBar.replaceWith($newProgresBar);
				} else {
					$coreProgressBar.show();
				}
				$coreProgressBar.show();
			} catch (e) {
				console.log(e);
			}
		});
	},

	progressFix: function() {
		var percents = 50;
		var progressBarWidthPx = 92;
		var offset = Math.floor(progressBarWidthPx * percents / 100);
		var margin = $('body.dir-ltr').length === 1 ? 'margin-left' : 'margin-right';
		var style = 'style="'+margin+':'+offset+'px;"';

		$('.coursecompletionstatus.completion-complete, .coursecompletionstatus.completion-completeviarpl').html(
			'<span class="coursecompletionstatus">'+
		        '<span class="completion-complete">complete</span>'+
		    '</span>'
		).removeClass('completion-complete');

		$('.completion-inprogress').html(
			'<span class="coursecompletionstatus">'+
		        '<span class="completion-inprogress with-percents">inprogress'+
		        	'<div class="hover" '+style+'"></div>'+
		        '</span>'+
		    '</span>'
		).removeClass('completion-inprogress');

		$('.coursecompletionstatus.completion-notyetstarted').html(
			'<span class="coursecompletionstatus">'+
		        '<span class="completion-notyetstarted">notyetstarted</span>'+
		    '</span>'
		).removeClass('completion-notyetstarted');
	},
	
	progCertTableFix: function () {
		var table = $('table.generaltable');
		if(table.length) {
			table.wrap( "<div class='pc-table-wrapper'></div>" );
		}
    }
}

$(document).ready(function() {
	//$(".navbar-toggle.collapsed").click(function() {
		//$(".totara-navbar.navbar-collapse.collapse").removeAttr('class');
		//$(".totara-navbar.navbar-collapse.collapse").attr('class','totara-navbar navbar-collapse collapse.open');
		//$(this).addClass('tog-cancel');
	//});
	$('#glz_collapsebtn').click(function() {//alert('test123');
		$(".totara-navbar.navbar-collapse.collapse").attr('class','totara-navbar navbar-collapse collapse.open');
		//$(".totara-navbar.navbar-collapse.collapse").removeClass('.open');
		//$(".totara-navbar.navbar-collapse.collapse").addClass('totara-navbar navbar-collapse collapse');
		$(".container.container-fluid.text-left").toggle();
	});
	/*menu-btn-wrapper*/
   


});