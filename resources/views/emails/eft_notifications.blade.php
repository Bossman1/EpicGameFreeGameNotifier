<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        .email-container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #333;
            text-align: center;
            font-size: 24px;
            margin-bottom: 20px;
        }

        .section {
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .item {
            margin-bottom: 8px;
            padding: 8px;
            background-color: #ecf0f1;
            border-radius: 5px;
        }

        .item-key {
            font-weight: bold;
            color: #2c3e50;
        }

        .item-value {
            color: #7f8c8d;
        }

        @media only screen and (max-width: 600px) {
            .email-container {
                padding: 10px;
            }

            h1 {
                font-size: 20px;
            }

            .section-title {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
<div class="email-container">
    <h1>{{ $subject }}</h1>


    @foreach($collections as   $collection)
        <div class="section">
            <div class="section-title">Section {{ $loop->iteration }}</div>
            @foreach($collection as $key =>  $packageObject)
                    <div class="item">
                        <span class="item-key">{{ str_replace('_',' ',ucfirst($key)) }}:</span>
                        <span class="item-value"> {{$packageObject}}</span>
                    </div>
            @endforeach
        </div>
    @endforeach


</div>
</body>
</html>
