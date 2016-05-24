<?php

namespace Symfony\Installer\Helper;

use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class Bundle
{

    /**
     * @var string
     */
    private $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return array|mixed|null|\stdClass
     */
    public function getConfig()
    {
        return Yaml::parse(file_get_contents(__DIR__ . "/../Bundles/{$this->name}/config.yml"));
    }

    /**
     * @param $type
     * @return string
     */
    protected function getTemplate($type)
    {
        return __DIR__ . "/../Bundles/{$this->name}/templates/$type.yml";
    }

    /**
     * @param $type
     * @return array
     * @throws \Symfony\Component\Yaml\Exception\ParseException
     */
    public function getTemplateData($type)
    {
        return Yaml::parse(file_get_contents($this->getTemplate($type)));
    }
}