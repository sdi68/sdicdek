<?php
/**
 * sdicdek.php  20.11.20 17:01
 * Created for project VamShop 1.x
 * Version 1.0.0
 * subpackage sdicdek - shipping module CDEK
 * https://econsultlab.ru
 * mail: info@econsultlab.ru
 * Released under the GNU General Public License
 * Copyright (c) 2020 Econsult Lab.
 */

require_once(DIR_FS_DOCUMENT_ROOT . 'includes/modules/shipping/sdicdek/CalculatePriceDeliveryCdek.php');

define ("TEXT_NONE","нет данных");
define("TXT_UNDEFINED","не определена");

//define('MODULE_SHIPPING_PICKPOINTPP_SORT_VALUE', '1');
//define('MODULE_SHIPPING_PICKPOINTPF_SORT_VALUE', '2');
//// перенаправляем запрос через AJAX на нужную процедуру
switch ($_REQUEST['method'])
{
	case 'getWidjetParams':
		$cdek = new sdicdek($_REQUEST['sdicdek_payment_type']);
		echo json_encode($cdek->getWidjetParams($_REQUEST['sdicdek_params']));
		exit;
		break;
	case 'updateDeliveryCost':
		$cdek = new sdicdek($_REQUEST['sdicdek_payment_type']);
		echo json_encode($cdek->updateDeliveryCost($_REQUEST['sdicdek_params']));
		exit;
		break;
	default:
		// если вход не через AJAX
		break;
}

class sdicdek
{
	public
		$type = SDI_SHIPPING_TYPE_SELF;

	protected
		$prefix = '',
		$settings,
		$error_msg = "",
		$calc = null; // экземпляр объекта CalculatePriceDeliveryCdek

	/**
	 * sdicdek constructor.
	 *
	 * @param string $payment_type
	 */
	public function __construct($payment_type = '')
	{

		if ($payment_type != '')
		{
			$this->loadParam($payment_type);
		}
	}

	/**
	 * Загрузка параметров для выбранной услуги. Применяется для инициализации класса в модуле доставки
	 *
	 * @param $payment_type Тип оплаты pp - предоплата, pf - оплата при получении
	 */
	protected function loadParam($payment_type)
	{
		$this->prefix   = 'SDICDEK' . mb_strtoupper($payment_type, "UTF-8");
		$this->settings = $this->_getSettings();
	}

