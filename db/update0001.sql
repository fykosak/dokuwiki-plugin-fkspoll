CREATE TABLE poll_question(
'question_id' INTEGER PRIMARY KEY AUTOINCREMENT,	 
'question' TEXT,	 
'type' INTEGER,
'valid_from' TEXT,	 
'valid_to' TEXT,
'new_answer' BOOLEAN,
'lang' TEXT,
'sectok' TEXT
);


CREATE TABLE poll_answer(
'answer_id' INTEGER PRIMARY KEY AUTOINCREMENT,
'question_id' INTEGER,
'answer' TEXT,
FOREIGN KEY(question_id) REFERENCES poll_question(question_id)
);

CREATE TABLE poll_response(
'response_id' INTEGER PRIMARY KEY AUTOINCREMENT,
'question_id' INTEGER,	 
'answer_id' INTEGER,	 
'users_id' INTEGER,	 
'remote_addr' TEXT,	 
'remote_host' TEXT,	 
'user_agent' TEXT,	 
'accept' TEXT,	 
'accept_language' TEXT,	 
'referer' TEXT,	 
'from' TEXT,	 
'cookie' TEXT,	 
'inserted' DATETIME,	
FOREIGN KEY(answer_id) REFERENCES poll_answer(answer_id),
FOREIGN KEY(question_id) REFERENCES poll_question(question_id)
);

 
 
