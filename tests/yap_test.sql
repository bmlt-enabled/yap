-- MySQL dump 10.13  Distrib 8.0.34, for macos13 (arm64)
--
-- Host: 127.0.0.1    Database: yap_test
-- ------------------------------------------------------
-- Server version	5.7.40

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `alerts`
--

DROP TABLE IF EXISTS `alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` timestamp NULL DEFAULT NULL,
  `alert_id` int(11) NOT NULL,
  `payload` longtext,
  `status` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `alerts`
--

LOCK TABLES `alerts` WRITE;
/*!40000 ALTER TABLE `alerts` DISABLE KEYS */;
/*!40000 ALTER TABLE `alerts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `id` bigint(11) NOT NULL AUTO_INCREMENT,
  `key` longtext,
  `value` longtext,
  `expiry` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_records_conference_participants`
--

DROP TABLE IF EXISTS `cache_records_conference_participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_records_conference_participants` (
  `parent_callsid` varchar(100) DEFAULT NULL,
  `callsid` varchar(100) DEFAULT NULL,
  `guid` varchar(36) DEFAULT NULL,
  `service_body_id` int(11) DEFAULT NULL,
  KEY `idx_rcp_parent_callsid` (`callsid`),
  KEY `idx_rcp_parent_parent_callsid` (`parent_callsid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_records_conference_participants`
--

LOCK TABLES `cache_records_conference_participants` WRITE;
/*!40000 ALTER TABLE `cache_records_conference_participants` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_records_conference_participants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `conference_participants`
--

DROP TABLE IF EXISTS `conference_participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `conference_participants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `conferencesid` varchar(100) NOT NULL,
  `callsid` varchar(100) NOT NULL,
  `friendlyname` varchar(100) NOT NULL,
  `role` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`),
  KEY `idx_conference_participants_callsid` (`callsid`),
  KEY `idx_conference_participants_conferencesid` (`conferencesid`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `conference_participants`
--

LOCK TABLES `conference_participants` WRITE;
/*!40000 ALTER TABLE `conference_participants` DISABLE KEYS */;
INSERT INTO `conference_participants` VALUES (1,'2024-03-15 23:04:18','CF9a278c2cef72c7ca16eccf0a03972958','CA460d1728a3e07606f36aaa8879a7fbd3','1060_5246011_1710543855',1),(2,'2024-03-16 00:57:07','abc','abc','abc',1),(3,'2024-03-16 00:57:22','abc','abc','abc',1),(4,'2024-03-16 00:57:42','abc','abc','abc',1),(5,'2024-03-16 00:57:42','abc','abc','abc',1),(6,'2024-03-16 00:57:55','abc','abc','abc',1),(7,'2024-03-16 00:57:55','abc','abc','abc',1),(8,'2024-03-16 00:58:01','abc','abc','abc',1),(9,'2024-03-16 00:58:01','abc','abc','abc',1),(10,'2024-03-16 00:58:44','abc','abc','abc',1),(11,'2024-03-16 00:58:44','abc','abc','abc',1),(12,'2024-03-16 03:16:05','CF9a278c2cef72c7ca16eccf0a03972958','CA460d1728a3e07606f36aaa8879a7fbd2','1060_5246011_1710543855',2),(13,'2024-03-16 03:18:23','abc','abc','abc',1),(14,'2024-03-16 03:18:23','abc','abc','abc',1);
/*!40000 ALTER TABLE `conference_participants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `config`
--

DROP TABLE IF EXISTS `config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_body_id` int(10) unsigned NOT NULL,
  `data` mediumtext NOT NULL,
  `data_type` varchar(45) NOT NULL,
  `parent_id` int(10) unsigned DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `service_body_id_data_type_parent_id_UNIQUE` (`service_body_id`,`data_type`,`parent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `config`
--

LOCK TABLES `config` WRITE;
/*!40000 ALTER TABLE `config` DISABLE KEYS */;
INSERT INTO `config` VALUES (1,1060,'[{\"volunteer_routing\":\"volunteers\",\"volunteers_redirect_id\":\"\",\"forced_caller_id\":\"\",\"call_timeout\":\"\",\"gender_routing\":\"0\",\"call_strategy\":\"1\",\"volunteer_sms_notification\":\"no_sms\",\"sms_strategy\":\"2\",\"primary_contact\":\"\",\"primary_contact_email\":\"\",\"moh\":\"\",\"override_en_US_greeting\":\"\",\"override_en_US_voicemail_greeting\":\"\"}]','_YAP_CALL_HANDLING_V2_',NULL,NULL),(2,1060,'[{\"volunteer_name\":\"Danny G\",\"volunteer_phone_number\":\"12125551212\",\"volunteer_gender\":\"0\",\"volunteer_responder\":\"0\",\"volunteer_shift_schedule\":\"W3siZGF5IjoxLCJ0eiI6IkFtZXJpY2EvTmV3X1lvcmsiLCJzdGFydF90aW1lIjoiMTI6MDAgQU0iLCJlbmRfdGltZSI6IjExOjU5IFBNIiwidHlwZSI6IlBIT05FIn0seyJkYXkiOjIsInR6IjoiQW1lcmljYS9OZXdfWW9yayIsInN0YXJ0X3RpbWUiOiIxMjowMCBBTSIsImVuZF90aW1lIjoiMTE6NTkgUE0iLCJ0eXBlIjoiUEhPTkUifSx7ImRheSI6MywidHoiOiJBbWVyaWNhL05ld19Zb3JrIiwic3RhcnRfdGltZSI6IjEyOjAwIEFNIiwiZW5kX3RpbWUiOiIxMTo1OSBQTSIsInR5cGUiOiJQSE9ORSJ9LHsiZGF5Ijo0LCJ0eiI6IkFtZXJpY2EvTmV3X1lvcmsiLCJzdGFydF90aW1lIjoiMTI6MDAgQU0iLCJlbmRfdGltZSI6IjExOjU5IFBNIiwidHlwZSI6IlBIT05FIn0seyJkYXkiOjUsInR6IjoiQW1lcmljYS9OZXdfWW9yayIsInN0YXJ0X3RpbWUiOiIxMjowMCBBTSIsImVuZF90aW1lIjoiMTE6NTkgUE0iLCJ0eXBlIjoiUEhPTkUifSx7ImRheSI6NiwidHoiOiJBbWVyaWNhL05ld19Zb3JrIiwic3RhcnRfdGltZSI6IjEyOjAwIEFNIiwiZW5kX3RpbWUiOiIxMTo1OSBQTSIsInR5cGUiOiJQSE9ORSJ9LHsiZGF5Ijo3LCJ0eiI6IkFtZXJpY2EvTmV3X1lvcmsiLCJzdGFydF90aW1lIjoiMTI6MDAgQU0iLCJlbmRfdGltZSI6IjExOjU5IFBNIiwidHlwZSI6IlBIT05FIn1d\",\"volunteer_notes\":\"\",\"volunteer_enabled\":\"true\"},{\"volunteer_name\":\"Bro Bro\",\"volunteer_phone_number\":\"19195552222\",\"volunteer_gender\":\"0\",\"volunteer_responder\":\"0\",\"volunteer_shift_schedule\":\"W3siZGF5IjoxLCJ0eiI6IkFtZXJpY2EvTmV3X1lvcmsiLCJzdGFydF90aW1lIjoiMTI6MDAgQU0iLCJlbmRfdGltZSI6IjExOjU5IFBNIiwidHlwZSI6IlBIT05FIn0seyJkYXkiOjIsInR6IjoiQW1lcmljYS9OZXdfWW9yayIsInN0YXJ0X3RpbWUiOiIxMjowMCBBTSIsImVuZF90aW1lIjoiMTE6NTkgUE0iLCJ0eXBlIjoiUEhPTkUifSx7ImRheSI6MywidHoiOiJBbWVyaWNhL05ld19Zb3JrIiwic3RhcnRfdGltZSI6IjEyOjAwIEFNIiwiZW5kX3RpbWUiOiIxMTo1OSBQTSIsInR5cGUiOiJQSE9ORSJ9LHsiZGF5Ijo0LCJ0eiI6IkFtZXJpY2EvTmV3X1lvcmsiLCJzdGFydF90aW1lIjoiMTI6MDAgQU0iLCJlbmRfdGltZSI6IjExOjU5IFBNIiwidHlwZSI6IlBIT05FIn0seyJkYXkiOjUsInR6IjoiQW1lcmljYS9OZXdfWW9yayIsInN0YXJ0X3RpbWUiOiIxMjowMCBBTSIsImVuZF90aW1lIjoiMTE6NTkgUE0iLCJ0eXBlIjoiUEhPTkUifSx7ImRheSI6NiwidHoiOiJBbWVyaWNhL05ld19Zb3JrIiwic3RhcnRfdGltZSI6IjEyOjAwIEFNIiwiZW5kX3RpbWUiOiIxMTo1OSBQTSIsInR5cGUiOiJQSE9ORSJ9LHsiZGF5Ijo3LCJ0eiI6IkFtZXJpY2EvTmV3X1lvcmsiLCJzdGFydF90aW1lIjoiMTI6MDAgQU0iLCJlbmRfdGltZSI6IjExOjU5IFBNIiwidHlwZSI6IlBIT05FIn1d\",\"volunteer_notes\":\"\",\"volunteer_enabled\":\"true\"}]','_YAP_VOLUNTEERS_V2_',NULL,NULL);
/*!40000 ALTER TABLE `config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event_status`
--

DROP TABLE IF EXISTS `event_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_status` (
  `id` bigint(11) NOT NULL AUTO_INCREMENT,
  `callsid` varchar(255) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `event_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_status`
--

LOCK TABLES `event_status` WRITE;
/*!40000 ALTER TABLE `event_status` DISABLE KEYS */;
/*!40000 ALTER TABLE `event_status` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `flags`
--

DROP TABLE IF EXISTS `flags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `flags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `flag_name` varchar(50) NOT NULL,
  `flag_setting` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `flag_name_UNIQUE` (`flag_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `flags`
--

LOCK TABLES `flags` WRITE;
/*!40000 ALTER TABLE `flags` DISABLE KEYS */;
/*!40000 ALTER TABLE `flags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `metrics`
--

DROP TABLE IF EXISTS `metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `metrics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data` text NOT NULL,
  `service_body_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `metrics`
--

LOCK TABLES `metrics` WRITE;
/*!40000 ALTER TABLE `metrics` DISABLE KEYS */;
/*!40000 ALTER TABLE `metrics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `version` varchar(45) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0','2024-03-10 16:26:24'),(2,'1','2024-03-10 16:26:24'),(3,'2','2024-03-10 16:26:24'),(4,'3','2024-03-10 16:26:24'),(5,'4','2024-03-10 16:26:24'),(6,'5','2024-03-10 16:26:24'),(7,'6','2024-03-10 16:26:24'),(8,'7','2024-03-10 16:26:24'),(9,'8','2024-03-10 16:26:24'),(10,'9','2024-03-10 16:26:24'),(11,'10','2024-03-10 16:26:24'),(12,'11','2024-03-10 16:26:24'),(13,'12','2024-03-10 16:26:24'),(14,'13','2024-03-10 16:26:24'),(15,'14','2024-03-10 16:26:24'),(16,'15','2024-03-10 16:26:24'),(17,'16','2024-03-10 16:26:24'),(18,'17','2024-03-10 16:26:24'),(19,'18','2024-03-10 16:26:24'),(20,'19','2024-03-10 16:26:24'),(21,'20','2024-03-10 16:26:24'),(22,'21','2024-03-10 16:26:24'),(23,'22','2024-03-10 16:26:24'),(24,'23','2024-03-10 16:26:24'),(25,'24','2024-03-10 16:26:24'),(26,'25','2024-03-10 16:26:24'),(27,'26','2024-03-10 16:26:24'),(28,'27','2024-03-10 16:26:24'),(29,'28','2024-03-10 16:26:24'),(30,'29','2024-03-10 16:26:24'),(31,'30','2024-03-10 16:26:24'),(32,'31','2024-03-10 16:26:24'),(33,'32','2024-03-10 16:26:24'),(34,'33','2024-03-10 16:26:24'),(35,'34','2024-03-10 16:26:24'),(36,'35','2024-03-10 16:26:24'),(37,'36','2024-03-10 16:26:24'),(38,'37','2024-03-10 16:26:24'),(39,'38','2024-03-10 16:26:24');
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `records`
--

DROP TABLE IF EXISTS `records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `callsid` varchar(255) NOT NULL,
  `start_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `from_number` varchar(255) NOT NULL,
  `to_number` varchar(255) NOT NULL,
  `payload` longtext,
  `duration` int(11) NOT NULL,
  `type` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_records_callsid` (`callsid`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `records`
--

LOCK TABLES `records` WRITE;
/*!40000 ALTER TABLE `records` DISABLE KEYS */;
INSERT INTO `records` VALUES (1,'CA31becca54c79565ad919b867bb56cf60','2024-03-11 01:55:21','2024-03-11 01:55:28','+19739316239','+19193559674','{\"Called\":\"+19193559674\",\"ToState\":\"NC\",\"CallerCountry\":\"US\",\"Direction\":\"inbound\",\"Timestamp\":\"Mon, 11 Mar 2024 01:55:28 +0000\",\"CallbackSource\":\"call-progress-events\",\"CallerState\":\"NJ\",\"ToZip\":\"27502\",\"SequenceNumber\":\"0\",\"To\":\"+19193559674\",\"CallSid\":\"CA31becca54c79565ad919b867bb56cf60\",\"ToCountry\":\"US\",\"CallerZip\":\"07055\",\"CalledZip\":\"27502\",\"ApiVersion\":\"2010-04-01\",\"CallStatus\":\"completed\",\"CalledCity\":\"APEX\",\"Duration\":\"1\",\"From\":\"+19739316239\",\"CallDuration\":\"7\",\"AccountSid\":\"ACcb1815ec24d4d4451331adb60cc94a58\",\"CalledCountry\":\"US\",\"CallerCity\":\"PASSAIC\",\"ToCity\":\"APEX\",\"FromCountry\":\"US\",\"Caller\":\"+19739316239\",\"FromCity\":\"PASSAIC\",\"CalledState\":\"NC\",\"FromZip\":\"07055\",\"FromState\":\"NJ\"}',7,1),(2,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:03:53','2024-03-15 23:05:30','+19739316239','+19193559674','{\"Called\":\"+19193559674\",\"ToState\":\"NC\",\"CallerCountry\":\"US\",\"Direction\":\"inbound\",\"Timestamp\":\"Fri, 15 Mar 2024 23:05:30 +0000\",\"CallbackSource\":\"call-progress-events\",\"CallerState\":\"NJ\",\"ToZip\":\"27502\",\"SequenceNumber\":\"0\",\"To\":\"+19193559674\",\"CallSid\":\"CA460d1728a3e07606f36aaa8879a7fbd3\",\"ToCountry\":\"US\",\"CallerZip\":\"07055\",\"CalledZip\":\"27502\",\"ApiVersion\":\"2010-04-01\",\"CallStatus\":\"completed\",\"CalledCity\":\"APEX\",\"Duration\":\"2\",\"From\":\"+19739316239\",\"CallDuration\":\"97\",\"AccountSid\":\"ACcb1815ec24d4d4451331adb60cc94a58\",\"CalledCountry\":\"US\",\"CallerCity\":\"PASSAIC\",\"ToCity\":\"APEX\",\"FromCountry\":\"US\",\"Caller\":\"+19739316239\",\"FromCity\":\"PASSAIC\",\"CalledState\":\"NC\",\"FromZip\":\"07055\",\"FromState\":\"NJ\"}',97,1);
/*!40000 ALTER TABLE `records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `records_events`
--

DROP TABLE IF EXISTS `records_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `records_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `callsid` varchar(255) NOT NULL,
  `event_time` timestamp NULL DEFAULT NULL,
  `event_id` int(11) NOT NULL,
  `service_body_id` int(11) DEFAULT NULL,
  `meta` text,
  `type` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_records_events_service_body_id` (`service_body_id`),
  KEY `idx_records_events_callsid` (`callsid`)
) ENGINE=InnoDB AUTO_INCREMENT=90 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `records_events`
--

LOCK TABLES `records_events` WRITE;
/*!40000 ALTER TABLE `records_events` DISABLE KEYS */;
INSERT INTO `records_events` VALUES (1,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:04:15',1,NULL,'{\"gather\":\"Buffalo, NY\",\"coordinates\":{\"location\":\"Buffalo, NY, USA\",\"latitude\":42.88644679999999,\"longitude\":-78.8783689}}',1),(2,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:04:18',10,1060,NULL,1),(3,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:04:19',5,1060,'{\"to_number\":\"12125551212\"}',1),(4,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:23:50',13,4400,NULL,1),(5,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:23:51',13,4400,NULL,1),(6,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:25:53',13,4400,NULL,1),(7,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:25:53',13,4400,NULL,1),(8,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:26:13',13,4400,NULL,1),(9,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:26:13',13,4400,NULL,1),(10,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:27:12',13,4400,NULL,1),(11,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:27:13',13,4400,NULL,1),(12,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:27:18',13,4400,NULL,1),(13,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:27:18',13,4400,NULL,1),(14,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:27:46',13,4400,NULL,1),(15,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:27:46',13,4400,NULL,1),(16,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:27:57',13,4400,NULL,1),(17,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:27:57',13,4400,NULL,1),(18,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:28:28',13,4400,NULL,1),(19,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:28:28',13,4400,NULL,1),(20,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:28:39',13,4400,NULL,1),(21,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:28:39',13,4400,NULL,1),(22,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:28:49',13,4400,NULL,1),(23,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:28:49',13,4400,NULL,1),(24,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:29:10',13,4400,NULL,1),(25,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:29:10',13,4400,NULL,1),(26,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:29:45',13,4400,NULL,1),(27,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:29:45',13,4400,NULL,1),(28,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:30:03',13,4400,NULL,1),(29,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:30:03',13,4400,NULL,1),(30,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:30:14',13,4400,NULL,1),(31,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:30:14',13,4400,NULL,1),(32,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:30:25',13,4400,NULL,1),(33,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:30:25',13,4400,NULL,1),(34,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:30:42',13,4400,NULL,1),(35,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:30:42',13,4400,NULL,1),(36,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:31:13',13,4400,NULL,1),(37,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:31:13',13,4400,NULL,1),(38,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:31:23',13,4400,NULL,1),(39,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:31:23',13,4400,NULL,1),(40,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:31:49',13,4400,NULL,1),(41,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:31:49',13,4400,NULL,1),(42,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:32:00',13,4400,NULL,1),(43,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:32:00',13,4400,NULL,1),(44,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:32:14',13,4400,NULL,1),(45,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:32:14',13,4400,NULL,1),(46,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:32:27',13,4400,NULL,1),(47,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:32:27',13,4400,NULL,1),(48,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-16 00:09:06',13,4400,NULL,1),(49,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-16 00:09:06',13,4400,NULL,1),(50,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-16 00:48:39',13,4400,NULL,1),(51,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-16 00:48:39',13,4400,NULL,1),(52,'abc','2024-03-16 00:57:07',10,4400,NULL,1),(53,'abc','2024-03-16 00:57:22',10,4400,NULL,1),(54,'abc','2024-03-16 00:57:42',10,4400,NULL,1),(55,'abc','2024-03-16 00:57:42',5,4400,'{\"to_number\":\"(732) 566-5232\"}',1),(56,'abc','2024-03-16 00:57:42',10,4400,NULL,1),(57,'abc','2024-03-16 00:57:42',5,4400,'{\"to_number\":\"(732) 566-5232\"}',1),(58,'abc','2024-03-16 00:57:55',10,4400,NULL,1),(59,'abc','2024-03-16 00:57:55',5,4400,'{\"to_number\":\"(732) 566-5232\"}',1),(60,'abc','2024-03-16 00:57:55',10,4400,NULL,1),(61,'abc','2024-03-16 00:57:55',5,4400,'{\"to_number\":\"(732) 566-5232\"}',1),(62,'abc','2024-03-16 00:58:01',10,4400,NULL,1),(63,'abc','2024-03-16 00:58:01',5,4400,'{\"to_number\":\"(732) 566-5232\"}',1),(64,'abc','2024-03-16 00:58:01',10,4400,NULL,1),(65,'abc','2024-03-16 00:58:01',5,4400,'{\"to_number\":\"(732) 566-5232\"}',1),(66,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-16 00:58:01',13,4400,NULL,1),(67,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-16 00:58:01',13,4400,NULL,1),(68,'abc','2024-03-16 00:58:44',10,4400,NULL,1),(69,'abc','2024-03-16 00:58:44',5,4400,'{\"to_number\":\"(732) 566-5232\"}',1),(70,'abc','2024-03-16 00:58:44',10,4400,NULL,1),(71,'abc','2024-03-16 00:58:44',5,4400,'{\"to_number\":\"(732) 566-5232\"}',1),(72,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-16 00:58:44',13,4400,NULL,1),(73,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-16 00:58:44',13,4400,NULL,1),(74,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-16 03:13:32',13,4400,NULL,1),(75,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-16 03:13:33',13,4400,NULL,1),(76,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-16 03:16:23',13,4400,NULL,1),(77,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-16 03:16:29',13,4400,NULL,1),(78,'CA460d1728a3e07606f36aaa8879a7fbd2','2024-03-16 03:17:06',11,4400,NULL,1),(79,'CA460d1728a3e07606f36aaa8879a7fbd2','2024-03-16 03:17:06',11,4400,NULL,1),(80,'abc','2024-03-16 03:18:23',10,4400,NULL,1),(81,'abc','2024-03-16 03:18:23',5,4400,'{\"to_number\":\"(732) 566-5232\"}',1),(82,'abc','2024-03-16 03:18:23',10,4400,NULL,1),(83,'abc','2024-03-16 03:18:23',5,4400,'{\"to_number\":\"(732) 566-5232\"}',1),(84,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-16 03:18:23',13,4400,NULL,1),(85,'CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-16 03:18:23',13,4400,NULL,1),(86,'CA460d1728a3e07606f36aaa8879a7fbd2','2024-03-16 03:18:23',11,4400,NULL,1),(87,'CA460d1728a3e07606f36aaa8879a7fbd2','2024-03-16 03:18:23',11,4400,NULL,1),(88,'CA460d1728a3e07606f36aaa8879a7fbd2','2024-03-16 03:18:55',11,4400,NULL,1),(89,'CA460d1728a3e07606f36aaa8879a7fbd2','2024-03-16 03:18:55',11,4400,NULL,1);
/*!40000 ALTER TABLE `records_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `callsid` varchar(255) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `pin` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('CA31becca54c79565ad919b867bb56cf60','2024-03-11 01:55:21',2916149),('abc','2024-03-15 22:58:47',4532979),('CA460d1728a3e07606f36aaa8879a7fbd3','2024-03-15 23:03:52',8723858),('CA460d1728a3e07606f36aaa8879a7fbd2','2024-03-16 03:17:06',6652045);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 NOT NULL,
  `username` varchar(45) NOT NULL,
  `password` varchar(255) NOT NULL,
  `permissions` int(11) NOT NULL DEFAULT '0',
  `is_admin` int(11) DEFAULT NULL,
  `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `service_bodies` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username_UNIQUE` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','admin','8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918',0,1,'2024-03-11 19:43:57',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2024-03-15 23:20:36
