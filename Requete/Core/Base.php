<?php

namespace Requete\Core {

    use answers\Join;
    use answers\Where;
    use Exception;
    use PDO;
    use PDOException;
    use PDOStatement;

    abstract class Base extends LoginServer
    {
        protected static array $fields;
        protected static string $table;
        protected static array $table_link;
        private static array $table_list = [];

        /***
         * Permet de savoir si une table existe ou non.
         * @param string $value Table dont il faut vérifier l'existence
         * @return bool Donne true si la table existe
         */
        static public function tableExist(string $value):bool{
            if (in_array($value, self::$table_list)) {
                return true;
            }
            return false;
        }

        static public function getFields():array {
            return static::$fields;
        }

        /***
         * Vérifie qu'un champ est bien dans une table
         * @param string $value Champ dont il faut vérifier la présence
         * @return bool Donne true si le champ est présent
         */
        public function inFields(string $value):bool{
            if (in_array($value, self::$fields)) {
                return true;
            }
            return false;
        }


        /***
         * Permet de savoir si une table est lié la table actuelle
         * @param string $value Table dont on veut savoir la connexion
         * @return bool Donne True si la liaison existe
         */
        public function inTableLink(string $value):bool{
            if (in_array($value, static::$table_link)) {
                return true;
            }
            return false;
        }

        /***
         * Créer le script SQL pour un insert et effectue les BindValue
         * @param string[] $values Tableau associatif avec le champ comme clé et la donnée à insérer comme valeur
         * @param PDO|null $get_PDO Référence afin de récupérer le pdo
         * @return PDOStatement retourne le PDOStatement à exécuter
         */
        protected function createInsert(array $values, PDO &$get_PDO = null): PDOStatement{
            $insert_values =[];

            foreach ($values as $field => $value) {
                if($this->inFields($field)){
                    $insert_values[$field] = $value;
                }
            }

            $insert = "INSERT INTO " . static::$table . " (";

            foreach ($insert_values as $field => $value) {
                $insert .= $field;
                if ($field != array_key_last($insert_values)) {
                    $insert .= ', ';
                } else {
                    $insert .= ') VALUE (';
                }
            }

            foreach ($insert_values as $field => $value) {
                $insert .= ":" . $field;
                if ($field != array_key_last($insert_values)) {
                    $insert .= ', ';
                } else {
                    $insert .= ');';
                }
            }

            echo $insert;
            $sql = $this->getPdo();
            $get_PDO = $sql;
            $sql = $sql->prepare($insert);

            foreach ($insert_values as $field => $value) {
                $sql->bindValue(":" . $field, $value);
            }

            return $sql;

        }

        /***
         * Créer le script SQL pour un select récupérant certains champs
         * @param string[] $fields Nom des champs à récupérer
         * @return string Donne le script SQL
         * @throws Exception Envoie une Exception si l'array avec les champs est vide
         */
        protected function createSelectColumn(array $fields):string{

            if (empty($fields)) {
                throw new Exception("Tentative de SELECT sur aucun champ");
            }

            $select = "SELECT ";
            foreach ($fields as $key => $value) {

                $select = $select . $value;

                if ($key != array_key_last($fields)) {
                    $select = $select . ", ";
                } else {
                    $select = $select . " FROM " . static::$table;
                }
            }
            return $select;
        }

        /***
         * Créer le script SQL pour un update
         * @param string[] $fields Tableau associatif avec le champ comme clé et la donnée à insérer comme valeur
         * @return string Donne le script SQL
         * @throws Exception Envoie une Exception si l'array avec les champs est vide
         */
        protected function createUpdate(array $fields): string{
            if(empty($fields)){
                throw new Exception("Tentative d'UPDATE avec aucun champ valide");
            }

            $query_update =   "UPDATE " . static::$table
                ." SET ";

            foreach ($fields as $field => $value) {
                $query_update .= $field . " = :" . $field;

                if ($field != array_key_last($fields)) {
                    $query_update .= ", ";
                }
            }
            return $query_update;
        }

