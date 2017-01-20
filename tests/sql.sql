-- MySQL Center SQL Экспорт
-- версия  1.124
--
-- Хост: localhost
-- Время создания: 27.10.2012, 08-17
-- Версия сервера: 5.1.40-community
-- Версия PHP: 5.2.4
--
-- БД: `mantis`
--

-- --------------------------------------------------------
--
-- Структура таблицы asdfsdfsdf
--

CREATE TABLE `asdfsdfsdf` (
  `sadfsdfdsf` varchar(255) NOT NULL,
  `sdafsdfsdfsdf` varchar(255) NOT NULL,
  `sdfasdf` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
--
-- Структура таблицы config
--

CREATE TABLE `config` (
  `id` tinyint(3) unsigned NOT NULL auto_increment,
  `content` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  `type` enum('string','integer','date','text','boolean') NOT NULL default 'string',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;

--
-- Дамп данных таблицы config
--

INSERT INTO `config` VALUES (1,'Время (в часах), в течение которого задача считается обновлённой','updateTime','6','integer');
INSERT INTO `config` VALUES (2,'Цвет статуса new','statusColor_new','#fdd','string');
INSERT INTO `config` VALUES (3,'Цвет статуса resolved','statusColor_resolved','#dfd','string');
INSERT INTO `config` VALUES (4,'Заголовок программы','programTitle','Task Manager','string');
INSERT INTO `config` VALUES (5,'Цвет статуса unknown','statusColor_unknown','#009999','string');
INSERT INTO `config` VALUES (6,'Задач на страницу','taskOnPage','100','string');
INSERT INTO `config` VALUES (7,'Цвет статуса closed','statusColor_closed','#ccc','string');
INSERT INTO `config` VALUES (8,'Цвет new major','statusColor_new_major','#faa','string');
INSERT INTO `config` VALUES (9,'Цвет шрифта bug','statusColor_bug','darkred','string');
INSERT INTO `config` VALUES (10,'Не показывать задания старше этой даты','showProjectsFromDate','1172696400','date');

--
-- Структура таблицы dfsdfg
--

CREATE TABLE `dfsdfg` (
  `name` varchar(255) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
--
-- Структура таблицы history
--

CREATE TABLE `history` (
  `id` smallint(5) unsigned NOT NULL auto_increment,
  `item_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `task_id` smallint(5) unsigned NOT NULL default '0',
  `param` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;

--
-- Дамп данных таблицы history
--

INSERT INTO `history` VALUES (1,'2009-04-24 09:54:14',13,'Status set to','resolved');
INSERT INTO `history` VALUES (2,'2009-04-24 09:55:49',13,'Note #3 added','');
INSERT INTO `history` VALUES (3,'2009-04-24 09:58:17',16,'Status set to','resolved');
INSERT INTO `history` VALUES (4,'2009-04-24 10:49:54',16,'Set task_title to','Историю задач сделать');
INSERT INTO `history` VALUES (5,'2009-04-24 10:50:32',17,'Status set to','resolved');
INSERT INTO `history` VALUES (6,'2009-04-27 16:55:44',22,'Status set to','resolved');
INSERT INTO `history` VALUES (10,'2009-04-30 14:34:11',23,'Status set to','resolved');
INSERT INTO `history` VALUES (9,'2009-04-27 17:00:57',22,'Set hours to','0.2');
INSERT INTO `history` VALUES (11,'2009-04-30 14:46:48',1,'Set name to','updateTime');
INSERT INTO `history` VALUES (12,'2009-04-30 14:48:10',1,'Set value to','100');
INSERT INTO `history` VALUES (13,'2009-04-30 14:48:28',1,'Set value to','6');
INSERT INTO `history` VALUES (14,'2009-04-30 14:53:25',18,'Status set to','resolved');
INSERT INTO `history` VALUES (15,'2009-04-30 14:53:33',18,'Set hours to','0.2');
INSERT INTO `history` VALUES (16,'2009-04-30 14:54:32',23,'Set hours to','0.2');
INSERT INTO `history` VALUES (17,'2009-04-30 14:54:53',24,'Set hours to','0.1');
INSERT INTO `history` VALUES (18,'2009-04-30 14:54:53',24,'Set status to','resolved');
INSERT INTO `history` VALUES (26,'2009-04-30 15:06:06',13,'Note #5 added','');
INSERT INTO `history` VALUES (25,'2009-04-30 15:04:18',21,'Note #4 added','');
INSERT INTO `history` VALUES (24,'2009-04-30 15:02:09',21,'Set status to','unknown');
INSERT INTO `history` VALUES (23,'2009-04-30 15:00:01',19,'Set hours to','0.1');
INSERT INTO `history` VALUES (27,'2009-04-30 15:29:39',15,'Set hours to','0.2');
INSERT INTO `history` VALUES (28,'2009-04-30 15:29:39',15,'Set status to','resolved');
INSERT INTO `history` VALUES (33,'2009-04-30 15:56:38',12,'Set task_title to','Управление статусами');
INSERT INTO `history` VALUES (30,'2009-04-30 15:44:02',8,'Set status to','resolved');
INSERT INTO `history` VALUES (31,'2009-04-30 15:44:14',14,'Set hours to','0.2');
INSERT INTO `history` VALUES (32,'2009-04-30 15:44:14',14,'Set status to','resolved');
INSERT INTO `history` VALUES (34,'2009-04-30 15:56:38',12,'Set status to','closed');
INSERT INTO `history` VALUES (35,'2009-04-30 15:57:34',12,'Note #6 added','');
INSERT INTO `history` VALUES (36,'2010-02-11 18:17:06',20,'Status set to','unknown');
INSERT INTO `history` VALUES (37,'2010-02-21 22:35:57',38,'Set hours to','0.1');
INSERT INTO `history` VALUES (38,'2010-02-21 22:36:03',38,'Status set to','resolved');
INSERT INTO `history` VALUES (39,'2010-02-21 22:36:46',38,'Note #7 added','');
INSERT INTO `history` VALUES (40,'2010-02-21 22:53:01',37,'Set hours to','0.2');
INSERT INTO `history` VALUES (41,'2010-02-21 22:53:04',37,'Status set to','resolved');
INSERT INTO `history` VALUES (42,'2010-02-21 22:54:53',36,'Note #8 added','');
INSERT INTO `history` VALUES (43,'2010-02-21 22:56:03',36,'Status set to','unknown');
INSERT INTO `history` VALUES (44,'2010-02-21 23:05:11',35,'Set hours to','02');
INSERT INTO `history` VALUES (45,'2010-02-21 23:05:17',35,'Set hours to','0.2');
INSERT INTO `history` VALUES (46,'2010-02-21 23:05:21',35,'Status set to','resolved');
INSERT INTO `history` VALUES (47,'2010-02-21 23:06:06',33,'Note #9 added','');
INSERT INTO `history` VALUES (48,'2010-02-21 23:06:08',33,'Status set to','unknown');
INSERT INTO `history` VALUES (49,'2010-02-23 10:45:04',28,'Set hours to','0.2');
INSERT INTO `history` VALUES (50,'2010-02-23 10:45:07',28,'Status set to','resolved');
INSERT INTO `history` VALUES (51,'2010-02-23 15:35:56',31,'Set hours to','3');
INSERT INTO `history` VALUES (52,'2010-02-23 15:35:58',31,'Status set to','resolved');
INSERT INTO `history` VALUES (53,'2010-02-24 18:48:58',46,'Set hours to','0.2');
INSERT INTO `history` VALUES (54,'2010-02-24 18:49:01',46,'Status set to','resolved');
INSERT INTO `history` VALUES (55,'2010-02-24 19:33:36',48,'Set task_title to','Добавить статистику просмотров');
INSERT INTO `history` VALUES (56,'2010-02-24 19:33:36',48,'Set content to','сколько % фильмов не смотрел, сколько частично и пр.');
INSERT INTO `history` VALUES (57,'2010-02-25 23:39:50',44,'Set hours to','0.1');
INSERT INTO `history` VALUES (58,'2010-02-25 23:39:52',44,'Status set to','resolved');
INSERT INTO `history` VALUES (59,'2010-02-25 23:40:12',29,'Set hours to','1');
INSERT INTO `history` VALUES (60,'2010-02-25 23:40:27',29,'Note #10 added','');
INSERT INTO `history` VALUES (61,'2010-02-27 00:53:17',56,'Note #11 added','');
INSERT INTO `history` VALUES (62,'2010-02-27 08:14:18',45,'Set task_title to','');
INSERT INTO `history` VALUES (63,'2010-02-27 08:14:18',45,'Set content to','В принципе не так важно');
INSERT INTO `history` VALUES (64,'2010-02-27 08:14:18',45,'Set status to','closed');
INSERT INTO `history` VALUES (65,'2010-02-27 08:15:21',45,'Set task_title to','\"По таблице\" - не показывать таблицы, уже имеющие объекты');
INSERT INTO `history` VALUES (66,'2010-02-27 20:06:55',59,'Set task_title to','xla.http.readyState == 3 - проблема');
INSERT INTO `history` VALUES (67,'2010-02-27 20:06:55',59,'Set content to',', как отловить критические ошибки без alert для всех');
INSERT INTO `history` VALUES (68,'2010-02-28 07:35:28',34,'Set task_title to','Поля данных - при изменении параметра, надо делать ALTER TABLE CHANGE...');
INSERT INTO `history` VALUES (69,'2010-02-28 07:35:42',34,'Set task_title to','Поля данных - при изменении параметра, надо делать ALTER...');
INSERT INTO `history` VALUES (70,'2010-02-28 07:48:27',60,'Note #12 added','');
INSERT INTO `history` VALUES (71,'2010-02-28 07:48:37',60,'Set content to','Это будет скрипт, который считывает все библиотечные файлы, и выводит инфо по функциям.');
INSERT INTO `history` VALUES (72,'2010-02-28 09:21:16',60,'Set task_title to','Сделать справочник функцйи автоматический');
INSERT INTO `history` VALUES (73,'2010-03-05 15:34:09',56,'Status set to','resolved');
INSERT INTO `history` VALUES (74,'2010-03-05 15:38:41',66,'Set content to','по умолчанию 0 - обычный. Например, для сайта главы, сайтов районов, Комионлайн, БНКоми.');
INSERT INTO `history` VALUES (75,'2010-03-05 16:37:56',63,'Set hours to','0.5');
INSERT INTO `history` VALUES (76,'2010-03-05 16:37:56',63,'Set status to','resolved');
INSERT INTO `history` VALUES (77,'2010-03-06 13:04:28',66,'Set hours to','0.5');
INSERT INTO `history` VALUES (78,'2010-03-06 13:04:28',66,'Set status to','resolved');
INSERT INTO `history` VALUES (79,'2010-03-06 13:04:54',59,'Set hours to','0.1');
INSERT INTO `history` VALUES (80,'2010-03-06 13:04:54',59,'Set status to','resolved');
INSERT INTO `history` VALUES (81,'2010-03-06 13:05:32',59,'Note #13 added','');
INSERT INTO `history` VALUES (82,'2010-03-06 13:05:57',58,'Set hours to','2');
INSERT INTO `history` VALUES (83,'2010-03-06 13:05:57',58,'Set status to','resolved');
INSERT INTO `history` VALUES (84,'2010-03-06 13:06:30',55,'Status set to','closed');
INSERT INTO `history` VALUES (85,'2010-03-06 13:06:42',57,'Set hours to','1');
INSERT INTO `history` VALUES (86,'2010-03-06 13:06:42',57,'Set status to','resolved');
INSERT INTO `history` VALUES (87,'2010-03-06 13:06:54',54,'Status set to','unknown');
INSERT INTO `history` VALUES (88,'2010-03-06 19:20:51',60,'Set hours to','1.5');
INSERT INTO `history` VALUES (89,'2010-03-06 19:21:07',60,'Set task_title to','Сделать справочник функций автоматический');
INSERT INTO `history` VALUES (90,'2010-03-06 22:49:09',60,'Set hours to','4.5');
INSERT INTO `history` VALUES (91,'2010-03-06 22:50:28',60,'Note #14 added','');
INSERT INTO `history` VALUES (92,'2010-03-06 22:51:11',30,'Set hours to','0.5');
INSERT INTO `history` VALUES (93,'2010-03-06 22:51:11',30,'Set status to','resolved');
INSERT INTO `history` VALUES (94,'2010-03-07 11:27:23',52,'Status set to','closed');
INSERT INTO `history` VALUES (95,'2010-03-07 11:27:39',28,'Set hours to','0.5');
INSERT INTO `history` VALUES (96,'2010-03-07 11:28:03',60,'Set hours to','5');
INSERT INTO `history` VALUES (97,'2010-03-07 11:28:03',60,'Set status to','resolved');
INSERT INTO `history` VALUES (98,'2010-03-07 17:58:30',32,'Set hours to','2');
INSERT INTO `history` VALUES (99,'2010-03-07 17:58:49',47,'Set hours to','1');
INSERT INTO `history` VALUES (100,'2010-03-07 17:58:49',47,'Set status to','resolved');
INSERT INTO `history` VALUES (101,'2010-03-07 17:59:45',51,'Set hours to','2');
INSERT INTO `history` VALUES (102,'2010-03-07 18:00:09',51,'Note #15 added','');
INSERT INTO `history` VALUES (103,'2010-03-07 18:07:56',5,'Set value to','#faa');
INSERT INTO `history` VALUES (104,'2010-03-07 18:08:26',5,'Set value to','#009999');
INSERT INTO `history` VALUES (105,'2010-03-07 18:08:42',8,'Set value to','#fdd');
INSERT INTO `history` VALUES (106,'2010-03-07 18:09:17',8,'Set value to','#ff6600');
INSERT INTO `history` VALUES (107,'2010-03-07 18:09:59',8,'Set value to','#ffcc33');
INSERT INTO `history` VALUES (108,'2010-03-07 18:10:18',8,'Set value to','#faa');
INSERT INTO `history` VALUES (109,'2010-03-07 18:10:54',32,'Status set to','resolved');
INSERT INTO `history` VALUES (110,'2010-03-07 23:15:45',29,'Set hours to','3');
INSERT INTO `history` VALUES (111,'2010-03-07 23:15:45',29,'Set status to','resolved');
INSERT INTO `history` VALUES (112,'2010-03-09 17:57:20',68,'Set hours to','1');
INSERT INTO `history` VALUES (113,'2010-03-09 17:57:20',68,'Set status to','resolved');
INSERT INTO `history` VALUES (114,'2010-03-09 17:57:38',67,'Status set to','closed');
INSERT INTO `history` VALUES (115,'2010-03-09 18:21:54',69,'Note #16 added','');
INSERT INTO `history` VALUES (116,'2010-03-10 13:45:18',76,'Set hours to','0.3');
INSERT INTO `history` VALUES (117,'2010-03-10 13:45:18',76,'Set status to','resolved');
INSERT INTO `history` VALUES (118,'2010-03-10 13:46:49',75,'Set hours to','0.1');
INSERT INTO `history` VALUES (119,'2010-03-10 13:46:49',75,'Set status to','resolved');
INSERT INTO `history` VALUES (120,'2010-03-10 13:47:36',71,'Set hours to','0.1');
INSERT INTO `history` VALUES (121,'2010-03-10 13:47:36',71,'Set status to','resolved');
INSERT INTO `history` VALUES (122,'2010-03-10 13:48:19',70,'Set hours to','0.1');
INSERT INTO `history` VALUES (123,'2010-03-10 13:48:19',70,'Set status to','resolved');
INSERT INTO `history` VALUES (124,'2010-03-10 13:57:46',73,'Set hours to','0.2');
INSERT INTO `history` VALUES (125,'2010-03-10 13:57:46',73,'Set status to','resolved');
INSERT INTO `history` VALUES (126,'2010-03-10 22:36:56',69,'Set hours to','2');
INSERT INTO `history` VALUES (127,'2010-03-10 22:36:56',69,'Set status to','resolved');
INSERT INTO `history` VALUES (128,'2010-03-10 22:37:06',77,'Set hours to','1');
INSERT INTO `history` VALUES (129,'2010-03-10 22:37:14',72,'Set hours to','3');
INSERT INTO `history` VALUES (130,'2010-03-10 22:37:21',74,'Status set to','unknown');
INSERT INTO `history` VALUES (131,'2010-03-11 18:40:27',72,'Set hours to','10');
INSERT INTO `history` VALUES (132,'2010-03-11 18:40:27',72,'Set status to','resolved');
INSERT INTO `history` VALUES (133,'2010-03-11 18:40:43',77,'Set hours to','2');
INSERT INTO `history` VALUES (134,'2010-03-12 19:15:28',80,'Note #17 added','');
INSERT INTO `history` VALUES (135,'2010-03-12 20:38:33',78,'Set hours to','0.5');
INSERT INTO `history` VALUES (136,'2010-03-12 21:32:01',77,'Set hours to','3');
INSERT INTO `history` VALUES (137,'2010-03-12 22:07:55',77,'Set hours to','4');
INSERT INTO `history` VALUES (138,'2010-03-13 08:47:38',107,'Set hours to','1');
INSERT INTO `history` VALUES (139,'2010-03-13 08:47:38',107,'Set status to','resolved');
INSERT INTO `history` VALUES (140,'2010-03-13 15:12:09',79,'Set hours to','1');
INSERT INTO `history` VALUES (141,'2010-03-13 15:12:09',79,'Set status to','resolved');
INSERT INTO `history` VALUES (142,'2010-03-13 15:12:21',97,'Set hours to','1');
INSERT INTO `history` VALUES (143,'2010-03-13 15:12:21',97,'Set status to','resolved');
INSERT INTO `history` VALUES (144,'2010-03-13 15:12:35',88,'Set hours to','0.5');
INSERT INTO `history` VALUES (145,'2010-03-13 15:12:35',88,'Set status to','resolved');
INSERT INTO `history` VALUES (146,'2010-03-13 16:32:52',98,'Set hours to','1');
INSERT INTO `history` VALUES (147,'2010-03-13 16:32:52',98,'Set status to','resolved');
INSERT INTO `history` VALUES (148,'2010-03-13 16:49:52',82,'Set hours to','0.3');
INSERT INTO `history` VALUES (149,'2010-03-13 16:49:52',82,'Set status to','resolved');
INSERT INTO `history` VALUES (150,'2010-03-13 18:17:02',99,'Set hours to','1');
INSERT INTO `history` VALUES (151,'2010-03-13 18:17:02',99,'Set status to','resolved');
INSERT INTO `history` VALUES (152,'2010-03-13 20:58:45',108,'Set hours to','0.1');
INSERT INTO `history` VALUES (153,'2010-03-13 20:58:45',108,'Set status to','resolved');
INSERT INTO `history` VALUES (154,'2010-03-13 23:47:02',86,'Set hours to','1');
INSERT INTO `history` VALUES (155,'2010-03-14 10:43:05',95,'Set hours to','0.5');
INSERT INTO `history` VALUES (156,'2010-03-14 10:43:05',95,'Set status to','resolved');
INSERT INTO `history` VALUES (157,'2010-03-14 11:55:00',112,'Set hours to','1');
INSERT INTO `history` VALUES (158,'2010-03-14 11:55:00',112,'Set status to','resolved');
INSERT INTO `history` VALUES (159,'2010-03-14 11:56:25',104,'Note #18 added','');
INSERT INTO `history` VALUES (160,'2010-03-14 11:59:07',104,'Note #19 added','');
INSERT INTO `history` VALUES (161,'2010-03-14 12:00:57',104,'Note #20 added','');
INSERT INTO `history` VALUES (162,'2010-03-14 12:02:42',104,'Set hours to','0.2');
INSERT INTO `history` VALUES (163,'2010-03-14 12:02:42',104,'Set status to','resolved');
INSERT INTO `history` VALUES (164,'2010-03-14 12:20:29',110,'Set hours to','0.2');
INSERT INTO `history` VALUES (165,'2010-03-14 12:20:29',110,'Set status to','resolved');
INSERT INTO `history` VALUES (166,'2010-03-14 12:50:30',109,'Set hours to','0.2');
INSERT INTO `history` VALUES (167,'2010-03-14 12:50:30',109,'Set status to','resolved');
INSERT INTO `history` VALUES (168,'2010-03-14 12:54:17',81,'Set hours to','0.1');
INSERT INTO `history` VALUES (169,'2010-03-14 12:54:51',81,'Note #21 added','');
INSERT INTO `history` VALUES (170,'2010-03-14 14:21:30',80,'Set hours to','1.5');
INSERT INTO `history` VALUES (171,'2010-03-14 14:21:30',80,'Set status to','resolved');
INSERT INTO `history` VALUES (172,'2010-03-14 14:53:33',89,'Set hours to','0.5');
INSERT INTO `history` VALUES (173,'2010-03-14 14:53:33',89,'Set status to','resolved');
INSERT INTO `history` VALUES (174,'2010-03-14 19:58:09',114,'Set hours to','0.2');
INSERT INTO `history` VALUES (175,'2010-03-14 19:58:09',114,'Set status to','resolved');
INSERT INTO `history` VALUES (176,'2010-03-14 21:41:05',77,'Set hours to','5');
INSERT INTO `history` VALUES (177,'2010-03-15 18:42:18',51,'Set hours to','3');
INSERT INTO `history` VALUES (178,'2010-03-15 18:42:23',51,'Status set to','resolved');
INSERT INTO `history` VALUES (179,'2010-03-15 18:42:49',51,'Note #22 added','');
INSERT INTO `history` VALUES (180,'2010-03-15 23:32:43',116,'Set hours to','2');
INSERT INTO `history` VALUES (181,'2010-03-15 23:32:43',116,'Set status to','resolved');
INSERT INTO `history` VALUES (182,'2010-03-16 00:03:02',118,'Set hours to','0.5');
INSERT INTO `history` VALUES (183,'2010-03-16 20:20:13',118,'Set hours to','1.5');
INSERT INTO `history` VALUES (184,'2010-03-16 20:20:13',118,'Set status to','resolved');
INSERT INTO `history` VALUES (185,'2010-06-29 17:48:25',106,'Set hours to','1');
INSERT INTO `history` VALUES (186,'2010-07-02 18:22:49',26,'Status set to','unknown');
INSERT INTO `history` VALUES (187,'2010-07-03 11:36:33',40,'Set hours to','0.5');
INSERT INTO `history` VALUES (188,'2010-07-03 14:51:00',27,'Set hours to','0.5');
INSERT INTO `history` VALUES (189,'2010-07-03 14:51:00',27,'Set status to','resolved');
INSERT INTO `history` VALUES (190,'2010-07-03 14:51:22',121,'Set hours to','0.5');
INSERT INTO `history` VALUES (191,'2010-07-03 14:51:31',41,'Set hours to','0.5');
INSERT INTO `history` VALUES (192,'2010-07-03 19:08:15',121,'Set hours to','1.5');
INSERT INTO `history` VALUES (193,'2010-07-03 19:08:15',121,'Set status to','resolved');
INSERT INTO `history` VALUES (194,'2010-07-03 19:08:23',41,'Set hours to','1');
INSERT INTO `history` VALUES (195,'2010-07-03 19:08:23',41,'Set status to','resolved');
INSERT INTO `history` VALUES (196,'2010-07-04 18:02:49',40,'Set hours to','1.5');
INSERT INTO `history` VALUES (197,'2010-07-04 18:02:49',40,'Set status to','resolved');
INSERT INTO `history` VALUES (198,'2010-07-04 18:11:53',122,'Set hours to','0.2');
INSERT INTO `history` VALUES (199,'2010-07-04 18:11:53',122,'Set status to','resolved');
INSERT INTO `history` VALUES (200,'2010-07-04 18:12:03',42,'Status set to','closed');
INSERT INTO `history` VALUES (201,'2010-07-04 18:12:10',26,'Status set to','closed');
INSERT INTO `history` VALUES (202,'2010-07-04 18:19:05',123,'Set severity to','major');
INSERT INTO `history` VALUES (203,'2010-07-04 18:19:05',123,'Set type to','bug');
INSERT INTO `history` VALUES (204,'2010-07-04 18:53:09',121,'Set task_title to','Даты рождения актеров, рост, годы жизни и др. инфо');
INSERT INTO `history` VALUES (205,'2010-07-04 20:43:34',124,'Set hours to','1.5');
INSERT INTO `history` VALUES (206,'2010-07-04 20:43:34',124,'Set status to','resolved');
INSERT INTO `history` VALUES (207,'2010-07-05 19:46:28',123,'Set hours to','0.5');
INSERT INTO `history` VALUES (208,'2010-07-05 19:46:28',123,'Set status to','resolved');
INSERT INTO `history` VALUES (209,'2010-07-09 22:24:13',126,'Set hours to','0.5');
INSERT INTO `history` VALUES (210,'2010-07-09 22:24:13',126,'Set status to','resolved');
INSERT INTO `history` VALUES (211,'2010-07-09 22:48:59',127,'Set hours to','0.5');
INSERT INTO `history` VALUES (212,'2010-07-09 22:48:59',127,'Set status to','resolved');
INSERT INTO `history` VALUES (213,'2010-07-10 02:03:57',129,'Set hours to','0.1');
INSERT INTO `history` VALUES (214,'2010-07-10 02:03:57',129,'Set status to','resolved');
INSERT INTO `history` VALUES (215,'2010-07-10 11:15:59',131,'Set hours to','0.5');
INSERT INTO `history` VALUES (216,'2010-07-10 11:15:59',131,'Set status to','resolved');
INSERT INTO `history` VALUES (217,'2010-07-10 11:16:15',117,'Set hours to','0.5');
INSERT INTO `history` VALUES (218,'2010-07-10 11:16:15',117,'Set status to','resolved');
INSERT INTO `history` VALUES (219,'2010-07-10 11:16:25',64,'Status set to','closed');
INSERT INTO `history` VALUES (220,'2010-07-10 11:16:36',62,'Status set to','closed');
INSERT INTO `history` VALUES (221,'2010-07-10 12:48:08',128,'Set hours to','1');
INSERT INTO `history` VALUES (222,'2010-07-10 12:48:08',128,'Set status to','resolved');
INSERT INTO `history` VALUES (223,'2010-07-10 23:07:28',133,'Set hours to','1');
INSERT INTO `history` VALUES (224,'2010-07-12 18:21:34',119,'Set hours to','2');
INSERT INTO `history` VALUES (225,'2010-07-12 18:21:34',119,'Set status to','resolved');
INSERT INTO `history` VALUES (226,'2012-10-15 08:35:28',135,'Status set to','resolved');
INSERT INTO `history` VALUES (227,'2012-10-15 08:35:30',135,'Status set to','closed');
INSERT INTO `history` VALUES (228,'2012-10-15 08:35:32',135,'Status set to','new');
INSERT INTO `history` VALUES (229,'2012-10-16 03:14:22',10,'Set value to','1293829200');
INSERT INTO `history` VALUES (230,'2012-10-16 03:15:05',10,'Set content to','Не показывать задания старше этой даты');
INSERT INTO `history` VALUES (231,'2012-10-16 03:15:05',10,'Set name to','showProjectsFromDate');
INSERT INTO `history` VALUES (232,'2012-10-16 03:21:30',10,'Set type to','integer');
INSERT INTO `history` VALUES (233,'2012-10-16 03:21:37',10,'Set type to','date');
INSERT INTO `history` VALUES (234,'2012-10-16 03:38:45',10,'Set value to','1230757200');
INSERT INTO `history` VALUES (235,'2012-10-16 03:38:58',10,'Set value to','1267390800');
INSERT INTO `history` VALUES (236,'2012-10-16 03:39:12',10,'Set value to','1172696400');
INSERT INTO `history` VALUES (237,'2012-10-16 04:20:08',136,'Set hours to','0.5');
INSERT INTO `history` VALUES (238,'2012-10-16 04:20:08',136,'Set status to','resolved');
INSERT INTO `history` VALUES (239,'2012-10-16 06:09:42',149,'Note #23 added','');
INSERT INTO `history` VALUES (240,'2012-10-16 06:09:56',149,'Note #24 added','');
INSERT INTO `history` VALUES (241,'2012-10-16 06:10:07',149,'Note #25 added','');
INSERT INTO `history` VALUES (242,'2012-10-16 10:23:23',140,'Set status to','resolved');
INSERT INTO `history` VALUES (243,'2012-10-24 05:42:42',143,'Note #26 added','');
INSERT INTO `history` VALUES (244,'2012-10-24 06:00:51',143,'Status set to','resolved');
INSERT INTO `history` VALUES (247,'2012-10-24 06:19:09',142,'Status set to','resolved');
INSERT INTO `history` VALUES (248,'2012-10-24 07:23:19',144,'Status set to','resolved');
INSERT INTO `history` VALUES (249,'2012-10-24 07:23:43',145,'Status set to','resolved');
INSERT INTO `history` VALUES (250,'2012-10-24 07:26:32',146,'Status set to','resolved');
INSERT INTO `history` VALUES (251,'2012-10-24 07:30:33',147,'Status set to','resolved');

--
-- Структура таблицы notes
--

CREATE TABLE `notes` (
  `note_id` smallint(5) unsigned NOT NULL auto_increment,
  `content` text NOT NULL,
  `created_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `updated_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `task_id` smallint(5) unsigned NOT NULL default '0',
  PRIMARY KEY  (`note_id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;

--
-- Дамп данных таблицы notes
--

INSERT INTO `notes` VALUES (1,'Хорошая задачка','2009-04-24 09:39:27','2009-04-24 09:39:27',17);
INSERT INTO `notes` VALUES (2,'Да','2009-04-24 09:48:14','2009-04-24 09:48:14',17);
INSERT INTO `notes` VALUES (3,'Задание выполнено','2009-04-24 09:55:49','2009-04-24 09:55:49',13);
INSERT INTO `notes` VALUES (4,'Нужно уточнить список приоритетов, что надо добавить то вообще','2009-04-30 15:04:18','2009-04-30 15:04:18',21);
INSERT INTO `notes` VALUES (5,'Пофиксил комментарии, добавил обновление поля task_id','2009-04-30 15:06:06','2009-04-30 15:06:06',13);
INSERT INTO `notes` VALUES (6,'Задача пока отменена, так как можно управлять статусами в базе данных, это редко надо','2009-04-30 15:57:34','2009-04-30 15:57:34',12);
INSERT INTO `notes` VALUES (7,'Пофиксил вывод данных в форме если тип данных строка.','2010-02-21 22:36:46','2010-02-21 22:36:46',38);
INSERT INTO `notes` VALUES (8,'Проблема видимо в том, что при создании таких объектов нужно делать поле родитель необязательным.\r\nЕсли так делать, то все нормально.','2010-02-21 22:54:53','2010-02-21 22:54:53',36);
INSERT INTO `notes` VALUES (9,'Можно поменять шрифт браузера, что я и сделал','2010-02-21 23:06:06','2010-02-21 23:06:06',33);
INSERT INTO `notes` VALUES (10,'Частично попытался решить проблему, добавил файл install.php','2010-02-25 23:40:27','2010-02-25 23:40:27',29);
INSERT INTO `notes` VALUES (11,'У сайта должно быть короткое название. Но также должно быть объяснение того, что же это за сайт.\r\nНапример, СЛИ. Длинное - Сыктыварский лесной институт. и т.д.','2010-02-27 00:53:17','2010-02-27 00:53:17',56);
INSERT INTO `notes` VALUES (12,'Это будет скрипт, который считывает все библиотечные файлы, и выводит инфо по функциям.','2010-02-28 07:48:27','2010-02-28 07:48:27',60);
INSERT INTO `notes` VALUES (13,'Удалил весь вывод echo в ajax вообще, теперь только лог остался в файл, соотственни из js файла удалил вывод ошибки, и проблема решилась','2010-03-06 13:05:32','2010-03-06 13:05:32',59);
INSERT INTO `notes` VALUES (14,'Аккуратно, с помощью reflection API сделал специальные функции извлечения всей информации о методах и функциях. Далее сделал reference скрипт.\r\nСейчас уже наполовину доделал описания для всех методов и функций. Надеюсь, будет понятнее.','2010-03-06 22:50:28','2010-03-06 22:50:28',60);
INSERT INTO `notes` VALUES (15,'Частично порешал эту проблему, но не факт, что наверняка. Возможно, буду переходить на jquery, хотя мой скриптик мне нравится...','2010-03-07 18:00:09','2010-03-07 18:00:09',51);
INSERT INTO `notes` VALUES (16,'перенос имён таблиц - да/нет, как сильно','2010-03-09 18:21:54','2010-03-09 18:21:54',69);
INSERT INTO `notes` VALUES (17,'надо разобраться, что это за опции, что они делают, работают ли и написать комментарии.','2010-03-12 19:15:28','2010-03-12 19:15:28',80);
INSERT INTO `notes` VALUES (18,'1. Добавлен комментарий к быстрому запросу','2010-03-14 11:56:25','2010-03-14 11:56:25',104);
INSERT INTO `notes` VALUES (19,'2. Улучшем комментарий к ссылке full таблицы данных','2010-03-14 11:59:07','2010-03-14 11:59:07',104);
INSERT INTO `notes` VALUES (20,'3. Добавлен комментарий к ссылке full списка таблиц','2010-03-14 12:00:57','2010-03-14 12:00:57',104);
INSERT INTO `notes` VALUES (21,'Не знаю, как решить эту проблему. Номер тоже нельзя использовать, так как там может быть и сортировка сверху, и whete условие.','2010-03-14 12:54:51','2010-03-14 12:54:51',81);
INSERT INTO `notes` VALUES (22,'Добавил опцию debug, а также функцию _trace() в XLAjax','2010-03-15 18:42:49','2010-03-15 18:42:49',51);
INSERT INTO `notes` VALUES (23,'дизайн админки - смогу ли нарисовать сам или взять Симплу? или МОДх тоже неплохой?','2012-10-16 06:09:42','2012-10-16 06:09:42',149);
INSERT INTO `notes` VALUES (24,'из своей CMS можно сделать что-то уровня MSC (хорошее) для управления данными хотя бы (админка)','2012-10-16 06:09:56','2012-10-16 06:09:56',149);
INSERT INTO `notes` VALUES (25,'сделать свою CMS это не так плохо. Главное понять - с нуля или старая версия сгодится.\r\n  старая cms - это гиблое дело, тупая изначально штука, или же из нее можно сделать что-то стоящее, и она может быть полезна и мне и другим\r\n* обобщить опыт других CMS\r\n  SIMPLA ++ хороший каркас, интересная, красивая админка, готовые API (?) -- там фактически\r\n  просто каркас из ООП, а все остальное -- это огромный набор очень похожих скриптов.\r\n  -- Она платная, не расширяемая, не универсальная.\r\n','2012-10-16 06:10:07','2012-10-16 06:10:07',149);
INSERT INTO `notes` VALUES (26,'тест','2012-10-24 05:42:42','2012-10-24 05:42:42',143);

--
-- Структура таблицы projects
--

CREATE TABLE `projects` (
  `project_id` tinyint(3) unsigned NOT NULL auto_increment,
  `project_name` varchar(255) NOT NULL,
  `active` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`project_id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;

--
-- Дамп данных таблицы projects
--

INSERT INTO `projects` VALUES (2,'Сайты Коми',0);
INSERT INTO `projects` VALUES (1,'engine',1);
INSERT INTO `projects` VALUES (3,'Mantis',1);
INSERT INTO `projects` VALUES (6,'MysqlCenter',1);
INSERT INTO `projects` VALUES (5,'Films',0);
INSERT INTO `projects` VALUES (8,'Team',0);

--
-- Структура таблицы sdfasdf
--

CREATE TABLE `sdfasdf` (
  `sadfasdf` tinyint(4) NOT NULL auto_increment,
  `asdfsdf` varchar(255) NOT NULL,
  PRIMARY KEY  (`sadfasdf`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
--
-- Структура таблицы tasks
--

CREATE TABLE `tasks` (
  `task_id` smallint(5) unsigned NOT NULL auto_increment,
  `task_title` varchar(255) NOT NULL,
  `minutes` varchar(255) default NULL,
  `content` text,
  `project_id` tinyint(3) unsigned NOT NULL default '0',
  `created_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `updated_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `status` enum('new','resolved','closed','unknown') NOT NULL default 'new',
  `severity` enum('minor','major') NOT NULL default 'minor',
  `type` enum('enchansment','bug') NOT NULL default 'enchansment',
  PRIMARY KEY  (`task_id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;

--
-- Дамп данных таблицы tasks
--

INSERT INTO `tasks` VALUES (6,'Сделать статусы для задач',NULL,NULL,3,'2009-04-23 11:09:24','2009-04-30 15:43:32','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (7,'Просмотр задачи сделать',NULL,NULL,3,'2009-04-23 15:57:38','2009-04-24 09:08:21','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (8,'Фильтр по проекту в списке',NULL,NULL,3,'2009-04-23 16:00:04','2010-03-07 18:10:18','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (9,'Выделение статусов в списке',NULL,NULL,3,'2009-04-23 16:00:14','2009-04-24 09:08:08','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (10,'Редактирование задачи',NULL,NULL,3,'2009-04-23 16:00:52','2012-10-16 03:39:12','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (11,'После добавления переход к списку',NULL,NULL,3,'2009-04-23 16:01:16','2009-04-23 16:01:16','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (12,'Управление статусами',NULL,NULL,3,'2009-04-23 16:37:44','2009-04-30 15:57:34','closed','minor','enchansment');
INSERT INTO `tasks` VALUES (13,'Комментарии к задачам',NULL,NULL,3,'2009-04-24 09:08:50','2009-04-30 15:06:06','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (14,'Постраничный вывод задач','12',NULL,3,'2009-04-24 09:09:13','2009-04-30 15:44:14','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (15,'Селектор проектов. Проект в cookie','12',NULL,3,'2009-04-24 09:09:43','2009-04-30 15:29:39','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (16,'Историю задач сделать',NULL,NULL,3,'2009-04-24 09:12:11','2009-04-24 09:58:17','resolved','major','enchansment');
INSERT INTO `tasks` VALUES (17,'Редактирование задач',NULL,NULL,3,'2009-04-24 09:14:54','2009-04-24 10:50:32','resolved','major','enchansment');
INSERT INTO `tasks` VALUES (18,'Список конфигурации','12',NULL,3,'2009-04-27 12:22:23','2009-04-30 14:53:25','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (19,'Редактирование конфигов','6',NULL,3,'2009-04-27 12:22:40','2009-04-30 15:00:01','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (20,'Сделать автоматическое меню',NULL,NULL,3,'2009-04-27 16:36:09','2010-02-11 18:17:06','unknown','minor','enchansment');
INSERT INTO `tasks` VALUES (21,'Новые приоритеты, выделение приоритета в списке',NULL,NULL,3,'2009-04-27 16:38:00','2009-04-30 15:04:18','unknown','minor','enchansment');
INSERT INTO `tasks` VALUES (22,'Полуавтоматическое обновление дампа базы','12',NULL,3,'2009-04-27 16:55:37','2009-04-27 16:55:44','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (23,'Поместить шапку в файл top.php','12',NULL,3,'2009-04-30 14:34:06','2009-04-30 14:34:11','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (24,'Добавить колонку часы в список задач','6',NULL,3,'2009-04-30 14:53:55','2009-04-30 14:53:55','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (25,'Логин и пароль для входа в программу',NULL,NULL,3,'2009-04-30 16:00:23','2009-04-30 16:00:23','new','major','enchansment');
INSERT INTO `tasks` VALUES (26,'Расставить жанры точнее',NULL,'Например, жанр сказка только у одной сказки. У остальных семейное и прочее.\r\nНадо сделать таблицу и на ajax быстро расставлять жанры.',5,'2010-02-06 20:11:42','2010-07-04 18:12:10','closed','minor','enchansment');
INSERT INTO `tasks` VALUES (27,'Поиск по актерам, жанрам, странам и пр. должен работать тоже','30',NULL,5,'2010-02-06 20:13:37','2010-07-03 14:51:00','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (28,'Поиск по базе всегда сверху','30','справа от запросов',6,'2010-02-06 20:15:14','2010-03-07 11:27:39','resolved','major','enchansment');
INSERT INTO `tasks` VALUES (29,'Установка движка работает очень плохо','180','Именно так. Очистка базы от старых записей проходит тяжело. Если не знать, что к чему, не разобраться.\r\nПроектные/движковые файлы размещены вместе во всех папках, непонятно может быть, что как где удалять.',1,'2010-02-10 21:40:39','2010-03-07 23:15:45','resolved','major','enchansment');
INSERT INTO `tasks` VALUES (30,'Объекты/поля плохая сортировка','30','Дурацкая сортировка через левую пятку полей данных, при создании или редактировании.',1,'2010-02-10 21:43:03','2010-03-06 22:51:11','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (31,'Поля данных - не создают полей в таблице','180','А это весьма даже желательно.',1,'2010-02-10 21:43:32','2010-02-23 15:35:58','resolved','major','enchansment');
INSERT INTO `tasks` VALUES (32,'Удаление объекта неадекватное','120','При удалении объекта, не удаляется связанная информация. Например, пункт конфигурации DefaultObject остаётся, и это провоцирует проблемы. Не удаляются права доступа, связанные поля данных и прочее.',1,'2010-02-10 21:44:30','2010-03-07 18:10:54','resolved','major','enchansment');
INSERT INTO `tasks` VALUES (33,'Шрифт надо бы помельче',NULL,NULL,1,'2010-02-10 21:45:20','2010-02-21 23:06:08','unknown','minor','enchansment');
INSERT INTO `tasks` VALUES (34,'Поля данных - при изменении параметра, надо делать ALTER...',NULL,NULL,1,'2010-02-10 21:51:55','2010-02-28 07:35:42','new','minor','enchansment');
INSERT INTO `tasks` VALUES (35,'Поля данных - первую страницу, объекты сортировать по ид DESC','12',NULL,1,'2010-02-10 21:54:26','2010-02-21 23:05:21','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (36,'Объекты с парентами - проблема с parent полем',NULL,'если создаются верхние уровни, то незачем заставлять вводить parent=0, и так ясно, что он равен NULL',1,'2010-02-10 22:03:41','2010-02-21 22:56:03','unknown','minor','enchansment');
INSERT INTO `tasks` VALUES (37,'В таблице показывать - по какому полю отсортировано','12',NULL,1,'2010-02-10 22:23:19','2010-02-21 22:53:04','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (38,'Строки с кавчками сохраняет неправильно (режет после кавычек всё)','6',NULL,1,'2010-02-11 18:19:50','2010-02-21 22:36:46','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (39,'Добавить поля kp_id, cashus, rvotes?, ivotes?',NULL,NULL,5,'2010-02-23 10:26:16','2010-02-23 10:26:16','new','minor','enchansment');
INSERT INTO `tasks` VALUES (40,'Флаги государств (кинопоиск)','90',NULL,5,'2010-02-23 10:26:31','2010-07-04 18:02:49','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (41,'Ссылки на инфо актёров на кинопоиск, поле kp_id','60',NULL,5,'2010-02-23 10:26:56','2010-07-03 19:08:23','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (42,'Автоматическое добавление кадров при ffmpeg',NULL,'Также можно сделать перескан всех фильмов (диски) на кадры, чтобы добавить кадры где их нет прямо из фильма, где это возможно',5,'2010-02-23 10:28:33','2010-07-04 18:12:03','closed','minor','enchansment');
INSERT INTO `tasks` VALUES (43,'Выбор фильма - добавить вторую сортировку',NULL,NULL,5,'2010-02-23 10:30:20','2010-02-23 10:30:20','new','minor','enchansment');
INSERT INTO `tasks` VALUES (44,'Создавать /content/ файлы без вложения их в подпапки','6',NULL,1,'2010-02-23 15:55:36','2010-02-25 23:39:52','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (45,'\"По таблице\" - не показывать таблицы, уже имеющие объекты',NULL,'В принципе не так важно',1,'2010-02-23 17:49:17','2010-02-27 08:15:21','closed','minor','enchansment');
INSERT INTO `tasks` VALUES (46,'Справочник - сделать удобнее заполнение','12','сейчас при введении значений справочника, надо выбирать из списка справочник, и плюс надо писать индекс-значение. \r\nЭто всё надо ставить автоматически',1,'2010-02-23 21:15:36','2010-02-24 18:49:01','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (47,'Хорошо бы сделать связи','60','Связи - это такая вещь, которая очень полезна при удалении связанных значений, либо при изменении ключевых полей, на которых ссылаются другие строки.\r\nСейчас таких изменений я старюсь либо избегать, либо вручную вношу правки.\r\nбыло бы здорово сделать, я уже начал в своё время...',1,'2010-02-24 19:27:06','2010-03-07 17:58:49','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (48,'Добавить статистику просмотров',NULL,'сколько % фильмов не смотрел, сколько частично и пр.',5,'2010-02-24 19:33:24','2010-02-24 19:33:36','new','minor','enchansment');
INSERT INTO `tasks` VALUES (49,'Проблема изменения названий полей таблиц БД и связь со скриптами',NULL,'Например, метод register класса File\r\nТам указаны поля БД. И при изменении этих полей в БД, эта функция работать не будет.',1,'2010-02-25 23:12:37','2010-02-25 23:12:37','new','minor','enchansment');
INSERT INTO `tasks` VALUES (50,'Как найти миме-тип файла автоматически?',NULL,NULL,1,'2010-02-25 23:31:25','2010-02-25 23:31:25','new','minor','enchansment');
INSERT INTO `tasks` VALUES (51,'Отладка моего ajax работает очень плохо','180',NULL,1,'2010-02-25 23:39:27','2010-03-15 18:42:49','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (52,'При резольве надо давать возможность прописать hours хотя бы',NULL,NULL,3,'2010-02-25 23:41:10','2010-03-07 11:27:23','closed','minor','enchansment');
INSERT INTO `tasks` VALUES (53,'Анализ полноты инфо по сайтам',NULL,'Неполная инфо о сайтах, нужно сделать систему аналогичную на films',2,'2010-02-26 17:42:46','2010-02-26 17:42:46','new','minor','enchansment');
INSERT INTO `tasks` VALUES (54,'Веб студии анализ, нужно ли делать рейтинги, таблицу',NULL,NULL,2,'2010-02-26 17:44:08','2010-03-06 13:06:54','unknown','minor','enchansment');
INSERT INTO `tasks` VALUES (55,'Если веб-студии нет, то надо это указывать',NULL,'Чтобы отличать сайт, где веб-студия еще не искалась.',2,'2010-02-26 22:32:33','2010-03-06 13:06:30','closed','minor','enchansment');
INSERT INTO `tasks` VALUES (56,'Добавить короткое имя сайта, отдельно полное наименование',NULL,NULL,2,'2010-02-27 00:51:29','2010-03-05 15:34:09','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (57,'В админке нет выбора категорий!','60',NULL,2,'2010-02-27 00:55:52','2010-03-06 13:06:42','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (58,'Должна быть одна нормальная функция для обработки ошибок','120','А то одна функция делает одно, другая другое, в итоге в ajax вылазяет сообщения об ошибках прямо через echo',1,'2010-02-27 08:13:27','2010-03-06 13:05:57','resolved','major','enchansment');
INSERT INTO `tasks` VALUES (59,'xla.http.readyState == 3 - проблема','6',', как отловить критические ошибки без alert для всех',2,'2010-02-27 20:06:42','2010-03-06 13:05:32','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (60,'Сделать справочник функций автоматический','300','Это будет скрипт, который считывает все библиотечные файлы, и выводит инфо по функциям.',1,'2010-02-28 07:47:57','2010-03-07 11:28:03','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (61,'Проблема DIR_ENGINE в ajax по всех путях',NULL,NULL,1,'2010-02-28 09:20:56','2010-02-28 09:20:56','new','minor','enchansment');
INSERT INTO `tasks` VALUES (62,'Проверять повторно сайты со статусом не 200',NULL,NULL,2,'2010-03-02 23:54:41','2010-07-10 11:16:36','closed','minor','enchansment');
INSERT INTO `tasks` VALUES (63,'Постраничный вывод сайтов','30',NULL,2,'2010-03-05 15:34:46','2010-03-05 16:37:56','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (64,'инфо header пересобирать и сохранить',NULL,NULL,2,'2010-03-05 15:37:40','2010-07-10 11:16:25','closed','minor','enchansment');
INSERT INTO `tasks` VALUES (65,'добавить функцию выборки сайтов',NULL,'общую, где прописать единый where.',2,'2010-03-05 15:38:17','2010-03-05 15:38:17','new','minor','enchansment');
INSERT INTO `tasks` VALUES (66,'добавить поле \"Значимость сайта\" числовое','30','по умолчанию 0 - обычный. Например, для сайта главы, сайтов районов, Комионлайн, БНКоми.',2,'2010-03-05 15:38:31','2010-03-06 13:04:28','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (67,'php.net сканирование php мануала - много нового!!!',NULL,NULL,1,'2010-03-05 19:51:48','2010-03-09 17:57:38','closed','minor','enchansment');
INSERT INTO `tasks` VALUES (68,'Фильтр (поиск) по таблице в админке сделать','60',NULL,1,'2010-03-07 18:01:12','2010-03-09 17:57:20','resolved','major','enchansment');
INSERT INTO `tasks` VALUES (69,'Настройки','120',NULL,6,'2010-03-09 18:19:28','2010-03-10 22:36:56','resolved','major','enchansment');
INSERT INTO `tasks` VALUES (70,'Поискс снизу убрать','6',NULL,6,'2010-03-09 18:19:56','2010-03-10 13:48:19','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (71,'Уменьшить поле SQL запрос в БД','6',NULL,6,'2010-03-09 18:21:18','2010-03-10 13:47:36','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (72,'Ключи','600',NULL,6,'2010-03-09 18:22:20','2010-03-11 18:40:27','resolved','major','enchansment');
INSERT INTO `tasks` VALUES (73,'Поле enum, при добавлении ряда - селектор','12',NULL,6,'2010-03-09 18:22:59','2010-03-10 13:57:46','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (74,'Поле tinyint(1), при добавлении, - чекбокс',NULL,NULL,6,'2010-03-09 18:23:38','2010-03-10 22:37:21','unknown','minor','enchansment');
INSERT INTO `tasks` VALUES (75,'Действия - таблица ... ставить имя таблицы по умолчанию','6',NULL,6,'2010-03-09 18:28:57','2010-03-10 13:46:49','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (76,'Fix Select-выборку, если не указана таблица','18','Если отсюда http://msc/?s=db_list или из списка таблиц сделать запрос на SELECT, то выведется ошибка, хотя запрос вполне можно осуществить (если он не разделен ;)',6,'2010-03-09 18:30:58','2010-03-10 13:45:18','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (77,'Reference, комментирование классов, функций','300',NULL,6,'2010-03-10 00:26:56','2010-03-14 21:41:05','new','major','enchansment');
INSERT INTO `tasks` VALUES (78,'Разбор веб-тестов полный','30','Нужно понять, написать структуру тестов - что не хватает, что уже есть, что надо сделать. Нужно выяснить охват.',6,'2010-03-11 18:42:26','2010-03-12 20:38:33','new','major','enchansment');
INSERT INTO `tasks` VALUES (79,'Ajax разобраться - либо свой, либо этот почистить','60',NULL,6,'2010-03-11 23:11:00','2010-03-13 15:12:09','resolved','major','enchansment');
INSERT INTO `tasks` VALUES (80,'Экспорт - добавить комментарии','90','а то непонятно, что такое \"полная вставка\" и другое',6,'2010-03-12 19:02:31','2010-03-14 14:21:30','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (81,'Редактирование таблиц без ключей','6','Баг - если нет разных полей, то невозможно отредактировать одно поле (нужно редактировать по номеру в выходе)\r\nНапример, таблица без ключей, где все строки и все поля содержат одинаковые значения...',6,'2010-03-12 19:03:46','2010-03-14 12:54:51','new','minor','bug');
INSERT INTO `tasks` VALUES (82,'WHERE поиск добавить селекторы-помощники','18','поиск.. добавить к условию WHERE добавить селекторы выбора полей, типов сравнения (= != LIKE \'%%\'), чтобы при выборе они копировались в поле',6,'2010-03-12 19:11:28','2010-03-13 16:49:52','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (83,'Кодировка полей не указана и не изменяемаая',NULL,NULL,6,'2010-03-12 19:12:02','2010-03-12 19:12:02','new','major','enchansment');
INSERT INTO `tasks` VALUES (84,'Fulltext сделать',NULL,'Fulltext - разобраться, что это такое, как это правильно использовать и внедрить. Не реализовано вообще видимо.',6,'2010-03-12 19:12:35','2010-03-12 19:12:35','new','major','enchansment');
INSERT INTO `tasks` VALUES (85,'Веб-тесты сделать проверку ключей',NULL,NULL,6,'2010-03-12 19:12:52','2010-03-12 19:12:52','new','major','enchansment');
INSERT INTO `tasks` VALUES (86,'Веб тесты допзадачи','60','Веб-тесты. Для тестов нужна большая таблица, со всеми типами данных и с количеством строк несколько тысяч.\r\nВеб-тесты доп проверка: tbl_data переход на страницу, сортировка, удаление ряда(рядов), если нет. Также новые поля поиска надо проверить.\r\nТакже проверка \"добавить к условию where\" и замена.\r\nВеб-тесты - проверка сохранения настроек (каждую настройку проверять не надо).',6,'2010-03-12 19:15:03','2010-03-13 23:47:02','new','minor','enchansment');
INSERT INTO `tasks` VALUES (87,'Полностью перебрать стили',NULL,'Полностью перебрать стили, убрать лишние, их не должно быть много, либо они должны быть структурированы. Далее буду доделывать стили.',6,'2010-03-12 19:16:57','2010-03-12 19:16:57','new','minor','enchansment');
INSERT INTO `tasks` VALUES (88,'Совместимость с php4','30','Желательно сделать совместимым с php4, то есть без статик/паблик. Приват обозначить через _. Статик.. никак.',6,'2010-03-12 19:17:21','2010-03-13 15:12:35','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (89,'Дата в списке таблиц улучшение','30','В списке таблиц, если обновление таблицы было в течение недели, отображать \"День недели, время\", как в Firefox.',6,'2010-03-12 19:17:48','2010-03-14 14:53:33','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (90,'Комментарии к типам данных',NULL,'Клик по типу поля в структуре - загрузка всплывающей подсказки об этом типе на основе reference',6,'2010-03-12 19:18:04','2010-03-12 19:18:04','new','minor','enchansment');
INSERT INTO `tasks` VALUES (91,'Селектор для enum при создании поля',NULL,'При редактировании, создании полей, если выбирается тип поля enum, то вместо длины надо бы показывать селектор с автосозданием/редактированием существующего набора.',6,'2010-03-12 19:18:26','2010-03-12 19:18:26','new','minor','enchansment');
INSERT INTO `tasks` VALUES (92,'Advanced sql запрос',NULL,'вообще было бы интересно сделать',6,'2010-03-12 19:18:41','2010-03-12 19:18:41','new','minor','enchansment');
INSERT INTO `tasks` VALUES (93,'Скрывание/показ списка таблиц слева',NULL,'Возможность спрятать список таблиц быстро, и быстро вернуть, либо посмотреть \"на лету\"',6,'2010-03-12 19:19:03','2010-03-12 19:19:03','new','minor','enchansment');
INSERT INTO `tasks` VALUES (94,'Механизм уникализации полей',NULL,'Механизм \"сделать поле уникальным\", т.е. найти повторяющиеся строки и удалить, либо переименовать.',6,'2010-03-12 19:20:13','2010-03-12 19:20:13','new','minor','enchansment');
INSERT INTO `tasks` VALUES (95,'Комментарии к ключам','30','Там где инфо о ключах нужно разъяснить, что такое Cardinality Sub_part Packed Null Тип индекса',6,'2010-03-12 19:20:34','2010-03-14 10:43:05','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (96,'Работа с пользователями',NULL,'Работа с пользователями. 1. Просмотр списка, прав. 2. Добавить нового пользователя, с такими-то правами. Удалить. Какой пользователь текущий. Права.',6,'2010-03-12 19:20:48','2010-03-12 19:20:48','new','major','enchansment');
INSERT INTO `tasks` VALUES (97,'Все js почистить, отказ от xajax','60','Полностью отказаться от xajax. Все js почистить, убрать лишние, библиотеки объединить.',6,'2010-03-12 19:23:07','2010-03-13 15:12:21','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (98,'js добавление нового ряда для вставки','60',NULL,6,'2010-03-12 19:44:45','2010-03-13 16:32:52','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (99,'структура: возможность двигать поля вверх вниз.','60',NULL,6,'2010-03-12 19:45:13','2010-03-13 18:17:02','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (100,'Комментарии к переменным сервера',NULL,NULL,6,'2010-03-12 19:50:33','2010-03-12 19:50:33','new','minor','enchansment');
INSERT INTO `tasks` VALUES (101,'Поиск по базе.. выбор типа поиска',NULL,'Искать по всем полям... добавить селектор тип поиска LIKE \'%%\' LIKE \'\' =',6,'2010-03-12 20:01:00','2010-03-12 20:01:00','new','minor','enchansment');
INSERT INTO `tasks` VALUES (102,'Отзыв о программе',NULL,'Снизу также надо добавить блок Сообщить об ошибке, пожелание, предложение... по емейл(?) сервер рано или поздно закончится....',6,'2010-03-12 20:04:57','2010-03-12 20:04:57','new','minor','enchansment');
INSERT INTO `tasks` VALUES (103,'Настройки - востановить умолчания опция',NULL,NULL,6,'2010-03-12 20:05:11','2010-03-12 20:05:11','new','minor','enchansment');
INSERT INTO `tasks` VALUES (104,'Разные пояснения в коде','12','Опять же... никто не будет знать, что при клике на время выводится sql запрос. Что в таблице данных есть кнопка full Двойной клик на таблице - rename',6,'2010-03-12 20:05:26','2010-03-14 12:02:42','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (105,'Создание полей set/timestamp улучшить',NULL,'set должен быть множественный селектор, timestamp вывод даты + галочка \"CURRENT_TIMESTAMP\"',6,'2010-03-12 20:06:23','2010-03-12 20:06:23','new','minor','enchansment');
INSERT INTO `tasks` VALUES (106,'Статистика, хотя бы простая','60',NULL,1,'2010-03-12 23:16:12','2010-06-29 17:48:25','new','major','enchansment');
INSERT INTO `tasks` VALUES (107,'Таблица config отделить локал от систем!','60',NULL,1,'2010-03-12 23:49:53','2010-03-13 08:47:38','resolved','major','bug');
INSERT INTO `tasks` VALUES (108,'Удаляет MUL/UNI ключи при редактировании полей','6',NULL,6,'2010-03-13 19:46:18','2010-03-13 20:58:45','resolved','major','bug');
INSERT INTO `tasks` VALUES (109,'Не выводятся поля при ошибке создания таблицы','12','Если полей больше 10 .....',6,'2010-03-13 22:39:34','2010-03-14 12:50:30','resolved','major','bug');
INSERT INTO `tasks` VALUES (110,'Поле типа FLOAT ошибка если () пусто','12',NULL,6,'2010-03-13 22:40:14','2010-03-14 12:20:29','resolved','major','bug');
INSERT INTO `tasks` VALUES (111,'serial auto_increment по умолчанию ? ошибка бывает',NULL,NULL,6,'2010-03-13 22:42:26','2010-03-13 22:42:26','new','minor','bug');
INSERT INTO `tasks` VALUES (112,'Комментарии к Подробности таблицы','60',NULL,6,'2010-03-14 11:54:52','2010-03-14 11:55:00','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (113,'Создание полей нет проверок',NULL,'Например, text поля не могут быть unsigned, авто-инкремент. Параметры подставляются под все поля без разбора, при том, что многие из них очень разные. Хотя это не обязательно проверять, но в принципе это было бы неплохо.',6,'2010-03-14 12:25:22','2010-03-14 12:25:22','new','minor','bug');
INSERT INTO `tasks` VALUES (114,'Копирование рядов при создании таблицы то же самое','12',NULL,6,'2010-03-14 12:49:36','2010-03-14 19:58:09','resolved','major','bug');
INSERT INTO `tasks` VALUES (115,'get_databases_full перебрать, уменьшить',NULL,'get_databases_full это функция PMA. Надо сделать так, чтобы это была собственная функция MSC, то есть сделать по-своему, оставив лучшее.',6,'2010-03-14 20:26:28','2010-03-14 20:26:28','new','minor','bug');
INSERT INTO `tasks` VALUES (116,'\"Добавить описание\" - возможность для пользователя','120',NULL,2,'2010-03-15 20:15:52','2010-03-15 23:32:43','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (117,'\"Добавить сайт\" - url строка сверху','30',NULL,2,'2010-03-15 20:20:09','2010-07-10 11:16:15','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (118,'Свои новости на Главной','90','Есть идея, чтобы сайт был чуть интереснее, сделать каталог не главной чуть меньше и справа разместить колонку своих новостей, о городе, о своём районе',2,'2010-03-15 22:05:41','2010-03-16 20:20:13','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (119,'Хорошо бы довести количество сайтов до 1000','120',NULL,2,'2010-03-15 22:09:36','2010-07-12 18:21:34','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (120,'Список турниров, выбор турнира. Турнир по умолч.',NULL,NULL,8,'2010-04-24 18:18:58','2010-04-24 18:18:58','new','minor','enchansment');
INSERT INTO `tasks` VALUES (121,'Даты рождения актеров, рост, годы жизни и др. инфо','90',NULL,5,'2010-06-29 17:49:09','2010-07-04 18:53:09','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (122,'Настройки','12','Например, генерировать ли кадры ffmpeg, путь к msc',5,'2010-07-04 05:44:58','2010-07-04 18:11:53','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (123,'Есть актеры с инфо, но без фоток','30',NULL,5,'2010-07-04 18:18:56','2010-07-05 19:46:28','resolved','major','bug');
INSERT INTO `tasks` VALUES (124,'Фильмография полностью реализовать','90',NULL,5,'2010-07-04 18:53:24','2010-07-04 20:43:34','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (125,'Режиссеры - нет фоток, информации',NULL,NULL,5,'2010-07-06 16:07:35','2010-07-06 16:07:35','new','minor','enchansment');
INSERT INTO `tasks` VALUES (126,'Добавление описаний сразу в базу, с уведомлением меня','30',NULL,2,'2010-07-09 21:37:07','2010-07-09 22:24:13','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (127,'Самые популярные сайты','30',NULL,2,'2010-07-09 22:24:39','2010-07-09 22:48:59','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (128,'В шапке количество сайтов и поиск слева крупнее','60',NULL,2,'2010-07-09 22:48:50','2010-07-10 12:48:08','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (129,'Новые сайты','6',NULL,2,'2010-07-10 00:31:44','2010-07-10 02:03:57','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (130,'Информация по регионам',NULL,NULL,2,'2010-07-10 02:03:40','2010-07-10 02:03:40','new','minor','enchansment');
INSERT INTO `tasks` VALUES (131,'Форма отзыва о сайте, совета, предложения, везде, небольшую','30',NULL,2,'2010-07-10 04:08:18','2010-07-10 11:15:59','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (132,'Комментарии к сайтам',NULL,NULL,2,'2010-07-10 04:08:54','2010-07-10 04:08:54','new','minor','enchansment');
INSERT INTO `tasks` VALUES (133,'Стили сайта (цвет)','60',NULL,2,'2010-07-10 04:09:32','2010-07-10 23:07:28','new','minor','enchansment');
INSERT INTO `tasks` VALUES (134,'Проверка сайтов - таблица с полной статистикой проверок',NULL,NULL,2,'2010-07-10 11:48:43','2010-07-10 11:48:43','new','minor','enchansment');
INSERT INTO `tasks` VALUES (135,'fdsg',NULL,NULL,2,'2012-10-15 03:59:20','2012-10-15 08:35:32','new','minor','enchansment');
INSERT INTO `tasks` VALUES (136,'Делать проекты неактивными и вообще не показывать задачи по ним','30',NULL,3,'2012-10-16 03:42:31','2012-10-16 04:20:08','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (137,'Быстрое добавление задачи на ajax',NULL,NULL,3,'2012-10-16 03:43:14','2012-10-16 03:43:14','new','minor','enchansment');
INSERT INTO `tasks` VALUES (138,'Удаление задач',NULL,NULL,3,'2012-10-16 03:44:02','2012-10-16 03:44:02','new','minor','enchansment');
INSERT INTO `tasks` VALUES (139,'Запоминать последнюю выбранную задачу и сразу проставлять при добавлении',NULL,NULL,3,'2012-10-16 03:44:23','2012-10-16 03:44:23','new','minor','enchansment');
INSERT INTO `tasks` VALUES (140,'Поле Hours сделать Minutes',NULL,NULL,3,'2012-10-16 03:46:03','2012-10-16 10:23:23','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (141,'возможность быстрого редактирования полей прямо из таблицы просмотра, через ajax и формы',NULL,NULL,6,'2012-10-16 06:00:38','2012-10-16 06:00:38','new','minor','enchansment');
INSERT INTO `tasks` VALUES (142,'если при создании таблицы возникла ошибка, снова переходить к полям редактирования',NULL,NULL,6,'2012-10-16 06:01:04','2012-10-24 06:19:09','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (143,'при добавлении, если поле DATETIME указывать текущее время',NULL,NULL,6,'2012-10-16 06:01:13','2012-10-24 05:47:18','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (144,'при создании таблицы, кнопки для добавления наиболее часто используемых полей (id title content email id_some и т.д.)',NULL,NULL,6,'2012-10-16 06:01:23','2012-10-24 07:23:19','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (145,'чтобы можно было при редактировании значений применять функции (md5 хотя бы)',NULL,NULL,6,'2012-10-16 06:01:31','2012-10-24 07:23:43','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (146,'поиск поля в БД нужно сделать и при переходе из таблицы',NULL,NULL,6,'2012-10-16 06:01:40','2012-10-24 07:26:32','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (147,'если несколько запросов, и внутри ошибки - выполнятся все или остановится? Надо все!',NULL,NULL,6,'2012-10-16 06:01:48','2012-10-24 07:30:33','resolved','minor','enchansment');
INSERT INTO `tasks` VALUES (148,'Поставить на большую базу как админку',NULL,'Поставить на какую-нибудь большую базу, в качестве оболочки управления,\r\n     далее создать автоматически пару объектов по таблицам. Удобно ли.',1,'2012-10-16 06:09:20','2012-10-16 06:09:20','new','minor','enchansment');
INSERT INTO `tasks` VALUES (149,'Рисунки CMS',NULL,'нарисовать, как должна выглядеть своя КМС',1,'2012-10-16 06:09:34','2012-10-16 06:10:07','new','minor','enchansment');

