<?php
/*
StorX API Remote
by @aaviator42

StorX API Remote version: 4.1
StorX.php version: 4.1

StorX file format version: 3.1

2022-02-22

*/

namespace StorX\Remote;
use Exception;

const THROW_EXCEPTIONS = TRUE; //false: return error codes, true: throw exceptions
const CURL_PEM_FILE = NULL; //path to certificate file for SSL requests

class Rx{
	private $DBfile;
	private $password;
	private $URL;
	private $fileStatus = 0;

	private $lockMode = 0;
	
	private $throwExceptions = THROW_EXCEPTIONS;
	
	private $curlPemFile = CURL_PEM_FILE;
	
	function sendRequest($method = NULL, $URL = NULL, $params = NULL, $payload = NULL){
		if(empty($method) || empty($URL)){
			throw new Exception("StorX Remote: URL not specified", 400);
		}
		
		
		if(!empty($params)){
			rtrim($params, '?');
			$URL .= "?";
			foreach($params as $key => $value){
				$URL = $URL . $key . "=" . $value . "&";
			}
		}
		
		$ch = curl_init();
		$options = array(
			CURLOPT_CUSTOMREQUEST => $method,
			CURLOPT_URL => $URL,
			CURLOPT_USERAGENT => "StorX Remote v4.0",
			CURLOPT_TIMEOUT => 120,
			CURLOPT_RETURNTRANSFER => true);
		
		if(!empty($payload)){
			$payload["version"] = "4.0";
			$payload  = json_encode($payload);
			$headers = array(
				'Content-Type: application/json',
				'Content-Length: ' . strlen($payload));
			$options[CURLOPT_POSTFIELDS] = $payload;
			$options[CURLOPT_HTTPHEADER] = $headers;
		}
		
		if(!empty($this->curlPemFile)){
			$options[CURLOPT_CAINFO] = $this->curlPemFile;
		}
		
		curl_setopt_array($ch, $options);
		$content = curl_exec($ch);	

		if($content === false){
			return false;
		} else {
			return json_decode($content, 1);
		}
	}
	
	function initChecks($writeCheck = 0){
		if(!isset($this->URL)){
			if($this->throwExceptions){
				throw new Exception("[StorX Remote] URL not specified.", 400);
			} else {
				return 0;  
			}
		}
		
		if(!isset($this->DBfile)){
			if($this->throwExceptions){
				throw new Exception("[StorX Remote] No file open.", 102);
			} else {
				return 0;  
			}
		}
		
		
		if($writeCheck){
			if($this->lockMode === 0){
				if($this->throwExceptions){
					throw new Exception("[StorX Remote] file was not opened for writing.", 103);
				} else {
					return 0;  
				}
			}
		}
		return 1;
	}
	
	function serverErrorCheck($code){
		switch($code){
			case -2:
				if($this->throwExceptions){
					throw new Exception("[StorX Remote: Server Error] Unable to open file.", 108);
				} else {
					return 0;
				}
				break;
			case -3:
				if($this->throwExceptions){
					throw new Exception("[StorX Remote: Server Error] Unable to commit changes to file.", 109);
				} else {
					return 0;
				}
				break;
			case -666:
				if($this->throwExceptions){
					throw new Exception("[StorX Remote: Server Error] Invalid request, you broke something.", 666);
				} else {
					return 0;
				}
				break;
			case -777:
				if($this->throwExceptions){
					throw new Exception("[StorX Remote: Server Error] Authentication failed.", 401);
				} else {
					return 0;
				}
				break;
			
		}
		return 1;
	}
	
	function doofusError(){
		throw new Exception("Ya broke something, doofus.", 666);
	}
	
	public function throwExceptions($throwExceptions = NULL){
		if(!empty($throwExceptions)){
			$this->throwExceptions = (bool)$throwExceptions;
		}
		return $this->throwExceptions;
	}
	
	public function setPemFile($pemFile = NULL){
		if(!empty($pemFile)){
			$this->curlPemFile = $pemFile;
		}
		return $this->curlPemFile;
	}
	
	
	
	public function setURL($URL){
		$URL = rtrim($URL, '/\\');
		
		if(empty($URL)){
			if($this->throwExceptions){
				throw new Exception("[StorX Remote: setURL()] URL does not point to StorX Receiver of matching version.", 402);
			} else {
				return 0;
			}
		}
		
		$testURL = $URL . "/ping";
		$payload = array("version" => "4.0");
		$response = $this->sendRequest("GET", $testURL, NULL, $payload);
		
		if($response === false){
			if($this->throwExceptions){
				throw new Exception("[StorX Remote: setURL()] Unable to connect to StorX Receiver.", 403);
			} else {
				return 0;
			}
		}
		
		if(isset($response["pong"]) && $response["pong"] === "OK"){
			$this->URL = $URL;
			return 1;
		} else {
			if($this->throwExceptions){
				throw new Exception("[StorX Remote: setURL()] URL does not point to StorX Receiver of matching version.", 402);
			} else {
				return 0;
			}
		}
	}
	
