<?php

declare(strict_types=1);

namespace RNIDS\Xml\Session;

use RNIDS\Session\Dto\PollRequest;
use RNIDS\Xml\XmlComposer;

/**
 * Builds XML payloads for EPP session poll commands.
 */
final class PollRequestBuilder
{
    /**
     * Builds deterministic EPP poll command XML.
     */
    public function build(PollRequest $request, string $clTrid): string
    {
        $poll = '<poll op="' . XmlComposer::escape($request->operation) . '"';

        if (null !== $request->messageId) {
            $poll .= ' msgID="' . XmlComposer::escape($request->messageId) . '"';
        }

        $poll .= '/>';

        return XmlComposer::commandEnvelope($poll, $clTrid);
    }
}
