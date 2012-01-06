<?php
namespace am\abrah\oauth ;

/**
 * Code based on:
 * Abraham Williams (abraham@abrah.am) http://abrah.am
 */

/** 
 * @ignore 
 */ 
class OAuthSignatureMethod { 
    public function check_signature(&$request, $consumer, $token, $signature) { 
        $built = $this->build_signature($request, $consumer, $token); 
        return $built == $signature; 
    } 
} 