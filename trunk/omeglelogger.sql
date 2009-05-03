--
-- Database: 'OmegleLogger'
--

-- --------------------------------------------------------

--
-- Table structure for table 'ChatLog'
--

CREATE TABLE IF NOT EXISTS ChatLog (
  chatLogId int(10) unsigned NOT NULL auto_increment,
  startTime int(10) unsigned NOT NULL,
  endTime int(10) unsigned NOT NULL,
  PRIMARY KEY (chatLogId)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table 'Message'
--

CREATE TABLE IF NOT EXISTS Message (
  messageId int(10) unsigned NOT NULL auto_increment,
  chatLogId int(10) unsigned NOT NULL,
  chatId varchar(10) NOT NULL,
  messageTime int(10) unsigned NOT NULL,
  messageType varchar(30) NOT NULL,
  message text NOT NULL,
  PRIMARY KEY  (messageId),
  KEY chatLogId (chatLogId)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Table structure for table 'Spamlist'
--

CREATE TABLE IF NOT EXISTS Spamlist (
  spamlistId int(11) NOT NULL auto_increment,
  spam varchar(255) NOT NULL,
  PRIMARY KEY  (spamlistId)
) ENGINE=MyISAM;

INSERT INTO Spamlist VALUES(1, 'TheSmizz.com');
INSERT INTO Spamlist VALUES(2, 'all-the-exgirlfriends.info');
INSERT INTO Spamlist VALUES(3, 'marie-wird-entjungfert.net');
INSERT INTO Spamlist VALUES(4, 'mybrute.com');
INSERT INTO Spamlist VALUES(5, 'Marie-gets-Deflowered.com');
INSERT INTO Spamlist VALUES(6, 'sexyemilie.com');
INSERT INTO Spamlist VALUES(7, 'ihateliz.com');
INSERT INTO Spamlist VALUES(8, 'saurav.k@hotmail.com');
INSERT INTO Spamlist VALUES(9, 'binjamin112.webs.com');
INSERT INTO Spamlist VALUES(10, 'whitehat.net.nz');
INSERT INTO Spamlist VALUES(11, 'iddin.com');
INSERT INTO Spamlist VALUES(12, 'free.fr');
INSERT INTO Spamlist VALUES(13, 'elbruto.es');

