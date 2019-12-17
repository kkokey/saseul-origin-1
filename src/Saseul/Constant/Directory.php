<?php

namespace Saseul\Constant;

class Directory
{
    public const ROOT = ROOT_DIR;
    public const SRC = self::ROOT.DIRECTORY_SEPARATOR.'src';
    public const SASEUL = self::SRC.DIRECTORY_SEPARATOR.'Saseul';

    public const ENV_FILE = self::ROOT.DIRECTORY_SEPARATOR.'saseul-env.json';
    public const CONFIG_FILE = self::ROOT.DIRECTORY_SEPARATOR.'saseul-config.json';

    public const DEBUG_LOG_FILE = self::ROOT.DIRECTORY_SEPARATOR.'debug.log';
    public const SASEULD_PID_FILE = self::ROOT.DIRECTORY_SEPARATOR.'saseuld.pid';
    public const CONSENSUS_PID_FILE = self::ROOT.DIRECTORY_SEPARATOR.'consensussvc.pid';

    public const SCRIPT_BIN = self::SRC.DIRECTORY_SEPARATOR.'saseul_script';
    public const SERVICE_BIN = self::SRC.DIRECTORY_SEPARATOR.'saseulsvc';

    public const API = self::SASEUL.DIRECTORY_SEPARATOR.'Api';
    public const SCRIPT = self::SASEUL.DIRECTORY_SEPARATOR.'Script';
    public const SERVICE = self::SASEUL.DIRECTORY_SEPARATOR.'Service';
    public const SYSTEM_CONTRACT = self::SASEUL.DIRECTORY_SEPARATOR.'Contract';
    public const SYSTEM_STATUS = self::SASEUL.DIRECTORY_SEPARATOR.'Status';
    public const SYSTEM_REQUEST = self::SASEUL.DIRECTORY_SEPARATOR.'Request';

    public const BLOCKDATA = self::ROOT.DIRECTORY_SEPARATOR.'blockdata';
    public const CONTRACTDATA = self::ROOT.DIRECTORY_SEPARATOR.'contractdata';
    public const TEMP = self::ROOT.DIRECTORY_SEPARATOR.'tmp';

    public const API_CHUNKS = self::BLOCKDATA.DIRECTORY_SEPARATOR.'apichunks';
    public const BROADCAST_CHUNKS = self::BLOCKDATA.DIRECTORY_SEPARATOR.'broadcastchunks';
    public const TRANSACTIONS = self::BLOCKDATA.DIRECTORY_SEPARATOR.'transactions';
    public const TX_ARCHIVE = self::BLOCKDATA.DIRECTORY_SEPARATOR.'txarchives';
    public const GENERATIONS = self::BLOCKDATA.DIRECTORY_SEPARATOR.'generations';

    public const CUSTOM_CONTRACT = self::CONTRACTDATA.DIRECTORY_SEPARATOR.'contract';
    public const CUSTOM_STATUS = self::CONTRACTDATA.DIRECTORY_SEPARATOR.'status';
    public const CUSTOM_REQUEST = self::CONTRACTDATA.DIRECTORY_SEPARATOR.'request';

    public const TMP_BUNCH = self::TEMP.DIRECTORY_SEPARATOR.'bunch.tar.gz';
}