<?php
namespace org\opencomb\oauth\adapter ;

use org\opencomb\platform\ext\Extension;
use org\jecat\framework\lang\Object;

class AdapterManager extends Object
{
	public function serviceTitle($sService)
	{
		return $this->arrAdapteeConfigs[$sService]['name'] ;
	}
	
	/**
	 * Auth
	 * @throws AuthAdapterException
	 * @return org\opencomb\oauth\adapter\AuthorizeAdapter
	 */
	public function createAuthAdapter($sServiceName,$sOAuthToken=null,$sOAuthTokenSecret=null)
	{
		if( !isset($this->arrAdapteeConfigs[$sServiceName]) )
		{
			throw new AuthAdapterException("无效的服务名称:%s",$sServiceName,null,AuthAdapterException::invalid_service) ;
		}
		if( !isset($this->arrAdapteeConfigs[$sServiceName]['auth']) )
		{
			throw new AuthAdapterException("服务:%s不支持认证/授权操作",$sServiceName,null,AuthAdapterException::not_support_feature) ;
		}
		$aSetting = Extension::flyweight('oauth')->setting() ;
		$sAppKey = $aSetting->item('/'.$sServiceName,'appKey') ;
		$sAppSecret = $aSetting->item('/'.$sServiceName,'appSecret') ;
		
		if( !$sAppKey or !$sAppSecret )
		{
			throw new AuthAdapterException("服务:%s尚未配置正确的 app key/secret",$sServiceName,null,AuthAdapterException::not_setup_appkey) ;
		}

		
		if($this->arrAdapteeConfigs[$sServiceName]['OAuthVersion'] == "1.0" || empty($this->arrAdapteeConfigs[$sServiceName]['OAuthVersion']))
		{
			$oOauth = new Oauth10Adapter($this->arrAdapteeConfigs[$sServiceName],array('appkey'=>$sAppKey,'appsecret'=>$sAppSecret));
		}
		
		if($this->arrAdapteeConfigs[$sServiceName]['OAuthVersion'] == "2.0")
		{
			$oOauth = new Oauth20Adapter($this->arrAdapteeConfigs[$sServiceName],array('appkey'=>$sAppKey,'appsecret'=>$sAppSecret));
		}
		return $oOauth;
	}
	
	/**
	 * API
	 * @param unknown_type $sServiceName
	 * @param unknown_type $sOAuthToken
	 * @param unknown_type $sOAuthTokenSecret
	 * @throws AuthAdapterException
	 */
	public function createApiAdapter($sServiceName,$sOAuthToken=null,$sOAuthTokenSecret=null)
	{
		
		if( !isset($this->arrAdapteeConfigs[$sServiceName]) )
		{
			throw new AuthAdapterException("无效的服务名称:%s",$sServiceName,null,AuthAdapterException::invalid_service) ;
		}
		
		

		$aSetting = Extension::flyweight('oauth')->setting() ;
		$sAppKey = $aSetting->item('/'.$sServiceName,'appKey') ;
		$sAppSecret = $aSetting->item('/'.$sServiceName,'appSecret') ;
	
		if( (!$sAppKey or !$sAppSecret) and $this->arrAdapteeConfigs[$sServiceName]['OAuthVersion']!="null")
		{
			throw new AuthAdapterException("服务:%s尚未配置正确的 app key/secret",$sServiceName,null,AuthAdapterException::not_setup_appkey) ;
		}
	
		$sApiObj = $this->arrAdapteeConfigs[$sServiceName]["api"]['adapter'];
		if(!empty($sApiObj))
		{
			$oOauth = new $sApiObj($this->arrAdapteeConfigs[$sServiceName],array('appkey'=>$sAppKey,'appsecret'=>$sAppSecret));
		}else{
			$oOauth = new ApiAdapter($this->arrAdapteeConfigs[$sServiceName],array('appkey'=>$sAppKey,'appsecret'=>$sAppSecret));
		}
		
		return $oOauth;
	}
	
	/**
	 * 返回CallbackCode编码类型
	 * @param unknown_type $sServiceName
	 */
	public function getCallbackCode($sServiceName){
		return @$this->arrAdapteeConfigs[$sServiceName]['auth']['callbackCode'];
	}
	
	/**
	 * 返回获得access时所需要然参数
	 * @param unknown_type $sServiceName
	 */
	public function getAccessParam($sServiceName){
		return @$this->arrAdapteeConfigs[$sServiceName]['auth']['tokenUrl']['accessParam'];
	}
	
