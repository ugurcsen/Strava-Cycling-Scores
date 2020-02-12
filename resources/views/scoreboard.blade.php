<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Laravel</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet">

    <!-- Styles -->
    <style>/*
        html, body {
            background-color: #fff;
            color: #636b6f;
            font-family: 'Nunito', sans-serif;
            font-weight: 200;
            margin: 0;
        }

        .full-height {
        }

        .flex-center {
            align-items: center;
            display: flex;
            justify-content: center;
        }

        .position-ref {
            position: relative;
        }

        .top-right {
            position: absolute;
            right: 10px;
            top: 18px;
        }

        .content {
            text-align: center;
        }

        .title {
            font-size: 84px;
        }

        .links > a {
            color: #636b6f;
            padding: 0 25px;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: .1rem;
            text-decoration: none;
            text-transform: uppercase;
        }

        .m-b-md {
            margin-bottom: 30px;
        }*/
    </style>
</head>
<body>
<div class="flex-center position-ref full-height">
    <div class="content">
        <div class="title m-b-md">
            Riders Score Board
        </div>
        Formule<br>
        - [score] = ([total_distance]/[total_time])*(51-([total_rank]/([segment_count])))
        <ul>
            @foreach($data as $key => $val)
                <li>{{$val['athlete_name']}}
                    <ul>
                        <li>Score: {{$val['score']}}</li>
                        <li>Segment count: {{$val['counter']}}</li>
                        <li>Total rank: {{$val['total_rank']}}</li>
                        <li>Total distance: {{$val['total_distance']}}</li>
                        <li>Total time:{{$val['total_time']}}</li>
                        <li>Avg speed:{{($val['total_distance']/1000)/($val['total_time']/3600)}}km/h</li>
                    </ul>
                </li>
            @endforeach
        </ul>
    </div>
</div>
</body>
</html>
