/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Ryan Adams <ryana@learningpool.com>
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_customfield
 */

define(['jquery'], function ($) {

    /* global google */

    /**
     * Creates a new Location management object.
     *
     * @class Location
     * @constructor
     * @param {google} google A google object from the Google API.
     * @param args Any arguments for this location.
     */
    function Location(google, args) {

        /**
         * Just a convenience function for getting argument values.
         *
         * @param {string} name The argument name to get.
         * @param {mixed} def The default value to use.
         * @returns {mixed}
         */
        function get_arg(name, def) {
            if (!args[name] || args[name] === 'undefined' || args[name] === '') {
                return def;
            }
            return args[name];
        }

        /**
         * Reference to the Google API namespace.
         * @type {google}
         */
        this.google = google;

        /**
         * A Google Maps client id, false if none provided.
         * @type {boolean|string}
         */
        this.clientid = get_arg('clientid', false);

        /**
         * A Google Maps API Key, false if none provided.
         * @type {boolean|string}
         */
        this.apikey = get_arg('apikey', false);

        /**
         * Prefix for this Location custom field.
         * Typically just the field name, and empty during definition.
         * @type {string}
         */
        this.fieldprefix = get_arg('fieldprefix', '');

        /**
         * True if this Location field is for display.
         * A custom field has three states:
         *    1. Definition - not for display.
         *    2. Setting the data for an item - not for display.
         *    3. Viewing the data that was set for an item - this is for display.
         * @type {boolean}
         */
        this.fordisplay = get_arg('fordisplay', false);

        /**
         * The region bias to apply to the map when initialising it.
         * This is part of the Google maps API and comes from a setting in Totara.
         * @type {string}
         */
        this.regionbias = get_arg('regionbias', '');

        /**
         * The default zoom level.
         * @type {int}
         */
        this.defaultzoomlevel = get_arg('defaultzoomlevel', 12);

        /**
         * The address entered by the user for the Location custom field.
         * Nothing to do with the map unless the user clicks the Use address button.
         * @type {string}
         */
        this.address = '';

        /**
         * Location properties, latitude and longitude.
         * @property {float} lat
         * @property {float} lng
         * @type {{lat: null, lng: null}}
         */
        this.location = {
            lat: null,
            lng: null
        };

        /**
         * The view for the map, can be roadmap, satellite, or hybrid.
         * @type {string}
         */
        this.view = null;

        /**
         * The Map object.
         * @type {google.maps.Map}
         */
        this.map = null;

        /**
         * The Marker object.
         * @type {google.maps.Marker}
         */
        this.marker = null;

        /**
         * The address input.
         * When definiting this is a textarea, when displaying this is a hidden input.
         * @type {jQuery}
         */
        this.input_address = $('#id_' + this.fieldprefix + 'address');

        /**
         * The latitude input.
         * @type {jQuery}
         */
        this.input_latitude = $('#' + this.fieldprefix + 'latitude');

        /**
         * The longitude input.
         * @type {jQuery}
         */
        this.input_longitude = $('#' + this.fieldprefix + 'longitude');

        /**
         * The zoom input.
         * @type {jQuery}
         */
        this.input_zoom = $('#' + this.fieldprefix + 'zoom');

        /**
         * The fieldset containing this location custom field.
         * @type {jQuery}
         */
        this.fieldset = null;

        if (!this.fordisplay) {
            this.wireDefinitionControls();
        }

        var input_view = $('#' + this.fieldprefix + 'room-location-view'),
            map = $('#' + this.fieldprefix + 'location_map');

        // There is no point in proceeding past this point if we don't have a map input.
        if (!map.length) {
            return;
        }

        // Get the value for the latitude - if we have it, if not the we'll hide the map and skip.
        // During display this is a hidden input.
        if (this.input_latitude.length) {
            this.location.lat = parseFloat(this.input_latitude.val());
        } else {
            map.hide();
            return;
        }

        // Get the value for the longitude - if we have it, if not the we'll hide the map and skip.
        // During display this is a hidden input.
        if (this.input_longitude.length) {
            this.location.lng = parseFloat(this.input_longitude.val());
        } else {
            map.hide();
            return;
        }

        // If the fieldset is collapsed then we need an action to initialise the map the first time the fieldset is expanded.
        // We only want to do this once, and only if it is collapsed.
        this.fieldset = map.closest('fieldset.collapsible.collapsed');
        if (this.fieldset.length) {
            window.form_fieldset_expand = window.form_fieldset_expand || [];
            window.form_fieldset_expand.push({
                context: this,
                callback: this.fieldSetRefreshMap
            });
            this.fieldset.attr('data-location-refresh-' + this.fieldprefix, 'true');
        }

        // Get initial zoom.
        if (this.input_zoom.length) {
            this.location.zoom = parseInt(this.input_zoom.val());
        } else {
            this.location.zoom = this.defaultzoomlevel;
        }

        // Get the initial address.
        if (this.input_address.length) {
            this.address = this.input_address.val().trim();
        }

        // Get the currently set view.
        // During display this is a hidden input.
        if (input_view.length) {
            if (input_view.val() == "map") {
                this.view = this.google.maps.MapTypeId.ROADMAP;
            } else if (input_view.val() == "satellite") {
                this.view = this.google.maps.MapTypeId.SATELLITE;
            } else {
                this.view = this.google.maps.MapTypeId.HYBRID;
            }
        } else {
            // No view inputs? Default to the roadmap.
            this.view = this.google.maps.MapTypeId.ROADMAP;
        }

        // Finally now that we know what we need to know initialise the map.
        this.load_map();
    }

    /**
     * Refreshes the map, needs to be done if the map was hidden when it was initialised.
     * @param {Element} fieldset
     */
    Location.prototype.fieldSetRefreshMap = function(fieldset) {
        if (!this.map) {
            return;
        }
        if ($(fieldset).attr('data-location-refresh-'+this.fieldprefix) !== 'true') {
            return;
        }
        // We only want to do this once, change it to false so that we know it has happened.
        $(fieldset).attr('data-location-refresh-'+this.fieldprefix, 'false');
        this.google.maps.event.trigger(this.map, 'resize');
        // No need to check the location inputs exist, they must do if we got this far.
        this.location.lat = parseFloat(this.input_latitude.val());
        this.location.lng = parseFloat(this.input_longitude.val());
        this.map.panTo(this.location);
    };
    Location.prototype.wireDefinitionControls = function() {
        var self = this,
            fordisplay = this.fordisplay,
            input_address = this.input_address,
            input_addresslookup = $('#id_' + this.fieldprefix + 'addresslookup'),
            input_radioview = $('.radio_view[name="' + this.fieldprefix + 'view"]'),
            btn_search = $('#id_' + this.fieldprefix + 'searchaddress_btn'),
            btn_useaddress = $('#id_' + this.fieldprefix + 'useaddress_btn');

        // The search button exists only when defining the field.
        if (btn_search) {
            // Attach events to the search button.
            btn_search.on('click', function () {
                self.address = input_addresslookup.val().trim();
                self.geocode_address();
            });
        }

        // The address lookup exists only when defining the field.
        if (input_addresslookup && btn_search) {
            self.address = input_addresslookup.keydown(function (evt) {
                if (evt.keyCode === 13) {
                    // Required, as enter will submit the form on this input.
                    evt.preventDefault();
                    btn_search.click();
                    return false;
                }
            });
        }

        // The use address button exists only when defining the field.
        if (btn_useaddress) {
            // Attach events to the "Use address" button.
            btn_useaddress.on('click', function () {
                self.address = input_address.val().trim();
                self.geocode_address();
            });
        }

        // The view radio buttons exist only when defining the field.
        if (input_radioview) {
            // Attach events to the view radio buttons.
            input_radioview.on('change', function () {
                if (this.id.indexOf(self.fieldprefix) > -1) {
                    self.defaultzoomlevel = self.map.getZoom();
                    self.set_map_view();
                    self.load_map(fordisplay);
                }
            });
        }
    };
    /**
     * Sets the map view type.
     *
     * Can be one of RoadMap (default), Satellite, or Hybrid of the two.
     */
    Location.prototype.set_map_view = function() {
        var self = this,
            selectedradio = $('.radio_view[name="' + this.fieldprefix + 'view"]:checked');

        selectedradio.each( function( index, element ) {
            if (element.id.indexOf(self.fieldprefix) > -1) {
                switch (element.value) {
                    case 'hybrid':
                        self.map.setMapTypeId(self.google.maps.MapTypeId.HYBRID);
                        self.view = self.google.maps.MapTypeId.HYBRID;
                        break;
                    case 'satellite':
                        self.map.setMapTypeId(self.google.maps.MapTypeId.SATELLITE);
                        self.view = self.google.maps.MapTypeId.SATELLITE;
                        break;
                    default:
                        self.map.setMapTypeId(self.google.maps.MapTypeId.ROADMAP);
                        self.view = self.google.maps.MapTypeId.ROADMAP;
                        break;
                }
            }
        });
    };
    /**
     * Geocode the address that the user has entered and load it into the map.
     */
    Location.prototype.geocode_address = function() {
        var self = this,
            address = this.url_encode_address(this.address),
            srcurl = 'https://maps.googleapis.com/maps/api/geocode/json?address=' + address
                + '&region=' + this.regionbias;

        M.util.js_pending('totara_customfield-location_search');
        if (address === '') {
            return;
        }
        if (this.clientid) {
            srcurl += '&client='+this.clientid;
        } else if (this.apikey) {
            srcurl += '&key='+this.apikey;
        }
        $.ajax({
            url: srcurl,
            type: 'GET',
            success: function (data, response) {
                if (response == 'success' && data.results.length > 0) {
                    self.address = data.results[0].formatted_address;
                    self.location = data.results[0].geometry.location;
                    self.load_map();
                    $('#fgroup_id_' + self.fieldprefix + 'mapelements .felement .alert').hide();
                    M.util.js_complete('totara_customfield-location_search');
                } else {
                    if ($('#fgroup_id_' + self.fieldprefix + 'mapelements .felement .alert').length === 0){
                        require(['core/templates', 'core/str'], function (templates, mdlstrings) {
                            mdlstrings.get_string('locationnotfound', 'totara_customfield').done(function (notfound) {
                                templates.render('core/notification_error', {message: notfound}).done(function (html) {
                                    $('#fgroup_id_' + self.fieldprefix + 'mapelements .felement').prepend(html);
                                    M.util.js_complete('totara_customfield-location_search');
                                });
                            });
                        });
                    } else {
                        $('#fgroup_id_' + self.fieldprefix + 'mapelements .felement .alert').show();
                        M.util.js_complete('totara_customfield-location_search');
                    }
                }
            },
            error: function () {
                if (window.console && window.console.log) {
                    window.console.log('Failed to load Google maps API.');
                }
            }
        });
    };
    /**
     * Get the zoom level for this map.
     * @returns {Number}
     */
    Location.prototype.get_zoom_level = function() {
        return parseInt(this.input_zoom.val());
    };
    /**
     * Load the map!
     * @param {boolean} fordisplay
     */
    Location.prototype.load_map = function(fordisplay) {
        // Optional argument, here just for testing.
        fordisplay = typeof fordisplay !== 'undefined' ? fordisplay : this.fordisplay;

        if (this.location.lat === null) {
            return;
        }

        var self = this,
            mapOptions = {
                center: this.location,
                zoomControl: true,
                mapTypeControl: false,
                scaleControl: false,
                streetViewControl: false,
                rotateControl: false,
                mapTypeId: this.view,
                zoom: this.get_zoom_level()
            },
            contentstring = '',
            infowindow;

        this.map = new this.google.maps.Map(
            document.getElementById(this.fieldprefix + 'location_map'), mapOptions
        );
        if (!fordisplay) {
            this.set_map_view();
        }

        this.marker = new this.google.maps.Marker({
            position: this.location,
            map: this.map,
            draggable: !fordisplay
        });

        this.input_latitude.val(this.location.lat);
        this.input_longitude.val(this.location.lng);

        if (!fordisplay) {
            this.marker.addListener('drag', function (e) {
                self.input_latitude.val(e.latLng.lat());
                self.input_longitude.val(e.latLng.lng());
            });
            this.map.addListener('zoom_changed', function (e) {
                self.input_zoom.val(self.map.getZoom());
            });
        } else {
            if (this.address !== null && this.address.length > 0) {
                contentstring = this.address;
            } else {
                return;
            }

            infowindow = new this.google.maps.InfoWindow({
                content: contentstring
            });
            this.marker.addListener('click', function () {
                infowindow.open(self.map, self.marker);
            });
        }
    };
    /**
     * URL encode the address provided.
     * @param address
     * @returns {string}
     */
    Location.prototype.url_encode_address = function(address) {
        return encodeURI(address.replace(/\s+/g, '+'));
    };

    return {
        init: function (args) {
            M.util.js_pending('totara_customfield-location_init');
            require(['totara_customfield/field_location_loader!'+args.mapparams], function() {
                // google is now defined thanks to the async loader.
                new Location(google, args);
                M.util.js_complete('totara_customfield-location_init');
            });
        }
    };

});