        /***
         * Effectue le BindValue d'une requête Update
         * @param PDOStatement $sql PDOStatement sur lequel effectué les BindValue
         * @param array $values Donnée à utiliser pour effectuer les BindValue
         * @return void
         */
        protected function doBindValueUpdate(PDOStatement $sql, array $values): void{
            foreach ($values as $field => $value) {
                $sql->bindValue(":" . $field, $value);
            }
        }

        /***
         * Créer le script SQL pour un INNER JOIN
         * @param Join $join Contient les informations nécessaires à l'Inner Join
         * @return string Donne le script SQL
         */
        protected function createJoin(Join $join):string{
            $query_join = " INNER JOIN ";
            $query_join .= $join->getTableTo() . " ON " . $join->getTableTo() . "." . $join->getIdField() . " = ";

            if($join->tableFromSet()){
                $query_join .= $join->getTableFrom();
            }else{
                $query_join .= static::$table;
            }

            return $query_join . "." . $join->getIdField();
        }

        /***
         * Créer le script SQL pour l'ensemble des Where nécessaires
         * @param Where|array $wheres Contient les informations propres aux Where
         * @return string Donne le script SQL
         */
        protected function createWhere(Where|array $wheres):string{
            $first_done = false;
            $query_where = "";
            foreach ($wheres as $key => $where) {
                $query_where .= $where->getWhere(!$first_done);
                if ($key != array_key_last($wheres)) {
                    $query_where .= " AND";
                    $first_done = true;
                }
            }
            return $query_where;
        }

        /***
         * Execute le BindValue des Where donnés
         * @param PDOStatement $sql PDOStatement sur lequel faire les BindValue
         * @param Where[] $where Contient les informations propres aux Where
         * @return void
         */
        protected function doBindValueWhere(PDOStatement $sql, array $where):void{
            if(!empty($where)){
                foreach ($where as $object) {
                    $object->doBindValue($sql);
                }
            }
        }

        /***
         * Vérifie que les join sont bien utilisable dans le contexte actuel
         * @param Join|Join[] $test_joins Ensemble des Join à vérifier
         * @param string[] $table_available Liste des tables disponibles dans le contexte
         * @return Join[] Donne un tableau comprenant uniquement les Join utilisable
         */
        protected function verifyJoin(Join|array $test_joins, array &$table_available = null):array{

            $join = [];//tableau avec les jointures valides
            $table_available = (empty($table_available)) ? [static::$table] : $table_available; //défini le contexte selon ce qu'il est donné en argument
            $test_joins = ($test_joins instanceof Join) ? [$test_joins] : $test_joins; //met le Join dans un array s'il est seul

            foreach ($test_joins as $test_join) {
                if($test_join->ready2Use()){
                    if($test_join->getTableTo() == static::$table){ //Valide la jointure si la table à joindre est la table de base
                        $join[] = $test_join;
                    }

                    elseif ($test_join->tableFromSet()){
                        if(in_array($test_join->tableFromSet(), $table_available)){ //Valide la jointure si la table d'origine de la jointure est présente dans les tables disponibles
                            $table_available[] = $test_join->getTableTo();
                            $join[] = $test_join;
                        }
                    }

                    else{
                        if($this->inTableLink($test_join->getTableTo())){ //Valide si la table à rejoindre est lié avec la table de base
                            $table_available[] = $test_join->getTableTo();
                            $join[] = $test_join;
                        }
                    }
                }

            }
            return $join;
        }

        /***
         * Vérifie que les where sont bien utilisable dans le contexte actuel
         * @param Where|Where[] $test_wheres Ensemble des where à vérifier
         * @param string[] $table_available liste des tables disponibles dans le contexte
         * @return Where[]|null[] Donne un tableau contenant les wheres utilisables dans le contexte
         */
        protected function verifyWhere(Where|array|null $test_wheres, array $table_available = null):array{

            $where = [];//tableau avec les wheres valides
            $table_available = (empty($table_available)) ? [static::$table] : $table_available; //défini le contexte selon ce qu'il est donné en argument
            $test_wheres = ($test_wheres instanceof Where) ? [$test_wheres] : $test_wheres; //met le Where dans un array s'il est seul

            foreach ($test_wheres as $test_where) {
                if($test_where->ready2Use()){
                    if(in_array($test_where->getTable(), $table_available)){ //Valide la jointure si la table à joindre est la table de base
                        $where[] = $test_where;
                    }
                }
            }
            return $where;
        }

