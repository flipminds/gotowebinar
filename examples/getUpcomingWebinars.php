<?php

include_once '../vendor/autoload.php';

use FlipMinds\GotoWebinar\GotoWebinar;

$config = include('config.php');

$gtw = new GotoWebinar($config['credentials'], $config['auth']);
$webinars = $gtw->getUpcomingWebinars();

print "getUpcomingWebinars : ({$gtw->getStatusCode()} {$gtw->getReasonPhrase()})\n";

foreach ($webinars as $webinar) {
	print "<h3>" . $webinar->subject . "</h3>";
	print "<pre>" . print_r($webinar, true) . "</pre>";
}