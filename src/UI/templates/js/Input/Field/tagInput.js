/**
 * Wraps the BootstrapTagsInput
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
var il = il || {};
il.UI = il.UI || {};
il.UI.Input = il.UI.Input || {};
(function ($) {
    il.UI.Input.tagInput = (function ($) {


        /**
         *
         * @param raw_id
         * @param config
         */
        var init = function (raw_id, config) {
            var _DEBUG = false;
            var _CONFIG = {
                id: '',
                options: [],
                selected_options: [],
                extendable: false,
                suggestion_starts: 1,
                max_chars: 2000,
                suggestion_limit: 50,
                debug: _DEBUG,
                allow_duplicates: false,
                highlight: true,
                hint: true,
                tag_class: "label label-primary"
            };

            var _ELEMENTS = {
                hidden_template: null,
                container: null
            };

            var _log = function (key, data) {
                if (!_DEBUG) {
                    return;
                }
                console.log("***********************");
                console.log(key + ":");
                console.log(data);
            };


            var _initBloodhound = function () {
                var bloodHoundObj = new Bloodhound({
                    datumTokenizer: Bloodhound.tokenizers.whitespace,
                    queryTokenizer: Bloodhound.tokenizers.whitespace,
                    local: _CONFIG.options
                });
                bloodHoundObj.initialize();

                return bloodHoundObj;
            };

            // Initialize ID and Configuration
            var id = '#' + raw_id;
            _CONFIG = $.extend(
                _CONFIG
                , config);
            _DEBUG = _CONFIG.debug;
            _CONFIG.id = raw_id;
            _log("config", _CONFIG);

            // Elements
            _ELEMENTS.hidden_template = $('#template-' + _CONFIG.id);
            _ELEMENTS.container = $('#container-' + _CONFIG.id);

            // Bloodhound
            var localSource = _initBloodhound();
            _log('datasources', localSource);

            // TagInput
            $(id).tagsinput({
                tagClass: _CONFIG.tag_class,
                cancelConfirmKeysOnEmpty: false,
                maxChars: _CONFIG.max_chars,
                allowDuplicates: _CONFIG.allow_duplicates,
                trimValue: true,
                freeInput: _CONFIG.extendable,
                typeaheadjs: {
                    name: 'local',
                    minLength: _CONFIG.suggestion_starts,
                    highlight: _CONFIG.highlight,
                    hint: _CONFIG.hint,
                    limit: _CONFIG.suggestion_limit,
                    source: localSource.ttAdapter()
                }
            });

            // Hooks
            $(id).on('beforeItemAdd', function (event) {
                _log("item", event.item);
            });

            $(id).on('itemAdded', function (event) {
                var new_hidden = _ELEMENTS.hidden_template.clone();
                new_hidden.attr("id", "tag-" + _CONFIG.id + "-" + event.item);
                new_hidden.attr("name", _ELEMENTS.hidden_template.val());
                new_hidden.val(event.item);
                _log('add_hidden', new_hidden);
                new_hidden.appendTo(_ELEMENTS.container);
            });

            $(id).on('itemRemoved', function (event) {
                var hidden = $("#tag-" + _CONFIG.id + "-" + event.item);
                _log('remove_hidden', hidden);
                hidden.remove();
            });
        };


        return {
            init: init
        };

    })($);
})($, il.UI.Input);
