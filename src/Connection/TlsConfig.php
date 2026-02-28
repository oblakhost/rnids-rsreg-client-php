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

    public readonly ?bool $verifyPeer;

    public readonly ?bool $verifyPeerName;

    /**
     * @param string $clientCertificatePath Path to the local client certificate.
     * @param string|null $clientCertificatePassword Optional certificate password.
     * @param string|null $caFilePath Optional CA bundle path for peer verification.
     * @param string|null $peerName Optional expected peer name for TLS validation.
     * @param bool $allowSelfSigned Whether self-signed certificates are accepted.
     * @param bool|null $verifyPeer Optional peer certificate verification toggle.
     * @param bool|null $verifyPeerName Optional peer name verification toggle.
     */
    public function __construct(
        string $clientCertificatePath,
        ?string $clientCertificatePassword = null,
        ?string $caFilePath = null,
        ?string $peerName = null,
        bool $allowSelfSigned = false,
        ?bool $verifyPeer = null,
        ?bool $verifyPeerName = null,
    ) {
        $this->clientCertificatePath = $clientCertificatePath;
        $this->clientCertificatePassword = $clientCertificatePassword;
        $this->caFilePath = $caFilePath;
        $this->peerName = $peerName;
        $this->allowSelfSigned = $allowSelfSigned;
        $this->verifyPeer = $verifyPeer;
        $this->verifyPeerName = $verifyPeerName;
    }
}
