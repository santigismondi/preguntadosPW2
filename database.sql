CREATE SCHEMA preguntadosMundial;
USE preguntadosMundial;

CREATE TABLE GENERO
(
    id          char(1) PRIMARY KEY,
    descripcion varchar(50) NOT NULL
);

CREATE TABLE CATEGORIA
(
    id     int AUTO_INCREMENT PRIMARY KEY,
    nombre varchar(50) NOT NULL UNIQUE,
    color  varchar(7)  NOT NULL DEFAULT '#3498db'
);

CREATE TABLE USUARIO
(
    id                 int AUTO_INCREMENT PRIMARY KEY,
    nombre             varchar(50)  NOT NULL,
    fecha_nac          date         NOT NULL,
    genero             char(1)      NOT NULL,
    coordenadas_ciudad varchar(100) NOT NULL,
    email              varchar(100) NOT NULL UNIQUE,
    contrasena         varchar(255) NOT NULL,
    nombre_usuario     varchar(50)  NOT NULL UNIQUE,
    foto_perfil        varchar(255) NOT NULL DEFAULT 'default.png',
    token_validacion   varchar(255),
    cuenta_activa      tinyint(1)   NOT NULL DEFAULT 0,
    nivel              int          NOT NULL DEFAULT 1,
    FOREIGN KEY (genero) REFERENCES GENERO (id)
);

CREATE TABLE ROL
(
    id          int AUTO_INCREMENT PRIMARY KEY,
    usuario_id  int         NOT NULL,
    descripcion varchar(50) NOT NULL,
    FOREIGN KEY (usuario_id) REFERENCES USUARIO (id)
);

CREATE TABLE PREGUNTA
(
    id           int AUTO_INCREMENT PRIMARY KEY,
    texto        varchar(255) NOT NULL,
    dificultad   int          NOT NULL DEFAULT 0,
    categoria_id int          NOT NULL,
    estado       varchar(20)  NOT NULL DEFAULT 'aprobada',
    FOREIGN KEY (categoria_id) REFERENCES CATEGORIA (id)
);

CREATE TABLE OPCION
(
    id          int AUTO_INCREMENT PRIMARY KEY,
    pregunta_id int          NOT NULL,
    texto       varchar(255) NOT NULL,
    es_correcta boolean      NOT NULL DEFAULT false,
    FOREIGN KEY (pregunta_id) REFERENCES PREGUNTA (id)
);

CREATE TABLE PARTIDA
(
    id         int AUTO_INCREMENT PRIMARY KEY,
    usuario_id int         NOT NULL,
    puntaje    int         NOT NULL DEFAULT 0,
    resultado  varchar(50) NOT NULL DEFAULT 'en_curso',
    FOREIGN KEY (usuario_id) REFERENCES USUARIO (id)
);

-- DATOS

INSERT INTO GENERO
VALUES ('M', 'Masculino'),
       ('F', 'Femenino'),
       ('N', 'Prefiero no cargarlo');

INSERT INTO CATEGORIA (nombre, color)
VALUES ('Grupos y Fase de Grupos', '#53f1c8'),
       ('Estadios y Sedes', '#abde02'),
       ('Jugadores y Figuras', '#ce0201'),
       ('Selecciones', '#2635bb'),
       ('Historia del Mundial', '#ffe400'),
       ('Récords y Estadísticas', '#6e23ee');

-- CATEGORÍA 1: Grupos y Fase de Grupos
INSERT INTO PREGUNTA (texto, dificultad, categoria_id)
VALUES ('¿Cuántos grupos hay en la fase de grupos del Mundial 2026?', 0, 1),
       ('¿Cuántas selecciones clasifican por grupo en el Mundial 2026?', 0, 1),
       ('¿Cuántos partidos se juegan en total en la fase de grupos del Mundial 2026 con 48 equipos?', 0, 1),
       ('¿Qué pasa si dos equipos terminan igualados en puntos en la fase de grupos?', 0, 1),
       ('¿Cuántos puntos se obtienen por ganar un partido en la fase de grupos?', 0, 1);

