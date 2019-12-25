YUI.add("moodle-core_availability-form",function(e,t){M.core_availability=M.core_availability||{},M.core_availability.form={plugins:{},field:null,mainDiv:null,rootList:null,idCounter:0,restrictByGroup:null,init:function(t){for(var n in t){var r=t[n],i=M[r[0]].form;i.init.apply(i,r)}this.field=e.one("#id_availabilityconditionsjson"),this.field.setStyle("display","none"),this.mainDiv=e.Node.create('<div class="availability-field fcontainer"></div>'),this.field.insert(this.mainDiv,"after");var s=this.field.get("value"),o=null;if(s!=="")try{o=e.JSON.parse(s)}catch(u){this.field.set("value","")}this.rootList=new M.core_availability.List(o,!0),this.mainDiv.appendChild(this.rootList.node),this.update(),this.rootList.renumber(),this.mainDiv.setAttribute("aria-live","polite"),this.field.ancestor("form").on("submit",function(){this.mainDiv.all("input,textarea,select").set("disabled",!0)},this),this.restrictByGroup=e.one("#restrictbygroup");if(this.restrictByGroup){this.restrictByGroup.on("click",this.addRestrictByGroup,this);var a=e.one("#id_groupmode"),f=e.one("#id_groupingid");a&&a.on("change",this.updateRestrictByGroup,this),f&&f.on("change",this.updateRestrictByGroup,this),this.updateRestrictByGroup()}},update:function(){var t=this.rootList.getValue(),n=[];this.rootList.fillErrors(n),n.length!==0&&(t.errors=n),this.field.set("value",e.JSON.stringify(t)),this.updateRestrictByGroup()},updateRestrictByGroup:function(){if(!this.restrictByGroup)return;if(this.rootList.getValue().op!=="&"){this.restrictByGroup.set("disabled",!0);return}var t=this.rootList.hasItemOfType("group")||this.rootList.hasItemOfType("grouping");if(t){this.restrictByGroup.set("disabled",!0);return}var n=e.one("#id_groupmode"),r=e.one("#id_groupingid");if((!n||Number(n.get("value"))===0)&&(!r||Number(r.get("value"))===0)){this.restrictByGroup.set("disabled",!0);return}this.restrictByGroup.set("disabled",!1)},addRestrictByGroup:function(t){t.preventDefault();var n=e.one("#id_groupingid"),r;n&&Number(n.get("value"))!==0?r=new M.core_availability.Item({type:"grouping",id:Number(n.get("value"))},!0):r=new M.core_availability.Item({type:"group"},!0),this.rootList.addChild(r),this.update(),this.rootList.renumber(),this.rootList.updateHtml()}},M.core_availability.plugin={allowAdd:!1,init:function(e,t,n){var r=e.replace(/^availability_/,"");this.allowAdd=t,M.core_availability.form.plugins[r]=this,this.initInner.apply(this,n)},initInner:function(){},getNode:function(){throw"getNode not implemented"},fillValue:function(){throw"fillValue not implemented"},fillErrors:function(){},focusAfterAdd:function(e){var t=e.one("input:not([disabled]),select:not([disabled])");t.focus()}},M.core_availability.List=function(t,n,r){this.children=[],n!==undefined&&(this.root=n),this.node=e.Node.create('<div class="availability-list"><h3 class="accesshide"></h3><div class="availability-inner"><div class="availability-header"><span class="p-l-1">'+M.util.get_string("listheader_sign_before","availability")+"</span>"+' <label><span class="accesshide">'+M.util.get_string("label_sign","availability")+' </span><select class="availability-neg custom-select m-x-1"'+' title="'+M.util.get_string("label_sign","availability")+'">'+'<option value="">'+M.util.get_string("listheader_sign_pos","availability")+"</option>"+'<option value="!">'+M.util.get_string("listheader_sign_neg","availability")+"</option></select></label> "+'<span class="availability-single">'+M.util.get_string("listheader_single","availability")+"</span>"+'<span class="availability-multi">'+M.util.get_string("listheader_multi_before","availability")+' <label><span class="accesshide">'+M.util.get_string("label_multi","availability")+" </span>"+'<select class="availability-op custom-select m-x-1"'+' title="'+M.util.get_string("label_multi","availability")+'"><option value="&">'+M.util.get_string("listheader_multi_and","availability")+"</option>"+'<option value="|">'+M.util.get_string("listheader_multi_or","availability")+"</option></select></label> "+M.util.get_string("listheader_multi_after","availability")+"</span></div>"+'<div class="availability-children"></div>'+'<div class="availability-none"><span class="p-x-1">'+M.util.get_string("none","moodle")+"</span></div>"+'<div class="clearfix m-t-1"></div>'+'<div class="availability-button"></div></div><div class="clearfix"></div></div>'),n||this.node.addClass("availability-childlist"),this.inner=this.node.one("> .availability-inner");var i=!0;n?(t&&t.show!==undefined&&(i=t.show),this.eyeIcon=new M.core_availability.EyeIcon(!1,i),this.node.one(".availability-header").get("firstChild").insert(this.eyeIcon.span,"before")):r&&(t&&t.showc!==undefined&&(i=t.showc),this.eyeIcon=new M.core_availability.EyeIcon(!1,i),this.inner.insert(this.eyeIcon.span,"before"));if(!n){var s=new M.core_availability.DeleteIcon(this),o=this.node.one(".availability-none");o.appendChild(document.createTextNode(" ")),o.appendChild(s.span),o.appendChild(e.Node.create('<span class="m-t-1 label label-warning">'+M.util.get_string("invalid","availability")+"</span>"))}var u=e.Node.create('<button type="button" class="btn btn-default m-t-1">'+M.util.get_string("addrestriction","availability")+"</button>");u.on("click",function(){this.clickAdd()},this),this.node.one("div.availability-button").appendChild(u);if(t){switch(t.op){case"&":case"|":this.node.one(".availability-neg").set("value","");break;case"!&":case"!|":this.node.one(".availability-neg").set("value","!")}switch(t.op){case"&":case"!&":this.node.one(".availability-op").set("value","&");break;case"|":case"!|":this.node.one(".availability-op").set("value","|")}for(var a=0;a<t.c.length;a++){var f=t.c[a];this.root&&t&&t.showc!==undefined&&(f.showc=t.showc[a]);var l;f.type!==undefined?l=new M.core_availability.Item(f,this.root):l=new M.core_availability.List(f,!1,this.root),this.addChild(l)}}this.node.one(".availability-neg").on("change",function(){M.core_availability.form.update(),this.updateHtml()},this),this.node.one(".availability-op"
).on("change",function(){M.core_availability.form.update(),this.updateHtml()},this),this.updateHtml()},M.core_availability.List.prototype.addChild=function(t){this.children.length>0&&this.inner.one(".availability-children").appendChild(e.Node.create('<div class="availability-connector"><span class="label"></span></div>')),this.children.push(t),this.inner.one(".availability-children").appendChild(t.node)},M.core_availability.List.prototype.focusAfterAdd=function(){this.inner.one("button").focus()},M.core_availability.List.prototype.isIndividualShowIcons=function(){if(!this.root)throw"Can only call this on root list";var e=this.node.one(".availability-neg").get("value")==="!",t=this.node.one(".availability-op").get("value")==="|";return!e&&!t||e&&t},M.core_availability.List.prototype.renumber=function(e){var t={count:this.children.length},n;e===undefined?(t.number="",n=""):(t.number=e+":",n=e+".");var r=M.util.get_string("setheading","availability",t);this.node.one("> h3").set("innerHTML",r);for(var i=0;i<this.children.length;i++){var s=this.children[i];s.renumber(n+(i+1))}},M.core_availability.List.prototype.updateHtml=function(){this.children.length>0?(this.inner.one("> .availability-children").setStyle("display",null),this.inner.one("> .availability-none").setStyle("display","none"),this.inner.one("> .availability-header").setStyle("display",null),this.children.length>1?(this.inner.one(".availability-single").setStyle("display","none"),this.inner.one(".availability-multi").setStyle("display",null)):(this.inner.one(".availability-single").setStyle("display",null),this.inner.one(".availability-multi").setStyle("display","none"))):(this.inner.one("> .availability-children").setStyle("display","none"),this.inner.one("> .availability-none").setStyle("display",null),this.inner.one("> .availability-header").setStyle("display","none"));if(this.root){var e=this.isIndividualShowIcons();for(var t=0;t<this.children.length;t++){var n=this.children[t];e?n.eyeIcon.span.setStyle("visibility",null):n.eyeIcon.span.setStyle("visibility","hidden")}e?this.eyeIcon.span.setStyle("visibility","hidden"):this.eyeIcon.span.setStyle("visibility",null)}var r;this.inner.one(".availability-op").get("value")==="&"?r=M.util.get_string("and","availability"):r=M.util.get_string("or","availability"),this.inner.all("> .availability-children > .availability-connector span.label").each(function(e){e.set("innerHTML",r)})},M.core_availability.List.prototype.deleteDescendant=function(e){for(var t=0;t<this.children.length;t++){var n=this.children[t];if(n===e){this.children.splice(t,1);var r=n.node;return this.children.length>0&&(r.previous(".availability-connector")?r.previous(".availability-connector").remove():r.next(".availability-connector").remove()),this.inner.one("> .availability-children").removeChild(r),M.core_availability.form.update(),this.updateHtml(),this.inner.one("> .availability-button").one("button").focus(),!0}if(n instanceof M.core_availability.List){var i=n.deleteDescendant(e);if(i)return!0}}return!1},M.core_availability.List.prototype.clickAdd=function(){var t=e.Node.create('<div><ul class="list-unstyled container-fluid"></ul><div class="availability-buttons mdl-align"><button type="button" class="btn btn-default">'+M.util.get_string("cancel","moodle")+"</button></div></div>"),n=t.one("button"),r={dialog:null},i=t.one("ul"),s,o,u,a;for(var f in M.core_availability.form.plugins){if(!M.core_availability.form.plugins[f].allowAdd)continue;s=e.Node.create('<li class="clearfix row"></li>'),o="availability_addrestriction_"+f,u=e.Node.create('<button type="button" class="btn btn-default col-xs-5"id="'+o+'">'+M.util.get_string("title","availability_"+f)+"</button>"),u.on("click",this.getAddHandler(f,r),this),s.appendChild(u),a=e.Node.create('<label for="'+o+'" class="col-xs-7">'+M.util.get_string("description","availability_"+f)+"</label>"),s.appendChild(a),i.appendChild(s)}s=e.Node.create('<li class="clearfix row"></li>'),o="availability_addrestriction_list_",u=e.Node.create('<button type="button" class="btn btn-default col-xs-5"id="'+o+'">'+M.util.get_string("condition_group","availability")+"</button>"),u.on("click",this.getAddHandler(null,r),this),s.appendChild(u),a=e.Node.create('<label for="'+o+'" class="col-xs-7">'+M.util.get_string("condition_group_info","availability")+"</label>"),s.appendChild(a),i.appendChild(s);var l={headerContent:M.util.get_string("addrestriction","availability"),bodyContent:t,additionalBaseClass:"availability-dialogue",draggable:!0,modal:!0,closeButton:!1,width:"450px"};r.dialog=new M.core.dialogue(l),r.dialog.show(),n.on("click",function(){r.dialog.destroy(),this.inner.one("> .availability-button").one("button").focus()},this)},M.core_availability.List.prototype.getAddHandler=function(e,t){return function(){var n;e?n=new M.core_availability.Item({type:e,creating:!0},this.root):n=new M.core_availability.List({c:[],showc:!0},!1,this.root),this.addChild(n),M.core_availability.form.update(),M.core_availability.form.rootList.renumber(),this.updateHtml(),t.dialog.destroy(),n.focusAfterAdd()}},M.core_availability.List.prototype.getValue=function(){var e={};e.op=this.node.one(".availability-neg").get("value")+this.node.one(".availability-op").get("value"),e.c=[];var t;for(t=0;t<this.children.length;t++)e.c.push(this.children[t].getValue());if(this.root)if(this.isIndividualShowIcons()){e.showc=[];for(t=0;t<this.children.length;t++)e.showc.push(!this.children[t].eyeIcon.isHidden())}else e.show=!this.eyeIcon.isHidden();return e},M.core_availability.List.prototype.fillErrors=function(e){this.children.length===0&&!this.root&&e.push("availability:error_list_nochildren");for(var t=0;t<this.children.length;t++)this.children[t].fillErrors(e)},M.core_availability.List.prototype.hasItemOfType=function(e){for(var t=0;t<this.children.length;t++){var n=this.children[t];if(n instanceof M.core_availability.List){if(n.hasItemOfType(e))return!0}else if(n.pluginType===e)return!0}return!1},M.core_availability.List
.prototype.eyeIcon=null,M.core_availability.List.prototype.root=!1,M.core_availability.List.prototype.children=null,M.core_availability.List.prototype.node=null,M.core_availability.List.prototype.inner=null,M.core_availability.Item=function(t,n){this.pluginType=t.type,M.core_availability.form.plugins[t.type]===undefined?(this.plugin=null,this.pluginNode=e.Node.create('<div class="availability-warning">'+M.util.get_string("missingplugin","availability")+"</div>")):(this.plugin=M.core_availability.form.plugins[t.type],this.pluginNode=this.plugin.getNode(t),this.pluginNode.addClass("availability_"+t.type)),this.node=e.Node.create('<div class="availability-item d-inline-block"><h3 class="accesshide"></h3></div>');if(n){var r=!0;t.showc!==undefined&&(r=t.showc),this.eyeIcon=new M.core_availability.EyeIcon(!0,r),this.node.appendChild(this.eyeIcon.span)}this.pluginNode.addClass("availability-plugincontrols"),this.node.appendChild(this.pluginNode);var i=new M.core_availability.DeleteIcon(this);this.node.appendChild(i.span),this.node.appendChild(document.createTextNode(" ")),this.node.appendChild(e.Node.create('<span class="m-t-1 label label-warning"/>'))},M.core_availability.Item.prototype.getValue=function(){var e={type:this.pluginType};return this.plugin&&this.plugin.fillValue(e,this.pluginNode),e},M.core_availability.Item.prototype.fillErrors=function(e){var t=e.length;this.plugin?this.plugin.fillErrors(e,this.pluginNode):e.push("core_availability:item_unknowntype");var n=this.node.one("> .label-warning");e.length!==t&&!n.get("firstChild")?n.appendChild(document.createTextNode(M.util.get_string("invalid","availability"))):e.length===t&&n.get("firstChild")&&n.get("firstChild").remove()},M.core_availability.Item.prototype.renumber=function(e){var t={number:e};this.plugin?t.type=M.util.get_string("title","availability_"+this.pluginType):t.type="["+this.pluginType+"]",t.number=e+":";var n=M.util.get_string("itemheading","availability",t);this.node.one("> h3").set("innerHTML",n)},M.core_availability.Item.prototype.focusAfterAdd=function(){this.plugin.focusAfterAdd(this.pluginNode)},M.core_availability.Item.prototype.pluginType=null,M.core_availability.Item.prototype.plugin=null,M.core_availability.Item.prototype.eyeIcon=null,M.core_availability.Item.prototype.node=null,M.core_availability.Item.prototype.pluginNode=null,M.core_availability.EyeIcon=function(t,n){this.individual=t,this.span=e.Node.create('<a class="availability-eye col-form-label" href="#" role="button">');var r=this,i=t?"_individual":"_all",s=function(){r.span.setAttribute("data-visible","false"),require(["core/str","core/templates"],function(e,t){var n=[{key:"hidden"+i,component:"availability"},{key:"show_verb",component:"availability"}];e.get_strings(n).then(function(e){return r.isHidden()&&r.span.set("title",e[0]+" \u2022 "+e[1]),t.renderIcon("show",e[0])}).then(function(e){r.isHidden()&&r.span.setContent(e)})})},o=function(){r.span.setAttribute("data-visible","true"),require(["core/str","core/templates"],function(e,t){var n=[{key:"shown"+i,component:"availability"},{key:"hide_verb",component:"availability"}];e.get_strings(n).then(function(e){return r.isHidden()||r.span.set("title",e[0]+" \u2022 "+e[1]),t.renderIcon("hide",e[0])}).then(function(e){r.isHidden()||r.span.setContent(e)})})};n?o.call(this):s.call(this);var u=function(e){e.preventDefault(),this.isHidden()?o.call(this):s.call(this),M.core_availability.form.update()};this.span.on("click",u,this),this.span.on("key",u,"up:32",this),this.span.on("key",function(e){e.preventDefault()},"down:32",this)},M.core_availability.EyeIcon.prototype.individual=!1,M.core_availability.EyeIcon.prototype.span=null,M.core_availability.EyeIcon.prototype.isHidden=function(){return this.span.getAttribute("data-visible")!=="true"},M.core_availability.DeleteIcon=function(t){var n=this;this.span=e.Node.create('<a class="d-inline-block col-form-label availability-delete p-x-1" href="#" role="button">'),require(["core/str","core/templates"],function(e,t){e.get_string("delete","moodle").then(function(e){return n.span.setAttribute("title",e),t.renderIcon("delete",e)}).then(function(e){n.span.appendChild(e)})});var r=function(e){e.preventDefault(),M.core_availability.form.rootList.deleteDescendant(t),M.core_availability.form.rootList.renumber()};this.span.on("click",r,this),this.span.on("key",r,"up:32",this),this.span.on("key",function(e){e.preventDefault()},"down:32",this)},M.core_availability.DeleteIcon.prototype.span=null},"@VERSION@",{requires:["base","node","event","event-delegate","panel","moodle-core-notification-dialogue","json"]});