	public $arrAdapteeConfigs = array(
	
			// 新浪微博
			'weibo.com' => array(
					'name' => '新浪微博' ,
					'url' => 'weibo.com' ,
					'OAuthVersion'=>'1.0',
					// 授权
					'auth' => array(
							'adapter' => 'org\\opencomb\\webopenapi\\adapter\\oauth\\AuthorizerRequest' ,
							'authorize' => 'http://api.t.sina.com.cn/oauth/authenticate' ,
							'tokenUrl' => array(
									'request' => 'http://api.t.sina.com.cn/oauth/request_token' ,
									'access' => 'http://api.t.sina.com.cn/oauth/access_token' ,
									'accessParam' => 'oauth_verifier' ,//获得access时所需要然参数
							) ,
							'accessRspn' => array(
									'keyId' => 'user_id' ,
							) ,
					),
					// 应用
					'api' => array(
							
							'adapter' => 'org\\opencomb\\oauth\\adapter\\ApiSinaWeiboAdapter' ,
							'userinfo'=>array(
									'uri'=>'http://api.t.sina.com.cn/account/verify_credentials.json',
									'params'=>array('format'=>'json'),
							),
							'get_oauth2_token'=>array(
									'uri'=>'https://api.weibo.com/oauth2/get_oauth2_token',
									'params'=>array('format'=>'json'),
							),
							'userotherinfo'=>array(
									'uri'=>'http://api.t.sina.com.cn/users/show.json',
									'params'=>array(),
							),
							'commentcount'=>array(
									'uri'=>'http://api.t.sina.com.cn/statuses/counts.json',
									'params'=>array(),
							),
							'add'=>array(
									'uri'=>'http://api.t.sina.com.cn/statuses/update.json',
									'params'=>array('format'=>'json'),
							),
							'add_file'=>array(
                    				'uri'=>'http://api.t.sina.com.cn/statuses/upload.json',
                    				'params'=>array('format'=>'json'),
                    		),
							'add_file_url'=>array(
                    				'uri'=>'https://api.weibo.com/2/statuses/upload_url_text.json',
                    				'params'=>array('format'=>'json'),
                    		),
							'forward'=>array(
									'uri'=>'http://api.t.sina.com.cn/statuses/repost.json',
									'params'=>array('format'=>'json'),
							),
							'createFriend'=>array(
									'uri'=>'http://api.t.sina.com.cn/friendships/create.json',
									'params'=>array('format'=>'json'),
							),
							'removeFriend'=>array(
									'uri'=>'http://api.t.sina.com.cn/friendships/destroy.json',
									'params'=>array('format'=>'json'),
							),
							'timeline'=>array(
									'uri'=>'http://api.t.sina.com.cn/statuses/friends_timeline.json',
									//'uri'=>'https://api.weibo.com/2/statuses/home_timeline.json',
									'params'=>array('format'=>'json'),
									'columns' => array(''=>'') , 
							),
							'pullcomment'=>array(
									'uri'=>'http://api.t.sina.com.cn/statuses/comments.json',
									'params'=>array(),
							),
							'commentcount'=>array(
									'uri'=>'http://api.t.sina.com.cn/statuses/counts.json',
									'params' => array(),
							),
							'pushcomment'=>array(
                    				'uri'=>'http://api.t.sina.com.cn/statuses/comment.json',
                    				'params'=>array(),
                    		),
                    		// 'search'=>array(
                            //      'uri'=>'https://api.t.sina.com.cn/search.json',
                            //      'params'=>array('format'=>'json'),
                            // ),
					),
			) ,
	
			// 腾讯微博
			't.qq.com' => array(
					'name' => '腾讯微博' ,
					'url' => 't.qq.com' ,
					'OAuthVersion'=>'1.0',
					// 授权
					'auth' => array(
							'adapter' => 'org\\opencomb\\webopenapi\\adapter\\oauth\\AuthorizerRequest' ,
							'authorize' => 'https://open.t.qq.com/cgi-bin/authorize' ,
							'tokenUrl' => array(
									'request' => 'https://open.t.qq.com/cgi-bin/request_token' ,
									'access' => 'https://open.t.qq.com/cgi-bin/access_token' ,
									'accessParam' => 'oauth_verifier' ,//获得access时所需要然参数
							) ,
							'accessRspn' => array(
									'keyId' => 'name' ,
							) ,
					),
					
					// 应用
					'api' => array(
							'adapter' => 'org\\opencomb\\oauth\\adapter\\ApiTencentAdapter' ,
							'userinfo'=>array(
									'uri'=>'http://open.t.qq.com/api/user/info',
									'params'=>array('format'=>'json'),
							),
							'usersearch'=>array(
									'uri'=>'http://open.t.qq.com/api/search/user',
									'params'=>array('format'=>'json'),
							),
							'userotherinfo'=>array(
									'uri'=>'http://open.t.qq.com/api/user/other_info',
									'params'=>array('format'=>'json'),
							),
							'add'=>array(
									'uri'=>'http://open.t.qq.com/api/t/add',
									'params'=>array('format'=>'json','clientip'=>'123.119.32.211'),
							),
							'add_file'=>array(
                    				'uri'=>'http://open.t.qq.com/api/t/add_pic',
                    				'params'=>array('format'=>'json'),
                    		),
							'add_pic_url'=>array(
                    				'uri'=>'http://open.t.qq.com/api/t/add_pic_url',
                    				'params'=>array('format'=>'json'),
                    		),
							'forward'=>array(
									'uri'=>'http://open.t.qq.com/api/t/re_add',
									'params'=>array('format'=>'json'),
							),
							'createFriend'=>array(
									'uri'=>'http://open.t.qq.com/api/friends/add',
									'params'=>array('format'=>'json'),
							),
							'removeFriend'=>array(
									'uri'=>'http://open.t.qq.com/api/friends/del',
									'params'=>array('format'=>'json'),
							),
							'timeline'=>array(
									'uri'=>'http://open.t.qq.com/api/statuses/home_timeline',
									'params'=>array('format'=>'json'),
									'columns' => array(''=>'') , 
							),
							'row'=>array(
									'uri'=>'http://open.t.qq.com/api/t/show',
									'params'=>array('format'=>'json'),
									'columns' => array(''=>'') , 
							),
							'pullcomment'=>array(
									'uri'=>'http://open.t.qq.com/api/t/re_list',
									'params'=>array(
											'format'=>'json',
											'flag'=>1,//回复
											'reqnum'=>30, //条目数量
											'lastid'=>0, //和pagetime配合使用（第一页：填0，向上翻页：填上一次请求返回的第一条记录id，向下翻页：填上一次请求返回的最后一条记录id
									),
							),
							'commentcount' => array(
									'uri'=>'http://open.t.qq.com/api/t/show',
									'params'=>array(
										'format' => 'json' ,
									),
							),
							'pushcomment'=>array(
	                				'uri'=>'http://open.t.qq.com/api/t/comment',
	                				'params'=>array( 'format'=>'json' ),
	                		),
	                		'search'=>array(
                                    'uri'=>'http://open.t.qq.com/api/search/t',
                                    'params'=>array(
                                            'format'=>'json',
                                            'pagesize'=>10,
                                            'page'=>1,
                                            'contenttype'=>0,
                                            'sorttype'=>0,
                                            'msgtype'=>0,
                                            'searchtype'=>0,
                                    ),
                            ),
					),
			) ,
	
			// 腾讯空间
			'qzone.qq.com' => array(
					'name' => '腾讯空间' ,
					'url' => 'qzone.qq.com' ,
					'OAuthVersion'=>'null',
					// 应用
					'api' => array(
							'adapter' => 'org\\opencomb\\oauth\\adapter\\ApiQzoneAdapter' ,
							'timeline'=>array(
									'uri'=>'http://qz.qq.com/{id}/fic/',
							),
					),
			) ,
	
			// douban
			'douban.com' => array(
					'name' => '豆瓣社区' ,
					'url' => 'douban.com' ,
					'OAuthVersion'=>'1.0',
					// 授权
					'auth' => array(
							'adapter' => 'org\\opencomb\\webopenapi\\adapter\\oauth\\AuthorizerRequest' ,
							'authorize' => 'http://www.douban.com/service/auth/authorize' ,
							'tokenUrl' => array(
									'request' => 'http://www.douban.com/service/auth/request_token' ,
									'access' => 'http://www.douban.com/service/auth/access_token' ,
									'accessParam' => 'oauth_token' ,//获得access时所需要然参数
							),
							'accessRspn' => array(
									'keyId' => 'douban_user_id' ,
							) ,
	
					),
					// 应用
					'api' => array(
							'adapter' => 'org\\opencomb\\oauth\\adapter\\ApiDoubanAdapter' ,
							'userinfo'=>array(
									'uri'=>'http://api.douban.com/people/%40me',
									'params'=>array('alt'=>'json'),
							),
							'add'=>array(
									'uri'=>'http://api.douban.com/miniblog/saying',
									'params'=>array('format'=>'xml','html'=>"<?xml version='1.0' encoding='UTF-8'?><entry xmlns:ns0=\"http://www.w3.org/2005/Atom\" xmlns:db=\"http://www.douban.com/xmlns/\"><content>{content}</content></entry>"),
							),
							'timeline'=>array(
									'uri'=>'http://api.douban.com/people/%40me/miniblog/contacts',
									'params'=>array('alt'=>'json'),
									'columns' => array(''=>'') , 
							),
							'laststate'=>array(
									'uri'=>'http://api.douban.com/people/%40me/miniblog',
									'params'=>array('alt'=>'json','max-results'=>'1'),
									'columns' => array(''=>'') , 
							),
							'pullcomment'=>array(
									'uri'=>'http://api.douban.com/miniblog/subject/{id}/reviews',
									'params'=>array('alt'=>'json'),
							),
					),
			) ,
	
			// sohu
			'sohu.com' => array(
					'name' => '搜狐' ,
					'OAuthVersion'=>'1.0',
					'url'=>'sohu.com',
					// 授权
					'auth' => array(
							'adapter' => 'org\\opencomb\\webopenapi\\adapter\\oauth\\AuthorizerRequest' ,
							'authorize' => 'http://api.t.sohu.com/oauth/authorize' ,
							'tokenUrl' => array(
									'request' => 'http://api.t.sohu.com/oauth/request_token' ,
									'access' => 'http://api.t.sohu.com/oauth/access_token' ,
									'accessParam' => 'oauth_token' ,//获得access时所需要然参数
							),
							'accessRspn'=> array(
									'keyId' => 'id' ,
							) ,
					),
					// 应用
					'api' => array(
							'adapter' => 'org\\opencomb\\oauth\\adapter\\ApiSohuAdapter' ,
							'userinfo'=>array(
									'uri'=>'http://api.t.sohu.com/users/show.json',
									'params'=>array('format'=>'json'),
							),
							'userotherinfo'=>array(
									'uri'=>'http://api.t.sohu.com/users/show/{nickname}.json',
									'params'=>array('format'=>'json'),
							),
							'add'=>array(
									'uri'=>'http://api.t.sohu.com/statuses/update.json',
									'params'=>array('format'=>'json'),
							),
							'forward'=>array(
									'uri'=>'http://api.t.sohu.com/statuses/transmit/{id}.json',
									'params'=>array('format'=>'json'),
							),
							'timeline'=>array(
									'uri'=>'http://api.t.sohu.com/statuses/friends_timeline.json',
									'params'=>array('format'=>'json'),
									'columns' => array(''=>'') , 
							),
							'createFriend'=>array(
									'uri'=>'http://api.t.sohu.com/friendships/create/{id}.json',
									'params'=>array('format'=>'json'),
							),
							'removeFriend'=>array(
									'uri'=>'http://api.t.sohu.com/friendships/destroy/{id}.json',
									'params'=>array('format'=>'json'),
							),
							'show'=>array(
									'uri'=>'http://api.t.sohu.com/statuses/show/{id}.json',
									'params'=>array('format'=>'json'),
							),
							'pullcomment'=>array(
									'uri'=>'http://api.t.sohu.com/statuses/comments/{id}.json',
									'params'=>array('count'=>100,),
							),
							'commentcount'=>array(
									'uri'=>'http://api.t.sohu.com/statuses/counts/{id}.json',
									'params' => array(),
							),
							'pushcomment'=>array(
                                    'uri'=>'http://api.t.sohu.com/statuses/comment.json',
                                    'params'=>array(),
                            ),
                            'search'=>array(
                                    'uri'=>'http://api.t.sohu.com/statuses/search.json',
                                    'params'=>array(),
                            ),
					),
			) ,
	
			// 163
			'163.com' => array(
					'name' => '网易' ,
					'OAuthVersion'=>'1.0',
					'url'=>'163.com',
					// 授权
					'auth' => array(
							'adapter' => 'org\\opencomb\\webopenapi\\adapter\\oauth\\AuthorizerRequest' ,
							'authorize' => 'http://api.t.163.com/oauth/authenticate' ,
							'tokenUrl' => array(
									'request' => 'http://api.t.163.com/oauth/request_token' ,
									'access' => 'http://api.t.163.com/oauth/access_token' ,
									'accessParam' => 'oauth_token' ,//获得access时所需要然参数
							),
							'accessRspn'=> array(
									'keyId' => 'id' ,
							) ,
					),
					// 应用
					'api' => array(
							'adapter' => 'org\\opencomb\\oauth\\adapter\\Api163Adapter' ,
					        'show'=>array(
					                'uri'=>'http://api.t.163.com/users/show.json',
					                'params'=>array('format'=>'json'),
					        ),
					        'showState'=>array(
					                'uri'=>'http://api.t.163.com/statuses/show/{id}.json',
					                'params'=>array('format'=>'json'),
					        ),
							'userinfo'=>array(
									'uri'=>'http://api.t.163.com/account/verify_credentials.json',
									'params'=>array("format"=>"json"),
							),
							'userotherinfo'=>array(
									'uri'=>'http://api.t.163.com/users/show.json',
									'params'=>array('format'=>'json'),
							),
							'add'=>array(
									'uri'=>'http://api.t.163.com/statuses/update.json',
									'params'=>array('format'=>'json'),
							),
							'forward'=>array(
									'uri'=>'http://api.t.163.com/statuses/retweet/{id}.json',
									'params'=>array('format'=>'json'),
							),
							'timeline'=>array(
									'uri'=>'http://api.t.163.com/statuses/home_timeline.json',
									'params'=>array('format'=>'json'),
									'columns' => array(''=>'') , 
							),
							'createFriend'=>array(
									'uri'=>'http://api.t.163.com/friendships/create.json',
									'params'=>array('format'=>'json'),
							),
							'removeFriend'=>array(
									'uri'=>'http://api.t.163.com/friendships/destroy.json',
									'params'=>array('format'=>'json'),
							),
							'pullcomment'=>array(
									'uri'=>'http://api.t.163.com/statuses/comments/{id}.json',
									'params'=>array('format'=>'json','count'=>30),
							),
							'pushcomment'=>array(
                                    'uri'=>'http://api.t.163.com/statuses/reply.json',
                                    'params'=>array(),
                            ),
                            'search'=>array(
                                    'uri'=>'http://api.t.163.com/statuses/search.json',
                                    'params'=>array(),
                            ),
					),
			) ,
	
			// renren
			'renren.com' => array(
					'name' => '人人' ,
					'OAuthVersion'=>'2.0',
					'url'=>'renren.com',
					// 授权
					'auth' => array(
							'adapter' => 'org\\opencomb\\webopenapi\\adapter\\oauth\\AuthorizerRequest' ,
							'authorize' => 'https://graph.renren.com/oauth/authorize' ,
							'callbackCode'=>'urlencode',
							'tokenUrl' => array(
									'access_token_uri' => 'https://graph.renren.com/oauth/token' ,
									'scope' => 'read_user_album+read_user_feed+read_user_status+publish_comment' ,
									'accessParam' => 'code' ,//获得access时所需要然参数
							),
							'accessRspn'=> array(
									'keyId' => 'user.id' ,
							) ,
							'refreshTtoken'=>array(
									'uri'=>'https://graph.renren.com/oauth/token',
									'params'=>array('grant_type'=>'refresh_token'),
							),
					),
					// 应用
					'api' => array(
							'adapter' => 'org\\opencomb\\oauth\\adapter\\ApiRenRenAdapter' ,
							'userinfo'=>array(
									'uri'=>'http://api.renren.com/restserver.do',
									'params'=>array('mode'=>'users.getInfo','method'=>'users.getInfo','fields'=>'uid,name,sex,star,zidou,vip,birthday,email_hash,tinyurl,headurl,mainurl,hometown_location,work_history,university_history'),
							),
							'add'=>array(
									'uri'=>'http://api.renren.com/restserver.do',
									'params'=>array('format'=>'json','method'=>'status.set'),
							),
							'timeline'=>array(
									'uri'=>'http://api.renren.com/restserver.do',
									'params'=>array('format'=>'json','method'=>'feed.get','type'=>'10,11,20,21,22,23,30,31,32,33,34,35,36,40,41,50,51,52,53,54,55'),
							),
							'laststate'=>array(
									'uri'=>'http://api.renren.com/restserver.do',
									'params'=>array('format'=>'json','method'=>'status.get'),
							),
							'pullcomment'=>array(
									'uri'=>'http://api.renren.com/restserver.do',
									'params'=>array(
											'format'=>'json',
											'method'=>'status.getComment',
											'count'=>30,
											'order'=>1,
									),
							),
							'commentcount'=>array(
									'uri'=>'http://api.renren.com/restserver.do',
									'params' => array(
											'method' => 'status.get',
									),
							),
							'pushcomment'=>array(
                                    'uri'=>'http://api.renren.com/restserver.do',
                                    'params'=>array('format'=>'json','method'=>'status.addComment'),
                            ),
					),
			) ,
	) ;
}