        /***
         * Créer le script SQL pour les fonctions utilisant Join
         * @param Join[] $joins Liste des Join à utiliser
         * @param bool $distinct Si vrai, fera un SELECT DISTINCT à la place d'un SELECT
         * @return string retourne le script SQL
         */
        protected function createQueryThroughTable(array $joins, bool $distinct = false):string{

            if($distinct){
                $query_select = "SELECT DISTINCT ";
            }else{
                $query_select =  "SELECT ";
            }

            $first_done = false;
            foreach ($joins as $join) {//Ajout de chaque champ à récuperer en précisant sa table

                foreach ($join->getFields() as $field) {
                    if($first_done){
                        $query_select = $query_select . ", ";
                    }
                    $query_select .= $join->getTableTo() . "." . $field;
                    $first_done = true;
                }
            }

            $query_select .= " FROM " . static::$table . " ";

            foreach ($joins as $join) {
                $query_select = ($join->getTableTo() != static::$table) ? $query_select . $this->createJoin($join): $query_select;//ajout de l'Inner Join seulement si la jointure n'est pas la table de base
            }
            return $query_select;
        }

        /***
         * Permet de faire un Insert dans la table
         * @param string[] $values Tableau associatif avec le champ comme clé et la donnée à insérer comme valeur
         * @return bool
         */
        public function insertValues(array $values):bool{
            $insert = $this->createInsert($values);
            try{
                if (!$insert->execute()){
                    throw new PDOException("Erreur lors de l'INSERT");
                }
                return true;
            }catch (PDOException $e){
                echo $e->getMessage();//TODO : ajouter l'écriture dans les logs
                return false;
            }
        }


        /***
         * Permet de récupérer seulement certains champs dans une table
         * @param array $columns Liste des champs à récupérer dans la table
         * @param Where|array|null $where Where à appliquer lors de la requête
         * @return bool|array Donne les données récupérées ou False si une erreur est survenu
         */
        public function getColumn(array $columns, Where|array $where = null): bool|array{
            $fields = [];
            foreach ($columns as $field) { //Filtres les champs pour garder seulement les bons
                if ($this->inFields($field)) {
                    $fields[] = $field;
                }
            }
            try{
                $where = (!empty($where)) ? $this->verifyWhere($where) : null;//vérifie les Where s'ils sont présents
                $query_select = $this->createSelectColumn($fields);

                $query_select = (!empty($where)) ? $query_select . $this->createWhere($where) : $query_select;//Ajoute les Where à la requête s'il y en a

                $sql = $this->getPdo()->prepare($query_select);

                if (!empty($where))//Effectue le BindValue des Where s'il y en a
                    $this->doBindValueWhere($sql, $where);

                if (!$sql->execute()){
                    throw new PDOException("Erreur lors de l'execution de la requête");
                }
                return $sql->fetchAll(PDO::FETCH_ASSOC);
            }catch (Exception $e){
                echo $e->getMessage(); //TODO : ajouter l'écriture dans les logs
                return false;
            }

        }

        /***
         * Permet de compter le nombre de lignes
         * @param Where|array|null $where Where à appliquer lors de la requête
         * @return int Donne le nombre de lignes trouvées
         */
        public function count(Where|array $where = null):int{
            try{
                $where = (!empty($where)) ? $this->verifyWhere($where) : null;//vérifie les Where s'ils sont présents

                $query_select =   "SELECT COUNT(*) AS nb 
                        FROM ".static::$table;

                $query_select = (!empty($where)) ? $query_select . $this->createWhere($where) : $query_select;//Ajoute les Where à la requête s'il y en a

                $sql = $this->getPdo()->prepare($query_select);

                if (!empty($where))//Effectue le BindValue des Where s'il y en a
                    $this->doBindValueWhere($sql, $where);


                if(!$sql->execute()){
                    throw new PDOException("Erreur lors du SELECT");
                }
                return $sql->fetchColumn();

            }catch (Exception $e){
                echo $e->getMessage(); //TODO : ajouter l'écriture dans les logs
                return 0;
            }
        }

