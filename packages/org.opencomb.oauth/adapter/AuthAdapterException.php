<?php
namespace org\opencomb\oauth\adapter ;

use org\jecat\framework\lang\Exception;

class AuthAdapterException extends Exception
{
	const invalid_service = 1 ;
	const not_support_feature = 2 ;
	const not_setup_appkey = 3 ;
}
