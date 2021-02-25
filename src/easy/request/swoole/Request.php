<?php


namespace easy\request\swoole;


use easy\request\Interfaces;

class Request implements Interfaces
{
    protected $request;

    //绑定swoole的resquest
    public function bind($request)
    {
        $this->request = $request;
    }

    public function getPath()
    {
        $request_uri = $this->server('request_uri');
        $query_string = $this->server('query_string');
        if ($query_string && false !== strpos($request_uri, $query_string)) {
            $request_uri = substr($request_uri, 0, -strlen($query_string) - 1);
        }
        return $request_uri;
    }

    public function header(string $name = null, string $default = null)
    {
        if (is_null($name)) {
            return $this->request;
        }
        return $this->request[$name] ?? $default;
    }

    public function server(string $name = null, string $default = null)
    {
        if (is_null($name)) {
            return $this->request->server;
        }
        $name = strtolower($name);
        return $this->request->server[$name] ?? $default;
    }

    public function get(string $name = null, string $default = null)
    {
        if (is_null($name)) {
            return $this->request->get;
        }
        return $this->request->get[$name] ?? $default;
    }

    public function post(string $name = null, string $default = null)
    {
        if (is_null($name)) {
            return $this->request->post;
        }
        return $this->request->post[$name] ?? $default;
    }

    public function files(string $name = null, string $default = null)
    {
        if (is_null($name)) {
            return $this->request->files;
        }
        return $this->request->files[$name] ?? $default;
    }

    public function content()
    {
        return $this->request->rawContent();
    }
}