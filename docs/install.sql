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

ALTER TABLE `db_info`
    ADD COLUMN `views` INT NOT NULL,
 ADD COLUMN `last_view` DATETIME NOT NULL;

ALTER TABLE `table_info`
    ADD COLUMN `views` INT NOT NULL,
 ADD COLUMN `last_view` DATETIME NOT NULL;