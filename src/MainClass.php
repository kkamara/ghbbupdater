#!/usr/bin/env php
<?php

namespace App;

require_once(__DIR__."/../vendor/autoload.php");

use Exception;
use stdClass;

set_time_limit(1200); // 20 minutes.

class MainClass extends stdClass
{
    protected string $version = "1.0.0";

    protected string $appName = "Github to Bitbucket Backup Repo Updater";

    protected Array|null $out = null;

    public function __construct() {}

    public function handle() {        
        try {
            $bitbucketDir = implode(
                "/", 
                [
                    getcwd(), 
                    "bitbucket",
                ]
            );
            echo "Making bitbucket directory: " .
                $bitbucketDir . PHP_EOL;
            $bitbucketDirExists = true === file_exists($bitbucketDir);
            echo "Directory already exists? " . 
                ($bitbucketDirExists ? "true" : "false") .
                PHP_EOL;
            if ($bitbucketDirExists) {
                throw new Exception(
                    "The bitbucket folder already exists in current directory." .
                    PHP_EOL
                );
            }
            mkdir($bitbucketDir);

            echo "Created directory: $bitbucketDir" .
                PHP_EOL;
            
            $config = json_decode(
                file_get_contents(implode("/", [
                    getcwd(),
                    "config.json"
                ])),
                true
            );

            $keys = array_keys($config["repos"]);
            for($i = 0; $i < sizeof($keys); $i++) {
                $repoName = $keys[$i];
                echo "Updating repo: $repoName".PHP_EOL;

                exec(
                    "git clone " . 
                    $config["repos"][$repoName]
                        ["github"]
                        ["origin"] . " " .
                    implode("/", [
                        getcwd(),
                        "bitbucket",
                        $repoName
                    ])
                );

                chdir(implode("/", [
                    getcwd(),
                    "bitbucket",
                    $repoName
                ]));

                exec(
                    "git remote add bitbucket " .
                        $config["repos"][$repoName]
                            ["bitbucket"]
                            ["origin"], 
                    $this->out
                );
                echo "Git replied: " . 
                    implode("\n", $this->out) .
                    PHP_EOL;
                $this->out = null;
                
                echo "Pushing to bitbucket " .
                    $config["repos"][$repoName]
                        ["bitbucket"]
                        ["branch"] .
                        PHP_EOL;

                exec(
                    "git push bitbucket " .
                        $config["repos"][$repoName]
                            ["bitbucket"]
                            ["branch"], 
                    $this->out
                );
                echo "Git replied: " . 
                    implode("\n", $this->out) .
                    PHP_EOL;
                $this->out = null;

                chdir(implode("/", [
                    getcwd(),
                    "..",
                    ".."
                ]));

                $this->repoCleanup($repoName);

                echo "Successfully updated repo: $repoName" .
                    PHP_EOL;
            }

            $this->cleanup();
        } catch (Exception $e) {
            echo print_r($e->getMessage(), true);
        }
    }

    protected function repoCleanup($repoName) {
        $fullRepoPath = implode("/", [
            getcwd(),
            "bitbucket",
            $repoName
        ]);
        echo "Removing directory " .
            $fullRepoPath . PHP_EOL;
        exec("rm -rf $fullRepoPath");
        echo "Removed directory " .
            $fullRepoPath . PHP_EOL;
    }

    protected function cleanup() {
        $fullBitbucketPath = implode("/", [
            getcwd(),
            "bitbucket"
        ]);
        echo "Removing directory " .
            $fullBitbucketPath . PHP_EOL;
        exec("rm -rf $fullBitbucketPath");
        echo "Removed directory " .
            $fullBitbucketPath . PHP_EOL;
    }
}

(new MainClass())->handle();
