// Standard license block omitted.
/*
 * @package    block_metrics_compliance
 * @copyright  2018 Yashco Systems
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
 /**
  * @module block_metrics_compliance/compliance_report
  */
define(['jquery'], function($, c) {


		
		
       return {
        init: function (noncomppercent = 0, expirepercent = 0, comlpercent = 0) {
           $("#noncomppercent").html(noncomppercent+"%");
           $("#expirepercent").html(expirepercent+"%");
           $("#comlpercent").html(comlpercent+"%");
           
           // First Odometer
           var value3 = noncomppercent;
           var Noncompliance = {
			  "type":"gauge",
			  "scale-r":{
			    "aperture":250,
			    "values":"0:100:10",
			    "center":{
			      "size":5,
			 	    "background-color":"#66CCFF #FFCCFF",
			 	    "border-color":"none"
			    },
			    "ring":{  //Ring with Ruleshttp://115.166.143.10:81/time_management/
			      "size":10,
			      "rules":[
			        {
			          "rule":"%v >= 0 && %v <=10",
			          "background-color":"green"
			        },
			        {
			          "rule":"%v >= 11 && %v <= 20",
			 	        "background-color":"green"
			        },

			        {
			          "rule":"%v >= 21 && %v <= 30",
			 	        "background-color":"#008000"
			        },
			        {
			          "rule":"%v >= 31 && %v <= 40",
			 	        "background-color":"#418D41"
			        },
			        {
			          "rule":"%v >= 41 && %v <= 50",
			 	        "background-color":"#FF9B79"
			        },
			        {
			          "rule":"%v >= 51 && %v <=60",
			          "background-color":"#FF784A"
			        },
			        {
			          "rule":"%v >= 61 && %v <= 70",
			 	        "background-color":"#FF4000"
			        },
			         {
			          "rule":"%v >= 71 && %v <= 80",
			 	        "background-color":"#FF2222"
			        },
			        {
			          "rule":"%v >= 81 && %v <= 90",
			 	        "background-color":"#FF0000"
			        },

			        {
			          "rule":"%v >= 91 && %v <= 100",
			 	        "background-color":"#E10000"
			        }
			      ]
			    }
			  },
			  "plot":{
			    "csize":"5%",
			    "size":"100%",
			    "background-color":"#000000"
			  },
			  "series":[
			    {"values":[value3]}
			  ]
			};

			zingchart.render({
			id: 'chartdiv',
			data: Noncompliance,
			height: "260px",
			width: "100%"
			});

			// Second Odometer
			var value2 = expirepercent;
			var Expire = {
			  "type":"gauge",
			  "scale-r":{
			    "aperture":250,
			    "values":"0:100:10",
			    "center":{
			      "size":5,
			 	    "background-color":"#FF0000 #FF0000",
			 	    "border-color":"none"
			    },
			    "ring":{  //Ring with Rules
			      "size":10,
			      "rules":[
			        {
			          "rule":"%v >= 0 && %v <=10",
			          "background-color":"green"
			        },
			        {
			          "rule":"%v >= 11 && %v <= 20",
			 	        "background-color":"green"
			        },

			        {
			          "rule":"%v >= 21 && %v <= 30",
			 	        "background-color":"#008000"
			        },
			        {
			          "rule":"%v >= 31 && %v <= 40",
			 	        "background-color":"#418D41"
			        },
			        {
			          "rule":"%v >= 41 && %v <= 50",
			 	        "background-color":"#FF9B79"
			        },
			        {
			          "rule":"%v >= 51 && %v <=60",
			          "background-color":"#FF784A"
			        },
			        {
			          "rule":"%v >= 61 && %v <= 70",
			 	        "background-color":"#FF4000"
			        },
			         {
			          "rule":"%v >= 71 && %v <= 80",
			 	        "background-color":"#FF2222"
			        },
			        {
			          "rule":"%v >= 81 && %v <= 90",
			 	        "background-color":"#FF0000"
			        },

			        {
			          "rule":"%v >= 91 && %v <= 100",
			 	        "background-color":"#E10000"
			        }
			      ]
			    }
			  },
			  "plot":{
			    "csize":"5%",
			    "size":"100%",
			    "background-color":"#000000"
			  },
			  "series":[
			    {"values":[value2]}
			  ]
			};

			zingchart.render({
			id: 'chartdiv2',
			data: Expire,
			height: "260px",
			width: "100%"
			});

			// Third Odometer
			var value1 = comlpercent;
			var Compliance = {
			"type":"gauge",
			  "scale-r":{
			    "aperture":250,
			    "values":"0:100:10",
			    "center":{
			      "size":0.5,
			 	    "background-color":"#66CCFF #FFCCFF",
			 	    "border-color":"none"
			    },
			    "ring":{  //Ring with Rules
			      "size":10,
			      "rules":[
			        {
			          "rule":"%v >= 0 && %v <= 10",
			 	        "background-color":"#E10000"
			        },
			        {
			          "rule":"%v >= 11 && %v <= 20",
			 	        "background-color":"#FF0000"
			        },
			        {
			          "rule":"%v >= 21 && %v <= 30",
			 	        "background-color":"#FF2222"
			        },
			        {
			          "rule":"%v >= 31 && %v <= 40",
			 	        "background-color":"#FF4000"
			        },
			        {
			          "rule":"%v >= 41 && %v <=50",
			          "background-color":"#FF784A"
			        },
			        {
			          "rule":"%v >= 51 && %v <= 60",
			 	        "background-color":"#FF9B79"
			        },
			        {
			          "rule":"%v >= 61 && %v <= 70",
			 	        "background-color":"#418D41"
			        },
			        {
			          "rule":"%v >= 71 && %v <= 80",
			 	        "background-color":"#008000"
			        },
			        {
			          "rule":"%v >= 81 && %v <= 90",
			 	        "background-color":"green"
			        },
			        {
			          "rule":"%v >= 91 && %v <=100",
			          "background-color":"green"
			        }
			      ]
			    }
			  },
			  "plot":{
			    "csize":"5%",
			    "size":"100%",
			    "background-color":"#000000"
			  },
			  "series":[
			    {"values":[value1]}
			  ]
			};

			zingchart.render({
			id: 'chartdiv3',
			data: Compliance,
			height: "260px",
			width: "100%"
			});

	    }

    };
});