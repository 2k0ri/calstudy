CREATE TABLE calstudy_users (
	user_id INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
	name VARCHAR(127),
	email VARCHAR(255) NOT NULL UNIQUE,
	created_date TIMESTAMP, -- 初期化時にNULL→現在時刻、以降はUPDATEしない
	state ENUM('created', 'updated', 'deleted') NOT NULL DEFAULT 'created',
	state_updated_date TIMESTAMP, -- state更新時にNULL→現在時刻をUPDATE
	INDEX (email, state)
) ENGINE = InnoDB;
CREATE TABLE calstudy_tasks (
	task_id INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
	start_date TIMESTAMP,
	end_date TIMESTAMP,
	task_title VARCHAR(255),
	task_detail TEXT,
	created_date TIMESTAMP, -- 初期化時にNULL→現在時刻、以降はUPDATEしない
	state ENUM('created', 'updated', 'finished', 'deleted') NOT NULL DEFAULT 'created',
	state_updated_date TIMESTAMP, -- state更新時にNULL→現在時刻をUPDATE
	INDEX (start_date, state)
) ENGINE = InnoDB;
CREATE TABLE calstudy_user_task (
	user_id INT UNSIGNED NOT NULL,
	task_id INT UNSIGNED NOT NULL,
	INDEX (user_id, task_id)
) ENGINE = InnoDB;
-- CREATE TABLE calstudy_task_details (
-- 	task_id INT UNSIGNED NOT NULL PRIMARY KEY,
-- 	detail TEXT
-- ) ENGINE = InnoDB;
