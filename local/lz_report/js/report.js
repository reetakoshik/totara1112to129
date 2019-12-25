function initCourseReport() {
    new ReportStatusRow().init();  
}

function initProgramReport() {
    new ReportStatusRow().init();
    // new ReportSummarizedInfo().init();
}

function ReportStatusRow() { }

ReportStatusRow.prototype = {
    getContainer: function() {
        return $('#page-content');
    },

    init: function() {
        var $container = this.getContainer();
        var $this = this;

        // Catching events on container because target elements could be reloaded by the sidebar filters
        $container.on('click', function (e) {
            if($(e.target).closest('tr td.course_completion_status, tr td.progcompletion_status')) {
                $this.toggleStatusRow(e);
            }
        });
    },

    toggleStatusRow: function(e) {
        var $cell = this._getStatusCellFromEvent(e);
        var $statusRow = this._getStatusRow($cell);
        if ($statusRow) {
            return this._removeStatusRow($statusRow);
        }
        return this._createStatusRow($cell);
    },

    _getStatusCellFromEvent: function(e) {
        var $target = $(e.target);
        if ($target.hasClass('course_completion_status') || 
            $target.hasClass('progcompletion_status')) {
            return $target;
        }
        return $target.closest('td.course_completion_status, td.progcompletion_status');
    },

    _getStatusRow: function($statusCell) {
        $tr = $statusCell.closest('tr').next();
        if ($tr.length === 1 && $tr.hasClass('status-row')) {
            return $tr;
        }
        return null;
    },

    _removeStatusRow: function($statusRow) {
        $statusRow.remove();
    },

    _createStatusRow: function($statusCell) {
        var $targetRow = $statusCell.closest('tr');
        var cellsCount = $targetRow.find('td').length;
        var $tr = $('<tr class="status-row"></tr>');
        var $td = $('<td colspan="'+cellsCount+'"></td>');
        var $statusContent =  $statusCell.find('div.status-row-inner').clone();
        $td.append($statusContent);
        $tr.append($td);
        $targetRow.after($tr);
        $statusContent.show();
    }
}


function ReportSummarizedInfo() { }

ReportSummarizedInfo.prototype = {
    init: function() {
        var params = {};
        params.reportid = this._getContainer().attr('id');
        params.programid = this._getQueryParameterByName('programid');
        if (!params.programid) {
            delete params.programid;
        }
        this._request(params).then(this._printResponse.bind(this));
    },

    _getContainer: function() {
        return $('.rb-display-table-container');
    },

    _request: function(params) {
        var url = '/local/lz_report/detailed_program_completion/summarized_data.php';
        return $.getJSON(url, params);
    },

    _printResponse: function(res) {
        var $container = this._getContainer();
        this._getContainer().before(
            '<div class="panel panel-info clear-bootstrap-panel">'+
                '<div class="panel-heading"><h4 class="panel-title">'+
                    'Number of assigned user to the programs/certifications:'+
                '</h4></div>'+
                '<div class="panel-body">'+
                    res.users_assigned_to_program.map(function(item) {
                        return (
                            '<div class="row">'+
                                '<div class="col-lg-3 col-md-4 col-sm-6 col-xs-6"><b>'+item.fullname+':</b></div>'+
                                '<div class="col-lg-3 col-md-4 col-sm-6 col-xs-6">'+item.users_count+'</div>'+
                            '</div>'
                        );
                    }).join('') +
                '</div>'+
            '</div>'+
            '<div class="panel panel-info clear-bootstrap-panel">'+
                '<div class="panel-heading"><h4 class="panel-title">'+
                    'Number of users in each status:'+
                '</h4></div>'+
                '<div class="panel-body">'+
                    res.users_with_program_completion_status.map(function(item) {
                        return (
                            '<div class="row">'+
                                '<div class="col-lg-3 col-md-4 col-sm-6 col-xs-6"><b>'+item.completion_status+':</b></div>'+
                                '<div class="col-lg-3 col-md-4 col-sm-6 col-xs-6">'+item.users_count+'</div>'+
                            '</div>'
                        );
                    }).join('') +
                '</div>'+
            '</div>'
        );
    },

    _getQueryParameterByName: function(name, url) {
        if (!url) {
          url = window.location.href;
        }
        name = name.replace(/[\[\]]/g, "\\$&");
        var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
            results = regex.exec(url);
        if (!results) return null;
        if (!results[2]) return '';
        return decodeURIComponent(results[2].replace(/\+/g, " "));
    }
}