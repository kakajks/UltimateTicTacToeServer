<?php
$socket = fsockopen("localhost", 7504);
stream_set_blocking($socket, false);
while (true) {
    if ($socket !== false) {
        $bruh = fgets($socket);
        
        if ($bruh !== false) {
            var_dump($bruh);
        }
    } else {
        $socket = fsockopen("localhost", 7504);
    }
}