MistertangoJq = jQuery.noConflict();
MisterTango = {
    is_opened: false,
    success: false,
    order: null,
    disallow_different_payment: false,
    transaction: null,
    customer: null,
    total: null,
    currency: null,
    language: null,
    init: function () {		
        mrTangoCollect.load();
		MisterTango.transaction = null;
		MisterTango.customer =null;
		MisterTango.amount = null;
		MisterTango.currency = null;
		MisterTango.language =null;
		MisterTango.OffLinePayment =null;
        mrTangoCollect.set.recipient(mrTangoUsername);

        mrTangoCollect.onOpened = MisterTango.onOpen;
        mrTangoCollect.onClosed = MisterTango.onClose;

        mrTangoCollect.onSuccess = MisterTango.onSuccess;
        mrTangoCollect.onOffLinePayment = MisterTango.onOfflinePayment;

        MisterTango.initButtonPay();
    },
    initButtonPay: function () {
        MistertangoJq(document).on('click', '.mistertango-button-pay', function (e) {
            e.preventDefault();

            if (typeof MistertangoJq(this).data('ws-id') != 'undefined') {
                mrTangoCollect.ws_id = MistertangoJq(this).data('ws-id');
            }

            MisterTango.order = null;

            if (typeof MistertangoJq(this).data('order') != 'undefined') {
                MisterTango.order = MistertangoJq(this).data('order');
            }

            MisterTango.transaction = MistertangoJq(this).data('transaction');
            MisterTango.customer = MistertangoJq(this).data('customer');
            MisterTango.amount = MistertangoJq(this).data('amount');
            MisterTango.currency = MistertangoJq(this).data('currency');
            MisterTango.language = MistertangoJq(this).data('language');

            mrTangoCollect.set.payer(MisterTango.customer);
            mrTangoCollect.set.amount(MisterTango.amount);
            mrTangoCollect.set.currency(MisterTango.currency);
            mrTangoCollect.set.description(MisterTango.transaction);
            mrTangoCollect.set.lang(MisterTango.language);

            mrTangoCollect.submit();
        });
    },
    onOpen: function () {
        MisterTango.is_opened = true;
    },
    onOfflinePayment: function (response) {
		MisterTango.OffLinePayment=true;
        mrTangoCollect.onSuccess = function () {};
        MisterTango.onSuccess(response);
		
		
		
    },
    onSuccess: function (response) {
		var mrTangoOrderId= MistertangoJq( "#mrTangoOrderId").val();
		mrTangoUriConfirm_orderid = decodeURIComponent(mrTangoUrlConfirm)+"&wooorderid="+mrTangoOrderId;		
		MistertangoJq.ajax({
            type: 'GET',
            async: true,
            dataType: "json",
            url: MisterTango.order?mrTangoUriConfirm_orderid:mrTangoUriConfirm_orderid,
            headers: { "cache-control": "no-cache" },
            cache: false,
            data: {
                order: MisterTango.order?MisterTango.order:null,
                transaction: MisterTango.transaction,
                websocket: mrTangoCollect.ws_id,
                amount: MisterTango.amount
            },
            success: function(data)
            {
				if (data.success) {
                    MistertangoJq('.jsAllowDifferentPayment').remove();
                    MisterTango.disallow_different_payment = true;
                    MisterTango.order = data.order;
                    MisterTango.success = true;

                    if (MisterTango.is_opened === false) {
                        MisterTango.afterSuccess();
                    }
                }
            }
        });
    },
    onClose: function () {
        MisterTango.is_opened = false;
        if (MisterTango.success) {
            MisterTango.afterSuccess();
        }
    },
    afterSuccess: function () {
		decodedmrTangoUrlInformation = decodeURIComponent(mrTangoUrlInformation);
        var operator = decodedmrTangoUrlInformation.indexOf('?') === -1?'?':'&';transaction: MisterTango.transaction;
		var order_id=MisterTango.order;
		if(MisterTango.order=='undefined')
		{
			order_id='';
		}
		window.location.href = decodedmrTangoUrlInformation + operator + 'order=' + order_id + "&transaction=" + MisterTango.transaction;
    }
};
mrTangoUriScript = decodeURIComponent(mrTangoUrlScript);
MistertangoJq.getScript(mrTangoUriScript, function(data, textStatus, jqxhr) {
    MisterTango.init();
});

