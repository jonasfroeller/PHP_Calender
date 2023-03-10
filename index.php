<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./style.css?<?php echo time(); ?>">
    <link rel="stylesheet" href="./calender.css?<?php echo time(); ?>">
    <title>Kalender</title>
</head>

<body>
    <?php
    /* https://www.schattenbaum.net/php/kalender.php */
    setlocale(LC_TIME, "de_DE.utf8");

    $cal_date = time();
    $month_and_year = mb_convert_encoding((new DateTime())->setTimestamp($cal_date)->format('F Y'), 'HTML-ENTITIES', 'UTF-8'); /* utf8_decode(strftime("%B %Y", $cal_date)); => deprecated */
    $cal_total_days = date("t", $cal_date);
    $cal_start_timestamp = mktime(0, 0, 0, date("n", $cal_date), 1, date("Y", $cal_date));
    $cal_start_day = date("N", $cal_start_timestamp);
    $cal_end_day = date("N", mktime(0, 0, 0, date("n", $cal_date), $cal_total_days, date("Y", $cal_date)));

    function csvToJson($fname)
    { /* https://www.kodingmadesimple.com/2016/04/convert-csv-to-json-using-php.html */
        // open csv file
        if (!($file = fopen($fname . ".csv", 'r'))) { /* 03\/[01-9]{1,}\/23 */
            die("Can't open file...");
        }

        //read csv headers
        $key = fgetcsv($file, "1024", ",");

        // parse csv rows into array
        $arr = array();
        while (($row = fgetcsv($file, "1024", ",")) !== false) {
            $arr[] = array_combine($key, $row);
        }

        // release file handle
        fclose($file);

        function trimArrayValues($arr)
        {
            foreach ($arr as &$value) {
                if (is_array($value)) {
                    $value = trimArrayValues($value);
                } else if (is_string($value)) {
                    $value = trim($value);
                }
            }
            return $arr;
        }

        return trimArrayValues(json_decode(json_encode($arr), true));
    }

    /* $events[i]["Subject", "Start Date", "Start Time", "End Date", "End Time", "All day event", "Reminder on/off", "Reminder Date", "Reminder Time", "Categories", "Description", "Location", "Private"] */
    $events = csvToJson("2023");
    ?>

    <main>
        <table class="calender">
            <caption class="cal-title">
                <?php echo $month_and_year; ?>
            </caption>
            <thead>
                <tr>
                    <th>Mo</th>
                    <th>Di</th>
                    <th>Mi</th>
                    <th>Do</th>
                    <th>Fr</th>
                    <th>Sa</th>
                    <th>So</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $statistics = new stdClass();
                function statisticUpdate($object, $attributeName)
                {
                    if (property_exists($object, $attributeName)) {
                        $object->$attributeName++;
                    } else {
                        $object->$attributeName = 1;
                    }
                }

                function addEvent($eventDate, $cal_today_timestamp, $event, $statistics)
                {
                    $eventData = "";
                    if (date("dmY", $cal_today_timestamp) == date("dmY", $eventDate)) {
                        $eventData .= "<div class='activity' data-allday='" . $event['All day event'] . "' data-reminder='" . $event['Reminder on/off'] . "' data-reminderDateTime='" . $event['Reminder Date'] . " " . $event['Reminder Time'] . "' data-categories='" . $event['Categories'] . "' data-private='" . $event['Private'] . "'>" .
                            "<strong>" . $event["Subject"] . "</strong>" . "<br>" . $event["Start Time"] . "-" . $event["End Time"] . ($event["Description"] != "" ? "<br><em>" . $event["Description"] . "</em>" : "") . ($event["Location"] != "" ? "<br>(" . $event["Location"] . ")" : "") .  "</div>";
                        statisticUpdate($statistics, $event["Subject"]);
                    }
                    return $eventData;
                }

                for ($i = 1; $i <= $cal_total_days + ($cal_start_day - 1) + (7 - $cal_end_day); $i++) {
                    $cal_day = $i - $cal_start_day;
                    $cal_today_timestamp = strtotime($cal_day . " day", $cal_start_timestamp);
                    $cal_today_day = date("j", $cal_today_timestamp);

                    if (date("N", $cal_today_timestamp) == 1) {
                        echo "<tr>\n";
                    }

                    $eventData = "";
                    foreach ($events as $event) {
                        $eventData .= addEvent(strtotime($event["Start Date"]), $cal_today_timestamp, $event, $statistics);
                    }

                    if (date("dmY", $cal_date) == date("dmY", $cal_today_timestamp)) {
                        echo "<td id=\"todays-day\">", $cal_today_day . "<br>" . $eventData, "</td>\n";
                    } else if ($cal_day >= 0 && $cal_day < $cal_total_days) {
                        echo "<td class=\"cal-day\">", $cal_today_day . "<br>" . $eventData, "</td>\n";
                    } else {
                        echo "<td class=\"pre-sub-cal-day\">", $cal_today_day . "<br>" . $eventData, "</td>\n";
                    }

                    if (date("N", $cal_today_timestamp) == 7) {
                        echo "</tr>\n";
                    }
                }
                ?>
            </tbody>
        </table>
        <section id="statistics">
            <h1>Statistics</h1>

            <?php
            echo "<ul>";
            $properties = get_object_vars($statistics);
            foreach ($properties as $propertyName => $propertyValue) {
                echo "<li>" .  $propertyName . ": " . $propertyValue . "</li>";
            }
            echo "</ul>";
            ?>
        </section>
    </main>

</body>

</html>