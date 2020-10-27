<?php


namespace easy;


use easy\jwt\JwtObj;
use easy\traits\Singleton;

/**
 * Class Jwt
 * @method Jwt getInstance
 * @package easy
 */
class Jwt
{
    use Singleton;
    private $secret = 'easy-php';


    const ALG_AES = 'AES';
    const ALG_HMACSHA256 = 'HMACSHA256';
    const ALG_HS256 = 'HS256';

    public function setSecret(string $key)
    {
        $this->secret = $key;
        return $this;
    }

    /**
     * @return JwtObj
     */
    public function publish()
    {
        return new JwtObj(['secret' => $this->secret]);
    }

    /**
     * @param string $raw
     * @return JwtObj
     * @throws Exception
     */
    public function decode(string $raw)
    {
        $items = explode('.', $raw);
        // token格式
        if (count($items) !== 3) {
            throw new Exception('Token format error!');
        }
        // 验证header
        $header = $this->base64UrlDecode($items[0]);
        $header = json_decode($header, true);
        if (empty($header)) {
            throw new Exception('Token header is empty!');
        }
        // 验证payload
        $payload = $this->base64UrlDecode($items[1]);
        $payload = json_decode($payload, true);
        if (empty($payload)) {
            throw new Exception('Token payload is empty!');
        }
        if (empty($items[2])) {
            throw new Exception('signature is empty');
        }
        $jwtObjConfig = array_merge(
            $header,
            $payload,
            [
                'signature' => $items[2],
                'secret' => $this->secret
            ]
        );

        return new JwtObj($jwtObjConfig);
    }

    /**
     * @param string $content
     * @return false|string
     */
    public function base64UrlDecode(string $content)
    {
        $remainder = strlen($content) % 4;
        if ($remainder) {
            $addlen = 4 - $remainder;
            $content .= str_repeat('=', $addlen);
        }
        return base64_decode(strtr($content, '-_', '+/'));
    }

    /**
     * @param string $content
     * @return string|string[]
     */
    public function base64UrlEncode(string $content)
    {
        return str_replace('=', '', strtr(base64_encode($content), '+/', '-_'));
    }

}