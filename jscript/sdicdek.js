/*
 * sdicdek.js  20.11.20 19:14 
 * Created for project VamShop 1.x
 * Version 1.0.0
 * subpackage sdicdek - shipping module CDEK
 * https://econsultlab.ru
 * mail: info@econsultlab.ru
 * Released under the GNU General Public License
 * Copyright (c) 2020 Econsult Lab. 
 */

var SDICDEK = {
    _url: "includes/modules/shipping/pickpoint/pickpoint.php",
    _payment_type:'',
    _prefix:'',
    _$link_block: "",
    _$ajaxlockPane: "",
    _$ajaxLoader: "",
    _widjet:"",
    _msgs:"",
    _$address_text:"",
    _$address:"",
    _$price:"",
    _$full_cost:"",
    _$days:"",
    _$sdicdek:"",
    _$error:"",

    _callback : function () {
        alert('SDICDEK Self callback;');
    },
    _buildAJAXLoader: function(){
        var content = '<div id="ajaxLoading">' +
            '<img src="../images/loading.gif"></div>'+
            '<div id="skm_LockPane" class="LockOff"></div>';
        $("body").append(content);
    },

    _ajaxLock : function(){
        if(SDICDEK._$ajaxlockPane.hasClass('LockOff'))
            SDICDEK._$ajaxlockPane.removeClass('LockOff');
        if(!SDICDEK._$ajaxlockPane.hasClass('LockOn'))
            SDICDEK._$ajaxlockPane.addClass('LockOn');
        SDICDEK._$ajaxLoader.show();
    },
    _ajaxUnLock : function(){
        if(!SDICDEK._$ajaxlockPane.hasClass('LockOff'))
            SDICDEK._$ajaxlockPane.addClass('LockOff');
        if(SDICDEK._$ajaxlockPane.hasClass('LockOn'))
            SDICDEK._$ajaxlockPane.removeClass('LockOn');
        SDICDEK._$ajaxLoader.hide();
    },

    init: function(params){
        // проверяем подключение базового скрипта и если не подключен то, подключаем
        if (typeof(params.callback) == 'function')
            this._callback = params.callback;

        if(!jQuery("script#ISDEKscript").length){
            //console.log("нет скрипта #ISDEKscript !!!");
            var script = document.createElement("script");
            script.src = "../widget/widjet.js";
            script.id = "ISDEKscript";
            document.head.appendChild(script);
            //console.log("Добавлена декларация скрипта #ISDEKscript.");
        }

        if($('#skm_LockPane').length && $('#skm_LockPane')){
            this._buildAJAXLoader();
        }

        this._$ajaxlockPane = $("#skm_LockPane");
        this._$ajaxLoader = $("#ajaxLoading");
        this._payment_type = params.payment_type;
        this._msgs = params.msgs;
        this._prefix = this._payment_type.toLowerCase();
        this._$link_block = $("#sdicdek"+this._prefix+"_link").parents(".shipping-method-text");
        this._$address = $("#sdicdek"+this._prefix+"_address");
        this._$address_text = $("#sdicdek"+this._prefix+"_address_text");
        this._$price = $("#sdicdek"+this._prefix+"_price");
        this._$full_cost = this._$price.siblings("input[name = \"full_cost\"]");
        this._$days = $("#sdicdek"+this._prefix+"_days");
        this._$sdicdek = $("#sdicdek"+this._prefix);
        this._$error =  $("#sdicdek"+this._prefix+"_error");
        this._$link_block.hide();
        var sdicdek_params = params.sdicdek_params;
        var url = "includes/modules/shipping/sdicdek/sdicdek.php";
        var _this = this;
        this._ajaxRequest(url,"getWidjetParams",sdicdek_params, function(data){
            data = $.parseJSON(data);
            //console.log("getWidjetParams",data);
            _this._initWidjet(data);
            _this._$link_block.show(500);
            _this._callback(SDICDEK._widjet);
        });
    },

    _initWidjet: function(data){
        SDICDEK._widjet = new ISDEKWidjet({
            showWarns: true,
            showErrors: true,
            showLogs: true,
            hideMessages: false,
            choose: true,
            popup: true,
            country: "Россия",
            defaultCity: data.receiverCity,
            cityFrom: data.senderCity,
            link: null,
            hidedress: false,
            hidecash: false,
            hidedelt: true,
            detailAddress: false,
            region: true,
            apikey: data.apiKey,
            goods: data.goods,
            onChoose: function(wat){SDICDEK._onChoose(wat);},
        });
    },

    _onChoose: function(wat){
        console.log("_onChoose",wat);
        var pvz_text = wat.PVZ.Postamat?SDICDEK._msgs.MODULE_SHIPPING_SDICDEK_ATP_TEXT_ADDRESS:SDICDEK._msgs.MODULE_SHIPPING_SDICDEK_PVZ_TEXT_ADDRESS;

        pvz_text += ' <strong>' + wat.id + '</strong> "' + wat.PVZ.Name + '" по адресу:<br>' + wat.cityName + ' ' + wat.PVZ.Address + '<br>' + SDICDEK._getOptionsString(wat.PVZ) + '<br>';
        SDICDEK._$address_text.html(pvz_text);
        var pure_text = pvz_text.replace("<br>"," ").replace(/\\"/g, "").replace(/\&nbsp;/g, " ").replace(/(<([^>]+)>)/ig,"");
        SDICDEK._$address.val(pure_text);

        // обновим стоимость с учетом лоп коэффициентов
        var url = "includes/modules/shipping/sdicdek/sdicdek.php";
        var _this = this;
        var params ={
            cost:wat.price,
            term: wat.term,
        };

        var _this = this;
        this._ajaxRequest(url,"updateDeliveryCost",params, function(data){
            data = $.parseJSON(data);
            //console.log("updateDeliveryCost",data);
            _this._updateDeliveryCost(data);

        });

    },

    _getOptionsString: function(pvz){
        var out = "";
        out =out + '<i class="fa fa-check-square-o" aria-hidden="true"></i>&nbsp;Оплата наличными';
        out = out + '<br>';
        if (pvz.Cash) {
            out =out + '<i class="fa fa-square-o" aria-hidden="true"></i>&nbsp;Оплата банковской картой';
        } else {
            out =out + '<i class="fa fa-check-square-o" aria-hidden="true"></i>&nbsp;Оплата банковской картой';
        }

        out = out + '<br>';

        if (pvz.Dressing == 0) {
            out =out + '<i class="fa fa-square-o" aria-hidden="true"></i>&nbsp;Примерочная';
        } else {
            out =out + '<i class="fa fa-check-square-o" aria-hidden="true"></i>&nbsp;Примерочная';
        }

        out = out + '<br>';

        if (pvz.Phone){
            out =out + ('<i class="fa fa-phone" aria-hidden="true"></i>&nbsp;' + pvz.Phone);
        }

        out = out + '<br>';

        if (pvz.WorkTime){
            out =out + ('<i class="fa fa-clock-o" aria-hidden="true"></i>&nbsp;' + pvz.WorkTime);
        }
        out = out + '<br>';
        return out;
    },

    _updateDeliveryCost: function(data) {
        //console.log("_updateDeliveryCost",data);
        SDICDEK._$price.text(data.FullPriceFormatted);
        SDICDEK._$full_cost.val(data.FullPrice);
        if(typeof data.DPMin !== "undefined" && typeof data.DPMax !== "undefined") {
            var di ="";
            if (data.DPMin != data.DPMax){
                di = SDICDEK._msgs.MODULE_SHIPPING_SDICDEK_SUCCESS_DELIVERY+' от '+data.DPMinFormatted+' до '+data.DPMaxFormatted;
            } else {
                $di = SDICDEK._msgs.MODULE_SHIPPING_SDICDEK_SUCCESS_DELIVERY+' от '+data.DPMinFormatted;
            }
            SDICDEK._$days.text(di);
        }
        // разрешаем выбор способа доставки ,если ошибки нет
        if(typeof data.error !== "undefined") {
            SDICDEK._$sdicdek.prop("checked", false).trigger("change").attr("disabled","disabled");
            SDICDEK._showError(data.error);
        } else {
            SDICDEK._$sdicdek.removeAttr("disabled").prop("checked", true).trigger("change").trigger("click");
        }
    },

    _showError: function(error){
        if(SDICDEK._$error.html())
            SDICDEK._$error.html("");
        SDICDEK._$error.html(error);
    },

    _ajaxRequest: function(url, method, params, callback) {
        var _this = this;
        $.ajax({
            url: "index_ajax.php",
            dataType : "html",
            data: {q : url, method: method, sdicdek_payment_type: this._payment_type, sdicdek_params: params},
            type: "GET",

            success: callback,
            beforeSend: this._beforeSendCallback,
            error: this._errCallback,
            complete: this._doneCallback
        });
    },

    _beforeSendCallback: function(){
        //console.log('SDICDEK beforeSend');
        SDICDEK._ajaxLock();
    },

    _errCallback: function(res) {
        console.log('SDICDEK error', res);
        SDICDEK._ajaxUnLock();
    },

    _doneCallback: function(res) {
        //console.log('SDICDEK complete');
        SDICDEK._ajaxUnLock();
    }
};
