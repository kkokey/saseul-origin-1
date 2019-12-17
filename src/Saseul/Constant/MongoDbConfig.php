<?php
namespace Saseul\Constant;

class MongoDbConfig
{
    public const DB_SASEUL = 'saseul_core';

    public const COLLECTION_BLOCK = 'block';
    public const COLLECTION_TRANSACTION = 'transaction';
    public const COLLECTION_GENERATION = 'generation';
    public const COLLECTION_TRACKER = 'tracker';
    public const COLLECTION_AUTHORITY = 'authority';
    public const COLLECTION_CODE = 'code';
    public const COLLECTION_PEER = 'peer';
    public const COLLECTION_KNOWN_HOSTS = 'known_hosts';

    public const NAMESPACE_BLOCK = self::DB_SASEUL.'.'.self::COLLECTION_BLOCK;
    public const NAMESPACE_TRANSACTION = self::DB_SASEUL.'.'.self::COLLECTION_TRANSACTION;
    public const NAMESPACE_GENERATION = self::DB_SASEUL.'.'.self::COLLECTION_GENERATION;
    public const NAMESPACE_TRACKER = self::DB_SASEUL.'.'.self::COLLECTION_TRACKER;
    public const NAMESPACE_AUTHORITY = self::DB_SASEUL.'.'.self::COLLECTION_AUTHORITY;
    public const NAMESPACE_CODE = self::DB_SASEUL.'.'.self::COLLECTION_CODE;
    public const NAMESPACE_PEER = self::DB_SASEUL.'.'.self::COLLECTION_PEER;
    public const NAMESPACE_KNOWN_HOSTS = self::DB_SASEUL.'.'.self::COLLECTION_KNOWN_HOSTS;
}
