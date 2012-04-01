<?php
namespace org\opencomb\oauth\adapter ;

use org\jecat\framework\session\Session;

use net\daichen\oauth\Http;

use net\daichen\oauth\OAuthCommon;

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
    
    public function getUser($token,$token_secret)
    {
        $url = $this->arrAdapteeConfigs['api']['userinfo']['uri'];
        $params = $this->arrAdapteeConfigs['api']['userinfo']['params'];
        $sRS =  $this->oauthCommon->SignRequest($url, "GET", $params, $token, $token_secret);
        $aRS = json_decode($sRS,true);
        $aRS['nickname'] = $aRS['name'];
        $aRS['username'] = $aRS['screen_name'];
        $aRS['id'] = $aRS['id'];
        return $aRS;
    }
    
    public function createFriendMulti($o,$uid){
    
        $url = $this->arrAdapteeConfigs['api']['createFriend']['uri'];
        $params = $this->arrAdapteeConfigs['api']['createFriend']['params'];
        
        $params['user_id'] = $uid;
        
        return  $this->oauthCommon->SignRequest($url, "post", $params, $o->token, $o->token_secret,'weibo.com');
    }
    
    public function removeFriendMulti($o,$uid){
    
        $url = $this->arrAdapteeConfigs['api']['removeFriend']['uri'];
        $params = $this->arrAdapteeConfigs['api']['removeFriend']['params'];
        
        $params['user_id'] = $uid;
        
        return  $this->oauthCommon->SignRequest($url, "post", $params, $o->token, $o->token_secret,'weibo.com');
    }
    
    public function pushLastForwardId($o,$aRs){
    
        $aRs = json_decode($aRs,true);
        return  $aRs['id'];
    }
    
    public function createPushForwardMulti($o,$forwardid,$title){
    
        $url = $this->arrAdapteeConfigs['api']['forward']['uri'];
        $params = $this->arrAdapteeConfigs['api']['forward']['params'];
        
        $params['id'] = $forwardid;
        $params['status'] = $title;
        
        return  $this->oauthCommon->SignRequest($url, "post", $params, $o->token, $o->token_secret,'weibo.com');
    }
    
    public function pushLastId($o,$aRs){
    
        $aRs = json_decode($aRs,true);
        return  $aRs['id'];
    }
    
    public function createPushMulti($o,$title){
    
        $url = $this->arrAdapteeConfigs['api']['add']['uri'];
        $params = $this->arrAdapteeConfigs['api']['add']['params'];
        
        $params['status'] = urlencode($title);
        
        return $this->oauthCommon->SignRequest($url, "post", $params, $o->token, $o->token_secret,'weibo.com');
    }
    
    public function createTimeLineMulti($o ,$lastData){
    
    
        $url = $this->arrAdapteeConfigs['api']['timeline']['uri'];
        $params = $this->arrAdapteeConfigs['api']['timeline']['params'];
        
        if(!empty($lastData['cursor_id']))
        {
            $params['since_id'] = $lastData['cursor_id'];
        }
        if(!empty($lastData['max_id']))
        {
            $params['max_id'] = $lastData['max_id'];
        }
        
        $params["access_token"] = $o->token;
        $params["source"] = '3576764673';
        
        return $this->oauthCommon->SignRequest($url, "get", $params, $o->token, $o->token_secret,'weibo.com');
    }
    public function createPullCommentMulti($o ,$astate,$otherParams){
        $url = $this->arrAdapteeConfigs['api']['pullcomment']['uri'];
        $params = $this->arrAdapteeConfigs['api']['pullcomment']['params'];
        $params["id"] = $astate['sid'];
        $params = $otherParams + $params;  // 组合额外配置
        return $this->oauthCommon->SignRequest($url, "get", $params, $o->token, $o->token_secret,'weibo.com');
    }
	public function createPullCommentCount($o,$astate){
		$url = $this->arrAdapteeConfigs['api']['commentcount']['uri'];
		$params = $this->arrAdapteeConfigs['api']['commentcount']['params'];
		$params['ids'] = $astate['sid'] ;
		return $this->oauthCommon->SignRequest($url, 'get' , $params , $o->token, $o->token_secret,'weibo.com');
	}

	public function pushCommentMulti($o ,$astate , $otherParams){
        $url = $this->arrAdapteeConfigs['api']['pushcomment']['uri'];
        $params = $this->arrAdapteeConfigs['api']['pushcomment']['params'];
        $params["access_token"] = $o['token'];
        $params += $otherParams;
        
        return $this->oauthCommon->SignRequest($url, "post", $params, $o['token'], $o['token_secret'],'weibo.com');
    }
    
    public function filterTimeLine($token,$token_secret,$responseData,$lastData)
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
            
//             $text = preg_replace("/#(.*)#/", "<a href='http://s.weibo.com/weibo/$1'>#$1#</a>", $aRs['text']);
//             $text = preg_replace("/@(.*?):/", "<a href='http://weibo.com/n/$1'>$1</a>:", $text);
            $aRsTmp['id'] = $aRs['id'];
            $aRsTmp['title'] = $aRs['text'];
//             $aRsTmp['body'] = $aRs['description'];
            $aRsTmp['time'] = strtotime($aRs['created_at']);
            $aRsTmp['data'] = json_encode($aRs);
            $aRsTmp['client'] = $aRs['source'];
            $aRsTmp['cursor_id'] = $aRs['id'];
//             $aRsTmp['client_url'] = $aRs['source']['href'];
            $aRsTmp['forwardcount'] = 0;
        
            
            $aRsTmp['uid'] = $aRs['user']['id'];
            $aRsTmp['username'] = $aRs['user']['screen_name'];
            $aRsTmp['password'] = md5($aRs['user']['id']);
            $aRsTmp['registerTime'] = time();
            $aRsTmp['nickname'] = $aRs['user']['name'];
            $aRsTmp['avatar'] = $aRs['user']['profile_image_url'];
            $aRsTmp['verified'] = $aRs['user']['verified'];
        
            if(!empty($aRs['thumbnail_pic']))
            {
                $aRsAttachmentTmp['type'] = 'image';
                $aRsAttachmentTmp['thumbnail_pic'] = $aRs['thumbnail_pic'];
                $aRsAttachmentTmp['url'] = $aRs['bmiddle_pic'];
                $aRsAttachmentTmp['link'] = $aRs['bmiddle_pic'];
                $aRsTmp['attachment'][] = $aRsAttachmentTmp;
            }
        
            return $aRsTmp;
        }
}

?>
