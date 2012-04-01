<?php
namespace org\opencomb\oauth\adapter ;

use org\jecat\framework\session\Session;

use net\daichen\oauth\Http;

use net\daichen\oauth\OAuthCommon;

class ApiRenRenAdapter
{
    public $oauthCommon;
    public $arrAdapteeConfigs = array() ;
    public $keys;
    
    public function __construct($aSiteConfig,$aKey) {
        
        if(empty($aSiteConfig) || empty($aKey))
        {
            throw new Exception("尚不能绑定此网站");
        }else{
            $this->arrAdapteeConfigs = $aSiteConfig;
            $this->keys = $aKey;
        }
        $this->oauthCommon = new OAuthCommon($aKey["appkey"],  $aKey["appsecret"]);
    }
    
    public function getUser($token,$token_secret)
    {
        $url = $this->arrAdapteeConfigs['api']['userinfo']['uri'];
        $params = $this->arrAdapteeConfigs['api']['userinfo']['params'];
        $sRS =  $this->oauthCommon->CallRequest($url, $params,"json", $token);
        $aRS = json_decode($sRS,true);
        $aRS['nickname'] = $aRS[0]['name'];
        $aRS['username'] = $aRS[0]['name'];
        $aRS['id'] = $aRS[0]['uid'];
        return $aRS;
    }
    
    public function pushLastForwardId($o,$aRs){

        return $this->pushLastId($o, $aRs);
    }
    
    public function createPushForwardMulti($o,$forwardid,$title){
        
        return $this->createPushMulti($o, $title);
    }
    
    public function pushLastId($o,$aRs){
        
        $url = $this->arrAdapteeConfigs['api']['laststate']['uri'];
        $params = $this->arrAdapteeConfigs['api']['laststate']['params'];
        
        $aTmp = json_decode($this->oauthCommon->CallRequest($url, $params,"json", $o->token),true);
        return $aTmp['status_id'];
    }
    
    public function createPushMulti($o,$title){
        
        $url = $this->arrAdapteeConfigs['api']['add']['uri'];
        $params = $this->arrAdapteeConfigs['api']['add']['params'];
        
        $params['status'] = $this->oauthCommon->http()->utf82Unicode($title);
        
        return $this->oauthCommon->CallRequest($url, $params,"json", $o->token,'renren.com');
    }
    
    public function createTimeLineMulti($o ,$lastData){
        
        $url = $this->arrAdapteeConfigs['api']['timeline']['uri'];
        $params = $this->arrAdapteeConfigs['api']['timeline']['params'];
        
        if($lastData['num'])
        {
            $params['count'] = 30;
            $params['page'] = ceil($lastData['num'] / 30);
        }
        
        return $this->oauthCommon->CallRequest($url, $params,"json", $o->token,'renren.com');
    }
    public function createPullCommentMulti($o ,$astate , $otherParams,$auther){
        $url = $this->arrAdapteeConfigs['api']['pullcomment']['uri'];
        $params = $this->arrAdapteeConfigs['api']['pullcomment']['params'];
        $params['status_id'] = $astate['sid'];
        $params['owner_id'] = $auther['suid'];
        $params = $otherParams + $params ;  // 组合额外配置
        return $this->oauthCommon->CallRequest($url, $params,"json", $o['token'],'renren.com');
    }
    public function createPullCommentCount($o ,$astate , $otherParams,$auther){
        $url = $this->arrAdapteeConfigs['api']['commentcount']['uri'];
        $params = $this->arrAdapteeConfigs['api']['commentcount']['params'];
        $params['owner_id'] = $auther['suid'];
        $params['status_id'] = $astate['sid'];
        
        return  $this->oauthCommon->CallRequest($url, $params, 'json',$o->token, 'renren.com');
    }
    
    public function pushCommentMulti($o , $astate ,$arrOtherParams ){
    	$url = $this->arrAdapteeConfigs['api']['pushcomment']['uri'];
    	$params = $this->arrAdapteeConfigs['api']['pushcomment']['params'];
    	$params += $arrOtherParams;
    	
    	return  $this->oauthCommon->CallRequest($url, $params,"json" ,  $o['token'],'renren.com');
    }
    
    public function refreshTtoken($token,$token_secret)
    {
        $url = $this->arrAdapteeConfigs['auth']['refreshTtoken']['uri'];
        $params = $this->arrAdapteeConfigs['auth']['refreshTtoken']['params'];
        
        $params['refresh_token'] = $token_secret;
        $params['client_id'] = $this->keys['appkey'];
        $params['client_secret'] = $this->keys['appsecret'];
        
        return $this->oauthCommon->CallRequest($url, $params,"json", $token);
    }
    
