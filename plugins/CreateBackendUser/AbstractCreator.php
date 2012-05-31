<?php

use Netresearch\Logger;

use Netresearch\Config;
use \Exception as Exception;

abstract class AbstractCreator
{
    /**
     * @var Config
     */
    protected $config;

    protected $data = array();

    /**
     * Set config and perform validation of the given config values
     * @param Config $config
     * @param string $key The config key for the current object's properties
     */
    public function __construct(Config $config, $key)
    {
        $this->config = $config;

        $configProperties = $config->plugins->CreateBackendUser->{$key};
        if (null !== $configProperties) {
            foreach ($configProperties->toArray() as $key => $value) {
                $this->validateProperty($key, $value);
                $this->{$key} = $value;
            }
        }
    }

    public function __set($key, $value)
    {
        $allowed_props = $this->getAllowedProperties();

        if (!in_array($key, $allowed_props)) {
            throw new Exception("Invalid property '$key'.");
        }

        $this->data[$key] = $value;
    }

    public function __get($key)
    {
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }

        return null;
    }

    /**
     * Validate all properties that are defined via @see val
     * @return boolean
     * @throws Exception
     */
    protected function validateProperties()
    {
        foreach ($this->getAllowedProperties() as $key) {
            $this->validateProperty($key);
        }

        return true;
    }
    
    /**
     * Validate a property by name.
     * 
     * @return boolean True on success
     * @throws Exception
     */
    abstract protected function validateProperty($key, $value = null);

    /**
     * Return array of properties that are applicable for the specific creator.
     *
     * @return array
     */
    abstract protected function getAllowedProperties();
}
