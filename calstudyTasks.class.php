<?php
/**
 * 予定にまつわるクラス
 * 日付をキーとする予定一覧、予定のCRUD処理を内包
 */
class CalstudyTasks
{
    const CREATE_QUERY = 'INSERT INTO calstudy_tasks (start_date, end_date, task_title, created_date, state) VALUES (:start_date, :end_date, :task)';
    const READ_QUERY = '';
    const UPDATE_QUERY = '';
    const DELETE_QUERY = '';

    private $mysqli;
    private $user_id;

    function __construct($email)
    {
        // MySQLに接続
        $this->mysqli = new mysqli(
            'localhost', // ホスト
            'calstudy', // ユーザー
            'passwd', // パスワード
            'calstudy' // データベース
        );
        $this->mysqli->set_charset('utf-8');

        // ユーザーIDを取得
        $this->user_id = $this->mysqli->query('SELECT user_id FROM calstudy_users WHERE email = \''.$email.'\'')->fetch_row();
        $this->user_id = $this->user_id[0];
    }

    function __descruct()
    {
        $this->mysqli->close();
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
        if ($day == 't') {
            $day = date('t', strtotime($year.$month.'01'));
        }
        return date('Y-m-d H:i:s', strtotime($yaer.$month.$day.$hour.$minute.$sec));
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
    public function create($year, $month, $date, $task_title, $task_details)
    {
        // プリペアドステートメント設定
        $stmt = $mysqli->prepare(CREATE_QUERY);

        // TIMESTAMP型に合わせて時刻を設定
        $start_date = $this->encodeTimestamp($year, $month, $date);

        // ステートメントの設定
        $stmt->bind_param(':user_id', $user_id);
        $stmt->bind_param(':start_date', $start_date);
        $stmt->bind_param(':end_date', $end_date);
        $stmt->bind_param(':task', $task);

        // 実行
        $stmt->execute();
    }

    /**
     * 予定の取得
     * @param  int $start_year  [description]
     * @param  int $start_month [description]
     * @param  int $end_year    [description]
     * @param  int $end_month   [description]
     * @return array
     */
    public function read($start_year, $start_month, $end_year, $end_month)
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
    public function readTask($task_id)
    {

    }

    public function update($task_id, $year, $month, $date, $task_title, $task_details)
    {

    }

    public function delete($task_id)
    {

    }
}

$calstudy_tasks = new CalstudyTasks('kori@aucfan.com');
// $calstudy_tasks->create(2014, 4, 16, 'DB実装', 'データベース実装');
var_dump($calstudy_tasks);
