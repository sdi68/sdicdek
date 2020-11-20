<?php
/**
 * sdicdek.php  18.11.20 22:21 
 * Created for project VamShop 1.x
 * Version 1.0.0
 * subpackage sdicdek - shipping module CDEK
 * https://econsultlab.ru
 * mail: info@econsultlab.ru
 * Released under the GNU General Public License
 * Copyright (c) 2020 Econsult Lab. 
 */

define('MODULE_SHIPPING_SDICDEK_TEST_TITLE','Тестовый режим');
define('MODULE_SHIPPING_SDICDEK_TEST_DESC','Включить тестовый режим');
define('MODULE_SHIPPING_SDICDEK_ACCOUNT_TITLE','Идентификатор');
define('MODULE_SHIPPING_SDICDEK_ACCOUNT_DESC','Указать идентификатор интеграции СДЭК, выданный в личном кабинете');
define('MODULE_SHIPPING_SDICDEK_SECURE_TITLE','Пароль');
define('MODULE_SHIPPING_SDICDEK_SECURE_DESC','Указать пароль интеграции СДЭК, выданный в личном кабинете');
define('MODULE_SHIPPING_SDICDEK_TAX_CLASS_TITLE' , 'Налог');
define('MODULE_SHIPPING_SDICDEK_TAX_CLASS_DESC' , 'Использовать налог.');
define('MODULE_SHIPPING_SDICDEK_ZONE_TITLE' , 'Зона');
define('MODULE_SHIPPING_SDICDEK_ZONE_DESC' , 'Если выбрана зона, то данный модуль доставки будет виден только покупателям из выбранной зоны.');
define('MODULE_SHIPPING_SDICDEK_YMAP_KEY_TITLE' , 'Ключ API Яндекс.Карт');
define('MODULE_SHIPPING_SDICDEK_YMAP_KEY_DESC' , 'Укажите ключ (получить в разделе ключей геокодера в ЛК разработчика.');

define('MODULE_SHIPPING_SDICDEK_FROMCITY_ID_TITLE','ID города отправки');
define('MODULE_SHIPPING_SDICDEK_FROMCITY_ID_DESC','ID города отправки вычисляется автоматически.<br> <b>!!! НЕ ЗАПОЛНЯТЬ ВРУЧНУЮ !!!</b>');
define('MODULE_SHIPPING_SDICDEK_FROMCITY_TITLE','Город отправки');
define('MODULE_SHIPPING_SDICDEK_FROMCITY_DESC','Выберите из списка город отправки<br>'.'
	<link type="text/css" href="'.HTTPS_CATALOG_SERVER.'/jscript/jquery/plugins/ui/css/jquery-ui-1.8.21.custom.css" rel="stylesheet" />
	<script type="text/javascript" src="'.HTTPS_CATALOG_SERVER.'/jscript/jquery/jquery-1.7.2.min.js"></script>
	<script src="'.HTTPS_CATALOG_SERVER.'/jscript/jquery/plugins/ui/jquery-ui-1.8.21.custom.min.js" type="text/javascript"></script>
	<script type="text/javascript">
	/**
	 * подтягиваем список городов ajax`ом, данные jsonp в зависмости от введённых символов
	 */
	$(function() {
	    var selector = "input[name = \'configuration[MODULE_SHIPPING_SDICDEK_FROMCITY]\']";
	    var target = "input[name = \'configuration[MODULE_SHIPPING_SDICDEK_FROMCITY_ID]\']"
	  $(selector).autocomplete({
	    source: function(request,response) {
	      $.ajax({
	        url: "https://api.cdek.ru/city/getListByTerm/jsonp.php?callback=?",
	        dataType: "jsonp",
	        data: {
	        	q: function () { return $(selector).val() },
	        	name_startsWith: function () { return $(target).val() }
	        },
	        success: function(data) {
	          response($.map(data.geonames, function(item) {
	            return {
	              label: item.name,
	              value: item.name,
	              id: item.id
	            }
	          }));
	        }
	      });
	    },
	    minLength: 1,
	    select: function(event,ui) {
	    	//console.log("Yep!");
	    	$(target).val(ui.item.id);
	    }
	  });
	  
	});
	</script>
');
define('MODULE_SHIPPING_SDICDEK_MAXWEIGHT_TITLE' , 'Максимальный вес заказа (кг)');
define('MODULE_SHIPPING_SDICDEK_MAXWEIGHT_DESC' , 'Максимальный вес заказа, доступный к выдаче. Вводить в килограммах.');

define('MODULE_SHIPPING_SDICDEK_INVALID_WEIGHT', 'превышен разрешенный вес отправления!');
define('MODULE_SHIPPING_SDICDEK_SUCCESS_DELIVERY','Ориентировочный срок доставки: ');
define('MODULE_SHIPPING_SDICDEK_TEXT_SELECT_ADDRESS','Выберите пункт самовывоза');
define('MODULE_SHIPPING_SDICDEK_TEXT_ADDRESS_HELP',' (откроется во всплывающем окне)');
define('MODULE_SHIPPING_SDICDEK_TEXT_ADDRESS','Ваш заказ доставят в ');
define('MODULE_SHIPPING_SDICDEK_ATP_TEXT_ADDRESS','Ваш заказ доставят в постамат: ');
define('MODULE_SHIPPING_SDICDEK_PVZ_TEXT_ADDRESS','Ваш заказ доставят в пункт выдачи заказов: ');
define('MODULE_SHIPPING_SDICDEK_TEXT_ANOTHER_ADDRESS','Выбрать другой адрес');
