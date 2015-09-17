<?php
$LOAD_FRONTEND = false;
$LOAD_HTML = true;
$LOAD_CSS = true;
$LOAD_JS = false;

require_once '../../bootstrap.php';
?>
<style type="text/css">
.score-half-container {
	position: relative;
	width: 200px;
	height: 100px;
	padding: 0 10px;
	margin: 0 auto;
	overflow: hidden;
	border-bottom: 1px solid #ccc;
}
.score-half {
	width: 200px;
	height: 200px;
	position: relative;
}
.score-half:after {
    content:'';
    display: block;
    position: absolute;
    z-index: 10;
    width: 198px;
    height: 198px;
    top: 1px;
    left: 1px;
    box-shadow: 0px 0px 0px 2px #fff, inset 0 0 1px #ccc;
    border-radius: 100%;
}
.score-half-content {
    position: absolute;
    z-index: 5;
    top: 25px;
    left: 25px;
    width: 150px;
    height: 150px;
    background: #fff;
    box-shadow: inset 0 0 1px #ccc;
    border-radius: 100%;
}
.score-half-bar {
    position: absolute;
    width: 100%;
    height: 100%;
    border-radius: 100%;
    clip: rect(100px 200px 200px 0px);
}
.score-half-value {
	display: block;
	text-align: center;
	line-height: 60px;
	margin: 15px 0 75px;
	letter-spacing: 1px;
	font-size: 36px;
	font-weight: 200;
}
/* mini */
.score-half-container.score-half-mini {
	width: 100px;
	height: 50px;
	margin: 10px;
	display: inline-block;
}
.score-half-mini .score-half {
	width: 100px;
	height: 100px;
}
.score-half-mini .score-half:after {
	width: 98px;
	height: 98px;
}
.score-half-mini .score-half-content {
	top: 15px;
	left: 15px;
	width: 70px;
	height: 70px;
}
.score-half-mini .score-half-bar {
	clip: rect(50px 100px 100px 0px);
}
.score-half-mini .score-half-value {
	line-height: 25px;
	margin: 10px 0 0;
	font-size: 16px;
}
</style>

<pre>&lt;div class=&quot;score-half (score-half-mini)&quot&gt;...&lt;/div&gt;</pre>

<div class="score-half-container">
	<div class="score-half">
		<div class="score-half-bar colored-light" style="-webkit-transform: rotate(120deg) translate3d(0,0,0);transform: rotate(120deg) translate3d(0,0,0);"></div>
		<div class="score-half-bar colored-blue" style="-webkit-transform: rotate(45deg) translate3d(0,0,0);transform: rotate(45deg) translate3d(0,0,0);"></div>
		<div class="score-half-content">
			<div class="score-half-value">25&#37;</div>
		</div>
	</div>
</div>

<div style="text-align: center;">
	<div class="score-half-container score-half-mini">
		<div class="score-half">
			<div class="score-half-bar colored-yellow" style="-webkit-transform: rotate(72deg) translate3d(0,0,0);transform: rotate(72deg) translate3d(0,0,0);"></div>
			<div class="score-half-content">
				<div class="score-half-value">40&#37;</div>
			</div>
		</div>
	</div>
	<div class="score-half-container score-half-mini">
		<div class="score-half">
			<div class="score-half-bar colored-light" style="-webkit-transform: rotate(130deg) translate3d(0,0,0);transform: rotate(130deg) translate3d(0,0,0);"></div>
			<div class="score-half-bar colored-orange" style="-webkit-transform: rotate(120deg) translate3d(0,0,0);transform: rotate(120deg) translate3d(0,0,0);"></div>
			<div class="score-half-content">
				<div class="score-half-value">66&#37;</div>
			</div>
		</div>
	</div>
</div>