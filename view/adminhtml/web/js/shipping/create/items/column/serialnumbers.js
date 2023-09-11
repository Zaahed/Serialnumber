define([
    'jquery',
], function($) {

    /**
     * Link qty value to multiselect serialnumber element so
     * that it can be used for validation.
     *
     * @param {Object} element
     * @param {Number} itemId
     * @return void
     */
    function linkQtyValueToElement(element, itemId) {
        let qtyInput = $(`input[name="shipment[items][${itemId}]`);

        // Set initial qty value on serialnumber multiselect element.
        element.setAttribute('qty', qtyInput.val());

        qtyInput.change(function() {
            element.setAttribute('qty', this.value);
        });
    }

    return function(config, element) {
        linkQtyValueToElement(element, config.item_id);
    }
});