	/**
	 * Получить настройки модуля доставки для текущей услуги
	 * @return array Массив параметров
	 */
	private function _getSettings()
	{
		// Запросим все настройки нашего модуля

		if (sizeof($this->settings) <= 1)
		{
			$sql = vam_db_query("SELECT configuration_key, configuration_value FROM " . TABLE_CONFIGURATION . "
                                WHERE configuration_key LIKE '%\_SDICDEK%'");
			while ($config_rows = vam_db_fetch_array($sql))
			{
				$keys[]   = $config_rows['configuration_key'];
				$values[] = $config_rows['configuration_value'];
			}

			return array_combine($keys, $values);
		}

		return null;
	}

	public function getWidjetParams($params){
		$goods = array();
		if($params['products'])
		{
			foreach ($params['products'] AS $product)
			{
				$goods[] = array(
					'weight' => $this->_checkGabarits('weight', $product['weight']),
					'length' => $this->_checkGabarits('length', $product['length']),
					'width'  => $this->_checkGabarits('width', $product['width']),
					'height' => $this->_checkGabarits('height', $product['height']) * $product['qty']
				);
			}
		}

		$receiverCity = "";
		if(isset($params['receiverCity']))
			$receiverCity = trim(preg_replace("/^г /si", "", $params['receiverCity'],1));
		$senderCity = "";
		if(is_null($this->calc)) {
			$this->calc = new CalculatePriceDeliveryCdek();
			if($this->calc->getCityNameById($this->settings['MODULE_SHIPPING_SDICDEK_FROMCITY_ID'])) {
				$senderCity = $this->calc->getResult();
			}
		}
		$out = array(
			"senderCityId" => $this->settings['MODULE_SHIPPING_SDICDEK_FROMCITY_ID'],
			"senderCity" => $senderCity,
			"receiverCity" => $receiverCity,
			"apikey" => $this->settings['MODULE_SHIPPING_SDICDEK_YMAP_KEY'],
			"goods" => $goods,
		);
		$out_json = json_encode($out);
		return $out;
	}

	public function getAccount(){
		return $this->settings['MODULE_SHIPPING_SDICDEK_ACCOUNT'];
	}

	public function getSecure(){
		return $this->settings['MODULE_SHIPPING_SDICDEK_SECURE'];
	}

	public function getTarif(){
		return $this->settings['MODULE_SHIPPING_SDICDEKPP_TARIF'];
	}

	public function quote($method = '')
	{
		global $order, $shipping_weight;
		//$this->calc = new CalculatePriceDeliveryCdek();
		//$destCityId = null;
		$error = false;
		$cost    = null;
		$err_msg = '';

		$params = array(
			"receiverCityId" => null,
			"senderCityId" => $this->settings['MODULE_SHIPPING_FROMCITY_ID'],
			"account" => $this->settings['MODULE_SHIPPING_SDICDEK_ACCOUNT'],
			"secure" => $this->settings['MODULE_SHIPPING_SDICDEK_SECURE'],
			"TariffId" => $this->settings['MODULE_SHIPPING_SDICDEK'.$this->prefix.'_TARIF']
		);

		$cost = $this->getDeliveryCost($params);

		if (!is_array($cost) || isset($cost['error']))
		{
			// ошибка получения стоимости доставки
			var_dump($cost['error']);
			$error   = true;
			$err_msg = 'Ошибка получения стоимости доставки от СДЭК. Стоимость доставки будет уточнена при обработке заказа.' . ((isset($cost['error'])) ? '<br>' . $cost['error'] : '');
			$cost    = array('FullPrice' => TXT_UNDEFINED, 'FullPriceFormatted' => TXT_UNDEFINED, 'FullPriceWithDiscount' => TXT_UNDEFINED, 'FullPriceWithDiscountFormatted' => TXT_UNDEFINED);
		}
		else
		{
			// стоимость корректно получена
			if ($cost['DPMin'] != $cost['DPMax'])
			{
				$di = '</br><span id = "' . mb_strtolower($this->prefix) . '_days" style="font-weight:normal; font-style: italic;"> (' . constant("MODULE_SHIPPING_SDICDEK_SUCCESS_DELIVERY") . ' от ' . $cost['DPMinFormatted'] . ' до ' . $cost['DPMaxFormatted'] . ')</span>';
			}
			else
			{
				$di = '</br><span id = "' . mb_strtolower($this->prefix) . '_days" style="font-weight:normal; font-style: italic;"> (' . constant("MODULE_SHIPPING_SDICDEK_SUCCESS_DELIVERY") . ' от ' . $cost['DPMinFormatted'] . ')</span>';
			}
		}

		if (isset($cost['FullPriceWithDiscount']))
		{
			$this->quotes = array(
				'id'      => $this->code,
				'module'  => constant('MODULE_SHIPPING_' . $this->prefix . '_TEXT_TITLE') . $di,
				'methods' => array(array('id'        => $this->code,
				'title'     => constant('MODULE_SHIPPING_' . $this->prefix . '_TEXT_WAY') . '</br><span id = "' . mb_strtolower($this->prefix) . '_error" class = "error">' . $err_msg . '</span>',
				'cost'      => $cost['FullPriceWithDiscount'],
				'full_cost' => $cost['FullPrice'])),
				'type'    => 'self_delivery'
			); // добавляем признак доставки самовывозом

		}
		else
		{
			$this->quotes = array('id'      => $this->code,
			                      'module'  => constant('MODULE_SHIPPING_' . $this->prefix . '_TEXT_TITLE') . $di,
			                      'methods' => array(array('id'    => $this->code,
			                                               'title' => constant('MODULE_SHIPPING_' . $this->prefix . '_TEXT_WAY') . '</br><span id = "' . mb_strtolower($this->prefix) . '_error" class = "error" >' . $err_msg . '</span>',
			                                               'cost'  => $cost['FullPrice'])));

		}
		if ($this->tax_class > 0)
		{
			$this->quotes['tax'] = vam_get_tax_rate($this->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id']);
		}

		if (vam_not_null($this->icon)) $this->quotes['icon'] = vam_image($this->icon, $this->title);

		if ($error == true) $this->quotes['error'] = '<span id = "' . mb_strtolower($this->prefix) . '_error" class = "error">' . $err_msg . '</span>';

		return $this->quotes;
	}


	public function getDeliveryCost($params){
		global $order, $shipping_weight, $vamPrice;
		$error = false;
		$err_msg = '';
		$out = null;
		try
		{

			$this->calc = new CalculatePriceDeliveryCdek();
			// проверяем id города назначения
			if (is_null($params['receiverCityId']))
			{
				if ($this->calc->getCityIdByName($order->delivery['city']))
				{
					$params['receiverCityId'] = $this->calc->getResult();
				}
				else
				{
					$error   = true;
					$err_msg = $this->calc->getError();
				}
			}

			// проверяем вес заказа
			if ($shipping_weight == 0)
				$shipping_weight = 1; // если вес не установлен, то считаем, что 1000 грамм

			$maxweight = constant('MODULE_SHIPPING_SDICDEK_MAXWEIGHT');
			if ($shipping_weight > $maxweight)
			{
				// превышен вес отправления
				$error   = true;
				$err_msg = constant('MODULE_SHIPPING_SDICDEK_INVALID_WEIGHT');
			}

			if(!$error) {
				$this->calc->setAuth($this->settings['MODULE_SHIPPING_SDICDEK_ACCOUNT'],$this->settings['MODULE_SHIPPING_SDICDEK_SECURE']);
				//устанавливаем город-отправитель
				$this->calc->setSenderCityId($this->settings['MODULE_SHIPPING_SDICDEK_FROMCITY_ID']);
				//устанавливаем город-получатель
				$this->calc->setReceiverCityId($params['receiverCityId']);
				//устанавливаем дату планируемой отправки
				//$this->calc->setDateExecute();

				//задаём список тарифов с приоритетами
				//$this->calc->addTariffPriority($_REQUEST['tariffList1']);
				//$this->calc->addTariffPriority($_REQUEST['tariffList2']);
				//устанавливаем тариф по-умолчанию
				$this->calc->setTariffId($this->settings['MODULE_SHIPPING_'.$this->prefix.'_TARIF']);

				//устанавливаем режим доставки
				//$this->calc->setModeDeliveryId(3);
				//добавляем места в отправление
				foreach($order->products AS $product)
				{
					$this->calc->addGoodsItemBySize(
						$this->_checkGabarits('weight', $product['weight']),
						$this->_checkGabarits('length', $product['length']),
						$this->_checkGabarits('width', $product['width']),
						$this->_checkGabarits('height', $product['height'])*$product['qty']
					);

				}

				if($this->calc->calculate() === true){
					$res = $this->calc->getResult();
					$out['Price']                          = $res['result']['price'];
					$extraCost                             = $this->_getExtraCost($out['Price']);
					$out['FullPrice']                      = ceil($out['Price'] + $extraCost['extraCost']);
					$out['FullPriceFormatted']             = $vamPrice->Format($out['FullPrice'], true);
					$out['extraCost']                      = $extraCost;
					$out['FullPriceWithDiscount']          = ceil($out['Price'] + $extraCost['extraCost']);
					$out['FullPriceWithDiscountFormatted'] = $vamPrice->Format($out['FullPrice'], true);
					$out['DPMin']                          = $res['result']['deliveryPeriodMin'];
					$out['DPMax']                          = $res['result']['deliveryPeriodMax'];
					$out['DPMinFormatted']                 = $this->getDaysString($res['result']['deliveryPeriodMin']);
					$out['DPMaxFormatted']                 = $this->getDaysString($res['result']['deliveryPeriodMax']);

					if (is_numeric($this->settings['MODULE_SHIPPING_' . $this->prefix . '_FREE']))
					{
						if ($this->getOrderTotal() >= intval($this->settings['MODULE_SHIPPING_' . $this->prefix . '_FREE']))
						{
							// выполнено условие бесплатной доставки
							$out['FullPriceWithDiscount']          = 0;
							$out['FullPriceWithDiscountFormatted'] = $vamPrice->Format(0, true);
						}
					}
				} else {
					$out['error'] = array('code' => "",'text'=>$err_msg);
				}

			} else {
				// есть ошибки, стоимость не расчитываем
				$out['error'] = $this->calc->getError();
			}
		} catch (Exception $e) {
			// есть ошибки, стоимость не расчитываем
			$out['error'] = $e;
		}

		return $out;
	}


	/**
	 * Обновляет стоимость доставки в глобальной переменной после выбора ПВЗ и перерасчета стоимости доставки через AJAX
	 *
	 * @param $cost стоимость доставки
	 */
	public function updateDeliveryCost($params)
	{
		global $shipping, $vamPrice;
		$out = array();
		if (isset($params['cost']))
		{
			$out['Price']                          = $params['cost'];
			$extraCost                             = $this->_getExtraCost($out['Price']);
			$out['FullPrice']                      = ceil($out['Price'] + $extraCost['extraCost']);
			$out['FullPriceFormatted']             = $vamPrice->Format($out['FullPrice'], true);
			$out['extraCost']                      = $extraCost;
			$out['FullPriceWithDiscount']          = ceil($out['Price'] + $extraCost['extraCost']);
			$out['FullPriceWithDiscountFormatted'] = $vamPrice->Format($out['FullPrice'], true);
			if (isset($params['term']))
			{
				$d                     = explode("-", $params['term']);
				$out['DPMin']          = $d[0];
				$out['DPMax']          = $d[1];
				$out['DPMinFormatted'] = $this->getDaysString($d[0]);
				$out['DPMaxFormatted'] = $this->getDaysString($d[1]);
			}

			if (is_numeric($this->settings['MODULE_SHIPPING_' . $this->prefix . '_FREE']))
			{
				if ($this->getOrderTotal() >= intval($this->settings['MODULE_SHIPPING_' . $this->prefix . '_FREE']))
				{
					// выполнено условие бесплатной доставки
					$out['FullPriceWithDiscount']          = 0;
					$out['FullPriceWithDiscountFormatted'] = $vamPrice->Format(0, true);
				}
			}

			$shipping['cost']      = $out['FullPriceWithDiscount'];
			$shipping['full_cost'] = $out['FullPrice'];
			if (isset($shipping['error']))
				unset($shipping['error']);
		}
		else
		{
			$out['error'] = $shipping['error'] = "Ошибка при пересчете стоимости доставки!";

		}

		return $out;
	}


	/**
	 * Расчет дополнительных наценок на стоимость доставки
	 *
	 * @param $price Стоимость доставки, рассчитанная
	 *
	 * @return array Массив наценки на доставки и ее расшифровки.
	 */
	private function _getExtraCost($price)
	{
		$extraCost = 0;
		// стоимость способа доставки
		$cost      = intval($this->settings['MODULE_SHIPPING_' . $this->prefix . '_COST']);
		$extraCost = $extraCost + $cost;

		// стоимость страховки за использования наложенного платежа
		$costOfInsurance = $this->_getInsuranceTax($extraCost + $price);
		$extraCost       = $extraCost + $costOfInsurance;

		// стоимость перевода денег при наложенном платеже
		$costOfTransfer = $this->_getTransferTax($extraCost + $price);
		$extraCost      = $extraCost + $costOfTransfer;

		$out['extraCost']       = $extraCost;
		$out['cost']            = $cost;
		$out['costOfInsurance'] = $costOfInsurance;
		$out['costOfTransfer']  = $costOfTransfer;

		return $out;
	}

	/**
	 * получить сумму рисков за использование наложенного платежа
	 *
	 * @param $shipping Стоимость доставки
	 *
	 * @return float Наценка за риск использования наложенного платежа
	 */
	private function _getInsuranceTax($shipping)
	{
		global $vamPrice;
		$risk   = 0;
		$burden = 0;

		$burden_data = isset($this->settings['MODULE_SHIPPING_' . $this->prefix . '_INSHURANCE']) ? $this->settings['MODULE_SHIPPING_' . $this->prefix . '_INSHURANCE'] : 0;

		if (!empty($burden_data) || $burden_data > 0)
		{

			$burden = (strpos($burden_data, '%') === false) ?
				$burden_data :
				substr($burden_data, 0, strpos($burden_data, '%'));

			$burden_proc = (strpos($burden_data, '%') === false) ? false : true;

			//узнаем откуда высчитывать страховку
			$burden_method = 0;
			if ($burden_proc)
			{
				$bm = substr($burden_data, 0, 1);
				if ($bm == 'p' || $bm == 'P' || $bm == 'р' || $bm == 'Р') $burden_method = 'products';
				else if ($bm == 'd' || $bm == 'D') $burden_method = 'delivery';
				else
				{
					$burden_method = 'all';
				}

				$burden = substr(substr($burden_data, 0, strpos($burden_data, '%')), ((is_numeric($bm)) ? 0 : 1), strlen($burden_data) - 1);

			}

		}

		if ($burden_method == 'delivery' && $burden_proc)
		{
			$risk = (($shipping / 100) * $burden);

		}
		elseif ($burden_method == 'products' && $burden_proc)
		{

			$risk = (($vamPrice->RemoveCurr($this->getOrderTotal()) / 100) * $burden);

		}
		elseif ($burden_method == 'all' && $burden_proc)
		{

			$risk = ((($shipping + $this->getOrderTotal()) / 100) * $burden);

		}
		else
		{
			$risk = 0;
		}

		//прибавим страховую сумму магазина (НЕ процент)
		if (!$burden_proc) $risk += $burden;

		return ceil($risk);

	}

	/**
	 * Рассчет комиссии за наложенный платеж
	 *
	 * @param $shipping стоимость доставки
	 *
	 * @return float Комиссия за перевод денег наложенным платежом
	 */
	private function _getTransferTax($shipping)
	{
		$transferedSumm = $this->getOrderTotal() + $shipping;
		$fee            = isset($this->settings['MODULE_SHIPPING_' . $this->prefix . '_PFKOMISS']) ? ($this->settings['MODULE_SHIPPING_' . $this->prefix . '_PFKOMISS'] * $transferedSumm) / 100 : 0;

		return ceil($fee);
	}

	protected function getDaysString($digit)
	{
		if(!is_null($digit))
		{
			switch ($digit)
			{
				case 1:
					$ret = $digit . ' раб. дня';
					break;
				case 2:
				case 3:
				case 4:
					$ret = $digit . ' раб. дней';
					break;
				default:
					$ret = $digit . ' раб. дней';
					break;
			}
		} else {
			$ret = "3 раб. дней";
		}
		return $ret;
	}


	private function _checkGabarits($key,$value){
		$alt = $value;
		switch($key) {
			case "weight":
				$alt = 1;
			case "length":
				$alt = 10;
			case "width":
				$alt = 10;
			case "height":
				$alt = 10;
			default:
		}
		return $value?$value:$alt;
	}

	/**
	 * получить сумму товаров заказа
	 * @return mixed сумма заказанных товаров
	 */
	protected function getOrderTotal()
	{
		global $order;
		// получаем сумму товаров заказа
		if (isset($_GET['oID']))
		{
			// заказ оформляется/редактируется в админке
			// сумму берем из заказа
			$order_total = $order->info['total'];
		}
		else
		{
			// заказ создается клиентом
			// сумму берем из корзины
			$order_total = $_SESSION['cart']->show_total();
		}

		return $order_total;
	}

	/**
	 *  получить вес заказа
	 * @return int вес заказа в кг.
	 */
	protected function getOrderWeight()
	{
		global $order, $shipping_weight;
		// получаем сумму товаров заказа
		$weight = 0;
		if (isset($_GET['oID']))
		{
			// заказ оформляется/редактируется в админке
			// вес берем из заказа

			require_once(DIR_FS_INC . 'sdi_get_order_weight.inc.php');
			$weight = sdi_get_order_weight();
		}
		else
		{
			// заказ создается клиентом
			// вес берем из корзины
			$weight = $_SESSION['cart']->show_weight();
			$weight = $weight + $weight * SHIPPING_BOX_WEIGHT;
			//$weight = $shipping_weight;
		}

		// проверяем значение веса и если оно не рассчитано подставляем 1
		if (!is_numeric($weight) || $weight == 0)
			$weight = 1;

		return $weight;
	}

	public function check()
	{
		if (!isset($this->_check))
		{
			$check_query  = vam_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_SHIPPING_" . $this->prefix . "_STATUS'");
			$this->_check = vam_db_num_rows($check_query);
		}

		return $this->_check;
	}

	function remove()
	{
		if ($this->checkInstalled() == true)
		{
			// другой модуль установлен удаляем только настройки этого модуля.
			vam_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys('delete')) . "')");
		}
		else
		{
			// другой модуль не установлен, удаляем все настройки
			vam_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
		}
	}

