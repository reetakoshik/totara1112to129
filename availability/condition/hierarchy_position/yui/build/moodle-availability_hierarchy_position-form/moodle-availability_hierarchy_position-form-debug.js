YUI.add('moodle-availability_hierarchy_position-form', function (Y, NAME) {

/**
 * JavaScript for form editing position conditions.
 *
 * @module moodle-availability_hierarchy_position-form
 */
M.availability_hierarchy_position = M.availability_hierarchy_position || {};

/**
 * @class M.availability_hierarchy_position.form
 * @extends M.core_availability.plugin
 */
M.availability_hierarchy_position.form = Y.Object(M.core_availability.plugin);

/**
 * Initialises this plugin.
 *
 * @method initInner
 * @param {Array} initParams Array of objects extra info for the form key => value
 */
M.availability_hierarchy_position.form.initInner = function(initParams) {
    this.conditionConfig = initParams;

};

/**
 * Generate an index so we can identify separate
 * position conditions.
 */
M.availability_hierarchy_position.form.generateIndex = (function() {
    var _count = 0;
    return function() {
        return _count++;
    };
})();


M.availability_hierarchy_position.form.getNode = function(json) {
    // Increment number used for unique ids.
    var index = M.availability_hierarchy_position.form.generateIndex();

    var selectName = 'pos[' + index + ']';
    var hiddenName = 'posid[' + index + ']';
    var position =  json.position === undefined ? 0 : json.position;

    // Create HTML structure.
    var html = '<label class="form-group" for="avail-position"><span class="p-r-1">' + M.util.get_string('title', 'availability_hierarchy_position') + '</span> ';
    html += '<span class="availability-group">';
    html += '</label>';
    html += '<select id="avail-position" name="' + selectName + '">';
    if (position > 0) {
        if (typeof this.conditionConfig !== 'undefined') {
            html += '<option value=' + position + '>' + this.conditionConfig.positionNames[position].fullname + '</option>';
        }
    }
    html += '</select>';
    html += '<input type="hidden" name="' + hiddenName + '" value="' + position  + '" />';
    html += '</span>';

    var node = Y.Node.create('<span class="form-inline">' + html + '</span>');

    // Due to issues caused by race conditions we must use AMD here
    // so we can be sure that the form was added to the DOM.
    require(['core/form-autocomplete', 'jquery'], function(autoComplete, $){
        var selectSelector = '[name="' + selectName  + '"]';
        var hiddenSelector = '[name="' + hiddenName  + '"]';

        autoComplete.enhance(selectSelector, false, 'availability_hierarchy_position/ajax_handler', M.util.get_string('searchpositions', 'availability_hierarchy_position'));

        $(selectSelector).on('change', function(evt) {
            // Triggers the value being updated.
            $(hiddenSelector).val($(selectSelector).val());
            M.core_availability.form.update();
        });
    });

    return node;
};

M.availability_hierarchy_position.form.fillValue = function(value, node) {
    value.position = node.one('[name^=posid]').getAttribute('value');
};

M.availability_hierarchy_position.form.fillErrors = function(errors, node) {
    var value = {};
    this.fillValue(value, node);

    // Check an position has been set.
    if (value.position.trim() === '0') {
        errors.push('availability_hierarchy_position:error_selectfield');
    }
};


}, '@VERSION@', {"requires": ["base", "node", "event", "moodle-core_availability-form"]});
