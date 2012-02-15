<?php
namespace org\opencomb\oauth\adapter ;

use org\jecat\framework\session\Session;

use net\daichen\oauth\Http;

use net\daichen\oauth\OAuthCommon;

if (!session_id()) session_start();

class Api163Adapter
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
    
    public function TimeLine($token,$token_secret ,$time="",$page=1){
    
    
        $url = $this->arrAdapteeConfigs['api']['timeline']['uri'];
        $params = $this->arrAdapteeConfigs['api']['timeline']['params'];
        //$params['max_id'] = "-4235698966700794925:1329280145147";
        
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
                        'user'=>array('id'=>$v['in_reply_to_user_id'],'name'=>$v['in_reply_to_user_name'])       ,
                        'text'=>$v['in_reply_to_status_text'],
                ));
            }
            $aRsTrue[] = $aRs;
        }
    
        return $aRsTrue;
    }
    
    private function filter($aRs){
    
    
        $aRsTmp = array();
        $aRsTmp['system'] = '163.com';
    
    
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
        $aRsTmp['nickname'] = $aRs['user']['name'];
        $aRsTmp['avatar'] = $aRs['user']['profile_image_url'];
    
        if($aRs['thumbnail_pic'])
        {
            $aRsAttachmentTmp['type'] = 'image';
            $aRsAttachmentTmp['url'] = $aRs['thumbnail_pic'];
            $aRsAttachmentTmp['link'] = $aRs['bmiddle_pic'];
            $aRsTmp['attachment'][] = $aRsAttachmentTmp;
        }
    
        return $aRsTmp;
    }
}

?>
