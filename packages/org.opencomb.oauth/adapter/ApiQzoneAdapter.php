<?php
namespace org\opencomb\oauth\adapter ;

use org\jecat\framework\session\Session;

use net\daichen\oauth\Http;

use net\daichen\oauth\OAuthCommon;

class ApiQzoneAdapter
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
    
    public function createTimeLineMulti($o,$lastData){
    
        $url = $this->arrAdapteeConfigs['api']['timeline']['uri'];
        $url = str_replace("{id}", $o->suid, $url);
        
        return $this->oauthCommon->SignRequest($url, "get", array(), $o->token, $o->token_secret,'qzone.qq.com');
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
    
    private function filter($aRs){
        
        
            $aRsTmp = array();
            $aRsTmp['system'] = '';
        
        
            $text = preg_replace("/#(.*)#/", "<a href='http://t.qq.com/k/$1'>#$1#</a>", $aRs['text']);
        
            preg_match_all("/@(.*?):/", $text, $aAT);
            if(!empty($aAT[1][0])) $text = preg_replace("/@(.*?):/", "<a href='http://t.qq.com/$1'>".$aRs['user'][$aAT[1][0]]."</a>:", $text);
        
            $aRsTmp['title'] = trim($text);
            $aRsTmp['time'] = $aRs['timestamp'];
            $aRsTmp['id'] = $aRs['id'];
            $aRsTmp['data'] = json_encode($aRs);
            $aRsTmp['client'] = $aRs['from'];
            $aRsTmp['client_url'] = @$aRs['fromurl'];
        
        
            $aRsTmp['username'] = $aRs['name'];
            $aRsTmp['password'] = md5($aRs['name']);
            $aRsTmp['registerTime'] = time();
            $aRsTmp['nickname'] = $aRs['nick'];
            $aRsTmp['avatar'] = $aRs['head'];
            $aRsTmp['verified'] = $aRs['isvip'];
        
            for($i = 0; $i < sizeof($aRs['image']); $i++){
        
                $aRsAttachmentTmp = array();
                $aRsAttachmentTmp['type'] = 'image';
                $aRsAttachmentTmp['url'] = $aRs['image'][$i];
                $aRsTmp['attachment'][] = $aRsAttachmentTmp;
            }
        
            for($i = 0; $i < sizeof($aRs['video']); $i++){
        
                $aRsAttachmentTmp = array();
                $aRsAttachmentTmp['type'] = 'video';
                $aRsAttachmentTmp['url'] = $aRs['video']['player'];
                $aRsAttachmentTmp['title'] = $aRs['video']['title'];
                $aRsAttachmentTmp['thumbnail_pic'] = $aRs['video']['picurl'];
                $aRsTmp['attachment'][] = $aRsAttachmentTmp;
            }
        
            for($i = 0; $i < sizeof($aRs['music']); $i++){
        
                $aRsAttachmentTmp = array();
                $aRsAttachmentTmp['type'] = 'music';
                $aRsAttachmentTmp['url'] = $aRs['music']['url'];
                $aRsAttachmentTmp['title'] = $aRs['music']['title'];
                $aRsTmp['attachment'][] = $aRsAttachmentTmp;
            }
        
            return $aRsTmp;
        }
}

?>
