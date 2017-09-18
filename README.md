# GotoWebinar  API 

## Description ##

A simple wrapper around the the [GotoWebinar API](https://goto-developer.logmeininc.com/content/gotowebinar-api-reference). 

## Install via Composer ##

We recommend installing this package with [Composer](http://getcomposer.org/).

Run in your project root:

```
composer require flipminds/gotowebinar:@dev
```
## Simple Usage ## 

```php
use FlipMinds/GotoWebinar;
  
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

```php
$gtw = new GotoWebinar($credentials); // see Above
 
$auth = $gtw->getAuth();
```

`$auth` can now be stored for later use.

```php
$auth = getFileFromCache(); // your function call. 

$gtw = new GotoWebinar($credentials, $auth); 
```