    public function filterTimeLine($token,$token_secret,$responseData,$lastData)
    {
    
        $aRs = json_decode ($responseData,true);
        
        foreach ($aRs as $v)
        {
            //if($lastData['time'] < strtotime($v['update_time']))
            //{
                $aRs = $this->filter($v);
                $aRsTrue[] = $aRs;
            //}
        }
        
        return $aRsTrue;
    }
    
    private function filter($aRs){
        
        
            $aRsTmp = array();
            
            if($aRs['feed_type'] == 20 || $aRs['feed_type'] == 21 ||$aRs['feed_type'] == 22 || $aRs['feed_type'] == 23)
            {
                $aRsTmp['system'] = 'blog';
            }elseif ($aRs['feed_type'] == 30 || $aRs['feed_type'] == 31 ||$aRs['feed_type'] == 32 || $aRs['feed_type'] == 33){
                $aRsTmp['system'] = 'album';
            }else{
                $aRsTmp['system'] = "";
            }
            
        
            if(empty($aRs['trace']))
            {
                $aRsTmp['title'] = $aRs['prefix'] == $aRs['title']? $aRs['title'] : $aRs['prefix'].$aRs['title'];
                $aRsTmp['body'] = $aRs['description'];
            }else{
                $title = $aRs['trace']['text'];
//                 for($i = 0; $i < sizeof($aRs['trace']['node']); $i++){
//                     $title = preg_replace("/".$aRs['trace']['node'][$i]['name']."/","<a href='http://www.renren.com/profile.do?id=".$aRs['trace']['node'][$i]['id']."'>".$aRs['trace']['node'][$i]['name']."</a>",$title);
//                 }
                
                $aRsTmp['title'] = $title;
                $aRsTmp['body'] = "<b>".$aRs['title']."</b><br/>".$aRs['description'];
            }
            
            $aRsTmp['time'] = strtotime($aRs['update_time']);
//             $aRsTmp['id'] = $aRs['post_id'];
            $aRsTmp['id'] = $aRs['source_id'];
            $aRsTmp['data'] = json_encode($aRs);
            $aRsTmp['client'] = $aRs['source']['text'];
            $aRsTmp['client_url'] = $aRs['source']['href'];
            $aRsTmp['forwardcount'] = 0;
        
            
            $aRsTmp['uid'] = $aRs['actor_id'];
            $aRsTmp['username'] = $aRs['name'];
            $aRsTmp['password'] = md5($aRs['name']);
            $aRsTmp['registerTime'] = time();
            $aRsTmp['nickname'] = $aRs['name'];
            $aRsTmp['avatar'] = $aRs['headurl'];
            $aRsTmp['verified'] = 0;
        
            for($i = 0; $i < sizeof($aRs['attachment']); $i++){
        
                $aRsAttachmentTmp = array();
                if($aRs['attachment'][$i]['media_type'] == "photo")
                {
                    $aRsAttachmentTmp['type'] = 'image';
                    $aRsAttachmentTmp['thumbnail_pic'] = $aRs['attachment'][$i]['src'];
                    $aRsAttachmentTmp['url'] = $aRs['attachment'][$i]['raw_src'];
                    $aRsAttachmentTmp['link'] = $aRs['attachment'][$i]['href'];
                    $aRsAttachmentTmp['title'] = $aRs['attachment'][$i]['content'];
                    $aRsTmp['attachment'][] = $aRsAttachmentTmp;
                }
                if($aRs['attachment'][$i]['media_type'] == "video")
                {
                    $aRsAttachmentTmp['type'] = 'video';
                    $aRsAttachmentTmp['thumbnail_pic'] = $aRs['attachment'][$i]['src'];
                    $aRsAttachmentTmp['url'] = $aRs['attachment'][$i]['href'];
                    $aRsAttachmentTmp['title'] = $aRs['attachment'][$i]['owner_name'];
                    $aRsTmp['attachment'][] = $aRsAttachmentTmp;
                }
                if($aRs['attachment'][$i]['media_type'] == "page")
                {
                    $aRsAttachmentTmp['type'] = 'text/html';
                    $aRsAttachmentTmp['url'] = $aRs['attachment'][$i]['src'];
                    $aRsAttachmentTmp['link'] = $aRs['attachment'][$i]['href'];
                    $aRsTmp['attachment'][] = $aRsAttachmentTmp;
                }
                
            }
        
            return $aRsTmp;
        }
}

?>
