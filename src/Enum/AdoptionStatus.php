<?php

namespace App\Enum;

enum AdoptionStatus: string
{
    case Pending = 'En attente';
    case Accepted = 'Acceptée';
    case Rejected = 'Refusée';
}
