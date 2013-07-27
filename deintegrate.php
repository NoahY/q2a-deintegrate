<?php
	
	// edit these values, then run the script.
	
	$host = "localhost";
	$user = "user";
	$pass = "password";
	$database = "wordpress"; // this database should have both your q2a and wp tables
	$wp_prefix = "wp_";
	$prefix = "qa_";
	$site_url = "http://www.example.com/"; // sent in email
	$site_title = "Sirimangalo Q&A";
	$return_address = "info@example.com";
	
// You shouldn't have to edit anything below this line, but feel free to peek if you want to see what it's doing.

//	Maximum column sizes - any of these can be defined in qa-config.php to override the defaults below,
//	but you need to do so before creating the database, otherwise it's too late.

	@define('QA_DB_MAX_EMAIL_LENGTH', 80);
	@define('QA_DB_MAX_HANDLE_LENGTH', 20);
	@define('QA_DB_MAX_TITLE_LENGTH', 800);
	@define('QA_DB_MAX_CONTENT_LENGTH', 8000);
	@define('QA_DB_MAX_FORMAT_LENGTH', 20);
	@define('QA_DB_MAX_TAGS_LENGTH', 800);
	@define('QA_DB_MAX_NAME_LENGTH', 40);
	@define('QA_DB_MAX_WORD_LENGTH', 80);
	@define('QA_DB_MAX_CAT_PAGE_TITLE_LENGTH', 80);
	@define('QA_DB_MAX_CAT_PAGE_TAGS_LENGTH', 200);
	@define('QA_DB_MAX_CAT_CONTENT_LENGTH', 800);
	@define('QA_DB_MAX_WIDGET_TAGS_LENGTH', 800);
	@define('QA_DB_MAX_WIDGET_TITLE_LENGTH', 80);
	@define('QA_DB_MAX_OPTION_TITLE_LENGTH', 40);
	@define('QA_DB_MAX_PROFILE_TITLE_LENGTH', 40);
	@define('QA_DB_MAX_PROFILE_CONTENT_LENGTH', 8000);
	@define('QA_DB_MAX_CACHE_AGE', 86400);
	@define('QA_DB_MAX_BLOB_FILE_NAME_LENGTH', 255);
	@define('QA_DB_MAX_META_TITLE_LENGTH', 40);
	@define('QA_DB_MAX_META_CONTENT_LENGTH', 8000);

	$mysqli = new mysqli($host,$user,$pass,$database);

	$useridcoltype='INT UNSIGNED';

	// userlogins
	// userprofile
	// users
	// userfields
	// userlevels
	// userfavorites
	// usernotices
	// userevents
	// messages
	// posts
	// uservotes
	// userpoints
	// userlimits
	// usermetas

	$tables = array(
		'users' => array(
			'userid' => $useridcoltype.' NOT NULL AUTO_INCREMENT',
			'created' => 'DATETIME NOT NULL',
			'createip' => 'INT UNSIGNED NOT NULL', // INET_ATON of IP address when created
			'email' => 'VARCHAR('.QA_DB_MAX_EMAIL_LENGTH.') NOT NULL',
			'handle' => 'VARCHAR('.QA_DB_MAX_HANDLE_LENGTH.') NOT NULL', // username
			'avatarblobid' => 'BIGINT UNSIGNED', // blobid of stored avatar
			'avatarwidth' => 'SMALLINT UNSIGNED', // pixel width of stored avatar
			'avatarheight' => 'SMALLINT UNSIGNED', // pixel height of stored avatar
			'passsalt' => 'BINARY(16)', // salt used to calculate passcheck - null if no password set for direct login
			'passcheck' => 'BINARY(20)', // checksum from password and passsalt - null if no passowrd set for direct login
			'level' => 'TINYINT UNSIGNED NOT NULL', // basic, editor, admin, etc...
			'loggedin' => 'DATETIME NOT NULL', // time of last login
			'loginip' => 'INT UNSIGNED NOT NULL', // INET_ATON of IP address of last login
			'written' => 'DATETIME', // time of last write action done by user
			'writeip' => 'INT UNSIGNED', // INET_ATON of IP address of last write action done by user
			'emailcode' => 'CHAR(8) CHARACTER SET ascii NOT NULL DEFAULT \'\'', // for email confirmation or password reset
			'sessioncode' => 'CHAR(8) CHARACTER SET ascii NOT NULL DEFAULT \'\'', // for comparing against session cookie in browser
			'sessionsource' => 'VARCHAR (16) CHARACTER SET ascii DEFAULT \'\'', // e.g. facebook, openid, etc...
			'flags' => 'SMALLINT UNSIGNED NOT NULL DEFAULT 0', // see constants at top of qa-app-users.php
			'wallposts' => 'MEDIUMINT NOT NULL DEFAULT 0', // cached count of wall posts 
			'PRIMARY KEY (userid)',
			'KEY email (email)',
			'KEY handle (handle)',
			'KEY level (level)',
			'kEY created (created, level, flags)',
		),
		'userlogins' => array(
			'userid' => $useridcoltype.' NOT NULL',
			'source' => 'VARCHAR (16) CHARACTER SET ascii NOT NULL', // e.g. facebook, openid, etc...
			'identifier' => 'VARBINARY (1024) NOT NULL', // depends on source, e.g. Facebook uid or OpenID url
			'identifiermd5' => 'BINARY (16) NOT NULL', // used to reduce size of index on identifier
			'KEY source (source, identifiermd5)',
			'KEY userid (userid)',
		),		
		'userprofile' => array(
			'userid' => $useridcoltype.' NOT NULL',
			'title' => 'VARCHAR('.QA_DB_MAX_PROFILE_TITLE_LENGTH.') NOT NULL', // profile field name
			'content' => 'VARCHAR('.QA_DB_MAX_PROFILE_CONTENT_LENGTH.') NOT NULL', // profile field value
			'UNIQUE userid (userid,title)',
		),
		'userfields' => array(
			'fieldid' => 'SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT',
			'title' => 'VARCHAR('.QA_DB_MAX_PROFILE_TITLE_LENGTH.') NOT NULL', // to match title column in userprofile table
			'content' => 'VARCHAR('.QA_DB_MAX_PROFILE_TITLE_LENGTH.')', // label for display on user profile pages - NULL means use default
			'position' => 'SMALLINT UNSIGNED NOT NULL',
			'flags' => 'TINYINT UNSIGNED NOT NULL', // QA_FIELD_FLAGS_* at top of qa-app-users.php
			'permit' => 'TINYINT UNSIGNED', // minimum user level required to view (uses QA_PERMIT_* constants), null means no restriction
			'PRIMARY KEY (fieldid)',
		),
			
		'userlevels' => array(
			'userid' => $useridcoltype.' NOT NULL', // the user who has this level
			'entitytype' => "CHAR(1) CHARACTER SET ascii NOT NULL", // see qa-app-updates.php
			'entityid' => 'INT UNSIGNED NOT NULL', // relevant postid / userid / tag wordid / categoryid
			'level' => 'TINYINT UNSIGNED', // if not NULL, special permission level for that user and that entity
			'UNIQUE userid (userid, entitytype, entityid)',
			'KEY entitytype (entitytype, entityid)',
		),
		'userfavorites' => array(
			'userid' => $useridcoltype.' NOT NULL', // the user who favorited the entity
			'entitytype' => "CHAR(1) CHARACTER SET ascii NOT NULL", // see qa-app-updates.php
			'entityid' => 'INT UNSIGNED NOT NULL', // favorited postid / userid / tag wordid / categoryid
			'nouserevents' => 'TINYINT UNSIGNED NOT NULL', // do we skip writing events to the user stream?
			'PRIMARY KEY (userid, entitytype, entityid)',
			'KEY userid (userid, nouserevents)',
			'KEY entitytype (entitytype, entityid, nouserevents)',
		),
		
		'usernotices' => array(
			'noticeid' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT',
			'userid' => $useridcoltype.' NOT NULL', // the user to whom the notice is directed
			'content' => 'VARCHAR('.QA_DB_MAX_CONTENT_LENGTH.') NOT NULL',
			'format' => 'VARCHAR('.QA_DB_MAX_FORMAT_LENGTH.') CHARACTER SET ascii NOT NULL',
			'tags' => 'VARCHAR('.QA_DB_MAX_CAT_PAGE_TAGS_LENGTH.')', // any additional information for a plugin to access
			'created' => 'DATETIME NOT NULL',
			'PRIMARY KEY (noticeid)',
			'KEY userid (userid, created)',
		),
		
		'userevents' => array(
			'userid' => $useridcoltype.' NOT NULL', // the user to be informed about this event in their updates
			'entitytype' => "CHAR(1) CHARACTER SET ascii NOT NULL", // see qa-app-updates.php
			'entityid' => 'INT UNSIGNED NOT NULL', // favorited source of event - see userfavorites table - 0 means not from a favorite
			'questionid' => 'INT UNSIGNED NOT NULL', // the affected question
			'lastpostid' => 'INT UNSIGNED NOT NULL', // what part of question was affected
			'updatetype' => 'CHAR(1) CHARACTER SET ascii', // what was done to this part - see qa-app-updates.php
			'lastuserid' => $useridcoltype, // which user (if any) did this action
			'updated' => 'DATETIME NOT NULL', // when the event happened
			'KEY userid (userid, updated)', // for truncation
			'KEY questionid (questionid, userid)', // to limit number of events per question per stream
		),
						
		'messages' => array(
			'messageid' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT',
			'type' => "ENUM('PUBLIC', 'PRIVATE') NOT NULL DEFAULT 'PRIVATE'",
			'fromuserid' => $useridcoltype.' NOT NULL',
			'touserid' => $useridcoltype.' NOT NULL',
			'content' => 'VARCHAR('.QA_DB_MAX_CONTENT_LENGTH.') NOT NULL',
			'format' => 'VARCHAR('.QA_DB_MAX_FORMAT_LENGTH.') CHARACTER SET ascii NOT NULL',
			'created' => 'DATETIME NOT NULL',
			'PRIMARY KEY (messageid)',
			'KEY type (type, fromuserid, touserid, created)',
			'KEY touserid (touserid, type, created)',
		),

		'posts' => array(
			'postid' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT',
			'type' => "ENUM('Q', 'A', 'C', 'Q_HIDDEN', 'A_HIDDEN', 'C_HIDDEN', 'Q_QUEUED', 'A_QUEUED', 'C_QUEUED', 'NOTE') NOT NULL",
			'parentid' => 'INT UNSIGNED', // for follow on questions, all answers and comments
			'categoryid' => 'INT UNSIGNED', // this is the canonical final category id
			'catidpath1' => 'INT UNSIGNED', // the catidpath* columns are calculated from categoryid, for the full hierarchy of that category
			'catidpath2' => 'INT UNSIGNED', // note that QA_CATEGORY_DEPTH=4
			'catidpath3' => 'INT UNSIGNED',
			'acount' => 'SMALLINT UNSIGNED NOT NULL DEFAULT 0', // number of answers (for questions)
			'amaxvote' => 'SMALLINT UNSIGNED NOT NULL DEFAULT 0', // highest netvotes of child answers (for questions)
			'selchildid' => 'INT UNSIGNED', // selected answer (for questions)
			'closedbyid' => 'INT UNSIGNED', // not null means question is closed
				// if closed due to being a duplicate, this is the postid of that other question
				// if closed for another reason, that reason should be added as a comment on the question, and this field is the comment's id
			'userid' => $useridcoltype, // which user wrote it
			'cookieid' => 'BIGINT UNSIGNED', // which cookie wrote it, if an anonymous post
			'createip' => 'INT UNSIGNED', // INET_ATON of IP address used to create the post
			'lastuserid' => $useridcoltype, // which user last modified it
			'lastip' => 'INT UNSIGNED', // INET_ATON of IP address which last modified the post
			'upvotes' => 'SMALLINT UNSIGNED NOT NULL DEFAULT 0',
			'downvotes' => 'SMALLINT UNSIGNED NOT NULL DEFAULT 0',
			'netvotes' => 'SMALLINT NOT NULL DEFAULT 0',
			'lastviewip' => 'INT UNSIGNED', // INET_ATON of IP address which last viewed the post
			'views' => 'INT UNSIGNED NOT NULL DEFAULT 0',
			'hotness' => 'FLOAT',
			'flagcount' => 'TINYINT UNSIGNED NOT NULL DEFAULT 0',
			'format' => 'VARCHAR('.QA_DB_MAX_FORMAT_LENGTH.') CHARACTER SET ascii NOT NULL DEFAULT \'\'', // format of content, e.g. 'html'
			'created' => 'DATETIME NOT NULL',
			'updated' => 'DATETIME', // time of last update
			'updatetype' => 'CHAR(1) CHARACTER SET ascii', // see qa-app-updates.php
			'title' => 'VARCHAR('.QA_DB_MAX_TITLE_LENGTH.')',
			'content' => 'VARCHAR('.QA_DB_MAX_CONTENT_LENGTH.')',
			'tags' => 'VARCHAR('.QA_DB_MAX_TAGS_LENGTH.')', // string of tags separated by commas
			'name' => 'VARCHAR('.QA_DB_MAX_NAME_LENGTH.')', // name of author if post anonymonus
			'notify' => 'VARCHAR('.QA_DB_MAX_EMAIL_LENGTH.')', // email address, or @ to get from user, or NULL for none
			'PRIMARY KEY (postid)',
			'KEY type (type, created)', // for getting recent questions, answers, comments
			'KEY type_2 (type, acount, created)', // for getting unanswered questions
			'KEY type_4 (type, netvotes, created)', // for getting posts with the most votes
			'KEY type_5 (type, views, created)', // for getting questions with the most views
			'KEY type_6 (type, hotness)', // for getting 'hot' questions
			'KEY type_7 (type, amaxvote, created)', // for getting questions with no upvoted answers
			'KEY parentid (parentid, type)', // for getting a question's answers, any post's comments and follow-on questions
			'KEY userid (userid, type, created)', // for recent questions, answers or comments by a user
			'KEY selchildid (selchildid, type, created)', // for counting how many of a user's answers have been selected, unselected qs
			'KEY closedbyid (closedbyid)', // for the foreign key constraint
			'KEY catidpath1 (catidpath1, type, created)', // for getting question, answers or comments in a specific level category
			'KEY catidpath2 (catidpath2, type, created)', // note that QA_CATEGORY_DEPTH=4
			'KEY catidpath3 (catidpath3, type, created)',
			'KEY categoryid (categoryid, type, created)', // this can also be used for searching the equivalent of catidpath4
			'KEY createip (createip, created)', // for getting posts created by a specific IP address
			'KEY updated (updated, type)', // for getting recent edits across all categories
			'KEY flagcount (flagcount, created, type)', // for getting posts with the most flags
			'KEY catidpath1_2 (catidpath1, updated, type)', // for getting recent edits in a specific level category
			'KEY catidpath2_2 (catidpath2, updated, type)', // note that QA_CATEGORY_DEPTH=4
			'KEY catidpath3_2 (catidpath3, updated, type)',
			'KEY categoryid_2 (categoryid, updated, type)',
			'KEY lastuserid (lastuserid, updated, type)', // for getting posts edited by a specific user
			'KEY lastip (lastip, updated, type)', // for getting posts edited by a specific IP address
			'CONSTRAINT ^posts_ibfk_2 FOREIGN KEY (parentid) REFERENCES ^posts(postid)', // ^posts_ibfk_1 is set later on userid
			'CONSTRAINT ^posts_ibfk_3 FOREIGN KEY (categoryid) REFERENCES ^categories(categoryid) ON DELETE SET NULL',
			'CONSTRAINT ^posts_ibfk_4 FOREIGN KEY (closedbyid) REFERENCES ^posts(postid)',
		),

		'uservotes' => array(
			'postid' => 'INT UNSIGNED NOT NULL',
			'userid' => $useridcoltype.' NOT NULL',
			'vote' => 'TINYINT NOT NULL', // -1, 0 or 1
			'flag' => 'TINYINT NOT NULL', // 0 or 1
			'UNIQUE userid (userid, postid)',
			'KEY postid (postid)',
			'CONSTRAINT ^uservotes_ibfk_1 FOREIGN KEY (postid) REFERENCES ^posts(postid) ON DELETE CASCADE',
		),
		
		// many userpoints columns could be unsigned but MySQL appears to mess up points calculations that go negative as a result
		
		'userpoints' => array(
			'userid' => $useridcoltype.' NOT NULL',
			'points' => 'INT NOT NULL DEFAULT 0', // user's points as displayed, after final multiple
			'qposts' => 'MEDIUMINT NOT NULL DEFAULT 0', // number of questions by user (excluding hidden/queued)
			'aposts' => 'MEDIUMINT NOT NULL DEFAULT 0', // number of answers by user (excluding hidden/queued)
			'cposts' => 'MEDIUMINT NOT NULL DEFAULT 0', // number of comments by user (excluding hidden/queued)
			'aselects' => 'MEDIUMINT NOT NULL DEFAULT 0', // number of questions by user where they've selected an answer
			'aselecteds' => 'MEDIUMINT NOT NULL DEFAULT 0', // number of answers by user that have been selected as the best
			'qupvotes' => 'MEDIUMINT NOT NULL DEFAULT 0', // number of questions the user has voted up
			'qdownvotes' => 'MEDIUMINT NOT NULL DEFAULT 0', // number of questions the user has voted down
			'aupvotes' => 'MEDIUMINT NOT NULL DEFAULT 0', // number of answers the user has voted up
			'adownvotes' => 'MEDIUMINT NOT NULL DEFAULT 0', // number of answers the user has voted down
			'qvoteds' => 'INT NOT NULL DEFAULT 0', // points from votes on this user's questions (applying per-question limits), before final multiple
			'avoteds' => 'INT NOT NULL DEFAULT 0', // points from votes on this user's answers (applying per-answer limits), before final multiple
			'upvoteds' => 'INT NOT NULL DEFAULT 0', // number of up votes received on this user's questions or answers
			'downvoteds' => 'INT NOT NULL DEFAULT 0', // number of down votes received on this user's questions or answers
			'bonus' => 'INT NOT NULL DEFAULT 0', // bonus assigned by administrator to a user
			'PRIMARY KEY (userid)',
			'KEY points (points)',
		),
			
		'userlimits' => array(
			'userid' => $useridcoltype.' NOT NULL',
			'actinformation_schema.TABLE_CONSTRAINTSion' => 'CHAR(1) CHARACTER SET ascii NOT NULL', // see constants at top of qa-app-limits.php
			'period' => 'INT UNSIGNED NOT NULL', // integer representing hour of last action
			'count' => 'SMALLINT UNSIGNED NOT NULL', // how many of this action has been performed within that hour
			'UNIQUE userid (userid, action)',
		),
		
		'usermetas' => array(
			'userid' => $useridcoltype.' NOT NULL',
			'title' => 'VARCHAR('.QA_DB_MAX_META_TITLE_LENGTH.') NOT NULL',
			'content' => 'VARCHAR('.QA_DB_MAX_META_CONTENT_LENGTH.') NOT NULL',
			'PRIMARY KEY (userid, title)',
		),			
		
	);

	// userlogins
	// userprofile
	// users
	// userfields
	// userlevels
	// userfavorites
	// usernotices
	// userevents
	// messages
	// posts
	// uservotes
	// userpoints
	// userlimits
	// usermetas

	$userforeignkey='FOREIGN KEY (userid) REFERENCES ^users(userid)';
	
	$constraints['userlogins']='CONSTRAINT ^userlogins_ibfk_1 '.$userforeignkey.' ON DELETE CASCADE';
	$constraints['userprofile']='CONSTRAINT ^userprofile_ibfk_1 '.$userforeignkey.' ON DELETE CASCADE';
	$constraints['posts']='CONSTRAINT ^posts_ibfk_1 '.$userforeignkey.' ON DELETE SET NULL';
	$constraints['uservotes']='CONSTRAINT ^uservotes_ibfk_2 '.$userforeignkey.' ON DELETE CASCADE';
	$constraints['userlimits']='CONSTRAINT ^userlimits_ibfk_1 '.$userforeignkey.' ON DELETE CASCADE';
	$constraints['userfavorites']='CONSTRAINT ^userfavorites_ibfk_1 '.$userforeignkey.' ON DELETE CASCADE';
	$constraints['usernotices']='CONSTRAINT ^usernotices_ibfk_1 '.$userforeignkey.' ON DELETE CASCADE';
	$constraints['userevents']='CONSTRAINT ^userevents_ibfk_1 '.$userforeignkey.' ON DELETE CASCADE';
	$constraints['userlevels']='CONSTRAINT ^userlevels_ibfk_1 '.$userforeignkey.' ON DELETE CASCADE';
	$constraints['usermetas']='CONSTRAINT ^usermetas_ibfk_1 '.$userforeignkey.' ON DELETE CASCADE';

	// get existing tables

	$tables_query = $mysqli->query("SHOW TABLES");
	if(!$tables_query) {
		printf("Error message: %s<br/>", $mysqli->error);
		die();
	}
	
	$existing_tables = array();
	while($atable = $tables_query->fetch_row()) {
		$existing_tables[] = $atable[0];
	}
	
	// create or modify tables

	foreach($tables as $rawname => $definition) {
		
		if(!in_array($prefix.$rawname, $existing_tables)) { // create
			printf("Creating table: %s<br/>", $prefix.$rawname);
			$sql = qa_db_create_table_sql($rawname, $definition);
			do_query_sub($sql);
			if ($rawname=='userfields')
				do_query_sub(qa_db_default_userfields_sql());			
		}
		else { // update userid type
			printf("modifying userid column types for %s<br/>", $prefix.$rawname);
			if(isset($definition['userid']))
				do_query_sub('ALTER TABLE ^'.$rawname.' MODIFY userid '.$useridcoltype.($rawname!='posts'?' NOT NULL':''));
			if(isset($definition['lastuserid']))
				do_query_sub('ALTER TABLE ^'.$rawname.' MODIFY lastuserid '.$useridcoltype);
		}
			
		if(isset($constraints[$rawname])) { // add constraint
			printf("Adding constraint to: %s<br/>", $prefix.$rawname);
			do_query_sub('ALTER TABLE ^'.$rawname.' ADD '.$constraints[$rawname], false);
		}
		
	}
	
	// move users
	
	$users_query = $mysqli->query("SELECT users.ID AS userid, users.user_nicename AS handle, users.user_url AS website, users.user_email AS email, users.user_registered AS created, users.display_name AS name, mcap.meta_value AS caps, mdesc.meta_value AS about FROM ".$wp_prefix."users AS users LEFT JOIN ".$wp_prefix."usermeta AS mcap ON users.ID=mcap.user_id AND mcap.meta_key LIKE \"".$wp_prefix."capabilities\" LEFT JOIN ".$wp_prefix."usermeta AS mdesc ON users.ID=mdesc.user_id AND mdesc.meta_key LIKE \"description\" WHERE users.user_status = 0");

    if(!$users_query) {
		printf("Error message: %s<br/>", $mysqli->error);
		die();
	}


	$levels = array();
	$levels['administrator'] = 120;
	$levels['editor'] = 50;
	$levels['author'] = 20;

	while ($user = $users_query->fetch_assoc()) {
		
		// check if user already exists
		
		$mysqli->query('SELECT * FROM '.$prefix.'users WHERE userid='.$user['userid']);
		if($mysqli->affected_rows > 0) {
			printf("User already exists: %s<br/>", $user['name']);
			continue;
		}
		
		$salt = qa_random_alphanum(16);
		$pass = randomPassword(8);

		$level = 10;
		foreach ($levels as $name => $no) {
			if( strpos($user['caps'], $name) > 0) {
				$level = $no;
				break;
			}
		}

		// create user
		printf("Creating user: %s<br/>", $user['name']);

		do_query_sub("INSERT INTO ^users (userid, created, createip, email, passsalt, passcheck, level, handle, loggedin, loginip) ".
			"VALUES (".$user["userid"].", '".$user["created"]."', COALESCE(INET_ATON('127.0.0.1'), 0), '".$user["email"]."', '".$salt."', UNHEX('".sha1(substr($salt, 0, 8).$pass.substr($salt, 8))."'), ".(int)$level.", '".$mysqli->real_escape_string($user["handle"])."', NOW(), COALESCE(INET_ATON('127.0.0.1'), 0))");
		
		// add user info
		printf("Creating user info for %s<br/>", $user['name']);

		do_query_sub("INSERT INTO ^userprofile (userid,title,content) ".
			"VALUES ('".$user["userid"]."', 'about', '".$mysqli->real_escape_string($user["about"])."')");
		do_query_sub("INSERT INTO ^userprofile (userid,title,content) ".
			"VALUES ('".$user["userid"]."', 'location', '')");
		do_query_sub("INSERT INTO ^userprofile (userid,title,content) ".
			"VALUES ('".$user["userid"]."', 'name', '".$mysqli->real_escape_string($user["name"])."')");
		do_query_sub("INSERT INTO ^userprofile (userid,title,content) ".
			"VALUES ('".$user["userid"]."', 'website', '".$mysqli->real_escape_string($user["website"])."')");

		$to      = $user['email'];
		$subject = '['.$site_title.'] Password Change';
		$message = 'Dear '.$user['name'].',
		
Due to a recent database migration, your password for username "'.$user['handle'].'" at '.$site_title.' has been reset.  Please visit your profile via the forum site ('.$site_url.') and update your password as soon as possible.  Your new temporary password is:

'.$pass.'

Thanks and sorry for the inconvenience,

'.$site_title.' Management';

		$headers = 'From: '.$return_address. "\r\n" .
			'Reply-To: '.$return_address. "\r\n" .
			'X-Mailer: PHP/' . phpversion();

		printf("sending mail to %s<br/>", $user['email']);
		mail($to, $subject, $message, $headers);
	}

