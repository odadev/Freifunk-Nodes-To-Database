# Freifunk Nodes To Database
Dies ist ein Teil des Repos [Freifunk-Nodes-Information](https://github.com/odadev/Freifunk-Nodes-Informationen) und dient der Speicherung von Node-Informationen in einer MySQL-Datenbank.

# Aktueller Stand
* Daten werden von der JSON geladen und in die Datenbank geschrieben
 * jedes Mal beim Ausführen wird die Tabelle "nodes" aktualisiert
 * jedes Mal beim Ausführen wird die Anzahl der vernundenen Clients + der Status des Nodes und dessen ID in die Tabelle "nodes_clients_live" geschrieben

## Die Tabelle node
Diese Tabelle beinhaltet sämtliche Daten zum Node. Diese kann natürlich beliebig erweitert werden.

Aber Achtung: Hierbei handelt es sich um aktuelle Daten. Die je nach Aktualisierungszeit aus der JSON-Datei stammen.
Diese Tabelle dient im Moment nur dazu, dass nicht jeder, der die Übersicht sehen möchte, jedes Mal (vor allem wenn er aktualisiert), die JSON neu lädt.
Somit wird der Server etwas entlastet und die Daten einfach aus der Datenbank gelesen.

In Zukunft könnte ich mir vorstellen, dass eine Historie mit Änderungen und Zeitpunkt oder andere Informationen gespeichert werden können.

### Anmerkung
Die Tabelle ist nicht normalisiert. Warum? Weil es bisher sowieso nur die aktuellen Daten sind, die jedes Mal überschrieben werden. Wenn mehr verlangt wird, sollte man allerdings über eine vernünftige Normalisierung und somit mehr Tabellen nachdenken!

## Die Tabelle node_clients_live
Diese Tabelle beinhaltet die Anzahl der verbunden Clients, den Online-Status, die Node-ID und den Zeitpunkt. Diese Tabelle wird jedes Mal bei Ausführen des Skriptes mit aktuellen Daten gefüttert.
Anhand dieser Daten kann man eine Historie der Anzahl an verbunden Clients zu Node erstellen.

Diese Historie wäre interessant um herauszufinden zu welchem Zeitpunkt wo wieviel los ist. Beispielsweise wäre bei Events mehr los als sonst. Wenn aber auch so an dem Ort, wo das Event stattfindet, schon sehr viele Clients bei wenig Nodes online sind, dann muss man dort eventuell mehr aufstocken als bei anderen Events und Orten.
Dies kann man natürlich beliebig für sich und seine Community so nutzen, wie man es braucht.

### Zusatz "_live"
Der Zusatz "_live" soll verdeutlichen, dass in dieser Tabelle noch recht aktuelle Daten vorhanden sind. Diese Daten werden - je nach Ausführung des Cronjobs - beispielsweise alle 5 Minuten neu geschrieben. Somit bekommt man für jeden Node für alle 5 Minuten die Anzahl der Clients und den Onlinestatus.

Da das natürlich sehr viele Daten sind, soll ein weiter Cronjob ausgeführt werden. Dieser schaut nach, ob die Daten älter als ein gewisser Zeitraum sind (beispielsweise 48 Stunden oder auch nur ein Jahr o.Ä., da die Daten nicht so viel Speicher benötigen), verdichtet die Daten und schreibt diese in eine andere Tabelle "node_clients_archive". Natürlich werden die Daten dann in der "_live"-Tabelle gelöscht.

### Zusatz "_archive"
In dieser Tabelle befinden sich die verdichteten Daten. Diese sind nicht aktuell.

#### Wie wird die Verdichtung vorgenommen?
Die Verdichtung sieht wie folgt aus:
* Es wird von jeder Stunde der MIN, MAX und AVG(Durchschnitt) Wert genommen
* Diese Werte landen dann in dieser Tabelle

Somit bliebe für die Vergangenheit ein Wert pro Stunde als Durchschnitt über. Für Interessierte könnten auch die MIN und MAX Werte pro Stunde interessant sein.
Pro Stunde bleiben also genau drei Werte vorhanden. Eigentlich vier, da der Zeitpunkt (TIMESTAMP) natürlich noch dazu kommt.

So kann ausgeschlossen werden, dass es zu viele Daten werden und zugleich sichergestellt werden, dass man eine ausreichende Analyse starten kann.

## Cronjobs
* Hinzufügen der Daten in die Datenbank
* Archivieren der Livedaten


# Wichtiges
* Cronjob und .sql werden bald online gestellt!