define(['jquery', 'core/ajax', 'core/modal_factory', 'core/modal_events', 'core/templates'], function($, ajax, ModalFactory, ModalEvents, Templates) {
  return {
        init: function() {
            var wwwroot = M.cfg.wwwroot;
            
                ajax.call([{
                    methodname: 'tool_policy_get_related_audiences',
                    args: {},
                    done: (function(response) {
                        var res1 = JSON.stringify(response);
                        //var res2 = {categories:[{catid:1,catname:"Miscellaneous",courses:[{cid:2,fullname:"Test demo course 1"},{cid:3,fullname:"Test demo course 2"}]},{catid:2,catname:"Information Technology",courses:[{cid:4,fullname:"JAVA"}]},{catid:3,catname:"Web Application",courses:[{cid:5,fullname:"Moodle"},{cid:6,fullname:"Hypertext Preprocessor"}]}]};
                        var res2 = JSON.parse(res1);

            var trigger = $('#create-modal-audiences');
            //trigger.click(function() {
            
                ModalFactory.create({
                  type: ModalFactory.types.SAVE_CANCEL,
                  title: '',
                  body: Templates.render('tool_policy/modal_related_paudiences',res2, {}),
                }, trigger)
                .then(function(modal) {
		            var root = modal.getRoot();
		            root.on(ModalEvents.cancel, function() {
		                $("#audiencesid").val("");
		                $("#auditemstoadd").html("");
		               
		            });
				});
                    }),
                    fail: (function(ex) {

                    })
                }]);

        }
  };

});

