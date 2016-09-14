<?php
namespace Emeric0101\PHPAngular\Service;
use Doctrine\Common\Annotations\AnnotationReader;
class CliCopyError extends \Exception {}
class Cli extends AService {
    private $entities = [];

    private function recurse_copy($src,$dst, $force = true) {
        $dir = opendir($src);
        @mkdir($dst);
        while(false !== ( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if ( is_dir($src . '/' . $file) ) {
                    $this->recurse_copy($src . '/' . $file,$dst . '/' . $file, $force);
                }
                else {
                    if (!file_exists($dst . '/' . $file) || $force) {
                        $result = copy($src . '/' . $file,$dst . '/' . $file);
                        if ($result == false) {
                            throw new CliCopyError();
                        }
                    }
                }
            }
        }
        closedir($dir);
    }
    /**
     * Write a file with the content
     * @param type $dir
     * @param type $contents
     * @param type $flags
     * @return type
     */
    public static function file_force_contents($dir, $contents, $flags = 0){
        $dir = explode('/', $dir);
        $file = array_pop($dir);
        $dir = implode('/', $dir);
        clearstatcache(true, $dir);
        if(!file_exists($dir)){mkdir($dir, 0705, true);}
        return file_put_contents($dir . '/' . $file, $contents, $flags);
    }

    private function createAttributes($name, $type) {
        $types = [
            'text' => 'string',
            'string' => 'string',
            'integer' => 'number',
            'float' => 'number'
        ];
        if (array_key_exists($type, $types)) {
            $type = $types[$type];
        }
        else {
            $type = 'any';
        }
        $code = '  		private ' . $name . ' :' . $type . ';'. PHP_EOL;
        $code .= '		get' . ucFirst($name) . '() : ' . $type . ' {' . PHP_EOL;
        $code .= '			return this.' . $name . ';' . PHP_EOL;
        $code .= '		}' . PHP_EOL;
        $code .= '      set' . ucFirst($name) . '(v : ' . $type . ')  {' . PHP_EOL;
        $code .= '          this.setValue(\'' . $name . '\', v);' . PHP_EOL;
        $code .= '      }' . PHP_EOL;
        return $code;
    }
    private function createOneToOne($name, $targetEntity) {
        $targetJs = implode('.', explode('\\', $targetEntity));
        $code = '  		private ' . $name . ' :' . $targetJs . ' = null;'. PHP_EOL;
        $code .= '		get' . ucFirst($name) . '() : ' . $targetJs . ' {' . PHP_EOL;
        $code .= '			return this.foreignKey(\'' . $name . '\');' . PHP_EOL;
        $code .= '		}' . PHP_EOL;
        $code .= '      set' . ucFirst($name) . '(v : ' . $targetJs . ') {' . PHP_EOL;
        $code .= '          this.setValue(\'' . $name . '\', v);' . PHP_EOL;
        $code .= '      }' . PHP_EOL;
        return $code;
    }
    private function createOneToMany($name, $targetEntity) {
        $targetJs = implode('.', explode('\\', $targetEntity));
        $code = '  		private ' . $name . ' :' . $targetJs . ' = null;'. PHP_EOL;
        $code .= '		get' . ucFirst($name) . '() : ' . $targetJs . ' {' . PHP_EOL;
        $code .= '			return this.foreignKeys(\'' . $name . '\');' . PHP_EOL;
        $code .= '		}' . PHP_EOL;
        $code .= '      set' . ucFirst($name) . '(v : ' . $targetJs . '[]) {' . PHP_EOL;
        $code .= '          this.setValue(\'' . $name . '\', v);' . PHP_EOL;
        $code .= '      }' . PHP_EOL;
        return $code;
    }

