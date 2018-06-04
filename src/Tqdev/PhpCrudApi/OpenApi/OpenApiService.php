<?php
namespace Tqdev\PhpCrudApi\OpenApi;

use Tqdev\PhpCrudApi\Meta\ReflectionService;

class OpenApiService
{
    private $tables;

    public function __construct(ReflectionService $reflection)
    {
        $this->tables = $reflection->getDatabase();
    }

}
