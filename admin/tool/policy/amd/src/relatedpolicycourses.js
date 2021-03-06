define(['jquery', 'core/ajax', 'core/modal_factory', 'core/modal_events', 'core/templates'], function($, ajax, ModalFactory, ModalEvents, Templates) {
  return {
        init: function() {
            /*Ajax.call([{
                methodname: 'core_group_submit_create_group_form',
                args: {contextid: this.contextid, jsonformdata: JSON.stringify(formData)},
                done: this.handleFormSubmissionResponse.bind(this, formData),
                fail: this.handleFormSubmissionFailure.bind(this, formData)
            }]);*/
            var wwwroot = M.cfg.wwwroot;
            
                ajax.call([{
                    methodname: 'tool_policy_get_related_pcourses',
                    args: {},
                    done: (function(response) {
                        var res1 = JSON.stringify(response);
                        //var res2 = {categories:[{catid:1,catname:"Miscellaneous",courses:[{cid:2,fullname:"Test demo course 1"},{cid:3,fullname:"Test demo course 2"}]},{catid:2,catname:"Information Technology",courses:[{cid:4,fullname:"JAVA"}]},{catid:3,catname:"Web Application",courses:[{cid:5,fullname:"Moodle"},{cid:6,fullname:"Hypertext Preprocessor"}]}]};
                        var res2 = JSON.parse(res1);

            var trigger = $('#create-modal');
            //trigger.click(function() {
            
                ModalFactory.create({
                  type: ModalFactory.types.SAVE_CANCEL,
                  title: '',
                  body: Templates.render('tool_policy/modal_related_pcourses',res2, {}),
                }, trigger)
                .then(function(modal) {
		            var root = modal.getRoot();
		            root.on(ModalEvents.cancel, function() {
		                $("#pcourseids").val("");
		                $("#citemstoadd").html("");
		               
		            });
				});
                    }),
                    fail: (function(ex) {

                    })
                }]);

        }
  };

});

/*
<button type="button" class="btn btn-primary" data-action="save">Save</button><button type="button" class="btn btn-secondary" data-action="cancel">Cancel</button>*/
