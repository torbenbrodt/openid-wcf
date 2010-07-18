DROP TABLE IF EXISTS wcf1_user_to_openid;
CREATE TABLE wcf1_user_to_openid (
	userID INT(10) NOT NULL DEFAULT 0,
	identifier VARCHAR(255) NOT NULL DEFAULT '',
	openID INT(10) NOT NULL DEFAULT 0,
	UNIQUE(userID),
	UNIQUE(identifier, openID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;