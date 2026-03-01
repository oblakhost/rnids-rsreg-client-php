<?php

declare(strict_types=1);

namespace RNIDS\Session;

use RNIDS\Session\Dto\HelloResponse;
use RNIDS\Session\Dto\PollResponse;

final class SessionResponseMapper
{
    /**
     * @return array{
     *   extensionUris: list<string>,
     *   languages: list<string>,
     *   objectUris: list<string>,
     *   serverDate: string|null,
     *   serverId: string|null,
     *   versions: list<string>
     * }
     */
    public function mapHelloResponse(HelloResponse $response): array
    {
        return [
            'extensionUris' => $response->extensionUris,
            'languages' => $response->languages,
            'objectUris' => $response->objectUris,
            'serverDate' => $response->serverDate,
            'serverId' => $response->serverId,
            'versions' => $response->versions,
        ];
    }

    /**
     * @return array{
     *   count: int|null,
     *   message: string|null,
     *   messageId: string|null,
     *   queueDate: string|null
     * }
     */
    public function mapPollResponse(PollResponse $response): array
    {
        return [
            'count' => $response->queueCount,
            'message' => $response->message,
            'messageId' => $response->messageId,
            'queueDate' => $response->queueDate,
        ];
    }

    /**
     * @return array{}
     */
    public function mapEmptyResponse(): array
    {
        return [];
    }
}
