<?php
include("lib/settings.inc");
$s=New MRTGResult;
$s->prepare();
$debug=true;
trace("Running as:");
print_r(posix_getpwuid(posix_getuid()));

trace(getparam("test","ok"));

print_r(cmdline("whoami"));
print_r(cmdline("pwd"));
print_r(cmdline("df /var/www"));
print_r(cmdline("free"));

?>