<?php
include("lib/settings.inc");

$s=New MRTGResult;
$s->prepare();
$s->set(10,100);
$s->output();

?>