	public function setPassword($password){
		if(!isset($this->URL)){
			if($this->throwExceptions){
				throw new Exception("[StorX Remote] URL not specified.", 400);
			} else {
				return 0;  
			}
		}
		
		if(!empty($password)){
			$this->password = strval($password);
		}		
	}
	
	public function openFile($filename, $mode = 0){
		if(!isset($this->URL)){
			if($this->throwExceptions){
				throw new Exception("[StorX Remote] URL not specified.", 400);
			} else {
				return 0;  
			}
		}
		
		if(empty($filename)){
			if($this->throwExceptions){
				throw new Exception("[StorX Remote: openFile()] No filename specified.", 102);
			} else {
				return 0;
			}
		}
		
		$this->DBfile = $filename;
		if($mode !== 0){
			$this->lockMode = 1;
		}
		return 1;		
	}
	
	public function closeFile(){
		unset($this->DBfile);
		$this->fileStatus = 0;
		$this->lockMode = 0;
		return 1;		
	}
	
	public function commitFile(){
		return 1;
	}
	
	public function readKey($keyName, &$store){
		if($this->initChecks() !== 1){
			return 0;
		}
		
		$URL = $this->URL . "/readKey";
		$payload = array(
			"filename" => $this->DBfile,
			"keyName" => $keyName
			);
		if(!empty($this->password)){
			$payload["password"] = $this->password;
		}
			
		$response = $this->sendRequest("GET", $URL, NULL, $payload);
			
		if($response === false){
			if($this->throwExceptions){
				throw new Exception("[StorX Remote: readKey()] Unable to connect to StorX Receiver.", 403);
			} else {
				return 0;
			}
		}
		
		if($this->serverErrorCheck($response["returnCode"]) === 0){
			return 0;
		}
		
		switch($response["returnCode"]){
			case 0:
				if($this->throwExceptions){
					throw new Exception("[StorX Remote: readKey() - Server Error] Key not found in file.", 201);
				} else {
					return 0;
				}
				break;
		}
			
		$keyValue = unserialize($response["keyValue"]);
		$store = $keyValue;
		return 1;		
	}	

	public function readAllKeys(&$store){
		if($this->initChecks() !== 1){
			return 0;
		}
		
		$URL = $this->URL . "/readAllKeys";
		$payload = array(
			"filename" => $this->DBfile,
			);
		if(!empty($this->password)){
			$payload["password"] = $this->password;
		}
			
		$response = $this->sendRequest("GET", $URL, NULL, $payload);
			
		if($response === false){
			if($this->throwExceptions){
				throw new Exception("[StorX Remote: readAllKeys()] Unable to connect to StorX Receiver.", 403);
			} else {
				return 0;
			}
		}
		
		if($this->serverErrorCheck($response["returnCode"]) === 0){
			return 0;
		}
		
		switch($response["returnCode"]){
			case 0:
				if($this->throwExceptions){
					throw new Exception("[StorX Remote: readAllKeys() - Server Error] Unable to read keys.", 203);
				} else {
					return 0;
				}
				break;
		}
			
		$store = unserialize($response["keyArray"]);
		return 1;		
	}	
	
	public function returnKey($keyName){
		if($this->initChecks() !== 1){
			return 0;
		}
		
		$URL = $this->URL . "/readKey";
		$payload = array(
			"filename" => $this->DBfile,
			"keyName" => $keyName
			);
		if(!empty($this->password)){
			$payload["password"] = $this->password;
		}
		
		$response = $this->sendRequest("GET", $URL, NULL, $payload);
			
		if($response === false){
			if($this->throwExceptions){
				throw new Exception("[StorX Remote: returnKey()] Unable to connect to StorX Receiver.", 403);
			} else {
				return 0;
			}
		}
		
		if($this->serverErrorCheck($response["returnCode"]) === 0){
			return 0;
		}
		
		switch($response["returnCode"]){
			case "STORX_ERROR":
				if($this->throwExceptions){
					throw new Exception("[StorX Remote: returnKey() - Server Error] Key not found in file.", 201);
				} else {
					return "STORX_ERROR";
				}
				break;
		}
			
		$keyValue = unserialize($response["keyValue"]);
		return $keyValue;		
	}
	
