<?php

namespace Symfony\Installer;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ZipArchive;

class GenerateGMMEntitiesCommand extends BaseCommand
{

    protected $api = 'https://api.genmymodel.com';

    protected function configure()
    {
        $this
            ->setName('generate-gmm-entities')
            ->addArgument('user')
            ->addArgument('project')
            ->addArgument('generator')
            ->addOption('token', 't', InputOption::VALUE_REQUIRED, 'Provide connexion token')
            ->addOption('overwrite', 'o')
            ->setDescription('Call GenMyModelApi to get doctrine entities ')
            ->setAliases(['generate:aw:gmm:entity']);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input  = $input;
        $this->output = $output;

        $userToken     = $this->input->getOption('token');
        $username      = $input->getArgument('user') ?: 'awstudio';
        $projectName   = $input->getArgument('project');
        $generatorName = $input->getArgument('generator') ?: 'DoctrineAWGenerator';

        if (!$projectName) {
            $project = $this->getUserOpenedProject($username);
        } else {
            $project = $this->getUserProjects($username, $projectName);
        }

        if ($project && isset($project['projectId'])) {
            $projectId = $project['projectId'];
        } else {
            $this->writeError('No opened project found nor projectId provided');
            exit;
        }

        $generator = $this->getUserGenerators($username, $generatorName);
        if (!$generator) {
            $this->writeError('Generator not found');
            exit;
        }
        $generatorId = $generator['generatorId'];

        $url  = "{$this->api}/usergenerators/$generatorId/run?projectId=$projectId";
        $data = '{"kind":"ZIP"}';

        $response = $this->requestApi($url, $data, $userToken);


        if (empty($response['links'][0]['href'])) {
            $this->exitError('Empty result');
        }

        $zip  = $response['links'][0]['href'];
        $temp = tempnam(sys_get_temp_dir(), 'zip');
        copy($zip, $temp);
        $zip     = new ZipArchive;
        $success = $zip->open($temp);
        if (!$success) {
            $this->exitError('Impossible to extract ' . $response['links'][0]['href']);
        }
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename    = $zip->getNameIndex($i);
            $fileContent = $zip->getFromIndex($i);
            $filePath    = $this->getFilePath($filename, $fileContent);
            if (!file_exists($filePath) || $input->getOption('overwrite')) {
                //                    if (strstr($filePath, 'Enum')) {
                //                        $yaml  = Yaml::dump($array, $inline);
                //                        $value = $yaml->parse(file_get_contents());
                //                    }
                file_put_contents($filePath, $fileContent);
            }
        }
        $zip->close();
        unlink($temp);
    }

    private function getFilePath($fileName, $fileContent)
    {
        $directory = 'AppBundle/Entity';
        if (preg_match('/namespace ([^;]*);/', $fileContent, $match)) {
            $directory = str_replace(['\\'], ['/'], $match[1]);
        }
        $directory = $this->project->getSrcDir() . $directory;
        if (!file_exists($directory)) {
            $old = umask(0);
            if (!@mkdir($directory, 0777, true) && !is_dir($directory)) {
                $this->writeError("Could not create directory $directory");
            }
            umask($old);
        }

        return $directory . '/' . basename($fileName);

    }

    private function getUserOpenedProject($user)
    {
        $projects = json_decode(file_get_contents("https://api.genmymodel.com/users/$user/projects"), true);

        foreach ($projects as $project) {
            if ($project['projectStatus'] === 'OPEN') {
                return $project;
            }
        }

        return false;
    }

    private function getUserProjects($user, $projectName = false)
    {
        $projects = json_decode(file_get_contents("https://api.genmymodel.com/users/$user/projects"), true);

        foreach ($projects as $project) {
            if ($project['name'] == $projectName) {
                return $project;
            }
        }

        return false;
    }

    private function getUserGenerators($user, $generatorName = false)
    {
        $generators = json_decode(file_get_contents("https://api.genmymodel.com/users/$user/generators"), true);

        if ($generatorName) {
            foreach ($generators as $generator) {
                if ($generator['name'] == $generatorName) {
                    return $generator;
                }
            }
        }

        return $generators;
    }

    private function login()
    {
        $data     = [
            'username' => 'contact@awstudio.fr',
            'password' => 'AW5tudi0',
        ];
        $response = $this->requestApi(
            $this->api . '/login',
            http_build_query($data),
            false, 'POST', 'application/x-www-form-urlencoded'
        );

        return $response;
    }

    private function requestApi($url, $data, $token = false, $method = 'POST', $type = 'application/json')
    {
        $curl = curl_init();
        curl_setopt_array(
            $curl, [
                     CURLOPT_URL            => $url,
                     CURLOPT_RETURNTRANSFER => true,
                     CURLOPT_CUSTOMREQUEST  => $method,
                     CURLOPT_POSTFIELDS     => $data,
                     CURLOPT_HTTPHEADER     => [
                         $token ? "authorization: Bearer $token" : '',
                         "content-type: $type",
                     ],
                 ]
        );
        $err = curl_error($curl);
        if ($err) {
            $this->writeError($err);
        }

        $response = json_decode(curl_exec($curl), true);
        //        $info = curl_getinfo($curl);
        curl_close($curl);

        if (!empty($response['message']) && $response['message'] !== 'SUCCESS') {
            $this->exitError($response['message'] . ' : ' . $response['message']);
        }

        if (!empty($response['error'])) {
            $this->exitError($response['error'] . ' : ' . $response['error_description']);
        }

        return $response;
    }
}
