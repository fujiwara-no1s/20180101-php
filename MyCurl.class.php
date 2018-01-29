<?php

class MyCurl {

  public $method = "GET";
  public $cookie = "mycurl.cookie";
  public $curl;
  public $param;
  public $responseBody;
  public $responseHeader;
  public $statusCode;

  public function __construct(string $request_url, array $param = [], array $posts = []) {
     $this->param = $param;
     if ( isset($this->param['method'])) {
        $this->method = $this->param['method'];
     }
     $this->curl = curl_init(); 
      
     // リクエストURL
     curl_setopt( $this->curl, CURLOPT_URL , $request_url );
     // ヘッダーを設定
     curl_setopt( $this->curl, CURLOPT_HEADER, true );
     curl_setopt( $this->curl, CURLOPT_HTTPHEADER, ["Accept-Language:ja,en-US;q=0.9,en;q=0.8"] );
   
     // GET
     curl_setopt( $this->curl, CURLOPT_CUSTOMREQUEST , $this->method );
     // Winなどでは中間証明書でエラーが起きる場合があるので、中間証明書の検証を行わない
     curl_setopt( $this->curl, CURLOPT_SSL_VERIFYPEER , false );
     // curl_execの結果を文字列で返す
     curl_setopt( $this->curl, CURLOPT_RETURNTRANSFER , true );
     if ($this->method === "POST" ) {
         curl_setopt( $this->curl, CURLOPT_POST, TRUE);
         curl_setopt( $this->curl, CURLOPT_POSTFIELDS, $posts);
     }
     curl_setopt( $this->curl, CURLOPT_RETURNTRANSFER, true);

     curl_setopt( $this->curl,CURLOPT_COOKIEFILE,$this->cookie);
     curl_setopt( $this->curl, CURLOPT_COOKIEJAR, $this->cookie);
     curl_setopt( $this->curl, CURLOPT_FOLLOWLOCATION, TRUE);
  }
  
  public function exec() {
    $response = curl_exec( $this->curl );
    $info = curl_getinfo( $this->curl );
    curl_close( $this->curl );
    $this->statusCode = $info['http_code'];

    if($response === false || $this->statusCode != 200 ) {
       $this->responseBody = "error: fail to get content";
    } else {
       $this->responseBody = substr( $response, $info['header_size']);
    }
    if($response !== false) {
       $this->responseHeader = substr( $response, 0, $info['header_size']);
    }
  }
  
  public function getResponseBody() {
    return $this->responseBody;
  }

  public function getResponseHeader() {
    $results = [];
    $lines = explode("\n",$this->responseHeader);
    array_shift($lines);
    foreach($lines as $line) {
       $line = trim($line);
       if (empty($line)) continue;
       list($key, $value) = explode(":",$line);
       $results[$key] = $value;
    }
    return $results;
  }
  
  public function finish() {
      unlink($this->cookie);
  }
}

