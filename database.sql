CREATE SCHEMA preguntadosMundial;
USE
preguntadosMundial;

CREATE TABLE GENERO
(
    id          char(1) primary key,
    descripcion varchar(50) not null
);

CREATE TABLE CATEGORIA
(
    id     int auto_increment primary key,
    nombre varchar(50) not null unique
);

CREATE TABLE USUARIO
(
    id                 int auto_increment primary key,
    nombre             varchar(50)  not null,
    fecha_nac          date         not null,
    genero             char(1)      not null,
    coordenadas_ciudad varchar(100) not null,
    email              varchar(100) not null unique,
    contrasena         varchar(255) not null,
    nombre_usuario     varchar(50)  not null unique,
    foto_perfil        varchar(255) not null,
    FOREIGN KEY (genero) REFERENCES GENERO (id)
);

CREATE TABLE PREGUNTA
(
    id           int auto_increment primary key,
    texto        varchar(255) not null,
    dificultad   int          not null,
    categoria_id int          not null,
    CONSTRAINT fk_pregunta_categoria FOREIGN KEY (categoria_id) REFERENCES CATEGORIA (id)
);

CREATE TABLE ROL
(
    id          int auto_increment primary key,
    usuario_id  int         not null,
    descripcion varchar(50) not null,
    FOREIGN KEY (usuario_id) REFERENCES USUARIO (id)
);

CREATE TABLE OPCION
(
    id          int auto_increment primary key,
    pregunta_id int          not null,
    texto       varchar(255) not null,
    es_correcta boolean      not null,
    FOREIGN KEY (pregunta_id) REFERENCES PREGUNTA (id)
);

CREATE TABLE PARTIDA
(
    id         int auto_increment primary key,
    usuario_id int         not null,
    puntaje    int         not null,
    resultado  varchar(50) not null,
    FOREIGN KEY (usuario_id) REFERENCES USUARIO (id)
);