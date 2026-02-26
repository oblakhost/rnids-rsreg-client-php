<?php

declare(strict_types=1);

namespace RNIDS\Connection;

/**
 * Immutable TLS settings for secure EPP transport connections.
 */
final class TlsConfig
{
    public readonly string $clientCertificatePath;

    public readonly ?string $clientCertificatePassword;

    public readonly ?string $caFilePath;

    public readonly ?string $peerName;

    public readonly bool $allowSelfSigned;

    /**
     * @param string $clientCertificatePath Path to the local client certificate.
     * @param string|null $clientCertificatePassword Optional certificate password.
     * @param string|null $caFilePath Optional CA bundle path for peer verification.
     * @param string|null $peerName Optional expected peer name for TLS validation.
     * @param bool $allowSelfSigned Whether self-signed certificates are accepted.
     */
    public function __construct(
        string $clientCertificatePath,
        ?string $clientCertificatePassword = null,
        ?string $caFilePath = null,
        ?string $peerName = null,
        bool $allowSelfSigned = false,
    ) {
        $this->clientCertificatePath = $clientCertificatePath;
        $this->clientCertificatePassword = $clientCertificatePassword;
        $this->caFilePath = $caFilePath;
        $this->peerName = $peerName;
        $this->allowSelfSigned = $allowSelfSigned;
    }
}