INSERT INTO OPCION (pregunta_id, texto, es_correcta)
VALUES (1, '8 grupos', false),
       (1, '12 grupos', true),
       (1, '10 grupos', false),
       (1, '16 grupos', false),
       (2, '1 selección', false),
       (2, '2 selecciones', false),
       (2, '3 selecciones', true),
       (2, '4 selecciones', false),
       (3, '48 partidos', false),
       (3, '64 partidos', false),
       (3, '72 partidos', true),
       (3, '96 partidos', false),
       (4, 'Se juega un partido extra', false),
       (4, 'Se define por diferencia de goles', true),
       (4, 'Se define por sorteo', false),
       (4, 'Ambos clasifican', false),
       (5, '1 punto', false),
       (5, '2 puntos', false),
       (5, '3 puntos', true),
       (5, '4 puntos', false);

-- CATEGORÍA 2: Estadios y Sedes
INSERT INTO PREGUNTA (texto, dificultad, categoria_id)
VALUES ('¿Cuál es el estadio donde se jugará la final del Mundial 2026?', 0, 2),
       ('¿En cuántos países se disputará el Mundial 2026?', 0, 2),
       ('¿Cuál de estos estadios es sede del Mundial 2026 en México?', 0, 2),
       ('¿Cuál es la ciudad canadiense sede del Mundial 2026?', 0, 2),
       ('¿Cuántos estadios en total se usarán en el Mundial 2026?', 0, 2);

INSERT INTO OPCION (pregunta_id, texto, es_correcta)
VALUES (6, 'Rose Bowl, California', false),
       (6, 'MetLife Stadium, Nueva York/Nueva Jersey', true),
       (6, 'Estadio Azteca, México', false),
       (6, 'AT&T Stadium, Dallas', false),
       (7, '1 país', false),
       (7, '2 países', false),
       (7, '3 países', true),
       (7, '4 países', false),
       (8, 'Estadio Jalisco', false),
       (8, 'Estadio Azteca', true),
       (8, 'Estadio Universitario', false),
       (8, 'Estadio Nemesio Diez', false),
       (9, 'Ottawa', false),
       (9, 'Montreal', false),
       (9, 'Vancouver', true),
       (9, 'Calgary', false),
       (10, '12 estadios', false),
       (10, '14 estadios', false),
       (10, '16 estadios', true),
       (10, '20 estadios', false);

-- CATEGORÍA 3: Jugadores y Figuras
INSERT INTO PREGUNTA (texto, dificultad, categoria_id)
VALUES ('¿Quién es el máximo goleador de la historia de los mundiales?', 0, 3),
       ('¿Con qué resultado Argentina ganó la final del Mundial 2022 en penales?', 0, 3),
       ('¿Qué jugador ganó el Balón de Oro en el Mundial 2022?', 0, 3),
       ('¿Quién fue el arquero titular de Argentina en el Mundial 2022?', 0, 3),
       ('¿Cuántos mundiales jugó Lionel Messi antes de ganar en 2022?', 0, 3);

INSERT INTO OPCION (pregunta_id, texto, es_correcta)
VALUES (11, 'Pelé', false),
       (11, 'Ronaldo Nazário', false),
       (11, 'Miroslav Klose', true),
       (11, 'Gerd Müller', false),
       (12, '3-2', false),
       (12, '4-2 en penales tras empatar 3-3', true),
       (12, '1-0', false),
       (12, '2-0', false),
       (13, 'Kylian Mbappé', false),
       (13, 'Luka Modric', false),
       (13, 'Lionel Messi', true),
       (13, 'Neymar', false),
       (14, 'Sergio Romero', false),
       (14, 'Franco Armani', false),
       (14, 'Emiliano Martínez', true),
       (14, 'Nahuel Guzmán', false),
       (15, '3 mundiales', false),
       (15, '4 mundiales', true),
       (15, '5 mundiales', false),
       (15, '2 mundiales', false);

