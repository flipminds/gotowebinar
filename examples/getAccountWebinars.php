<?php

include_once '../vendor/autoload.php';

use FlipMinds\GotoWebinar\GotoWebinar;

$config = include('config.php');

$gtw = new GotoWebinar($config['credentials'], $config['auth']);

$fromTime = new DateTime('1st Jan 1970');
$toTime = new DateTime();
$result = $gtw->getAccountWebinars($fromTime->format('Y-m-d\tH:i:s\Z'), $toTime->format('Y-m-d\tH:i:s\Z'));
print "<pre>";
print "getAccountWebinars : ({$gtw->getStatusCode()} {$gtw->getReasonPhrase()})\n";
print_r($result);