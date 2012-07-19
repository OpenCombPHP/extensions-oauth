<?php
namespace org\opencomb\oauth\controlPanel;

use org\opencomb\coresystem\mvc\controller\ControlPanel;

use org\opencomb\coresystem\mvc\controller\Controller;
use org\opencomb\platform\ext\Extension;

/**
 * 设置appkey
 * @author gaojun
 *
 */

class OAuthItem extends ControlPanel
{
	protected $arrConfig = array(
			'view'=>array(
				'template' => 'OAuthItem.html' ,
			),
	) ;	
	
	public function process()
	{
		$aSetting = Extension::flyweight('oauth')->setting();
		
		$aOauthAppkey = array(
		    array('name'=>'163.com'),     
		    array('name'=>'douban.com'),     
		    array('name'=>'renren.com'),     
		    array('name'=>'sohu.com'),     
		    array('name'=>'t.qq.com'),     
		    array('name'=>'weibo.com'),     
        );
		
		for($i = 0; $i < sizeof($aOauthAppkey); $i++)
		{	
			if($aSetting->hasItem( $aOauthAppkey[$i]['name'], 'appKey'))
			{
				$aOauthAppkey[$i]['appKey'] = $aSetting->item($aOauthAppkey[$i]['name'],'appKey');
				$aOauthAppkey[$i]['appSecret'] = $aSetting->item($aOauthAppkey[$i]['name'],'appSecret');
				$aOauthAppkey[$i]['flag'] = $aSetting->item($aOauthAppkey[$i]['name'],'flag');
				$aOauthAppkey[$i]['display'] = $aSetting->item($aOauthAppkey[$i]['name'],'display');
			}else{
				$aOauthAppkey[$i]['appKey'] = '';
				$aOauthAppkey[$i]['appSecret'] = '';
				$aOauthAppkey[$i]['flag'] = '';
				$aOauthAppkey[$i]['display'] = '';
			}
		}
		
		$this->view()->variables()->set('aOauthAppkey',$aOauthAppkey ) ;
		
		if ($_POST)
		{
		    for($i = 0; $i < sizeof($aOauthAppkey); $i++)
		    {
		        $aSetting->setItem($aOauthAppkey[$i]['name'],'appKey',trim($_POST['appKey_text'][$aOauthAppkey[$i]['name']]));
			    $aSetting->setItem($aOauthAppkey[$i]['name'],'appSecret',trim($_POST['appSecret_text'][$aOauthAppkey[$i]['name']]));
			    $aSetting->setItem($aOauthAppkey[$i]['name'],'display', !empty($_POST['display'][$aOauthAppkey[$i]['name']]) ? :'false');
		    }
		    
		    $this->location('?c=org.opencomb.oauth.controlPanel.OAuthItem');
		};
	}
}
