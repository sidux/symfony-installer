<?php

namespace Symfony\Installer;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;
use Symfony\Installer\Exception\AbortException;
use Symfony\Installer\Helper\Bundle;
use Symfony\Installer\Helper\Project;

/**
 * Class AddBundleCommand
 *
 * @package Symfony\Installer
 */
class AddBundleCommand extends BaseCommand
{

    private $bundlesDir;

    private $bundles;

    private $selectedBundles;

    protected function configure()
    {
        $this
            ->setName('add-bundles')
            ->setDescription('Installs some Symfony Bundles.');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->bundlesDir = __DIR__ . DIRECTORY_SEPARATOR . 'Bundles';
        $this->bundles    = scandir($this->bundlesDir);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this
                ->askBundleName()
//                ->resolveDependencies()
//                ->cleanUp()
//                ->updateConfig()
//                ->updateParameters()
//                ->updateRouting()
//                ->updateAppKernel()
                ->updateComposer()
                ->displayInstallationResult();
        } catch (AbortException $e) {
            aborted:

            $output->writeln('');
            $output->writeln('<error>Aborting download and cleaning up temporary directories.</error>');

            $this->cleanUp();

            return 1;
        } catch (\Exception $e) {
            // Guzzle can wrap the AbortException in a GuzzleException
            if ($e->getPrevious() instanceof AbortException) {
                goto aborted; // NOSONAR
            }

            $this->cleanUp();
            throw $e;
        }

        return true;
    }

    protected function askBundleName()
    {

        $dialog = $this->getHelper('dialog');

        $selected = $dialog->select(
            $this->output,
            'Please select your bundles',
            $this->bundles,
            0,
            false,
            'Value "%s" is invalid',
            true
        );

        $selectedBunles = array_map(
            function ($c) {
                return $this->bundles[$c];
            }, $selected
        );

        $this->selectedBundles = $selectedBunles;

        return $this;
    }

    protected function cleanUp()
    {
        return $this;
    }

    protected function resolveDependencies()
    {
        foreach ($this->selectedBundles as $bundle) {
            $bundle                = new Bundle($bundle);
            $config                = $bundle->getConfig();
            $this->selectedBundles = array_merge($this->selectedBundles, $config['Dependencies']);
        }

        return $this;
    }

    protected function updateConfig()
    {
        foreach ($this->selectedBundles as $bundle) {
            $bundle = new Bundle($bundle);
            $this->project->updateConfigYaml('config', $bundle->getTemplateData('config'));
        }

        return $this;

    }

    protected function updateRouting()
    {
        foreach ($this->selectedBundles as $bundle) {
            $bundle = new Bundle($bundle);
            $this->project->updateConfigYaml('routing', $bundle->getTemplateData('routing'));
        }

        return $this;
    }

    protected function updateParameters()
    {
        foreach ($this->selectedBundles as $bundle) {
            $bundle = new Bundle($bundle);
            $this->project->updateConfigYaml('routing', $bundle->getTemplateData('routing'));
        }

        return $this;
    }

    protected function updateComposer()
    {
        foreach ($this->selectedBundles as $bundle) {
            $bundle = new Bundle($bundle);
            $config = $bundle->getConfig();
            $this->project->updateComposer($config['Composer']);
        }

        $process = new Process('composer update');
        $process->run(
            function ($type, $buffer) {
                if (Process::ERR === $type) {
                    $this->writeError($buffer);
                } else {
                    $this->output->write($buffer);
                }
            });
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
        echo $process->getOutput();

        return $this;
    }

    protected function updateAppKernel()
    {
        foreach ($this->selectedBundles as $bundle) {
            $bundle = new Bundle($bundle);
            $config = $bundle->getConfig();
            $this->project->updateAppKernel($config['AppKernel']);
        }

        return $this;
    }

    protected function displayInstallationResult()
    {
        return $this;
    }


}
