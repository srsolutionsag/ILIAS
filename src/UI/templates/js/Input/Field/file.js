/**
 * file.js
 *
 * @author Thibeau Fuhrer <thf@studer-raimann.ch>
 *
 * This script wraps the Dropzone.js library for the UI Component
 * \ILIAS\UI\Implementation\Component\Input\Field.
 */

var il = il || {};
il.UI = il.UI || {};
il.UI.Input = il.UI.Input || {};

Dropzone.autoDiscover = false;

(function ($, UI) {

    /**
     * Public interface of a file-input element.
     *
     * @type {{init: init, renderFileListEntry: renderFileListEntry}}
     */
    il.UI.Input.file = (function ($) {

        /**
         * Constant to enable or disable the debugging of a file-input
         *
         * @type {boolean}
         */
        const DEBUG = true;

        /**
         * Default settings used for dropzone.js initialization.
         *
         * @type {object}
         */
        const DEFAULT_SETTINGS = {
            file_upload_url:    '',
            file_removal_url:   '',
            file_info_url:      '',
            file_identifier:    'file_id',
            max_file_amount:    1,
            file_mime_types:    null,
            existing_files:     null,
            max_file_size:      null,
            with_nested_inputs: false,
        };

        /**
         * Default file-input translation messages.
         *
         * @type {object}
         */
        const DEFAULT_TRANSLATIONS = {
            msg_invalid_mime:   'Type of uploaded file(s) is not supported.',
            msg_invalid_amount: 'Too many files were uploaded at once.',
            msg_invalid_size:   'Max file-size exceeded, upload aborted.',
            msg_upload_failure: 'Something went wrong when uploading...',
            msg_upload_success: 'File(s) successfully uploaded.',
        };

        /**
         * Selectors used for DOM manipulations within a file-input element.
         *
         * @type {object}
         */
        const SELECTOR = {
            dropzone:           '.il-file-input-dropzone',
            action_btn:         '.il-file-input-dropzone button',
            submit_btn:         '.il-standard-form-cmd > button',
            file_list:          '.il-file-input-list',
            file_preview:       '.il-file-input-preview',
            file_input:         '.il-file-input',
            file_input_tpl:     '.il-file-input-template',
            nested_inputs:      '.il-file-input-metadata',
            inputs_toggle:      '.il-file-input-preview .metadata-toggle',
            file_removal:       '.il-file-input-preview .remove',
            file_error_msg:     '.il-file-input-upload-error',
            toggle_glyph:       '.metadata-toggle .glyph',
            close_glyph:        '.remove',
        };

        /**
         * Keeps track of the amount of dropzones (file-inputs) that
         * have to be processed.
         *
         * @type {number}
         */
        let dropzone_amount = 1;

        /**
         * Holds the current number of the iterator variable during
         * the processing of multiple dropzones (file-inputs).
         *
         * @type {number}
         */
        let current_dropzone = 0;

        /**
         * Holds the current form of which the file-inputs are being processed of.
         *
         * @type {object}
         */
        let current_form = {};

        /**
         * File-input translations used within this element.
         *
         * @type {object}
         */
        let translations = {};

        /**
         * Holds all instances of dropzones instantiated for the current context.
         *
         * @type {Dropzone[]}
         */
        let instances = [];

        /**
         * If ONE file-input was instantiated, this will be set to true.
         *
         * Therefore this variable is used for things that have to be
         * done just once.
         *
         * @type {boolean}
         */
        let instantiated = false;

        /**
         * Helper function to debug a file-input.
         *
         * @param {*} variables
         */
        let debug = function (...variables) {
            if (DEBUG) {
                for (let i in variables) {
                    console.log(variables[i]);
                }
            }
        };

        /**
         * Handles files added by dropzone.js, adjusts the files preview element.
         *
         * @param {File} file
         */
        let addFileHook = function (file) {
            $(file.previewElement).find(SELECTOR.file_input_tpl).val(file.file_id);
            debug(file);
        };

        /**
         * Removes a file from the list and server manually, if
         * the element was not rendered by dropzone.js.
         *
         * @param {Event} event
         */
        let removeFileManuallyHook = async function (event) {
            let preview = $(this).parent().parent();

            // trigger manual removal only if the pseudo class for
            // server-side rendering is provided.
            if (preview.hasClass('il-file-input-server-side')) {
                // create pseudo file object in order to work with removal hook
                let file = Object.assign({
                    status: 'success',
                    file_id: preview.find(SELECTOR.file_input_tpl).val(),
                });

                // remove file preview from DOM.
                preview.remove();

                await removeFileHook(file);
            }
        };

        /**
         * Removes files from the server that were removed
         * from the dropzone.js file-list.
         *
         * @param {File} file
         */
        let removeFileHook = async function (file) {
            // if the file-status is not successful, the file doesn't
            // need to be removed.
            if ('success' === file.status) {
                await $.ajax({
                    type: 'GET',
                    url:  settings.file_removal_url,
                    data: {
                        [settings.file_identifier]: file.file_id
                    },
                    success: function(response) {
                        response = Object.assign(JSON.parse(response));
                        if (1 !== response.status) {
                            uploadFailureHook(file, response.message, response);
                        } else {
                            debug("File successfully removed.");
                        }
                    },
                    error: function(response) {
                        uploadFailureHook(file, `Failed to remove file: ${file.file_id}`, response);
                    }
                });
            }

            debug(file);
        };

        /**
         * Handles the successful upload of a file by dropzone.js.
         *
         * @param {File}   file
         * @param {string} json_response
         */
        let uploadSuccessHook = function (file, json_response) {
            let response = Object.assign(JSON.parse(json_response));
            if (1 === response.status) {
                // override dropzone.js file-id with IRSS file-id.
                file.file_id = response[settings.file_identifier];
                addFileHook(file);
            } else {
                uploadFailureHook(file, response.message, response);
            }

            debug(file, response);
        };

        /**
         * Handles the unsuccessful upload of a file by dropzone.js.
         *
         * @param {File}   file
         * @param {string} message
         * @param {xhr}    response
         */
        let uploadFailureHook = function (file, message, response) {
            // give feedback to user (highlight list-entry red, show message).
            $(file.previewElement).addClass('alert-danger');
            $(file.previewElement).find(SELECTOR.file_error_msg).text(message);

            debug(file, message, response);
        };

        /**
         * Processes the dropzone's queue before submitting the form.
         *
         * @param {Event} event
         */
        let formSubmissionHook = function (event) {
            event.preventDefault();

            // set the currently processed form id
            current_form = $(this).closest('form');

            // disable ALL submit buttons on the current page,
            // so the data is submitted AFTER the queue is
            // processed (finishQueueHook is triggered).
            $(document)
                .find(SELECTOR.submit_btn)
                .each(function() {
                    $(this).attr('disabled', true);
                })
            ;

            // fetch dropzone(s) of the current form and process their queues.
            let file_inputs = current_form.find(SELECTOR.file_input);

            // @TODO: processQueue() is not working :(.
            //
            // in case multiple file-inputs were added to ONE form, they
            // all need to be processed.
            if (Array.isArray(file_inputs)) {
                dropzone_amount = file_inputs.length;
                for (let i = 0; i < file_inputs.length; i++) {
                    let dropzone = instances[file_inputs[i].attr('id')];
                    dropzone.processQueue();
                }
            } else {
                let dropzone = instances[file_inputs.attr('id')];
                dropzone.processQueue();
            }
        };

        /**
         * Handles what happens after a dropzone's queue is processed.
         */
        let finishQueueHook = function () {
            debug("FINISH HOOK CALLED", current_form, current_dropzone, dropzone_amount);
            // submit the currently processed form if all dropzone queues are finished.
            current_dropzone = current_dropzone + 1;
            if (current_dropzone === dropzone_amount) {
                current_form.submit();
            }
        };

        /**
         * Toggles the state of each list-entry's metadata inputs section.
         *
         * @param {Event} event
         */
        let toggleMetadataHook = function (event) {
            $(this)
                .parent()
                .parent()
                .find(SELECTOR.nested_inputs)
                .toggle()
            ;

            $(this)
                .parent()
                .find(SELECTOR.toggle_glyph)
                .each(function () {
                        $(this).toggle();
                }
            );
        };

        /**
         * Returns the prepared file previews HTML.
         *
         * @param {string} id
         * @return {string}
         */
        let getPreparedFilePreview = function (id) {
            let preview  = $(`#${id} ${SELECTOR.file_preview}`);
            let metadata = $(SELECTOR.file_preview).find(SELECTOR.metadata);

            if (undefined !== metadata) {
                // if metadata inputs were provided, the toggle is set up.
                preview.find(SELECTOR.toggle_glyph + ':first').hide();
            }

            // remove initial preview HTML from the page.
            if (!DEBUG) {
                preview.remove();
            }

            // return html of the outer element, so the whole file preview
            // element is returned.
            return $(`#${id} ${SELECTOR.file_list}`).html();
        };

        /**
         * Helper function to manage the event-listeners of a file-input element.
         *
         * @param {Dropzone} dropzone
         */
        let initEventListeners = function (dropzone) {
            // general event-listeners
            if (!instantiated) {
                $(SELECTOR.dropzone).closest('form').on('click', SELECTOR.submit_btn, formSubmissionHook);
                $(SELECTOR.file_list).on('click', SELECTOR.toggle_glyph, toggleMetadataHook);
                $(SELECTOR.file_list).on('click', SELECTOR.close_glyph, removeFileManuallyHook)
            }

            // dropzone.js event-listeners
            dropzone.on('queuecomplete', finishQueueHook);
            // dropzone.on('processing', processFileHook)
            dropzone.on('removedfile', removeFileHook);
            dropzone.on('fileadded', addFileHook);
            dropzone.on('success', uploadSuccessHook);
            dropzone.on('error', uploadFailureHook);
        };

        /**
         * Initializes a file-input element.
         *
         * @param {string} id
         * @param {string} json_settings
         */
        let init = function (id, json_settings) {
            // parse json settings to object and override defaults.
            let settings = Object.assign(DEFAULT_SETTINGS, JSON.parse(json_settings));
            debug(settings);

            // parse translations given by json settings and override defaults.
            if (!instantiated) {
                translations = Object.assign(DEFAULT_TRANSLATIONS, settings.translations);
                debug(translations);
            }

            // file list and action button must be fetched with vanilla js in
            // order to work properly with dropzone.js.
            let file_list  = document.querySelector(`#${id} ${SELECTOR.file_list}`);
            let action_btn = document.querySelector(`#${id} ${SELECTOR.action_btn}`);
            debug(file_list, action_btn);

            // initialize the dropzone.js element.
            let dropzone = new Dropzone(`#${id} ${SELECTOR.dropzone}`, {
                url:                encodeURI(settings.file_upload_url),
                uploadMultiple:     (1 < settings.max_file_amount),
                maxFiles:           settings.max_file_amount,
                maxFileSize:        settings.max_file_size,
                acceptedFiles:      settings.file_mime_types,
                previewTemplate:    getPreparedFilePreview(id),
                previewsContainer:  file_list,
                clickable:          action_btn,
                autoProcessQueue:   false,
                parallelUploads:    1, // maybe allow more?
            });

            initEventListeners(dropzone);
            debug(dropzone);

            instances[id] = dropzone;
            instantiated = true;
        };

        /**
         * Renders an existing file-preview within a file-input.
         *
         * @param {string} file_input_id
         * @param {File}   file
         */
        let renderFileListEntry = function (file_input_id, file) {
            let dropzone = instances[file_input_id];

            dropzone.files.push(file);
            dropzone.emit('drop');
            dropzone.emit('addedfile', file);
            dropzone._updateMaxFilesReachedClass();
        };

        return {
            renderFileListEntry: renderFileListEntry,
            init: init,
        };

    })($);
})($, il.UI);
