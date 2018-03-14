<?php

class Narnooauthen {

	public $authenticate_url = 'https://connect.narnoo.com/oauth/accesstoken';
    public $accessKey;
    public $secretKey;

	public function __construct($access_key,$secret_key) {

        $this->accessKey = $access_key;
        $this->secretKey = $secret_key;
    }


    public function authenticate(){

    	$api_settings = array(
				"API-KEY:".$this->accessKey,
				"API-SECRET-KEY:".$this->secretKey
		);

		$fields = array(
			"client_id"				=>	$this->accessKey,
			"client_secret"			=>	$this->secretKey,
			"grant_type"			=>	'client_credentials'
		);


		$query = http_build_query( $fields );

		//open connection
		$ch = curl_init();

		//set the url, number of POST vars, POST data
		curl_setopt($ch,CURLOPT_URL, $this->authenticate_url);
		curl_setopt($ch,CURLOPT_HTTPHEADER, $api_settings);
		curl_setopt($ch,CURLOPT_POST, count($fields));
		curl_setopt($ch,CURLOPT_POSTFIELDS, $query);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		$response = curl_exec($ch);
		curl_close($ch);
		
		if(!empty( $response )){
			$token = json_decode( $response );
			
			if($token->access_token){
				return $token->access_token;
			}else{
				error_log('Narnoo API - No authenticate token');
				
			}
			
		}else{
			error_log('Narnoo API - No authenticate returned. Authorization error!');
			
		}

    }


}

?>