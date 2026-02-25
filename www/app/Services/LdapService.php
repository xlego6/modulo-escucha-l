<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class LdapService
{
    protected $enabled;
    protected $host;
    protected $port;
    protected $baseDn;
    protected $bindFormat;
    protected $domain;
    protected $netbios;
    protected $useSsl;
    protected $useStarttls;
    protected $timeout;

    public function __construct()
    {
        $this->enabled = (bool) config('ldap.enabled', false);
        $this->host = (string) config('ldap.host', '');
        $this->port = (int) config('ldap.port', 389);
        $this->baseDn = (string) config('ldap.base_dn', '');
        $this->bindFormat = strtoupper((string) config('ldap.bind_format', 'UPN'));
        $this->domain = (string) config('ldap.domain', '');
        $this->netbios = (string) config('ldap.netbios', '');
        $this->useSsl = (bool) config('ldap.use_ssl', false);
        $this->useStarttls = (bool) config('ldap.use_starttls', false);
        $this->timeout = (int) config('ldap.timeout', 5);
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function isExtensionInstalled(): bool
    {
        return function_exists('ldap_connect');
    }

    public function canAuthenticate(): bool
    {
        return $this->isEnabled() && $this->isExtensionInstalled();
    }

    public function validateUser(string $email, string $password): bool
    {
        $connection = $this->bind($email, $password);

        if (!$connection) {
            return false;
        }

        @ldap_unbind($connection);
        return true;
    }

    public function getUserLdap(string $email, string $password): ?array
    {
        $connection = $this->bind($email, $password);

        if (!$connection) {
            return null;
        }

        try {
            $safeEmail = $this->escapeFilter($email);
            $filter = "(mail={$safeEmail})";
            $attributes = ['givenName', 'sn', 'displayName', 'mail'];

            $search = @ldap_search($connection, $this->baseDn, $filter, $attributes);
            if (!$search) {
                return null;
            }

            $entries = @ldap_get_entries($connection, $search);
            if (!is_array($entries) || (int) ($entries['count'] ?? 0) < 1) {
                return null;
            }

            $entry = $entries[0];

            $givenName = $this->entryValue($entry, 'givenname');
            $sn = $this->entryValue($entry, 'sn');
            $displayName = $this->entryValue($entry, 'displayname');
            $mail = $this->entryValue($entry, 'mail') ?: $email;

            if (empty($displayName)) {
                $displayName = trim("{$givenName} {$sn}");
            }

            return [
                'given_name' => $givenName,
                'last_name' => $sn,
                'display_name' => $displayName ?: $mail,
                'email' => strtolower(trim($mail)),
            ];
        } finally {
            @ldap_unbind($connection);
        }
    }

    protected function bind(string $email, string $password)
    {
        if (!$this->enabled || empty($this->host) || empty($this->baseDn) || empty($password)) {
            Log::warning('LDAP bind omitido por configuración incompleta o deshabilitada.', [
                'enabled' => $this->enabled,
                'host' => $this->host,
                'base_dn' => $this->baseDn,
            ]);
            return null;
        }

        if (!function_exists('ldap_connect')) {
            Log::error('Extensión LDAP no disponible en PHP.');
            return null;
        }

        $usernameSam = explode('@', trim($email))[0] ?? trim($email);
        $bindUser = $this->resolveBindUser($usernameSam);
        $host = $this->useSsl ? "ldaps://{$this->host}" : $this->host;

        $connection = @ldap_connect($host, $this->port);

        if (!$connection) {
            Log::error('No fue posible crear conexión LDAP.', [
                'host' => $host,
                'port' => $this->port,
            ]);
            return null;
        }

        @ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        @ldap_set_option($connection, LDAP_OPT_REFERRALS, 0);
        @ldap_set_option($connection, LDAP_OPT_NETWORK_TIMEOUT, $this->timeout);

        if ($this->useStarttls && !$this->useSsl) {
            if (!@ldap_start_tls($connection)) {
                Log::error('No fue posible iniciar StartTLS en LDAP.', [
                    'host' => $host,
                    'port' => $this->port,
                    'ldap_error' => @ldap_error($connection),
                ]);
                @ldap_unbind($connection);
                return null;
            }
        }

        if (!@ldap_bind($connection, $bindUser, $password)) {
            Log::warning('LDAP bind fallido.', [
                'host' => $host,
                'port' => $this->port,
                'bind_user' => $bindUser,
                'ldap_error' => @ldap_error($connection),
                'ldap_errno' => @ldap_errno($connection),
            ]);
            @ldap_unbind($connection);
            return null;
        }

        return $connection;
    }

    protected function resolveBindUser(string $usernameSam): string
    {
        if ($this->bindFormat === 'DOMAIN_SAM') {
            if (empty($this->netbios)) {
                return $usernameSam;
            }

            return $this->netbios . '\\' . $usernameSam;
        }

        if (empty($this->domain)) {
            return $usernameSam;
        }

        return $usernameSam . '@' . $this->domain;
    }

    protected function entryValue(array $entry, string $field): string
    {
        if (!isset($entry[$field]) || !is_array($entry[$field]) || !isset($entry[$field][0])) {
            return '';
        }

        return (string) $entry[$field][0];
    }

    protected function escapeFilter(string $value): string
    {
        if (function_exists('ldap_escape')) {
            return ldap_escape($value, '', LDAP_ESCAPE_FILTER);
        }

        return str_replace(
            ['\\', '*', '(', ')', "\x00"],
            ['\\5c', '\\2a', '\\28', '\\29', '\\00'],
            $value
        );
    }
}
