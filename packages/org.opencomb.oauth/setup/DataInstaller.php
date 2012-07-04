<?php
namespace org\opencomb\oauth\setup;

use org\jecat\framework\db\DB ;
use org\jecat\framework\message\Message;
use org\jecat\framework\message\MessageQueue;
use org\opencomb\platform\ext\Extension;
use org\opencomb\platform\ext\ExtensionMetainfo ;
use org\opencomb\platform\ext\IExtensionDataInstaller ;

class DataInstaller implements IExtensionDataInstaller
{
	public function install(MessageQueue $aMessageQueue,ExtensionMetainfo $aMetainfo)
	{
		$aExtension = new Extension($aMetainfo);
		
		// 1 . create data table
		$aDB = DB::singleton();
		
		$aDB->execute( "CREATE TABLE IF NOT EXISTS `".$aDB->transTableName("oauth_state")."` (
  `stid` varchar(250) NOT NULL,
  `service` varchar(20) NOT NULL,
  `sid` varchar(200) NOT NULL,
  `forwardcount` int(11) NOT NULL,
  `pullcommenttime` int(11) NOT NULL DEFAULT '0',
  `old_comment_page` int(11) NOT NULL DEFAULT '0' COMMENT '向旧的评论的方向拉到了第几页',
  UNIQUE KEY `service-sid` (`sid`,`service`),
  UNIQUE KEY `stid-service` (`stid`(150),`service`),
  KEY `service` (`service`,`stid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8" );
		$aMessageQueue->create(Message::success,'新建数据表： `%s` 成功',$aDB->transTableName('oauth_state') );
		
		
		$aDB->execute( "CREATE TABLE IF NOT EXISTS `".$aDB->transTableName("oauth_user")."` (
  `uid` int(10) NOT NULL,
  `service` varchar(30) NOT NULL,
  `suid` varchar(100) NOT NULL,
  `username` varchar(100) NOT NULL,
  `nickname` varchar(100) NOT NULL,
  `note` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `token_secret` varchar(255) NOT NULL,
  `valid` tinyint(1) NOT NULL DEFAULT '1' COMMENT '认证是否已经失效',
  `actiontime` int(11) NOT NULL DEFAULT '0' COMMENT '认证最后一次使用的时间',
  `verified` tinyint(1) NOT NULL DEFAULT '0' COMMENT '用户认证',
  `pulltime` int(11) DEFAULT NULL COMMENT '上次拉取时间',
  `pullnexttime` int(11) DEFAULT NULL COMMENT '下次拉取偏移秒数',
  `pulldata` text COMMENT '最后一次拉取的最后一条数据',
  KEY `service-suid` (`service`,`suid`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8" );
		$aMessageQueue->create(Message::success,'新建数据表： `%s` 成功',$aDB->transTableName('oauth_user') );
		
		
		
		// 2. insert table data
		
		
		
		
		// 3. settings
		
		$aSetting = $aExtension->setting() ;
			
				
		$aSetting->setItem('/qzone.qq.com/','appKey',array (
));
				
		$aSetting->setItem('/qzone.qq.com/','appSecret',array (
));
				
		$aSetting->setItem('/qzone.qq.com/','flag','1');
				
		$aMessageQueue->create(Message::success,'保存配置：%s',"/qzone.qq.com/");
			
				
		$aSetting->setItem('/douban.com/','appKey','0ceac7905f0c9d9726c056b11d25d8c5');
				
		$aSetting->setItem('/douban.com/','appSecret','e4a6e42578079f5d');
				
		$aSetting->setItem('/douban.com/','flag','1');
				
		$aSetting->setItem('/douban.com/','display',true);
				
		$aMessageQueue->create(Message::success,'保存配置：%s',"/douban.com/");
			
				
		$aSetting->setItem('/weibo.com/','appKey','1694319595');
				
		$aSetting->setItem('/weibo.com/','appSecret','7a8c3bd750b0dff492949631205cc435');
				
		$aSetting->setItem('/weibo.com/','flag','1');
				
		$aSetting->setItem('/weibo.com/','display',true);
				
		$aMessageQueue->create(Message::success,'保存配置：%s',"/weibo.com/");
			
				
		$aSetting->setItem('/163.com/','appKey','EdfhbziNaN0AhD7O');
				
		$aSetting->setItem('/163.com/','appSecret','7Rb6klRqF8C5BjBCdfpUPAHYFZ5UsrLK');
				
		$aSetting->setItem('/163.com/','flag','1');
				
		$aSetting->setItem('/163.com/','display','false');
				
		$aMessageQueue->create(Message::success,'保存配置：%s',"/163.com/");
			
				
		$aSetting->setItem('/t.qq.com/','appKey','801086404');
				
		$aSetting->setItem('/t.qq.com/','appSecret','736fdb45d5169fd7c014437630e06c9d');
				
		$aSetting->setItem('/t.qq.com/','flag','1');
				
		$aSetting->setItem('/t.qq.com/','display',true);
				
		$aMessageQueue->create(Message::success,'保存配置：%s',"/t.qq.com/");
			
				
		$aSetting->setItem('/renren.com/','appKey','924c354aabf14119aca95e1d04620ae8');
				
		$aSetting->setItem('/renren.com/','appSecret','e504b6e5a79a4584983c37cdc8c32b53');
				
		$aSetting->setItem('/renren.com/','flag','1');
				
		$aSetting->setItem('/renren.com/','display','false');
				
		$aMessageQueue->create(Message::success,'保存配置：%s',"/renren.com/");
			
				
		$aSetting->setItem('/menu/mainmenu/','mainmenu',array (
));
				
		$aMessageQueue->create(Message::success,'保存配置：%s',"/menu/mainmenu/");
			
				
		$aSetting->setItem('/sohu.com/','appKey','UWybXWBjpBCMSTYpGsON');
				
		$aSetting->setItem('/sohu.com/','appSecret','=i6SRj-zxoyQDcUsps)RJLa-Cy*5f=xGCosm50WB');
				
		$aSetting->setItem('/sohu.com/','flag','1');
				
		$aSetting->setItem('/sohu.com/','display',true);
				
		$aMessageQueue->create(Message::success,'保存配置：%s',"/sohu.com/");
			
				
		$aSetting->setItem('/','',array (
));
				
		$aMessageQueue->create(Message::success,'保存配置：%s',"/");
			
		
		
		// 4. files
		
	}
}
