<?php

namespace answers;

use Exception;
use Requete\Core\EntityRepository;

class Join
{
    private EntityRepository $entityFrom;
    private EntityRepository $entityTo;

    private function hasEntityFrom():bool {
        return !empty($this->entityFrom);
    }

    private function hasEntityTo():bool {
        return !empty($this->entityTo);
    }

    //region getter and Setter
    public function getEntityFrom(): string
    {
        return $this->entityFrom::getName();
    }

    public function setEntityFrom(string $entity): void
    {
        if (!$this->hasEntityTo())
            throw new Exception("La table à rejoindre n'a pas été renseigné.");

        if (EntityRepository::doEntityExist($entity)) {
            if($this->entityTo::isLinked($entity)) {
                $entityRepository = "\\Requete\\" . $entity . "Repository";
                $this->entityFrom = new $entityRepository();
            } else
                throw new Exception("La table " . $entity . " n'est pas liée à la table " . $this->getEntityTo() . ".");
        } else {
            throw new Exception("La table $entity n'existe pas.");
        }
    }

    public function getEntityTo(): string
    {
        return $this->entityTo::getName();
    }
    public function setEntityTo(string $entity): void
    {
        $this->reset();
        if (EntityRepository::doEntityExist($entity)) {
            $entityRepository = $entity . "Repository";
            $this->entityTo = new $entityRepository();
        } else
            throw new Exception("La table $entity n'existe pas.");

    }
    //endregion

    public function getQuery(array &$entityAvailable):string {
        if (!$this->hasEntityTo())
            throw new Exception("La table à rejoindre n'a pas été renseigné.");

        if ($this->hasEntityFrom()) {
            $valid = true;
            foreach ($entityAvailable as $entity) {
                if ($entity()::getName() == $this->getEntityFrom()) {
                    $valid = false;
                    break;
                }
            }

            if (!$valid)
                throw new Exception("La table de départ n'est pas disponible.");
        }

        if (!$this->hasEntityFrom()) {
            foreach ($entityAvailable as $entity) {
                if ($entity()::isLinked($this->getEntityTo())) {
                    $this->setEntityFrom($entity::getName());
                    break;
                }
            }
        }

        if (!$this->hasEntityFrom())
            throw new Exception("La table " . $this->getEntityTo() . " est injoignable.");

        $entityAvailable[] = $this->getEntityTo();

        $field1 = $this->entityFrom::getLink($this->getEntityTo());
        $field2 = $this->entityTo::getLink($this->getEntityFrom());

        if ($field1[array_key_first($field1)] == null)
            $field1[array_key_first($field1)] = "id";
        if ($field2[array_key_first($field2)] == null)
            $field2[array_key_first($field2)] = "id";

        return  "INNER JOIN " . $this->getEntityTo()
                . " ON " . array_key_first($field1) . "." . $field1[array_key_first($field1)]
                . " = "
                . array_key_first($field2) . "." . $field2[array_key_first($field2)];
    }

    public function reset(): void
    {
        unset($this->entityFrom);
        unset($this->entityTo);
    }

}