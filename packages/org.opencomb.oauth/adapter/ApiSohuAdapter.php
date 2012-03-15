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
    
    public function createFriendMulti($o,$uid){
    
        $url = $this->arrAdapteeConfigs['api']['createFriend']['uri'];
        $params = $this->arrAdapteeConfigs['api']['createFriend']['params'];
        $url = preg_replace("/\{id\}/",$uid,$url );
        return  $this->oauthCommon->SignRequest($url, "post", $params, $o->token, $o->token_secret,'sohu.com');
    }
    
    public function removeFriendMulti($o,$uid){
    
        $url = $this->arrAdapteeConfigs['api']['removeFriend']['uri'];
        $params = $this->arrAdapteeConfigs['api']['removeFriend']['params'];
        $url = preg_replace("/\{id\}/",$uid,$url );
        return  $this->oauthCommon->SignRequest($url, "post", $params, $o->token, $o->token_secret,'sohu.com');
    }
    
    public function pushLastForwardId($o,$aRs){
    
        $aRs = json_decode($aRs,true);
        return  $aRs['id'];
    }
    
    public function createPushForwardMulti($o,$forwardid,$title){
    
        $url = $this->arrAdapteeConfigs['api']['forward']['uri'];
        $params = $this->arrAdapteeConfigs['api']['forward']['params'];
        
        $url = preg_replace("/\{id\}/",$forwardid,$url );
        $params['status'] = $title;
        
        return  $this->oauthCommon->SignRequest($url, "post", $params, $o->token, $o->token_secret,'sohu.com');
    }
    
    public function pushLastId($o,$aRs){
    
        $aRs = json_decode($aRs,true);
        return  $aRs['id'];
    }
    
    public function createPushMulti($o,$title){
    
        $url = $this->arrAdapteeConfigs['api']['add']['uri'];
        $params = $this->arrAdapteeConfigs['api']['add']['params'];
        
        $params['status'] = urlencode($title);
        
        return  $this->oauthCommon->SignRequest($url, "post", $params, $o->token, $o->token_secret,'sohu.com');
    }
    
    public function createTimeLineMulti($o ,$lastData){
    
        $url = $this->arrAdapteeConfigs['api']['timeline']['uri'];
        $params = $this->arrAdapteeConfigs['api']['timeline']['params'];
        
        if(!empty($lastData))
        {
            $params['since_id'] = $lastData['cursor_id'];
        }
    
        return  $this->oauthCommon->SignRequest($url, "get", $params, $o->token, $o->token_secret,'sohu.com');
    }
    
    public function filterTimeLine($token,$token_secret,$responseData,$lastData)
    {
        $aRs = json_decode ($responseData,true);
        
        foreach ($aRs as $v)
        {
            if(empty($v['text']))
            {
                return ;
            }
            $aRs = $this->filter($v);
            
            if(!empty($v['in_reply_to_status_text']))
            {
                    $url = $this->arrAdapteeConfigs['api']['show']['uri'];
                    $params = $this->arrAdapteeConfigs['api']['show']['params'];
                    $url = preg_replace("/\{id\}/",$v['in_reply_to_status_id'],$url );
                    $aSource =  $this->oauthCommon->SignRequest($url, "get", $params, $token, $token_secret);
                    $aRs['source'] = $this->filter(json_decode($aSource,true));
            }
            $aRsTrue[] = $aRs;
        }
        
        return $aRsTrue;
    }
    
    private function filter($aRs){
        
        
            $aRsTmp = array();
            $aRsTmp['system'] = '';
        
            
//             $text = preg_replace("/#(.*)#/", "<a href='http://s.weibo.com/weibo/$1'>#$1#</a>", $aRs['text']);
//             $text = preg_replace("/@(.*?):/", "<a href='http://weibo.com/n/$1'>$1</a>:", $text);
            $aRsTmp['title'] = $aRs['text'];
            $aRsTmp['id'] = $aRs['id'];
//             $aRsTmp['body'] = $aRs['description'];
            $aRsTmp['time'] = strtotime($aRs['created_at']);
            $aRsTmp['data'] = json_encode($aRs);
            $aRsTmp['client'] = $aRs['source'];
            $aRsTmp['cursor_id'] = $aRs['id'];
//             $aRsTmp['client_url'] = $aRs['source']['href'];
        
        
            $aRsTmp['username'] = $aRs['user']['id'];
            $aRsTmp['password'] = md5($aRs['user']['id']);
            $aRsTmp['registerTime'] = time();
            $aRsTmp['nickname'] = $aRs['user']['screen_name'];
            $aRsTmp['avatar'] = $aRs['user']['profile_image_url'];
        
            if($aRs['small_pic'])
            {
                $aRsAttachmentTmp['type'] = 'image';
                $aRsAttachmentTmp['thumbnail_pic'] = $aRs['small_pic'];
                $aRsAttachmentTmp['url'] = $aRs['middle_pic'];
                $aRsTmp['attachment'][] = $aRsAttachmentTmp;
            }
        
            return $aRsTmp;
        }
}

?>