	/**
	 * Проверка на наличие установок других модулей пакета
	 * @return bool true - если установлены еще модули из пакета
	 */
	private function checkInstalled()
	{
		$sql = vam_db_query("SELECT configuration_key, configuration_value FROM " . TABLE_CONFIGURATION . "
                                WHERE configuration_key LIKE 'MODULE_SHIPPING_SDICDEK%_STATUS' AND configuration_key NOT LIKE 'MODULE_SHIPPING_" . $this->prefix . "_STATUS'");
		if (vam_db_num_rows($sql) >> 0)
			return true;

		return false;

	}

	function keys($mode = '')
	{
		if ($mode == 'delete')
		{
			// удаляем только ключи текущего модуля
			$keys[] = 'MODULE_SHIPPING_' . $this->prefix . '_STATUS';
			$keys[] = 'MODULE_SHIPPING_' . $this->prefix . '_COST';
			$keys[] = 'MODULE_SHIPPING_' . $this->prefix . '_SORT_ORDER';
			$keys[] = 'MODULE_SHIPPING_' . $this->prefix . '_FREE'; // сумма заказа, начиная с которой доставка бесплатная
			$keys[] = 'MODULE_SHIPPING_' . $this->prefix . '_TARIF';
			if ($this->prefix == 'SDICDEKPF')
			{
				$keys[] = 'MODULE_SHIPPING_' . $this->prefix . '_PFKOMISS';
				$keys[] = 'MODULE_SHIPPING_' . $this->prefix . '_INSURANCE';
				$keys[] = 'MODULE_SHIPPING_' . $this->prefix . '_FEELIMIT';

			}
			$keys[] = 'MODULE_SHIPPING_' . $this->prefix . '_ALLOWED';
		}
		else
		{
			$keys[] = 'MODULE_SHIPPING_' . $this->prefix . '_STATUS';
			$keys[] = 'MODULE_SHIPPING_' . $this->prefix . '_SORT_ORDER';
			$keys[] = 'MODULE_SHIPPING_' . $this->prefix . '_COST';
			if ($this->prefix == 'SDICDEKPF')
			{
				$keys[] = 'MODULE_SHIPPING_' . $this->prefix . '_PFKOMISS';
				$keys[] = 'MODULE_SHIPPING_' . $this->prefix . '_INSURANCE';
				$keys[] = 'MODULE_SHIPPING_' . $this->prefix . '_FEELIMIT';
			}
			$keys[] = 'MODULE_SHIPPING_' . $this->prefix . '_FREE'; // сумма заказа, начиная с которой доставка бесплатная
			$keys[] = 'MODULE_SHIPPING_SDICDEK_MAXWEIGHT'; // Максимально допустимый вес
			$keys[] = 'MODULE_SHIPPING_SDICDEK_ACCOUNT';
			$keys[] = 'MODULE_SHIPPING_SDICDEK_SECURE';
			//$keys[] = 'MODULE_SHIPPING_SDICDEK_TEST';

			$keys[] = 'MODULE_SHIPPING_SDICDEK_FROMCITY';
			$keys[] = 'MODULE_SHIPPING_SDICDEK_FROMCITY_ID';
			$keys[] = 'MODULE_SHIPPING_SDICDEK_YMAP_KEY';
			$keys[] = 'MODULE_SHIPPING_' . $this->prefix . '_ALLOWED';
			$keys[] = 'MODULE_SHIPPING_SDICDEK_TAX_CLASS';
			$keys[] = 'MODULE_SHIPPING_SDICDEK_ZONE';
			$keys[] = 'MODULE_SHIPPING_' . $this->prefix . '_TARIF';

		}

		return $keys;
	}

	function install()
	{
		vam_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value,  configuration_group_id, sort_order, set_function, date_added) values ('MODULE_SHIPPING_" . $this->prefix . "_STATUS', 'False', '6', '0', 'vam_cfg_select_option(array(\'True\', \'False\'), ', now())");
		vam_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value,  configuration_group_id, sort_order, date_added) values ('MODULE_SHIPPING_" . $this->prefix . "_COST', '60', '6', '0', now())");
		vam_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value,  configuration_group_id, sort_order, date_added) values ('MODULE_SHIPPING_" . $this->prefix . "_SORT_ORDER', '" . constant('MODULE_SHIPPING_' . $this->prefix . '_SORT_VALUE') . "', '6', '0', now())");
		vam_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value,  configuration_group_id, sort_order, date_added) values ('MODULE_SHIPPING_" . $this->prefix . "_FREE', '10000', '6', '0', now())");
		vam_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value,  configuration_group_id, sort_order, date_added) values ('MODULE_SHIPPING_" . $this->prefix . "_ALLOWED', '', '6', '0', now())");
		vam_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value,  configuration_group_id, sort_order, use_function, set_function, date_added) values ('MODULE_SHIPPING_" . $this->prefix . "_TARIF', '', '6', '0', 'sdi_get_sdicdek_tarifs_title', 'sdi_cfg_pull_down_sdicdek_tarifs(', now())");
		if ($this->prefix == 'SDICDEKPF')
		{
			vam_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value,  configuration_group_id, sort_order, date_added) values ('MODULE_SHIPPING_SDICDEKPF_PFKOMISS', '4.13', '6', '0', now())");
			vam_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value,  configuration_group_id, sort_order, date_added) values ('MODULE_SHIPPING_SDICDEKPF_INSURANCE', 'p2%', '6', '0', now())");
			vam_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value,  configuration_group_id, sort_order, date_added) values ('MODULE_SHIPPING_SDICDEKPF_FEELIMIT', '25000', '6', '0', now())");
			vam_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value,  configuration_group_id, sort_order, date_added) values ('MODULE_SHIPPING_SDICDEKPF_ADVANCED_FEELIMIT', '15000', '6', '0', now())");
		}
		// смотрим есть ли другой модуль
		if ($this->checkInstalled() == false)
		{
			// никакой модуль не установлен
			vam_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value,  configuration_group_id, sort_order, date_added) values ('MODULE_SHIPPING_SDICDEK_ACCOUNT', '1AkR4dmovc127A4V0f3Vw8nqoeO9SX4S', '6', '0', now())");
			vam_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value,  configuration_group_id, sort_order, date_added) values ('MODULE_SHIPPING_SDICDEK_SECURE', 'kAP0psHFulsmwfCqORbaCOYjq6wP1jcP', '6', '0', now())");
			vam_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value,  configuration_group_id, sort_order, date_added) values ('MODULE_SHIPPING_SDICDEK_MAXWEIGHT', '20', '6', '0', now())");
			//vam_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value,  configuration_group_id, sort_order, use_function, set_function, date_added) values ('MODULE_SHIPPING_SDICDEK_FROMCITY', '', '6', '0', 'sdi_get_sdicdek_regions_title', 'sdi_cfg_pull_down_sdicdek_regions(', now())");
			vam_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value,  configuration_group_id, sort_order, date_added) values ('MODULE_SHIPPING_SDICDEK_FROMCITY', '', '6', '0', now())");
			vam_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value,  configuration_group_id, sort_order, date_added) values ('MODULE_SHIPPING_SDICDEK_FROMCITY_ID', '', '6', '0', now())");
			vam_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value,  configuration_group_id, sort_order, date_added) values ('MODULE_SHIPPING_SDICDEK_YMAP_KEY', '', '6', '0', now())");
			vam_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value,  configuration_group_id, sort_order, use_function, set_function, date_added) values ('MODULE_SHIPPING_SDICDEK_TAX_CLASS', '0', '6', '0', 'vam_get_tax_class_title', 'vam_cfg_pull_down_tax_classes(', now())");
			vam_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_key, configuration_value,  configuration_group_id, sort_order, use_function, set_function, date_added) values ('MODULE_SHIPPING_SDICDEK_ZONE', '0', '6', '0', 'vam_get_zone_class_title', 'vam_cfg_pull_down_zone_classes(', now())");
		}
	}

	/**
	 * Проверка превышает или нет стоимость заказа максимальную сумму заказа для данного способа доставки
	 * @return bool Возвращает true если не превышает, false - если превышает
	 */
	protected function checkMaxLimit()
	{
		if (isset($this->settings['MODULE_SHIPPING_' . $this->prefix . '_FEELIMIT']))
		{
			if (is_numeric($this->settings['MODULE_SHIPPING_' . $this->prefix . '_FEELIMIT']))
			{
				if ($this->getOrderTotal() >= intval($this->settings['MODULE_SHIPPING_' . $this->prefix . '_FEELIMIT']))
				{
					// превышена максимальная сумма заказа для наложенного платежа
					return false;
				}
			}
		}

		return true;
	}

	static public function getTarifs(){
		return  array(
			array(
				'id' => '136',
				'text' => 'Посылка склад-склад'
			),
			array(
				'id' => '138',
				'text' => 'Посылка дверь-склад'
			),
			array(
				'id' => '234',
				'text' => 'Экономичная посылка склад-склад'
			),
			array(
				'id' => '366',
				'text' => 'Посылка дверь-постамат'
			),
			array(
				'id' => '368',
				'text' => 'Посылка склад-постамат'
			),
			array(
				'id' => '378',
				'text' => 'Экономичная посылка склад-постамат'
			),
		);
	}

	static public function getCitiesFrom(){
		return  array(
			array(
				'id' => '1',
				'text' => 'Город1'
			),
			array(
				'id' => '2',
				'text' => 'Город2'
			),
		);
	}

}

