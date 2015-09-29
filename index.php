<?php
    require 'paths.php';
    require 'database.php';
    require 'node.php';
    require 'nodemanager.php';
    require 'cronjobDatabase.php';
    
    // Objekt ersstellen und alle Informationen in die Datenbank schreiben
    $cronDatabase = new CronjobDatabase();
    $cronDatabase->updateNodesInDB();
?>