<?php
/**
 * HttpRequest Class for HTTP (GET/POST) request
 */

class HttpRequest{
    private $url, $response = null;
    private $data = array();
    private $options = array(
        'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        )
    );

    public function __construct($url=null){
        if ($url!==null) {
            $this->url = $url;
        }
    }
    public function setUrl($url=null){
        if ($url!==null) {
            $this->url = $url;
        }
    }
    /**
     * @method setData
     * @param array : $data
     * @author 
     **/
    public function setData($data=null){
        if ($data!==null) {
            $this->data = $data;
        }
        return $this;
    }
    private function getContex(){
        $this->options['http']['content'] = http_build_query($this->data);
        return stream_context_create($this->options);;
    }
    private function sendRequest($context){
        try {
            $this->response = @file_get_contents($this->url, false, $context);
        } catch (\Throwable $e) {
            $this->response = $e->getMessage();
        }
    }
    public function sendGET(){
        $this->options['http']['method'] = "GET";
        $context  = $this->getContex();
        $this->sendRequest($context);
        return $this;
    }
    public function sendPOST(){
        $this->options['http']['method'] = "POST";
        $context  = $this->getContex();
        $this->sendRequest($context);
        return $this;
    }
    public function getResponse(){
        return $this->response;
    }
    public function parseJSON(){
        return json_decode($this->response, true);
    }
    public function getObject(){
        return (object) $this->parseJSON();
    }
}