<?php

namespace Symfony\Installer\Helper;

use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Class Project
 *
 * @package Symfony\Installer\Helper
 */
class Project
{

    private $path;

    private $configFolderPath;

    private $appKernelPath;


    public function __construct($path)
    {
        $this->path             = rtrim($path, '/');
        $this->configFolderPath = $this->path . '/app/config';
        $this->appKernelPath    = $this->path . '/app/AppKernel.php';
        $this->composerPath     = $this->path . '/composer.json';
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getSrcDir()
    {
        return $this->getPath() . 'src/';
    }

    public function updateConfigYaml($filename, $data)
    {
        $data = array_merge($this->getConfigYaml($filename), $data);
        file_put_contents($this->configFolderPath . '/' . $filename . '.yml', Yaml::dump($data, 4));
    }

    public function getConfigYaml($filename)
    {
        try {
            return Yaml::parse(file_get_contents($this->configFolderPath . '/' . $filename . '.yml'));
        } catch (ParseException $e) {
            printf('Unable to parse the YAML string: %s', $e->getMessage());
        }

        return false;
    }

    public function updateComposer($lib)
    {
        $composer = json_decode(file_get_contents($this->composerPath), true);
        $composer['require'][$lib['name']] = $lib['version'];
        $composer = json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($this->composerPath, $composer);
    }

    public function updateAppKernel($bundles)
    {
        $code = '';
        foreach ($bundles as $bundle) {
            $code .= "\n\t\t\t$bundle,";
        }
        $filecontent = file_get_contents($this->appKernelPath);
        $detector    = "new Symfony\\Bundle\\AsseticBundle\\AsseticBundle(),";
        $replacement = "$detector$code";
        $filecontent = str_replace($detector, $replacement, $filecontent);
        file_put_contents($this->appKernelPath, $filecontent);
    }


}