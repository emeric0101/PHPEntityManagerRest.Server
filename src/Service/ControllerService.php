<?php
namespace Emeric0101\PHPAngular\Service;
use Emeric0101\PHPAngular\Service\{Request, Response, DbService};

class ControllerService extends AService{
    private $container = null;
    private $request = null;
    private $response = null;
    private $dbService = null;
    public function __construct(Request $r, Response $rp, DbService $d) {
        $this->request = $r;
        $this->response = $rp;
        $this->dbService = $d;
    }
    public function setContainer($container) {
        $this->container = $container;
    }

    public function getControllerName($controllerGet) {
        if (class_exists(\Emeric0101\PHPAngular\Config::PHPANGULAR_BUNDLE . '\\Controller\\' . $controllerGet)) {
			$controllerName = \Emeric0101\PHPAngular\Config::PHPANGULAR_BUNDLE . '\\Controller\\' . $controllerGet;
        }
        else if (class_exists('Emeric0101\\PHPAngular\\Controller\\' . $controllerGet)) {
			$controllerName = 'Emeric0101\\PHPAngular\\Controller\\' . $controllerGet;
        }
        else {
            throw new \Exception("Controller : $controllerGet not found");
        }
        return $controllerName;
    }

    public function callController($controllerGet = null, $methodGet = null, $idGet = null, $params = null) {
        // Getting the controller asked
        $controller = 'Home';
        $method = 'index';

		// if not provided, get from the $_GET
		if ($controllerGet == null) {
			$controllerGet = $this->request->get("controller", "Home");
			$methodGet = $this->request->get("method", "index");
			$idGet = intval($this->request->get('id', ""));
		}
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit();
        }
        $controllerName = static::getControllerName($controllerGet);

        // Add base services if the controller don't exist
        $controllerInstance = $this->container->get($controllerName);
        $this->container->call(function(Response $response, DbService $db, Request $request) use ($controllerInstance){
            $controllerInstance->setBaseService($response, $db, $request);
        });



        if (method_exists($controllerInstance, $methodGet)) {
            $method = $methodGet;
        }

        // Manage the return values of the controller called, default TRUE
        $ret = $controllerInstance->$method($idGet);
        if ($ret === null) {
            $this->response->setResponse('success', true);
        }
        else {
            $this->response->setResponse('success', $ret);
        }
        // Logging
        if (PHPANGULAR_DEBUG) {
            $debug = (array)$this->dbService->getLog();
            $debug['end'] = microtime(true);
            $this->response->setResponse('debug', $debug);
        }
    }

    public function render() {
        $this->response->render();
    }

}
