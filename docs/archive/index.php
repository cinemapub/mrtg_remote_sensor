<?php

use MrtgSensor\docs\archive\lib\Sensor;

include 'lib/settings.inc';
$debug = getparam('debug');

$key = strtolower(getparam('key', 'cpu'));

$param = getparam('param');
$config = getparam('config');
$options = getparam('options');

$s = new Sensor;

$values = false;
$debug = getparam('debug', false);
switch ($key) {
    case 'cpu':		$values = $s->cpuusage();
        break;
    case 'cpu%':  	$values = $s->cpuusage(true);
        break;

    case 'mem':		$values = $s->memusage();
        break;
    case 'mem%':	$values = $s->memusage(true);
        break;

    case 'disk':	$values = $s->diskusage($param);
        break;
    case 'disk%':	$values = $s->diskusage($param, true);
        break;

    case 'proc':	$values = $s->proccount($param);
        break;

    case 'battery':		$values = $s->battery('');
        break;
    case 'battery-':	$values = $s->battery('-');
        break;
    case 'battery%':	$values = $s->battery('%');
        break;
    case 'batt_volt':	$values = $s->battery('V');
        break;
    case 'batt_amp':	$values = $s->battery('A');
        break;
    case 'batt_cycles':	$values = $s->battery('C');
        break;

    case 'folder':
    case 'foldersize':	$values = $s->foldersize($param, $options);
        break;

    case 'filecount':	$values = $s->filecount($param, $options);
        break;

    case 'foldercount':	$values = $s->foldercount($param, $options);
        break;

    case 'pingtime':	$values = $s->pingtime($param);
        break;

    default: 	echo "Unknown key [$key]\n";
}
if ($values) {
    if ($config) {
        $s->mrtg_output($values, true);
    } else {
        $s->mrtg_output($values);
    }
} else {
    echo "Key [$key] has no results";
}
