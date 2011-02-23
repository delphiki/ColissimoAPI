<?php

/*
 * @author Julien 'delphiki' Villetorte <gdelphiki@gmail.com>
 * http://www.delphiki.com/
 * http://twitter.com/delphiki
 *
 */

class ColissimoAPI{
    private $host = 'http://www.laposte.fr';
    private $page = '/outilsuivi/web/suiviInterMetiers.php';
    private $key;
    private $method;
    private $code;
    private $param_string;
    private $xmlResponse;
    private $jsonResponse;
    private $invalidResponse;
    private $parsedResponse = array();
    
    public function __construct($_key = 'd112dc5c716d443af02b13bf708f73985e7ee943'){
        $this->key = $_key;
    }
    
    public function getStatus($_code, $_method = 'xml'){
        $this->code = $_code;
        $this->method = $_method;
        
        $this->param_string = '?key='.$this->key.'&code='.$this->code;
        
        $res = $this->host.$this->page.$this->param_string;
        switch($_method){
            case 'xml':
                $this->getXmlResponse();
            break;
            case 'json':
                $this->getJsonResponse();
            break;
            case 'img':
            default:
                return $this->host.$this->page.$this->param_string;
            break;
        }
        
        return $this->parsedResponse;
    }
    
    public function setKey($_key){
        $this->key = $_key;
    }
    
    private function getXmlResponse(){
        $ch = curl_init();
        
        $url = $this->host.$this->page.$this->param_string.'&method=xml';
        curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
		curl_setopt($ch, CURLOPT_FAILONERROR, true); 
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
		curl_setopt($ch, CURLOPT_TIMEOUT, 3);
		$data = curl_exec($ch);
		curl_close($ch);
        
        $this->xmlResponse = $data;
        return $this->parseXmlResponse();
    }
    
    private function getJsonResponse(){
        $ch = curl_init();
        
        $url = $this->host.$this->page.$this->param_string.'&method=json';
        curl_setopt($ch, CURLOPT_URL, $url); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
		curl_setopt($ch, CURLOPT_FAILONERROR, true); 
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
		curl_setopt($ch, CURLOPT_TIMEOUT, 3);
		$data = curl_exec($ch);
		curl_close($ch);
        
        $this->jsonResponse = $data;
        return $this->parseJsonResponse();
    }
    
    private function parseXmlResponse(){
        $dom = new DOMDocument('1.0', 'utf-8');
        if(!$dom->loadXML($this->xmlResponse)){
            $this->invalidResponse = $this->xmlResponse;
            $this->xmlResponse = null;
            
            if($this->invalidResponse != '')
                return $this->invalidResponse;
            else
                throw new Exception("Invalid XML.\n\n".$this->invalidResponse);
        }
        
        $this->parsedResponse['status'] = $dom->getElementsByTagName('status')->item(0)->nodeValue;
        $this->parsedResponse['code'] = $dom->getElementsByTagName('code')->item(0)->nodeValue;
        $this->parsedResponse['client'] = $dom->getElementsByTagName('client')->item(0)->nodeValue;
        $this->parsedResponse['date'] = $dom->getElementsByTagName('date')->item(0)->nodeValue;
        $this->parsedResponse['message'] = $dom->getElementsByTagName('message')->item(0)->nodeValue;
        $this->parsedResponse['gamme'] = $dom->getElementsByTagName('gamme')->item(0)->nodeValue;
        $this->parsedResponse['base_label'] = $dom->getElementsByTagName('base_label')->item(0)->nodeValue;
        $this->parsedResponse['link'] = $dom->getElementsByTagName('link')->item(0)->nodeValue;
        $this->parsedResponse['error'] = $dom->getElementsByTagName('error')->item(0)->nodeValue;
        
        $this->parsedResponse = array_map('utf8_decode', $this->parsedResponse);
        
        return true;
    }
    
    private function parseJsonResponse(){
        if($this->jsonResponse === null){
            $this->invalidResponse = $this->jsonResponse;
            $this->jsonResponse = null;
            
            if($this->invalidResponse != '')
                return $this->invalidResponse;
            else
                throw new Exception("Invalid JSON.\n\n".$this->invalidResponse);
        }
        
        $this->parsedResponse = json_decode($this->jsonResponse, true);
        $this->parsedResponse = array_map('utf8_decode', $this->parsedResponse);
        
        return true;
    }
}