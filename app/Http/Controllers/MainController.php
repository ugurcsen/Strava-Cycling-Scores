<?php

namespace App\Http\Controllers;

use http\Header;
use Illuminate\Http\Request;
use phpDocumentor\Reflection\DocBlock\Tags\Method;
use phpDocumentor\Reflection\Types\Array_;

class MainController extends Controller
{
    private $baseURL = 'https://www.strava.com/api/v3';
    private $clientID = '43263';
    private $clientSecret = 'd19de98cd7ea06efff7c4b2f375c64d7098cadc1';

    public function index()
    {
        if (empty(session('stravaAuthKey'))) {
            return $this->stravaAuth();
        }

        $segmentsData = $this->apiRequest($this->baseURL . '/segments/explore',
            ['Authorization : Bearer ' . session('stravaAuthKey')],
            'GET', ['bounds' => '[40.816430, 28.132642, 41.274538, 29.744736]', 'activity_type' => 'riding']);
        if (isset($segmentsData['message'])) {
            session(['stravaAuthKey' => '']);
            return $segmentsData['message'];
        }
        $resultArray = array();
        $counter = 0;
        foreach ($segmentsData['segments'] as $val) {
            $data = $this->apiRequest($this->baseURL . '/segments/' . $val['id'],
                ['Authorization : Bearer ' . session('stravaAuthKey')],
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
        if (empty(session('stravaAuthKey'))) {
            return $this->stravaAuth();
        }
        $data = $this->apiRequest($this->baseURL . '/segments/' . $id . '/leaderboard',
            ['Authorization : Bearer ' . session('stravaAuthKey')],
            'GET', ['per_page' => 50]);
        if (isset($data['message'])) {
            session(['stravaAuthKey' => '']);
            return $data['message'];
        }
        $name = $this->apiRequest($this->baseURL . '/segments/' . $id,
            ['Authorization : Bearer ' . session('stravaAuthKey')],
            'GET', []);
        $name = $name['name'];
        return view('leaderboard', ['data' => $data['entries'], 'name' => $name]);
    }

    public function getRiders()
    {
        if (empty(session('stravaAuthKey'))) {
            return $this->stravaAuth();
        }
        $athlets = array();
        $segmentsData = $this->apiRequest($this->baseURL . '/segments/explore',
            ['Authorization : Bearer ' . session('stravaAuthKey')],
            'GET', ['bounds' => '[40.816430, 28.132642, 41.274538, 29.744736]', 'activity_type' => 'riding']);
        if (isset($segmentsData['message'])) {
            session(['stravaAuthKey' => '']);
            return $segmentsData['message'];
        }
        $counter = 0;
        foreach ($segmentsData['segments'] as $val) {
            $data = $this->apiRequest($this->baseURL . '/segments/' . $val['id'],
                ['Authorization : Bearer ' . session('stravaAuthKey')],
                'GET');
            if ($data['city'] == 'Istanbul') {
                $temp = $this->apiRequest($this->baseURL . '/segments/' . $val['id'] . '/leaderboard',
                    ['Authorization : Bearer ' . session('stravaAuthKey')],
                    'GET', ['per_page' => 50]);
                foreach ($temp['entries'] as $val2) {
                    if (!isset($athletsName[$val2['athlete_name']])) {
                        $athletsName[$val2['athlete_name']]['id'] = $counter;
                        $athlets[$athletsName[$val2['athlete_name']]['id']]['counter'] = 1;
                        $athlets[$athletsName[$val2['athlete_name']]['id']]['athlete_name'] = $val2['athlete_name'];
                        $athlets[$athletsName[$val2['athlete_name']]['id']]['total_distance'] = $data['distance'];
                        $athlets[$athletsName[$val2['athlete_name']]['id']]['total_time'] = $val2['elapsed_time'];
                        $counter++;
                    } else {
                        $athlets[$athletsName[$val2['athlete_name']]['id']]['counter']++;
                        $athlets[$athletsName[$val2['athlete_name']]['id']]['total_distance'] += $data['distance'];
                        $athlets[$athletsName[$val2['athlete_name']]['id']]['total_time'] += $val2['elapsed_time'];
                    }
                }
            }
        }
        do {
            $flag = false;
            for ($i = 0; $i < count($athlets); $i++) {
                if ($i > 0) {
                    if ($athlets[$i]['counter'] > $athlets[$i - 1]['counter']) {
                        $flag = true;
                        $temp = $athlets[$i - 1];
                        $athlets[$i - 1] = $athlets[$i];
                        $athlets[$i] = $temp;
                    }
                }
            }
        } while ($flag);
        foreach ($athlets as $key => $val) {
            if ($athlets[$key]['counter'] < 2) {
                unset($athlets[$key]);
            }
        }
        return view('riders', ['data' => $athlets]);
    }

    public function getScoreBoard()
    {
        if (empty(session('stravaAuthKey'))) {
            return $this->stravaAuth();
        }
        $athlets = array();
        $segmentsData = $this->apiRequest($this->baseURL . '/segments/explore',
            ['Authorization : Bearer ' . session('stravaAuthKey')],
            'GET', ['bounds' => '[40.816430, 28.132642, 41.274538, 29.744736]', 'activity_type' => 'riding']);
        if (isset($segmentsData['message'])) {
            session(['stravaAuthKey' => '']);
            return $segmentsData['message'];
        }
        $counter = 0;
        foreach ($segmentsData['segments'] as $val) {
            $data = $this->apiRequest($this->baseURL . '/segments/' . $val['id'],
                ['Authorization : Bearer ' . session('stravaAuthKey')],
                'GET');
            if ($data['city'] == 'Istanbul') {
                $temp = $this->apiRequest($this->baseURL . '/segments/' . $val['id'] . '/leaderboard',
                    ['Authorization : Bearer ' . session('stravaAuthKey')],
                    'GET', ['per_page' => 50]);
                foreach ($temp['entries'] as $val2) {
                    if (!isset($athletsName[$val2['athlete_name']])) {
                        $athletsName[$val2['athlete_name']]['id'] = $counter;
                        $athlets[$athletsName[$val2['athlete_name']]['id']]['counter'] = 1;
                        $athlets[$athletsName[$val2['athlete_name']]['id']]['athlete_name'] = $val2['athlete_name'];
                        $athlets[$athletsName[$val2['athlete_name']]['id']]['total_distance'] = $data['distance'];
                        $athlets[$athletsName[$val2['athlete_name']]['id']]['total_time'] = $val2['elapsed_time'];
                        $athlets[$athletsName[$val2['athlete_name']]['id']]['total_rank'] = $val2['rank'];
                        $counter++;
                    } else {
                        $athlets[$athletsName[$val2['athlete_name']]['id']]['counter']++;
                        $athlets[$athletsName[$val2['athlete_name']]['id']]['total_distance'] += $data['distance'];
                        $athlets[$athletsName[$val2['athlete_name']]['id']]['total_time'] += $val2['elapsed_time'];
                        $athlets[$athletsName[$val2['athlete_name']]['id']]['total_rank'] += $val2['rank'];
                    }
                }
            }
        }
        do {
            $flag = false;
            for ($i = 0; $i < count($athlets); $i++) {
                $athlets[$i]['score'] = ($athlets[$i]['total_distance']/$athlets[$i]['total_time'])*(51-($athlets[$i]['total_rank']/($athlets[$i]['counter'])));
                if ($i > 0) {
                    if ($athlets[$i]['score'] > $athlets[$i - 1]['score']) {
                        $flag = true;
                        $temp = $athlets[$i - 1];
                        $athlets[$i - 1] = $athlets[$i];
                        $athlets[$i] = $temp;
                    }
                }
            }
        } while ($flag);
        return view('scoreboard', ['data' => $athlets]);
    }

    public function getStravaToken(Request $request)
    {
        if (empty($request->code)) {
            return $this->stravaAuth();
        }
        $path = storage_path() . '/strava_api_chace/auth457keegr4y431';
        $url = 'https://www.strava.com/oauth/token';
        $params = [
            'client_id' => $this->clientID,
            'client_secret' => $this->clientSecret,
            'code' => $request->code,
            'grant_type' => 'authorization_code'
        ];
        $curl = curl_init();
        $fileHandle = fopen($path, 'w');
        curl_setopt($curl, CURLOPT_POST, count($params));
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_FILE, $fileHandle);
        curl_exec($curl);
        curl_close($curl);
        fclose($fileHandle);
        $size = filesize($path);

        if ($size > 0) {
            $fileHandle = fopen($path, 'r');
            $data = fread($fileHandle, $size);
            fclose($fileHandle);
            $data = json_decode($data, 1);
            session(['stravaAuthKey' => $data['access_token']]);
        }
        return redirect('/');
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

    private function stravaAuth()
    {
        return redirect('http://www.strava.com/oauth/authorize?client_id=' . $this->clientID . '&response_type=code&redirect_uri=' . getenv('APP_URL') . ':8000/exchange_token&approval_prompt=force&scope=read');
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
