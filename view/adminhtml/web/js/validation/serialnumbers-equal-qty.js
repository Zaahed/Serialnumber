define(['jquery'], function($) {
    'use strict';

    return function (targetWidget) {
        $.validator.addMethod(
            'serialnumbers-equal-qty',
            function(value, element) {
                let serialnumbersCount;
                let qty;

                serialnumbersCount = value === null ? 0 : value.length;
                qty = element.getAttribute('qty') === null ? 0 : parseInt(element.getAttribute('qty'));

                return serialnumbersCount === qty;
            },
            $.mage.__('Number of serialnumber(s) has to equal the qty.')
        )

        return targetWidget;
    }
});