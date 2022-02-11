<?php
require __DIR__ . '/GameServer.php';
require __DIR__ . '/Game.php';
$server = new GameServer(7504);
$g = new Game();
$games = [];
// Server loop
do {

    // Server GUCKT NACH ANFRAGEN VON SOCKETS
    socket_listen($server->getSocket());
    socket_set_nonblock($server->getSocket());

    // SOCKETS WERDEN VERBUNDEN
    $sock = socket_accept($server->getSocket());
    if ($sock != null && $sock != false) {
        $server->addToConnectedSockets($sock);
    }

    // SCHICKE QUEUE NACHRICHT
    $QueueMsg = json_encode(array(
        "Status" => "Queue"
    ));
    $data = @socket_write($sock, $QueueMsg, strlen($QueueMsg));
    echo sizeof($server->getConnectedSockets()) . PHP_EOL;

    // LOOP ZUM ÜBERPRÜFEN OB CLIENTS NOCH CONNECTED SIND
    foreach ($server->getConnectedSockets() as $socket) {
        $msg = json_encode(array(
            "Msg" => "Lifesign"
        ));
        $data = @socket_write($socket, $msg, strlen($msg));

        if ($data === FALSE) {
            $server->removeConnectedSocket($socket);
            echo 'Client disconnected!' . PHP_EOL;
            // ÜBERPRÜFUNG OB DER SPIELER IN EINEM GAME DRINNEN WAR
            $connGame = isAlreadyInGame($socket, $games);
            if ($connGame[0]) {
                foreach ($games as $key => $game) {
                    if ($game["Game"] == $connGame[1]) {
                        // NACHRICHT AN VERBLEIBENDEN SPIELER SENDEN, DASS DIESER GEWONNEN HAT
                        $GameIDMsg = array(
                            "Game" => $game["Game"],
                            "Status" => "Won due Disconnect"
                        );
                        socket_write($game["X"] == $socket ? $game["O"] : $game["X"], json_encode($GameIDMsg), strlen(json_encode($GameIDMsg)));
                        unset($games[$key]);
                    }
                }
            }
            socket_close($socket);
            continue;
        }
    }
    
    //ÜBERPRÜFEN OB DER CLIENT ETWAS SENDET
    /*
     * jsonDATA
     * {
     *      "request" => "getState"
     *      "gameID" => "GAMEID"
     * }
     *
     */
    $readed = socket_read($server->getSocket(), "\n", PHP_NORMAL_READ);
    if($readed != "") {
        $readed = json_decode($readed);
       //Bei getState abfrage wird der State zurück gegeben
        if($readed["request"] == "getState" || $readed["gameID"] != null) {
            foreach ($games as $key => $game) {
                if ($game["Game"] == $readed["gameID"]) {
                    
                    $stateMsg = array(
                        "Game" => $game["Game"],
                        "State" => json_encode($game["State"])
                    );
                    socket_write($game["O"], json_encode($stateMsg), strlen(json_encode($stateMsg)));
                    socket_write($game["X"], json_encode($stateMsg), strlen(json_encode($stateMsg)));
                }
            }
        }
    }
    
    // BEI GENUG SPIELERN EIN SPIEL ERSTELLEN
    if ((sizeof($server->getConnectedSockets()) - (sizeof($games) * 2)) >= 2) {
        $currgame = $g->createGame();

        // SPIELERN EIN GAME ZUWEISEN
        foreach ($server->getConnectedSockets() as $connSocket) {
            if (! isAlreadyInGame($connSocket, $games)[0]) {

                if ($currgame["O"] == null) {
                    $currgame["O"] = $connSocket;
                } else if ($currgame["X"] == null) {
                    $currgame["X"] = $connSocket;
                } else {
                    break;
                }
            }
        }

        // SPIELERN MITTEILEN WER WELCHER SPIELER IST
        if ($currgame["O"] != null && $currgame["X"] != null) {

            $GameIDMsg = array(
                "Game" => $currgame["Game"],
                "Player" => "X",
                "Status" => "Ingame"
            );
            socket_write($currgame["X"], json_encode($GameIDMsg), strlen(json_encode($GameIDMsg)));
            $GameIDMsg["Player"] = "O";
            socket_write($currgame["O"], json_encode($GameIDMsg), strlen(json_encode($GameIDMsg)));
            array_push($games, $currgame);
        }
    }

    sleep(1);
} while (true);

/**
 * Diese Funktion prüft ob ein Socket bereits in einem Game ist
 *
 * @param resource $socket
 * @param array $games
 * @return array
 */
function isAlreadyInGame($socket, $games)
{
    $isInGame = false;
    $ConnectedGame = null;
    foreach ($games as $game) {
        if ($game["X"] === $socket || $game["O"] === $socket) {
            $isInGame = true;
            $ConnectedGame = $game["Game"];
            break;
        }
    }
    return [
        $isInGame,
        $ConnectedGame
    ];
}

function startsWith( $haystack, $needle ) {
    $length = strlen( $needle );
    return substr( $haystack, 0, $length ) === $needle;
}
