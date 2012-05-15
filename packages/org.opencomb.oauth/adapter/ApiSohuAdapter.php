<?php
namespace org\opencomb\oauth\adapter ;

use net\daichen\oauth\OAuthCommon;

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
    
    public function getForwardNumber($token,$token_secret,$id)
    {
        return 0;
    }
    
    public function getUser($token,$token_secret)
    {
        $url = $this->arrAdapteeConfigs['api']['userinfo']['uri'];
        $params = $this->arrAdapteeConfigs['api']['userinfo']['params'];
        $sRS =  $this->oauthCommon->SignRequest($url, "GET", $params, $token, $token_secret);
        $aRS = json_decode($sRS,true);
        $aRS['nickname'] = $aRS['screen_name'];
        $aRS['username'] = $aRS['screen_name'];
        $aRS['id'] = $aRS['id'];
        return $aRS;
    }
    
    public function getUserByNickName($o,$sNickName)
    {
    	$url = $this->arrAdapteeConfigs['api']['userotherinfo']['uri'];
    	$params = $this->arrAdapteeConfigs['api']['userotherinfo']['params'];
    	$url = preg_replace("/\{nickname\}/",urlencode($sNickName),$url );
    	return $this->oauthCommon->SignRequest($url, "get", $params, $o->token, $o->token_secret,'sohu.com');
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
    
    public function createPushMulti($o,$title,$picFile){
    
        $url = $this->arrAdapteeConfigs['api']['add']['uri'];
        $params = $this->arrAdapteeConfigs['api']['add']['params'];
        
        $params['status'] = urlencode($title);
        
        return  $this->oauthCommon->SignRequest($url, "post", $params, $o->token, $o->token_secret,'sohu.com');
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
        
        return  $this->oauthCommon->SignRequest($url, "get", $params, $o->token, $o->token_secret,'sohu.com');
    }
    public function createPullCommentMulti($o ,$astate , $otherParams){
        $url = $this->arrAdapteeConfigs['api']['pullcomment']['uri'];
        $url = preg_replace("/\{id\}/",$astate['sid'],$url );
        $params = $this->arrAdapteeConfigs['api']['pullcomment']['params'];
        $params = $otherParams + $params;  // 组合额外配置
        
        return  $this->oauthCommon->SignRequest($url, "get", $params, $o->token, $o->token_secret,'sohu.com');
    }
    public function createPullCommentCount($o ,$astate){
        $url = $this->arrAdapteeConfigs['api']['commentcount']['uri'];
        $url = preg_replace("/\{id\}/",$astate['sid'],$url );
        $params = $this->arrAdapteeConfigs['api']['commentcount']['params'];
        
        return  $this->oauthCommon->SignRequest($url, "GET", $params, $o->token, $o->token_secret,'sohu.com');
    }

	public function pushCommentMulti($o ,$astate , $otherParams){
        $url = $this->arrAdapteeConfigs['api']['pushcomment']['uri'];
        $params = $this->arrAdapteeConfigs['api']['pushcomment']['params'];
        $params = $params + $otherParams;  // 组合额外配置
        
        return  $this->oauthCommon->SignRequest($url, "POST", $params, $o['token'], $o['token_secret'],'sohu.com');
    }
    
    public function filterCommentCount($aRs){
    	$aRs = json_decode($aRs,true);
    	$aRsTemp['commentcount'] = $aRs['comments_count'];
    	$aRsTemp['retweetcount'] = $aRs['transmit_count'];
    	return $aRsTemp;
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
    
    public function filterUser($aRs){
    	$aRs = json_decode($aRs,true);
    	$aRsTmp['uid'] = $aRs['id'];
            $aRsTmp['username'] = $aRs['screen_name'];
            $aRsTmp['password'] = md5($aRs['id']);
            $aRsTmp['registerTime'] = time();
            $aRsTmp['nickname'] = $aRs['screen_name'];
            $aRsTmp['avatar'] = $aRs['profile_image_url'];
            $aRsTmp['verified'] = $aRs['verified'];
            $aRsTmp['forwardcount'] = 0;
    	//     	    	var_dump($aRsTmp);
    	return $aRsTmp;
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
        
            
            $aRsTmp['uid'] = $aRs['user']['id'];
            $aRsTmp['username'] = $aRs['user']['screen_name'];
            $aRsTmp['password'] = md5($aRs['user']['id']);
            $aRsTmp['registerTime'] = time();
            $aRsTmp['nickname'] = $aRs['user']['screen_name'];
            $aRsTmp['avatar'] = $aRs['user']['profile_image_url'];
            $aRsTmp['verified'] = $aRs['user']['verified'];
            $aRsTmp['forwardcount'] = 0;
            $aRsTmp['commentcount'] = 0;
            
            if($aRs['small_pic'])
            {
                $aRsAttachmentTmp['type'] = 'image';
                $aRsAttachmentTmp['thumbnail_pic'] = $aRs['small_pic'];
                $aRsAttachmentTmp['url'] = $aRs['middle_pic'];
                $aRsTmp['attachment'][] = $aRsAttachmentTmp;
            }
        
            return $aRsTmp;
        }

    public function search($o, $searchText){
        $url = $this->arrAdapteeConfigs['api']['search']['uri'];
        $params = $this->arrAdapteeConfigs['api']['search']['params'];
        
        $params["q"] = urlencode($searchText);

        return $this->oauthCommon->SignRequest($url, "get", $params, $o->token, $o->token_secret,'sohu.com');
    }
    public function filterSearchTimeLine($token, $token_secret, $responseData)
    {
        $aRs = json_decode ($responseData,true);
        foreach ($aRs as $aRs2)
        {
            foreach ($aRs2 as $v) {
                if(empty($v['text']))
                {
                    return ;
                }
                $aRs2 = $this->filterforSearch($v);
                
                if(!empty($v['in_reply_to_status_text']))
                {
                        $url = $this->arrAdapteeConfigs['api']['show']['uri'];
                        $params = $this->arrAdapteeConfigs['api']['show']['params'];
                        $url = preg_replace("/\{id\}/",$v['in_reply_to_status_id'],$url );
                        $aSource =  $this->oauthCommon->SignRequest($url, "get", $params, $token, $token_secret);
                        $aRs['source'] = $this->filterforSearch(json_decode($aSource,true));
                }
                $aRsTrue[] = $aRs2;
            }
        }
        return $aRsTrue;
    }
    private function filterforSearch($aRs){
        $aRsTmp = $this->filter($aRs);
        $aRsTmp['service'] = $this->arrAdapteeConfigs['url'];
        return $aRsTmp;
    }
}
