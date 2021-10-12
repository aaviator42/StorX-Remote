# StorX-Remote
A library to interface with the [StorX API](https://github.com/aaviator42/StorX-API)

Current library version: `3.5` | `2020-10-11`  

License: `AGPLv3`

## About 

This library makes it easy for you to make use of [StorX](https://github.com/aaviator42/StorX) DB files stored on a different machine over the network. Simply [set up the API](https://github.com/aaviator42/StorX-API), include this file on the client, and get to work!

## Usage

If you've ever used StorX, then the following code should be very easy for you to understand:

```php
//StorX Remote example

<?php

//include the StorX library
require 'StorX-Remote.php';	

//create Rx 'handle' object to interface with the API
$rx = new \StorX\Remote\Rx;

//set URL to API
$rx->setURL("http://example.com/api/receiver.php");

//set password to be used
$rx->setPassword("1234");

//create a DB file on the server
$rx->createFile("testDB.dat");

//open the file for writing
$rx->openFile('testDB.dat', 1);

//write stuff to the DB file
$rx->writeKey('username', 'Aavi'); //username is now 'Aavi'

//modify the key
$rx->modifyKey('username', 'Amit'); //username is now 'Amit'

//read the key
$rx->readKey('username', $username); 
echo "User: $username"; //prints 'User: Amit'
echo "<br>";

//delete the key
$rx->deleteKey('password');
```

## Installation
1. Save `StorX-Remote.php` on your server. You can rename it.
2. Include the file: `require 'StorX-Remote.php';`.

## Requirements
1. [Supported versions of PHP](https://www.php.net/supported-versions.php). At the time of writing, that's PHP `7.3+`. StorX Remote will almost certainly work on older versions, but we don't test it on those, so be careful, do your own testing.
2. The PHP cURL extenision should be enabled.

## Stuff you should know

 * Ensure that the versions of `StorX`, `StorX-Remote` and `StorX-API` match!
 * StorX Remote works almost exactly like regular StorX. See the "Functions" section below for additional functions.
 * Unlike regular StorX, a separate request does not need to be made to write changes made to DB files to disk. All changes are automatically written to disk after each key write/modify/delete request.
 * Exceptions are enabled by default, this behaviour can be changed by changing the value of the constant `THROW_EXCEPTIONS` at the beginning of `StorX-Remote.php`.
 * If you'd like to connect to an API over SSL, you might have to specify a PEM file for cURL. To do so, modify the constant `CURL_PEM_FILE` at the beginning of `StorX-Remote.php`.
* cURL is configured to wait for 120 seconds for a response from the API before timing out. You can change this value if required, modify `CURLOPT_TIMEOUT` in `sendRequest()`. 

## Functions

* In StorX Remote, the class is called `Rx`, and not `Sx`.
* Unlike in regular StorX, `createFile()`, `checkFile()` and `deleteFile()` are also member functions of this class, and an object must be created in order to use them. Other than that, their functionality remains identical.
* `readKey()`, `returnKey()`, `writeKey()`, `modifyKey()`, `checkKey()` and `returnKey()` have the exact same behaviour as with regular StorX.
* `commitFile()` and `closeFile()` don't really do anything, because as noted above, when dealing with DB files over the API, all changes are automatically written to disk after each key write/modify/delete request.
* Before using any of these functions, you need to use `setURL()` and (optionally) `setPassword()`. See below.

#### 1. `\StorX\Remote\Rx::setURL(<URL>)`

Sets the URL to the API to be used by the `Rx` object.

```php

require 'StorX-Remote.php';	
$rx = new \StorX\Remote\Rx;

//set URL to API
rx->setURL("http://example.com/api/receiver.php");

```
Returns `1` if it is able to successfully connect to the API and determine that the remote's version matches that of the API's. Throws an exception or returns `0` if unable to connect or if versions do not match.


#### 2. `\StorX\Remote\Rx::setPassword(<password>)`

```php
//set URL to API
rx->setURL("http://example.com/api/receiver.php");

//set password for API
rx->setPassword("1234");
```

Sets the password to be used while communicating with the API. The password should be a string. Must be called after `setURL()`. 



-----
Documentation updated `2020-10-11`
