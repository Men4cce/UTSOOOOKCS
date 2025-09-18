-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Gép: 127.0.0.1
-- Létrehozás ideje: 2025. Sze 18. 15:37
-- Kiszolgáló verziója: 10.4.32-MariaDB
-- PHP verzió: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Adatbázis: `nyomtato`
--

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `eszkozok`
--

CREATE TABLE `eszkozok` (
  `id` int(10) UNSIGNED NOT NULL,
  `vonalkod` varchar(64) NOT NULL,
  `gyariszam` varchar(64) NOT NULL,
  `gep_kod` varchar(64) NOT NULL,
  `nyomtato_tipusa` varchar(128) NOT NULL,
  `toner` varchar(128) NOT NULL,
  `epulet` varchar(36) NOT NULL,
  `leltarkorzet` varchar(36) NOT NULL,
  `ip_cim` varchar(36) NOT NULL,
  `szobaszam` varchar(128) NOT NULL,
  `raktaron` tinyint(4) NOT NULL,
  `toner_ig_rogzites` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `eszkozok`
--

INSERT INTO `eszkozok` (`id`, `vonalkod`, `gyariszam`, `gep_kod`, `nyomtato_tipusa`, `toner`, `epulet`, `leltarkorzet`, `ip_cim`, `szobaszam`, `raktaron`, `toner_ig_rogzites`) VALUES
(1, 'LK235345', 'QHJ53435', '34534', 'HP LaserJet P2015', 'HP 53X (CE253)', 'Markó 27.', '341AF53A', '10.2.66.6', 'fsz.53 dolgozószoba', 1, 1),
(2, 'LK998877', 'QHJ99911', '88221', 'HP LaserJet Pro M402dn', 'HP 26X (CF226X)', 'Markó 27.', '341A112', '10.2.67.7', 'I. em. 12 tárgyaló', 1, 0),
(3, 'LK123456', 'QHJ12345', '77777', 'Brother HL-L2352DW', 'TN-2421', 'Markó 27.', '341AF12D', '10.2.68.8', 'fsz.12 iroda', 1, 1);

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `kiadasok`
--

CREATE TABLE `kiadasok` (
  `id` int(10) UNSIGNED NOT NULL,
  `eszkoz_id` int(10) UNSIGNED NOT NULL,
  `vonalkod` varchar(64) NOT NULL,
  `nyomtato_tipusa` varchar(128) NOT NULL,
  `toner` varchar(128) NOT NULL,
  `atvevo_nev` varchar(128) NOT NULL,
  `kiadas_dt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `kiadasok`
--

INSERT INTO `kiadasok` (`id`, `eszkoz_id`, `vonalkod`, `nyomtato_tipusa`, `toner`, `atvevo_nev`, `kiadas_dt`) VALUES
(1, 1, 'LK235345', 'HP LaserJet P2015', 'HP 53X (CE253)', 'Kovács Anna', '2025-09-18 01:44:56'),
(2, 2, 'LK998877', 'HP LaserJet Pro M402dn', 'HP 26X (CF226X)', 'Teszt János', '2025-09-18 01:45:29'),
(3, 2, 'LK998877', 'HP LaserJet Pro M402dn', 'HP 26X (CF226X)', 'Kovács Péter', '2025-09-18 01:50:09'),
(4, 2, 'LK998877', 'HP LaserJet Pro M402dn', 'HP 26X (CF226X)', 'Kovács Teszt', '2025-09-18 01:53:24'),
(5, 2, 'LK998877', 'HP LaserJet Pro M402dn', 'HP 26X (CF226X)', 'Teszt', '2025-09-18 01:54:48'),
(6, 2, 'LK998877', 'HP LaserJet Pro M402dn', 'HP 26X (CF226X)', 'Teeeeszt', '2025-09-18 01:55:40'),
(7, 2, 'LK998877', 'HP LaserJet Pro M402dn', 'HP 26X (CF226X)', 'Jani', '2025-09-18 01:58:04'),
(8, 2, 'LK998877', 'HP LaserJet Pro M402dn', 'HP 26X (CF226X)', 'adsasds', '2025-09-18 01:59:25'),
(9, 2, 'LK998877', 'HP LaserJet Pro M402dn', 'HP 26X (CF226X)', 'asdasdasdasdsdsddddd', '2025-09-18 02:02:48'),
(10, 2, 'LK998877', 'HP LaserJet Pro M402dn', 'HP 26X (CF226X)', 'ddd', '2025-09-18 02:03:24'),
(11, 2, 'LK998877', 'HP LaserJet Pro M402dn', 'HP 26X (CF226X)', 'eeee', '2025-09-18 02:06:52'),
(12, 1, 'LK235345', 'HP LaserJet P2015', 'HP 53X (CE253)', 'ASD', '2025-09-18 02:14:37'),
(13, 3, 'LK123456', 'Brother HL-L2352DW', 'TN-2421', 'Szia', '2025-09-18 02:21:50'),
(14, 3, 'LK123456', 'Brother HL-L2352DW', 'TN-2421', 'asdasdasd', '2025-09-18 02:42:05'),
(15, 3, 'LK123456', 'Brother HL-L2352DW', 'TN-2421', 'DSA', '2025-09-18 15:26:23');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `rendelesek`
--

CREATE TABLE `rendelesek` (
  `id` int(10) UNSIGNED NOT NULL,
  `eszkoz_id` int(10) UNSIGNED NOT NULL,
  `vonalkod` varchar(64) NOT NULL,
  `nyomtato_tipusa` varchar(128) NOT NULL,
  `toner` varchar(128) NOT NULL,
  `statusz` enum('Rendelve','Megjött') NOT NULL DEFAULT 'Rendelve',
  `rendeles_dt` datetime NOT NULL DEFAULT current_timestamp(),
  `megerkezes_dt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `rendelesek`
--

INSERT INTO `rendelesek` (`id`, `eszkoz_id`, `vonalkod`, `nyomtato_tipusa`, `toner`, `statusz`, `rendeles_dt`, `megerkezes_dt`) VALUES
(1, 2, 'LK998877', 'HP LaserJet Pro M402dn', 'HP 26X (CF226X)', 'Megjött', '2025-09-18 02:20:21', '2025-09-18 02:20:46'),
(2, 3, 'LK123456', 'Brother HL-L2352DW', 'TN-2421', 'Megjött', '2025-09-18 02:21:20', '2025-09-18 02:21:35'),
(3, 3, 'LK123456', 'Brother HL-L2352DW', 'TN-2421', 'Megjött', '2025-09-18 02:40:33', '2025-09-18 02:41:32'),
(4, 3, 'LK123456', 'Brother HL-L2352DW', 'TN-2421', 'Megjött', '2025-09-18 08:04:40', '2025-09-18 08:04:56'),
(5, 3, 'LK123456', 'Brother HL-L2352DW', 'TN-2421', 'Megjött', '2025-09-18 08:05:51', '2025-09-18 08:07:05'),
(6, 1, 'LK235345', 'HP LaserJet P2015', 'HP 53X (CE253)', 'Megjött', '2025-09-18 08:12:54', '2025-09-18 08:13:17'),
(7, 3, 'LK123456', 'Brother HL-L2352DW', 'TN-2421', 'Megjött', '2025-09-18 15:26:57', '2025-09-18 15:28:14'),
(8, 3, 'LK123456', 'Brother HL-L2352DW', 'TN-2421', 'Megjött', '2025-09-18 15:28:01', '2025-09-18 15:28:09');

--
-- Indexek a kiírt táblákhoz
--

--
-- A tábla indexei `eszkozok`
--
ALTER TABLE `eszkozok`
  ADD PRIMARY KEY (`id`);

--
-- A tábla indexei `kiadasok`
--
ALTER TABLE `kiadasok`
  ADD PRIMARY KEY (`id`),
  ADD KEY `eszkoz_id` (`eszkoz_id`);

--
-- A tábla indexei `rendelesek`
--
ALTER TABLE `rendelesek`
  ADD PRIMARY KEY (`id`),
  ADD KEY `eszkoz_id` (`eszkoz_id`),
  ADD KEY `statusz` (`statusz`),
  ADD KEY `rendeles_dt` (`rendeles_dt`);

--
-- A kiírt táblák AUTO_INCREMENT értéke
--

--
-- AUTO_INCREMENT a táblához `eszkozok`
--
ALTER TABLE `eszkozok`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT a táblához `kiadasok`
--
ALTER TABLE `kiadasok`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT a táblához `rendelesek`
--
ALTER TABLE `rendelesek`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Megkötések a kiírt táblákhoz
--

--
-- Megkötések a táblához `kiadasok`
--
ALTER TABLE `kiadasok`
  ADD CONSTRAINT `fk_kiadas_eszkoz` FOREIGN KEY (`eszkoz_id`) REFERENCES `eszkozok` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
