<?php
/**********************************************************
// Define security credentials for your app.
// You can get these when you register your app on the Live Connect Developer Center.*/
define("client_id", "YOUR_CLIENT_ID_HERE");
define("client_secret", "YOUR_CLIENT_SECRET_HERE");
define("callback_uri", "YOUR_CALLBACK_URL_HERE");
define("onedrive_base_url", "https://graph.microsoft.com/v1.0/");
define("token_store", "tokens"); // Edit path to your token store if required, see Wiki for more info.

class onedrive {

	public $access_token = '';

	public function __construct($passed_access_token) {
		$this->access_token = $passed_access_token;
	}
	
	
	// Gets the contents of a onedrive folder.
	// Pass in the ID of the folder you want to get.
	// Or leave the second parameter blank for the root directory (/me/skydrive/files)
	// Returns an array of the contents of the folder.

	/*public function get_folder($folderid, $sort_by='name', $sort_order='ascending', $limit='255', $offset='0') {
		if ($folderid === null) {
			$response = $this->curl_get(onedrive_base_url."me/skydrive/files?sort_by=".$sort_by."&sort_order=".$sort_order."&offset=".$offset."&limit=".$limit."&access_token=".$this->access_token);
		} else {
			$response = $this->curl_get(onedrive_base_url.$folderid."/files?sort_by=".$sort_by."&sort_order=".$sort_order."&offset=".$offset."&limit=".$limit."&access_token=".$this->access_token);
		}
		if (@array_key_exists('error', $response)) {
			throw new Exception($response['error']." - ".$response['description']);
			exit;
		} else {		
			$arraytoreturn = Array();
			$temparray = Array();
			if (@$response['paging']['next']) {
				parse_str($response['paging']['next'], $parseout);
				$numerical = array_values($parseout);
			}
			if (@$response['paging']['previous']) {
				parse_str($response['paging']['previous'], $parseout1);
				$numerical1 = array_values($parseout1);
			}			
			foreach ($response as $subarray) {
				foreach ($subarray as $item) {
					if (@array_key_exists('id', $item)) {
						array_push($temparray, Array('name' => $item['name'], 'id' => $item['id'], 'type' => $item['type'], 'size' => $item['size'], 'source' => @$item['source']));
					}
				}
			}
			$arraytoreturn['data'] = $temparray;
			if (@$numerical[0]) {
				if (@$numerical1[0]) {
					$arraytoreturn['paging'] = Array('previousoffset' => $numerical1[0], 'nextoffset' => $numerical[0]);
				} else {
					$arraytoreturn['paging'] = Array('previousoffset' => 0, 'nextoffset' => $numerical[0]);		
				}			
			} else {
				$arraytoreturn['paging'] = Array('previousoffset' => 0, 'nextoffset' => 0);
			}
			return $arraytoreturn;
		}
	}*/
	public function get_folder($folderid, $sort_by = 'name', $sort_order = 'ascending', $limit = '255', $offset = '0')
	{
		if ($folderid === null) {
			$response = $this->curl_get(onedrive_base_url . "drive/root/children");
		} else {

			$response = $this->curl_get(onedrive_base_url . "/me/drive/items/" . $folderid . "/children");
		}
	
		if (@array_key_exists('error', $response)) {
			throw new Exception($response['error'] . " - " . $response['description']);
			exit; 
		} else {
			$arraytoreturn = array();
			$temparray = array(); 
				
			$array = $response['value'];
			foreach ($array as $obj) {
				$thumb_url = '';
				if (@array_key_exists('id', $obj)) {
					if (isset($obj['folder'])) {
						$type = "folder";
						$download_link = "";
					} else {
						$type = "file";
						$download_link = $obj['@microsoft.graph.downloadUrl'];
					}
					if (isset($obj['file'])) {
						$thumb_url = '';
						$is_image = explode('/', $obj['file']['mimeType']);
						if ($is_image[0] == 'image') {

							$response_image = $this->curl_get(onedrive_base_url . "/me/drive/items/" . $obj['id'] . "/thumbnails");

							$thumb_url = $response_image['value'][0]['large']['url'];

						}
					}
					array_push($temparray, array('name' => $obj['name'], 'id' => $obj['id'], 'size' => $obj['size'], 'type' => $type, 'download_link' => $download_link, 'image_url' => $thumb_url));
				}
			}
			$arraytoreturn['data'] = $temparray;
		
			return $arraytoreturn;
		}
	}

