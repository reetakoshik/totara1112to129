YUI.add("moodle-filter_glossary-autolinker",function(e,t){var n="Glossary filter autolinker",r="width",i="height",s="menubar",o="location",u="scrollbars",a="resizable",f="toolbar",l="status",c="directories",h="fullscreen",p="dependent",d;d=function(){d.superclass.constructor.apply(this,arguments)},e.extend(d,e.Base,{overlay:null,alertpanels:{},initializer:function(){var t=this;require(["core/event"],function(n){e.delegate("click",function(r){r.preventDefault();var i="",s=e.Node.create('<div id="glossaryfilteroverlayprogress"></div>'),o=new e.Overlay({headerContent:i,bodyContent:s}),u,a;require(["core/templates"],function(e){e.renderIcon("i/loading").then(function(e){s.append(e)})}),t.overlay=o,o.render(e.one(document.body)),u=this.getAttribute("href").replace("showentry.php","showentry_ajax.php"),a={method:"get",context:t,on:{success:function(e,t){this.display_callback(t.responseText,n)},failure:function(e,t){var n=t.statusText;M.cfg.developerdebug&&(t.statusText+=" ("+u+")"),new M.core.exception({message:n})}}},e.io(u,a)},e.one(document.body),"a.glossary.autolink.concept")})},display_callback:function(t,n){var r,i,s,o,u,a;try{r=e.JSON.parse(t);if(r.success){this.overlay.hide();for(i in r.entries)u=r.entries[i].definition+r.entries[i].attachments,s=new M.core.alert({title:r.entries[i].concept,draggable:!0,message:u,modal:!1,yesLabel:M.util.get_string("ok","moodle")}),n.notifyFilterContentUpdated(s.get("boundingBox").getDOMNode()),e.Node.one("#id_yuialertconfirm-"+s.get("COUNT")).focus(),o="#moodle-dialogue-"+s.get("COUNT"),s.on("complete",this._deletealertpanel,this,o),e.Object.isEmpty(this.alertpanels)||(a=this._getLatestWindowPosition(),e.Node.one(o).setXY([a[0]+10,a[1]+10])),this.alertpanels[o]=e.Node.one(o).getXY();return!0}r.error&&new M.core.ajaxException(r)}catch(f){new M.core.exception(f)}return!1},_getLatestWindowPosition:function(){var t=[0,0];return e.Object.each(this.alertpanels,function(e){e[0]>t[0]&&(t=e)}),t},_deletealertpanel:function(e,t){delete this.alertpanels[t]}},{NAME:n,ATTRS:{url:{validator:e.Lang.isString,value:M.cfg.wwwroot+"/mod/glossary/showentry.php"},name:{validator:e.Lang.isString,value:"glossaryconcept"},options:{getter:function(){return{width:this.get(r),height:this.get(i),menubar:this.get(s),location:this.get(o),scrollbars:this.get(u),resizable:this.get(a),toolbar:this.get(f),status:this.get(l),directories:this.get(c),fullscreen:this.get(h),dependent:this.get(p)}},readOnly:!0},width:{value:600},height:{value:450},menubar:{value:!1},location:{value:!1},scrollbars:{value:!0},resizable:{value:!0},toolbar:{value:!0},status:{value:!0},directories:{value:!1},fullscreen:{value:!1},dependent:{value:!0}}}),M.filter_glossary=M.filter_glossary||{},M.filter_glossary.init_filter_autolinking=function(e){return new d(e)}},"@VERSION@",{requires:["base","node","io-base","json-parse","event-delegate","overlay","moodle-core-event","moodle-core-notification-alert","moodle-core-notification-exception","moodle-core-notification-ajaxexception"]});
