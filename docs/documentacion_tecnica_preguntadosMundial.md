# Documentación Técnica - Preguntados Mundial

## 1. Introducción a la Arquitectura
Este proyecto está desarrollado bajo el patrón de diseño **MVC (Modelo-Vista-Controlador)** en **PHP 7.1**.
La arquitectura divide las responsabilidades del sistema en tres capas fundamentales, permitiendo un código escalable y mantenible:
* **Controladores (`Controller`):** Actúan como intermediarios. Reciben las peticiones HTTP del usuario, procesan la lógica de negocio apoyándose en los Modelos, gestionan las variables de sesión y deciden qué Vista renderizar o a dónde redirigir.
* **Modelos (`Model`):** Son los encargados de interactuar directamente con la base de datos, ejecutar consultas SQL y retornar la información estructurada al controlador.
* **Vistas (`View`):** Utilizan el motor de plantillas **Mustache** (`.mustache`) para separar completamente la lógica de PHP de la estructura HTML, recibiendo arreglos de datos (`$data`) desde los controladores para su renderizado.

---

## 2. Flujo Funcional por Controlador

A continuación, se detalla la responsabilidad de cada controlador, su modelo asociado, sus vistas correspondientes y la explicación de sus métodos para defender el flujo de la aplicación.

### 2.1. `UsuarioController`
**Propósito:** Gestionar la autenticación de usuarios, registro, subida de imágenes de perfil y la validación de correos electrónicos.
* **Modelo:** `UsuarioModel`
* **Vistas:** `login.mustache`, `registro.mustache`

**Métodos y Flujo:**
* `login()` / `registro()`: Métodos encargados de renderizar los formularios correspondientes.
* `registrar()`: Recibe los datos por método POST. Realiza validaciones de campos (contraseñas coincidentes, email válido, verificación de duplicados en DB). Procesa la subida de la **foto de perfil** (validando extensiones y guardando localmente). Hashea la contraseña usando `password_hash()` (por seguridad), genera un token único hexadecimal y guarda al usuario. Finalmente, dispara el envío del mail.
* `enviarCorreoValidacion()`: Utiliza la librería **PHPMailer** conectada a *Mailtrap* para enviar un correo electrónico con un enlace único de activación.
* `validar()`: Recibe el token por URL (GET), valida en el Modelo que exista y cambia el estado de la cuenta a "activada" (`cuenta_activa = 1`).
* `procesarLogin()`: Verifica credenciales usando `password_verify()`. Verifica que la cuenta esté validada por mail. Establece las variables de sesión iniciales (`$_SESSION['usuario_id']`, `rol`, `puntaje`) y redirige al panel correspondiente (Lobby para Usuarios, Dashboard para Admins/Editores).
* `logout()`: Limpia y destruye la sesión del usuario.

---

### 2.2. `LobbyController`
**Propósito:** Es la pantalla principal del jugador. Actúa como el centro de operaciones (hub) desde donde el usuario decide jugar, ver su perfil o consultar el ranking.
* **Modelo:** `LobbyModel`
* **Vistas:** `lobby.mustache`, `ruleta.mustache`

**Métodos y Flujo:**
* `ver()`: Posee barreras de acceso (Redirige a Admins/Editores a sus respectivos paneles). Inicializa el puntaje a 0 si no existe. Verifica si el usuario viene de terminar una partida (para mostrar el cartel de Game Over y su puntaje final). Obtiene las categorías y la posición en el ranking global del jugador. Renderiza el Lobby.
* `jugar()`: Renderiza la vista de la ruleta, la cual será la antesala visual antes de seleccionar una pregunta al azar.
* `reiniciar()`: Resetea el array de preguntas respondidas y el puntaje en la sesión para permitir iniciar una partida limpia.

---

### 2.3. `PreguntaController`
**Propósito:** Es el motor central del juego (Core Loop). Gestiona la asignación de preguntas, el cálculo de niveles, el temporizador y la validación de respuestas.
* **Modelo:** `PreguntaModel`
* **Vistas:** `pregunta.mustache`, `gameOver.mustache`, `proponerPregunta.mustache`

**Métodos y Flujo:**
* `ver()`: Se invoca tras girar la ruleta, recibiendo el ID de la categoría. Calcula el "Nivel del Jugador" en base a su puntaje actual para entregar preguntas más difíciles progresivamente. Establece una marca de tiempo (`microtime`) en la sesión para controlar que el jugador no exceda los 10 segundos. Renderiza la pregunta con sus opciones mezcladas.
* `verificar()`: Endpoint que recibe la respuesta mediante AJAX/POST. Compara la opción elegida contra la base de datos.
    * *Si es correcta:* Aumenta el puntaje en sesión y devuelve un JSON para redirigir a tirar la ruleta de nuevo.
    * *Si es incorrecta:* Finaliza la partida, guarda el puntaje en el historial de la DB usando el modelo y devuelve un JSON para mostrar el error y volver al lobby.
