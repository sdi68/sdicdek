<?php
/**
 * sdicdekpf.php  16.11.20 11:43
 * Created for project VamShop 1.x
 * Version 1.0.0
 * subpackage sdicdek - shipping module CDEK
 * https://econsultlab.ru
 * mail: info@econsultlab.ru
 * Released under the GNU General Public License
 * Copyright (c) 2020 Econsult Lab.
 */
require_once(DIR_FS_DOCUMENT_ROOT . 'includes/modules/shipping/sdicdek/sdicdek.php');

class sdicdekpf extends sdicdek {
	var $code, $title, $description, $icon, $enabled;
	/**
	 * sdicdekpf constructor.
	 */
	public function __construct()
	{
		global $order;

		$this->loadParam('PF');

		$this->code = 'sdicdekpf';
		$this->title = MODULE_SHIPPING_SDICDEKPF_TEXT_TITLE;
		$this->description = MODULE_SHIPPING_SDICDEKPF_TEXT_DESCRIPTION;
		$this->sort_order = MODULE_SHIPPING_SDICDEKPF_SORT_ORDER;
		$this->icon = DIR_WS_ICONS . 'cdek.png';
		$this->tax_class = MODULE_SHIPPING_SDICDEK_TAX_CLASS;
		$this->enabled = ((MODULE_SHIPPING_SDICDEKPF_STATUS == 'True') ? true : false);


		if ( ($this->enabled == true) && ((int)MODULE_SHIPPING_SDICDEK_ZONE > 0) ) {
			$check_flag = false;
			$check_query = vam_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_SHIPPING_SDICDEK_ZONE . "' and zone_country_id = '" . $order->delivery['country']['id'] . "' order by zone_id");
			while ($check = vam_db_fetch_array($check_query)) {
				if ($check['zone_id'] < 1) {
					$check_flag = true;
					break;
				} elseif ($check['zone_id'] == $order->delivery['zone_id']) {
					$check_flag = true;
					break;
				}
			}

			if ($check_flag == false) {
				$this->enabled = false;
			}
		}

		$this->enabled = $this->enabled && $this->checkMaxLimit();
	}

}
?>