	// Gets the remaining quota of your onedrive account.
	// Returns an array containing your total quota and quota available in bytes.

	function get_quota() {
		$response = $this->curl_get(onedrive_base_url."me/drive/recent?access_token=".$this->access_token);
		if (@array_key_exists('error', $response)) {
			throw new Exception($response['error']." - ".$response['description']);
			exit;
		} else {			
			return $response;
		}
	}

	// Gets the properties of the folder.
	// Returns an array of folder properties.
	// You can pass null as $folderid to get the properties of your root onedrive folder.

	public function get_folder_properties($folderid) {
		$arraytoreturn = Array();
		if ($folderid === null) {
			$response = $this->curl_get(onedrive_base_url."/me/skydrive?access_token=".$this->access_token);
		} else {
			$response = $this->curl_get(onedrive_base_url.$folderid."?access_token=".$this->access_token);
		}
		
		if (@array_key_exists('error', $response)) {
			throw new Exception($response['error']." - ".$response['description']);
			exit;
		} else {			
			@$arraytoreturn = Array('id' => $response['id'], 'name' => $response['name'], 'parent_id' => $response['parent_id'], 'size' => $response['size'], 'source' => $response['source'], 'created_time' => $response['created_time'], 'updated_time' => $response['updated_time'], 'link' => $response['link'], 'upload_location' => $response['upload_location'], 'is_embeddable' => $response['is_embeddable'], 'count' => $response['count']);
			return $arraytoreturn;
		}
	}

	// Gets the properties of the file.
	// Returns an array of file properties.

	public function get_file_properties($fileid) {
		$response = $this->curl_get(onedrive_base_url.$fileid."?access_token=".$this->access_token);
		if (@array_key_exists('error', $response)) {
			throw new Exception($response['error']." - ".$response['description']);
			exit;
		} else {
			$arraytoreturn = Array('id' => $response['id'], 'type' => $response['type'], 'name' => $response['name'], 'parent_id' => $response['parent_id'], 'size' => $response['size'], 'source' => $response['source'], 'created_time' => $response['created_time'], 'updated_time' => $response['updated_time'], 'link' => $response['link'], 'upload_location' => $response['upload_location'], 'is_embeddable' => $response['is_embeddable']);
			return $arraytoreturn;
		}
	}

	// Gets a pre-signed (public) direct URL to the item
	// Pass in a file ID
	// Returns a string containing the pre-signed URL.

	public function get_source_link($fileid) {
		$response = $this->get_file_properties($fileid);
		if (@array_key_exists('error', $response)) {
			throw new Exception($response['error']." - ".$response['description']);
			exit;
		} else {
			return $response['source'];
		}
	}
	
	
	// Gets a shared read link to the item.
	// This is different to the 'link' returned from get_file_properties in that it's pre-signed.
	// It's also a link to the file inside onedrive's interface rather than directly to the file data.

	function get_shared_read_link($fileid) {
		$response = curl_get(onedrive_base_url.$fileid."/shared_read_link?access_token=".$this->access_token);
		if (@array_key_exists('error', $response)) {
			throw new Exception($response['error']." - ".$response['description']);
			exit;
		} else {	
			return $response['link'];
		}
	}

	// Gets a shared edit (read-write) link to the item.

