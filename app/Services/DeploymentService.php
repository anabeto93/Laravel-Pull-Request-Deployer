<?php

namespace App\Services;

use App\DTOs\ApiResponse;
use App\Models\PullRequest;

class DeploymentService
{
    public function deploy(array $properties): ApiResponse
    {
        $latest = PullRequest::latest()->first();


        $request = PullRequest::create([
            'branch' => $properties['branch'],
            'commit' => $properties['commit'],
            'path' => $properties['path'],
        ]);

        $deploymentPath = config('deployment.path');

        return ApiResponse::success("Success", data: $properties);
    }

    function replaceLineInFile(string $original, string $replacement, $file = "docker-compose-test.yml")
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