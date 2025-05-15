<?php
header('Content-Type: application/json');

// Função para ler o arquivo .ini
function getServerStatus() {
    $ini_array = parse_ini_file("D:/ZoneServer/SystemSave/ServerDisplay.ini", true);

    // Obter o status do servidor
    $isOnline = ($ini_array['SERVICE']['Open'] == 1) ? true : false;

    // Obter o número de jogadores online
    $onlinePlayers = $ini_array['USER']['UserNum'];

    return [
        'status' => $isOnline ? 'online' : 'offline',
        'players' => $onlinePlayers
    ];
}

// Obter os dados de status
$server = getServerStatus();

// Retornar os dados em formato JSON
echo json_encode($server);
