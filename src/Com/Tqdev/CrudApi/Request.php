<?php
namespace Com\Tqdev\CrudApi;

class Request {
    
    protected $method;
    protected $path;
    protected $params;
    protected $body;
    protected $headers;

    public function __construct(String $method = null, String $path = null, String $query = null, String $body = null) {
        $this->parseMethod($method);
        $this->parsePath($path);
        $this->parseParams($query);
        $this->parseBody($body);
        $this->headers = array();
    }

    protected function parseMethod(String $method = null) {
        if (!$method) {
            if (isset($_SERVER['REQUEST_METHOD'])) {
                $method = $_SERVER['REQUEST_METHOD'];
            } else {
                $method = 'GET';
            }
        }
        $this->method = $method;
    }

    protected function parsePath(String $path = null) {
        if (!$path) {
            if (isset($_SERVER['PATH_INFO'])) {
                $path = $_SERVER['PATH_INFO'];
            } else {
                $path = '/';
            }
        }
        $this->path = explode('/',$path);
    }

    protected function parseParams(String $query = null) {
        if (!$query) {
            if (isset($_SERVER['QUERY_STRING'])) {
                $query = $_SERVER['QUERY_STRING'];
            } else {
                $query = '';
            }
        }
        $query = str_replace('[][]=', '[]=', str_replace('=', '[]=', $query));
        parse_str($query, $this->params);
    }

    protected function parseBody(String $body = null) {
        if (!$body) {
            $body = file_get_contents('php://input');
        }
        $this->body = $body;
    }

    public function getMethod(): String {
        return $this->method;
    }

    public function getPath(int $part = -1): String {
        if ($part == -1) {
            return implode('/', $this->path);
        }
        if (count($this->path) <= $part) {
            return '';
        }
        return $this->path[$part];
    }

    public function getParams(): array {
        return $this->params;
    }

    public function getBody() {
        $body = $this->body;
        $first = substr($body,0,1);
        if ($first=='[' || $first=='{') {
            $body = json_decode($body);
            $causeCode = json_last_error();
            if ($causeCode !== JSON_ERROR_NONE) {
                throw new \Exception("Error decoding input JSON. json_last_error code: " . $causeCode);
            }
        } else {
            parse_str($body, $input);
            foreach ($input as $key => $value) {
                if (substr($key,-9)=='__is_null') {
                    $input[substr($key,0,-9)] = null;
                    unset($input[$key]);
                }
            }
            $body = (object)$input;
        }
        return $body;
    }

    public function addHeader(String $key, String $value = null) {
        if ($value === null) {
            $header = 'HTTP_'.strtoupper(str_replace('-','_',$key));
            if (isset($_SERVER[$header])) {
                $value = $_SERVER[$header];
            }
        }
        if ($value !== null) {
            $this->headers[$key] = $value;
        }
    }

    public function getHeader(String $key): String {
        if (isset($this->headers[$key])) {
            return $this->headers[$key];
        }
        return '';
    }

    public function getHeaders(): array {
        return $this->headers;
    }
}