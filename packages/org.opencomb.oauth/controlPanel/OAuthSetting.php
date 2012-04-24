<?php
namespace org\opencomb\oauth\controlPanel ;

// use org\jecat\framework\message\Message;
// use org\jecat\framework\setting\Setting;
// use org\opencomb\platform\service\Service;

use org\opencomb\platform\ext\Extension;
use org\opencomb\oauth\adapter\AdapterManager;
use org\opencomb\coresystem\mvc\controller\ControlPanel;

class OAuthSetting extends ControlPanel
{
	public function createBeanConfig()
	{
		$arrBean = array(
			'view:oauthSetting' => array(
					'template' => 'OAuthSetting.html' ,
					'class' => 'view' 
					),
			'controllers' => array() 
		);
		
		$aSetting = Extension::flyweight('oauth')->setting();
		foreach(AdapterManager::singleton()->arrAdapteeConfigs as $sdomain=>$arrItem)
		{
			$arrOAuth = array();
			$arrOAuth['name']=$arrItem['name'];
			$arrOAuth['domain']=$arrItem['url'];
			$sdomain=$arrItem['url'];
			$akey=$aSetting->key('/'.$arrItem['url'],true);
			$arrOAuth['appKey'] = $akey->item('appKey',array());
			$arrOAuth['appSecret'] = $akey->item('appSecret',array());
			$arrOAuth['flag'] = $akey->item('flag',"1");
			$arrBean['controllers'][$sdomain] = array(
					'class' => 'org\\opencomb\\oauth\\controlPanel\\OAuthItem' ,
					'params' => $arrOAuth,
			);
		}
		
		return $arrBean;
	}
	
	public function process()
	{	
	
	}
	
}

