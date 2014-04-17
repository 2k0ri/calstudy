<?php
// タイムゾーン、ロケールの設定
setlocale(LC_TIME, 'ja_JP.utf8');
date_default_timezone_set('Asia/Tokyo');
// GoogleカレンダーURL
const GCAL_BASEURL = 'http://www.google.com/calendar/feeds/%s/public/full-noattendees?start-min=%s&start-max=%s&max-results=%d&alt=json';
const GCAL_HOLIDAY_ADDRESS = 'outid3el0qkcrsuf89fltf7a4qbacgt9@import.calendar.google.com'; // 'japanese@holiday.calendar.google.com'
// オクトピRSS
const AUC_TOPIC_RSS = 'http://aucfan.com/article/feed/';
// 今日の年月日
$now = time();
$today = getdate($now);
// 中心の月、パラメータで渡されていたらその月
if (isset($_GET['year'])) {
    $year = $_GET['year'];
}
if (isset($_GET['month'])) {
    $month = sprintf('%02d', $_GET['month']);
}
// 整数が代入されているか/1970年以降か確認、なければ現在の年月を代入
if (!ctype_digit($year) || strlen((int) $year) != 4) {
    $year = date('Y', $now);
}
if (!ctype_digit($month)) {
    $month = date('m', $now);
}
// 中心の月のtime()表記
$target_time = strtotime($year.$month.'01');
// 先月
$last = array(
    'year' => date('Y', strtotime('last month', $target_time)),
    'month' => date('m', strtotime('last month', $target_time))
);
// 来月
$next = array(
    'year' => date('Y', strtotime('next month', $target_time)),
    'month' => date('m', strtotime('next month', $target_time))
);

// 週表記
$week_letters = array('日','月','火','水','木','金','土');

// 祝日３ヶ月分
$holidays = getHolidays($last['year'], $last['month'], $next['year'], $next['month']);
// オクトピ
$auc_topics = getAucTopics();

// 先月、今月、来月分のカレンダーの配列
$calendars = array(
    buildCalendar($last['year'], $last['month']),
    buildCalendar($year, $month),
    buildCalendar($next['year'], $next['month']),
);

/**
 * 引数をもとにyear, month, calendar連想配列を格納した配列を返す
 * @param  int $year YYYY形式の数字
 * @param  int $month MM形式の数字
 * @return array 'year' => YYYY, 'month' => MM, 'calendar' => array[4-6][6]
 */
function buildCalendar($year, $month)
{
    // 桁数整形
    $year = sprintf('%04d', $year);
    $month = sprintf('%02d', $month);

    //該当月初日の曜日
    $start_weekday = date('w', strtotime($year.$month.'01'));

    //該当月の日数
    $end_date = date('t', strtotime($year.$month.'01'));

    // カレンダーを格納した連想配列
    // eg. $calendar[0][] : 日曜の配列
    $week_index = 0;
    $current_week = $start_weekday;
    for ($current_date=1; $current_date <= $end_date; $current_date++) {
        // カレンダー連想配列に日付を代入
        $calendar[$week_index][$current_week++] = $current_date;
        // 土曜日(6)の場合、次の列へ
        if ($current_week > 6) {
            $week_index += 1;
            $current_week = 0;
        }
    }
    // １日より手前の枠に空白を入れる
    for ($i=$start_weekday-1; $i >= 0; $i--) {
        $calendar[0][$i] = '';
    }

    return array(
        'year' => $year,
        'month' => $month,
        'calendar' => $calendar
    );
}

/**
 * 曜日・日の情報を取得、class属性を返す
 * @param  array $calendarAry
 * @param  int $weekday
 * @param  int $date
 * @return string
 */
function dayInfo($calendarAry, $weekday, $date = null)
{
    global $today, $holidays;
    $year = $calendarAry['year'];
    $month = $calendarAry['month'];

    $class = array();

    // 日付がある場合は桁数整形、年月日をクラスに追加
    if (isset($date)) {
        $date = sprintf('%02d', $date);
        array_push($class, $year.'-'.$month.'-'.$date);
    }

    // 曜日判定
    switch ($weekday) {
        case 0: // 日曜
            array_push($class, 'sunday');
            break;
        case 6: // 土曜
            array_push($class, 'saturday');
            break;
    }
    // 今日の判定
    if (isset($date) && $today['year'] == $year && $today['mon'] == $month && $today['mday'] == ltrim($date, 0)) {
        array_push($class, 'today');
    }
    // 祝日判定
    if (isset($date) && isset($holidays[$year.'-'.$month.'-'.$date])) {
        array_push($class, 'holiday');
    }

    // 空の場合は何も返さない
    if (empty($class)) {
        return;
    }

    return implode(' ', $class);
}

/**
 * YYYYとMMを引数に祝日情報を取得、日付(YYYY-MM-DD)をキーとする連想配列を返す
 * @param  int    $start_year YYYY
 * @param  int    $start_month MM
 * @param  int    $end_year = null YYYY 終了年
 * @param  int    $end_month = null MM 終了月
 * @return array $holidays['2014-04-08']:string
 */
