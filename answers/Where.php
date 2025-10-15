<?php

namespace answers;

use Requete\Core\base;

class Where
{
    private string $table;
    private base $class;
    private string $wherefield;
    private mixed $wherevalue;
    private bool $different = false;
    private bool $list = false;
    private bool $like = false;
    private string $salt = "";

    /**
     * @param string $table
     * @return bool
     */
    public function setTable(string $table): bool
    {
        if(!empty($this->table)){
            unset($this->table);
            unset($this->class);
            unset($this->wherefield);
            unset($this->wherevalue);
        }
        if(base::tableExist($table)){
            $this->table = $table;
            $classname = "Requete\\" . $table ."Repository";
            $this->class = new $classname();
            return true;
        }
        return false;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * @param mixed $wherevalue
     */
    public function setWherevalue(mixed $wherevalue): void
    {
        if(is_array($wherevalue)){
            $this->list = true;
            $this->wherevalue = array_values($wherevalue);
        }else{
            $this->wherevalue = $wherevalue;
        }
    }

    /**
     * @param string $wherefield
     * @return bool
     */
    public function setWherefield(string $wherefield): bool
    {

        if(!empty($this->class) && $this->class->inFields($wherefield)){
            $this->wherefield = $wherefield;
            return true;
        }
        return false;
    }

    /***
     * Si different est à true. Alors le where sera "Where Field != Value"
     * Valeur par défaut = False
     * @param bool $different
     */
    public function setDifferent(bool $different): void
    {
        $this->different = $different;
    }

    public function setLike(bool $like): bool
    {
        if(is_string($this->wherevalue)){
            $this->like = true;
            return true;
        }
        return false;
    }

    public function setSalt(string $salt): bool{
        $tmp = preg_replace('/[^a-zA-Z0-9]/', '', $salt);
        if (!empty($tmp)) {
            $this->salt = $tmp;
            return true;
        }
        return false;
    }

    public function ready2Use(): bool{
        if(!empty($this->wherevalue) && !empty($this->wherefield)){
            return true;
        }
        return false;
    }

    public function getWhere(bool $alone = true): string{
        if($alone){
            $where =  " WHERE ";
        }else{
            $where = " ";
        }
        $where .= $this->table . "." . $this->wherefield;

        if($this->list){
            if ($this->different){
                $where .= " NOT IN (";
            }else{
                $where .= " IN (";
            }

            foreach ($this->wherevalue as $key=> $value){
                $where .= ":" . $this->wherefield . $key;
                if($key != array_key_last($this->wherevalue)){
                    $where .= ", ";
                }
            }
            return $where . ")";
        }elseif ($this->like){
            if ($this->different){
                $where .= " NOT LIKE ";
            }else{
                $where .= " LIKE ";
            }
            return $where . ":" . $this->wherefield . $this->salt;
        }else {
            if ($this->different){
                $where .= " != ";
            }else{
                $where .= " = ";
            }
            return $where . ":" . $this->wherefield . $this->salt;
        }

    }

    public function doBindValue(\PDOStatement $pdo):void{
        if($this->list){
            foreach ($this->wherevalue as $key => $value){
                $pdo->bindValue(":" . $this->wherefield . $key, $value);
            }
        }elseif($this->like){
            $pdo->bindValue(":" . $this->wherefield . $this->salt, '%' . $this->wherevalue . '%' );
        }
        else{
            $pdo->bindValue(":" . $this->wherefield . $this->salt, $this->wherevalue);
        }
    }

}