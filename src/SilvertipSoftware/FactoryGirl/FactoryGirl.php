<?php namespace SilvertipSoftware\FactoryGirl;

use Closure;

class Association {
    public $overrides;
    public $factory;

    public function __construct($overrides = array(), $factory = NULL) {
        $this->overrides = $overrides;
        $this->factory = $factory;
    }
}
class Sequence {
    public $index = 0;
    public $defn;

    public function __construct(\Closure $func) {
        $this->defn = $func;
    }
    public function next() {
        $this->index++;
        $func = $this->defn;
        return $func($this->index);
    }
}

class FactoryGirl {

    protected $app;

    protected $factories = NULL;

    protected $sequences = array();

    public function __construct($app) {
        $this->app = $app;
    }

    public function loadDefinitions($path = "/tests/factories.php") {
        require($this->app['path'].$path);
    }

    public function define($key, \Closure $block, $clsName = NULL) {
        // TODO: check model exists
        if ($clsName == NULL)
            $clsName = ucfirst(strtolower($key));
        $this->factories[$key] = array($block, $clsName);
    }

    public function build($f, array $overrides = array()) {
        if ( $this->factories == NULL)
            $this->loadDefinitions();

        if ( !array_key_exists($f, $this->factories) )
            throw new \InvalidArgumentException("Factory for ".$f." does not exist");

        // TODO: worry about infinite loops...
        $model = NULL;
        list($block, $clsName) = $this->factories[$f];
        if ($block != NULL) {
            $attrs = $block($this);
            $attrs = array_merge($attrs,$overrides);
            $this->finalize($f, $attrs);
            $model = new $clsName();
            foreach ( $attrs as $key => $value) {
                $model->$key = $value;
            }
        }
        return $model;
    }

    public function create($f, array $overrides = array()) {
        $model = $this->build($f, $overrides);
        if (!$model->save())
            throw new \InvalidArgumentException("Could not save model built from ".$f."\n".$model->errors);
        return $model;
    }

    // TODO: should do more than belongs-to relations
    public function finalize($f, &$attrs) {
        $keys = array_keys($attrs);
        $keys_to_be_unset = array();
        while ( count($keys) != 0 ) {
            $k = array_shift($keys);
            $v = $attrs[$k];
            if ($v instanceof Association) {
                $attrs[$k] = $this->createAssociatedObject($k,$v);
                \array_push($keys,$k);
            } else if ($v instanceof \Eloquent) {
                $keys_to_be_unset[] = $k;
                $attrs[$k.'_id'] = $v->id;               
            } else if ($v instanceof \Closure) {
                $attrs[$k] = $v($attrs,$this);
                \array_push($keys,$k);
            }
        }
        foreach ($keys_to_be_unset as $k) {
            unset($attrs[$k]);
        }
    }

    public function createAssociatedObject($key, $assoc) {
        $factory = $assoc->factory;
        if ( $factory == NULL )
            $factory = $key;
        $model = $this->create($factory, $assoc->overrides);
        return $model;
    }

    public function associate(array $overrides = array()) {
        return new Association($overrides);
    }

    public function sequence($name, \Closure $defn) {
        $this->sequences[$name] = new Sequence($defn);
    }

    public function next($name) {
        $seq = $this->sequences[$name];
        return $seq->next();
    }
}
