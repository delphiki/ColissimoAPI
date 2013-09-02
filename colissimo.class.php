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
    private $user_agent = 'Dalvik/1.4.0 (Linux; U; Android 2.3.5; HTC Desire HD Build/GRJ90)';
    private $key ;
    private $method;
    private $code;
    private $image_dir;
    private $param_string;
    private $response;
    private $invalidResponse;
    private $parsedResponse = array();
    
    /**
     * @access Public
     * @param string $_key
     */
    public function __construct($_key = 'd112dc5c716d443af02b13bf708f73985e7ee943'){
        $this->setKey($_key);
        $this->setImageDir('images/');
    }
    
    /**
     * @access public
     * @name setImageDir()
     * @param Path of image Directory
     * @throws Exception
     */
    public function setImageDir($_image_dir){
        $this->image_dir = $_image_dir;
        if(substr($this->image_dir, -1) !== '/'){
            $this->image_dir .= '/';
        }
        if(!is_writable($this->image_dir)){
            throw new Exception('Image directory not writable.');
        }
    }
    
    /**
     * @access public
     * @param string $_key
     * @throws Exception
     */
    public function setKey($_key ){
        if(preg_match('#^[a-zA-Z0-9]{40}$#', $_key) || empty($_key) ){
            $this->key = $_key;
        } else {
            throw new Exception('Invalid key or empty.');
        }
    }

    /**
     * @access public$
     * @name   setUserAgent()
     * @param  string $_user_agent
     */
    public function setUserAgent($_user_agent){
        $this->user_agent = $_user_agent;
    }
 
    /**
     * @access public
     * @name setReferer()
     * @param string $_referer
     * @throws Exception
     */
    public function setReferer($_referer){
        if(filter_var($_referer, FILTER_VALIDATE_URL)) {
            $this->referer = $_referer;
        } else {
            throw new Exception('Invalid URL');
        }
    }
    
    /**
     * @access public
     * @name getStatus()
     * @param string $_code
     * @param string $_method
     * @param bool $_plain
     * @return Xml
     * @throws Exception
     */
    public function getStatus($_code, $_method = 'xml', $_plain = false){
        if(!preg_match('#^[0-9]{1}[a-zA-Z]{1}[0-9]{11}#', $_code)) {
            throw new Exception('Invalid code.');
        }
        $this->code = $_code;
        
        $allowed_methods = array('xml', 'json', 'img');
        
        if(!in_array($_method, $allowed_methods)){
            throw new Exception('Invalid method.');
        }
        $this->method = $_method;
        
        $this->param_string = '?key='.urlencode($this->key).'&code='.urlencode($this->code);
        
        return $this->getResponse(!$_plain);
    }
            
    /**
     * @access private
     * @name getResponse()  
     * @param bool $_parse
     * @return type
     */
    private function getResponse($_parse = true){
        $ch = curl_init();
        
        $url = $this->host.$this->page.$this->param_string;
     
        if($this->method != 'img'){
            $url .= '&method='.$this->method;
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->user_agent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        $data = curl_exec($ch);
        curl_close($ch);

        $this->response = $data;

        return ($_parse || $this->method == 'img') ? $this->parseResponse() : $this->response;
    }

   /**
    * @access private
    * @name parseResponse()
    * @return img, xml, json
    * @throws Exception
    */
    private function parseResponse(){
        switch($this->method){
            default:
                throw new Exception('Invalid method.');
                break;
            case 'img':
                $newImg = imagecreatefromstring($this->response);
                imagepng($newImg, $this->image_dir.$this->code.'.png');

                $this->parsedResponse = array(
                    'code' => $this->code,
                    'image' => $this->image_dir.$this->code.'.png'
                );
                break;
            case 'xml':
                $dom = new DOMDocument('1.0', 'utf-8');
                if(!$dom->loadXML($this->response)){
                    $this->invalidResponse = $this->response;
                    $this->response = null;
                    
                    if($this->invalidResponse != NULL ) {
                        return $this->invalidResponse;
                    } else {
                        throw new Exception("Invalid XML.\n\n" . $this->invalidResponse);
                    }
                }
                
                $this->parsedResponse['status']     = $dom->getElementsByTagName('status')->item(0)->nodeValue;
                $this->parsedResponse['code']       = $dom->getElementsByTagName('code')->item(0)->nodeValue;
                $this->parsedResponse['client']     = $dom->getElementsByTagName('client')->item(0)->nodeValue;
                $this->parsedResponse['date']       = $dom->getElementsByTagName('date')->item(0)->nodeValue;
                $this->parsedResponse['message']    = $dom->getElementsByTagName('message')->item(0)->nodeValue;
                $this->parsedResponse['gamme']      = $dom->getElementsByTagName('gamme')->item(0)->nodeValue;
                $this->parsedResponse['base_label'] = $dom->getElementsByTagName('base_label')->item(0)->nodeValue;
                $this->parsedResponse['link']       = $dom->getElementsByTagName('link')->item(0)->nodeValue;
                $this->parsedResponse['error']      = $dom->getElementsByTagName('error')->item(0)->nodeValue;
                
                $this->parsedResponse = array_map('utf8_decode', $this->parsedResponse);
                
                break;
            case 'json':
                if($this->response === null){
                    $this->invalidResponse = $this->response;
                    $this->response = null;
                    
                    if( $this->invalidResponse != NULL ){
                        return $this->invalidResponse;
                    } else {
                        throw new Exception("Invalid JSON.\n\n".$this->invalidResponse);
                    }
                }
                
                $this->parsedResponse = json_decode($this->response, true);
                $this->parsedResponse = array_map('utf8_decode', $this->parsedResponse);
                
                break;
        }
        return $this->parsedResponse;
    }
}
?>
