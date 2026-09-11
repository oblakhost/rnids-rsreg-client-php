<?php

declare(strict_types=1);

namespace RNIDS\Xml;

/** Converts internationalized DNS names to the ASCII form required by EPP. */
final class DnsNameEncoder
{
    public static function toAscii(string $name): string
    {
        if (1 !== \preg_match('/[^\x00-\x7F]/', $name)) {
            return $name;
        }

        if (!\function_exists('idn_to_ascii')) {
            throw new \InvalidArgumentException(
                'Internationalized domain and host names require the PHP intl extension or ASCII Punycode input.',
            );
        }

        $ascii = \idn_to_ascii(
            $name,
            IDNA_NONTRANSITIONAL_TO_ASCII | IDNA_USE_STD3_RULES | IDNA_CHECK_BIDI | IDNA_CHECK_CONTEXTJ,
            INTL_IDNA_VARIANT_UTS46,
        );
        if (false === $ascii) {
            throw new \InvalidArgumentException('Domain or host name cannot be encoded as a valid IDN.');
        }

        return \rtrim($ascii, '.');
    }
}