// functions

	function do_query_sub($query, $die = true)
/*
	Run $query after substituting ^
*/
	{
		global $mysqli, $prefix;
		$query = str_replace('^', $prefix, $query);
		$query = $mysqli->query($query);
		if($query)
			return $query;

		printf("Error message: %s<br/>", $mysqli->error);
		
		if($die)
			die();
	}

	function qa_random_alphanum($length)
/*
	Return a random alphanumeric string (base 36) of $length
*/
	{
		$string='';
		
		while (strlen($string)<$length)
			$string.=str_pad(base_convert(mt_rand(0, 46655), 10, 36), 3, '0', STR_PAD_LEFT);
			
		return substr($string, 0, $length);
	}

	function randomPassword($length) {
		$alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
		$pass = '';
		$alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
		for ($i = 0; $i < $length; $i++) {
			$n = rand(0, $alphaLength);
			$pass .= $alphabet[$n];
		}
		return $pass;
	}

	function qa_db_default_userfields_sql()
/*
	Return the SQL to create the default entries in the userfields table (before 1.3 these were hard-coded in PHP)
*/
	{

		define('QA_FIELD_FLAGS_MULTI_LINE', 1);
		define('QA_FIELD_FLAGS_LINK_URL', 2);

		$oldprofileflags=array(
			'name' => 0,
			'location' => 0,
			'website' => QA_FIELD_FLAGS_LINK_URL,
			'about' => QA_FIELD_FLAGS_MULTI_LINE,
		);
		
		$sql='INSERT INTO ^userfields (title, position, flags) VALUES '; // content column will be NULL, meaning use default from lang files
		
		$index=0;
		foreach ($oldprofileflags as $title => $flags)
			$sql.=($index ? ', ' : '')."('".$mysqli->real_escape_string($title)."', ".(++$index).", ".(int)@$oldprofileflags[$title].")";
				
		return $sql;
	}

	function qa_db_create_table_sql($rawname, $definition)
/*
	Return the SQL command to create a table with $rawname and $definition obtained from qa_db_table_definitions()
*/
	{
		$querycols='';
		foreach ($definition as $colname => $coldef)
			if (isset($coldef))
				$querycols.=(strlen($querycols) ? ', ' : '').(is_int($colname) ? $coldef : ($colname.' '.$coldef));
			
		return 'CREATE TABLE IF NOT EXISTS ^'.$rawname.' ('.$querycols.') ENGINE=InnoDB CHARSET=utf8';
	}

/*
	Omit PHP closing tag to help avoid accidental output
*/
