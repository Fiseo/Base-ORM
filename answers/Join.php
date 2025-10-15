<?php

namespace answers;

use Exception;
use Requete\Core\base;

class Join
{
    /***
     * @var string Table à join
     */
    private string $table_to;
    private base $class_to;
    /***
     * @var string Table depuis laquel rejoindre
     */
    private string $table_from;
    private base $class_from;
    /***
     * @var string[] Liste des champs devant être récupéré
     */
    private array $fields;
    /***
     * @var string  champ d'id servant à faire la jointure
     */
    private string $id_field;

    public function getTableTo(): string
    {
        return $this->table_to;
    }

    public function getIdField(): string
    {
        return $this->id_field;
    }

    public function getTableFrom(): string
    {
        return $this->table_from;
    }

    public function setTableTo(string $table):bool{
        if(!empty($this->table_to)){
            unset($this->table_to);
            unset($this->table_from);
            unset($this->fields);
            unset($this->id_field);
        }
        try{
            if(base::tableExist('"' . $table . '"')){
                throw new Exception("La table '$table' n'existe pas");
            }
            $classname = "Requete\\" . $table . "Repository";
            $this->class_to = new $classname();
            $this->table_to = $table;
            return true;
        }catch (Exception $e){
            echo $e->getMessage();
        }
        return false;
    }

    protected function tableToSet():void{
        if (empty($this->table_to)) {
            throw new Exception("La table à joindre n'a pas été définie");
        }
    }

    public function setIdField(string $id_field):bool{
        try {
            $this->tableToSet();

            if (!$this->class_to->inFields($id_field)) {
                throw new Exception("Le champ '$id_field' n'existe pas dans la table à joindre");
            }

            if(!empty($this->class_from) && !$this->class_from->inFields($id_field)) {
                throw new Exception("Le champ '$id_field' n'existe pas dans la table depuis laquel rejoindre");
            }

            $this->id_field = $id_field;
            return true;

        }catch (Exception $e){
            echo $e->getMessage();
        }
        return false;
    }

    public function setTableFrom(string $table):bool{
        try{
            $this->tableToSet();
            if(!base::tableExist($table)){
                throw new Exception("La table '$table' n'existe pas");
            }
            if (!$this->class_to->inTableLink($table)) {
                throw new Exception("Cette table n'est pas lié à la table à joindre");
            }

            $classname = "Requete\\" . $table . "Repository";
            $this->class_from = new $classname();

            if (!empty($this->id_field) && !$this->class_from->inFields($this->id_field)) {
                unset($this->class_from);
                throw new Exception("Le champ '$this->id_field' n'est pas présent dans cette table");
            }

            $this->table_from = $table;
            return true;

        }catch (Exception $e){
            echo $e->getMessage();
        }
        return false;
    }

    private function verifyFields(string $field):void{
        if(!$this->class_to->inFields($field)){
            throw new Exception("Le champ " . $field ." n'existe pas dans la table à joindre");
        }

        if (!empty($this->fields) && in_array($field, $this->fields)) {
            throw new Exception("Le champ " .  $field . " existe déja dans les fields");
        }

        $this->fields[] = $field;
    }

    public function addInFields(array|string $fields):bool
    {
        try {
            $this->tableToSet();

            if (is_array($fields)) {
                foreach ($fields as $field) {
                    if(is_string($field)) {
                        $this->verifyFields($field);
                    }
                }
            }
            else{
                $this->verifyFields($fields);
            }


            return true;

        } catch (Exception $e) {
            echo $e->getMessage();
        }
        return false;
    }

    public function getFields():array{
        if(empty($this->fields)){
            return [];
        }
        return $this->fields;
    }

    public function ready2Use():bool{
        if (empty($this->id_field)) {
            return false;
        }
        return true;
    }

    /***
     * @return bool
     * retourne un true si la variable tablefrom a été initialisé
     */
    public function tableFromSet():bool{
        return !empty($this->table_from);
    }

}