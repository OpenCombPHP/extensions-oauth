<?php
namespace org\opencomb\oauth\adapter ;

use net\daichen\oauth\OAuthCommon;

class ApiTencentAdapter
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
        $aRS['nickname'] = $aRS['data']['nick'];
        $aRS['username'] = $aRS['data']['name'];
        $aRS['id'] = $aRS['data']['name'];
        return $aRS;
    }
    
    public function getUserByNickName($o,$sNickName)
    {
    	$url = $this->arrAdapteeConfigs['api']['usersearch']['uri'];
    	$params = $this->arrAdapteeConfigs['api']['usersearch']['params'];
    	$params['keyword'] = $sNickName;
    	$this->oauthCommon->SignRequest($url, "get", $params, $o->token, $o->token_secret,'t.qq.com');
    	
    	$OAuthCommon = new \net\daichen\oauth\OAuthCommon("",  "");
    	$aRsT = $OAuthCommon -> multi_exec();
    	$aRsT = json_decode($aRsT['t.qq.com'],true);
    	foreach($aRsT['data']['info'] as $key=>$info){
    		if($info['nick'] === $sNickName){
    			$aRsT = $info;
    			break;
    		}
    	}
    	$url = $this->arrAdapteeConfigs['api']['userotherinfo']['uri'];
    	$params = $this->arrAdapteeConfigs['api']['userotherinfo']['params'];
    	$params['name'] = $aRsT['name'];
    	return $this->oauthCommon->SignRequest($url, "get", $params, $o->token, $o->token_secret,'t.qq.com');
    }
    
    public function createFriendMulti($o,$uid){
    
        $url = $this->arrAdapteeConfigs['api']['createFriend']['uri'];
        $params = $this->arrAdapteeConfigs['api']['createFriend']['params'];
        
        $params['name'] = $uid;
        
        return  $this->oauthCommon->SignRequest($url, "post", $params, $o->token, $o->token_secret,'t.qq.com');
    }
    
    public function removeFriendMulti($o,$uid){
    
        $url = $this->arrAdapteeConfigs['api']['removeFriend']['uri'];
        $params = $this->arrAdapteeConfigs['api']['removeFriend']['params'];
        
        $params['name'] = $uid;
        
        return  $this->oauthCommon->SignRequest($url, "post", $params, $o->token, $o->token_secret,'t.qq.com');
    }
    
    public function pushLastForwardId($o,$aRs){
    
        $aRs = json_decode($aRs,true);
        return  $aRs['data']['id'];
    }
    
    public function createPushForwardMulti($o,$forwardid,$title){
    
        $url = $this->arrAdapteeConfigs['api']['forward']['uri'];
        $params = $this->arrAdapteeConfigs['api']['forward']['params'];
        
        $params['content'] = $title;
        $params['reid'] = $forwardid;
        
        return  $this->oauthCommon->SignRequest($url, "post", $params, $o->token, $o->token_secret,'t.qq.com');
    }
    
    public function pushLastId($o,$aRs){
    
        $aRs = json_decode($aRs,true);
        return  $aRs['data']['id'];
    }
    
    public function createPushMulti($o,$title){
    
        $url = $this->arrAdapteeConfigs['api']['add']['uri'];
        $params = $this->arrAdapteeConfigs['api']['add']['params'];
        
        $params['content'] = $title;
        $params['clientip'] = $_SERVER['REMOTE_ADDR'];
        
        return $this->oauthCommon->SignRequest($url, "post", $params, $o->token, $o->token_secret,'t.qq.com');
    }
    
    public function createTimeLineMulti($o,$lastData){
    
        $url = $this->arrAdapteeConfigs['api']['timeline']['uri'];
        $params = $this->arrAdapteeConfigs['api']['timeline']['params'];
        
        if(!empty($lastData['time']))
        {
            $params['pageflag'] = "2";
            $params['pagetime'] = $lastData['time'];
        }
        
        return $this->oauthCommon->SignRequest($url, "get", $params, $o->token, $o->token_secret,'t.qq.com');
    }
    
    public function createPullCommentMulti($o ,$astate,$otherParams){
        $url = $this->arrAdapteeConfigs['api']['pullcomment']['uri'];
        $params = $this->arrAdapteeConfigs['api']['pullcomment']['params'];
        $params['rootid']= $astate['sid'];
        $params = $otherParams + $params;  // 组合额外配置
        return $this->oauthCommon->SignRequest($url, "get", $params, $o->token, $o->token_secret,'t.qq.com');
    }
	public function createPullCommentCount($o,$astate){
		$url = $this->arrAdapteeConfigs['api']['commentcount']['uri'];
		$params = $this->arrAdapteeConfigs['api']['commentcount']['params'];
		$params['id'] = $astate['sid'] ;
		return $this->oauthCommon->SignRequest($url, 'get' , $params , $o->token, $o->token_secret,'t.qq.com');
	}
    
    public function pushCommentMulti($o , $astate ,$arrOtherParams){
    	$url = $this->arrAdapteeConfigs['api']['pushcomment']['uri'];
    	$params = $this->arrAdapteeConfigs['api']['pushcomment']['params'];
    	$params += $arrOtherParams;
    	return  $this->oauthCommon->SignRequest($url, "POST", $params, $o['token'], $o['token_secret'],'t.qq.com');
    }
    
    public function filterCommentCount($aRs){
    	$aRs = json_decode($aRs,true);
    	$aRs = $aRs['data'];
		$aRsTemp['commentcount'] = $aRs['mcount'];
		$aRsTemp['retweetcount'] = $aRs['count'];
    	return $aRsTemp;
    }
    
    public function filterTimeLine($token,$token_secret,$responseData,$lastData)
    {
        $responseData = preg_replace("||",'',$responseData );
        $aRs = json_decode ($responseData,true);
        $aUser = $aRs['data']['user'];
        
        
        foreach ($aRs['data']['info'] as $v)
        {
            $v['user'] = $aUser;
            $aRs = $this->filter($v);
        
            if(!empty($v['source']))
            {
                $v['source']['user'] = $aUser;
                $aRs['source'] = $this->filter($v['source']);
            }
            $aRsTrue[] = $aRs;
        }
        
        return $aRsTrue;
    }
    
    public function filterUser($aRs){
    	$aRs = json_decode($aRs,true);
    	$aRs = $aRs['data'];
    	$aRsTmp['uid'] = $aRs['name'];
    	$aRsTmp['username'] = $aRs['name'];
    	$aRsTmp['password'] = md5($aRs['name']);
    	$aRsTmp['registerTime'] = time();
    	$aRsTmp['nickname'] = $aRs['nick'];
    	$aRsTmp['avatar'] = $aRs['head']."/50.jpg";
    	$aRsTmp['verified'] = $aRs['isvip'];
//     	var_dump($aRsTmp);
    	return $aRsTmp;
    }
    
    private function filter($aRs){
        
        
            $aRsTmp = array();
            $aRsTmp['system'] = '';
        
        
           // $text = preg_replace("/#(.*)#/", "<a href='http://t.qq.com/k/$1'>#$1#</a>", $aRs['text']);
        
            preg_match_all("/@(.*?)[ |:]/", $aRs['text'], $aAT);
            
            for($i = 0; $i < sizeof($aAT[1]); $i++){
                $aRs['text'] = str_replace($aAT[0][$i], "@".$aRs['user'][$aAT[1][$i]], $aRs['text']);
            }
            
            $aRsTmp['title'] = $aRs['text'];
            $aRsTmp['time'] = $aRs['timestamp'];
            $aRsTmp['id'] = $aRs['id'];
            $aRsTmp['data'] = json_encode($aRs);
            $aRsTmp['client'] = $aRs['from'];
            $aRsTmp['client_url'] = @$aRs['fromurl'];
            $aRsTmp['forwardcount'] = @$aRs['count'];
            $aRsTmp['commentcount'] = $aRs['mcount'];
            
            $aRsTmp['uid'] = $aRs['name'];
            $aRsTmp['username'] = $aRs['name'];
            $aRsTmp['password'] = md5($aRs['name']);
            $aRsTmp['registerTime'] = time();
            $aRsTmp['nickname'] = $aRs['nick'];
            $aRsTmp['avatar'] = $aRs['head']."/50.jpg";
			$aRsTmp['verified'] = $aRs['isvip'];
        
            for($i = 0; $i < sizeof($aRs['image']); $i++){
        
                $aRsAttachmentTmp = array();
                $aRsAttachmentTmp['type'] = 'image';
                $aRsAttachmentTmp['url'] = $aRs['image'][$i]."/460.jpg";
                $aRsAttachmentTmp['thumbnail_pic'] = $aRs['image'][$i]."/120.jpg";
                $aRsTmp['attachment'][] = $aRsAttachmentTmp;
            }
        
            if(!empty($aRs['video']))
            {
                $aRsAttachmentTmp = array();
                $aRsAttachmentTmp['type'] = 'video';
                $aRsAttachmentTmp['url'] = $aRs['video']['player'];
                $aRsAttachmentTmp['title'] = $aRs['video']['title'];
                $aRsAttachmentTmp['thumbnail_pic'] = $aRs['video']['picurl']."/120.jpg";
                $aRsTmp['attachment'][] = $aRsAttachmentTmp;
            }
    
            if(!empty($aRs['music']))
            {
                $aRsAttachmentTmp = array();
                $aRsAttachmentTmp['type'] = 'music';
                $aRsAttachmentTmp['url'] = $aRs['music']['url'];
                $aRsAttachmentTmp['title'] = $aRs['music']['title'];
                $aRsTmp['attachment'][] = $aRsAttachmentTmp;
            }
        
            return $aRsTmp;
        }
}
