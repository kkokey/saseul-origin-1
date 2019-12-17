<?php

namespace Saseul\Script;

use Saseul\Common\Script;
use Saseul\Constant\Signal;
use Saseul\Core\Process;
use Saseul\Core\Property;
use Saseul\Core\Setup;
use Saseul\Util\Logger;

class Reset extends Script
{
    public function main()
    {
        $setup = new Setup();

        if ($this->ask('Reset? [y/n] ') !== 'y') {
            return;
        }

        Property::daemonSig(Signal::STOP);

        for ($i = 1; $i <= 20; $i++) {
            if (Process::isRunning(Property::daemonPid()) === false) {
                break;
            }
            Logger::log("waiting round ... ({$i})");
            sleep(1);
        }

        if (Process::isRunning(Property::daemonPid()) === true) {
            Logger::error("Can't stop daemon ");
        }

        sleep(2);
        $setup->DeleteFiles();
        $setup->FlushCache();
        $setup->DropDatabase();
        $setup->CreateDatabase();
        $setup->CreateIndex();
        sleep(2);

        Property::init();
        Logger::log('OK, Please restart daemon. ');
    }
}
