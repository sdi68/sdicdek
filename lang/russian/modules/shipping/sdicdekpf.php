<?php
/**
 * sdicdekpf.php  20.11.20 19:16 
 * Created for project VamShop 1.x
 * Version 1.0.0
 * subpackage sdicdek - shipping module CDEK
 * https://econsultlab.ru
 * mail: info@econsultlab.ru
 * Released under the GNU General Public License
 * Copyright (c) 2020 Econsult Lab. 
 */
include($_SERVER['DOCUMENT_ROOT'].'/lang/russian/modules/shipping/sdicdek.php');

define('MODULE_SHIPPING_SDICDEKPF_TEXT_TITLE', 'Самовывоз заказа из ПВЗ СДЭК с оплатой при получении');
define('MODULE_SHIPPING_SDICDEKPF_TEXT_DESCRIPTION', 'Доставка заказаов до ПВЗ СДЭК с оплатой при получении');

define('MODULE_SHIPPING_SDICDEKPF_STATUS_TITLE' , 'Разрешить модуль СДЭК с оплатой при получении');
define('MODULE_SHIPPING_SDICDEKPF_STATUS_DESC' , 'Вы хотите разрешить модуль СДЭК с оплатой при получении?');
define('MODULE_SHIPPING_SDICDEKPF_ALLOWED_TITLE' , 'Разрешённые страны');
define('MODULE_SHIPPING_SDICDEKPF_ALLOWED_DESC' , 'Укажите коды стран, для которых будет доступен данный модуль (например RU,DE (оставьте поле пустым, если хотите что б модуль был доступен покупателям из любых стран))');
define('MODULE_SHIPPING_SDICDEKPF_COST_TITLE' , 'Стоимость доставки');
define('MODULE_SHIPPING_SDICDEKPF_COST_DESC' , 'Стоимость доставки данным способом.');
define('MODULE_SHIPPING_SDICDEKPF_SORT_ORDER_TITLE' , 'Порядок сортировки');
define('MODULE_SHIPPING_SDICDEKPF_SORT_ORDER_DESC' , 'Порядок сортировки модуля.');
define('MODULE_SHIPPING_SDICDEKPF_FREE_TITLE' , 'Сумма заказа для бесплатной доставки.');
define('MODULE_SHIPPING_SDICDEKPF_FREE_DESC' , 'Сумма заказа (товара в заказе) при которой доставка этим способом бесплатна (указать только цифру). Если такой цены нет - оставить поле пустым.');
define('MODULE_SHIPPING_SDICDEKPF_INSURANCE_TITLE' , 'Наценка за использование наложенного платежа');
define('MODULE_SHIPPING_SDICDEKPF_INSURANCE_DESC' , 'Наценка за использование наложенного платежа в % от стоимости заказа.<br>Zx% - некий процент от стоимости заказа; x - фиксированная стоимость. x - какое-либо число, Z режим: <b>p</b> - процент от стоимости товара, <b>d</b> - процент от стоимости доставки (с учётом суммы за сборку), <b>a (или отсутсвие буквы)</b> - процент от стоимости товара и доставки. <br><i>Указанная сумма (процент) прибавится к стоимости доставки.</i>');
define('MODULE_SHIPPING_SDICDEKPF_PFKOMISS_TITLE' , 'Комиссия за наложенный платеж');
define('MODULE_SHIPPING_SDICDEKPF_PFKOMISS_DESC' , 'Комиссия за наложенный платеж в %.');
define('MODULE_SHIPPING_SDICDEKPF_FEELIMIT_TITLE' , 'Ограничение суммы наложенного платежа');
define('MODULE_SHIPPING_SDICDEKPF_FEELIMIT_DESC' , 'Максимально разрешенная сумма наложенного платежа');
define('MODULE_SHIPPING_SDICDEKPF_FEELIMIT_ERROR' , 'Сумма заказа больше максимальной разрешенной суммы наложенного платежа. Доставка не возможна.');
define('MODULE_SHIPPING_SDICDEKPF_TARIF_TITLE','Тариф');
define('MODULE_SHIPPING_SDICDEKPF_TARIF_DESC','Выберите тариф для расчета стоимости');


if (isset($_SESSION) &&  !isset($current_page) && $current_page !== "modules.php")
{
	$js ='
<script type="text/javascript">
var widjetPF;
jQuery(document).ready(function(){
	if(typeof SDICDEK === "undefined") {
	    //console.log("SDICDEK is", typeof SDICDEK);
		$.getScript("jscript/sdicdek.js", function(){
		   //console.log("Скрипт sdicdek.js выполнен. SDICDEK is", typeof SDICDEK);
		   callCDEKWidjetPF(SDICDEK);
		 });
	} else {
	    callCDEKWidjetPF(SDICDEK);
	}
});


function callCDEKWidjetPF(SDICDEK){
	//console.log("callCDEKWidjetPF is started...");
	var params = {
	    '.(isset($order->products)?"products:".json_encode($order->products).",":"").'
	    '.(isset($order->delivery['city'])?"receiverCity:\"".$order->delivery['city']."\",":"").'
	};
	
	var sdicdek_params = {
		payment_type: "PF",
		sdicdek_params: params,
		msgs: {
			MODULE_SHIPPING_SDICDEK_ATP_TEXT_ADDRESS:"'.MODULE_SHIPPING_SDICDEK_ATP_TEXT_ADDRESS.'",
			MODULE_SHIPPING_SDICDEK_PVZ_TEXT_ADDRESS:"'.MODULE_SHIPPING_SDICDEK_PVZ_TEXT_ADDRESS.'",
			MODULE_SHIPPING_SDICDEK_SUCCESS_DELIVERY:"'.MODULE_SHIPPING_SDICDEK_SUCCESS_DELIVERY.'",
		},
		callback: onCDEKWidjetPFReady,
	};

	SDICDEK.init(sdicdek_params);
}

function onCDEKWidjetPFReady(widjet){
	//console.log("onCDEKWidjetPFReady is started...");
	widjetPF = widjet;
}

function runWidjetPF(){
    //console.log("started runWidjetPF ... ");
    if(typeof widjetPF === "undefined") {
        //console.log("widjetPF failed to start. Undefined!");
        return false;
    }
    widjetPF.open();
}
</script>
<input type="hidden" name="sdicdekpf_type" id="sdicdekpf_type" value="" />
<input type="hidden" name="sdicdekpf_full_cost" id="sdicdekpf_full_cost" value="" />
<input type="hidden" name="sdicdekpf_address" id="sdicdekpf_address" value="" />
<span id="sdicdekpf_address_text"></span>
<a href="javascript:void(0);" onclick=" runWidjetPF();"><span id="sdicdekpf_link" style="color:blue;">' . MODULE_SHIPPING_SDICDEK_TEXT_SELECT_ADDRESS . '</span></a><span id="sdicdekpf_link_help">' . MODULE_SHIPPING_SDICDEK_TEXT_ADDRESS_HELP . '</span>
';

	define('MODULE_SHIPPING_SDICDEKPF_TEXT_WAY', $js);

}
