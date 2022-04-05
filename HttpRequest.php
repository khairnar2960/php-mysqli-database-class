<?php
/**
 * HttpRequest Class for HTTP (GET/POST) request
 */

class HttpRequest{
    private $url, $data, $response = null;
    private $options = array('http' => array('header'  => "Content-type: application/x-www-form-urlencoded\r\n",));
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
    public function sendGET(){
        $this->options['http']['method'] = "GET";
        $this->options['http']['content'] = http_build_query($this->data);
        $context  = stream_context_create($this->options);
        $this->response = file_get_contents($this->url, false, $context);
        return $this;
    }
    public function sendPOST(){
        $this->options['http']['method'] = "POST";
        $this->options['http']['content'] = http_build_query($this->data);
        $context  = stream_context_create($this->options);
        $this->response = file_get_contents($this->url, false, $context);
        return $this;
    }
    public function getResponse(){
        return $this->response;
    }
    public function getJSON(){
        return json_decode($this->response, true);
    }
}