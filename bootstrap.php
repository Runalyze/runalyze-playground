<?php

$PATH_TO_RUNALYZE = __DIR__.'/../../';
$LOAD_FRONTEND	= isset($LOAD_FRONTEND) ? $LOAD_FRONTEND : true;
$LOAD_HTML		= isset($LOAD_HTML) ? $LOAD_HTML : true;
$LOAD_CSS		= isset($LOAD_CSS) ? $LOAD_CSS : true;
$LOAD_JS		= isset($LOAD_JS) ? $LOAD_JS : true;

if ($LOAD_FRONTEND) {
	if (!file_exists($PATH_TO_RUNALYZE.'inc/class.Frontend.php')) {
		die('Runalyze core is not available.');
	}

	require $PATH_TO_RUNALYZE.'inc/class.Frontend.php';
	$Frontend = new Frontend(true);
}

if ($LOAD_HTML):
?><!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">

	<base href="/">

	<?php if ($LOAD_CSS): ?><link rel="stylesheet" href="lib/less/runalyze-style.css"><?php endif; ?>
	<?php if ($LOAD_JS): ?><script src="build/scripts.js"></script><?php endif; ?>
	<?php if ($LOAD_JS): ?><script>Runalyze.init();</script><?php endif; ?>
</head>

<body id="home" style="background-image:url('img/backgrounds/Default.jpg');padding-top:0;">
	<div class="panel" style="padding:1em;">
<?php
endif;
