<?php
namespace Com\Tqdev\CrudApi;

use Com\Tqdev\CrudApi\Config;
use Com\Tqdev\CrudApi\Request;
use Com\Tqdev\CrudApi\Response;
use Com\Tqdev\CrudApi\Database\GenericDB;
use Com\Tqdev\CrudApi\Api\ErrorCode;
use Com\Tqdev\CrudApi\Controller\BaseController;
use Com\Tqdev\CrudApi\Controller\CrudApiController;
use Com\Tqdev\CrudApi\Router\CorsProtectedRouter;
use Com\Tqdev\CrudApi\Api\CrudApiService;
use Com\Tqdev\CrudApi\Meta\CrudMetaService;

class Api {
    
    protected $router;

    public function __construct(Config $config) {
        $db = new GenericDB(
            $config->getDriver(),
            $config->getAddress(),
            $config->getPort(),
            $config->getDatabase(),
            $config->getUsername(),
            $config->getPassword()
        );
        $meta = new CrudMetaService($db);
        $router = new CorsProtectedRouter($config->getAllowedOrigins());
        $api = new CrudApiService($db, $meta);
        new CrudApiController($router,$api);
        $this->router = $router;
    }

    public function handle(Request $request): Response {
        $response = null;
        try {
            $response = $this->router->route($request);
        } catch (\Throwable $e) {
            $response = BaseController::error(ErrorCode::ERROR_NOT_FOUND, $e->getMessage());
        }
        return $response;
    }
}