function getHolidays($start_year, $start_month, $end_year = null, $end_month = null)
{
    $date_prefix = $start_year.'-'.$start_month.'-';
    // $end_year, $end_monthがある場合は終了日時を変更
    if (isset($end_year) && isset($end_month)) {
        $end_date_prefix = $end_year.'-'.$end_month.'-';
    } else {
        $end_date_prefix = $date_prefix;
    }
    // 終了月の日数
    $end_date = date('t', strtotime($end_date_prefix.'01'));

    $url = sprintf(
        GCAL_BASEURL,
        GCAL_HOLIDAY_ADDRESS,
        $date_prefix.'01', // 月初
        $end_date_prefix.$end_date, // 月末
        30 // 最大取得数
    );

    // JSON取得
    if ($results = file_get_contents($url)) {
        $results = json_decode($results, true); // 連想配列で格納
        // 空っぽの時は終了
        if (empty($results['feed']['entry'])) {
            return;
        }
        $holidays = array();
        foreach ($results['feed']['entry'] as $val) {
            $date = $val['gd$when'][0]['startTime']; // 日付取得
            $title = explode(' / ', $val['title']['$t']); // 祝日取得、分割
            $title = $title[0]; // 日本語部分のみ
            $holidays[$date] = $title;
        }

        return $holidays;
    }
}

/**
 * オクトピのRSSから日付とタイトルを取得、日付(YYYY-MM-DD)をキーとする連想配列を渡す
 * @return array $feeds['2014-04-09']:array['title', 'link']
 */
function getAucTopics()
{
    $xml = simplexml_load_file(AUC_TOPIC_RSS)->channel->item; // SimpleXMLオブジェクトとして取得
    if (empty($xml)) {
        return;
    }
    $feeds = array();
    foreach ($xml as $item) {
        $date = (string) $item->pubDate;
        $date = date('Y-m-d', strtotime($date)); // YYYY-mm-dd 形式に変換

        $title = (string) $item->title;
        $link = (string) $item->link;

        $feeds[$date]['title'] = $title;
        $feeds[$date]['link'] = $link;
    }

    return $feeds;
}
/**
 * 指定文字数以上の文字列を省略して返す
 * @param  string  $str 元の文字列
 * @param  integer $len 上限文字数(マルチバイト)
 * @return string       省略された文字列
 */
function shortenStr($str, $len = 20)
{
    if (mb_strlen($str) <= $len) {
        return $str;
    } else {
        return mb_substr($str, 0, $len).'...';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <nav class="nav">
            <a href="<?php echo '?year='.$last['year'].'&month='.$last['month'] ?>" class="last">先月</a>
            <a href="/calstudy/" class="this_month">今月</a>
            <a href="<?php echo '?year='.$next['year'].'&month='.$next['month'] ?>" class="next">来月</a>

            <form action="" id="combo">
                <input type="text" size="4" value="<?php echo $year ?>" maxlength="4" name="year">
                <select name="month">
                    <?php for ($i_month=1; $i_month <= 12; $i_month++) : ?>
                        <option value="<?php echo sprintf('%02d', $i_month) ?>"<?php if($i_month == $month) echo ' selected' ?>><?php echo $i_month ?></option>
                    <?php endfor ?>
                </select>
                <button type="submit">更新</button>
            </form>
        </nav>
        <div class="calendar-container">
        <?php foreach ($calendars as $current_calendar) : ?>
            <table class="calendar<?php echo $current_calendar['month'] == $month ? ' main' : '' ?>">
                <caption><?php echo $current_calendar['year'] ?>年 <?php echo ltrim($current_calendar['month'], 0) ?>月</caption>
                <thead>
                    <tr>
                        <?php for ($i_weekday=0; $i_weekday <= 6; $i_weekday++) : ?>
                            <th class="<?php echo dayInfo($current_calendar, $i_weekday) ?>"><?php echo $week_letters[$i_weekday] ?></th>
                        <?php endfor ?>
                    </tr>
                </thead>
                <tbody>
                <?php for ($i_weekindex=0; $i_weekindex < count($current_calendar['calendar']); $i_weekindex++) : ?>
                    <tr>
                    <?php for ($j_weekdaynum=0; $j_weekdaynum <= 6; $j_weekdaynum++) :
                    $the_day = $current_calendar['calendar'][$i_weekindex][$j_weekdaynum];
                    $the_day_format = $current_calendar['year'].'-'.$current_calendar['month'].'-'.sprintf('%02d', $the_day); ?>
                        <td class="<?php echo dayInfo($current_calendar, $j_weekdaynum, $the_day) ?>">
                            <span class="day"><?php echo $the_day ?></span>
                            <div class="details">
                                <?php if (isset($holidays[$the_day_format])) {
                                    echo $holidays[$the_day_format];
                                } ?>
                                <?php if (isset($auc_topics[$the_day_format])) : ?>
                                    <a class="feed" href="<?php echo $auc_topics[$the_day_format]['link'] ?>" target="_blank" title="<?php echo $auc_topics[$the_day_format]['title'] ?>"><?php echo shortenStr($auc_topics[$the_day_format]['title']) ?></a>
                                <?php endif ?>
                            </div>
                        </td>
                    <?php endfor ?>
                    </tr>
                <?php endfor ?>
                </tbody>
            </table>
        <?php endforeach ?>
        </div>
    </div>
</body>
</html>
