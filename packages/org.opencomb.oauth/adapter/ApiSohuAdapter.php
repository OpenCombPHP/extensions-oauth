<?php
namespace org\opencomb\oauth\adapter ;

use org\jecat\framework\session\Session;

use net\daichen\oauth\Http;

use net\daichen\oauth\OAuthCommon;

if (!session_id()) session_start();

class ApiSohuAdapter
{
    public $oauthCommon;
    public $arrAdapteeConfigs = array() ;
    
    public function __construct($aSiteConfig,$aKey) {
        
        if(empty($aSiteConfig) || empty($aKey))
        {
            throw new Exception("尚不能绑定此网站");
        }else{
            $this->arrAdapteeConfigs = $aSiteConfig;
        }
        
        $this->oauthCommon = new OAuthCommon($aKey["appkey"],  $aKey["appsecret"]);
    }
    
    public function TimeLine($token,$token_secret ,$time=""){
    
    
        $url = $this->arrAdapteeConfigs['api']['timeline']['uri'];
        $params = $this->arrAdapteeConfigs['api']['timeline']['params'];
        
        if($time)
        {
            //$params["pagetime"] = $time;
        }
    
        
        $responseData = $this->oauthCommon->SignRequest($url, "get", $params, $token, $token_secret);
        
        $aRs = json_decode ($responseData,true);
        
        foreach ($aRs as $v)
        {
            $aRs = $this->filter($v);
            
            if(!empty($v['in_reply_to_status_text']))
            {
                $aRs['source'] = $this->filter(array(
                    'user'=>array('id'=>$v['in_reply_to_user_id'],'screen_name'=>$v['in_reply_to_screen_name'])       , 
                    'text'=>$v['in_reply_to_status_text'],
                ));
            }
            $aRsTrue[] = $aRs;
        }
        
        return $aRsTrue;
    }
    
    private function filter($aRs){
        
        
            $aRsTmp = array();
            $aRsTmp['system'] = 'sohu.com';
        
            
            $text = preg_replace("/#(.*)#/", "<a href='http://s.weibo.com/weibo/$1'>#$1#</a>", $aRs['text']);
            $text = preg_replace("/@(.*?):/", "<a href='http://weibo.com/n/$1'>$1</a>:", $text);
            $aRsTmp['subject'] = $text;
//             $aRsTmp['summary'] = $aRs['description'];
            $aRsTmp['time'] = strtotime($aRs['created_at']);
            $aRsTmp['data'] = json_encode($aRs);
            $aRsTmp['client'] = $aRs['source'];
//             $aRsTmp['client_url'] = $aRs['source']['href'];
        
        
            $aRsTmp['username'] = $aRs['user']['id'];
            $aRsTmp['password'] = md5($aRs['user']['id']);
            $aRsTmp['registerTime'] = time();
            $aRsTmp['nickname'] = $aRs['user']['screen_name'];
            $aRsTmp['avatar'] = $aRs['user']['profile_image_url'];
        
            if($aRs['small_pic'])
            {
                $aRsAttachmentTmp['type'] = 'image';
                $aRsAttachmentTmp['url'] = $aRs['small_pic'];
                $aRsAttachmentTmp['link'] = $aRs['middle_pic'];
                $aRsTmp['attachment'][] = $aRsAttachmentTmp;
            }
        
            return $aRsTmp;
        }
}

?>
