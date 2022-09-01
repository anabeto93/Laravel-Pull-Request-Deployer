<?php

namespace App\Services;

use App\DTOs\ApiResponse;
use App\Models\PullRequest;

class DeploymentService
{
    public function deploy(array $properties): ApiResponse
    {
        $port = 8081;
        $sslPort = 445;

        $latest = PullRequest::latest()->first();

        if ($latest) {
            $port = $latest->port ?? $port;
            $sslPort = $latest->ssl_port ?? $sslPort;
        }

        // increment the ports by 1
        $port += 1;
        $sslPort += 1;

        // first generate a unique url token
        $token = strtolower(generate_unique_url());
        $url = $token . "." . config('deployment.url');

        // create the app storage path
        $deploymentPath = config('deployment.path');
        $storedPath = $deploymentPath . "/" . $token;

        // copy the files from the given path to the storedPath
        $givenPath = $properties['path'];
        shell_exec("cp -Rf {$givenPath} {$storedPath}");
        // yh I know, ignore it and let's continue

        //change the docker-compose contents
        $this->modifyDockerComposeContents($token, $storedPath, $port, $sslPort);

        // create nginx config for this deployment
        $this->createNginxConfig($token, $storedPath, $url, $port);

        $request = PullRequest::create([
            'branch' => $properties['branch'],
            'commit' => $properties['commit'],
            'path' => $properties['path'],
            'url' => $url,
            'stored_path' => $storedPath,
            'port' => $port,
            'ssl_port' => $sslPort,
            'token' => $token,
        ]);

        return ApiResponse::success("Success", data: $request->toArray());
    }

    protected function modifyDockerComposeContents(string $token, string $storedPath, int $port, int $sslPort): void
    {
        $network = $token . "_network";
        $image = "default/laravel" . "-{$token}";
        $container = "test-laravel" . "-{$token}";
        $server_container = "test-webserver" . "-{$token}";

        $file = "{$storedPath}/docker-compose.yml";

        // replace the network name
        $this->replaceLineInFile("default_network", $network, $file);
        // replace the image
        $this->replaceLineInFile("default/laravel", $image, $file);
        // replace the container
        $this->replaceLineInFile("test-laravel", $container, $file);
        // replace the nginx webserver
        $this->replaceLineInFile("test-webserver", $server_container, $file);
        // replace the nginx port
        $this->replaceLineInFile("8081:80", "{$port}:80", $file);
        // replace the ssl port too
        $this->replaceLineInFile("445:443", "{$sslPort}:443", $file);
    }

    protected function createNginxConfig(string $token, string $storedPath, string $url, $port): void
    {
        $configFile = storage_path('configs') . "/nginx.conf";
        $configFile = str_replace("//", "/", $configFile);

        // create a copy of it
        $file = str_replace("nginx.conf", "nginx2.conf", $configFile);

        shell_exec("cp {$configFile} {$file}"); // yh I know, leave it

        $rootDir = $storedPath . "/public";

        // replace the demo url with provided
        $this->replaceLineInFile("test.homeserver.com www.test.homeserver.com", "{$url} www.{$url}", $file);
        $this->replaceLineInFile("root /var/www/html/api/public;", "root {$rootDir};", $file);

        // replace the default upstream
        $this->replaceLineInFile("upstream app", "upstream {$token}");
        $this->replaceLineInFile("server http://localhost:8000;", "server http://localhost:{$port};");

        //move the config to nginx configs
        shell_exec("sudo mv {$file} /etc/nginx/sites-available/{$token}.conf");
        shell_exec("sudo ln -s /etc/nginx/sites-available/{$token}.conf /etc/nginx/sites-enabled/");
        shell_exec("sudo service nginx reload");
    }

    private function replaceLineInFile(string $original, string $replacement, $file = "docker-compose.yml")
    {
        $temp = "{$file}.tmp";

        $reading = fopen($file, "r");
        $writing = fopen($temp, "w");

        $replaced_line = false;

        while (!feof($reading)) {
            $line = fgets($reading);

            if (stristr($line, $original)) {
                $line = str_replace($original, $replacement, $line);
                $replaced_line = true;
            }

            fputs($writing, $line);
        }

        fclose($reading);
        fclose($writing);

        // replace the file if only the line was replaced
        if ($replaced_line) {
            shell_exec("cat {$temp} > $file");
        }
        unlink($temp);
    }
}