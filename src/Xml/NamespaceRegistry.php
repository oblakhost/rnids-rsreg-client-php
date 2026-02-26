<?php

declare(strict_types=1);

namespace RNIDS\Xml;

final class NamespaceRegistry
{
    public const EPP = 'urn:ietf:params:xml:ns:epp-1.0';
    public const DOMAIN = 'urn:ietf:params:xml:ns:domain-1.0';
    public const CONTACT = 'urn:ietf:params:xml:ns:contact-1.0';
    public const HOST = 'urn:ietf:params:xml:ns:host-1.0';
    public const RNIDS = 'http://www.rnids.rs/rnids-epp/rnids-1.0';
    public const RNIDS_DOMAIN_EXT = 'http://www.rnids.rs/epp/xml/domain-rnids-ext-1.0';
    public const RNIDS_CONTACT_EXT = 'http://www.rnids.rs/epp/xml/contact-rnids-ext-1.0';

    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        return [
            'contact' => self::CONTACT,
            'contactExt' => self::RNIDS_CONTACT_EXT,
            'domain' => self::DOMAIN,
            'domainExt' => self::RNIDS_DOMAIN_EXT,
            'epp' => self::EPP,
            'host' => self::HOST,
            'rnids' => self::RNIDS,
        ];
    }

    public static function registerXpathNamespaces(\DOMXPath $xpath): void
    {
        foreach (self::all() as $prefix => $namespace) {
            $xpath->registerNamespace($prefix, $namespace);
        }
    }
}
