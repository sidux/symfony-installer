<?php

namespace Symfony\Installer;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Installer\Helper\Project;

/**
 * Class BaseCommand
 * @package Symfony\Installer
 */
class BaseCommand extends Command
{
    /**
     * @var Project
     */
    protected $project;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var Filesystem
     */
    protected $fs;


    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->input = $input;
        $this->project = new Project(getcwd());
        $this->fs = new Filesystem();
    }

    protected function writeError($msg)
    {
        $this->output->writeln("<error>$msg</error>");
    }

    protected function writeInfo($msg)
    {
        $this->output->writeln("<info>$msg</info>");
    }

    protected function writeSuccess($msg)
    {
        $this->output->writeln("<success>$msg</success>");
    }

    protected function exitError($error)
    {
        $this->writeError($error);
        exit;
    }
}
