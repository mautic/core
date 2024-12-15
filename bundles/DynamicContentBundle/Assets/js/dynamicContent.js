Mautic.toggleDwcFilters = function () {
    mQuery("#dwcFiltersTab, #slotNameDiv").toggleClass("hide");
    if (mQuery("#dwcFiltersTab").hasClass('hide')) {
        mQuery('.nav-tabs a[href="#details"]').click();
    } else {
        Mautic.dynamicContentOnLoad();
    }
};

var Mautic = Mautic || {};

Mautic.dwcGenerator = (function() {
    // Selectors
    var copyBtnSelector = '#generator-copy-dynamic-content-slot';
    var pluginTabSelector = '#dwc--generator-plugins';
    var htmlTabSelector = '#dwc--generator-html';
    var codeContainerSelector = '.dwc--generator-content-code';
    var inputSelector = '.dwc--generator-content-input';
    var switchCodeWrapperBtnSelector = '#generator-switch-code-wrapper';
    var switchHtmlTagBtnSelector = '#generator-switch-html-tag';

    // State variables
    var isPluginBracketMode = true; // True means {mautic ...}, false means [mautic ...]
    var isUsingDiv = true; // True means using <div> for HTML snippet, false means using <span>

    function getActiveTabType() {
        // Identify which tab is active by looking for a .tab-pane.active
        var activePane = document.querySelector('.tab-pane.active.in') || document.querySelector('.tab-pane.active');
        if (!activePane) {
            return 'plugin'; // default if none found
        }
        if (activePane.id === 'dwc--generator-plugins') {
            return 'plugin';
        } else if (activePane.id === 'dwc--generator-html') {
            return 'html';
        }
        return 'plugin';
    }

    /**
     * Toggle between {mautic ...} and [mautic ...] in the plugin snippet
     */
    function switchCodeWrapper() {
        var pluginTab = document.querySelector(pluginTabSelector);
        if (!pluginTab) return;

        var pre = pluginTab.querySelector(codeContainerSelector);
        if (!pre) return;

        // Store the input value before making changes
        var input = pre.querySelector(inputSelector);
        var inputValue = input ? input.value : '';

        var code = pre.innerHTML;

        if (isPluginBracketMode) {
            // {mautic ...} -> [mautic ...]
            code = code.replace(/\{mautic/g, '[mautic');
            code = code.replace(/\{\/mautic\}/g, '[/mautic]');
        } else {
            // [mautic ...] -> {mautic ...}
            code = code.replace(/\[mautic/g, '{mautic');
            code = code.replace(/\[\/mautic\]/g, '{/mautic}');
        }

        pre.innerHTML = code;

        // Restore the input value after the HTML is updated
        var newInput = pre.querySelector(inputSelector);
        if (newInput && inputValue) {
            newInput.value = inputValue;
        }

        isPluginBracketMode = !isPluginBracketMode;
    }


    /**
     * Toggle between <div> and <span> in the HTML snippet.
     * The snippet is partially HTML-encoded (&lt;div ...) for the slot tags,
     * and has a real HTML tag for the editable block.
     */
    function switchHtmlTag() {
        var htmlTab = document.querySelector(htmlTabSelector);
        if (!htmlTab) return;

        var pre = htmlTab.querySelector(codeContainerSelector);
        if (!pre) return;

        var code = pre.innerHTML;

        // Store the input value before making changes
        var input = pre.querySelector(inputSelector);
        var inputValue = input ? input.value : '';

        // Store the entire editable div block
        var editableDiv = pre.querySelector('.dwc--generator-content-editable');
        var editableDivHtml = editableDiv ? editableDiv.outerHTML : '';

        if (isUsingDiv) {
            // Replace <div> with <span>
            code = code.replace(/&lt;div/, '&lt;span');
            code = code.replace(/&lt;\/div&gt;/, '&lt;/span&gt;');
        } else {
            // Replace <span> with <div>
            code = code.replace(/&lt;span/, '&lt;div');
            code = code.replace(/&lt;\/span&gt;/, '&lt;/div&gt;');
        }

        // Re-insert the editable div block
        code = code.replace(/&gt;.*?&lt;\//, '&gt;' + editableDivHtml + '&lt;/');

        pre.innerHTML = code;

        // Restore the input value after the HTML is updated
        var newInput = pre.querySelector(inputSelector);
        if (newInput && inputValue) {
            newInput.value = inputValue;
        }

        isUsingDiv = !isUsingDiv;
    }


    /**
     * Copy the current code to the clipboard
     */
    function copyCode() {
        var activeTab = getActiveTabType();
        var container;

        if (activeTab === 'plugin') {
            container = document.querySelector(pluginTabSelector + ' ' + codeContainerSelector);
        } else {
            container = document.querySelector(htmlTabSelector + ' ' + codeContainerSelector);
        }

        if (!container) return;

        // Get the full content from the pre element
        var code = container.innerHTML;

        // Find the input element within the pre block
        var input = container.querySelector(inputSelector);
        var userValue = input ? input.value : '';

        // Different replacement patterns for plugin and HTML tabs
        if (activeTab === 'plugin') {
            // Replace the editable div block with user input for plugin format
            var editableBlockRegex = /<div class="dwc--generator-content-editable.*?<\/div>/s;
            code = code.replace(editableBlockRegex, '\n' + userValue + '\n');

            // Clean up and format plugin code
            code = code.replace(/\s+/g, ' ')
                     .replace(/{mautic/, '\n{mautic')
                     .replace(/{\/mautic}/, '\n{/mautic}\n')
                     .trim();
        } else {
            // Replace the editable div block with user input for HTML format
            var editableBlockRegex = /<div class="dwc--generator-content-editable.*?<\/div>/s;
            code = code.replace(editableBlockRegex, userValue);

            // Clean up HTML entities
            code = code.replace(/&lt;/g, '<')
                     .replace(/&gt;/g, '>')
                     .trim();
        }

        // Copy to clipboard
        navigator.clipboard.writeText(code).then(function() {
            alert('Copied to clipboard!');
        }).catch(function() {
            // Fallback for browsers that don't support clipboard API
            var textarea = document.createElement('textarea');
            textarea.value = code;
            document.body.appendChild(textarea);
            textarea.select();
            try {
                document.execCommand('copy');
                alert('Copied to clipboard!');
            } catch (err) {
                alert('Failed to copy. Please try again.');
            }
            document.body.removeChild(textarea);
        });
    }

    function init() {
        // Copy button
        var copyBtn = document.querySelector(copyBtnSelector);
        if (copyBtn) {
            copyBtn.addEventListener('click', copyCode);
        }

        // Switch code wrapper button (plugin)
        var switchCodeBtn = document.querySelector(switchCodeWrapperBtnSelector);
        if (switchCodeBtn) {
            switchCodeBtn.addEventListener('click', switchCodeWrapper);
        }

        // Switch HTML tag button (html)
        var switchTagBtn = document.querySelector(switchHtmlTagBtnSelector);
        if (switchTagBtn) {
            switchTagBtn.addEventListener('click', switchHtmlTag);
        }
    }

    return {
        init: init
    };
})();

// Initialize after DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    Mautic.dwcGenerator.init();
});


Mautic.dynamicContentOnLoad = function (container, response) {
    if (typeof container !== 'object') {
        if (mQuery(container + ' #list-search').length) {
            Mautic.activateSearchAutocomplete('list-search', 'dynamicContent');
        }
    }

    var availableFilters = mQuery('div.dwc-filter').find('select[data-mautic="available_filters"]');
    Mautic.activateChosenSelect(availableFilters, false);

    Mautic.dynamicFiltersOnLoad('div.dwc-filter');
};

Mautic.dynamicFiltersOnLoad = function(container, response) {

    mQuery('#campaign-share-tab').hover(function () {
        if (Mautic.shareTableLoaded != true) {
            Mautic.loadAjaxColumn('campaign-share-stat', 'lead:getCampaignShareStats', 'afterStatsLoad');
            Mautic.shareTableLoaded = true;
        }
    })

    Mautic.afterStatsLoad = function () {
        Mautic.sortTableByColumn('#campaign-share-table', '.campaign-share-stat', true)
    }


    if (mQuery(container + ' #list-search').length) {
        Mautic.activateSearchAutocomplete('list-search', 'lead.list');
    }

    var prefix = 'leadlist';
    var parent = mQuery('.dynamic-content-filter, .dwc-filter');
    if (parent.length) {
        prefix = parent.attr('id');
    }

    if (mQuery('#' + prefix + '_filters').length) {
        mQuery('#available_filters').on('change', function() {
            if (mQuery(this).val()) {
                Mautic.addDwcFilter(mQuery(this).val(),mQuery('option:selected',this).data('field-object'));
                mQuery(this).val('');
                mQuery(this).trigger('chosen:updated');
            }
        });

        mQuery('#' + prefix + '_filters .remove-selected').each( function (index, el) {
            mQuery(el).on('click', function () {
                mQuery(this).closest('.panel').animate(
                    {'opacity': 0},
                    'fast',
                    function () {
                        mQuery(this).remove();
                        Mautic.reorderSegmentFilters();
                    }
                );

                if (!mQuery('#' + prefix + '_filters li:not(.placeholder)').length) {
                    mQuery('#' + prefix + '_filters li.placeholder').removeClass('hide');
                } else {
                    mQuery('#' + prefix + '_filters li.placeholder').addClass('hide');
                }
            });
        });

        var bodyOverflow = {};
        mQuery('#' + prefix + '_filters').sortable({
            items: '.panel',
            helper: function(e, ui) {
                ui.children().each(function() {
                    if (mQuery(this).is(":visible")) {
                        mQuery(this).width(mQuery(this).width());
                    }
                });

                // Fix body overflow that messes sortable up
                bodyOverflow.overflowX = mQuery('body').css('overflow-x');
                bodyOverflow.overflowY = mQuery('body').css('overflow-y');
                mQuery('body').css({
                    overflowX: 'visible',
                    overflowY: 'visible'
                });

                return ui;
            },
            scroll: true,
            axis: 'y',
            stop: function(e, ui) {
                // Restore original overflow
                mQuery('body').css(bodyOverflow);

                // First in the list should be an "and"
                ui.item.find('select.glue-select').first().val('and');

                Mautic.reorderSegmentFilters();
            }
        });

    }

    // segment contact filters
    var segmentContactForm = mQuery('#segment-contact-filters');

    if (segmentContactForm.length) {
        segmentContactForm.on('change', function() {
            segmentContactForm.submit();
        }).on('keyup', function() {
            segmentContactForm.delay(200).submit();
        }).on('submit', function(e) {
            e.preventDefault();
            Mautic.refreshSegmentContacts(segmentContactForm);
        });
    }
};

Mautic.addDwcFilter = function (elId, elObj) {
    var filterId = '#available_' + elObj + '_' + elId;
    var filterOption = mQuery(filterId);
    var label = filterOption.text();
    var alias = filterOption.val();

    // Create a new filter

    var filterNum = parseInt(mQuery('.available-filters').data('index'));
    mQuery('.available-filters').data('index', filterNum + 1);

    var prototypeStr = mQuery('.available-filters').data('prototype');
    var fieldType = filterOption.data('field-type');
    var fieldObject = filterOption.data('field-object');
    var isSpecial = (mQuery.inArray(fieldType, ['leadlist', 'campaign', 'device_type',  'device_brand', 'device_os', 'lead_email_received', 'lead_email_sent', 'tags', 'multiselect', 'boolean', 'select', 'country', 'timezone', 'region', 'stage', 'locale', 'globalcategory']) != -1);

    prototypeStr = prototypeStr.replace(/__name__/g, filterNum);
    prototypeStr = prototypeStr.replace(/__label__/g, label);

    // Convert to DOM
    prototype = mQuery(prototypeStr);

    var prefix = 'leadlist';
    var parent = mQuery(filterId).parents('.dynamic-content-filter, .dwc-filter');
    if (parent.length) {
        prefix = parent.attr('id');
    }

    var filterBase  = prefix + "[filters][" + filterNum + "]";
    var filterIdBase = prefix + "_filters_" + filterNum + "_";

    if (isSpecial) {
        var templateField = fieldType;
        if (fieldType == 'boolean' || fieldType == 'multiselect') {
            templateField = 'select';
        }

        var template = mQuery('#templates .' + templateField + '-template').clone();
        template.attr('name', mQuery(template).attr('name').replace(/__name__/g, filterNum));
        template.attr('id', mQuery(template).attr('id').replace(/__name__/g, filterNum));
        prototype.find('input[name="' + filterBase + '[filter]"]').replaceWith(template);
    }

    if (mQuery('#' + prefix + '_filters div.panel').length == 0) {
        // First filter so hide the glue footer
        prototype.find(".panel-heading").addClass('hide');
    }

    if (fieldObject == 'company') {
        prototype.find(".object-icon").removeClass('ri-user-6-fill').addClass('ri-building-2-line');
    } else {
        prototype.find(".object-icon").removeClass('ri-building-2-line').addClass('ri-user-6-fill');
    }
    prototype.find(".inline-spacer").append(fieldObject);

    prototype.find("a.remove-selected").on('click', function() {
        mQuery(this).closest('.panel').animate(
            {'opacity': 0},
            'fast',
            function () {
                mQuery(this).remove();
                Mautic.reorderSegmentFilters();
            }
        );
    });

    prototype.find("input[name='" + filterBase + "[field]']").val(elId);
    prototype.find("input[name='" + filterBase + "[type]']").val(fieldType);
    prototype.find("input[name='" + filterBase + "[object]']").val(fieldObject);

    var filterEl = (isSpecial) ? "select[name='" + filterBase + "[filter]']" : "input[name='" + filterBase + "[filter]']";

    prototype.appendTo('#' + prefix + '_filters');

    var filter = mQuery('#' + filterIdBase + 'filter');

    //activate fields
    if (isSpecial) {
        if (fieldType == 'select' || fieldType == 'multiselect' || fieldType == 'boolean' || fieldType == 'leadlist') {
            // Generate the options
            var fieldOptions = filterOption.data("field-list");
            mQuery.each(fieldOptions, function(val, index) {
                if (mQuery.isPlainObject(index)) {
                    var optGroup = index;
                    mQuery.each(optGroup, function(value, index) {
                        mQuery('<option class="' + optGroup + '">').val(index).text(value).appendTo(filterEl);
                    });
                    mQuery('.' + index).wrapAll("<optgroup label='"+index+"' />");
                } else {
                    mQuery('<option>').val(index).text(val).appendTo(filterEl);
                }
            });
        }
    } else if (fieldType == 'lookup') {
        var fieldCallback = filterOption.data("field-callback");
        if (fieldCallback && typeof Mautic[fieldCallback] == 'function') {
            var fieldOptions = filterOption.data("field-list");
            Mautic[fieldCallback](filterIdBase + 'filter', elId, fieldOptions);
        } else {
            filter.attr('data-target', alias);
            Mautic.activateLookupTypeahead(filter.parent());
        }
    } else if (fieldType == 'datetime') {
        filter.datetimepicker({
            format: 'Y-m-d H:i',
            lazyInit: true,
            validateOnBlur: false,
            allowBlank: true,
            scrollMonth: false,
            scrollInput: false
        });
    } else if (fieldType == 'date') {
        filter.datetimepicker({
            timepicker: false,
            format: 'Y-m-d',
            lazyInit: true,
            validateOnBlur: false,
            allowBlank: true,
            scrollMonth: false,
            scrollInput: false,
            closeOnDateSelect: true
        });
    } else if (fieldType == 'time') {
        filter.datetimepicker({
            datepicker: false,
            format: 'H:i',
            lazyInit: true,
            validateOnBlur: false,
            allowBlank: true,
            scrollMonth: false,
            scrollInput: false
        });
    } else if (fieldType == 'lookup_id') {
        //switch the filter and display elements
        var oldFilter = mQuery(filterEl);
        var newDisplay = oldFilter.clone();
        newDisplay.attr('name', filterBase + '[display]')
            .attr('id', filterIdBase + 'display');

        var oldDisplay = prototype.find("input[name='" + filterBase + "[display]']");
        var newFilter = mQuery(oldDisplay).clone();
        newFilter.attr('name', filterBase + '[filter]');
        newFilter.attr('id', filterIdBase + 'filter');

        oldFilter.replaceWith(newFilter);
        oldDisplay.replaceWith(newDisplay);

        var fieldCallback = filterOption.data("field-callback");
        if (fieldCallback && typeof Mautic[fieldCallback] == 'function') {
            var fieldOptions = filterOption.data("field-list");
            Mautic[fieldCallback](filterIdBase + 'display', elId, fieldOptions);
        }
    } else {
        filter.attr('type', fieldType);
    }

    var operators = filterOption.data('field-operators');
    mQuery('#' + filterIdBase + 'operator').html('');
    mQuery.each(operators, function (label, value) {
        var newOption = mQuery('<option/>').val(value).text(label);
        newOption.appendTo(mQuery('#' + filterIdBase + 'operator'));
    });

    // Convert based on first option in list
    Mautic.convertDwcFilterInput('#' + filterIdBase + 'operator');

    // Reposition if applicable
    Mautic.updateFilterPositioning(mQuery('#' + filterIdBase + 'glue'));
};

Mautic.convertDwcFilterInput = function(el) {
    var prefix = 'leadlist';

    var parent = mQuery(el).parents('.dynamic-content-filter, .dwc-filter');
    if (parent.length) {
        prefix = parent.attr('id');
    }

    var operator = mQuery(el).val();

    // Extract the filter number
    var regExp    = /_filters_(\d+)_operator/;
    var matches   = regExp.exec(mQuery(el).attr('id'));
    var filterNum = matches[1];
    var filterId  = '#' + prefix + '_filters_' + filterNum + '_filter';

    // Reset has-error
    if (mQuery(filterId).parent().hasClass('has-error')) {
        mQuery(filterId).parent().find('div.help-block').hide();
        mQuery(filterId).parent().removeClass('has-error');
    }

    var disabled = (operator == 'empty' || operator == '!empty');
    mQuery(filterId+', #' + prefix + '_filters_' + filterNum + '_display').prop('disabled', disabled);

    if (disabled) {
        mQuery(filterId).val('');
    }

    var newName = '';
    var lastPos;

    if (mQuery(filterId).is('select')) {
        var isMultiple  = mQuery(filterId).attr('multiple');
        var multiple    = (operator == 'in' || operator == '!in');
        var placeholder = mQuery(filterId).attr('data-placeholder');

        if (multiple && !isMultiple) {
            mQuery(filterId).attr('multiple', 'multiple');

            // Update the name
            newName =  mQuery(filterId).attr('name') + '[]';
            mQuery(filterId).attr('name', newName);

            placeholder = mauticLang['chosenChooseMore'];
        } else if (!multiple && isMultiple) {
            mQuery(filterId).removeAttr('multiple');

            // Update the name
            newName = mQuery(filterId).attr('name');
            lastPos = newName.lastIndexOf('[]');
            newName = newName.substring(0, lastPos);

            mQuery(filterId).attr('name', newName);

            placeholder = mauticLang['chosenChooseOne'];
        }

        if (multiple) {
            // Remove empty option
            mQuery(filterId).find('option[value=""]').remove();

            // Make sure none are selected
            mQuery(filterId + ' option:selected').removeAttr('selected');
        } else {
            // Add empty option
            mQuery(filterId).prepend("<option value='' selected></option>");
        }

        // Destroy the chosen and recreate
        Mautic.destroyChosen(mQuery(filterId));

        mQuery(filterId).attr('data-placeholder', placeholder);

        Mautic.activateChosenSelect(mQuery(filterId));
    }
};

Mautic.standardDynamicContentUrl = function(options) {
    if (!options) {
        return;
    }

    var url = options.windowUrl;
    if (url) {
        var editDynamicContentKey = '/dwc/edit/dynamicContentId';
        var previewDynamicContentKey = '/dwc/preview/dynamicContentId';
        if (url.indexOf(editDynamicContentKey) > -1 ||
            url.indexOf(previewDynamicContentKey) > -1) {
            options.windowUrl = url.replace('dynamicContentId', mQuery('#campaignevent_properties_dynamicContent').val());
        }
    }

    return options;
};

Mautic.disabledDynamicContentAction = function(opener) {
    if (typeof opener == 'undefined') {
        opener = window;
    }

    var dynamicContent = opener.mQuery('#campaignevent_properties_dynamicContent').val();

    var disabled = dynamicContent === '' || dynamicContent === null;

    opener.mQuery('#campaignevent_properties_editDynamicContentButton').prop('disabled', disabled);
};
