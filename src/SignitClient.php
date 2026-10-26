<?php

namespace Signit;

use Signit\Exceptions\SignitException;

/** Framework independent client for the Signit v1 API. */
class SignitClient
{
    private $clientId;
    private $clientSecret;
    private $baseUrl;
    private $timeout;
    private $accessToken;
    private $tokenExpiresAt = 0;
    private $clientQueryId;

    public function __construct($clientId, $clientSecret, $baseUrl, $clientQueryId = null, $timeout = 30)
    {
        if (!$clientId || !$clientSecret || !$baseUrl) { throw new \InvalidArgumentException('Signit client ID, secret, and base URL are required.'); }
        $this->clientId=$clientId; $this->clientSecret=$clientSecret; $this->baseUrl=rtrim($baseUrl,'/'); $this->clientQueryId=$clientQueryId; $this->timeout=(int)$timeout;
    }

    public function getAccessToken($forceRefresh = false)
    {
        if (!$forceRefresh && $this->accessToken && time() < $this->tokenExpiresAt - 30) return $this->accessToken;
        $result=$this->request('POST','/v1/oauth/token',['scope'=>'signature-requests:write signature-requests:read','grant_type'=>'client_credentials'],['Authorization: Basic '.base64_encode($this->clientId.':'.$this->clientSecret)],true);
        if (empty($result['access_token'])) throw new SignitException('Signit token response did not contain access_token.',200,$result);
        $this->accessToken=$result['access_token']; $this->tokenExpiresAt=time()+(int)($result['expires_in']??3600); return $this->accessToken;
    }

    public function uploadDocument($path,$fileName=null)
    {
        if (!is_file($path)||!is_readable($path)) throw new \InvalidArgumentException('Document path must be a readable file.');
        $result=$this->request('POST','/v1/documents',['document'=>new \CURLFile($path,'application/pdf',$fileName?:basename($path))],[],false,true);
        if (empty($result['document_name'])) throw new SignitException('Signit upload response did not contain document_name.',200,$result); return $result['document_name'];
    }

    public function createEmbeddedSignatureRequest($documentName,array $signatureRequest)
    {
        $result=$this->request('POST','/v1/signature-requests/embedded',['document_name'=>$documentName,'signature_request'=>$signatureRequest]);
        if (empty($result['id'])) throw new SignitException('Signit response did not contain a signature request ID.',200,$result); return $result;
    }
    public function getSignatureRequest($requestId) { return $this->request('GET','/v1/signature-requests/'.rawurlencode($requestId)); }
    public function getSignatureStatus($requestId) { $data=$this->getSignatureRequest($requestId); return $data['status']??null; }
    public function getSignatoryId($requestId,$index=0)
    { $data=$this->getSignatureRequest($requestId); if(!isset($data['signatories'][$index]['id'])) throw new SignitException('Signatory ID was missing from Signit response.',200,$data); return $data['signatories'][$index]['id']; }

    public function getSigningUrl($requestId,$signatoryId)
    {
        $data=$this->request('GET','/v1/signature-requests/'.rawurlencode($requestId).'/signatories/'.rawurlencode($signatoryId).'/signing-link');
        if(empty($data['url'])) throw new SignitException('Signing URL was missing from Signit response.',200,$data);
        if($this->clientQueryId!==null&&$this->clientQueryId!=='') { $parts=parse_url($data['url']); $query=[]; parse_str($parts['query']??'',$query); $query['client_id']=$this->clientQueryId; $data['url']=(isset($parts['scheme'])?$parts['scheme'].'://':'').($parts['host']??'').($parts['path']??'').'?'.http_build_query($query).(isset($parts['fragment'])?'#'.$parts['fragment']:''); }
        return $data['url'];
    }
    public function getSealedFileUrl($requestId)
    { $data=$this->request('GET','/v1/signature-requests/'.rawurlencode($requestId).'/files?file_type=sealed'); if(empty($data['url'])) throw new SignitException('Sealed file URL was missing from Signit response.',200,$data); return $data['url']; }

    private function request($method,$endpoint,array $data=[],array $extraHeaders=[],$form=false,$multipart=false)
    {
        $headers=array_merge(['Accept: application/json'],$extraHeaders); if($endpoint!=='/v1/oauth/token') $headers[]='Authorization: Bearer '.$this->getAccessToken(); if(!$multipart) $headers[]=$form?'Content-Type: application/x-www-form-urlencoded':'Content-Type: application/json';
        $handle=curl_init($this->baseUrl.$endpoint); curl_setopt_array($handle,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>$this->timeout,CURLOPT_HTTPHEADER=>$headers,CURLOPT_CUSTOMREQUEST=>$method]);
        if($method!=='GET') curl_setopt($handle,CURLOPT_POSTFIELDS,$multipart?$data:($form?http_build_query($data):json_encode($data)));
        $raw=curl_exec($handle); $status=(int)curl_getinfo($handle,CURLINFO_HTTP_CODE); $error=curl_error($handle); curl_close($handle); if($raw===false||$error) throw new SignitException('Signit connection failed: '.$error);
        $decoded=json_decode($raw,true); $decoded=is_array($decoded)?$decoded:[]; if($status<200||$status>=300) throw new SignitException($decoded['message']??('Signit API request failed with HTTP '.$status),$status,$decoded); return $decoded;
    }
}
