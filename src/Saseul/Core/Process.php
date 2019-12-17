<?php

namespace Saseul\Core;

use COM;
use Saseul\Core\Debugger;
use Saseul\Constant\Directory;
use Saseul\Util\Logger;

class Process
{
    # warning: Unix / Windows
    public static function spawn(string $service_name)
    {
        if (extension_loaded('pcntl')) {
            # Unix
            $php = $_SERVER['_'];
            $args = [Directory::SERVICE_BIN, $service_name];

            switch ($pid = pcntl_fork()) {
                case 0:
                    Debugger::info("spawn: ".$service_name);
                    @umask(0);
                    pcntl_exec($php, $args);
                    break;
                case -1:
                    Debugger::info("fork error");
                    exit();
                    break;
                default:
                    break;
            }

        } elseif (extension_loaded('com_dotnet')) {
            # TODO: Windows, need path;
            $command = 'php '.Directory::SERVICE_BIN.' '.$service_name;

            try {
                Debugger::info("spawn: ".$service_name);
                $handle = new COM('WScript.Shell');
                $handle->run($command, 0, false);
            } catch (\Exception $e) {
                Debugger::info($e);
                Debugger::info("fork error");
            }

        } else {
            Debugger::info("fork error");
            exit();
        }
    }

    public static function isRunning($pid): bool
    {
        if (empty($pid)) {
            return false;
        }

        if (extension_loaded('posix')) {
            return posix_kill($pid, 0);

        } elseif (extension_loaded('com_dotnet')) {
            # Windows 10;
            $command = 'TASKLIST /NH /FO "CSV" /FI "PID eq '.$pid.'"';
            $handle = new COM('WScript.Shell');
            $rs = $handle->exec($command)->StdOut->ReadAll;
            $rs = explode('","', $rs);

            return isset($rs[1]);

        } else {
            Debugger::info("Can't check pid. ");
            exit();
        }
    }

    public static function destroy($pid): void
    {
        if (extension_loaded('posix')) {
            # linux
            posix_kill($pid, SIGKILL);

        } elseif (extension_loaded('com_dotnet')) {
            # Windows 10;
            $command = 'TASKKILL /PID '.$pid.' /F';
            $handle = new COM('WScript.Shell');
            $handle->exec($command);

        } else {
            Debugger::info("Can't kill process. ");
            exit();
        }
    }

    public static function killDefunct($pid): void
    {
        if (extension_loaded('pcntl')) {
            for ($i = 0; $i < 10; $i++) {
                pcntl_waitpid($pid, $status);
                usleep(100000);

                if (!self::isRunning($pid)) {
                    break;
                }
            }
        }
    }
}
