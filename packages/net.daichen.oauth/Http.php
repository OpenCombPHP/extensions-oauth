<?php
namespace net\daichen\oauth ;
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
date_default_timezone_set("PRC");
class Http{
    public $connecttimeout = 30;
    public $timeout = 30;
    
    private $aMultiParams;
    
    public function createMultiParams($url,$params=false,$httpMethod=false,$service)
    {
        $aMultiParam = array(
            'url'=>$url,
            'params'=>$params,
            'httpMethod'=>$httpMethod,
            'service'=>$service,
        );
        
        $this->aMultiParams[] = $aMultiParam;
    }
    
    public function multi_exec(){
        
        // 创建批处理cURL句柄
        $mh = curl_multi_init();
        $curl_array = array();
        for($i = 0; $i < sizeof($this->aMultiParams); $i++){
            $aRs = $this->aMultiParams[$i];
            
            $curl_array[$i] = curl_init($aRs['url']);
            curl_setopt($curl_array[$i], CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl_array[$i], CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl_array[$i], CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($curl_array[$i], CURLOPT_SSL_VERIFYPEER,   FALSE);
            curl_setopt($curl_array[$i], CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($curl_array[$i], CURLOPT_TIMEOUT,  $this->timeout );
            curl_setopt($curl_array[$i], CURLOPT_CONNECTTIMEOUT, $this->connecttimeout);
            $httpMethod = strtoupper($aRs['httpMethod']);
            switch ($httpMethod){
                case "POST":
                    curl_setopt($curl_array[$i], CURLOPT_POST, 1);
                    curl_setopt($curl_array[$i], CURLOPT_POSTFIELDS, $aRs['params']);
                    break;
                case "GET":
                    curl_setopt($curl_array[$i], CURLOPT_HTTPGET, true);
                    break;
            }
            
            curl_setopt($curl_array[$i],CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.3) Gecko/2008092417 Firefox/3.0.3');
            //curl_setopt($curl_array[$i], CURLOPT_URL,$aRs['url']);
            
            curl_multi_add_handle($mh, $curl_array[$i]);
        }        
        
        $running = NULL;
        do {
            usleep(10000);
            curl_multi_exec($mh,$running);
        } while($running > 0);
        
        $res = array();
        for($i = 0; $i < sizeof($this->aMultiParams); $i++)
        {
            $res[$this->aMultiParams[$i]['service']] = curl_multi_getcontent($curl_array[$i]);;
        }
        
        for($i = 0; $i < sizeof($this->aMultiParams); $i++)
        {
            curl_multi_remove_handle($mh, $curl_array[$i]);
        }
        curl_multi_close($mh);
        
        return $res;
    }
    
    public function fetch_page($url,$params=false,$httpMethod=false)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch,   CURLOPT_SSL_VERIFYPEER,   FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_TIMEOUT,  $this->timeout );
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connecttimeout);
        $httpMethod = strtoupper($httpMethod);
        switch ($httpMethod){
            case "POST":
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                break;
           case "GET":
                curl_setopt($ch, CURLOPT_HTTPGET, true); 
                break;
        }

        curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.3) Gecko/2008092417 Firefox/3.0.3');
        curl_setopt($ch, CURLOPT_URL,$url);

        
        $result = curl_exec($ch);
        
        curl_close($ch);
        return $result;
    }
    public function fetch_header_page($url,$header,$params)
    {
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_HTTPHEADER,$header);
        curl_setopt($ch,CURLOPT_HEADER,1);
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch,CURLOPT_POST,1); 
        curl_setopt($ch,CURLOPT_POSTFIELDS, $params);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
    public function StartsWith($Haystack, $Needle){  
        return strpos($Haystack, $Needle) === 0;  
    }  
    public function GetQueryParameters($querystring){
        if($this->StartsWith($querystring, "?")==1){
            $querystring = str_replace("?","",$querystring);
        }
        $result = array();
        if(!empty ($querystring)){
            $arr = explode("&", $querystring);
            foreach($arr as $key=>$value){
                if(strpos($value, "=")>-1){
                    $arr2 = explode("=", $value);
                    $result[$arr2[0]] = $arr2[1];
                }
            }
        }
        return $result;
    }
    
    function utf82Unicode($str=""){
        $unicode = array();
        $values = array();
        $lookingFor = 1;
        for ($i = 0; $i < strlen($str); $i++ ) {
            $thisValue = ord($str[$i]);
            if ($thisValue < 128) {
                $unicode[] = $thisValue;
            } else {
                if ( count( $values ) == 0 ) {
                    $lookingFor = ( $thisValue < 224 ) ? 2 : 3;
                }

               $values[] = $thisValue;
               if ( count( $values ) == $lookingFor ) {
                   $number = ( $lookingFor == 3 ) ?( ( $values[0] % 16 ) * 4096 ) + ( ( $values[1] % 64 ) * 64 ) + ( $values[2] % 64 ):( ( $values[0] % 32 ) * 64 ) + ( $values[1] % 64 );
                   $unicode[] = $number;
                   $values = array();
                   $lookingFor = 1;
              }
          }
       }
       $return = '';
       foreach($unicode as $val){
          $return .= '&#'.$val.';';
       }
       return $return;
    }
}
