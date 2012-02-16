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
    public $multiParams ;
    public function fetch_page($url,$params=false,$httpMethod=false)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,   FALSE);
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
    
    public function createChPage($url,$params=false,$httpMethod=false)
    {
        $this->multiParams[] = array('url'=>$url,'params'=>$params,'httpMethod'=>$httpMethod);
        return $this->multiParams;
    }
    
    public function execChpage()
    {
        // 创建一对cURL资源
        $ch1 = curl_init();
        $ch2 = curl_init();
        
        // 设置URL和相应的选项
        curl_setopt($ch1, CURLOPT_URL, "http://www.example.com/");
        curl_setopt($ch1, CURLOPT_HEADER, 0);
        curl_setopt($ch2, CURLOPT_URL, "http://www.php.net/");
        curl_setopt($ch2, CURLOPT_HEADER, 0);
        
        
        
        
        
        
        
        
        
        
        
        
        // 创建批处理cURL句柄
        $mh = curl_multi_init();
        
        // 增加2个句柄
        curl_multi_add_handle($mh,$ch1);
        curl_multi_add_handle($mh,$ch2);
        
        $running=null;
        // 执行批处理句柄
        do {
            usleep(10000);
            curl_multi_exec($mh,$running);
        } while ($running > 0);
        
        // 关闭全部句柄
        curl_multi_remove_handle($mh, $ch1);
        curl_multi_remove_handle($mh, $ch2);
        curl_multi_close($mh);
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



?>
