YUI.add("moodle-availability_audience-form",function(e,t){M.availability_audience=M.availability_audience||{},M.availability_audience.form=e.Object(M.core_availability.plugin),M.availability_audience.form.initInner=function(e){this.dialogConfig=e},M.availability_audience.form.generateIndex=function(){var e=0;return function(){return e++}}(),M.availability_audience.form.getNode=function(t){var n=M.availability_audience.form.generateIndex(),r="cohort["+n+"]",i="cohortid["+n+"]",s=t.cohort===undefined?0:t.cohort,o='<label class="form-group" for="avail-audience"><span class="p-r-1">'+M.util.get_string("title","availability_audience")+"</span> ";o+='<span class="availability-group">',o+="</label>",o+='<select id="avail-audience" name="'+r+'">',s>0&&typeof this.dialogConfig!="undefined"&&(o+="<option value="+s+">"+this.dialogConfig.audienceNames[s].name+"</option>"),o+="</select>",o+='<input type="hidden" name="'+i+'" value="'+s+'" />',o+="</span>";var u=e.Node.create('<span class="form-inline">'+o+"</span>");return require(["core/form-autocomplete","jquery"],function(e,t){var n='[name="'+r+'"]',s='[name="'+i+'"]';e.enhance(n,!1,"availability_audience/ajax_handler","Type something"),t(n).on("change",function(e){t(s).val(t(n).val()),M.core_availability.form.update()})}),u},M.availability_audience.form.fillValue=function(e,t){e.cohort=t.one("[name^=cohortid]").getAttribute("value")},M.availability_audience.form.fillErrors=function(e,t){var n={};this.fillValue(n,t),n.cohort.trim()==="0"&&e.push("availability_audience:error_selectfield")}},"@VERSION@",{requires:["base","node","event","moodle-core_availability-form"]});
