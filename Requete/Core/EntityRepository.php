<?php

namespace Requete\Core {

    use answers\Join;
    use answers\Where;
    use Exception;
    use PDO;
    use PDOStatement;

    abstract class EntityRepository
    {
        const ID = "id";
        protected static array $fields;
        protected static string $entity;
        protected static array $entitylinked;

        static public function doEntityExist(string $entity):bool {
            $entities = [];
            if (in_array($entity, $entities)) {
                return true;
            }
            return false;
        }

        static public function hasField(string $field):bool {
            if (in_array($field, self::$fields)) {
                return true;
            }
            return false;
        }

        static public function getName(): string{
            return self::$entity;
        }

        static public function isLinked(string $entity):bool {
            $entityLinked = [];
            foreach (self::$entitylinked as $linkedEntity => $field) {
                $entityLinked[] = $linkedEntity;
            }
            if (in_array($entity, $entityLinked)) {
                return true;
            }
            return false;
        }

        static public function getLink(string $entity):array {
            $result = [];
            foreach (self::$entitylinked as $linkedEntity => $field) {
                if ($linkedEntity == $entity) {
                    $result[$linkedEntity] = $field;
                    break;
                }
            }
            if (empty($result)) {
                throw new Exception("No linked entity found for $entity");
            } else {
                return $result;
            }
        }

        private function verifyValues(array $values):void {
            foreach ($values as $field => $value) {
                if(!self::hasField($field)) {
                    throw new Exception("Field '$field' does not exist");
                }
            }
        }

        private function verifyFields(array $fields):void {
            foreach ($fields as $field) {
                if(!self::hasField($field)) {
                    throw new Exception("Field '$field' does not exist");
                }
            }
        }

        private function getQueryInsert(array $values): string{

            $query = "INSERT INTO " . self::$entity . " (";

            foreach ($values as $field => $value) {
                $query .= $field;
                if ($field != array_key_last($values)) {
                    $query .= ', ';
                } else {
                    $query .= ') VALUE (';
                }
            }

            foreach ($values as $field => $value) {
                $query .= ":" . $field;
                if ($field != array_key_last($values)) {
                    $query .= ', ';
                } else {
                    $query .= ');';
                }
            }

            return $query;
        }

        private function getQueryUpdate(array $values): string{

            $query =   "UPDATE " . self::$entity . " SET ";

            foreach ($values as $field => $value) {
                $query .= $field . " = :" . $field;

                if ($field != array_key_last($values)) {
                    $query .= ", ";
                }
            }
            return $query;
        }

        private function getQueryDelete(): string{
            return "DELETE FROM " . self::$entity;
        }

        private function getQuerySelect(array|string $fields): string{
            if (is_string($fields)) { $fields = [$fields]; }
            $query = "SELECT ";
            foreach ($fields as $key => $field) {

                $query = $query . $field;

                if ($key != array_key_last($fields)) {
                    $query = $query . ", ";
                } else {
                    $query = $query . " FROM " . self::$entity;
                }
            }
            return $query;
        }

        private function getQueryWhere(array|Where $wheres): string{
            $query = "WHERE ";

            if (is_array($wheres)) {
                foreach ($wheres as $key => $where) {
                    $query .= $where->getQuery();
                    if ($key != array_key_last($wheres)) {
                        $query .= " AND ";
                    }
                }
            } else {
                $query .= $wheres->getQuery();
            }
            return $query;
        }

        private function getQueryJoin(array|Join $joins, array $entityAvailable):string{
            $query = " ";
            if (is_array($joins)) {
                foreach ($joins as $key => $join) {
                    $query = $join->getQuery($entityAvailable);
                    if ($key != array_key_last($joins)) {
                        $query .= " ";
                    }
                }
            } else {
                $query = " " . $joins->getQuery($entityAvailable) . " ";
            }
            return $query;
        }

        private function bindValues(PDOStatement $statement, array $values): void{
            foreach ($values as $field => $value) {
                $statement->bindValue(":" . $field, $value);
            }
        }

        protected function bindWhereValues(PDOStatement $statement, array|Where $wheres):void{
            if (is_array($wheres)) {
                foreach ($wheres as $where) {
                    $where->doBindValue($statement);
                }
            } else {
                $wheres->doBindValue($statement);
            }
        }

        public function insert(array $values):int {
            $this->verifyValues($values);
            $sql = $this->getQueryInsert($values);
            $pdo = (new LoginServer())->getPDO();
            $statement =$pdo->prepare($sql);
            $this->bindValues($statement, $values);
            $statement->execute();
            return $pdo->lastInsertId();
        }

        public function update(array $values, Where|array $wheres): void {
            $this->verifyValues($values);
            $sql = $this->getQueryUpdate($values);
            $sql .= $this->getQueryWhere($wheres);
            $statement = (new LoginServer())->getPDO()->prepare($sql);
            $this->bindValues($statement, $values);
            $this->bindWhereValues($statement, $wheres);
            $statement->execute();
        }

        public function delete(Where|array $wheres): void {
            $sql = $this->getQueryDelete();
            $sql .= $this->getQueryWhere($wheres);
            $statement = (new LoginServer())->getPDO()->prepare($sql);
            $this->bindWhereValues($statement, $wheres);
            $statement->execute();
        }

        public function select(array|string $fields,array|Join|null $joins, array|Where|null $wheres):array {
            $this->verifyFields($fields);
            $sql = $this->getQuerySelect($fields);
            if(!empty($joins)) {$sql .= $this->getQueryJoin($joins, [self::getName()]);}
            if(!empty($wheres)) {$sql .= $this->getQueryWhere($wheres);}
            $statement = (new LoginServer())->getPDO()->prepare($sql);
            if (!empty($wheres)) {$this->bindWhereValues($statement, $wheres);}
            $statement->execute();
            return $statement->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}