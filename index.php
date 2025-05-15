<?php
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

// Obter o status e número de jogadores online
$server = getServerStatus();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>RF SHADOW PVP</title>
  <link rel="icon" href="/imagens/Screenshot_5.png" type="image/png" />

  <!-- Fontes -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Audiowide&display=swap" rel="stylesheet">

  <!-- Estilos -->
  <link rel="stylesheet" href="style.css" />

  <!-- Scroll Reveal -->
  <script src="https://unpkg.com/scrollreveal"></script>
  <!-- Particles.js -->
  <script src="https://cdn.jsdelivr.net/npm/tsparticles@2.11.1/tsparticles.bundle.min.js"></script>

  <style>
    .octagon-container {
      width: 130px; /* Tamanho do octógono */
      height: 130px;
      background-image: url('imagens/octogono.png');
      background-size: contain;
      background-repeat: no-repeat;
      background-position: center;
      text-align: center;
      position: relative;
      margin: auto;
      margin-top: 5px; /* Ajustado para subir ainda mais o octógono para perto do texto "Em Breve..." */
    }

    .status-text {
      position: absolute;
      bottom: 20px; /* Ajustado para um pouco mais perto da imagem */
      left: 50%;
      transform: translateX(-50%);
      color: white;
      text-align: center;
    }

    .status-text p {
      margin: 3px;
    }

    .status-text .total {
      font-size: 18px; /* Tamanho ajustado do texto "Total" */
      font-weight: bold;
      text-shadow: 0 0 10px rgba(0, 255, 255, 0.8);
    }

    .status-text .players {
      font-size: 18px; /* Ajustei o tamanho do número de jogadores */
      font-weight: bold;
      text-shadow: 0 0 10px rgba(0, 255, 255, 0.8);
    }

    .status-text .players, .status-text .total {
      font-family: 'Audiowide', sans-serif;
    }

    .status-img {
      max-width: 85px; /* Ajuste para a imagem de status */
      max-height: 85px; /* Ajuste para a imagem de status */
      width: auto;
      height: auto;
      margin-top: 30px;
      margin-left: 22px; /* Moveu a imagem ligeiramente para a direita */
      margin-bottom: 0px; /* Moveu a imagem ligeiramente para baixo */
    }
  </style>
</head>
<body>
  <!-- Fundo com partículas -->
  <div id="particles-js"></div>

  <!-- Vídeo de Fundo -->
  <video autoplay muted loop class="video-bg" playsinline>
    <source src="videos/Cinematic Background HDMINITOOL_a167d03d-2b29-4a23-898d-1f833210338e.mp4" type="video/mp4">
    Seu navegador não suporta vídeo em HTML5.
  </video>

  <!-- Overlay escuro -->
  <div class="overlay"></div>

  <nav class="navbar">
    <div class="navbar-container">
      <ul class="navbar-menu">
        <li><a href="index.php">Home</a></li>
        <li><a href="download.html">Download</a></li>
        <li class="dropdown">
          <a href="javascript:void(0)">
            Informações
            <span class="arrow">&#9660;</span>
          </a>
          <ul class="dropdown-menu">
            <li><a href="server-info.html">Server Informação</a></li>
            <li><a href="combine-itens.html">Combine Itens</a></li>
            <li><a href="drop-list.html">Drop List</a></li>
            <li><a href="box-info.html">Box Informação</a></li>
            <li><a href="regras.html">Regras</a></li>
          </ul>
        </li>
        <li><a href="donation.html">Donate</a></li>
        <li class="dropdown">
          <a href="javascript:void(0)">
            Comunidade
            <span class="arrow">&#9660;</span>
          </a>
          <ul class="dropdown-menu">
            <li><a href="https://www.facebook.com/rfshadow/" target="_blank">Facebook Page</a></li>
            <li><a href="https://discord.gg/A8wp3U7VhA" target="_blank">Discord</a></li>
            <li><a href="https://www.instagram.com/rfoshadow" target="_blank">Instagram</a></li>
            <li><a href="https://chat.whatsapp.com/GR9C6GVCFhqL2R4D4qTXRs" target="_blank">Grupo de Whatsapp</a></li>
          </ul>
        </li>
        <li><a href="https://rfshadow.rfocp.net/index.php" target="_blank">GameCP/Registro</a></li>
      </ul>
    </div>
  </nav>

  <div class="hamburger" id="hamburger">
    <span></span><span></span><span></span>
  </div>

  <!-- Conteúdo do Site -->
  <main class="hero">
    <img src="imagens/RF SHADOW LOGO ROXA.png" alt="RF Shadow" class="hero__logo" />
    <p class="hero__subtitle">O lado sombrio do Rising Force Online</p>
    <h1 class="hero__title">
      <span id="countdon">Em Breve...</span>
    </h1>

    <!-- Adicionando o Octógono e o Texto -->
    <div class="octagon-container">
      <img src="imagens/<?= $server['status'] ?>.png" alt="Status" class="status-img">
      <div class="status-text">
        <p class="total">Total</p>
        <p class="players"><?= $server['players'] ?></p>
      </div>
    </div>

    <!-- Botões de Acesso -->
    <div class="button-group">
      <a href="https://discord.gg/A8wp3U7VhA" class="button button--glow" target="_blank">Discord</a>
      <a href="https://chat.whatsapp.com/GR9C6GVCFhqL2R4D4qTXRs" class="button button--glow" target="_blank">Grupo do Whatsapp</a>
    </div>
  </main>

  <footer class="footer">
    © 2025 todos os direitos reservados RF SHADOW PVP.
  </footer>

  <!-- Scripts -->
  <script src="script.js"></script>
</body>
</html>
