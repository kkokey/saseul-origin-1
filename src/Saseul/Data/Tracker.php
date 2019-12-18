<?php

namespace Saseul\Data;

use Saseul\Constant\MongoDbConfig;
use Saseul\Constant\Role;
use Saseul\Core\Key;
use Saseul\Core\Property;
use Saseul\Core\Rule;
use Saseul\System\Database;
use Saseul\Util\Parser;
use Saseul\Util\RestCall;

class Tracker
{
    public static function addKnownHosts(string $host): void
    {
        $db = Database::GetInstance();

        $db->bulk->update(
            ['host' => $host],
            ['$set' => [
                'host' => $host,
            ]],
            ['upsert' => true]
        );

        if ($db->bulk->count() > 0) {
            $db->BulkWrite(MongoDbConfig::NAMESPACE_KNOWN_HOSTS);
        }
    }

    public static function addPeers(array $items): void
    {
        $db = Database::GetInstance();

        # peer;
        foreach ($items as $item) {
            $host = $item['host'] ?? null;
            $address = $item['address'] ?? '';

            if (Key::isValidAddressSize($address) && $host !== null) {
                $db->bulk->update(
                    ['host' => $host],
                    ['$set' => [
                        'host' => $host,
                        'address' => $address,
                    ]],
                    ['upsert' => true]
                );
            }
        }

        if ($db->bulk->count() > 0) {
            $db->BulkWrite(MongoDbConfig::NAMESPACE_PEER);
        }

        # known_hosts;
        foreach ($items as $item) {
            $host = $item['host'] ?? null;

            if ($host !== null) {
                $db->bulk->update(
                    ['host' => $host],
                    ['$set' => [
                        'host' => $host,
                    ]],
                    ['upsert' => true]
                );
            }
        }

        if ($db->bulk->count() > 0) {
            $db->BulkWrite(MongoDbConfig::NAMESPACE_KNOWN_HOSTS);
        }
    }

    public static function removePeers(array $items): void
    {
        $db = Database::GetInstance();

        foreach ($items as $host) {
            $db->bulk->delete(['host' => $host]);
        }

        if ($db->bulk->count() > 0) {
            $db->BulkWrite(MongoDbConfig::NAMESPACE_PEER);
        }
    }

    public static function removeKnownhosts(array $items): void
    {
        $db = Database::GetInstance();

        foreach ($items as $host) {
            $db->bulk->delete(['host' => $host]);
        }

        if ($db->bulk->count() > 0) {
            $db->BulkWrite(MongoDbConfig::NAMESPACE_KNOWN_HOSTS);
        }
    }

    public static function addTracker(
        string $address, string $role = 'light', string $status = 'admitted'): void
    {
        $db = Database::GetInstance();

        if (Key::isValidAddressSize($address) && Role::isExist($role))
        {
            $db->bulk->update(
                ['address' => $address],
                ['$set' => [
                    'role' => $role,
                    'status' => $status
                ]],
                ['upsert' => true]
            );
        }

        if ($db->bulk->count() > 0) {
            $db->BulkWrite(MongoDbConfig::NAMESPACE_TRACKER);
        }
    }

    public static function getPeers($query = [], $limit = Rule::PEER_SEARCH)
    {
        $count = self::countPeers();
        $skip = 0;

        if ($count > $limit) {
            $skip = rand(0, ($count - $limit));
        }

        $db = Database::GetInstance();
        $opt = [
            'limit' => $limit,
            'skip' => $skip
        ];

        $rs = $db->Query(MongoDbConfig::NAMESPACE_PEER, $query, $opt);
        $nodes = [];

        foreach ($rs as $item) {
            $host = $item->host ?? null;
            $address = $item->address ?? null;

            if (Key::isValidAddressSize($address) && $host !== null) {
                $nodes[] = [
                    'host' => $host,
                    'address' => $address
                ];
            }
        }

        return $nodes;
    }

    public static function countPeers()
    {
        $db = Database::GetInstance();
        $command = ['count' => MongoDbConfig::COLLECTION_PEER];
        $rs = $db->Command(MongoDbConfig::DB_SASEUL, $command);
        $count = 0;

        foreach ($rs as $item) {
            $count = $item->n;
            break;
        }

        return $count;
    }

    public static function getAccessibleValidators()
    {
        $db = Database::GetInstance();
        $query = [
            'role' => Role::VALIDATOR,
            'status' => ['$ne' => 'ban']
        ];

        $rs = $db->Query(MongoDbConfig::NAMESPACE_TRACKER, $query);
        $validators = [];

        foreach ($rs as $item) {
            $address = $item->address ?? '';

            if (Key::isValidAddressSize($address)) {
                $validators[] = $address;
            }
        }

        $query = ['address' => ['$in' => $validators]];
        $peers = self::getPeers($query);

        return $peers;
    }

    public static function getKnownHosts($exclude_hosts, $limit = Rule::PEER_SEARCH)
    {
        $count = self::countKnownHosts();
        $skip = 0;

        if ($count > $limit) {
            $skip = rand(0, ($count - $limit));
        }

        $db = Database::GetInstance();

        $query = ['host' => ['$nin' => $exclude_hosts]];
        $opt = [
            'limit' => $limit,
            'skip' => $skip
        ];

        $rs = $db->Query(MongoDbConfig::NAMESPACE_KNOWN_HOSTS, $query, $opt);
        $hosts = [];

        foreach ($rs as $item) {
            $host = $item->host ?? null;

            if ($host !== null) {
                $hosts[] = $host;
            }
        }

        return $hosts;
    }

