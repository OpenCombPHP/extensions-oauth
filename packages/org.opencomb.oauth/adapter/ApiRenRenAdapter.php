<?php
namespace org\opencomb\oauth\adapter ;

use org\jecat\framework\session\Session;

use net\daichen\oauth\Http;

use net\daichen\oauth\OAuthCommon;

if (!session_id()) session_start();

class ApiRenRenAdapter
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
    
    public function TimeLine($token,$token_secret ,$time=""){
        
        $url = $this->arrAdapteeConfigs['api']['timeline']['uri'];
        $params = $this->arrAdapteeConfigs['api']['timeline']['params'];
        
        if($time)
        {
            //$params["pagetime"] = $time;
        }
        
        
        $responseData = $this->oauthCommon->CallRequest($url, $params,"json", $token);
    
        $aRs = json_decode ($responseData,true);
        
        foreach ($aRs as $v)
        {
            $aRs = $this->filter($v);
            $aRsTrue[] = $aRs;
        }
        
        return $aRsTrue;
    }
    
    private function filter($aRs){
        
        
            $aRsTmp = array();
            $aRsTmp['system'] = 'renren.com';
        
            if(empty($aRs['trace']))
            {
                $aRsTmp['subject'] = $aRs['prefix'].' <a href="'.$aRs['href'].'">'.$aRs['title']."</a>";
                $aRsTmp['summary'] = $aRs['description'];
            }else{
                $title = $aRs['trace']['text'];
                for($i = 0; $i < sizeof($aRs['trace']['node']); $i++){
                    $title = preg_replace("/".$aRs['trace']['node'][$i]['name']."/","<a href='http://www.renren.com/profile.do?id=".$aRs['trace']['node'][$i]['id']."'>".$aRs['trace']['node'][$i]['name']."</a>",$title);
                }
                
                $aRsTmp['subject'] = $title;
                $aRsTmp['summary'] = "<b>".$aRs['title']."</b><br/>".$aRs['description'];
            }
            
            $aRsTmp['time'] = strtotime($aRs['update_time']);
            $aRsTmp['data'] = json_encode($aRs);
            $aRsTmp['client'] = $aRs['source']['text'];
            $aRsTmp['client_url'] = $aRs['source']['href'];
        
        
            $aRsTmp['username'] = $aRs['name'];
            $aRsTmp['password'] = md5($aRs['name']);
            $aRsTmp['registerTime'] = time();
            $aRsTmp['nickname'] = $aRs['name'];
            $aRsTmp['avatar'] = $aRs['headurl'];
        
            for($i = 0; $i < sizeof($aRs['attachment']); $i++){
        
                $aRsAttachmentTmp = array();
                if($aRs['attachment'][$i]['media_type'] == "photo")
                {
                    $aRsAttachmentTmp['type'] = 'image';
                    $aRsAttachmentTmp['url'] = $aRs['attachment'][$i]['src'];
                    $aRsAttachmentTmp['link'] = $aRs['attachment'][$i]['href'];
                    $aRsAttachmentTmp['title'] = $aRs['attachment'][$i]['content'];
                    $aRsTmp['attachment'][] = $aRsAttachmentTmp;
                }
                if($aRs['attachment'][$i]['media_type'] == "video")
                {
                    $aRsAttachmentTmp['type'] = 'video';
                    $aRsAttachmentTmp['url'] = $aRs['attachment'][$i]['src'];
                    $aRsAttachmentTmp['link'] = $aRs['attachment'][$i]['href'];
                    $aRsAttachmentTmp['title'] = $aRs['attachment'][$i]['owner_name'];
                    $aRsTmp['attachment'][] = $aRsAttachmentTmp;
                }
                
            }
        
            return $aRsTmp;
        }
}

?>
