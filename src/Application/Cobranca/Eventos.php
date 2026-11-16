<?php

namespace Application\Cobranca;

use Domain\Shared\Messaging\Envelope;
use Domain\Shared\Messaging\MessagePublisher;

class Eventos
{
    public function __construct(private MessagePublisher $publisher) {}

    public function publicar(string $type, string $sagaId, string $correlationId, int $osId, array $payload): void
    {
        $this->publisher->publicar(Envelope::criar($type, $sagaId, $correlationId, $osId, $payload));
    }

    public function responder(Envelope $comando, string $type, array $payload): void
    {
        $this->publicar($type, $comando->sagaId, $comando->correlationId, $comando->osId, $payload);
    }
}
