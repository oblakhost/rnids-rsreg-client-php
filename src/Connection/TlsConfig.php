<?php

declare(strict_types=1);

namespace RNIDS\Connection;

final class TlsConfig
{
    public readonly string $clientCertificatePath;

    public readonly ?string $clientCertificatePassword;

    public readonly ?string $caFilePath;

    public readonly ?string $peerName;

    public readonly bool $allowSelfSigned;

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
