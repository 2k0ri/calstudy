CREATE TABLE calstudy_users (
	user_id INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
	name VARCHAR(127),
	email VARCHAR(255) NOT NULL UNIQUE,
	update_at TIMESTAMP NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
	created_at TIMESTAMP NOT NULL default 0,
	deleted_at DATETIME,
	INDEX (email, deleted_at)
) ENGINE = InnoDB;
CREATE TABLE calstudy_tasks (
	task_id INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
	update_at TIMESTAMP NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
	start_date TIMESTAMP,
	end_date TIMESTAMP,
	task_title VARCHAR(255),
	task_detail TEXT,
	created_at TIMESTAMP NOT NULL default 0,
	deleted_at DATETIME,
	INDEX (start_date, deleted_at)
) ENGINE = InnoDB;
CREATE TABLE calstudy_user_task (
	user_id INT UNSIGNED NOT NULL,
	task_id INT UNSIGNED NOT NULL,
	update_at TIMESTAMP NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
	created_at TIMESTAMP NOT NULL default 0,
	deleted_at DATETIME,
	INDEX (user_id, task_id),
	FOREIGN KEY (user_id) REFERENCES calstudy_users (user_id),
	FOREIGN KEY (task_id) REFERENCES calstudy_tasks (task_id)
) ENGINE = InnoDB;
