<?php

namespace Requete\Core;

abstract class Child extends Base
{

    protected string $parent;
    protected array $parent_compositions = [];

    public function __construct() {
        if (!base::tableExist($this->parent)) {
            throw new \Exception("Table '{$this->parent}' does not exist");
        }
        $classname = "Requete\\" . $this->parent ."Repository";
        $this->parent_compositions = (new $classname())->getFields();
    }
}