<?php
/**
 * 予定にまつわるクラス
 * 予定のCRUD処理を内包
 */
class CalstudyTasks
{
    // 予定登録クエリ
    const CREATE_REGISTER_QUERY = 'INSERT INTO calstudy_tasks (start_date, task_title, task_detail) VALUES (?, ?, ?)';
    // 予定マッピングクエリ
    const CREATE_MAP_QUERY = 'INSERT INTO calstudy_user_task (user_id, task_id) VALUES (?, LAST_INSERT_ID())';
    // ユーザー登録クエリ
    const CREATE_USER_QUERY = 'INSERT INTO calstudy_users (email, name) VALUES (?, ?)';
    // 予定読み込みクエリ
    // @TODO: INNER JOIN
    const READ_QUERY = 'SELECT * FROM calstudy_tasks WHERE (start_date BETWEEN ? AND ?) AND is_deleted = 0';
    // 予定更新クエリ
    const UPDATE_TASK_QUERY = 'UPDATE calstudy_tasks SET start_date = ?, end_date = ?, task_title = ?, task_detail = ? WHERE task_id = ?';
    const UPDATE_USER_QUERY = 'UPDATE calstudy_users SET name = ?, email = ? WHERE user_id = ?';
    // 予定削除クエリ
    // DELETE文ではなく、stateを更新する論理削除
    const DELETE_TASK_QUERY = 'UPDATE calstudy_tasks SET is_deleted = 1';
    const DELETE_USER_QUERY = 'UPDATE calstudy_users SET is_deleted = 1';

    private $mysqli;
    private $user_id;

    function __construct($email = null)
    {
        // MySQLに接続
        $this->mysqli = new mysqli(
            'localhost', // ホスト
            'calstudy', // ユーザー
            'passwd', // パスワード
            'calstudy' // データベース
        );
        // エラー処理
        if (mysqli_connect_errno()) {
            printf("Connect failed: %s\n", mysqli_connect_error());
            exit();
        }

        $this->mysqli->set_charset('utf-8');

        if (!empty($email)) {
            // ユーザーIDを取得
            $this->user_id = $this->mysqli->query('SELECT user_id FROM calstudy_users WHERE email = \''.$email.'\' AND is_deleted = 0')->fetch_row();
            $this->user_id = $this->user_id[0];
        }
    }

    function __descruct()
    {
        // mysqli接続を終了
        $this->mysqli->close();
    }

    /**
     * メールアドレスからユーザーを作成
     * @param  string $email [description]
     * @param  string $name [description]
     * @return int        ユーザーID
     */
    function createUser($email, $name)
    {
        $stmt = $this->mysqli->prepare(self::CREATE_USER_QUERY);
        $stmt->bind_param('ss', $email, $name);

        $stmt->execute();
        $this->user_id = $stmt->insert_id; // user_idメンバ変数にIDを格納
        $stmt->close();
        return $this->user_id;
    }

    /**
     * TIMESTAMP型に合うタイムスタンプを返す
     * @param  string $year  YYYY
     * @param  string $month mm
     * @param  string $day   dd 't'を指定した場合、月末を指定
     * @return string 'Y-m-d H:i:s'
     */
    function encodeTimestamp($year, $month, $day = '01', $hour = null, $minute = null, $sec = null)
    {
        $year = sprintf('%04d', $year);
        $month = sprintf('%02d', $month);

        if ($day == 't') {
            $day = date('t', strtotime($year.$month.'01'));
        }
        $day = sprintf('%02d', $day);

        return date('Y-m-d H:i:s', strtotime($year.$month.$day.$hour.$minute.$sec));
    }

    /**
     * 予定を作成
     * @param  int $year         [description]
     * @param  int $month        [description]
     * @param  int $date         [description]
     * @param  string $task_title   [description]
     * @param  string $task_details [description]
     * @return [type]               [description]
     */
    function createTask($year, $month, $date, $task_title, $task_detail)
    {
        // トランザクションのためオートコミットを無効化
        $this->mysqli->autocommit(FALSE);

        // 予定作成SQLのプリペアドステートメント設定
        $stmt = $this->mysqli->prepare(CalstudyTasks::CREATE_REGISTER_QUERY);
        // TIMESTAMP型に合わせて時刻を設定
        $start_date = $this->encodeTimestamp($year, $month, $date);

        // @debug
        var_dump($start_date, $task_title, $task_detail);
        echo '<br>';

        // プリペアドステートメント登録
        // @TODO: エラーを吐かれている
        $stmt->bind_param('sss', $start_date, $task_title, $task_detail);

        $stmt->execute(); // 実行
        $stmt->close(); // 予定作成SQLを開放
        $task_id = $stmt->insert_id; // REGISTERで登録した予定のtask_idを取得
        // エラーが返っている場合はロールバック
        if ($mysqli->sqlstate != '00000') {
            $mysqli->rollback();
        }

        // マッピングテーブル登録SQLのプリペアドステートメント設定
        $stmt = $this->mysqli->prepare(CalstudyTasks::CREATE_MAP_QUERY);
        // プリペアドステートメント登録
        $stmt->bind_param('i', $this->user_id);

        $stmt->execute();
        $stmt->close();
        // エラーが返っている場合はロールバック
        if ($mysqli->sqlstate != '00000') {
            $mysqli->rollback();
        }

        // コミット
        $this->mysqli->commit();
        $this->mysqli->autocommit(TRUE);

        return $task_id;
    }

    /**
     * 予定の取得
     * @param  int $start_year  [description]
     * @param  int $start_month [description]
     * @param  int $end_year    [description]
     * @param  int $end_month   [description]
     * @return array
     */
    function read($start_year, $start_month, $end_year, $end_month)
    {
        // TIMESTAMP型に合わせて時刻を設定
        $start_date = $this->encodeTimestamp($start_year, $start_month);
        $end_date = $this->encodeTimestamp($end_year, $end_month, 't');
    }

    /**
     * 特定の予定の詳細を取得
     * @param  int $task_id [description]
     * @return [type]          [description]
     */
    function readTask($task_id)
    {
        // @TODO
    }

    function update($task_id, $year, $month, $date, $task_title, $task_details)
    {
        // @TODO
    }

    function delete($task_id)
    {
        // @TODO
    }
}

$calstudy_tasks = new CalstudyTasks();
// @debug
$calstudy_tasks->createUser('esc13245@gmail.com', '2k0ri');
$calstudy_tasks->createTask(2014, 4, 16, 'DB実装', 'データベース実装');
var_dump($calstudy_tasks);
