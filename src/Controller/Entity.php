<?php
namespace Emeric0101\PHPAngular\Controller;
use Emeric0101\PHPAngular\Controller\Controller;

class Entity extends Controller {
    private $name = '';

    public function index($id = 0) {
        $this->name = $this->request->get("method", "");
        if ($this->name === "") {
            $this->response->setError("CONTROLLER_REQUIRED");
            return false;
        }
        if ($id === 0) {
            $this->gets();
        }
        else {
            $this->get($id);
        }

    }

    /** Get an Entity from Doctrine
    * @param id integer
    **/
    private function get($id) {
        if ($id === 0) {
            $this->response->setError();
            return false;
        }
        $entity = $this->entityManager->find(\Emeric0101\PHPAngular\Config::PHPANGULAR_BUNDLE ."\\Entity\\" . $this->name, $id);
        if ($entity === null) {
            $this->response->setError("NOT_FOUND");
            return false;
        }

        $this->response->setResponse($this->name, $entity);
        return true;
    }
    /** Get an Entity from Doctrine
    * @param id integer
    **/
    private function gets() {
        $entities = $this->entityManager->getRepository(\Emeric0101\PHPAngular\Config::PHPANGULAR_BUNDLE . "\\Entity\\" . $this->name)->findAll();
        $this->response->setResponse($this->name . 's', $entities);
        return true;
    }

}
