<?php

namespace App\Http\Controllers;

use http\Header;
use Illuminate\Http\Request;
use phpDocumentor\Reflection\DocBlock\Tags\Method;
use phpDocumentor\Reflection\Types\Array_;

class MainController extends Controller
{
    private $authKey = 'e192342211f636138af60e94672c2866e40738a7';

    public function index()
    {
        $segmentsData = $this->apiRequest('https://www.strava.com/api/v3/segments/explore',
            ['Authorization : Bearer ' . $this->authKey],
            'GET', ['bounds' => '[40.816430, 28.132642, 41.274538, 29.744736]', 'activity_type' => 'riding']);
        $resultArray = array();
        $counter = 0;
        foreach ($segmentsData['segments'] as $val) {
            $data = $this->apiRequest('https://www.strava.com/api/v3/segments/' . $val['id'],
                ['Authorization : Bearer ' . $this->authKey],
                'GET');
            if ($data['city'] == 'Istanbul') {
                $resultArray[$counter] = $data;
                $counter++;
            }
        }
        $resultArray = $this->arraySorter($resultArray, 'athlete_count');
        return view('main', ['data' => $resultArray]);
    }

    public function getSegment($id)
    {
        $data = $this->apiRequest('https://www.strava.com/api/v3/segments/' . $id . '/leaderboard',
            ['Authorization : Bearer ' . $this->authKey],
            'GET', ['per_page' => 50]);
        $name = $this->apiRequest('https://www.strava.com/api/v3/segments/' . $id,
            ['Authorization : Bearer ' . $this->authKey],
            'GET', ['per_page' => 50]);
        $name = $name['name'];
        return view('leaderboard', ['data' => $data['entries'], 'name' => $name]);
    }

    public function getRiders()
    {
        $athlets = array();
        $segmentsData = $this->apiRequest('https://www.strava.com/api/v3/segments/explore',
            ['Authorization : Bearer ' . $this->authKey],
            'GET', ['bounds' => '[40.816430, 28.132642, 41.274538, 29.744736]', 'activity_type' => 'riding']);
        foreach ($segmentsData['segments'] as $val) {
            $data = $this->apiRequest('https://www.strava.com/api/v3/segments/' . $val['id'],
                ['Authorization : Bearer ' . $this->authKey],
                'GET');
            if ($data['city'] == 'Istanbul') {
                $temp = $this->apiRequest('https://www.strava.com/api/v3/segments/' . $val['id'] . '/leaderboard',
                    ['Authorization : Bearer ' . $this->authKey],
                    'GET', ['per_page' => 50]);
                foreach ($temp['entries'] as $val2) {
                    if (!isset($athlets[$val2['athlete_name']])) {
                        $athlets[$val2['athlete_name']] = 1;
                    } else {
                        $athlets[$val2['athlete_name']]++;
                    }
                }
            }
        }
        return view('riders',['data' => $athlets]);
    }

    private function apiRequest($url, $header, $method = 'GET', $params = [])
    {
        $path = storage_path() . '/strava_api_chace/' . md5($url . implode(":", $params));
        $day = 1;
        if (file_exists($path)) {
            if (filesize($path) > 0) {
                $day = $this->dateDifference(date('y-m-d', filemtime($path)), date('y-m-d', time()));
            }
        }
        //To optimize data using
        if ($day > 0) {
            $getParams = '';
            $curl = curl_init();
            $fileHandle = fopen($path, 'w');
            if ($method == 'GET') {
                foreach ($params as $key => $val) {
                    if ($getParams == '') {
                        $getParams .= '?' . urlencode($key) . '=' . urlencode($val);
                    } else {
                        $getParams .= '&' . urlencode($key) . '=' . urlencode($val);
                    }
                }

            } else if ($method == 'POST') {
                curl_setopt($curl, CURLOPT_POST, count($params));
                curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
            }
            curl_setopt($curl, CURLOPT_URL, $url . $getParams);
            curl_setopt($curl, CURLOPT_FILE, $fileHandle);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
            curl_exec($curl);
            curl_close($curl);
            fclose($fileHandle);
        }
        $size = filesize($path);

        if ($size > 0) {
            $fileHandle = fopen($path, 'r');
            $data = fread($fileHandle, $size);
            fclose($fileHandle);
            $data = json_decode($data, 1);
            if (isset($data['message'])) {
                unlink($path);
            }
        } else {
            $data['message'] = 'Connection Error';
        }
        return $data;
    }

    private function dateDifference($date_1, $date_2, $differenceFormat = '%a')
    {
        $datetime1 = date_create($date_1);
        $datetime2 = date_create($date_2);

        $interval = date_diff($datetime1, $datetime2);

        return $interval->format($differenceFormat);

    }

    private function arraySorter($array, $parameter)
    {
        do {
            $flag = false;
            for ($i = 0; $i < count($array); $i++) {
                if ($i > 0) {
                    if ($array[$i][$parameter] > $array[$i - 1][$parameter]) {
                        $flag = true;
                        $temp = $array[$i - 1];
                        $array[$i - 1] = $array[$i];
                        $array[$i] = $temp;
                    }
                }
            }
        } while ($flag);
        return $array;
    }
}
