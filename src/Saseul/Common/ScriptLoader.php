<?php

namespace Saseul\Common;

use Saseul\Constant\Directory;
use Saseul\Core\Debugger;
use Saseul\Core\Env;
use Saseul\Util\File;
use Saseul\Util\Logger;
use Saseul\Util\Parser;

class ScriptLoader
{
    private $argv;
    private $setup_env_script;
    private $setup_account_script;

    public function __construct($argv)
    {
        $this->argv = Parser::escapeArg($argv);
        $this->setup_env_script = 'Setup'.DIRECTORY_SEPARATOR.'Env';
        $this->setup_account_script = 'Setup'.DIRECTORY_SEPARATOR.'Account';

        if (!isset($this->argv[1])) {
            $this->argv[1] = '';
        }
    }

    public function main(): void
    {
        $env_load = Env::load();

        if ($env_load === false) {
            $msg = "Env file load failed. Env file : " . Directory::ENV_FILE;

            Debugger::info($msg);
            Logger::debug($msg);

            $this->execScript($this->setup_env_script);

            return;
        }

        Env::apply();
        $script = $this->route($this->argv[1]);

        if ($script === '') {
            $this->showAllScripts();
        } else {
            $this->execScript($script);
        }
    }

    public function route(string $arg): string
    {
        $script_file = Directory::SCRIPT.DIRECTORY_SEPARATOR.$arg;

        if (is_file($script_file.'.php')) {
            return $arg;
        }

        return '';
    }

    public function showAllScripts()
    {
        echo PHP_EOL;
        echo 'You can run like this ' . PHP_EOL;
        echo ' $ saseul_script <script_name>';
        echo PHP_EOL;
        echo PHP_EOL;
        echo 'This is script lists. ' . PHP_EOL;

        $scripts = File::getFiles(Directory::SCRIPT, Directory::SCRIPT);
        $scripts = preg_replace('/\.php$/', '', $scripts);

        foreach ($scripts as $script) {
            echo ' - ' . $script . PHP_EOL;
        }

        echo PHP_EOL;
    }

    public function execScript($script)
    {
        $prefix = str_replace(Directory::SRC.DIRECTORY_SEPARATOR, '', Directory::SCRIPT);
        $script = $prefix.DIRECTORY_SEPARATOR.$script;
        $script = preg_replace('/\\'.DIRECTORY_SEPARATOR.'/', '\\', $script);
        $script = preg_replace('/\\\\{2,}/', '\\', $script);

        $arg = [];

        if (count($this->argv) > 2) {
            $arg = $this->argv;
            unset($arg[0]);
            unset($arg[1]);

            $arg = array_values($arg);
        }

        $target = new $script();
        $target->setArg($arg);
        $target->exec();
    }
}
