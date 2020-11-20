<?php
/**
 * sdicdekpp.php  20.11.20 19:16 
 * Created for project VamShop 1.x
 * Version 1.0.0
 * subpackage sdicdek - shipping module CDEK
 * https://econsultlab.ru
 * mail: info@econsultlab.ru
 * Released under the GNU General Public License
 * Copyright (c) 2020 Econsult Lab. 
 */
include($_SERVER['DOCUMENT_ROOT'].'/lang/russian/modules/shipping/sdicdek.php');

define('MODULE_SHIPPING_SDICDEKPP_TEXT_TITLE', 'Самовывоз оплаченного заказа из ПВЗ СДЭК');
define('MODULE_SHIPPING_SDICDEKPP_TEXT_DESCRIPTION', 'Доставка предварительно оплаченных заказаов до ПВЗ СДЭК');

define('MODULE_SHIPPING_SDICDEKPP_STATUS_TITLE' , 'Разрешить модуль СДЭК по предоплате');
define('MODULE_SHIPPING_SDICDEKPP_STATUS_DESC' , 'Вы хотите разрешить модуль СДЭК по предоплате?');
define('MODULE_SHIPPING_SDICDEKPP_ALLOWED_TITLE' , 'Разрешённые страны');
define('MODULE_SHIPPING_SDICDEKPP_ALLOWED_DESC' , 'Укажите коды стран, для которых будет доступен данный модуль (например RU,DE (оставьте поле пустым, если хотите что б модуль был доступен покупателям из любых стран))');
define('MODULE_SHIPPING_SDICDEKPP_COST_TITLE' , 'Стоимость доставки');
define('MODULE_SHIPPING_SDICDEKPP_COST_DESC' , 'Стоимость доставки данным способом.');
define('MODULE_SHIPPING_SDICDEKPP_SORT_ORDER_TITLE' , 'Порядок сортировки');
define('MODULE_SHIPPING_SDICDEKPP_SORT_ORDER_DESC' , 'Порядок сортировки модуля.');
define('MODULE_SHIPPING_SDICDEKPP_FREE_TITLE' , 'Сумма заказа для бесплатной доставки.');
define('MODULE_SHIPPING_SDICDEKPP_FREE_DESC' , 'Сумма заказа (товара в заказе) при которой доставка этим способом бесплатна (указать только цифру). Если такой цены нет - оставить поле пустым.');
define('MODULE_SHIPPING_SDICDEKPP_TARIF_TITLE','Тариф');
define('MODULE_SHIPPING_SDICDEKPP_TARIF_DESC','Выберите тариф для расчета стоимости');

if (isset($_SESSION) &&  !isset($current_page) && $current_page !== "modules.php")
{
$js ='
<script type="text/javascript">
var widjetPP;
jQuery(document).ready(function(){
	if(typeof SDICDEK === "undefined") {
	    //console.log("SDICDEK is", typeof SDICDEK);
		$.getScript("jscript/sdicdek.js", function(){
		   //console.log("Скрипт sdicdek.js выполнен. SDICDEK is", typeof SDICDEK);
		   callCDEKWidjetPP(SDICDEK);
		 });
	} else {
	    callCDEKWidjetPP(SDICDEK);
	}
});


function callCDEKWidjetPP(SDICDEK){
	//console.log("callCDEKWidjetPP is started...");
	var params = {
	    '.(isset($order->products)?"products:".json_encode($order->products).",":"").'
	    '.(isset($order->delivery['city'])?"receiverCity:\"".$order->delivery['city']."\",":"").'
	};
	
	var sdicdek_params = {
		payment_type: "PP",
		sdicdek_params: params,
		msgs: {
			MODULE_SHIPPING_SDICDEK_ATP_TEXT_ADDRESS:"'.MODULE_SHIPPING_SDICDEK_ATP_TEXT_ADDRESS.'",
			MODULE_SHIPPING_SDICDEK_PVZ_TEXT_ADDRESS:"'.MODULE_SHIPPING_SDICDEK_PVZ_TEXT_ADDRESS.'",
			MODULE_SHIPPING_SDICDEK_SUCCESS_DELIVERY:"'.MODULE_SHIPPING_SDICDEK_SUCCESS_DELIVERY.'",
		},
		callback: onCDEKWidjetPPReady,
	};

	SDICDEK.init(sdicdek_params);
}

function onCDEKWidjetPPReady(widjet){
	//console.log("onCDEKWidjetPPReady is started...");
	widjetPP = widjet;
}

function runWidjetPP(){
    //console.log("started runWidjetPP ... ");
    if(typeof widjetPP === "undefined") {
        //console.log("widjetPP failed to start. Undefined!");
        return false;
    }
    widjetPP.open();
}
</script>
<input type="hidden" name="sdicdekpp_type" id="sdicdekpp_type" value="" />
<input type="hidden" name="sdicdekpp_full_cost" id="sdicdekpp_full_cost" value="" />
<input type="hidden" name="sdicdekpp_address" id="sdicdekpp_address" value="" />
<span id="sdicdekpp_address_text"></span>
<a href="javascript:void(0);" onclick=" runWidjetPP();"><span id="sdicdekpp_link" style="color:blue;">' . MODULE_SHIPPING_SDICDEK_TEXT_SELECT_ADDRESS . '</span></a><span id="sdicdekpp_link_help">' . MODULE_SHIPPING_SDICDEK_TEXT_ADDRESS_HELP . '</span>
';
	
	define('MODULE_SHIPPING_SDICDEKPP_TEXT_WAY', $js);

}
