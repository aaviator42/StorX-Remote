# StorX-Remote
A library to interface with the [StorX API](https://github.com/aaviator42/StorX-API)

Current library version: `4.1` | `2022-02-22`  

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
1. Download `StorX-Remote.php`. You can rename it.
2. Include the file: `require 'StorX-Remote.php';`.

## Requirements
1. [Supported versions of PHP](https://www.php.net/supported-versions.php). At the time of writing, that's PHP `7.3+`. StorX Remote will almost certainly work on older versions, but we don't test it on those, so be careful, do your own testing.
2. The PHP cURL extenision should be enabled.

## Stuff you should know

 * Ensure that the versions of `StorX`, `StorX-Remote` and `StorX-API` match!
 * StorX Remote works almost exactly like regular StorX. See the "Functions" section below for additional functions.
 * Unlike regular StorX, a separate request does not need to be made to write changes made to DB files to disk. All changes are automatically written to disk after each key write/modify/delete request.
 * Exceptions are enabled by default, this behaviour can be changed by calling `\StorX-Remote\Rx::throwExceptions()` or by changing the value of the constant `THROW_EXCEPTIONS` at the beginning of `StorX-Remote.php`.
 * If you'd like to connect securely to an API over TLS, you might have to specify a PEM file for cURL. To do so, call `\StorX-Remote\Rx::setPemFile()` or modify the constant `CURL_PEM_FILE` at the beginning of `StorX-Remote.php`.
* cURL is configured to wait for 120 seconds for a response from the API before timing out. You can change this value if required, modify `CURLOPT_TIMEOUT` in `sendRequest()`. 

## Functions

* In StorX Remote, the class is called `Rx`, and not `Sx`.
* The following functions have the exact same behaviour as with regular StorX, the only difference being that we interact with an API and not directly with the Db files:  
 `createFile()`, `checkFile()`, `deleteFile()`, `openFile()`   
 `readKey()`, `readAllKeys()`, `returnKey()`,  
 `writeKey()`, `modifyKey()`, `modifyMultipleKeys()`,  
 `checkKey()`, `deleteKey()` and `throwExceptions()`
* `commitFile()`, and `closeFile()` don't really do anything, because as noted above, when dealing with DB files over the API, all changes are automatically written to disk after each key write/modify/delete request.
* Before using any of these functions, you need to call  `setURL()` and (optionally) `setPassword()`. See below.

### 1. `\StorX\Remote\Rx::setURL(<URL>)`

Configures the URL of the StorX API to be used by the `Rx` object.

```php

require 'StorX-Remote.php';	
$rx = new \StorX\Remote\Rx;

//set URL to API
$rx->setURL("http://example.com/api/receiver.php");

//do stuff
$rx->writeKey("key1", "val1");
```
Returns `1` if it is able to successfully connect to the API and determine that the remote's version matches that of the API. Throws an exception or returns `0` if unable to connect or if versions do not match.


### 2. `\StorX\Remote\Rx::setPassword(<password>)`

```php
//set URL to API
$rx->setURL("http://example.com/api/receiver.php");

//set password for API
$rx->setPassword("1234");

//do stuff
$rx->writeKey("key1", "val1");
```

Configures the password to be used while communicating with the API. The password should be a string. Must be called after `setURL()`. 


### 3. `\StorX\Remote\Rx::setPemFile(<filename>)`

```php
//set PEM file to be used by cURL
$rx->setPemFile("latest-certs.pem");

//set URL to API
$rx->setURL("https://example.com/api/receiver.php");

//set password for API
$rx->setPassword("1234");

//do stuff
$rx->writeKey("key1", "val1");
```

Configures the PEM file to be used by cURL for secure TLS while communicating with the API. Should be called before `setURL()`. 

## Exception Codes

If exceptions are enabled, the thrown exceptions can have these exception codes:

Code |  Meaning
-----|--------
101 | File does not exist
102 | No file open
103 | File is not locked for writing (but should be)
104 | File is locked (but shouldn't be)
105 | File not of matching StorX version
106 | Unable to create file 
107 | Unable to delete file 
108 | Unable to open file 
109 | Unable to commit changes to file
-|-
201 | Key doesn't exist 	
202 | Key already exists 	
203 | Unable to read key(s)
204 | Unable to write/modify key(s)
205 | Unable to delete key 
-|-
300 | SQLite3 error 
-|-
400 | URL not specified 
401 | Authentication failed 
402 | StorX Remote and API version mismatch 
403 | Unable to connect to the API

-----
Documentation updated `2022-02-22`
