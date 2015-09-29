<?php

/**
 * Holt sich alle Informationen zu den Nodes und speichert diese in die Datenbank.
 * 
 * Noch ist das Speichern in der Datenbank für die normalen Node-Informationen
 * relativ überflüssig. Allerdings wird schon mal an die Zukunft gedacht:
 * Der Grundstein für weitere Auswertungen wie beispielswiese:
 *  - wann wurde die Hardware des Knoten XX-XX-XXXXXXX gewechselt?
 *  - wann wurde die Firmware des Knoten XX-XX-XXXXXXX aktualisert?
 *  - ...
 * ist aber schon mal gesetzt.
 * 
 * @author Timo Jeske <timo@jeske.email>
 */
class CronjobDatabase {
    
    private $db;
    
    /**
     * Query zum Hinzufügen der Anzahl an CLients und den Onlinestatus in die DB
     */
    private static $query_addNodeClientinformation = "INSERT INTO node_clients_live (
                     NODE_ID,
                     CLIENTS,
                     TIMESTAMP, 
                     STATE) 
                  VALUES (
                     :id, 
                     :clients, 
                     :timestamp, 
                     :state
                  )";
    
    /**
     * Query zum Bearbeiten der Node-Informationen (auf den aktuellen Stand
     * bringen). Warum? Weil so nicht bei jedem Seitenaufruf die JSON-Datei
     * ausgelesen und verarbeitet werden muss, sondern die Daten von der 
     * Datenbank geladen werden.
     * 
     * In Zukunft könnte hier auch eine Historie gespeichert werden mit dem Ziel
     * herauszufinden, wann sich ein Modell geändert hat oder beispielsweise
     * die Firmware geupdatet wurde).
     */
    private static $query_updateNodeInformation = "UPDATE node SET 
                    HOSTNAME = :hostname, 
                    CLIENTS = :clients, 
                    AUTOUPDATE = :autoupdate, 
                    HARDWARE_MODEL = :hardwareModel, 
                    FIRMWARE_RELEASE = :firmwareRelease, 
                    FIRMWARE_BASE = :firmwareBase,
                    LOADAVG = :loadavg, 
                    MEMORY_USAGE = :memoryUsage, 
                    IP_INTERN = :ipIntern, 
                    IP_EXTERN = :ipExtern, 
                    STATE = :state, 
                    MAC = :mac, 
                    UPTIME = :uptime, 
                    FIRST_SEEN = :firstSeen, 
                    LAST_SEEN = :lastSeen, 
                    NODE_TIMESTAMP = :timestamp 
                WHERE
                    ID = :id
                ";
    
    /**
     * Query zum Hinzufügen von neuen Nodes
     */
    private static $query_addNodeInformation = "INSERT INTO node ( 
                    ID, 
                    HOSTNAME, 
                    CLIENTS, 
                    AUTOUPDATE, 
                    HARDWARE_MODEL, 
                    FIRMWARE_RELEASE, 
                    FIRMWARE_BASE, 
                    LOADAVG, 
                    MEMORY_USAGE, 
                    IP_INTERN, 
                    IP_EXTERN, 
                    STATE, 
                    MAC, 
                    UPTIME, 
                    FIRST_SEEN, 
                    LAST_SEEN, 
                    NODE_TIMESTAMP) 
                VALUES (
                    :id, 
                    :hostname, 
                    :clients,
                    :autoupdate, 
                    :hardwareModel, 
                    :firmwareRelease, 
                    :firmwareBase, 
                    :loadavg, 
                    :memoryUsage, 
                    :ipIntern, 
                    :ipExtern, 
                    :state, 
                    :mac, 
                    :uptime, 
                    :firstSeen, 
                    :lastSeen,
                    :timestamp
                )
                ";
    
    /**
     * Statement für das Hinzufügen der Anzahl an CLients und den Onlinestatus in die DB
     */
    private $stmt_addNodeClientInformation;
    
    /**
     * Statement zum Bearbeiten der Node-Informationen
     */
    private $stmt_updateNodeInformation;
    
    /**
     * Statement zum Hinzufügen von Nodes
     */
    private $stmt_addNodeInformation;
    
    // NodeManager-Objekt mit allen Datein zu den Nodes
    private $nodeManager; 
    
    /**
     * Ländt eine Instanz der Datenbank (PDO) und bereitet die Statements vor
     */
    public function __construct() {
        $this->db = Database::getInstance();
       
        $this->stmt_addNodeClientInformation = $this->db->prepare(self::$query_addNodeClientinformation);
        $this->stmt_updateNodeInformation = $this->db->prepare(self::$query_updateNodeInformation);
        $this->stmt_addNodeInformation = $this->db->prepare(self::$query_addNodeInformation);
        
        // Beschafft die Daten aus der JSON-Datei
        $this->nodeManager = new NodeManager();
    }

    /**
     * Holt alle Nodes auf dem NodeManager und gibt diese als Array zurück
     * 
     * @return  Node[]  Array mit allen Node-Objekten
     */
    private function fetchNodeInformations() {
        // Array mit allen Node-Objekten
        return $this->nodeManager->getAllNodes(); 
    }
    
    /**
     * Fügt eine neue Clients-Node Kombination in die Datenbank ein.
     * Wenn es nicht geklappt hat eventuell loggen, oder einfach auf die 
     * verzichten...
     */
    function addNodeClientInformationToDB($id, $clients, $timestamp, $state) {
        $this->stmt_addNodeClientInformation->bindParam(':id', $id);
        $this->stmt_addNodeClientInformation->bindParam(':clients', $clients);
        $this->stmt_addNodeClientInformation->bindParam(':timestamp', $this->getSQLTimestamp($timestamp));
        if($state) {
            $this->stmt_addNodeClientInformation->bindValue(':state', 1);
        } else {
            $this->stmt_addNodeClientInformation->bindValue(':state', 0);
        }
        $this->stmt_addNodeClientInformation->execute();
    }
    
    /**
     * Falls ein Node schon vorhanden ist, werden die Daten in der Datenbank
     * aktualisiert
     */
    function updateNodesInDB() {
        $nodes = $this->fetchNodeInformations();

        // Wenn NodeID schon existiert, einfach nur Updaten
        // Wenn NodeID noch nicht existiert, dann hinzufügen (insert)
        
        foreach($nodes as $node) {
            $this->stmt_updateNodeInformation->bindParam(':id', $node->getNodeID());
            
            $this->stmt_updateNodeInformation->bindParam(':hostname', $node->getHostname());
            $this->stmt_updateNodeInformation->bindParam(':clients', $node->getClients());
            
            if($node->getAutoUpdate()) {
                $this->stmt_updateNodeInformation->bindValue(':autoupdate', 1);
            } else {
                $this->stmt_updateNodeInformation->bindValue(':autoupdate', 0);
            }
      
            $this->stmt_updateNodeInformation->bindParam(':hardwareModel', $node->getModel());
            
            $firmware = $node->getFirmware();
            $this->stmt_updateNodeInformation->bindParam(':firmwareRelease', $firmware["release"]);
            $this->stmt_updateNodeInformation->bindParam(':firmwareBase', $firmware["base"]);
            $this->stmt_updateNodeInformation->bindParam(':loadavg', $node->getLoadavg());
            $this->stmt_updateNodeInformation->bindParam(':memoryUsage', $node->getMemoryusage());
            
            $extern = "";
            $intern = "";
            foreach ($node->getAddresses() as $address) {
                if(strlen($extern) < strlen($address)) {
                    $extern = $address;
                } else {
                    $intern = $address;
                }
            }
            $this->stmt_updateNodeInformation->bindParam(':ipIntern', $intern);
            $this->stmt_updateNodeInformation->bindParam(':ipExtern', $extern);
            
            if($node->getOnline()) {
                $this->stmt_updateNodeInformation->bindValue(':state', 1);
            } else {
                $this->stmt_updateNodeInformation->bindValue(':state', 0);
            }
            
            $this->stmt_updateNodeInformation->bindParam(':mac', $node->getMac());
            $this->stmt_updateNodeInformation->bindParam(':uptime', $node->getUptime());
            $this->stmt_updateNodeInformation->bindParam(':firstSeen', $this->getSQLTimestamp($node->getFirstSeen()));
            $this->stmt_updateNodeInformation->bindParam(':lastSeen', $this->getSQLTimestamp($node->getLastSeen()));
            
            $this->stmt_updateNodeInformation->bindParam(':timestamp', $this->getSQLTimestamp($this->nodeManager->getTimestamp()));
            
            // Node - CLients in DB speichern
            $this->addNodeClientInformationToDB($node->getNodeID(), $node->getClients(), $this->nodeManager->getTimestamp(), $node->getOnline());

            // Hier noch loggen ob Fehler aufgetreten und wenn ja, beheben!
            $this->stmt_updateNodeInformation->execute();
            
            //  Fügt einen komplett neuen Datensatz hinzu, wenn kein Datensatz
            //  zum Updaten gefunden wurde, da der Node noch nicht existiert
            if($this->stmt_updateNodeInformation->rowCount() != 1) {
                // Ertmal die Funktion nutzen, da es unnötig ist dafür extra 
                // eine neue zu schreiben
                $this->insertoNodesToDB(array($node));
            }
        }
    }
    
    /**
     * Wenn Node noch nicht in Datenbank, dann werden die Daten hinzugefügt
     */
    function insertoNodesToDB($nodes) {       
        foreach($nodes as $node) {
            $this->stmt_addNodeInformation->bindParam(':id', $node->getNodeID());
            
            $this->stmt_addNodeInformation->bindParam(':hostname', $node->getHostname());
            $this->stmt_addNodeInformation->bindParam(':clients', $node->getClients());
            
            if($node->getAutoUpdate()) {
                $this->stmt_addNodeInformation->bindValue(':autoupdate', 1);
            } else {
                $this->stmt_addNodeInformation->bindValue(':autoupdate', 0);
            }
      
            $this->stmt_addNodeInformation->bindParam(':hardwareModel', $node->getModel());
            
            $firmware = $node->getFirmware();
            $this->stmt_addNodeInformation->bindParam(':firmwareRelease', $firmware["release"]);
            $this->stmt_addNodeInformation->bindParam(':firmwareBase', $firmware["base"]);
            $this->stmt_addNodeInformation->bindParam(':loadavg', $node->getLoadavg());
            $this->stmt_addNodeInformation->bindParam(':memoryUsage', $node->getMemoryusage());
            
            $extern = "";
            $intern = "";
            foreach ($node->getAddresses() as $address) {
                if(strlen($extern) < strlen($address)) {
                    $extern = $address;
                } else {
                    $intern = $address;
                }
            }
            $this->stmt_addNodeInformation->bindParam(':ipIntern', $intern);
            $this->stmt_addNodeInformation->bindParam(':ipExtern', $extern);
            
            if($node->getOnline()) {
                $this->stmt_addNodeInformation->bindValue(':state', 1);
            } else {
                $this->stmt_addNodeInformation->bindValue(':state', 0);
            }
            
            $this->stmt_addNodeInformation->bindParam(':mac', $node->getMac());
            $this->stmt_addNodeInformation->bindParam(':uptime', $node->getUptime());
            $this->stmt_addNodeInformation->bindParam(':firstSeen', $this->getSQLTimestamp($node->getFirstSeen()));
            $this->stmt_addNodeInformation->bindParam(':lastSeen', $this->getSQLTimestamp($node->getLastSeen()));
            $this->stmt_addNodeInformation->bindParam(':timestamp', $this->getSQLTimestamp($this->nodeManager->getTimestamp()));
            
            $this->stmt_addNodeInformation->execute();
        }
    }
    
    /**
     * Gibt ein übergebenes date() als SQL-date() zurück
     * @return date Datum in SQL-Zeitformat
     */
    private function getSQLTimestamp($time) {
        return date("Y-m-d G:i:s", strtotime($time));
    }
}