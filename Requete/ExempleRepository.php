<?php

namespace Requete;
class ExempleRepository extends Core\EntityRepository
{
    protected static array $fields = [
        'FieldExemple1',
        'FieldExemple2',
    ];
    protected static string $entity = 'Exemple';
    protected static array $entitylinked = [
        'Entity1' => 'IdEntity',    //L'identifiant représente la table liée. La valeur représente la clé étrangère.
        'Entity2' => null           //Lorsque la clé étrangère n'est pas dans la table actuelle la valeur est null.
    ];
}