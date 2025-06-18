-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: MySQL-8.0
-- Время создания: Июн 17 2025 г., 01:11
-- Версия сервера: 8.0.35
-- Версия PHP: 8.1.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `learn_programming`
--

-- --------------------------------------------------------

--
-- Структура таблицы `achievements`
--

CREATE TABLE `achievements` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `achievement_type` varchar(50) NOT NULL,
  `earned_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `courses`
--

CREATE TABLE `courses` (
  `id` int NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text,
  `icon` varchar(50) DEFAULT NULL,
  `difficulty` enum('beginner','intermediate','advanced') DEFAULT 'beginner',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_active` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `courses`
--

INSERT INTO `courses` (`id`, `title`, `description`, `icon`, `difficulty`, `created_at`, `is_active`) VALUES
(1, 'Колодец и бабочка', 'fvdsdsvcsdvvsvsd', 'fa-css3', 'beginner', '2025-06-16 15:06:46', 1);

-- --------------------------------------------------------

--
-- Структура таблицы `exercises`
--

CREATE TABLE `exercises` (
  `id` int NOT NULL,
  `lesson_id` int NOT NULL,
  `question` text NOT NULL,
  `answer` text NOT NULL,
  `code_template` text,
  `exercise_type` enum('multiple_choice','code','fill_blank') NOT NULL,
  `order_number` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `exercises`
--

INSERT INTO `exercises` (`id`, `lesson_id`, `question`, `answer`, `code_template`, `exercise_type`, `order_number`, `created_at`) VALUES
(4, 1, 'Какой тег используется для создания абзаца?', 'йцук', '', 'multiple_choice', 1, '2025-06-16 20:09:52');

-- --------------------------------------------------------

--
-- Структура таблицы `exercise_options`
--

CREATE TABLE `exercise_options` (
  `id` int NOT NULL,
  `exercise_id` int NOT NULL,
  `option_text` text NOT NULL,
  `is_correct` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `exercise_options`
--

INSERT INTO `exercise_options` (`id`, `exercise_id`, `option_text`, `is_correct`) VALUES
(147, 4, '<body>', 1),
(148, 4, '<p>', 0);

-- --------------------------------------------------------

--
-- Структура таблицы `lessons`
--

CREATE TABLE `lessons` (
  `id` int NOT NULL,
  `course_id` int NOT NULL,
  `title` varchar(100) NOT NULL,
  `content` text NOT NULL,
  `order_number` int NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `duration_minutes` int DEFAULT '10'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `lessons`
--

INSERT INTO `lessons` (`id`, `course_id`, `title`, `content`, `order_number`, `is_active`, `duration_minutes`) VALUES
(1, 1, 'Html - урок №1', 'Цель урока:\r\nПознакомить учащихся с базовыми понятиями HTML, структурой документа и простейшими тегами.\r\n\r\nПлан урока (45-60 минут):\r\n1. Введение (10 минут)\r\nЧто такое HTML (HyperText Markup Language)?\r\n\r\nРоль HTML в создании веб-страниц\r\n\r\nБраузеры и их функция интерпретации HTML\r\n\r\nПростые аналогии (HTML как скелет страницы)\r\n\r\n2. Базовая структура HTML-документа (15 минут)\r\nhtml\r\n<!DOCTYPE html>\r\n<html>\r\n<head>\r\n    <title>Моя первая страница</title>\r\n</head>\r\n<body>\r\n    <h1>Привет, мир!</h1>\r\n    <p>Это мой первый HTML-документ.</p>\r\n</body>\r\n</html>\r\nОбъяснение каждого элемента:\r\n\r\n<!DOCTYPE html> - объявление типа документа\r\n\r\n<html> - корневой элемент\r\n\r\n<head> - метаинформация\r\n\r\n<title> - заголовок вкладки браузера\r\n\r\n<body> - видимое содержимое страницы\r\n\r\n3. Основные теги (15 минут)\r\nЗаголовки: <h1>-<h6>\r\n\r\nАбзацы: <p>\r\n\r\nСсылки: <a href=\"url\">текст</a>\r\n\r\nИзображения: <img src=\"image.jpg\" alt=\"описание\">\r\n\r\nРазрывы строк: <br>\r\n\r\nГоризонтальные линии: <hr>\r\n\r\n4. Практическое задание (15-20 минут)\r\nСоздать простую страницу с:\r\n\r\nЗаголовком\r\n\r\nДвумя абзацами текста\r\n\r\nСсылкой\r\n\r\nИзображением\r\n\r\n5. Домашнее задание\r\nСоздать страницу \"О себе\" с использованием изученных тегов\r\n\r\nНайти и записать назначение 3 тегов, которые не рассматривались на уроке', 1, 1, 15);

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_active` tinyint(1) DEFAULT '1',
  `is_admin` tinyint(1) DEFAULT '0',
  `last_login` timestamp NULL DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT 'default.jpg',
  `bio` text,
  `xp` int DEFAULT '0',
  `streak_days` int DEFAULT '0',
  `last_streak_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `full_name`, `created_at`, `is_active`, `is_admin`, `last_login`, `profile_picture`, `bio`, `xp`, `streak_days`, `last_streak_date`) VALUES
(1, 'Chocolate_Gypsy', 'alexpro65658787@gmail.com', '$2y$10$K2fKIYLrHJdFuptDB9ekCetb7C38quFjjrMnVoBw8zdzl0O5ko5Xu', 'Морозов Александр Юрьевич', '2025-06-16 14:32:18', 1, 1, '2025-06-16 15:53:18', 'user_1_1750106020.png', 'я крутой', 0, 1, '2025-06-16');

-- --------------------------------------------------------

--
-- Структура таблицы `user_exercise_progress`
--

CREATE TABLE `user_exercise_progress` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `lesson_id` int NOT NULL,
  `exercise_id` int NOT NULL,
  `completed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `user_progress`
--

CREATE TABLE `user_progress` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `course_id` int NOT NULL,
  `lesson_id` int NOT NULL,
  `completed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `score` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `achievements`
--
ALTER TABLE `achievements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `exercises`
--
ALTER TABLE `exercises`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lesson_id` (`lesson_id`);

--
-- Индексы таблицы `exercise_options`
--
ALTER TABLE `exercise_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exercise_id` (`exercise_id`);

--
-- Индексы таблицы `lessons`
--
ALTER TABLE `lessons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Индексы таблицы `user_exercise_progress`
--
ALTER TABLE `user_exercise_progress`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `lesson_id` (`lesson_id`),
  ADD KEY `exercise_id` (`exercise_id`);

--
-- Индексы таблицы `user_progress`
--
ALTER TABLE `user_progress`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `lesson_id` (`lesson_id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `achievements`
--
ALTER TABLE `achievements`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `exercises`
--
ALTER TABLE `exercises`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблицы `exercise_options`
--
ALTER TABLE `exercise_options`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=149;

--
-- AUTO_INCREMENT для таблицы `lessons`
--
ALTER TABLE `lessons`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `user_exercise_progress`
--
ALTER TABLE `user_exercise_progress`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `user_progress`
--
ALTER TABLE `user_progress`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `achievements`
--
ALTER TABLE `achievements`
  ADD CONSTRAINT `achievements_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `exercises`
--
ALTER TABLE `exercises`
  ADD CONSTRAINT `exercises_ibfk_1` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `exercise_options`
--
ALTER TABLE `exercise_options`
  ADD CONSTRAINT `exercise_options_ibfk_1` FOREIGN KEY (`exercise_id`) REFERENCES `exercises` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `lessons`
--
ALTER TABLE `lessons`
  ADD CONSTRAINT `lessons_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `user_exercise_progress`
--
ALTER TABLE `user_exercise_progress`
  ADD CONSTRAINT `user_exercise_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_exercise_progress_ibfk_2` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_exercise_progress_ibfk_3` FOREIGN KEY (`exercise_id`) REFERENCES `exercises` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `user_progress`
--
ALTER TABLE `user_progress`
  ADD CONSTRAINT `user_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_progress_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_progress_ibfk_3` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
