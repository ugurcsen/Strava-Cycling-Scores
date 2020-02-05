<?php

namespace App\Http\Controllers;

use http\Header;
use Illuminate\Http\Request;
use phpDocumentor\Reflection\DocBlock\Tags\Method;

class MainController extends Controller
{
    public function index()
    {
        $data = $this->apiRequest('https://www.strava.com/api/v3/segments/explore', ['Authorization : Bearer 4846ab5d43e6ed4babc0b3222b67913ecc232f85'], 'GET', ['bounds' => '[40.816430, 28.132642, 41.274538, 29.744736]', 'activity' => 'riding']);
        return $data;
    }

    private function apiRequest($url, $header, $method = 'GET', $params = [])
    {
        $path = storage_path('strava_api_chace') . "/" . md5($url . implode(":", $params));

        if (!file_exists($path)) {
            $diff = abs(time() - filectime($path));
            $years = floor($diff / (365 * 60 * 60 * 24));
            $months = floor(($diff - $years * 365 * 60 * 60 * 24)
                / (30 * 60 * 60 * 24));
            $days = floor(($diff - $years * 365 * 60 * 60 * 24 -
                    $months * 30 * 60 * 60 * 24) / (60 * 60 * 24));
            $hours = floor(($diff - $years * 365 * 60 * 60 * 24
                    - $months * 30 * 60 * 60 * 24 - $days * 60 * 60 * 24)
                / (60 * 60));
            //To optimize data using
            if($hours > 0) {
                $getParams = '';
                $curl = curl_init();
                $fileHandle = fopen($path, "w");
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
        }
        $fileHandle = fopen($path, "r");
        $size = filesize($path);
        $data = fread($fileHandle, $size);
        fclose($fileHandle);
        return $data;
    }
}