	function get_shared_edit_link($fileid) {
		$response = curl_get(onedrive_base_url.$fileid."/shared_edit_link?access_token=".$this->access_token);
		if (@array_key_exists('error', $response)) {
			throw new Exception($response['error']." - ".$response['description']);
			exit;
		} else {	
			return $response['link'];
		}
	}

	// Deletes an object.

	function delete_object($fileid) {
		$response = curl_delete(onedrive_base_url.$fileid."?access_token=".$this->access_token);
		if (@array_key_exists('error', $response)) {
			throw new Exception($response['error']." - ".$response['description']);
			exit;
		} else {
			return true;
		}
	}
	
	// Downloads a file from onedrive to the server.
	// Pass in a file ID.
	// Returns a multidimensional array:
	// ['properties'] contains the file metadata and ['data'] contains the raw file data.
	
	public function download($fileid) {
		$props = $this->get_file_properties($fileid);
		$response = $this->curl_get(onedrive_base_url.$fileid."/content?access_token=".$this->access_token, "false", "HTTP/1.1 302 Found");
		$arraytoreturn = Array();
		if (@array_key_exists('error', $response)) {
			throw new Exception($response['error']." - ".$response['description']);
			exit;
		} else {
			array_push($arraytoreturn, Array('properties' => $props, 'data' => $response));
			return $arraytoreturn;
		}		
	}

	
	// Uploads a file from disk.
	// Pass the $folderid of the folder you want to send the file to, and the $filename path to the file.
	// Also use this function for modifying files, it will overwrite a currently existing file.

	function put_file($folderid, $filename) {
		$r2s = onedrive_base_url.$folderid."/files/".basename($filename)."?access_token=".$this->access_token;
		$response = $this->curl_put($r2s, $filename);
		if (@array_key_exists('error', $response)) {
			throw new Exception($response['error']." - ".$response['description']);
			exit;
		} else {
			return $response;
		}
			
	}
	
	/**
	 * Upload file directly from remote URL
	 * 
	 * @param string $sourceUrl - URL of the file
	 * @param string $folderId - folder you want to send the file to
	 * @param string $filename - target filename after upload
	 */
	function put_file_from_url($sourceUrl, $folderId, $filename){
		$r2s = onedrive_base_url.$folderId."/files/".$filename."?access_token=".$this->access_token;
		
		$chunkSizeBytes = 1 * 1024 * 1024; //1MB
		
		//download file first to tempfile
		$tempFilename = tempnam("/tmp", "UPLOAD");
		$temp = fopen($tempFilename, "w");
		
		$handle = @fopen($sourceUrl, "rb");
		if($handle === FALSE){
			throw new Exception("Unable to download file from " . $sourceUrl);
		}
		
		while (!feof($handle)) {
			$chunk = fread($handle, $chunkSizeBytes);
			fwrite($temp, $chunk);
		}		
		
		fclose($handle);
		fclose($temp);
		
		//upload to onedrive
		$response = $this->curl_put($r2s, $tempFilename);
		if (@array_key_exists('error', $response)) {
			throw new Exception($response['error']." - ".$response['description']);
			exit;
		} else {
			unlink($tempFilename);
			return $response;
		}
	}	
	
	
	// Creates a folder.
	// Pass $folderid as the containing folder (or 'null' to create the folder under the root).
	// Also pass $foldername as the name for the new folder and $description as the description.
	// Returns the new folder metadata or throws an exception.
	
	function create_folder($folderid, $foldername, $description="") {
		if ($folderid===null) {
			$r2s = onedrive_base_url."me/skydrive";
		} else {
			$r2s = onedrive_base_url.$folderid;
		}
		$arraytosend = array('name' => $foldername, 'description' => $description);	
		$response = $this->curl_post($r2s, $arraytosend, $this->access_token);
		if (@array_key_exists('error', $response)) {
				throw new Exception($response['error']." - ".$response['description']);
				exit;
			} else {		
				$arraytoreturn = Array();
				array_push($arraytoreturn, Array('name' => $response['name'], 'id' => $response['id']));					
				return $arraytoreturn;
			}
	}
	
