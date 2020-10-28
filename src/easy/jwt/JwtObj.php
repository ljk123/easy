<?php


namespace easy\jwt;


use easy\Jwt;
use easy\utils\Str;

/**
 * Class JwtObj
 * @package easy\jwt
 * @method JwtObj setAlg()
 * @method getAlg(string $alg)
 * @method JwtObj setExp(int $exp)
 * @method getExp()
 * @method JwtObj setSub(string $sub)
 * @method getSub()
 * @method JwtObj setNbf(string $nbf)
 * @method getNbf()
 * @method JwtObj setAud(string $aud)
 * @method getAud()
 * @method JwtObj setIat(string $iat)
 * @method getIat()
 * @method JwtObj setJti(string $jti)
 * @method getJti()
 * @method JwtObj setStatus(int $status)
 * @method getStatus()
 * @method JwtObj setData(mixed $data)
 * @method getData()
 * @method getHeader()
 * @method getPayload()
 * @method JwtObj setSecret(string $secret)
 * @method getSecret()
 * @method getSignature()
 */
class JwtObj
{
    const STATUS_OK = 1;//状态
    const STATUS_SIGNATURE_ERROR = -1;//签名错误
    const STATUS_EXPIRED = -2;//过期

    protected $alg = Jwt::ALG_HMACSHA256; // 加密方式
    protected $iss = 'easyphp'; // 发行人
    protected $sub = 'easy-token'; // 主题
    protected $exp; // 到期时间
    protected $nbf; // 在此之前不可用
    protected $aud = 'nobody'; // 用户
    protected $iat; // 发布时间
    protected $jti; // JWT ID用于标识该JWT
    protected $signature;
    protected $status = 0;
    protected $data = [];

    protected $secret;
    protected $header;
    protected $payload;

    public function __construct(array $bean)
    {
        $this->secret = $bean['secret'] ?? null;
        $this->signature = $bean['signature'] ?? null;

        //header
        !empty($bean['alg']) && $this->alg = $bean['alg'];
        !empty($bean['typ']) && $this->typ = $bean['typ'];
        //payload
        !empty($bean['exp']) && $this->exp = $bean['exp'];
        !empty($bean['sub']) && $this->sub = $bean['sub'];
        !empty($bean['nbf']) && $this->nbf = $bean['nbf'];
        !empty($bean['aud']) && $this->aud = $bean['aud'];
        !empty($bean['iat']) && $this->iat = $bean['iat'];
        !empty($bean['jti']) && $this->jti = $bean['jti'];
        !empty($bean['data']) && $this->data = $bean['data'];
        if (empty($this->nbf)) {
            $this->nbf = time();
        }
        if (empty($this->iat)) {
            $this->iat = time();
        }
        if (empty($this->exp)) {
            $this->exp = time() + 7200;
        }
        if (empty($this->jti)) {
            $this->jti = Str::getRandChar(10);
        }

        // 解包：验证签名
        if (!empty($this->signature)) {
            $signature = $this->signature();
            if ($this->signature !== $signature) {
                $this->status = self::STATUS_SIGNATURE_ERROR;
                return;
            }
            if (time() > $this->exp) {
                $this->status = self::STATUS_EXPIRED;
                return;
            }
        }
        $this->status = self::STATUS_OK;
    }

    public function __call($name, $arguments)
    {
        $snake = Str::snake($name);
        if (in_array($action = substr($snake, 0, 4), [
            'get_', 'set_'
        ])) {
            $attr = substr($snake, 4);
            if (in_array($attr, [
                'alg',
                'iss',
                'exp',
                'sub',
                'nbf',
                'aud',
                'iat',
                'jti',
                'signature',
                'status',
                'data',
                'secret',
                'header',
                'payload',
            ])) {
                if ($action === 'get_') {
                    return $this->$attr;
                } else {
                    if (!empty($arguments[0])) {
                        $this->$attr = $arguments[0];
                        return $this;
                    }
                }
            }
        }
        return null;
    }

    public function setHeader()
    {
        $header = json_encode([
            'alg' => $this->getAlg(),
            'typ' => 'JWT'
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $this->header = Jwt::getInstance()->base64UrlEncode($header);
        return $this;
    }

    public function setPayload()
    {
        $payload = json_encode([
            'exp' => $this->getExp(),
            'sub' => $this->getSub(),
            'nbf' => $this->getNbf(),
            'aud' => $this->getAud(),
            'iat' => $this->getIat(),
            'jti' => $this->getJti(),
            'data' => $this->getData()
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $this->payload = Jwt::getInstance()->base64UrlEncode($payload);
        return $this;
    }

    public function signature(): string
    {

        $this->setHeader();
        $this->setPayload();

        $content = $this->getHeader() . '.' . $this->getPayload();
        /**@var Jwt $instance */
        $instance = Jwt::getInstance();
        switch ($this->getAlg()) {
            case Jwt::ALG_HS256:
            case Jwt::ALG_HMACSHA256:
                $signature = $instance->base64UrlEncode(
                    hash_hmac('sha256', $content, $this->getSecret(), true)
                );
                break;
            case Jwt::ALG_AES:
                $signature = $instance->base64UrlEncode(
                    openssl_encrypt($content, 'AES-128-ECB', $this->getSecret())
                );
                break;
            default:
                $signature = '';
        }

        return $signature;
    }

    public function getToken()
    {
        $this->signature = $this->signature();
        return $this->header . '.' . $this->payload . '.' . $this->signature;
    }

    public function __toString()
    {
        return $this->getToken();
    }

}