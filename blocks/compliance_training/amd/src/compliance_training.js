// Standard license block omitted.
/*
 * @package    block_compliance_training
 * @copyright  2019 Yashco Systems
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
 /**
  * @module block_compliance_training/compliance_training
  */
define(['jquery'], function($, c) {
	
       return {
        init: function () {

          
          $(".poscertif").click(function() {
            var details = $(this).attr('id');
            
            if($(this). prop("checked") == true){
        var dvalue = 1;
      }
      else if($(this). prop("checked") == false){
        var dvalue = 0;
      }
            
      var wwwroot = M.cfg.wwwroot;
      var saveData = $.ajax({
        type: 'POST',
        url: wwwroot + "/blocks/compliance_training/savepostraningcert.php",
        data: {details:details, dvalue:dvalue},
        dataType: "text",
        success: function(resultData) { alert("Save Complete") }
      });
      saveData.error(function() { alert("Something went wrong"); });

           }); 


           $(".orgcertif").click(function() {
           	var details = $(this).attr('id');
           	
           	if($(this). prop("checked") == true){
				var dvalue = 1;
			}
			else if($(this). prop("checked") == false){
				var dvalue = 0;
			}
           	
      var wwwroot = M.cfg.wwwroot;
			var saveData = $.ajax({
				type: 'POST',
				url: wwwroot + "/blocks/compliance_training/savecomptrainingrec.php",
				data: {details:details, dvalue:dvalue},
				dataType: "text",
				success: function(resultData) { alert("Save Complete") }
			});
			saveData.error(function() { alert("Something went wrong"); });

           });    
        }
    };
});


$(document).ready(function() {
   //$('[data-toggle="tooltip"]').tooltip(); 
   $(".certiftooltip").mouseover(function() {
     var certifid = $(this).attr('id');
    $(this).tooltip(); 
   });
});
/*
require(['core/ajax'], function(ajax) {
    /*var promises = ajax.call([
        { methodname: 'core_get_string', args: { component: 'mod_wiki', stringid: 'pluginname' } },
        { methodname: 'core_get_string', args: { component: 'mod_wiki', stringid: 'changerate' } }
    ]);
	
   promises[0].done(function(response) {
       console.log('mod_wiki/pluginname is' + response);
   }).fail(function(ex) {
       alert('test12345');
   });
 
   promises[1].done(function(response) {
       console.log('mod_wiki/changerate is' + response);
   }).fail(function(ex) {
       alert('test11111111111');
   });
   ajax.call([{
            methodname: 'block_compliance_training',
            args: {assignmentid: assignmentid, userid: this._lastUserId, jsonformdata: JSON.stringify(data)},
            done: this._handleFormSubmissionResponse.bind(this, data, nextUserId),
            fail: notification.exception
        }]);
});*/