define([
    'jquery',
    'Magento_Ui/js/grid/columns/column',
    'mage/url',
    'mage/storage'
], function($, Column, urlBuilder, storage) {
    'use strict';

    let timeoutId;

    return Column.extend({
        defaults: {
            bodyTmpl: 'Zaahed_Serialnumber/grid/cells/serialnumbers'
        },

        /**
         * Get order item data with serial numbers.
         *
         * @param {Object} record - Record object.
         * @return array
         */
        getOrderItems: function(record) {
            return record['serialnumbers'];
        },

        /**
         * Get serial numbers
         *
         * @param {String} item_id
         * @return array
         */
        getSerialnumbers: function(item_id) {
            return this._getTextArea(item_id)
                .val()
                .split('\n')
                .filter(item => item !== '');
        },

        /**
         * Save entered serial numbers.
         *
         * @param {Object} record - Record object.
         * @param {String} item_id
         * @return void
         */
        saveSerialnumbers: function(record, item_id) {
            const column = this;
            const errorMessagesLimit = 3;
            const apiConfig = this.getSerialnumberApiConfig(record, item_id);
            const serialnumbers = this.getSerialnumbers(item_id);
            let textarea = this._getTextArea(item_id);

            clearTimeout(timeoutId);

            timeoutId = setTimeout(() => {
                textarea.prop('disabled', true);
                this._setTextAreaMessage(item_id, 'Saving...');
                // noinspection JSVoidFunctionReturnValueUsed
                storage.put(
                    apiConfig['api_url'],
                    JSON.stringify({
                        itemId: item_id,
                        serialnumbers: serialnumbers,
                        'form_key=': FORM_KEY
                    }),
                    true,
                    'application/json',
                    {
                        'Authorization': 'Bearer ' + apiConfig['token']
                    }
                ).done(
                    function(response) {
                        column._setTextAreaMessage(item_id,'Saved', true);
                    }
                ).fail(
                    function(response) {
                        let errorMessages = response.responseJSON.errors;
                        errorMessages = errorMessages.map((error) => {
                            if (!error.parameters.length) {
                                return error.message;
                            }

                            let message = error.message;
                            for (const [index, param] of error.parameters.entries()) {
                                message = message.replace(`%${index + 1}`, param);
                            }
                            return message;
                        })
                            .slice(0, errorMessagesLimit)
                            .join('<br>');
                        column._setTextAreaMessage(item_id, errorMessages, false);
                    }
                ).always(
                    function(response) {
                        textarea.prop('disabled', false);
                    }
                )
            }, 1000);

        },

        /**
         * Get API config for serial numbers.
         *
         * @param {Object} record - Record object.
         * @param {String} item_id
         * @return array|null
         */
        getSerialnumberApiConfig: function(record, item_id) {
            const orderItems = this.getOrderItems(record);
            for(const item of orderItems) {
                if (item['item_id'] === item_id) {
                    return item['config'];
                }
            }

            return null;
        },

        /**
         * Get text area element.
         *
         * @param {String} item_id
         * @return {Object}
         */
        _getTextArea: function(item_id) {
            const itemIdClass = '.item-id-' + item_id;
            return $(itemIdClass + ' .serialnumbers-input');
        },

        /**
         * Set text area status.
         *
         * @param {String} item_id
         * @param {String} message
         * @param {Boolean} success
         * @return void
         */
        _setTextAreaMessage(item_id, message, success= null) {
            const itemIdClass = '.item-id-' + item_id;
            const statusElement = $(itemIdClass + ' .status');
            statusElement.html(message);
            statusElement.show();

            if (success === true) {
                statusElement.removeClass('failed');
                statusElement.addClass('success');
            } else if (success === false) {
                statusElement.removeClass('success');
                statusElement.addClass('failed');
            } else if (success === null) {
                statusElement.removeClass('success');
                statusElement.removeClass('failed');
            }
        }
    });
});