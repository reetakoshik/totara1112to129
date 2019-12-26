$(document).ready(function() {  
    $('.section-collapser').closest('.content').find('ul.section').hide();

    $('.section-collapser').click(function(e) {
        var $collapser = $(e.target);
        var $sectionList = $(e.target).closest('.content').find('ul.section');
        if ($collapser.text() == '-') {
            $sectionList.hide();
            $collapser.text('+');
        } else {
            $sectionList.show();
            $collapser.text('-');
        }
    });

    var $section0 = $('#section-0');
    var $activities = $section0.find('li.activity');
    if ($activities.length === 0 && !$(document.body).hasClass('editing')) {
        $section0.remove();
    }
})