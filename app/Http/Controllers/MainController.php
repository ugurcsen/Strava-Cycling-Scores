<?php

namespace App\Http\Controllers;

use http\Header;
use Illuminate\Http\Request;
use phpDocumentor\Reflection\DocBlock\Tags\Method;

class MainController extends Controller
{
    private $authKey = '5c166de3e898e690c10093dff4d4def51fc44c92';

    public function index()
    {
        $data = $this->apiRequest('https://www.strava.com/api/v3/segments/explore',
            ['Authorization : Bearer ' . $this->authKey],
            'GET', ['bounds' => '[40.816430, 28.132642, 41.274538, 29.744736]', 'activity' => 'riding']);
        return $data;
    }

    public function getSegment($id)
    {
        $data = $this->apiRequest('https://www.strava.com/api/v3/segments/' . $id,
            ['Authorization : Bearer ' . $this->authKey],
            'GET');
        return $data;
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
            if(isset($data['message'])){
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
}
