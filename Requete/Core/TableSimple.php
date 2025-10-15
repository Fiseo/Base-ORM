<?php

namespace Requete\Core;
use answers\Join;
use Exception;
use PDO;
use PDOException;

class TableSimple extends Base
{
    protected string $id_field;

    public function __construct(){
        $this->id_field = "Id" . static::$table;
    }

    /***
     * Permet de faire un Insert dans la table ainsi que de récupérer l'Id de la ligne ajoutée
     * @param string[] $values Tableau associatif avec le champ comme clé et la donnée à insérer comme valeur
     * @return int Donne l'Id de la ligne ajoutée
     */
    public function insertValuesGetId(array $values):int{
        $pdo = null;
        $sql = $this->createInsert($values,$pdo);
        $sql->execute();
        return $pdo->lastInsertId();
    }

    /***
     * Permet de récupérer les données de la ligne d'un certain Id
     * @param int $id Id de la ligne demandée
     * @return array Donne les données de la ligne demandée
     */
    public function getById(int $id):array{
        $select =  "SELECT * 
                    FROM ".static::$table."
                    WHERE ".$this->id_field." = ".$id;
        $sql = $this->getPdo()->prepare($select);
        $sql->execute();
        return $sql->fetchAll(PDO::FETCH_ASSOC)[0];
    }

    /***
     * Permet de récupérer certaines données de la ligne d'un certain Id
     * @param string[] $column Liste des champs à récupérer
     * @param int $id Id de la ligne demandée
     * @return bool|array Donne les données de la ligne demandée ou un false si une erreur est survenue
     */
    public function getColumnById(array $column,int $id): bool|array
    {
        $fields = [];
        foreach ($column as $field) {
            if ($this->inFields($field)) {
                $fields[] = $field;
            }
        }

        try{
            $select = $this->createSelectColumn($fields);

            $select .= " WHERE " . $this->id_field . " = ".$id;

            $sql = $this->getPdo()->prepare($select);
            if(!$sql->execute()){
                throw new Exception("Erreur SQL : " . $sql->errorInfo()[2]);
            }

            return $sql->fetchAll(PDO::FETCH_ASSOC);
        }catch (Exception $e){
            echo $e->getMessage(); //TODO : ajouter l'écriture dans les logs
            return false;
        }
    }

    /***
     * Permet de récupérer l'ensemble des données présente dans une table
     * @param string $order Champ selon lequel ranger les données, par défaut sur l'id de la table
     * @return array Donne les données demandées
     */
    public function getAll(string $order = "id"):array
    {
        if(!$this->inFields($order)){
            $order = $this->id_field;
        }
        $select = "SELECT *
                    FROM ".static::$table."
                    ORDER BY " . $order . " ASC";
        $sql = $this->getPdo()->prepare($select);
        $sql->execute();
        return $sql->fetchAll(PDO::FETCH_ASSOC);

    }

    /***
     *Permet de mettre à jour les données d'une ligne avec un certain id
     * @param int $id Id dont il faut modifier la ligne
     * @param string[] $values Tableau associatif avec le champ comme clé et la donnée à insérer comme valeur
     * @return bool Donne True ou False selon si la requête s'est bien éfféctué
     */
    public function updateLigneOnId(int $id, array $values): bool
    {
        try{
            $verified_values = [];
            foreach ($values as $field => $value) {
                if ($this->inFields($field)) {
                    $verified_values[$field] = $value;
                }
            }

            if(empty($verified_values))
                throw new Exception("Tentative d'UPDATE avec aucun champ valide");

            $query_update = $this->createUpdate($verified_values);

            $query_update .= " WHERE " . $this->id_field . " = " . $id;

            $sql = $this->getPdo()->prepare($query_update);

            $this->doBindValueUpdate($sql, $verified_values);

            if (!$sql->execute())
                throw new Exception("Erreur lors de la mise à jour");

            return true;

        }catch (Exception $e){
            echo $e->getMessage();//TODO : ajouter l'écriture dans les logs
            return false;
        }
    }

    /***
     * Permet de supprimer la ligne d'un Id précis
     * @param int $id Id dont la ligne doit être supprimée
     * @return bool Donne True ou False selon si la requête s'est bien passé ou non
     */
    public function deleteLigneOnId(int $id): bool{
        try{
            $query_delete =   "DELETE FROM ".static::$table."
                    WHERE " . $this->id_field . '= ' . $id;

            $sql = $this->getPdo()->prepare($query_delete);

            if(!$sql->execute())
                throw new PDOException("Erreur lors de la suppression");

            return true;
        }catch (Exception $e){
            echo $e->getMessage();//TODO : ajouter l'écriture dans les logs
            return false;
        }
    }
}