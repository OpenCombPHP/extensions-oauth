<?php
namespace org\opencomb\oauth\adapter ;

use org\jecat\framework\session\Session;

use net\daichen\oauth\Http;

use net\daichen\oauth\OAuthCommon;

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
    
    public function getUser($token,$token_secret)
    {
        $url = $this->arrAdapteeConfigs['api']['show']['uri'];
        $params = $this->arrAdapteeConfigs['api']['show']['params'];
        $sRS =  $this->oauthCommon->SignRequest($url, "GET", $params, $token, $token_secret);
        $aRS = json_decode($sRS,true);
        $aRS['nickname'] = $aRS['name'];
        $aRS['username'] = $aRS['screen_name'];
        return $aRS;
    }
    
	public function getUserByNickName($o,$sNickName)
    {
    	$url = $this->arrAdapteeConfigs['api']['userotherinfo']['uri'];
    	$params = $this->arrAdapteeConfigs['api']['userotherinfo']['params'];
    	$params['name'] = $sNickName;
    	return $this->oauthCommon->SignRequest($url, "get", $params, $o->token, $o->token_secret,'163.com');
    }
    
    public function createFriendMulti($o,$uid){
    
        $url = $this->arrAdapteeConfigs['api']['createFriend']['uri'];
        $params = $this->arrAdapteeConfigs['api']['createFriend']['params'];
        
        $params['user_id'] = $uid;
        
        return  $this->oauthCommon->SignRequest($url, "post", $params, $o->token, $o->token_secret,'163.com');
    }
    
    public function removeFriendMulti($o,$uid){
    
        $url = $this->arrAdapteeConfigs['api']['removeFriend']['uri'];
        $params = $this->arrAdapteeConfigs['api']['removeFriend']['params'];
        
        $params['user_id'] = $uid;
        
        return  $this->oauthCommon->SignRequest($url, "post", $params, $o->token, $o->token_secret,'163.com');
    }
    
    public function pushLastForwardId($o,$aRs){
    
        $aRs = json_decode($aRs,true);
        return  $aRs['retweeted_status']['id'];
    }
    
    public function createPushForwardMulti($o,$forwardid,$title){
    
        $url = $this->arrAdapteeConfigs['api']['forward']['uri'];
        $params = $this->arrAdapteeConfigs['api']['forward']['params'];
        
        $url = preg_replace("/\{id\}/",$forwardid,$url );
        $params['status'] = $title;
        
        return  $this->oauthCommon->SignRequest($url, "post", $params, $o->token, $o->token_secret,'163.com');
    }
    
    public function pushLastId($o,$aRs){
    
        $aRs = json_decode($aRs,true);
        return  $aRs['id'];
    }
    
    public function createPushMulti($o,$title){
    
        $url = $this->arrAdapteeConfigs['api']['add']['uri'];
        $params = $this->arrAdapteeConfigs['api']['add']['params'];
        
        $params['status'] = $title;
        
        return  $this->oauthCommon->SignRequest($url, "post", $params, $o->token, $o->token_secret,'163.com');
    }
    
    public function createTimeLineMulti($o ,$lastData){
    
        $url = $this->arrAdapteeConfigs['api']['timeline']['uri'];
        $params = $this->arrAdapteeConfigs['api']['timeline']['params'];
        
        
        if(!empty($lastData['cursor_id']))
        {
            $params['max_id'] = $lastData['cursor_id'];
        }
        
        if(!empty($lastData['max_id']))
        {
            $params['max_id'] = $lastData['max_id'];
        }
        
        return  $this->oauthCommon->SignRequest($url, "get", $params, $o->token, $o->token_secret,'163.com');
    }
    
    public function createPullCommentMulti($o , $astate, $otherParams){
        $url = $this->arrAdapteeConfigs['api']['pullcomment']['uri'];
        $url = preg_replace("/\{id\}/",$astate['sid'],$url );
        $params = $this->arrAdapteeConfigs['api']['pullcomment']['params'];
        $params = $otherParams + $params;  // 组合额外配置
//         var_dump($params);
//         var_dump($url);
        return  $this->oauthCommon->SignRequest($url, "get", $params, $o->token, $o->token_secret,'163.com');
    }
	public function createPullCommentCount($o,$astate){
		$url = $this->arrAdapteeConfigs['api']['show']['uri'];
		$url = preg_replace("/\{id\}/",$astate['sid'],$url );
		return $this->oauthCommon->SignRequest($url, 'get' , array() , $o->token, $o->token_secret,'163.com');
	}

	public function pushCommentMulti($o , $astate ,$arrOtherParams){
    	$url = $this->arrAdapteeConfigs['api']['pushcomment']['uri'];
    	$params = $this->arrAdapteeConfigs['api']['pushcomment']['params'];
    	$params += $arrOtherParams;
    	
    	return  $this->oauthCommon->SignRequest($url, "POST", $params, $o['token'], $o['token_secret'],'163.com');
    }
    public function filterTimeLine($token,$token_secret,$responseData,$lastData)
    {
    
        $aRs = json_decode ($responseData,true);
    
        foreach ($aRs as $v)
        {
            if(empty($v['text']) || empty($v['user']['id']))
            {
                return ;
            }
            /**
             * 排除当前条
             */
            if($lastData['cursor_id'] != $v['cursor_id'])
            {
                $aRs = $this->filter($v);
                
                if(!empty($v['in_reply_to_status_text']))
                {
                    $url = $this->arrAdapteeConfigs['api']['showState']['uri'];
                    $params = $this->arrAdapteeConfigs['api']['showState']['params'];
                    $url = preg_replace("/\{id\}/",$v['in_reply_to_status_id'],$url );
                    $aSource =  $this->oauthCommon->SignRequest($url, "get", $params, $token, $token_secret);
                    $aRs['source'] = $this->filter(json_decode($aSource,true));
                }
                $aRsTrue[] = $aRs;
            }
        }
    
        return $aRsTrue;
    }
    
    public function filterUser($aRs){
    	$aRs = json_decode($aRs,true);
    	$aRsTmp['uid'] = $aRs['id'];
        $aRsTmp['username'] = $aRs['screen_name'];
        $aRsTmp['password'] = md5($aRs['id']);
        $aRsTmp['registerTime'] = time();
        $aRsTmp['nickname'] = $aRs['name'];
        $aRsTmp['avatar'] = $aRs['profile_image_url'];
        $aRsTmp['verified'] = $aRs['verified'];
//     	    	var_dump($aRsTmp);
    	return $aRsTmp;
    }
    
    private function filter($aRs){
    
    
        $aRsTmp = array();
        $aRsTmp['system'] = '';
    
        $aRsTmp['title'] = $aRs['text'];
        //             $aRsTmp['body'] = $aRs['description'];
        $aRsTmp['time'] = strtotime($aRs['created_at']);
        $aRsTmp['id'] = $aRs['id'];
        $aRsTmp['data'] = json_encode($aRs);
        $aRsTmp['client'] = $aRs['source'];
        $aRsTmp['cursor_id'] = $aRs['cursor_id'];
        $aRsTmp['forwardcount'] = $aRs['retweet_count'];
        //             $aRsTmp['client_url'] = $aRs['source']['href'];
    
        
        $aRsTmp['uid'] = $aRs['user']['id'];
        $aRsTmp['username'] = $aRs['user']['screen_name'];
        $aRsTmp['password'] = md5($aRs['user']['id']);
        $aRsTmp['registerTime'] = time();
        $aRsTmp['nickname'] = $aRs['user']['name'];
        $aRsTmp['avatar'] = $aRs['user']['profile_image_url'];
        $aRsTmp['verified'] = $aRs['user']['verified'];
    
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
