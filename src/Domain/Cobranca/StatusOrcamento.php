<?php

namespace Domain\Cobranca;

enum StatusOrcamento: string
{
    case Pendente = 'pendente';
    case Aprovado = 'aprovado';
    case Recusado = 'recusado';
    case Cancelado = 'cancelado';
}
