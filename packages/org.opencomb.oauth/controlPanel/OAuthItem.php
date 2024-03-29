<?php
namespace org\opencomb\oauth\controlPanel;

use org\opencomb\coresystem\mvc\controller\Controller;
use org\opencomb\platform\ext\Extension;

class OAuthItem extends Controller
{
	public function createBeanConfig()
	{
		$arrBean = array(
			'view:authItem' => array(
				'template' => 'OAuthItem.html' ,
				'class' => 'form' ,
				'widgets'=>array(
						 array(
								'id'=>'appKey_text',
								'class'=>'text',
								'title'=>'appKey',
								'verifier:notempty'=>array(),
								'verifier:length'=>array(
										'min'=>5,
										'max'=>10)
						),
						 array(
								'id'=>'appSecret_text',
								'class'=>'text',
								'title'=>'appSecret',
								'verifier:notempty'=>array(),
								'verifier:length'=>array(
										'min'=>5,
										'max'=>32)
						),
						//是否使用appkey
						/*
						array(
								'id'=>'do_checkbox',
								'class'=>'checkbox',
								'checked'=>1,
								//'type'=>'radio',
						)
						*/
				)
			)
		) ;
		return $arrBean;
	}
	
	public function process()
	{
		$aSetting = Extension::flyweight('oauth')->setting();
		$akey=$aSetting->key('/'.'dalian',true);
		$this->viewAuthItem->widget('appKey_text')->setValue($this->params->get('appKey'));
		$this->viewAuthItem->widget('appSecret_text')->setValue($this->params->get('appSecret'));
		$this->viewAuthItem->variables()->set('oAuthName',$this->params->get('name')) ;
		
		if ($this->viewAuthItem->isSubmit ( $this->params ))
		{
			
			$this->viewAuthItem->loadWidgets ( $this->params );
			$skey = $this->params->get('domain');
			$sappKey = $this->viewAuthItem->widget('appKey_text')->value();
			$sappSecret = $this->viewAuthItem->widget('appSecret_text')->value();
			$aSetting->setItem('/'.$skey,'appKey',trim($sappKey));
			$aSetting->setItem('/'.$skey,'appSecret',trim($sappSecret));
			if (! $this->viewAuthItem->verifyWidgets ())
			{
				//break;
			}
		};
	}
}
