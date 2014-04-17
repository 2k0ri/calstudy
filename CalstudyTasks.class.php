<?php
/**
 * 予定にまつわるクラス
 * 予定のCRUD処理を内包
 */
class CalstudyTasks
{
    // 予定登録クエリ
    const CREATE_QUERY_REGISTER = 'INSERT INTO calstudy_tasks (start_date, task_title, task_detail) VALUES (?, ?, ?)';
    // 予定マッピングクエリ
    const CREATE_QUERY_MAP = 'INSERT INTO calstudy_user_task (user_id, task_id) VALUES (?, ?)';
    // ユーザー登録クエリ
    const CREATE_USER = 'INSERT INTO calstudy_users (email, name) VALUES (?, ?)';
    // 予定読み込みクエリ
    // @TODO: INNER JOIN
    const READ_QUERY = 'SELECT * FROM calstudy_tasks WHERE (start_date BETWEEN ? AND ?) AND state != \'deleted\'';
    // @TODO: クエリ書き込み
    const UPDATE_QUERY = '';
    // 予定削除クエリ
    // DELETE文ではなく、stateを更新する論理削除
    const DELETE_QUERY = '';

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
        $this->mysqli->set_charset('utf-8');

        if (!empty($email)) {
            // ユーザーIDを取得
            $this->user_id = $this->mysqli->query('SELECT user_id FROM calstudy_users WHERE email = \''.$email.'\' AND state != \'deleted\'')->fetch_row();
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
     * クラスメソッドとしての使用(CalstudyTasks::createUser())を想定
     * @param  string $email [description]
     * @param  string $name [description]
     * @return int        ユーザーID
     */
    static function createUser($email, $name)
    {
        $stmt = $this->mysqli->prepare(CREATE_USER);

        $stmt->bind_param('ss', $email, $name);

        $stmt->execute();
        $stmt->close();
        return $stmt->insert_id;
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
    function create($year, $month, $date, $task_title, $task_detail)
    {
        // トランザクションのためオートコミットを無効化
        $this->mysqli->autocommit(FALSE);

        // 予定作成SQLのプリペアドステートメント設定
        $stmt = $this->mysqli->prepare(CREATE_QUERY_REGISTER);
        // TIMESTAMP型に合わせて時刻を設定
        $start_date = $this->encodeTimestamp($year, $month, $date);
        var_dump($start_date);
        echo '<br>';
        // プリペアドステートメント登録
        // @TODO: エラーを吐かれている
        $stmt->bind_param('sss', $start_date, $task_title, $task_detail);

        $stmt->execute(); // 実行
        $task_id = $stmt->insert_id; // REGISTERで登録した予定のtask_idを取得
        $stmt->close(); // 予定作成SQLを開放

        // マッピングテーブル登録SQLのプリペアドステートメント設定
        $stmt = $this->mysqli->prepare(CREATE_QUERY_MAP);
        // プリペアドステートメント登録
        $stmt->bind_param('ii', $this->user_id, $task_id);

        $stmt->execute();
        $stmt->close();

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

$calstudy_tasks = new CalstudyTasks('kori@aucfan.com');
$calstudy_tasks->create(2014, 4, 16, 'DB実装', 'データベース実装');
var_dump($calstudy_tasks);
