// Standard license block omitted.
/*
 * @package    block_compliance_training
 * @copyright  2019 Yashco Systems
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
 /**
  * @module block_compliance_training/usermapping
  */

define(['jquery'], function($, c) {
	
       return {
        init: function (subcategory) {
          var i=0;
          $.each(JSON.parse(subcategory), function(idx, obj) {
          var comp = obj.comp;
          if(comp == 0) {
              var noncomp = 1;
              var myConfig = {
backgroundColor:'#FBFCFE',
  type: "ring",
  title: {
    text: obj.name,
    fontFamily: 'Lato',
    fontSize:14, 
    // border: "1px solid black",
    padding: "20 10 30 10",
    fontColor : "#1E5D9E",
  },
  subtitle: {
    text: "",
    fontFamily: 'Lato',
    fontSize: 14,
    fontColor: "#000",
    padding: "5"
  },
  plot: {
    slice:'40%',
    borderWidth:0,
    backgroundColor:'#FBFCFE',
    animation:{
      effect:2,
      sequence:3
    },
    valueBox: [
      {
        type: 'all',
        text: '%t',
        placement: 'out'
      }, 
      {
        type: 'all',
        text: '%npv%',
        placement: 'in'
      }
    ]
  },
  tooltip:{
      fontSize:5,
      anchor:'c',
      x:'50%',
      y:'50%',
      sticky:true,
      backgroundColor:'none',
      borderWidth:0,
      thousandsSeparator:',',
      text:'',
      mediaRules:[
        {
            maxWidth:500,
            y:'54%',
        }
      ]
  },
  plotarea: {
    backgroundColor: 'transparent',
    borderWidth: 0,
    borderRadius: "0 0 0 2",
    margin: "4 0 0 0"
  },
  legend : {
    toggleAction:'remove',
    backgroundColor:'#FBFCFE',
    borderWidth:0,
    adjustLayout:true,
    align:'center',
    verticalAlign:'bottom',
    marker: {
        type:'circle',
        cursor:'pointer',
        borderWidth:0,
        size:6
    },
    item: {
        fontColor: "#000",
        cursor:'pointer',
        offsetX:-6,
        fontSize:5
    },
    mediaRules:[
        {
            maxWidth:500,
            visible:false
        }
    ]
  },
  scaleR:{
    refAngle:90
  },
  series : [
    {
      text: "",
      fontSize:5,
      values : [noncomp],
      lineColor: "#E80C60",
      backgroundColor: "#E80C60",
      lineWidth: 1,
      marker: {
        backgroundColor: '#E80C60'
      }
    }
  ]
};
} else {
     var noncomp = obj.noncomp;
      if(noncomp == 0) {
      var myConfig = {
backgroundColor:'#FBFCFE',
  type: "ring",
  title: {
    text: obj.name,
    fontFamily: 'Lato',
    fontSize: 5, 
    // border: "1px solid black",
    padding: "20 10 30 10",
    fontColor : "#1E5D9E",
  },
  subtitle: {
    text: "",
    fontFamily: 'Lato',
    fontSize: 5,
    fontColor: "#000",
    padding: "5"
  },
  plot: {
    slice:'40%',
    borderWidth:0,
    backgroundColor:'#FBFCFE',
    animation:{
      effect:2,
      sequence:3
    },
    valueBox: [
      {
        type: 'all',
        text: '%t',
        placement: 'out'
      }, 
      {
        type: 'all',
        text: '%npv%',
        placement: 'in'
      }
    ]
  },
  tooltip:{
      fontSize:5,
      anchor:'c',
      x:'50%',
      y:'50%',
      sticky:true,
      backgroundColor:'none',
      borderWidth:0,
      thousandsSeparator:',',
      text:'',
      mediaRules:[
        {
            maxWidth:500,
            y:'54%',
        }
      ]
  },
  plotarea: {
    backgroundColor: 'transparent',
    borderWidth: 0,
    borderRadius: "0 0 0 2",
    margin: "4 0 0 0"
  },
  legend : {
    toggleAction:'remove',
    backgroundColor:'#FBFCFE',
    borderWidth:0,
    adjustLayout:true,
    align:'center',
    verticalAlign:'bottom',
    marker: {
        type:'circle',
        cursor:'pointer',
        borderWidth:0,
        size:6
    },
    item: {
        fontColor: "#000",
        cursor:'pointer',
        offsetX:-6,
        fontSize:5
    },
    mediaRules:[
        {
            maxWidth:500,
            visible:false
        }
    ]
  },
  scaleR:{
    refAngle:90
  },
  series : [
    {
      text: "",
      values : [comp],
      lineColor: "#00BAF2",
      backgroundColor: "#00BAF2",
      lineWidth: 1,
      marker: {
        backgroundColor: '#00BAF2'
      }
    }
  ]
};
              } else {
              var myConfig = {
backgroundColor:'#FBFCFE',
  type: "ring",
  title: {
    text: obj.name,
    fontFamily: 'Lato',
    fontSize: 5, 
    // border: "1px solid black",
    padding: "20 10 30 10",
    fontColor : "#1E5D9E",
  },
  subtitle: {
    text: "",
    fontFamily: 'Lato',
    fontSize: 5,
    fontColor: "#000",
    padding: "5"
  },
  plot: {
    slice:'40%',
    borderWidth:0,
    backgroundColor:'#FBFCFE',
    animation:{
      effect:2,
      sequence:3
    },
    valueBox: [
      {
        type: 'all',
        text: '%t',
        placement: 'out'
      }, 
      {
        type: 'all',
        text: '%npv%',
        placement: 'in'
      }
    ]
  },
  tooltip:{
      fontSize:5,
      anchor:'c',
      x:'50%',
      y:'50%',
      sticky:true,
      backgroundColor:'none',
      borderWidth:0,
      thousandsSeparator:',',
      text:'',
      mediaRules:[
        {
            maxWidth:500,
            y:'54%',
        }
      ]
  },
  plotarea: {
    backgroundColor: 'transparent',
    borderWidth: 0,
    borderRadius: "0 0 0 2",
    margin: "4 0 0 0"
  },
  legend : {
    toggleAction:'remove',
    backgroundColor:'#FBFCFE',
    borderWidth:0,
    adjustLayout:true,
    align:'center',
    verticalAlign:'bottom',
    marker: {
        type:'circle',
        cursor:'pointer',
        borderWidth:0,
        size:6
    },
    item: {
        fontColor: "#000",
        cursor:'pointer',
        offsetX:-6,
        fontSize:5
    },
    mediaRules:[
        {
            maxWidth:500,
            visible:false
        }
    ]
  },
  scaleR:{
    refAngle:90
  },
  series : [
    {
      text: "",
      values : [comp],
      lineColor: "#00BAF2",
      backgroundColor: "#00BAF2",
      lineWidth: 1,
      marker: {
        backgroundColor: '#00BAF2'
      }
    },
    {
      text: "",
      values : [noncomp],
      lineColor: "#E80C60",
      backgroundColor: "#E80C60",
      lineWidth: 1,
      marker: {
        backgroundColor: '#E80C60'
      }
    }
  ]
};
}
}
        
zingchart.render({ 
	id : 'myChart'+i, 
  data: {
    gui:{
      contextMenu:{
      
        position: "right",
        backgroundColor:"#306EAA", /*sets background for entire contextMenu*/
        docked: true, 
        item:{
          backgroundColor:"#306EAA",
          borderColor:"#306EAA",
          borderWidth: 0,
          fontFamily: "Lato",
          color:"#000"
        }
      
      },
    },
    graphset: [myConfig]
  },
  button: 'test123',
	height: '250', 
	width: '100%' 
});

i++;
}); //each end

}


};
});

$('.radiocontent').hide();
$(".radioButton").change(function() {
       $('.radiocontent').hide()
       $('#'+this.dataset.show).show()
})
