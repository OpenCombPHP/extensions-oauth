<?php
namespace org\opencomb\oauth\adapter ;

use net\daichen\oauth\OAuthCommon;

class ApiDoubanAdapter
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
        $sRs = $this->oauthCommon->SignRequest($url, "get", $params, $token, $token_secret);
        $aRS =  json_decode($sRs,true);
        $aRS['nickname'] = $aRS['title']['$t'];
        $aRS['username'] = $aRS['title']['$t'];
        $aRS['id'] = $aRS['db:uid']['$t'];
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
        
        $aTmp = json_decode($this->oauthCommon->SignRequest($url, "get", $params, $o->token, $o->token_secret),true);
        $id = $aTmp['entry'][0]['id']['$t'];
        
        preg_match("/\/([0-9]{1,20})$/", $id,$aId);
        return $aId[1];
    }
    
    public function createPushMulti($o,$title){
    
        $url = $this->arrAdapteeConfigs['api']['add']['uri'];
        $params = $this->arrAdapteeConfigs['api']['add']['params'];
        
        $params['html'] = str_replace("{content}", $title, $params['html']);
        
        return $this->oauthCommon->SignXMLRequest($url, "post", $params['html'], $o->token, $o->token_secret);
    }
    
    public function createTimeLineMulti($o ,$lastData){
    
    
        $url = $this->arrAdapteeConfigs['api']['timeline']['uri'];
        $params = $this->arrAdapteeConfigs['api']['timeline']['params'];
        
        if($lastData['num'])
        {
            $params['start-index'] = $lastData['num'];
        }
        
        return $this->oauthCommon->SignRequest($url, "get", $params, $o->token, $o->token_secret,'douban.com');
    }
    
    //豆瓣没有提供广播的评论接口
//     public function createPullCommentMulti($o ,$id){
//         $url = $this->arrAdapteeConfigs['api']['pullcomment']['uri'];
//         $url = preg_replace("/\{id\}/",$id,$url );
//         var_dump($url);
//         $params = $this->arrAdapteeConfigs['api']['pullcomment']['params'];
// //         $params['apikey']=  $o->token_secret;
//         return $this->oauthCommon->SignRequest($url, "get", $params, $o->token, $o->token_secret,'douban.com');
//     }
    
    public function filterTimeLine($token,$token_secret,$responseData,$lastData)
    {
        
        $aRs = json_decode ($responseData,true);
        
        foreach ($aRs['entry'] as $v)
        {
            if(empty($v['content']['$t']))
            {
                return ;
            }
            //if($lastData['time'] < strtotime($v['published']['$t']))
            //{
                $aRs = $this->filter($v);
                $aRsTrue[] = $aRs;
            //}
        }
        return $aRsTrue;
    }
    
    private function filter($aRs){
        
        
            $aRsTmp = array();
            $aRsTmp['system'] = '';
        
            $aRsTmp['title'] = $aRs['content']['$t'];
            
            //http://api.douban.com/miniblog/879502427
            preg_match("/\/([0-9]{1,20})$/", $aRs['id']['$t'],$aId);
            $aRsTmp['id'] = $aId[1];
            $aRsTmp['time'] = strtotime($aRs['published']['$t']);
            $aRsTmp['data'] = json_encode($aRs);
            $aRsTmp['client'] = "";
            $aRsTmp['client_url'] = "";
            $aRsTmp['forwardcount'] = 0;
        
            preg_match("/\/([0-9]{1,20})$/", $aRs['author']['uri']['$t'],$aUId);
            $aRsTmp['uid'] = $aUId[1];
            $aRsTmp['username'] = $aRs['author']['name']['$t'];
            $aRsTmp['password'] = md5($aRs['author']['name']['$t']);
            $aRsTmp['registerTime'] = time();
            $aRsTmp['nickname'] = $aRs['author']['name']['$t'];
            $aRsTmp['avatar'] = $aRs['author']['link'][2]['@href'];
            $aRsTmp['verified'] = 0;
        
            for($i = 0; $i < sizeof($aRs['image']); $i++){
        
                $aRsAttachmentTmp = array();
                $aRsAttachmentTmp['type'] = 'image';
                $aRsAttachmentTmp['url'] = $aRs['image'][$i];
                $aRsTmp['attachment'][] = $aRsAttachmentTmp;
            }
        
            for($i = 0; $i < sizeof($aRs['video']); $i++){
        
                $aRsAttachmentTmp = array();
                $aRsAttachmentTmp['type'] = 'video';
                $aRsAttachmentTmp['url'] = $aRs['video'][$i]['realurl'];
                $aRsAttachmentTmp['title'] = $aRs['video'][$i]['title'];
                $aRsTmp['attachment'][] = $aRsAttachmentTmp;
            }
        
            for($i = 0; $i < sizeof($aRs['music']); $i++){
        
                $aRsAttachmentTmp = array();
                $aRsAttachmentTmp['type'] = 'music';
                $aRsAttachmentTmp['url'] = $aRs['music'][$i]['url'];
                $aRsAttachmentTmp['title'] = $aRs['music'][$i]['title'];
                $aRsTmp['attachment'][] = $aRsAttachmentTmp;
            }
        
            return $aRsTmp;
        }
}
