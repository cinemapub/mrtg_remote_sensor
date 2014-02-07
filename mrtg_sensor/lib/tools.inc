<?php
$debug=false;
$time_pagestarted=microtime(true);

function trace($info,$level="DEBUG"){
	global $debug;
	global $time_pagestarted;
	if(!$debug AND $level=="DEBUG"){
		// only print info if in debug mode
		return false;
	}
	$secs=round($time_pagestarted - microtime(true),2);
	if(is_array($info)){
		echo "\n<-- $level @ ${secs}s:\n";
		print_r($info);
		echo "\n-->\n";
	} else {
		echo "\n<-- $level @ ${secs}s: " . htmlspecialchars($info) . " -->\n";
	}
}

Class Cache {
	var $enabled=false;

	function __construct($cachedir="./cache"){
		if(!file_exists("$cachedir/.")){
			if(mkdir($cachedir,0666,true)){
				trace("created cache dir [$cachedir]");
				$this->enabled=true;
				$this->cachedir=$cachedir;
				return true;
			} else {
				trace("cannot cache dir [$cachedir]","ERROR");
				$this->enabled=false;
				return false;
			}
				;
		} else {
			$this->enabled=true;
			$this->cachedir=$cachedir;
			return true;
		}
	}

	function get($id,$group="cache",$maxsecs=295){
		if(!$this->enabled){
			return false;
		}
		$cachefile=$this->mkfilename($id,$group);
		if(!file_exists($cachefile)){
			trace("Cache::get : no cache [$cacheid] for [$id] yet");
			return false;
		}
		// so the cache file exists
		if((time()-filemtime($cachefile)) > $maxsecs){
			trace("Cache::get : cache [$cacheid] for [$id] too old");
			return false;
		}
		trace("Cache::get : cache [$cacheid] for [$id] is used");
		return file_get_contents($cachefile);
	}

	function get_arr($id,$group="cache",$maxsecs=295){
		if(!$this->enabled){
			return false;
		}
		$cachefile=$this->mkfilename($id,$group);
		if(!file_exists($cachefile)){
			trace("Cache::get_arr : no cache [$cacheid] for [$id] yet");
			return false;
		}
		// so the cache file exists
		if((time()-filemtime($cachefile)) > $maxsecs){
			trace("Cache::get_arr : cache [$cacheid] for [$id] too old");
			return false;
		}
		trace("Cache::get_arr : cache [$cacheid] for [$id] is used");
		return unserialize(file_get_contents($cachefile));
	}

	function set($id,$group="cache",$value){
		if(!$this->enabled){
			return false;
		}
		if(rand(0,100) > 95){
			$this->cleanup();
		}
		$cachefile=$this->mkfilename($id,$group);
		trace("Cache::set : saving [$cacheid] for [$id]");
		file_set_contents($cachefile,$value);
		return true;
	}

	function set_arr($id,$group="cache",$array){
		if(!$this->enabled){
			return false;
		}
		if(rand(0,100) > 95){
			$this->cleanup();
		}
		$cachefile=$this->mkfilename($id,$group);
		trace("Cache::set_arr : saving [$cacheid] for [$id]");
		file_set_contents($cachefile,serialize($array);
		return true;
	}

	function cleanup($hours=24){
		if(!$this->enabled){
			return false;
		}
		$cachefiles=glob("$this->cachedir/*.temp");
		if($cachefiles){
			$treshold=time()-$hours*3600;
			$nbdeleted=0;
			foreach($cachefiles as $cachefile){
				if(filemtime($cachefile) < $treshold){
					unlink($cachefile);
					$nbdeleted++;
				}
			}
			if($nbdeleted > 0){
				trace("Cache::cleanup : removed $nbdeleted old cache files");
			} else {
				// no cache files deleted
			}
		} else {
			// no cache files yet, so nothing to do
		}
	}

	private function mkfilename($id,$group="cache"){
		$group=strtolower(substr($group,0,10));
		$cacheid="$group." . substr(sha1($id),0,16);
		$cachefile=$this->cachedir . "/$cacheid.temp";
		return $cachefile;
	}


}

function cmdline($text,$folder=false,$cachesecs=0){
	if($folder){
		$path=realpath($folder);
		if(!$path){
			trace("cannot find folder [$folder]","ERROR");
			return false;
		}
		$line="cd \"$path\"; $text";
	} else {
		$line=$text;
	}
	if($cachesecs>0){
		$cc=New Cache;
		$result=$cc->get_arr($line,"commandline",$cachesecs);
		if($result)	return $result;
	}

	$result=exec($line,$stdout);
	trace("cmdline: [$line] => " . count($stdout) . " lines returned (return: $result)");
	if($cachesecs>0 AND $stdout){
		$cc=New Cache;
		$cc->set_arr($line,"commandline",$stdout);
	}
	return $stdout;
}

?>