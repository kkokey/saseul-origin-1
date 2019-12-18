<?php

namespace Saseul\Script\Network;

use Custom\Contract\C1;
use Custom\Contract\C2;
use Custom\Contract\C3;
use Custom\Contract\C4;
use Custom\Contract\C5;
use Custom\Contract\C6;
use Custom\Status\S1;
use Custom\Status\S2;
use Saseul\Common\Script;
use Saseul\Constant\Directory;
use Saseul\Constant\Form;
use Saseul\Constant\MongoDbConfig;
use Saseul\Core\Env;
use Saseul\Core\Key;
use Saseul\Core\Rule;
use Saseul\Data\Tracker;
use Saseul\Util\DateTime;
use Saseul\Util\Logger;
use Saseul\Util\RestCall;
use Saseul\Version;

class RegisterSampleCode extends Script
{
    function __construct()
    {
        Env::registerErrorHandler();
    }

    function main()
    {
        $s1 = Directory::SASEUL.DIRECTORY_SEPARATOR
            .'SampleCode'.DIRECTORY_SEPARATOR
            .'Status'.DIRECTORY_SEPARATOR
            .'RoleToken.php';

        $s2 = Directory::SASEUL.DIRECTORY_SEPARATOR
            .'SampleCode'.DIRECTORY_SEPARATOR
            .'Status'.DIRECTORY_SEPARATOR
            .'Coin.php';

        $c1 = Directory::SASEUL.DIRECTORY_SEPARATOR
            .'SampleCode'.DIRECTORY_SEPARATOR
            .'Contract'.DIRECTORY_SEPARATOR
            .'GenesisRoleToken.php';

        $c2 = Directory::SASEUL.DIRECTORY_SEPARATOR
            .'SampleCode'.DIRECTORY_SEPARATOR
            .'Contract'.DIRECTORY_SEPARATOR
            .'SendValidatorToken.php';

        $c3 = Directory::SASEUL.DIRECTORY_SEPARATOR
            .'SampleCode'.DIRECTORY_SEPARATOR
            .'Contract'.DIRECTORY_SEPARATOR
            .'SendNetworkManagerToken.php';

        $c4 = Directory::SASEUL.DIRECTORY_SEPARATOR
            .'SampleCode'.DIRECTORY_SEPARATOR
            .'Contract'.DIRECTORY_SEPARATOR
            .'GenesisCoin.php';

        $c5 = Directory::SASEUL.DIRECTORY_SEPARATOR
            .'SampleCode'.DIRECTORY_SEPARATOR
            .'Contract'.DIRECTORY_SEPARATOR
            .'SendCoin.php';

        $c6 = Directory::SASEUL.DIRECTORY_SEPARATOR
            .'SampleCode'.DIRECTORY_SEPARATOR
            .'Contract'.DIRECTORY_SEPARATOR
            .'Farming.php';

        require_once($s1);
        require_once($s2);
        require_once($c1);
        require_once($c2);
        require_once($c3);
        require_once($c4);
        require_once($c5);
        require_once($c6);

        $s1_object = S1::GetInstance();
        $s2_object = S2::GetInstance();
        $c1_object = new C1();
        $c2_object = new C2();
        $c3_object = new C3();
        $c4_object = new C4();
        $c5_object = new C5();
        $c6_object = new C6();

        $this->testStatus($s1_object);
        $this->testStatus($s2_object);
        $this->testContract($c1_object);
        $this->testContract($c2_object);
        $this->testContract($c3_object);
        $this->testContract($c4_object);
        $this->testContract($c5_object);
        $this->testContract($c6_object);

        $items = [];

        $items[] = $this->registerCode($s1, $s1_object, Form::STATUS);
        $items[] = $this->registerCode($s2, $s2_object, Form::STATUS);
        $items[] = $this->registerCode($c1, $c1_object, Form::CONTRACT);
        $items[] = $this->registerCode($c2, $c2_object, Form::CONTRACT);
        $items[] = $this->registerCode($c3, $c3_object, Form::CONTRACT);
        $items[] = $this->registerCode($c4, $c4_object, Form::CONTRACT);
        $items[] = $this->registerCode($c5, $c5_object, Form::CONTRACT);
        $items[] = $this->registerCode($c6, $c6_object, Form::CONTRACT);

        $validator = Tracker::getRandomValidator();
        $host = $validator['host'] ?? '';
        $rest = RestCall::GetInstance();

        if ($host === '') {
            Logger::log('There is no validators ');
        }

        foreach ($items as $item) {
            $rs = $rest->post('http://'.$host.'/transaction', $item);
            Logger::log($rs);
        }

        Logger::log('OK. ');
    }

    function registerCode($filename, $object, $form)
    {
        $class_name = get_class($object);
        $cid = preg_replace('/.+\\\/', '', $class_name);
        $code = file_get_contents($filename);

        $type = 'RegisterCode';
        $transaction = [
            'type' => $type,
            'version' => Version::CURRENT,
            'from' => Env::getAddress(),
            'timestamp' => DateTime::microtime() + Rule::MICROINTERVAL_OF_CHUNK,
            'code' => $code,
            'form' => $form,
            'cid' => $cid,
        ];

        $thash = Rule::hash($transaction);
        $private_key = Env::getPrivateKey();
        $public_key = Env::getPublicKey();
        $signature = Key::makeSignature($thash, $private_key, $public_key);

        $item = [
            'transaction' => json_encode($transaction),
            'thash' => $thash,
            'public_key' => $public_key,
            'signature' => $signature
        ];

        return $item;
    }

    function testStatus($object): void
    {
        $class_name = get_class($object);
        $interface = class_implements($class_name);
        $object->dbname = MongoDbConfig::DB_TEST;

        $object->_setup();
        $object->_setup();
        $object->_reset();
        $object->_load();
        $object->_preprocess();
        $object->_save();
        $object->_postprocess();

        foreach ($interface as $item)
        {
            if ($item === 'Saseul\Common\StatusInterface') {
                return;
            }
        }
    }

    function testContract($object): void
    {
        $class_name = get_class($object);
        $interface = class_implements($class_name);

        $object->_init([], '', '', '');
        $object->_getValidity();
        $object->_loadStatus();
        $object->_getStatus();
        $object->_makeDecision();
        $object->_setStatus();

        foreach ($interface as $item)
        {
            if ($item === 'Saseul\Common\ContractInterface') {
                return;
            }
        }
    }

}
