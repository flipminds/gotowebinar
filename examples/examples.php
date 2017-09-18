<?php

include_once '../vendor/autoload.php';

use FlipMinds\GotoWebinar\GotoWebinar;

$config = include('config.php');

print "<pre>";
$gtw = new GotoWebinar($config['credentials'], $config['$auth']);


$fromTime = new DateTime('1st Jan 1970');
$toTime = new DateTime();
$result = $gtw->getAccountWebinars($fromTime->format('Y-m-d\tH:i:s\Z'), $toTime->format('Y-m-d\tH:i:s\Z'));
print "\n\ngetAccountWebinars : " . print_r([$gtw->getstatusCode(), $gtw->getReasonPhrase(), $result], true);




exit;
//print_r($gtw->getHistoricalWebinars($fromTime->format('Y-m-d\tH:i:s\Z'), $toTime->format('Y-m-d\tH:i:s\Z')));
//print_r($gtw->getUpcomingWebinars());
//print_r($gtw->getAllWebinars());

$startTime = new DateTime('dec 1 2017 14:00 ', new DateTimeZone('UTC'));
$endTime = new DateTime('dec 1 2017 15:30 ', new DateTimeZone('UTC'));

$times = [
	(object)[
		'startTime' => $startTime->format('Y-m-d\TH:i:s\Z'),
		'endTime'   => $endTime->format('Y-m-d\TH:i:s\Z'),
	],

];

$result = $gtw->createWebinar('test', 'description', $times, 'Asia/Dubai');
$key = $result->webinarKey;
print "\n\nCreate Webinar : " . print_r([$gtw->getResponseCode(), $gtw->getCurlError(), $result], true);

$result = $gtw->getWebinar($key);
print "\n\nGet Webinar : " . print_r([$gtw->getResponseCode(), $gtw->getCurlError(), $result], true);

$result = $gtw->cancelWebinar($key);
print "\n\nCancel Webinar : " . print_r([$gtw->getResponseCode(), $gtw->getCurlError(), $result], true);