    public static function countKnownHosts()
    {
        $db = Database::GetInstance();
        $command = ['count' => MongoDbConfig::COLLECTION_KNOWN_HOSTS];
        $rs = $db->Command(MongoDbConfig::DB_SASEUL, $command);
        $count = 0;

        foreach ($rs as $item) {
            $count = $item->n;
            break;
        }

        return $count;
    }

    public static function checkPeers($hosts)
    {
        $rest = RestCall::GetInstance();
        $url_path = '/checkpeer';
        $checksum = Rule::checksum();
        $data = ['checksum' => $checksum];

        $rs = $rest->multiPOST($hosts, $url_path, $data);
        $peers = [];

        foreach ($rs as $item)
        {
            $result = json_decode($item['result'], true);
            $host = $item['host'] ?? '';

            if (isset($result['data']) && $host !== '') {
                $data = $result['data'];
                $public_key = $data['public_key'] ?? '';
                $address = $data['address'] ?? '';
                $signature = $data['signature'] ?? '';
                $validity = Key::isValidAddress($address, $public_key)
                    && Key::isValidSignature($checksum, $public_key, $signature);

                if ($validity === true) {
                    $peers[] = [
                        'host' => $host,
                        'address' => $address
                    ];
                }
            }
        }

        if (count($peers) > 0) {
            self::addPeers($peers);
        }
    }

    public static function hostsFromPeers(array $peers): array
    {
        $hosts = [];

        foreach ($peers as $peer) {
            $host = $peer['host'] ?? '';

            if ($host !== '') {
                $hosts[] = $peer['host'];
            }
        }

        return $hosts;
    }

    public static function pullPeerHosts(): array
    {
        $rest = RestCall::GetInstance();
        $hosts = self::hostsFromPeers(self::getPeers());

        $url_path = '/peer';
        $rs = $rest->multiPOST($hosts, $url_path);
        $new_hosts = [];

        foreach ($rs as $item)
        {
            $result = json_decode($item['result'], true);
            $peers = $result['data'] ?? [];

            if (is_array($peers)) {
                $new_hosts = self::hostsFromPeers($peers);
            }
        }

        return array_merge($hosts, $new_hosts);
    }

    public static function registerRequest(string $host): void
    {
        if (self::isPeer($host)) {
            return;
        }

        $r = Property::registerRequest();

        if (empty($r)) {
            $r = [];
        }

        if (count($r) < Rule::PEER_SEARCH) {
            Property::registerRequest(array_unique(array_merge($r, [$host])));
        }
    }

    public static function excludeRequest(string $host, bool $force = false)
    {
        $e = Property::excludeRequest();

        if (empty($e)) {
            $e = [];
        }

        if (count($e) < Rule::PEER_SEARCH) {
            Property::excludeRequest(array_unique(array_merge($e, [$host])));
        }

        self::removePeers([$host]);

        if ($force === true)
        {
            self::removeKnownhosts([$host]);
        }
    }

    public static function isPeer($host): bool
    {
        $db = Database::GetInstance();
        $query = ['host' => $host];
        $rs = $db->Query(MongoDbConfig::NAMESPACE_PEER, $query);

        foreach ($rs as $item) {
            $host = $item->host ?? null;

            if ($host !== null) {
                return true;
            }
        }

        return false;
    }

    public static function getValidatorAddress()
    {
        $db = Database::GetInstance();
        $query = ['role'=> Role::VALIDATOR];
        $rs = $db->Query(MongoDbConfig::NAMESPACE_TRACKER, $query);
        $addresses = [];

        foreach ($rs as $item) {
            $address = $item->address ?? '';

            if (Key::isValidAddressSize($address)) {
                $addresses[] = $address;
            }
        }

        return $addresses;
    }

    public static function ban(string $address)
    {
        $db = Database::GetInstance();

        if (Key::isValidAddressSize($address))
        {
            $db->bulk->update(
                ['address' => $address],
                ['$set' => [
                    'status' => 'ban'
                ]]
            );
        }

        if ($db->bulk->count() > 0) {
            $db->BulkWrite(MongoDbConfig::NAMESPACE_TRACKER);
        }
    }

    public static function isValidator(string $address)
    {
        $db = Database::GetInstance();
        $query = [
            'address' => $address,
            'role' => Role::VALIDATOR,
        ];
        $command = [
            'count' => MongoDbConfig::COLLECTION_TRACKER,
            'query' => $query,
        ];

        $rs = $db->Command(MongoDbConfig::DB_SASEUL, $command);
        $count = 0;

        foreach ($rs as $item) {
            $count = $item->n;
            break;
        }

        if ($count > 0) {
            return true;
        }

        return false;
    }

    public static function getRole(string $address)
    {
        $db = Database::GetInstance();
        $query = ['address' => $address];

        $rs = $db->Query(MongoDbConfig::NAMESPACE_TRACKER, $query);
        $role = Role::LIGHT;

        foreach ($rs as $item) {
            $role = $item->role ?? Role::LIGHT;
            break;
        }

        return $role;
    }

    # TODO: need ping test;
    public static function getRandomValidator()
    {
        $validators = self::getAccessibleValidators();
        $count = count($validators);
        $pick = rand(0, $count - 1);

        if (count($validators) > 0) {
            return $validators[$pick];
        }

        return [];
    }
}
