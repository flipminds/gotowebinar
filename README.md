# GotoWebinar  API 

## Description ##

A simple wrapper around the the [GotoWebinar API](https://goto-developer.logmeininc.com/content/gotowebinar-api-reference). 

## Install via Composer ##

We recommend installing this package with [Composer](http://getcomposer.org/).

Run in your project root:

```
composer require flipminds/gotowebinar:~1.0.0
```
## Simple Usage ## 

```php
use FlipMinds\GotoWebinar\GotoWebinar;
  
$credentials = [
    'username' => ''
    'password' => ''
    'apiKey' => ''
];
 
$gtw = new GotoWebinar($credentials);
 
$webinars = $gtw->getUpcoming();
  
$key = ''
foreach($webinars as $webinar) { 
    if (!$key) $key = $webinar->webinarKey;
}
 
$result = $gtw->createRegistrant($key, 'firstname','lastname','email);
print_r($result);

```
See the examples folder for more usages examples.

## Caching the Authentication Token ##

By default GotoWebinar Authentication Tokens are valid for 356 days. Caching the token result in one less round trip to GotoWebinar servers.  

You can use the `getAuth()` method call to retrieve an array of data that can be cached. You can use this array as a second argument to the constructor.

You can also set a callback to capture the authentication array after authenticating with GotoWebinar servers.  

```php

// $auth =  getfromcache()
   
$gtw = new GotoWebinar($credentials, $auth); // see Above
 
$gtw->setAuthCallback(function($auth) {
 // save $auth to cache 
});
 
```