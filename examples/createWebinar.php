<?php

include_once '../vendor/autoload.php';

use FlipMinds\GotoWebinar\GotoWebinar;

$config = include('config.php');

$gtw = new GotoWebinar($config['credentials'], $config['auth']);
$timezone = new DateTimeZone('UTC');
$startTime = new DateTime('next Thursday 8:00pm', $tz);
$endTime = new DateTime('next Thursday 9:00pm', $tz);
$webinar = $gtw->createWebinar(
	'test Webinar',
	'This is a test webinar',
	[
		(object) [
			'startTime' => $startTime->format('Y-m-d\tH:i:s\Z'),
			'endTime' => $endTime->format('Y-m-d\tH:i:s\Z'),
		]
	],
	'UTC'
);

print "<pre>";
print "createWebinar : ({$gtw->getStatusCode()} {$gtw->getReasonPhrase()})\n";
print_r($webinar);
$key = $webinar->webinarKey;

$result = $gtw->createRegistrant($key,'firstname','lastname','firstname@lastname.com');
print "createRegistrant : ({$gtw->getStatusCode()} {$gtw->getReasonPhrase()})\n";
print_r($result);
$registrantKey = $result->registrantKey;

$result = $gtw->getRegistrants($key);
print "getRegistrants : ({$gtw->getStatusCode()} {$gtw->getReasonPhrase()})\n";
print_r($result);

$result = $gtw->deleteRegistrant($key,$registrantKey);
print "deleteRegistrants : ({$gtw->getStatusCode()} {$gtw->getReasonPhrase()})\n";
print_r($result);

$result = $gtw->getRegistrants($key);
print "getRegistrants : ({$gtw->getStatusCode()} {$gtw->getReasonPhrase()})\n";
print_r($result);

$result = $gtw->cancelWebinar($key);
print "cancelWebinar : ({$gtw->getStatusCode()} {$gtw->getReasonPhrase()})\n";
print_r($result);
