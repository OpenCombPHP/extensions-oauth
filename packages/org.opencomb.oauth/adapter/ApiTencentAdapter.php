<?php
namespace org\opencomb\oauth\adapter ;

use org\jecat\framework\session\Session;

use net\daichen\oauth\Http;

use net\daichen\oauth\OAuthCommon;

if (!session_id()) session_start();

class ApiTencentAdapter
{
    public $oauthCommon;
    public $arrAdapteeConfigs = array() ;
    public $appkey;
    
    public function __construct($aSiteConfig,$aKey) {
        
        $this->appkey = $aKey;
        if(empty($aSiteConfig) || empty($aKey))
        {
            throw new Exception("尚不能绑定此网站");
        }else{
            $this->arrAdapteeConfigs = $aSiteConfig;
        }
        
        $this->oauthCommon = new OAuthCommon($aKey["appkey"],  $aKey["appsecret"]);
    }
    
    public function filterTimeLineParams($token,$token_secret,$lastData){
    
        $params = $this->arrAdapteeConfigs['api']['timeline']['params'];
        $params['appkey'] = $this->appkey["appkey"];
        $params['appsecret'] = $this->appkey["appsecret"];
        $params['url'] = $this->arrAdapteeConfigs['api']['timeline']['uri'];
        $params['HttpMode'] = "get";
        if(!empty($lastData))
        {
            $params['pageflag'] = "2";
            $params['pagetime'] = $lastData['time'];
        }
        
        //$responseData = $this->oauthCommon->SignRequest_multi($url, "get", $params, $token, $token_secret);
        
        return $params;
    }
    
    public function execTimeLine()
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
    }
    
    
    private function filter($aRs){
        
        
            $aRsTmp = array();
            $aRsTmp['system'] = 't.qq.com';
        
        
            $text = preg_replace("/#(.*)#/", "<a href='http://t.qq.com/k/$1'>#$1#</a>", $aRs['text']);
        
            preg_match_all("/@(.*?):/", $text, $aAT);
            if(!empty($aAT[1][0])) $text = preg_replace("/@(.*?):/", "<a href='http://t.qq.com/$1'>".$aRs['user'][$aAT[1][0]]."</a>:", $text);
        
            $aRsTmp['title'] = trim($text);
            $aRsTmp['time'] = $aRs['timestamp'];
            $aRsTmp['data'] = json_encode($aRs);
            $aRsTmp['client'] = $aRs['from'];
            $aRsTmp['client_url'] = @$aRs['fromurl'];
        
        
            $aRsTmp['username'] = $aRs['name'];
            $aRsTmp['password'] = md5($aRs['name']);
            $aRsTmp['registerTime'] = time();
            $aRsTmp['nickname'] = $aRs['nick'];
            $aRsTmp['avatar'] = $aRs['head'];
        
            for($i = 0; $i < sizeof($aRs['image']); $i++){
        
                $aRsAttachmentTmp = array();
                $aRsAttachmentTmp['type'] = 'image';
                $aRsAttachmentTmp['url'] = $aRs['image'][$i];
                $aRsTmp['attachment'][] = $aRsAttachmentTmp;
            }
        
            for($i = 0; $i < sizeof($aRs['video']); $i++){
        
                $aRsAttachmentTmp = array();
                $aRsAttachmentTmp['type'] = 'video';
                $aRsAttachmentTmp['url'] = $aRs['video']['realurl'];
                $aRsAttachmentTmp['title'] = $aRs['video']['title'];
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
