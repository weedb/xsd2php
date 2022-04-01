<?php

namespace GoetasWebservices\Xsd\XsdToPhp\Php\Structure;

class PHPClass
{
    protected $name;

    protected $namespace;

    protected $doc;

    protected $implements = [];

    public static function createFromFQCN($className)
    {
        if (($pos = strrpos($className, '\\')) !== false) {
            return new self(substr($className, $pos + 1), substr($className, 0, $pos));
        } else {
            return new self($className);
        }
    }

    /**
     * @return array
     */
    public function getImplements(): array
    {
        return $this->implements;
    }

    /**
     * @param bool $onlyParent
     *
     * @return PHPProperty
     */
    public function isSimpleType($onlyParent = false)
    {
        if ($onlyParent) {
            $e = $this->getExtends();
            if ($e) {
                if ($e->hasProperty('_')) {
                    return $e->getProperty('_');
                }
            }
        } else {
            if ($this->hasPropertyInHierarchy('_') && count($this->getPropertiesInHierarchy()) === 1) {
                return $this->getPropertyInHierarchy('_');
            }
        }
    }

    public function setImplements(array $fqcn)
    {
        $this->implements = $fqcn;
    }

    public function getPhpType()
    {
        if (!$this->getNamespace()) {
            if ($this->isNativeType()) {
                return $this->getName();
            }

            return '\\' . $this->getName();
        }

        return '\\' . $this->getFullName();
    }

    public function isNativeType()
    {
        return !$this->getNamespace() && in_array($this->getName(), [
            'string',
            'int',
            'float',
            'bool',
            'array',
            'callable',

            'mixed', //todo this is not a php type but it's needed for now to allow mixed return tags
        ]);
    }

    public function __construct($name = null, $namespace = null)
    {
        $this->name = $name;
        $this->namespace = $namespace;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getNamespace()
    {
        return $this->namespace;
    }

    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;

        return $this;
    }

    public function getDoc()
    {
        return $this->doc;
    }

    public function setDoc($doc)
    {
        $this->doc = $doc;

        return $this;
    }

    public function __toString()
    {
        return $this->getFullName();
    }

    public function getFullName()
    {
        return "{$this->namespace}\\{$this->name}";
    }

    protected $checks = [];

    /**
     * @var PHPConstant[]
     */
    protected $constants = [];

    /**
     * @var PHPProperty[]
     */
    protected $properties = [];

    /**
     * @param
     *            $property
     *
     * @return array
     */
    public function getChecks($property)
    {
        return isset($this->checks[$property]) ? $this->checks[$property] : [];
    }

    /**
     * @param
     *            $property
     * @param
     *            $check
     * @param
     *            $value
     *
     * @return $this
     */
    public function addCheck($property, $check, $value)
    {
        $this->checks[$property][$check][] = $value;

        return $this;
    }

    /**
     * @return PHPProperty[]
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasProperty($name)
    {
        return isset($this->properties[$name]);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasPropertyInHierarchy($name)
    {
        if (count($this->getProperties()) > 1 || (count($this->getProperties()) > 0 && !$this->hasProperty($name))) {
            return false;
        }
        if ($this->hasProperty($name)) {
            return true;
        }
        if (($this instanceof PHPClass) && $this->getExtends() && $this->getExtends()->hasPropertyInHierarchy($name)) {
            return true;
        }

        return false;
    }

    /**
     * @param string $name
     *
     * @return PHPProperty
     */
    public function getPropertyInHierarchy($name)
    {
        if (count($this->getProperties()) > 1 || (count($this->getProperties()) > 0 && !$this->hasProperty($name))) {
            return null;
        }

        if ($this->hasProperty($name)) {
            return $this->getProperty($name);
        }
        if (($this instanceof PHPClass) && $this->getExtends() && $this->getExtends()->hasPropertyInHierarchy($name)) {
            return $this->getExtends()->getPropertyInHierarchy($name);
        }

        return null;
    }

    /**
     * @param string $name
     *
     * @return PHPProperty
     */
    public function getPropertiesInHierarchy()
    {
        $ps = $this->getProperties();

        if (($this instanceof PHPClass) && $this->getExtends()) {
            $ps = array_merge($ps, $this->getExtends()->getPropertiesInHierarchy());
        }

        return $ps;
    }

    /**
     * @param string $name
     *
     * @return PHPProperty
     */
    public function getProperty($name)
    {
        return $this->properties[$name];
    }

    /**
     * @return $this
     */
    public function addProperty(PHPProperty $property)
    {
        $this->properties[$property->getName()] = $property;

        return $this;
    }

    /**
     * @var bool
     */
    protected $abstract;

    /**
     * @var PHPClass
     */
    protected $extends;

    /**
     * @return PHPClass
     */
    public function getExtends()
    {
        return $this->extends;
    }

    /**
     * @return PHPClass
     */
    public function setExtends(PHPClass $extends)
    {
        $this->extends = $extends;

        return $this;
    }

    public function getAbstract()
    {
        return $this->abstract;
    }

    public function setAbstract($abstract)
    {
        $this->abstract = (bool) $abstract;

        return $this;
    }
}