	// *** PROTECTED FUNCTIONS ***
	
	// Internally used function to make a GET request to onedrive.
	// Functions can override the default JSON-decoding and return just the plain result.
	// They can also override the expected HTTP status code too.
	
	protected function curl_get($uri, $json_decode_output="true", $expected_status_code="HTTP/1.1 200 OK") {
		echo $uri;
		$output = "";
		$output = @file_get_contents($uri);
	
		if ($http_response_header[0] == $expected_status_code) {
			if ($json_decode_output == "true") {
				return json_decode($output, true);
			} else {
				return $output;
			}
		} else {
			print_r($http_response_header);
			return Array('error' => 'HTTP status code not expected - got ', 'description' => substr($http_response_header[0],9,3));
		}
	}

	// Internally used function to make a POST request to onedrive.

	protected function curl_post($uri, $inputarray, $access_token) {
		$trimmed = json_encode($inputarray);
		try {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $uri);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);	
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Authorization: Bearer '.$access_token,
		));
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $trimmed);
		$output = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		} catch (Exception $e) {
		}
		if ($httpcode == "201") {
			return json_decode($output, true);
		} else {
			return array('error' => 'HTTP status code not expected - got ', 'description' => $httpcode);
		}
	}

	// Internally used function to make a PUT request to onedrive.

	protected function curl_put($uri, $fp) {
	  $output = "";
	  try {
	  	$pointer = fopen($fp, 'r+');
	  	$stat = fstat($pointer);
	  	$pointersize = $stat['size'];
		$ch = curl_init($uri);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($ch, CURLOPT_PUT, true);
		curl_setopt($ch, CURLOPT_INFILE, $pointer);
		curl_setopt($ch, CURLOPT_INFILESIZE, (int)$pointersize);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));			
		
		$output = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	  } catch (Exception $e) {
	  }
	  	if ($httpcode == "200" || $httpcode == "201") {
	  		return json_decode($output, true);
	  	} else {
	  		return array('error' => 'HTTP status code not expected - got ', 'description' => $httpcode);
	  	}
		
	}

	// Internally used function to make a DELETE request to onedrive.

	protected function curl_delete($uri) {
	  $output = "";
	  try {
		$ch = curl_init($uri);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');    
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_TIMEOUT, 4);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
		$output = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	  } catch (Exception $e) {
	  }
	  	if ($httpcode == "200") {
	  		return json_decode($output, true);
	  	} else {
	  		return array('error' => 'HTTP status code not expected - got ', 'description' => $httpcode);
	  	}
	}
	

}

class onedrive_auth {

	// build_oauth_url()
	
	// Builds a URL for the user to log in to onedrive and get the authorization code, which can then be
	// passed onto get_oauth_token to get a valid oAuth token.

	public static function build_oauth_url() {
		
		$response = "https://login.microsoftonline.com/common/oauth2/v2.0/authorize?client_id=".client_id."&response_type=code&response_mode=query&scope=profile%20offline_access%20https%3A%2F%2Fgraph.microsoft.com%2Fmail.read%20https%3A%2F%2Fgraph.microsoft.com%2Fmail.send%20https%3A%2F%2Fgraph.microsoft.com%2Fmail.readwrite%20https%3A%2F%2Fgraph.microsoft.com%2Fcalendars.readwrite%20https%3A%2F%2Fgraph.microsoft.com%2Fcontacts.readwrite%20https%3A%2F%2Fgraph.microsoft.com%2FTasks.ReadWrite%20https%3A%2F%2Fgraph.microsoft.com%2FTasks.Read%20https%3A%2F%2Fgraph.microsoft.com%2FGroup.Read.All%20https%3A%2F%2Fgraph.microsoft.com%2FGroup.ReadWrite.All%20openid&state=12345&prompt=consent&redirect_uri=".urlencode(callback_uri);
		return $response;
	}


	// get_oauth_token()

