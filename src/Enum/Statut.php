<?php
// src/Enum/Statut.php

namespace App\Enum;

enum Statut: string
{
    case EN_CREATION = 'en creation';
    case OUVERT = 'ouvert';
    case FERME = 'ferme';
    case EN_COURS = 'en cours';
    case ANNULE = 'annule';
}