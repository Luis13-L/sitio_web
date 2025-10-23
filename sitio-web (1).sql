-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 23-10-2025 a las 19:07:28
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
-- Base de datos: `sitio-web`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_confifiguraciones`
--

CREATE TABLE `tbl_confifiguraciones` (
  `ID` int(11) NOT NULL,
  `nombreConfiguracion` varchar(255) NOT NULL,
  `valor` varchar(255) NOT NULL,
  `log` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_confifiguraciones`
--

INSERT INTO `tbl_confifiguraciones` (`ID`, `nombreConfiguracion`, `valor`, `log`) VALUES
(1, 'bienvenida Principal', 'Welcome to studio de Jorge', ''),
(3, 'bienvenida_secundaria', 'Un gusto tenerte con nosotros', ''),
(4, 'boton_principal', 'EMPEZAR', ''),
(5, 'link_boton_principal', '#services', ''),
(6, 'titulo_servicios', 'SERVICIOS', ''),
(7, 'descripcion_servicios', 'Un gusto tenerte con nosotros', ''),
(8, 'titulo_noticias', 'NOTICIAS', ''),
(9, 'descripcion_noticias', 'Un gusto tenerte con nosotros', ''),
(10, 'titulo_historia', 'HISTORIA', ''),
(11, 'descripcion_historia', 'Un gusto tenerte con nosotros', ''),
(12, 'ultima_opcion_historia', 'Welcome to studio', ''),
(13, 'titulo_equipo', 'EQUIPO', ''),
(14, 'descripcion_equipo', 'Welcome to studio de Jorge', ''),
(15, 'titulo_contacto', 'CONTACTANOS', ''),
(16, 'descripcion_contacto', 'Un gusto tenerte con nosotros', ''),
(17, 'link_tw', 'linkk', ''),
(18, 'link_fb', '', ''),
(19, 'link_lnk', '', '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_entradas`
--

CREATE TABLE `tbl_entradas` (
  `ID` int(11) NOT NULL,
  `fecha` varchar(255) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `imagen` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_entradas`
--

INSERT INTO `tbl_entradas` (`ID`, `fecha`, `titulo`, `descripcion`, `imagen`) VALUES
(2, '2024-10-02', 'Esto es una prueba', 'acá se puede hacer pruebassss', '1727936385_Visita_a Campus Central.jpg'),
(3, '2024-10-04', 'Esto es una prueba2', 'Use this area to describe your project. Lorem ipsum dolor sit amet, consectetur adipisicing elit. Est blanditiis dolorem culpa incidunt minus dignissimos deserunt repellat aperiam quasi sunt officia expedita beatae cupiditate, maiores repudiandae, nostrum', '1727937099_herramientas-tecnologicas-ayudarte-proceso-ventas.jpg'),
(4, '2024-10-15', 'Tienda en linea', 'La prueba de insertar fue exitosa...2', '1727937207_IMG_20211104_165601.jpg');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_equipo`
--

CREATE TABLE `tbl_equipo` (
  `ID` int(11) NOT NULL,
  `imagen` varchar(255) NOT NULL,
  `nombrecompleto` varchar(255) NOT NULL,
  `puesto` varchar(255) NOT NULL,
  `correo` varchar(255) NOT NULL,
  `linkedin` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_equipo`
--

INSERT INTO `tbl_equipo` (`ID`, `imagen`, `nombrecompleto`, `puesto`, `correo`, `linkedin`) VALUES
(2, '1728014408_Entrega_de_proyecto_final.jpg', 'Equipo UVGj', 'Estudiantes', 'tautiugomezluis@gmail.com', 'Luis'),
(4, '1728362621_IMG_20220426_211151.jpg', 'Luis Gomez', 'CEO', 'tautiugomezluis@gmail.com', 'Luis Jorge'),
(5, '1728362648_226adee2-4987-43fc-970d-7118cc8d0cdb.jpg', 'One Punch Man', 'Anime', 'tautiugomezluis@gmail.com', 'Luis');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_inicioo`
--

CREATE TABLE `tbl_inicioo` (
  `ID` int(11) NOT NULL,
  `componente` varchar(255) NOT NULL,
  `imagen` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_inicioo`
--

INSERT INTO `tbl_inicioo` (`ID`, `componente`, `imagen`) VALUES
(2, 'Portada', '1729056030_011.jpg'),
(3, 'Logo', '1729056063_logo001.png');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_portafolio`
--

CREATE TABLE `tbl_portafolio` (
  `ID` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `subtitulo` varchar(255) NOT NULL,
  `imagen` varchar(255) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `cliente` varchar(255) NOT NULL,
  `categoria` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_portafolio`
--

INSERT INTO `tbl_portafolio` (`ID`, `titulo`, `subtitulo`, `imagen`, `descripcion`, `cliente`, `categoria`, `url`) VALUES
(20, 'Cocacola Prueba', 'Lorem ipsum dolor sit amet consectetur.', '1727407608_Coca-Cola.png', 'Use this area to describe your project. Lorem ipsum dolor sit amet, consectetur adipisicing elit. Est blanditiis dolorem culpa incidunt minus dignissimos deserunt repellat aperiam quasi sunt officia expedita beatae cupiditate, maiores repudiandae, nostrum', 'Client: Explore', '', 'faceboook.com'),
(21, 'Finish', 'Lorem ipsum dolor sit amet consectetur.', '1727407706_herramientas-tecnologicas-ayudarte-proceso-ventas.jpg', 'Use this area to describe your project. Lorem ipsum dolor sit amet, consectetur adipisicing elit. Est blanditiis dolorem culpa incidunt minus dignissimos deserunt repellat aperiam quasi sunt officia expedita beatae cupiditate, maiores repudiandae, nostrum', 'Finish', '', 'faceboook.com'),
(22, 'Lines', 'Branding', '1727407805_Visita_a Campus Central.jpg', 'Use this area to describe your project. Lorem ipsum dolor sit amet, consectetur adipisicing elit. Est blanditiis dolorem culpa incidunt minus dignissimos deserunt repellat aperiam quasi sunt officia expedita beatae cupiditate, maiores repudiandae, nostrum', 'Lines', '', 'faceboook .com');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_servicios`
--

CREATE TABLE `tbl_servicios` (
  `ID` int(11) NOT NULL,
  `icono` varchar(255) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descripcion` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_servicios`
--

INSERT INTO `tbl_servicios` (`ID`, `icono`, `titulo`, `descripcion`) VALUES
(2, 'prueba img22', 'Esto es una prueba2', 'La prueba de insertar fue exitosa...2'),
(3, 'prueba imgeee', 'Esto es una prueba', 'La prueba de insertar fue exitosa...'),
(4, 'prueba imge', 'Esto es una prueba', 'La prueba de insertar fue exitosa...'),
(5, 'prueba img', 'Jorgeww', 'La prueba de insertar fue exitosa...');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_usuarios`
--

CREATE TABLE `tbl_usuarios` (
  `ID` int(11) NOT NULL,
  `usuario` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `correo` varchar(255) NOT NULL,
  `cargo` varchar(255) NOT NULL,
  `unidad` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_usuarios`
--

INSERT INTO `tbl_usuarios` (`ID`, `usuario`, `password`, `correo`, `cargo`, `unidad`) VALUES
(2, 'Jgomez', '12345', 'tautiugomezluis@gmail.com', '', ''),
(3, 'Prueba_tania', '12345', '', '', '');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `tbl_confifiguraciones`
--
ALTER TABLE `tbl_confifiguraciones`
  ADD PRIMARY KEY (`ID`);

--
-- Indices de la tabla `tbl_entradas`
--
ALTER TABLE `tbl_entradas`
  ADD PRIMARY KEY (`ID`);

--
-- Indices de la tabla `tbl_equipo`
--
ALTER TABLE `tbl_equipo`
  ADD PRIMARY KEY (`ID`);

--
-- Indices de la tabla `tbl_inicioo`
--
ALTER TABLE `tbl_inicioo`
  ADD PRIMARY KEY (`ID`);

--
-- Indices de la tabla `tbl_portafolio`
--
ALTER TABLE `tbl_portafolio`
  ADD PRIMARY KEY (`ID`);

--
-- Indices de la tabla `tbl_servicios`
--
ALTER TABLE `tbl_servicios`
  ADD PRIMARY KEY (`ID`);

--
-- Indices de la tabla `tbl_usuarios`
--
ALTER TABLE `tbl_usuarios`
  ADD PRIMARY KEY (`ID`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `tbl_confifiguraciones`
--
ALTER TABLE `tbl_confifiguraciones`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de la tabla `tbl_entradas`
--
ALTER TABLE `tbl_entradas`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `tbl_equipo`
--
ALTER TABLE `tbl_equipo`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `tbl_inicioo`
--
ALTER TABLE `tbl_inicioo`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `tbl_portafolio`
--
ALTER TABLE `tbl_portafolio`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `tbl_servicios`
--
ALTER TABLE `tbl_servicios`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `tbl_usuarios`
--
ALTER TABLE `tbl_usuarios`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
