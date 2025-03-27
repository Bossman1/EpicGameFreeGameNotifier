<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            max-width: 700px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
            line-height: 1.6;
        }

        .email-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .email-header {
            background-color: #3498db;
            color: white;
            padding: 20px;
            text-align: center;
        }

        .section {
            border-bottom: 1px solid #ecf0f1;
            padding: 20px;
        }

        .section:last-child {
            border-bottom: none;
        }

        .section-image {
            max-width: 100%;
            height: auto;
            margin-bottom: 15px;
            border-radius: 4px;
        }

        .info-row {
            margin-bottom: 10px;
            display: flex;
        }

        .info-label {
            font-weight: bold;
            width: 100px;
            color: #2c3e50;
            margin-right: 15px;
        }

        .info-value {
            color: #34495e;
        }
    </style>
</head>
<body>
<div class="email-container">



    @foreach($collections as $collection)
        <div class="email-header">
            <h1>{{ $collection['game_title'] }}</h1>
        </div>
        <div class="section">

            @php
                $gameImages = json_decode($collection['game_images'], true);
                $imageToShow = $gameImages['Thumbnail'] ?? reset($gameImages);
            @endphp

            @if($imageToShow != '')
                <img src="{{ $imageToShow }}" alt="{{ $collection['game_title'] }}" class="section-image">
            @endif


            @php
                unset($collection['game_images']);
                unset($collection['game_effective_date']);
                unset($collection['game_id']);

                $gameDescription = $collection['game_description'];
                unset($collection['game_description']);
                $collection['game_description'] = $gameDescription;
            @endphp
            @foreach($collection as $key =>  $info)
                @php
                  if($key !='game_offer_start' && $key !='game_offer_end'){
                    $labels = explode('game_',$key);
                    $label = isset($labels[1]) ? ucfirst($labels[1]) :'';
                    $content = $info;
                  }else{
                    $labels = explode('game_',$key);

                    $label = str_replace("_"," ",$labels[1]);
                    $label = ucfirst($label);
                    $content = \Carbon\Carbon::parse($info)->format('F j, Y');
                  }
                @endphp
                <div class="info-row">
                    <div class="info-label">{{ $label }}:</div>
                    <div class="info-value">{{ $content }}</div>
                </div>
            @endforeach


        </div>
    @endforeach


</div>
</body>
</html>
