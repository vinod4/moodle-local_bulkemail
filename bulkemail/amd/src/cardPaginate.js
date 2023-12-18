// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * JavaScript for the cardPaginate_preview of the
 * add_random_form class.
 *
 * @module    local_bulkemail/cardPaginate
 * @copyright 2022 eabyas info solutions <http://eabyas.in>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(
    [
        'jquery',
        'core/ajax',
        'core/str',
        'core/notification',
        'core/templates',
        'core/paged_content_factory',
    ],
    function(
        $,
        Ajax,
        Str,
        Notification,
        Templates,
        PagedContentFactory
    ) {

    var ITEMS_PER_PAGE = 6;
    var targetID = '';
    var targetRoot = '';
    var SELECTORS = {
        LOADING_ICON_CONTAINER: '[data-region="overlay-icon-container"]',
        PAGINATE_COUNT_CONTAINER: '[data-region="'+targetID+'-count-container"]',
        PAGINATE_LIST_CONTAINER: '[data-region="'+targetID+'-list-container"]'
    };

    var setOptions = function(options){

        // var methodName = options.methodName;
        // var TEMPLATE_NAME = options.templateName;
        if(options.hasOwnProperty('targetID')){
            targetID = options.targetID;
            targetRoot = $('#'+targetID);
        }
        if(options.hasOwnProperty('perPage') && typeof(options.perPage) == 'number'){
            ITEMS_PER_PAGE = options.perPage;
        }
        SELECTORS = {
            LOADING_ICON_CONTAINER: '[data-region="overlay-icon-container"]',
            PAGINATE_COUNT_CONTAINER: '[data-region="'+targetID+'-count-container"]',
            PAGINATE_LIST_CONTAINER: '[data-region="'+targetID+'-list-container"]'
        };
    };

    /**
     * Show the loading spinner over the preview section.
     *
     * @param {object} targetRoot
     *
     */
    var showLoadingIcon = function(targetRoot) {
        targetRoot.find(SELECTORS.LOADING_ICON_CONTAINER).removeClass('hidden');
    };

    /**
     * Hide the loading spinner.
     *
     * @param  {object} targetRoot
     *
     */
    var hideLoadingIcon = function(targetRoot) {
        targetRoot.find(SELECTORS.LOADING_ICON_CONTAINER).addClass('hidden');
    };

    /**
     * Render the section of text to show the record count.
     *
     * @param  {jquery} targetID The targetID element.
     * @param  {int} recordCount The number of records.
     */

    /**
     * Send a request to the server for more records.
     *
     * /@param  {int} userId All records user id.
     * /@param  {int} contextId The context where the records will be added.
     * /@param  {int} limit How many records to retrieve.
     * /@param  {int} offset How many records to skip from the start of the result set.
     * @param  {object} options
     * @param  {object} dataoptions
     * @param  {object} filterdata
     * @return {promise} Resolved when the preview section has rendered.
     */
    var requestMethod = function(options, dataoptions, filterdata) {
        var request = {
            methodname: options.methodName,
            args: {
                contextid: dataoptions.contextid,
                options: JSON.stringify(options),
                dataoptions: JSON.stringify(dataoptions),
                offset: options.offset,
                limit: options.perPage,
                filterdata: JSON.stringify(filterdata)
            }
        };
        return Ajax.call([request])[0];
    };

    /**
     * Build a paged content widget for records with the given criteria. The
     * criteria is used to fetch more records from the server as the user
     * requests new pages.
     *
     * @param  {object[]} options All records user id.
     * @param  {object[]} dataoptions The context where the records will be added.
     * @param  {int} totalCount How many records match the criteria above.
     * /@param  {object[]} firstrecords cardPaginates List of records for the first page.
     * @param  {object} firstresponse
     * @param  {object} filterdata
     * @return {promise} A promise resolved with the HTML and JS for the paged content.
     */
    var renderAsPagedContent = function(options, dataoptions, totalCount, firstresponse,filterdata){
        // to control how the records on each page are rendered.
        return PagedContentFactory.createFromAjax(totalCount, ITEMS_PER_PAGE,
            // Callback function to render the requested pages.
            function(pagesData) {
                return pagesData.map(function(pageData) {
                    var offset = pageData.offset;
                    var limit = pageData.limit;
                        options.offset = offset;
                        options.limit = limit;

                        if(offset > 0){
                            return requestMethod(options, dataoptions, filterdata)
                            .then(function(response) {
                                // var records = response.records;
                                response["cardClass"] = options.cardClass;

                                response["viewtypeCard"] = false;
                                if(options.viewType == "card" || options.viewType == "table"){
                                    response["viewtypeCard"] = true;
                                }
                                return Templates.render(options.templateName, {response: response});
                            })
                            .fail(Notification.exception);
                        } else {
                                firstresponse["cardClass"] = options.cardClass;

                                firstresponse["viewtypeCard"] = false;
                                if(options.viewType == "card" || options.viewType == "table"){
                                    firstresponse["viewtypeCard"] = true;
                                }
                            return Templates.render(options.templateName, {response: firstresponse});
                        }
                    // }
                });
            },
        // Config to set up the paged content.
        {
            controlPlacementBottom: true,
            eventNamespace: 'paginate-paged-content-'+options.targetID,
            persistentLimitKey: 'paginate-paged-content-limit-key'
        }
        );
    };

    /**
     * Re-render the preview section based on the provided filter criteria.
     *
     * @param  {object} options
     * @param  {object} dataoptions
     * @param  {object} filterdata
     * /@param  {int} userId All records user id.
     * /@param  {int} contextId The context where the records will be added.
     * @return {promise} Resolved when the preview section has rendered.
     */
    var reload = function(options, dataoptions,filterdata) {
        setOptions(options);

        // Show the loading spinner to tell the user that something is happening.
        showLoadingIcon(targetRoot);

        // Load the first set of records.
        options.offset = 0;
        return requestMethod(options, dataoptions,filterdata)
            .then(function(response) {

                var totalCount = response.totalcount;
                var records = response.records;
                if (records.length) {
                    // We received some records so render them as paged content
                    // with a paging bar.
                    return renderAsPagedContent(options, dataoptions, totalCount, response, filterdata);
                } else {
                    // If we didn't receive any records then we can return empty
                    // HTML and JS to clear the preview section.
                    if(response.nodata){
                        return Templates.render(options.templateName, {response: response});
                    }else{
                        var name=Str.get_string('no_data_available', 'local_bulkemail');
                        return name.then(function(s) {
                        return Templates.render('local_bulkemail/no-data', {name:s});
                        });
                    }

                    //return $.Deferred().resolve('', '');
                }
            })
            .then(function(html, js) {
                // Show the user the records set.
                targetRoot = $('#'+options.targetID);
                var paginatelistcontainer = '[data-region="'+options.targetID+'-list-container"]';
                var container = targetRoot.find(paginatelistcontainer);
                Templates.replaceNodeContents(container, html, js);
                return;
            })
            .always(function() {
                targetRoot = $('#'+options.targetID);
                hideLoadingIcon(targetRoot);
            })
            .fail(Notification.exception);

    };


    //added for the filtering the data
    var filteringData = function(e,submitid) {
        var formdata =  $("form#"+submitid+"").serializeArray();
        var values = [];
        var filterdatavalue = [];
        $.each(formdata, function (i, field) {
            // var valuedata = [];
            if(field.name != '_qf__filters_form' && field.name != 'sesskey'){
                if(field.name == 'options' || field.name == 'dataoptions'){
                    values[field.name] = field.value;
                }else{
                    var str = field.name;
                    if(str.indexOf('[]') != -1){
                        field.name = str.substring(0, str.length - 2);
                    }
                    if(field.name in filterdatavalue){
                        filterdatavalue[field.name] = filterdatavalue[field.name]+','+field.value;
                    }else{
                        filterdatavalue[field.name] = field.value;
                    }
                }

            }
        });
        var filtervalue = $('#global_filter').val();
        if(filtervalue){
            filterdatavalue[$('#global_filter').attr('name')] = filtervalue;
        }
        var optionsparsondata     = JSON.parse(values['options']);
        var dataoptionsparsondata = JSON.parse(values['dataoptions']);
        // var filterdataparsondata  =  Object.assign({}, filterdatavalue);
        var filterdataparsondata = $.extend({}, filterdatavalue);
        $('#global_filter').attr('data-filterdata', JSON.stringify(filterdataparsondata));
        return reload(optionsparsondata, dataoptionsparsondata,filterdataparsondata);
    };

    //added for the reset the data
    var resetingData = function(e,submitid) {
        var formdata =  $("form#"+submitid+"").serializeArray();
        var values = [];
        var filterdatavalue = [];
        $.each(formdata, function (i, field) {
            // var valuedata = [];
            if(field.name != '_qf__filters_form' && field.name != 'sesskey'){
                if(field.name == 'options' || field.name == 'dataoptions'){
                    values[field.name] = field.value;
                }
            }
        });
        var filtervalue = $('#global_filter').val();
        if(filtervalue){
            filterdatavalue[$('#global_filter').attr('name')] = filtervalue;
        }
        var optionsparsondata     = JSON.parse(values['options']);
        var dataoptionsparsondata = JSON.parse(values['dataoptions']);
        // filterdataparsondata  =  Object.assign({}, filterdatavalue);
        var filterdataparsondata = $.extend({}, filterdatavalue);
        $('#global_filter').attr('data-filterdata', '[]');
        // $('#global_filter').data('filterdata', '[]');
        reload(optionsparsondata, dataoptionsparsondata, filterdataparsondata);
        $("form#"+submitid+"")[0].reset();
        // $("#fitem_id_acceptchallengedate_"+submitid+" .custom-select").attr("disabled", "disabled");
        // $("#fitem_id_challengeenddate_"+submitid+" .custom-select").attr("disabled", "disabled");
        $(".tag-info").html("");
        $("div.form-autocomplete-selection span.mb-3").html("");
        $("div.form-autocomplete-selection span.mb-3").removeClass("tag-info");
        $("div.form-autocomplete-selection span.mb-3").removeClass("tag");
    };
    return {
        reload: reload,
        showLoadingIcon: showLoadingIcon,
        hideLoadingIcon: hideLoadingIcon,
        filteringData:filteringData,
        resetingData:resetingData
    };
});
