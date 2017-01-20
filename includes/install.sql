-- MySQL Center SQL Экспорт
-- версия  1.28
-- http://mysqlcenter.com/
--
-- Хост: localhost
-- Время создания: 1.01.2009, 00-29
-- Версия сервера: 4.1.8-max
-- Версия PHP: 5.2.4
--
-- БД: `mysqlcenter`
--

-- --------------------------------------------------------

CREATE DATABASE `mysqlcenter` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `mysqlcenter`;

--
-- Структура таблицы column_info
--

CREATE TABLE `column_info` (
  `id` int(5) unsigned NOT NULL auto_increment,
  `db_name` varchar(64) NOT NULL,
  `table_name` varchar(64) NOT NULL,
  `column_name` varchar(64) NOT NULL,
  `comment` varchar(255) NOT NULL,
  `mimetype` varchar(255) NOT NULL,
  `transformation` varchar(255) NOT NULL,
  `transformation_options` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `db_name` (`column_name`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251 AUTO_INCREMENT=1  COMMENT="Column information for phpMyAdmin";
--
-- Структура таблицы db_info
--

CREATE TABLE `db_info` (
  `db_name` varchar(255) NOT NULL,
  `visible` int(1) NOT NULL default '1',
  PRIMARY KEY  (`db_name`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;
--
-- Структура таблицы export_set
--

CREATE TABLE `export_set` (
  `id` int(8) NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  `db_set` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251 AUTO_INCREMENT=12 ;

--
-- Дамп данных таблицы export_set
--

INSERT INTO `export_set` VALUES (2,'Форум',NULL);
INSERT INTO `export_set` VALUES (11,'Движок',NULL);

--
-- Структура таблицы export_table
--

CREATE TABLE `export_table` (
  `id` int(8) NOT NULL auto_increment,
  `id_set` int(8) NOT NULL default '1',
  `table_name` varchar(255) NOT NULL,
  `struct` int(1) NOT NULL default '0',
  `data` int(1) NOT NULL default '1',
  `where_sql` varchar(255) default NULL,
  `pk_top` int(8) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251 AUTO_INCREMENT=151 ;

--
-- Дамп данных таблицы export_table
--

INSERT INTO `export_table` VALUES (1,2,'da_forum_category',1,0,NULL,NULL);
INSERT INTO `export_table` VALUES (2,2,'da_forum_censoring',1,0,NULL,NULL);
INSERT INTO `export_table` VALUES (3,2,'da_forum_message',1,0,NULL,NULL);
INSERT INTO `export_table` VALUES (4,2,'da_forum_post',1,0,NULL,NULL);
INSERT INTO `export_table` VALUES (5,2,'da_forum_rank',1,0,NULL,NULL);
INSERT INTO `export_table` VALUES (6,2,'da_forum_subscribe',1,0,NULL,NULL);
INSERT INTO `export_table` VALUES (7,2,'da_forum_topic',1,0,NULL,NULL);
INSERT INTO `export_table` VALUES (8,2,'da_forum_user_online',1,0,NULL,NULL);
INSERT INTO `export_table` VALUES (9,2,'da_forum_user_visit',1,0,NULL,NULL);
INSERT INTO `export_table` VALUES (10,2,'da_ajax',0,1,'id_ajax BETWEEN 21 AND 30',NULL);
INSERT INTO `export_table` VALUES (11,2,'da_event_subscriber',0,1,'id_event_type BETWEEN 1 AND 6',NULL);
INSERT INTO `export_table` VALUES (12,2,'da_event_type',0,1,'id_event_type BETWEEN 1 AND 6',NULL);
INSERT INTO `export_table` VALUES (13,2,'da_files',0,1,'id_file = 63 OR id_file = 2',NULL);
INSERT INTO `export_table` VALUES (14,2,'da_group_system_parameter',0,1,'id_group_system_parameter = 11',NULL);
INSERT INTO `export_table` VALUES (15,2,'da_groups',0,1,'id_group = 5',NULL);
INSERT INTO `export_table` VALUES (16,2,'da_job',0,1,'id_job = 2',NULL);
INSERT INTO `export_table` VALUES (17,2,'da_object_parameters',0,1,'id_object BETWEEN 130 AND 139',NULL);
INSERT INTO `export_table` VALUES (18,2,'da_menu',0,1,'id BETWEEN 45 AND 60',NULL);
INSERT INTO `export_table` VALUES (19,2,'da_object',0,1,'id_object BETWEEN 130 AND 139',NULL);
INSERT INTO `export_table` VALUES (20,2,'da_object_property',0,1,'ID_OBJECT = 24',NULL);
INSERT INTO `export_table` VALUES (21,2,'da_permissions',0,1,'id_object BETWEEN 130 AND 139',NULL);
INSERT INTO `export_table` VALUES (22,2,'da_system_parameter',0,1,'id_group_system_parameter = 11',NULL);
INSERT INTO `export_table` VALUES (150,11,'da_users',0,1,'',0);
INSERT INTO `export_table` VALUES (149,11,'da_system_parameter',0,1,'id_system_parameter <= 199',199);
INSERT INTO `export_table` VALUES (144,11,'da_rules_process_text',0,1,'',0);
INSERT INTO `export_table` VALUES (143,11,'da_references',0,1,'id_reference <= 100',100);
INSERT INTO `export_table` VALUES (141,11,'da_permissions',0,1,'',0);
INSERT INTO `export_table` VALUES (142,11,'da_reference_element',0,1,'id_instance <= 200',200);
INSERT INTO `export_table` VALUES (140,11,'da_permission_type',0,1,'',0);
INSERT INTO `export_table` VALUES (139,11,'da_object_parameters',0,1,'id_parameter <= 1000',1000);
INSERT INTO `export_table` VALUES (138,11,'da_object_parameter_type',0,1,'',0);
INSERT INTO `export_table` VALUES (137,11,'da_object',0,1,'id_object <= 99',99);
INSERT INTO `export_table` VALUES (136,11,'da_mail_account',0,1,'',0);
INSERT INTO `export_table` VALUES (135,11,'da_job_parameter_value',0,1,'',0);
INSERT INTO `export_table` VALUES (134,11,'da_job',0,1,'',0);
INSERT INTO `export_table` VALUES (133,11,'da_groups',0,1,'',0);
INSERT INTO `export_table` VALUES (132,11,'da_group_system_parameter',0,1,'',0);
INSERT INTO `export_table` VALUES (131,11,'da_files',0,1,'id_file <= 499',499);
INSERT INTO `export_table` VALUES (130,11,'da_file_type',0,1,'',0);
INSERT INTO `export_table` VALUES (129,11,'da_file_extension',0,1,'',0);
INSERT INTO `export_table` VALUES (128,11,'da_event_type',0,1,'',0);
INSERT INTO `export_table` VALUES (127,11,'da_event_subscriber',0,1,'',0);
INSERT INTO `export_table` VALUES (126,11,'da_event_format',0,1,'',0);
INSERT INTO `export_table` VALUES (125,11,'da_ajax',0,1,'',0);

--
-- Структура таблицы relation
--

CREATE TABLE `relation` (
  `master_db` varchar(64) NOT NULL,
  `master_table` varchar(64) NOT NULL,
  `master_field` varchar(64) NOT NULL,
  `foreign_db` varchar(64) NOT NULL,
  `foreign_table` varchar(64) NOT NULL,
  `foreign_field` varchar(64) NOT NULL,
  PRIMARY KEY  (`master_db`,`master_table`,`master_field`),
  KEY `foreign_field` (`foreign_table`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251 COMMENT="Relation table";
--
-- Структура таблицы table_info
--

CREATE TABLE `table_info` (
  `db_name` varchar(64) NOT NULL,
  `table_name` varchar(64) NOT NULL,
  `display_field` varchar(64) NOT NULL,
  PRIMARY KEY  (`db_name`,`table_name`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251 COMMENT="Table information for phpMyAdmin";
