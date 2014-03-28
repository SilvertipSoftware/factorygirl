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

    public function define($key, \Closure $block, $options = array()) {
        // TODO: check model exists
        if ( empty($options) ) {
            $options = array(
                'class' => ucfirst(strtolower($key))
            );
        } else if ( is_string( $options) ) {
            $options = array(
                'class' => $options
            );
        }
        $this->factories[$key] = array($block, $options);
    }

    public function build($key, array $overrides = array()) {
        if ( $this->factories == NULL)
            $this->loadDefinitions();

        // TODO: worry about infinite loops...
        $attrs = $this->getAttributesForFactory( $key );
        $attrs = array_merge( $attrs, $overrides );
        $this->finalize($key, $attrs);
        $model = $this->createModel( $key, $attrs, $this->getOptionsForFactory($key) );
        return $model;
    }

    //TODO: next two functions should be collapsed...
    protected function getOptionsForFactory( $key ) {
        if ( !array_key_exists($key, $this->factories) )
            throw new \InvalidArgumentException("Factory for ".$key." does not exist");

        list( $block, $options ) = $this->factories[$key];
        if ( array_key_exists('parent', $options) ) {
            $base_opts = $this->getOptionsForFactory( $options['parent'] );
        } else {
            $base_opts = array(
                'class' => ucfirst(strtolower($key))
            );
        }
        return array_merge( $base_opts, $options );
    }

    protected function getAttributesForFactory( $key ) {
        if ( !array_key_exists($key, $this->factories) )
            throw new \InvalidArgumentException("Factory for ".$key." does not exist");

        list( $block, $options ) = $this->factories[$key];
        $base_attrs = array();
        if ( array_key_exists('parent', $options) ) {
            $base_attrs = $this->getAttributesForFactory( $options['parent'] );
        }
        $attrs = array();
        if ( $block != NULL )
            $attrs = $block($this);

        return array_merge( $base_attrs, $attrs );
    }

    protected function createModel( $key, $attrs, $options ) {
        if ( !array_key_exists('class', $options) )
            throw new \InvalidArgumentException('Model class is not specified for factory '.$key);

        $clsName = $options['class'];
        $model = new $clsName();
        foreach ( $attrs as $attr => $value) {
            $model->$attr = $value;
        }
        return $model;
    }

    public function create($key, array $overrides = array()) {
        $model = $this->build($key, $overrides);
        if (!$model->save())
            throw new \InvalidArgumentException("Could not save model built from factory ".$key."\n".$model->errors);
        return $model;
    }

    // TODO: should do more than belongs-to relations
    public function finalize($key, &$attrs) {
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

    public function associate($key = NULL, array $overrides = array()) {
        if ( $key == NULL )
            $overrides = array();
        else if ( is_array($key) ) {
            $overrides = $key;
            $key = NULL;
        }

        return new Association($overrides, $key);
    }

    public function sequence($name, \Closure $defn) {
        $this->sequences[$name] = new Sequence($defn);
    }

    public function next($name) {
        $seq = $this->sequences[$name];
        return $seq->next();
    }
}
