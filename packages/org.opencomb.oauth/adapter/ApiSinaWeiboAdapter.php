<?php
namespace org\opencomb\oauth\adapter ;

use net\daichen\oauth\OAuthCommon;

class ApiSinaWeiboAdapter
{
    public $oauthCommon;
    public $arrAdapteeConfigs = array() ;
    public $appkey = array();
    
    public function __construct($aSiteConfig,$aKey) {
        
        if(empty($aSiteConfig) || empty($aKey))
        {
            throw new Exception("尚不能绑定此网站");
        }else{
            $this->arrAdapteeConfigs = $aSiteConfig;
        }
        
        $this->appkey = $aKey;
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
    
    public function getForwardNumber($token,$token_secret,$id)
    {
        
        /**
         * 交换oauth2
         * @var unknown_type
         */
        $url2 = $this->arrAdapteeConfigs['api']['get_oauth2_token']['uri'];
        $params2 = $this->arrAdapteeConfigs['api']['get_oauth2_token']['params'];
        $trs = $this->oauthCommon->SignRequest($url2, "post", $params2, $token, $token_secret);
        $aTrs = json_decode($trs,true);
        $oauth2_access_token = $aTrs['access_token'];
        
        /**
         * 使用oauth2
         * @var unknown_type
         */
        $url = $this->arrAdapteeConfigs['api']['show']['uri'];
        $params = $this->arrAdapteeConfigs['api']['show']['params'];
        $params['id'] = $id;
        $params["access_token"] = $oauth2_access_token;
        
        $this->oauthCommon->setOAuthVersion("2.0");
        $rs = $this->oauthCommon->SignRequest($url, "GET", $params, $oauth2_access_token, "");
        
        $aRS = json_decode($rs,true);
        return $aRS['reposts_count'];
    }
    
    public function getUserByNickName($o,$sNickName)
    {
    	$url = $this->arrAdapteeConfigs['api']['userotherinfo']['uri'];
    	$params = $this->arrAdapteeConfigs['api']['userotherinfo']['params'];
    	$params['screen_name'] =$sNickName;
    	return $this->oauthCommon->SignRequest($url, "GET", $params, $o->token,  $o->token_secret,'weibo.com');
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
    
    public function createPushMulti($o,$title,$picFile){
    
        if(empty($picFile))
        {
            $url = $this->arrAdapteeConfigs['api']['add']['uri'];
            $params = $this->arrAdapteeConfigs['api']['add']['params'];
            $params['status'] = urlencode($title);
            return $this->oauthCommon->SignRequest($url, "post", $params, $o->token, $o->token_secret,'weibo.com');
            
        }else {
            /**
             * 交换oauth2
             * @var unknown_type
             */
            $url2 = $this->arrAdapteeConfigs['api']['get_oauth2_token']['uri'];
            $params2 = $this->arrAdapteeConfigs['api']['get_oauth2_token']['params'];
            $trs = $this->oauthCommon->SignRequest($url2, "post", $params2, $o->token, $o->token_secret);
            $aTrs = json_decode($trs,true);
            $oauth2_access_token = $aTrs['access_token'];
            
            /**
             * 使用oauth2
             * @var unknown_type
             */
            $url = $this->arrAdapteeConfigs['api']['add_file_url']['uri'];
            $params = $this->arrAdapteeConfigs['api']['add_file_url']['params'];
            $localPath = dirname(dirname(dirname(dirname(dirname(dirname(__DIR__))))));
            $params['pic_url'] = "http://".$_SERVER['HTTP_HOST']."/extensions/userstate/upload/pic/".$picFile;
            //$params['url'] = 'http://img.baidu.com/img/image/ilogob.gif';
            $params['status'] = urlencode($title);
            $params["access_token"] = $oauth2_access_token;
            
            $this->oauthCommon->setOAuthVersion("2.0");
            $rs = $this->oauthCommon->SignRequest($url, "post", $params, $oauth2_access_token, "");
            
            return $rs;
            
        }
        
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
        if($aRs['error_code'] == "403")
        {
            return array();
        }
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
    
    public function filterUser($aRs){
    	$aRs=  json_decode($aRs,true);
    	$aRsTmp['uid'] = $aRs['id'];
    	$aRsTmp['username'] = $aRs['screen_name'];
    	$aRsTmp['password'] = md5($aRs['id']);
    	$aRsTmp['registerTime'] = time();
    	$aRsTmp['nickname'] = $aRs['name'];
    	$aRsTmp['avatar'] = $aRs['profile_image_url'];
    	$aRsTmp['verified'] = $aRs['verified'];
    	return $aRsTmp;
    }
    
    public function filterCommentCount($aRs){
    	$aRs = json_decode($aRs,true);
    	foreach($aRs as  $key=>$value){
    		$aRsTemp[$key]['commentcount'] = $aRs[$key]['comments'];
    		$aRsTemp[$key]['retweetcount'] = $aRs[$key]['rt'];
    	}
    	if(isset($aRsTemp[0])){
    		return $aRsTemp[0];
    	}else{
    		return 0;
    	}
    }
    
    private function filter($aRs){
            $aRsTmp = array();
            $aRsTmp['system'] = '';
            
//             $text = preg_replace("/#(.*)#/", "<a href='http://s.weibo.com/weibo/$1'>#$1#</a>", $aRs['text']);
//             $text = preg_replace("/@(.*?):/", "<a href='http://weibo.com/n/$1'>$1</a>:", $text);
            $aRsTmp['id'] = (string)$aRs['id'];
            $aRsTmp['title'] = $aRs['text'];
//             $aRsTmp['body'] = $aRs['description'];
            $aRsTmp['time'] = strtotime($aRs['created_at']);
            $aRsTmp['data'] = json_encode($aRs);
            $aRsTmp['client'] = $aRs['source'];
            $aRsTmp['cursor_id'] = (string)$aRs['id'];
//             $aRsTmp['client_url'] = $aRs['source']['href'];
            $aRsTmp['forwardcount'] = 0;
			
			$aCommentCount = new \com\wonei\woneibridge\comment\CommentCount(array('service'=>'weibo.com' ,'stid'=>'pull|weibo.com|'.$aRsTmp['id'].'|$aRsTmp["uid"]'));
			$nCommentCount = (int)$aCommentCount->getCommentCount();
			$aRsTmp['commentcount'] = $nCommentCount;
            
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

    public function search($o, $searchText){
        return ;
    }
    public function filterSearchTimeLine($token, $token_secret, $responseData)
    {
        return ;
    }
    private function filterforSearch($aRs){
        $aRsTmp = $this->filter($aRs);
        $aRsTmp['service'] = $this->arrAdapteeConfigs['url'];
        return $aRsTmp;
    }
}