	// Obtains an oAuth token
	// Pass in the authorization code parameter obtained from the inital callback.
	// Returns the oAuth token and an expiry time in seconds from now (usually 3600 but may vary in future).

	public static function get_oauth_token($auth) {
		$arraytoreturn = array();
		$output = "";
		try {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "https://login.microsoftonline.com/common/oauth2/v2.0/token");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);	
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/x-www-form-urlencoded',
				));
			curl_setopt($ch, CURLOPT_POST, TRUE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);		

			$data = "client_id=".client_id."&redirect_uri=".urlencode(callback_uri)."&client_secret=".urlencode(client_secret)."&code=".$auth."&grant_type=authorization_code";	
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			$output = curl_exec($ch);
		} catch (Exception $e) {
		}
	
		$out2 = json_decode($output, true);
		$arraytoreturn = Array('access_token' => $out2['access_token'], 'refresh_token' => $out2['refresh_token'], 'expires_in' => $out2['expires_in']);
		return $arraytoreturn;
	}
	
	
	// refresh_oauth_token()
	
	// Attempts to refresh an oAuth token
	// Pass in the refresh token obtained from a previous oAuth request.
	// Returns the new oAuth token and an expiry time in seconds from now (usually 3600 but may vary in future).
		
	public static function refresh_oauth_token($refresh) {
		$arraytoreturn = array();
		$output = "";
		try {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "https://login.microsoftonline.com/common/oauth2/v2.0/token");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);	
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/x-www-form-urlencoded',
				));
			curl_setopt($ch, CURLOPT_POST, TRUE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);		

			$data = "client_id=".client_id1."&redirect_uri=".urlencode(callback_uri3)."&client_secret=".urlencode(client_secret1)."&refresh_token=".$refresh."&grant_type=refresh_token";	
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			$output = curl_exec($ch);
		} catch (Exception $e) {
		}
	
		$out2 = json_decode($output, true);
		$arraytoreturn = Array('access_token' => $out2['access_token'], 'refresh_token' => $out2['refresh_token'], 'expires_in' => $out2['expires_in']);
		return $arraytoreturn;
	}
	
}

class onedrive_tokenstore {

	// acquire_token()
	
	// Will attempt to grab an access_token from the current token store.
	// If there isn't one then return false to indicate user needs sending through oAuth procedure.
	// If there is one but it's expired attempt to refresh it, save the new tokens and return an access_token.
	// If there is one and it's valid then return an access_token.
	
	
	public static function acquire_token() {
		
		$response = onedrive_tokenstore::get_tokens_from_store();
		if (empty($response['access_token'])) {	// No token at all, needs to go through login flow. Return false to indicate this.
			return false;
			exit;
		} else {
			if (time() > (int)$response['access_token_expires']) { // Token needs refreshing. Refresh it and then return the new one.
				$refreshed = onedrive_auth::refresh_oauth_token($response['refresh_token']);
				if (onedrive_tokenstore::save_tokens_to_store($refreshed)) {
					$newtokens = onedrive_tokenstore::get_tokens_from_store();
					return $newtokens['access_token'];
				}
				exit;
			} else {
				return $response['access_token']; // Token currently valid. Return it.
				exit;
			}
		}
	}
	
	
	public static function get_tokens_from_store() {
		$response = json_decode(file_get_contents(token_store), TRUE);
		return $response;
	}
	
	public static function save_tokens_to_store($tokens) {
		$tokentosave = Array();
		$tokentosave = Array('access_token' => $tokens['access_token'], 'refresh_token' => $tokens['refresh_token'], 'access_token_expires' => (time()+(int)$tokens['expires_in']));
		if (file_put_contents(token_store, json_encode($tokentosave))) {
			return true;
		} else {
			return false;
		}
	}
	
		public static function destroy_tokens_in_store() {
		
		if (file_put_contents(token_store, "loggedout")) {
			return true;
		} else {
			return false;
		}
		
	   }
}

?>