    function install() {
        echo 'Phangular.io - Generate entities' . PHP_EOL;
        $targetJs = implode('.', explode('\\', substr(PHPANGULAR_BUNDLE, 1)));
        $cwd = getcwd();
        $this->createWeb(false);
        $this->createEntityFactory($targetJs);
        $className = PHPANGULAR_BUNDLE . '\\Entity\\';


        echo PHP_EOL;
        $this->entities = $this->getEntities();
        foreach ($this->entities as $entity) {
            if ($entity == '') {continue;}
            // path of the class
            $path = $cwd . '/web/js/Entity/' . $entity . '.ts';
            // name of the class
            $classNameCurrent = $className . $entity;
            echo 'Class ' . $classNameCurrent . '...';
            // skip if exist
            if (file_exists($path)) {
                echo 'file already exist' . PHP_EOL;
                continue;
            }
            else {
                echo PHP_EOL;
            }

            $class = new $classNameCurrent();
            $code = 'module ' . $targetJs . '.Entity {' . PHP_EOL;
            $code .= '    export class '. $entity . ' extends Emeric0101.PHPAngular.Entity.Model {' . PHP_EOL;

            $reflectionClass = new \ReflectionClass($classNameCurrent);
            $annotationReader = new AnnotationReader($reflectionClass);
            foreach ($reflectionClass->getProperties() as $property) {
                if ($property->name == 'id') {continue;}
                $methodInfo = $annotationReader->getPropertyAnnotations($property);
                switch (get_class($methodInfo[0])) {
                    case 'Column':
                        $code .= $this->createAttributes($property->name, $methodInfo[0]->type);
                    break;
                    case 'OneToOne':
                        $code .= $this->createOneToOne($property->name, $methodInfo[0]->targetEntity);

                    break;
                    case 'OneToMany':
                        $code .= $this->createOneToMany($property->name, $methodInfo[0]->targetEntity);
                    break;
                    case 'ManyToMany':
                    break;
                    case 'ManyToOne':
                        $code .= $this->createOneToOne($property->name, $methodInfo[0]->targetEntity);

                    break;
                    default:
                        throw new \Exception('Unable to find the type of this attribute');
                }
            }
            $code .= '        constructor(repositoryService) {' . PHP_EOL;
            $code .= '          super("' . $entity . '", repositoryService);' . PHP_EOL;
            $code .= '        }' . PHP_EOL;
            $code .= '  }' . PHP_EOL;
            $code .= '}' . PHP_EOL;
            $this->file_force_contents($path, $code);
        }
        echo PHP_EOL . 'Okay, everything is ready but.... you have to do a last little thing' . PHP_EOL;
        echo 'You have to run some commands : ' . PHP_EOL;
        echo 'cd web' . PHP_EOL;
        echo 'bower install' . PHP_EOL;
        echo 'Then, you have to compile everything with Typescript compiler (command "tsc")' . PHP_EOL;

    }

    function update() {
        $this->createWeb();
    }


    function main($argv) {
        $method = 'help';
        if (count($argv) > 1) {
            $method = $argv[1];
        }
        $this->$method();
    }

    function help() {
        echo 'Help : you can use "install", "update"' . PHP_EOL;
    }

    private function createWeb() {
        $cwd = getcwd();

        $packagePath = $cwd . '/vendor/emeric0101/phpangular/';
        @mkdir($cwd . '/web');
        @mkdir($cwd . '/web/core');
        try {
            $this->recurse_copy($packagePath . 'web', $cwd . '/core/web');
        }
        catch (CliCopyError $e) {
            echo 'Unable to copy web directory : ' . $e->getMessage() . PHP_EOL;
            return false;
        }

        // copy the layout
        @mkdir($cwd . '/src');
        @mkdir($cwd . '/src/layout');
        copy($packagePath . 'src/layout/index.php', $cwd . '/src/layout/index.php');

    }

    private function getEntities() {
        $entitiesPath = 'src/Entity';
        $except = ['EntityAbstract.php'];
        $files = scandir($entitiesPath);
        $entities = [];
        foreach ($files as $file) {
            if ($file == '.' || $file == '..') {continue;}
            if (in_array($file, $except)) {continue;}
            $entities[] = substr($file, 0, -4);
        }
        return $entities;
    }

    private function createEntityFactory($bundle) {
        $code = 'module Emeric0101.PHPAngular.Service {
            export class EntityFactory {
                private bundle = '.$bundle.';
                getBundle() : any {
                    return this.bundle;
                }
                create(model : string) {
                    return new (this.getBundle()).Entity[model](0, model);
                }
                /** Create a model from a id object
                * @param id number Id of the object
                * @param model string : name of the model we want \'User\', ...
                * @param callback Function(result) called when object is ready to use (with data)
                */
                createFromId(id : number, model : string, callback? : (result : boolean) => void) {
                    if (typeof(this.getBundle().Entity[model]) !== \'function\') {
                        throw \'Model not found : \' + model;
                    }
                    return new (this.getBundle().Entity[model](id, callback));
                }
            }
            phpangularModule.service("EntityFactory", EntityFactory);
        }';
        $this->file_force_contents('web/js/service/EntityFactory.ts', $code);
    }


}
