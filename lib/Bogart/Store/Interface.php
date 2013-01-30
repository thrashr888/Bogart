<?php

namespace Bogart\Store;

interface Interface
{
  public
    $connected = false;
  
  public
    $class,
    $conn,
    $dbname;
  
  public function connect($dbname = null, $config = array());
  
  public function find($collection, $query = null);
  public function findOne($collection, $query = null);
  public function count($collection, $query = null);
  public function insert($collection, &$value = null, $safe = true);
  public function update($collection, $query, &$value = null, $options = null);
  public function remove($collection, $query, $options = null);
}