// вывод списка городов отправки
//function sdi_cfg_pull_down_sdicdek_regions($region_code, $key = '')
//{
//	$name = (($key) ? 'configuration[' . $key . ']' : 'configuration_value');
//	$cities = sdicdek::getCitiesFrom();
//
//	return vam_draw_pull_down_menu($name, $cities, $region_code);
//}

// вывод списка городов отправки
//function sdi_get_sdicdek_regions_title($region_code)
//{
//
//	if ($region_code == '')
//	{
//		return TEXT_NONE;
//	}
//	else
//	{
//		$cities = sdicdek::getTarifs();
//
//		if(is_array($cities))
//		{
//			foreach ($cities as $citiy){
//				if($citiy['id'] == $region_code)
//					return $citiy['text'];
//			}
//		}
//	}
//	return TEXT_NONE;
//}

// вывод списка тарифов
function sdi_cfg_pull_down_sdicdek_tarifs($tarif_code, $key = '')
{
	$name = (($key) ? 'configuration[' . $key . ']' : 'configuration_value');

	$tarifs = sdicdek::getTarifs();
var_dump($name);
	return vam_draw_pull_down_menu($name, $tarifs, $tarif_code);
}

// вывод тарифа
function sdi_get_sdicdek_tarifs_title($tarif_code)
{
	if ($tarif_code == '')
	{
		return TEXT_NONE;
	}
	else
	{
		$tarifs = sdicdek::getTarifs();

		if(is_array($tarifs))
		{
			foreach ($tarifs as $tarif){
				if($tarif['id'] == $tarif_code)
					return $tarif['text'];
			}
		}
	}
	return TEXT_NONE;
}