* `timeout()`: Método de control que se invoca si el tiempo supera los 10 segundos. Finaliza la partida, registra el puntaje nulo o acumulado y renderiza la pantalla de "Game Over".
* `proponer()` / `guardarPropuesta()`: Flujo para que los usuarios (o staff) propongan nuevas preguntas al sistema. Éstas entran en un estado "Pendiente" para que un Editor las apruebe.
* `reportar()`: Recibe peticiones para denunciar una pregunta mal formulada y la guarda para el Editor.

---

### 2.4. `EditorController`
**Propósito:** Proporcionar un panel de gestión de contenido (Preguntas y Reportes) garantizando la calidad del juego.
* **Modelo:** `EditorModel`
* **Vistas:** `editor.mustache`, `editarPregunta.mustache`, `nuevaPregunta.mustache`

**Métodos y Flujo:**
* `ver()`: Tras verificar el rol "Editor", compila el listado de todas las preguntas activas, las propuestas hechas por los usuarios y los reportes de preguntas con errores.
* `nuevaPregunta()` / `crearPregunta()`: Formularios y procesos de alta (ABM) para introducir nuevas preguntas oficiales al sistema.
* `editarPregunta()` / `guardarPregunta()`: Modificación de preguntas existentes.
* `eliminarPregunta()` / `rechazarReporte()`: Gestión de la calidad del contenido, borrando inconsistencias o validando reportes falsos.

---

### 2.5. `AdminController`
**Propósito:** Panel de métricas y estadísticas del sistema a nivel global.
* **Modelo:** `AdminModel`
* **Vistas:** `admin.mustache`

**Métodos y Flujo:**
* `dashboard()` / `ver()`: Revisa los permisos de Administrador. Recupera de la base de datos las métricas del sistema filtradas por un parámetro temporal (día, semana, mes, año).
* `resolverPais($coordenadas)`: **Aspecto técnico clave**. Se conecta a la API externa de *Nominatim (OpenStreetMap)*. Recibe las coordenadas guardadas de los usuarios y mediante un Reverse Geocoding, devuelve el nombre del país para generar las métricas geográficas del dashboard.

---

### 2.6. `PerfilController`
**Propósito:** Gestión de la información pública y privada de cada jugador.
* **Modelo:** `PerfilModel`
* **Vistas:** `perfil.mustache`

**Métodos y Flujo:**
* `ver()`: Identifica si el perfil a visualizar es el propio del usuario en sesión o el de otro competidor (mediante ID por GET). Trae el historial de partidas, puntaje máximo y cantidad de partidas.
* `resolverLocalidad()`: Al igual que el AdminController, consume la API de Nominatim para convertir la latitud y longitud del usuario en el nombre de la ciudad visible en su perfil.
* *Nota*: Integra la API de `qrserver.com` para generar un código QR dinámico y enlazarlo directamente a la URL pública del perfil.

---

### 2.7. `RankingController`
**Propósito:** Exponer la tabla de posiciones global de los jugadores.
* **Modelo:** `RankingModel`
* **Vistas:** `ranking.mustache`

**Métodos y Flujo:**
* `ver()`: Pide al modelo los jugadores ordenados de mayor a menor según su mejor puntaje. Realiza un ciclo `foreach` en PHP para asignarle dinámicamente un número de posición a cada jugador antes de mandarlo a la vista Mustache.

---

## 3. Puntos Fuertes a destacar en la Defensa
Si te preguntan por justificaciones técnicas en tu tesis, puedes mencionar:
1.  **Seguridad y Autenticación:** Las contraseñas se almacenan con algoritmos de hashing fuertes (`password_hash`). Se validan cuentas por token hexadecimal vía correo utilizando `PHPMailer`.
2.  **Control de Accesos (RBAC):** Uso estricto de variables de sesión (`$_SESSION['rol']`) previniendo ataques de escalada de privilegios en los controladores (ej. un usuario normal intentando acceder por URL al Admin).
3.  **Integración de APIs Externas:** * *Geolocalización:* Se consume la API REST de Nominatim para aplicar Reverse Geocoding, resolviendo Coordenadas (Lat/Long) a Países/Ciudades tanto en las métricas de Administrador como en los Perfiles.
    * *Generación de QR:* Para compartir perfiles dinámicamente usando `api.qrserver.com`.
4.  **Manejo de Estados del Juego y Prevención de Trampas:** El `PreguntaController` guarda `microtime(true)` en la sesión para validar estrictamente desde el backend que el usuario no manipule el temporizador de 10 segundos desde la vista del cliente.