-- CATEGORÍA 4: Selecciones
INSERT INTO PREGUNTA (texto, dificultad, categoria_id)
VALUES ('¿Cuántas Copas del Mundo tiene Brasil en su historia?', 0, 4),
       ('¿Qué selección ganó el primer Mundial de la historia en 1930?', 0, 4),
       ('¿Cuál es el apodo de la selección de Brasil?', 0, 4),
       ('¿Qué selección europea ganó el Mundial 2018?', 0, 4),
       ('¿Cuál es la selección con más participaciones mundialistas de CONMEBOL?', 0, 4);

INSERT INTO OPCION (pregunta_id, texto, es_correcta)
VALUES (16, '4 Copas del Mundo', false),
       (16, '5 Copas del Mundo', true),
       (16, '6 Copas del Mundo', false),
       (16, '3 Copas del Mundo', false),
       (17, 'Argentina', false),
       (17, 'Brasil', false),
       (17, 'Uruguay', true),
       (17, 'Italia', false),
       (18, 'La Albiceleste', false),
       (18, 'La Furia Roja', false),
       (18, 'La Canarinha', true),
       (18, 'Les Bleus', false),
       (19, 'Alemania', false),
       (19, 'España', false),
       (19, 'Francia', true),
       (19, 'Croacia', false),
       (20, 'Argentina', false),
       (20, 'Brasil', true),
       (20, 'Uruguay', false),
       (20, 'Chile', false);

-- CATEGORÍA 5: Historia del Mundial
INSERT INTO PREGUNTA (texto, dificultad, categoria_id)
VALUES ('¿En qué año se jugó el primer Mundial de fútbol?', 0, 5),
       ('¿Quién organizó el Mundial 2014?', 0, 5),
       ('¿Qué país fue el primero en organizar dos mundiales?', 0, 5),
       ('¿Cuántos mundiales se jugaron en América del Sur hasta 2022?', 0, 5),
       ('¿Qué selección ganó el Mundial 2010 en Sudáfrica?', 0, 5);

INSERT INTO OPCION (pregunta_id, texto, es_correcta)
VALUES (21, '1924', false),
       (21, '1928', false),
       (21, '1930', true),
       (21, '1934', false),
       (22, 'Argentina', false),
       (22, 'Brasil', true),
       (22, 'Colombia', false),
       (22, 'Chile', false),
       (23, 'Brasil', false),
       (23, 'Alemania', false),
       (23, 'México', true),
       (23, 'Francia', false),
       (24, '3 mundiales', false),
       (24, '4 mundiales', false),
       (24, '5 mundiales', true),
       (24, '6 mundiales', false),
       (25, 'Brasil', false),
       (25, 'Alemania', false),
       (25, 'España', true),
       (25, 'Holanda', false);

-- CATEGORÍA 6: Récords y Estadísticas
INSERT INTO PREGUNTA (texto, dificultad, categoria_id)
VALUES ('¿Cuántos goles marcó Miroslav Klose en mundiales para ser el máximo goleador?', 0, 6),
       ('¿Qué selección tiene más títulos mundiales?', 0, 6),
       ('¿Cuántos goles metió Kylian Mbappé en el Mundial 2022?', 0, 6),
       ('¿Cuál fue el primer mundial con VAR?', 0, 6),
       ('¿Cuántos mundiales ganó Argentina en total?', 0, 6);

INSERT INTO OPCION (pregunta_id, texto, es_correcta)
VALUES (26, '14 goles', false),
       (26, '16 goles', true),
       (26, '17 goles', false),
       (26, '12 goles', false),
       (27, 'Alemania', false),
       (27, 'Argentina', false),
       (27, 'Brasil', true),
       (27, 'Italia', false),
       (28, '5 goles', false),
       (28, '6 goles', false),
       (28, '8 goles', true),
       (28, '7 goles', false),
       (29, 'Brasil 2014', false),
       (29, 'Rusia 2018', true),
       (29, 'Qatar 2022', false),
       (29, 'Alemania 2006', false),
       (30, '1', false),
       (30, '2', false),
       (30, '3', true),
       (30, '4', false);