        /***
         * Permet de mettre à jour les données de la table
         * @param array $values Tableau associatif avec le champ comme clé et la donnée à insérer comme valeur
         * @param Where|array $wheres Where à appliquer lors de la requête
         * @return bool Donne True si la requête s'est bien effectuée
         */
        public function updateLigne(array $values, Where|array $wheres): bool
        {
            try{

                $wheres = (!empty($wheres)) ? $this->verifyWhere($wheres) : null;//vérifie les Where s'ils sont présents
                if (empty($wheres))
                    throw new Exception("Tentative de mise à jour de toutes les données de la table");

                $verified_values = [];
                foreach ($values as $field => $value) {
                    if ($this->inFields($field)) {
                        $verified_values[$field] = $value;
                    }
                }

                $query_update = $this->createUpdate($verified_values);

                foreach ($wheres as $where)
                    $where->setSalt('UPDATE');

                $query_update .= $this->createWhere($wheres);//Ajoute les Where à la requête s'il y en a

                $sql = $this->getPdo()->prepare($query_update);

                $this->doBindValueUpdate($sql, $verified_values);
                $this->doBindValueWhere($sql, $wheres);

                if (!$sql->execute()) {
                    throw new Exception("Erreur lors de la mise à jour");
                }

                return true;

            }catch (Exception $e){
                echo $e->getMessage();//TODO : ajouter l'écriture dans les logs
                return false;
            }
        }

        /***
         * Permet de supprimer des données dans la table
         * @param Where|array $wheres Where à appliquer lors de la requête
         * @return bool Donne True si la requête s'est bien effectuée
         */
        public function deleteLigne(Where|array $wheres): bool{
            try{
                $wheres = (!empty($wheres)) ? $this->verifyWhere($wheres) : null;//vérifie les Where s'ils sont présents

                if (empty($wheres))
                    throw new Exception("Tentative de suppression de toutes les données de la table");

                $query_delete =   "DELETE FROM ". static::$table ;

                $query_delete .= $this->createWhere($wheres);//Ajoute les Where à la requête

                $sql = $this->getPdo()->prepare($query_delete);

                $this->doBindValueWhere($sql, $wheres);

                if(!$sql->execute()){
                    throw new PDOException("Erreur lors de la suppression");
                }
                return true;
            }catch (Exception $e){
                echo $e->getMessage();//TODO : ajouter l'écriture dans les logs
                return false;
            }
        }

        /***
         *Permets de récupérer des données à travers des tables
         * @param Join|Join[] $joins Ensemble des données à récupérer et des informations nécessaires pour les jointures
         * @param Where|Where[]|null $wheres Where à appliquer lors de la requête
         * @param bool $distinct Si vrai, fera un SELECT DISCTINCT à la place d'un SELECT
         * @return array|bool Donne les données récupérées ou false si une erreur est survenu
         */
        public function getFieldsThroughTable(Join|array $joins, Where|array|null $wheres = null, bool $distinct = false): array|bool
        {
            $table_available = [];
            try{
                $joins = $this->verifyJoin($joins, $table_available);

                $wheres = (!empty($wheres)) ? $this->verifyWhere($wheres, $table_available) : null;//vérifie les Where s'ils sont présents

                $query_select = $this->createQueryThroughTable($joins, $distinct);

                $query_select = (!empty($wheres)) ? $query_select . $this->createWhere($wheres) : $query_select;//Ajoute les Where à la requête s'il y en a

                $sql = $this->getPdo()->prepare($query_select);

                if(!empty($wheres)){
                    foreach ($wheres as $where) {
                        $where->doBindValue($sql);
                    }
                }

                if(!$sql->execute()){
                    throw new PDOException("Erreur lors de la selection");
                }

                return $sql->fetchAll(PDO::FETCH_ASSOC);
            }catch (Exception $e){
                echo $e->getMessage();//TODO : ajouter l'écriture dans les logs
                return false;
            }
        }

    }
}