	public function writeKey($keyName, $keyValue){
		if($this->initChecks(1) !== 1){
			return 0;
		}
		
		$URL = $this->URL . "/writeKey";
		$payload = array(
			"filename" => $this->DBfile,
			"keyInputSerialization" => "PHP",
			"keyName" => $keyName,
			"keyValue" => serialize($keyValue)
			);
		if(!empty($this->password)){
			$payload["password"] = $this->password;
		}
		
		$response = $this->sendRequest("PUT", $URL, NULL, $payload);
			
		if($response === false){
			if($this->throwExceptions){
				throw new Exception("[StorX Remote: writeKey()] Unable to connect to StorX Receiver.", 403);
			} else {
				return 0;
			}
		}
		
		if($this->serverErrorCheck($response["returnCode"]) === 0){
			return 0;
		}
		
		switch($response["returnCode"]){
			case 0:
				if($this->throwExceptions){
					throw new Exception("[StorX Remote: writeKey() - Server Error] Key already exists in file or unable to write key to file.", 204);
				} else {
					return 0;
				}
				break;
			case 1:
				return 1;	
				break;
		}
		
	}	

	public function modifyKey($keyName, $keyValue){
		if($this->initChecks(1) !== 1){
			return 0;
		}
		
		$URL = $this->URL . "/modifyKey";
		$payload = array(
			"filename" => $this->DBfile,
			"keyInputSerialization" => "PHP",
			"keyName" => $keyName,
			"keyValue" => serialize($keyValue)
			);
		if(!empty($this->password)){
			$payload["password"] = $this->password;
		}
		
		$response = $this->sendRequest("PUT", $URL, NULL, $payload);
			
		if($response === false){
			if($this->throwExceptions){
				throw new Exception("[StorX Remote: modifyKey()] Unable to connect to StorX Receiver.", 403);
			} else {
				return 0;
			}
		}
		
		if($this->serverErrorCheck($response["returnCode"]) === 0){
			return 0;
		}
		
		switch($response["returnCode"]){
			case 0:
				if($this->throwExceptions){
					throw new Exception("[StorX Remote: modifyKey() - Server Error] Unable to modify key in file.", 204);
				} else {
					return 0;
				}
				break;
			case 1:
				return 1;
				break;				
		}
		
	}
	
	public function modifyMultipleKeys($keyArray){
		if($this->initChecks(1) !== 1){
			return 0;
		}
		
		$URL = $this->URL . "/modifyMultipleKeys";
		$payload = array(
			"filename" => $this->DBfile,
			"keyInputSerialization" => "PHP",
			"keyArray" => serialize($keyArray),
			);
		if(!empty($this->password)){
			$payload["password"] = $this->password;
		}
		
		$response = $this->sendRequest("PUT", $URL, NULL, $payload);
			
		if($response === false){
			if($this->throwExceptions){
				throw new Exception("[StorX Remote: modifyMultipleKeys()] Unable to connect to StorX Receiver.", 403);
			} else {
				return 0;
			}
		}
		
		if($this->serverErrorCheck($response["returnCode"]) === 0){
			return 0;
		}
		
		switch($response["returnCode"]){
			case 0:
				if($this->throwExceptions){
					throw new Exception("[StorX Remote: modifyMultipleKeys() - Server Error] Unable to modify key in file.", 204);
				} else {
					return 0;
				}
				break;
			case 1:
				return 1;
				break;				
		}
		
	}
	
	public function deleteKey($keyName){
		if($this->initChecks() !== 1){
			return 0;
		}
		
		$URL = $this->URL . "/deleteKey";
		$payload = array(
			"filename" => $this->DBfile,
			"keyName" => $keyName
			);
		if(!empty($this->password)){
			$payload["password"] = $this->password;
		}
		
		$response = $this->sendRequest("DELETE", $URL, NULL, $payload);
			
		if($response === false){
			if($this->throwExceptions){
				throw new Exception("[StorX Remote: deleteKey()] Unable to connect to StorX Receiver.", 403);
			} else {
				return 0;
			}
		}
		
		if($this->serverErrorCheck($response["returnCode"]) === 0){
			return 0;
		}
		
		switch($response["returnCode"]){
			case 0:
				if($this->throwExceptions){
					throw new Exception("[StorX Remote: deleteKey() - Server Error] Unable to delete key from file.", 205);
				} else {
					return 0;
				}
				break;
			case 1:
				return 1;
				break;				
		}
		
	}
	
