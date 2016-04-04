MistertangoJq = jQuery.noConflict();
MisterTango.Information = {
    init: function () {
        setInterval(MisterTango.Information.updateOrderHistoriesTable, 30000);
    },
    updateOrderHistoriesTable: function () {
        MistertangoJq.ajax({
            type: 'GET',
            async: true,
            dataType: "json",
            url: mrTangoUrlHistories,
            headers: { "cache-control": "no-cache" },
            cache: false,
            data: {
                order: mrTangoOrderId
            },
            success: function(data)
            {
                MistertangoJq('#mistertango-information-order-histories').replaceWith(data.html_table_order_histories);
                if (MisterTango.disallow_different_payment) {
                    MistertangoJq('.jsAllowDifferentPayment').remove();
                }
            }
        });
    }
};

MistertangoJq(MisterTango.Information.init);
