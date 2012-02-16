<?php
namespace org\opencomb\oauth\adapter ;

use org\jecat\framework\session\Session;

use net\daichen\oauth\Http;

use net\daichen\oauth\OAuthCommon;

if (!session_id()) session_start();

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
    
    public function TimeLine($token,$token_secret ,$lastData){
    
    
        $url = $this->arrAdapteeConfigs['api']['timeline']['uri'];
        $params = $this->arrAdapteeConfigs['api']['timeline']['params'];
        
        
        $responseData = $this->oauthCommon->SignRequest($url, "get", $params, $token, $token_secret);
        
        $aRs = json_decode ($responseData,true);
        
        foreach ($aRs['entry'] as $v)
        {
            if($lastData['time'] < strtotime($v['published']['$t']))
            {
                $aRs = $this->filter($v);
                $aRsTrue[] = $aRs;
            }
        }
        return $aRsTrue;
    }
    
    private function filter($aRs){
        
        
            $aRsTmp = array();
            $aRsTmp['system'] = 'douban.com';
        
            $aRsTmp['title'] = $aRs['content']['$t'];
            $aRsTmp['time'] = strtotime($aRs['published']['$t']);
            $aRsTmp['data'] = json_encode($aRs);
            $aRsTmp['client'] = "";
            $aRsTmp['client_url'] = "";
        
        
            $aRsTmp['username'] = $aRs['author']['name']['$t'];
            $aRsTmp['password'] = md5($aRs['author']['name']['$t']);
            $aRsTmp['registerTime'] = time();
            $aRsTmp['nickname'] = $aRs['author']['name']['$t'];
            $aRsTmp['avatar'] = $aRs['author']['link'][2]['@href'];
        
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

?>