	public function deleteFile($filename){
		if(!isset($this->URL)){
			if($this->throwExceptions){
				throw new Exception("[StorX Remote] URL not specified.", 400);
			} else {
				return 0;  
			}
		}
		
		$URL = $this->URL . "/deleteFile";
		$payload = array(
			"filename" => $filename
			);
		if(!empty($this->password)){
			$payload["password"] = $this->password;
		}
		
		$response = $this->sendRequest("DELETE", $URL, NULL, $payload);
			
		if($response === false){
			if($this->throwExceptions){
				throw new Exception("[StorX Remote: deleteFile()] Unable to connect to StorX Receiver.", 403);
			} else {
				return 0;
			}
		}
		
		if($this->serverErrorCheck($response["returnCode"]) === 0){
			return 0;
		}
		
		switch($response["returnCode"]){
			case 0:
				if($this->throwExceptions){
					throw new Exception("[StorX Remote: deleteFile() - Server Error] Unable to delete file.", 107);
				} else {
					return 0;
				}
				break;
			case 1:
				return 1;
				break;				
		}
		
	}
	
	public function createFile($filename){
		if(!isset($this->URL)){
			if($this->throwExceptions){
				throw new Exception("[StorX Remote] URL not specified.", 400);
			} else {
				return 0;  
			}
		}
		
		$URL = $this->URL . "/createFile";
		$payload = array(
			"filename" => $filename
			);
		if(!empty($this->password)){
			$payload["password"] = $this->password;
		}
		
		$response = $this->sendRequest("PUT", $URL, NULL, $payload);
			
		if($response === false){
			if($this->throwExceptions){
				throw new Exception("[StorX Remote: createFile()] Unable to connect to StorX Receiver.", 403);
			} else {
				return 0;
			}
		}
		
		if($this->serverErrorCheck($response["returnCode"]) === 0){
			return 0;
		}
		
		switch($response["returnCode"]){
			case 0:
				if($this->throwExceptions){
					throw new Exception("[StorX Remote: createFile() - Server Error] Unable to create file.", 106);
				} else {
					return 0;
				}
				break;
			case 1:
				return 1;
				break;
			default:
				if($this->throwExceptions){
					doofusError();
				} else {
					return 0;
				}
				break;
		}
		
	}
		
	public function checkKey($keyName){
		if($this->initChecks() !== 1){
			return 0;
		}
		
		$URL = $this->URL . "/checkKey";
		$payload = array(
			"filename" => $this->DBfile,
			"keyName" => $keyName
			);
		if(!empty($this->password)){
			$payload["password"] = $this->password;
		}
		
		$response = $this->sendRequest("GET", $URL, NULL, $payload);
			
		if($response === false){
			if($this->throwExceptions){
				throw new Exception("[StorX Remote: checkKey()] Unable to connect to StorX Receiver.", 403);
			} else {
				return 0;
			}
		}
		
		if($this->serverErrorCheck($response["returnCode"]) === 0){
			return 0;
		}
		
		switch($response["returnCode"]){
			case 0:
				return 0;
				break;
			case 1:
				return 1;
				break;
			default:
				if($this->throwExceptions){
					doofusError();
				} else {
					return "STORX_REMOTE_YOU_BROKE_SOMETHING";
				}
				break;
		}	
	}	
	
	public function checkFile($filename){
		if(!isset($this->URL)){
			if($this->throwExceptions){
				throw new Exception("[StorX Remote] URL not specified.", 400);
			} else {
				return 0;  
			}
		}
		
		$URL = $this->URL . "/checkFile";
		$payload = array(
			"filename" => $filename,
			);
		if(!empty($this->password)){
			$payload["password"] = $this->password;
		}
		
		$response = $this->sendRequest("GET", $URL, NULL, $payload);
			
		if($response === false){
			if($this->throwExceptions){
				throw new Exception("[StorX Remote: checkFile()] Unable to connect to StorX Receiver.", 403);
			} else {
				return 0;
			}
		}
		
		if($this->serverErrorCheck($response["returnCode"]) === 0){
			return 0;
		}
		
		switch($response["returnCode"]){
			case 0:
				return 0;
				break;
			case 1:
				return 1;
				break;
			case 3:
				return 3;
				break;
			case 4:
				return 4;
				break;
			case 5:
				return 5;
				break;
			default:
				if($this->throwExceptions){
					doofusError();
				} else {
					return "STORX_REMOTE_YOU_BROKE_SOMETHING";
				}
				break;
		}
	}
	
}
	