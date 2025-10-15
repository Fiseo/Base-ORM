<?php

namespace Requete;
class ExempleRepository extends Core\TableSimple
{
    protected static array $fields = [
        'ChampExemple1',
        'ChampExemple2',
    ];
    protected static string $table = 'Exemple';
    protected static array $table_link = [
        'LienExemple1'
    ];
}