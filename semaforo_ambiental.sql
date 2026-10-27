-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 27-10-2026 a las 22:17:06
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `semaforo_ambiental`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_actividades`
--

CREATE TABLE `tbl_actividades` (
  `id_actividad` int(11) NOT NULL,
  `id_docente` int(11) NOT NULL,
  `titulo` varchar(180) NOT NULL,
  `descripcion` text NOT NULL,
  `fecha_publicacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_entrega` datetime DEFAULT NULL,
  `puntaje_maximo` decimal(5,2) NOT NULL DEFAULT 5.00,
  `estado` enum('publicada','cerrada') NOT NULL DEFAULT 'publicada'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_calificaciones`
--

CREATE TABLE `tbl_calificaciones` (
  `id_calificacion` int(11) NOT NULL,
  `id_entrega` int(11) NOT NULL,
  `id_docente` int(11) NOT NULL,
  `nota` decimal(5,2) NOT NULL,
  `observaciones` text DEFAULT NULL,
  `fecha_calificacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_contactos`
--

CREATE TABLE `tbl_contactos` (
  `id_contacto` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(150) NOT NULL,
  `mensaje` text NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tbl_contactos`
--

INSERT INTO `tbl_contactos` (`id_contacto`, `id_usuario`, `nombre`, `correo`, `mensaje`, `fecha`) VALUES
(1, NULL, 'Juan Pérez', 'juan@gmail.com', 'Quiero conocer más información sobre el proyecto de la huerta.', '2026-10-01 16:23:55'),
(2, NULL, 'María Gómez', 'maria@gmail.com', 'Me gustaría conocer cómo funciona el semáforo ambiental.', '2026-10-01 16:23:55'),
(3, NULL, 'Carlos Rodríguez', 'carlos@gmail.com', 'Tengo una pregunta sobre el monitoreo de la huerta.', '2026-10-01 16:23:55');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_entregas`
--

CREATE TABLE `tbl_entregas` (
  `id_entrega` int(11) NOT NULL,
  `id_actividad` int(11) NOT NULL,
  `id_estudiante` int(11) NOT NULL,
  `respuesta` text DEFAULT NULL,
  `enlace` varchar(500) DEFAULT NULL,
  `archivo` varchar(255) DEFAULT NULL,
  `fecha_entrega` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_galeria`
--

CREATE TABLE `tbl_galeria` (
  `id_galeria` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tbl_galeria`
--

INSERT INTO `tbl_galeria` (`id_galeria`, `id_usuario`, `titulo`, `descripcion`, `imagen`, `activo`) VALUES
(1, 1, 'Huerta escolar', 'Espacio destinado al cultivo y cuidado de plantas dentro de la institución.', 'admin_b693af8c361af76019716fb9.png', 1),
(2, 1, 'Sistema de monitoreo', 'Control ambiental de la huerta mediante el sistema de semáforo.', 'admin_6ca504843f3745f834bd1913.png', 1),
(3, 1, 'Cuidado de las plantas', 'Actividades realizadas para mantener en buenas condiciones la huerta.', 'admin_5280ea7679f85e4ff944e4f9.png', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_novedades`
--

CREATE TABLE `tbl_novedades` (
  `id_novedad` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_publicacion` date NOT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tbl_novedades`
--

INSERT INTO `tbl_novedades` (`id_novedad`, `id_usuario`, `titulo`, `descripcion`, `fecha_publicacion`, `imagen`, `activo`) VALUES
(1, 1, 'Nueva huerta escolar', 'Se continúa trabajando en el mejoramiento y cuidado de la huerta escolar.', '2026-09-01', 'admin_9a3222d52841b67ecaed720b.png', 1),
(2, 1, 'Implementación del semáforo ambiental', 'Se implementa el sistema de semáforo para facilitar el seguimiento ambiental.', '2026-09-10', 'admin_de3e2609cf66cb1943c56904.png', 1),
(3, 1, 'Cuidado responsable de la huerta', 'Se promueven buenas prácticas para el cuidado de las plantas.', '2026-09-15', 'admin_d971bd2634093bb6c757d1ca.png', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_servicios`
--

CREATE TABLE `tbl_servicios` (
  `id_servicio` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tbl_servicios`
--

INSERT INTO `tbl_servicios` (`id_servicio`, `id_usuario`, `titulo`, `descripcion`, `imagen`, `activo`) VALUES
(1, 1, 'Monitoreo ambiental', 'Seguimiento de las condiciones ambientales de la huerta.', 'admin_185d6f50dced865907464a5a.png', 1),
(2, 1, 'Control de humedad', 'Supervisión de la humedad para mejorar el cuidado de las plantas.', 'admin_b0df9e2abd3e1f2895f0f0fd.png', 1),
(3, 1, 'Alertas ambientales', 'Identificación de condiciones que requieren atención en la huerta.', 'admin_fbd34b24c1becdccc756fa9f.png', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_usuarios`
--

CREATE TABLE `tbl_usuarios` (
  `id_usuario` int(11) NOT NULL,
  `nombre_completo` varchar(150) NOT NULL,
  `correo` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `rol` enum('administrador','profesor','estudiante') NOT NULL DEFAULT 'estudiante'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tbl_usuarios`
--

INSERT INTO `tbl_usuarios` (`id_usuario`, `nombre_completo`, `correo`, `password`, `fecha_registro`, `rol`) VALUES
(1, 'Profesor', 'elprofe@gmail.com', '$2y$10$Yth6qBw2gN3.aBwCH15gUuNmaaF3eXSX3uv90VyGJLmui2iS1V.si', '2026-10-01 16:23:55', 'profesor'),
(2, 'Estudiante', 'estudiante@correo.com', '$2y$10$mN049clvOCPoJEEWFlkXX.z6dZfplt/OlH4.o1cS4Shfw2cT.W/ge', '2026-10-01 16:23:55', 'estudiante'),
(3, 'Administrador del Sistema', 'admin@semaforo.com', '$2y$10$HX7usTk08hT36Nt2Cp51NeDyH8mTnt7qvQvbqG7sGxS/jQ.vXVUtG', '2026-10-26 18:37:08', 'administrador');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `tbl_actividades`
--
ALTER TABLE `tbl_actividades`
  ADD PRIMARY KEY (`id_actividad`),
  ADD KEY `id_docente` (`id_docente`),
  ADD KEY `estado` (`estado`);

--
-- Indices de la tabla `tbl_calificaciones`
--
ALTER TABLE `tbl_calificaciones`
  ADD PRIMARY KEY (`id_calificacion`),
  ADD UNIQUE KEY `uq_entrega` (`id_entrega`),
  ADD KEY `id_docente` (`id_docente`);

--
-- Indices de la tabla `tbl_contactos`
--
ALTER TABLE `tbl_contactos`
  ADD PRIMARY KEY (`id_contacto`),
  ADD KEY `fk_contactos_usuario` (`id_usuario`);

--
-- Indices de la tabla `tbl_entregas`
--
ALTER TABLE `tbl_entregas`
  ADD PRIMARY KEY (`id_entrega`),
  ADD UNIQUE KEY `uq_actividad_estudiante` (`id_actividad`,`id_estudiante`),
  ADD KEY `id_estudiante` (`id_estudiante`),
  ADD KEY `id_actividad` (`id_actividad`);

--
-- Indices de la tabla `tbl_galeria`
--
ALTER TABLE `tbl_galeria`
  ADD PRIMARY KEY (`id_galeria`),
  ADD KEY `fk_galeria_usuario` (`id_usuario`);

--
-- Indices de la tabla `tbl_novedades`
--
ALTER TABLE `tbl_novedades`
  ADD PRIMARY KEY (`id_novedad`),
  ADD KEY `fk_novedades_usuario` (`id_usuario`);

--
-- Indices de la tabla `tbl_servicios`
--
ALTER TABLE `tbl_servicios`
  ADD PRIMARY KEY (`id_servicio`),
  ADD KEY `fk_servicios_usuario` (`id_usuario`);

--
-- Indices de la tabla `tbl_usuarios`
--
ALTER TABLE `tbl_usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `correo` (`correo`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `tbl_actividades`
--
ALTER TABLE `tbl_actividades`
  MODIFY `id_actividad` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tbl_calificaciones`
--
ALTER TABLE `tbl_calificaciones`
  MODIFY `id_calificacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tbl_contactos`
--
ALTER TABLE `tbl_contactos`
  MODIFY `id_contacto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `tbl_entregas`
--
ALTER TABLE `tbl_entregas`
  MODIFY `id_entrega` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tbl_galeria`
--
ALTER TABLE `tbl_galeria`
  MODIFY `id_galeria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `tbl_novedades`
--
ALTER TABLE `tbl_novedades`
  MODIFY `id_novedad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `tbl_servicios`
--
ALTER TABLE `tbl_servicios`
  MODIFY `id_servicio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `tbl_usuarios`
--
ALTER TABLE `tbl_usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `tbl_actividades`
--
ALTER TABLE `tbl_actividades`
  ADD CONSTRAINT `fk_actividades_docente` FOREIGN KEY (`id_docente`) REFERENCES `tbl_usuarios` (`id_usuario`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `tbl_calificaciones`
--
ALTER TABLE `tbl_calificaciones`
  ADD CONSTRAINT `fk_calificaciones_docente` FOREIGN KEY (`id_docente`) REFERENCES `tbl_usuarios` (`id_usuario`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_calificaciones_entrega` FOREIGN KEY (`id_entrega`) REFERENCES `tbl_entregas` (`id_entrega`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `tbl_contactos`
--
ALTER TABLE `tbl_contactos`
  ADD CONSTRAINT `fk_contactos_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `tbl_usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `tbl_entregas`
--
ALTER TABLE `tbl_entregas`
  ADD CONSTRAINT `fk_entregas_actividad` FOREIGN KEY (`id_actividad`) REFERENCES `tbl_actividades` (`id_actividad`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_entregas_estudiante` FOREIGN KEY (`id_estudiante`) REFERENCES `tbl_usuarios` (`id_usuario`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `tbl_galeria`
--
ALTER TABLE `tbl_galeria`
  ADD CONSTRAINT `fk_galeria_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `tbl_usuarios` (`id_usuario`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `tbl_novedades`
--
ALTER TABLE `tbl_novedades`
  ADD CONSTRAINT `fk_novedades_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `tbl_usuarios` (`id_usuario`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `tbl_servicios`
--
ALTER TABLE `tbl_servicios`
  ADD CONSTRAINT `fk_servicios_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `tbl_usuarios` (`id_usuario`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
