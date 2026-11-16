<?php

namespace Domain\Cobranca;

enum StatusPagamento: string
{
    case Pendente = 'pendente';
    case Aprovado = 'aprovado';
    case Recusado = 'recusado';
    case Estornado = 'estornado';
}
