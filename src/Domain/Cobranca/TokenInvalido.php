<?php

namespace Domain\Cobranca;

use DomainException;

class TokenInvalido extends DomainException
{
    public function __construct()
    {
        parent::__construct('Token de aprovação inválido ou já utilizado.');
    }
}
