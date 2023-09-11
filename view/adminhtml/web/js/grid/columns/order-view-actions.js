define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'Magento_Ui/js/grid/columns/actions',
    'uiRegistry'
], function($, modal, ActionsColumn, registry) {
    'use strict';

    /**
     * Get serial number exchange form.
     *
     * @returns {*}
     */
    function getForm() {
        return registry.get('serialnumber_exchange_form.areas');
    }

    /**
     * Initialize the serial number exchange modal.
     *
     */
    function initModal() {
        let options = {
            type: 'popup',
            title: 'Exchange Serial Number',
            buttons: [{
                text: $.mage.__('Exchange'),
                class: 'action-primary',
                click: function() {
                    let form = getForm();
                    form.save();
                    if (form.source.get('params.invalid')) {
                        return;
                    }
                    this.closeModal();

                    $('body').on('processStop', function() {
                        registry
                            .get('sales_order_view_serialnumber_listing.sales_order_view_serialnumber_listing')
                            .source
                            .reload({'refresh': true});
                    });
                }
            }]
        };

        $('#exchange-serialnumber-modal').modal(options);

    }

    return ActionsColumn.extend({

        /**
         * @inheritDoc
         */
        initialize: function() {
            this._super();
            initModal();

            return this;
        },

        /**
         * @inheritDoc
         */
        applyAction: function (actionIndex, rowIndex) {
            let action = this.getAction(rowIndex, actionIndex);
            if (!action.class.includes('exchange-action')) {
                return this._super();
            }

            $('#exchange-serialnumber-modal').modal('openModal');
            this._setExchangeFormValues(rowIndex);

            return this;
        },

        /**
         * @inheritDoc
         */
        isHandlerRequired: function(actionIndex, rowIndex) {
            let action = this.getAction(rowIndex, actionIndex);
            return action.class !== undefined && action.class.includes('exchange-action') ? true : this._super();
        },

        /**
         * Set order item serial number ID and current serial number from row index.
         *
         * @param {Int} rowIndex
         */
        _setExchangeFormValues: function(rowIndex) {
            var itemSerialnumberId = this.rows[rowIndex]['id'];
            var serialnumber = this.rows[rowIndex]['serialnumber'];
            getForm().reset();

            $('input[name="serialnumber_exchange[item_serialnumber_id]"]').val(itemSerialnumberId).change();
            $('input[name="serialnumber_exchange[current_serialnumber]"]').val(serialnumber).change();
        }
    });
});