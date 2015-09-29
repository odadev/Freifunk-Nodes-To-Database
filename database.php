<?php

/**
 * Singleton-Datenbankklasse
 *
 * Eine Datenbankklasse, die die Verbindung zur Datenbank mittels PDO's herstellt.
 * 
 * @author Timo Jeske <timo@jeske.email>
 */
class Database {

    /**
     * Datenbank-Host
     * 
     * @access private
     * @var String
     * @static
     */
    private static $dbHost = "127.0.0.1";
    
    /**
     * Datenbank-Username
     * 
     * @access private
     * @var String
     * @static
     */
    private static $dbUsername = "USERNAME";

    /**
     * Datenbank-Passwort
     * 
     * @access private
     * @var String
     * @static
     */
    private static $dbPassword = "PASSWORT";

    /**
     * Datenbankname
     *
     * @access private
     * @var String
     * @static
     */
    static private $dbName = "DBNAME";

    /**
     * Instanz-Objekt
     *
     * In dieser Variable wird das einzige Objekt, welches von dieser Klasse erzeugt wird, gespeichert.
     * @access private
     * @var Database
     * @static
     */
    static private $instance = null;
                
    /**
     * Gibt die einzige Instanz dieser Klasse zurück
     *
     * Diese Funktion gibt die einzige Instanz dieser Klasse zurück. 
     * Existiert noch keine, so erzeugt er diese neu und gibt diese zurück.
     *
     * @return  Database	Das einzige Database-Objekt wird zurückgegeben.	
     */
    static public function getInstance() {
        if (null === self::$instance) {
            try {
                self::$instance = new PDO("mysql:host=" . self::$dbHost . ";dbname=" . self::$dbName, self::$dbUsername, self::$dbPassword);
                self::$instance->query("SET NAMES 'utf8'");
            } catch (PDOException $e) {
                echo ($e->getMessage());
                die();
            }
        }
        return self::$instance;
    }
    
}