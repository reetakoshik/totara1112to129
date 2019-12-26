// Standard license block omitted.
/*
 * @package    block_compliance_training
 * @copyright  2019 Yashco Systems
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
 /**
  * @module block_compliance_training/questionnairesett
  */
define(['jquery'], function($, c) {
	
       return {
        init: function () {
          
        }
    };
});

function changequestionnaire(quesid) {
    $("#quesid").val(quesid);
    var params = { quesid:quesid };
    var str = jQuery.param( params );
    //var tech = getUrlParameter('technology');
    var a = window.location.pathname + "?"+str;
    window.location.replace(a);
    //location.reload();
    window.open(a,'target="_blank"');
}