<?php


namespace easy\request\fpm;


use easy\request\Interfaces;
use easy\utils\Str;

class Request implements Interfaces
{
    public function getPath()
    {
        $request_uri = $this->server('REQUEST_URI');
        $query_string = $this->server('QUERY_STRING');
        if ($query_string && false !== strpos($request_uri, $query_string)) {
            $request_uri = substr($request_uri, 0, -strlen($query_string) - 1);
        }
        return $request_uri;
    }

    public function header(string $name = null)
    {
        $server = $this->server();
        $header = [];
        foreach ($server as $k => $v) {
            if (substr($k, 0, 5) === 'HTTP_') {
                $header[Str::studly(strtolower(substr($k, 5)))] = $v;
            }
        }
        if (is_null($name)) {
            return $header;
        }
        return $header[$name] ?? null;
    }

    public function server(string $name = null)
    {

        if (is_null($name)) {
            return $_SERVER;
        }
        return $_SERVER[$name] ?? null;
    }

    public function get(string $name = null)
    {
        if (is_null($name)) {
            return $_GET;
        }
        return $_GET[$name] ?? null;
    }

    public function post(string $name = null)
    {
        $post = !empty($_POST) ? $_POST : json_decode($this->content(), true);
        if (is_null($name)) {
            return $post;
        }
        return $post[$name] ?? null;
    }

    public function files(string $name = null)
    {
        if (is_null($name)) {
            return $_FILES;
        }
        return $_FILES[$name] ?? null;
    }

    public function content()
    {
        return file_get_contents("php://input");
    }
}