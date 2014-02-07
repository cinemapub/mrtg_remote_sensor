<?php
include_once("tools.php");

Class Sensor{

	function cpu(){

	}

	function mem(){
	// >free
	//               total         used         free       shared      buffers
	//   Mem:       249184       214376        34808            0        47724
	//  Swap:      2097144       188224      1908920
	// Total:      2346328       402600      1943728


	}

	function diskusage(){
	// >df
	// Filesystem           1K-blocks      Used Available Use% Mounted on
	// /dev/sda3            1918213808  80720900 1837390508   4% /volume1		
	}

	function foldersize($folder){

	}

	function filecount($folder){

	}

	function foldercount($folder){

	}
}

?>