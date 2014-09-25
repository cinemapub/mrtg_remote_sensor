<!DOCTYPE html>
<html lang="en">
<head>
<title>mrtg_remote_sensor: config</title>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.0/css/bootstrap.min.css">
	<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.0/css/bootstrap-theme.min.css">
    <script src="https://code.jquery.com/jquery.js"></script>
	<script src="//netdna.bootstrapcdn.com/bootstrap/3.1.0/js/bootstrap.min.js"></script>
</head>
<body>
<div class="container">
	<div class="header">
		<ul class="nav nav-pills pull-right">
			<li class="active"><a href="?">Home</a></li>
			<li><a href="?topic=cpu">CPU</a></li>
			<li><a href="?topic=mem">RAM</a></li>
			<li><a href="?topic=disk">DISK</a></li>
			<li><a href="#">About</a></li>
		</ul>
		<h3 class="text-muted">mrtg_remote_sensor</h3>
	</div>

	<div class="jumbotron">
		<h1>mrtg_remote_sensor: config</h1>
		<p class="lead">See what data you can get, and what it looks like.</p>
	</div>
	<div class="row marketing">
		<div class="col-lg-6">

<?php
$topic=$_GET["topic"];

switch($topic){
case "cpu":
	echo "<iframe width='300' height='100' src='./?value=$topic'></iframe>";
	break;
default:

}

?>
		</div>
	</div>
</div>
</body>
</html>