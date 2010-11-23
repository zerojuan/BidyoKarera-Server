<?php

/*
CLASS   : DbConnection
PURPOSE : Contains logic and error handling for a MySql database connection
*/
class DbConnection
{
  var $db = null;

  var $server = "localhost";
  var $username = "root";
  var $password = "";
  var $schema = "tadapokerdb";

  // Open - opens a database connection and selects the default schema
  function Open()
  {
    // attempt to connect to the database
    $this->db = mysql_connect($this->server, $this->username, $this->password);
    if (!$this->db)
      echo mysql_error();
    else
    {
      // select default schema
      if (!mysql_select_db($this->schema, $this->db))
      {
        echo mysql_error($this->db);
        $this->Close();
      }
    }
  }

  // Close - close the database connection
  function Close()
  {
    if ($this->db != null)
      mysql_close($this->db);
    $this->db = null;
  }

  // GetDataSet - obtain an array of records
  function GetDataSet($sql)
  {
    $rs = $this->_execQuery($sql);
    $recs = array();
    while (($rec = mysql_fetch_row($rs)) != null)
      array_push($recs, $rec);
    mysql_free_result($rs);
    return $recs;
  }

  // _execQuery - Public clients should not use this function
  // This is a helper class for derived classes
  function _execQuery($sql)
  {
    $rs = mysql_query($sql);
    if (!$rs)
    {
      echo mysql_error($this->db);
      $this->Close();
      exit;
    }
    return $rs;
  }
}

/*
CLASS   : DataObject
PURPOSE : Manages a database connection for a given data object
        : Public clients should only set the $Connection to an open DbConnection
        : If left null (the default) the class will manage its own connection
        : Public clients shouldn't use any other member variables or methods
        : as these are for derived classes only
*/
class DataObject
{
  var $Connection = null;
  var $ownsCn = false;

  function _open()
  {
    $this->ownsCn = ($this->Connection == null);
    if ($this->ownsCn)
    {
      $this->Connection = new DbConnection();
      $this->Connection->Open();
    }
    return $this->Connection;
  }

  function _close()
  {
    if ($this->ownsCn)
    {
      $this->Connection->Close();
      $this->Connection = null;
    }
  }

  function _execQuery($sql)
  {
    $cn = $this->_open();
    return $cn->_execQuery($sql);
  }

  function _execQueryAndClose($sql)
  {
    $cn = $this->_open();
    $cn->_execQuery($sql);
    $this->_close();
  }
}

/*
CLASS   : DbRecord
PURPOSE : Public class to perform simple database operations such as
          Get,Insert,Update and Delete.
*/
class DbRecord extends DataObject
{
  // Get - obtain a single record as an array of field values.
  function Get($sql)
  {
    $rs = parent::_execQuery($sql);
    $rec = mysql_fetch_assoc($rs);
    mysql_free_result($rs);
    parent::_close();
    return $rec;
  }

  // Insert - insert a record an obtain the inserted record id
  function Insert($sql)
  {
    parent::_execQuery($sql);
    $recId = mysql_insert_id();
    parent::_close();
    return $recId;
  }

  // Update - update record(s) according to the supplied $sql text
  function Update($sql)
  {
    parent::_execQueryAndClose($sql);
  }

  // Delete - delete record(s) according to the supplied $sql text
  function Delete($sql)
  {
    parent::_execQueryAndClose($sql);
  }
}

class DbReader extends DataObject
{
  var $sql;
  var $rs;

  function DbReader($sql)
  {
    $this->sql = $sql;
  }

  function Open()
  {
    $this->rs = parent::_execQuery($this->sql);
  }

  function Next(&$rec)
  {
    $rec = mysql_fetch_assoc($this->rs);
    return ($rec != null);
  }

  function Close()
  {
    mysql_free_result($this->rs);
    parent::_close();
  }
}
?>