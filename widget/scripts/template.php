<?php
/**
 * template.php  08.06.20 4:49 
 * Created for project VamShop 1.x
 * Version 1.0.0
 * subpackage sdicdek - shipping module CDEK
 * https://econsultlab.ru
 * mail: info@econsultlab.ru
 * Released under the GNU General Public License
 * Copyright (c) 2020 Econsult Lab. 
 */

header('Access-Control-Allow-Origin: *');
$files = scandir($D = __DIR__ . '/tpl');
unset($files[0]);
unset($files[1]);

$arTPL = array();

foreach ($files as $filesname) {
    $file_tmp = explode('.', $filesname);
	$arTPL[strtolower($file_tmp[0])] = file_get_contents($D . '/' . $filesname);
}

echo str_replace(array('\r','\n','\t',"\n","\r","\t"),'',json_encode($arTPL));