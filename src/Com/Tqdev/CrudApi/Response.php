<?php
namespace Com\Tqdev\CrudApi;

class Response
{

    const OK = 200;
    const INTERNAL_SERVER_ERROR = 500;
    const NOT_FOUND = 404;
    const FORBIDDEN = 403;
    const NOT_ACCEPTABLE = 406;

    private $status;
    private $headers;
    private $body;

    public function __construct(int $status, $body)
    {
        $this->status = $status;
        $this->headers = array();
        $this->parseBody($body);
    }

    private function parseBody($body)
    {
        $data = json_encode($body);
        $this->addHeader('Content-Type', 'application/json');
        $this->addHeader('Content-Length', strlen($data));
        $this->body = $data;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getBody(): String
    {
        return $this->body;
    }

    public function addHeader(String $key, String $value)
    {
        $this->headers[$key] = $value;
    }

    public function getHeader(String $key): String
    {
        if (isset($this->headers[$key])) {
            return $this->headers[$key];
        }
        return null;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function output()
    {
        http_response_code($this->getStatus());
        foreach ($this->getHeaders() as $key => $value) {
            header("$key: $value");
        }
        echo $this->getBody();
    }

    public function __toString(): String
    {
        $str = "$this->status\n";
        foreach ($this->headers as $key => $value) {
            $str .= "$key: $value\n";
        }
        $str .= "\n";
        $str .= "$this->body\n";
        return $str;
    }
}
