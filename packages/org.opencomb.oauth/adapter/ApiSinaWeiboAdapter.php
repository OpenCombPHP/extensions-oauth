<?php
namespace org\opencomb\oauth\adapter ;

use org\jecat\framework\session\Session;

use net\daichen\oauth\Http;

use net\daichen\oauth\OAuthCommon;

if (!session_id()) session_start();

class ApiSinaWeiboAdapter
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
    
    public function createTimeLineMulti($token,$token_secret ,$lastData){
    
    
        $url = $this->arrAdapteeConfigs['api']['timeline']['uri'];
        $params = $this->arrAdapteeConfigs['api']['timeline']['params'];
        
        if(!empty($lastData))
        {
            $params['since_id'] = $lastData['cursor_id'];
        }
        
        $params["access_token"] = $token;
        $params["source"] = '3576764673';
        
        return $this->oauthCommon->SignRequest($url, "get", $params, $token, $token_secret,'weibo.com');
    }
    
    public function filterTimeLine($responseData,$lastData)
    {
        
        $aRs = json_decode ($responseData,true);
        
        foreach ($aRs as $v)
        {
            $aRs = $this->filter($v);
            
            if(!empty($v['retweeted_status']))
            {
                $aRs['source'] = $this->filter($v['retweeted_status']);
            }
            $aRsTrue[] = $aRs;
        }
        
        return $aRsTrue;
    }
    
    private function filter($aRs){
        
        
            $aRsTmp = array();
            $aRsTmp['system'] = '';
            
            $text = preg_replace("/#(.*)#/", "<a href='http://s.weibo.com/weibo/$1'>#$1#</a>", $aRs['text']);
            $text = preg_replace("/@(.*?):/", "<a href='http://weibo.com/n/$1'>$1</a>:", $text);
            $aRsTmp['id'] = $aRs['id'];
            $aRsTmp['title'] = $text;
//             $aRsTmp['body'] = $aRs['description'];
            $aRsTmp['time'] = strtotime($aRs['created_at']);
            $aRsTmp['data'] = json_encode($aRs);
            $aRsTmp['client'] = $aRs['source'];
            $aRsTmp['cursor_id'] = $aRs['id'];
//             $aRsTmp['client_url'] = $aRs['source']['href'];
        
        
            $aRsTmp['username'] = $aRs['user']['id'];
            $aRsTmp['password'] = md5($aRs['user']['id']);
            $aRsTmp['registerTime'] = time();
            $aRsTmp['nickname'] = $aRs['user']['name'];
            $aRsTmp['avatar'] = $aRs['user']['profile_image_url'];
        
            if(!empty($aRs['thumbnail_pic']))
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
