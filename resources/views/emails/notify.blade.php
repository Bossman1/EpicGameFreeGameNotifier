<!DOCTYPE html>
<html>
<head>
    <title>{{ $subject }}</title>
    <style>
        .font-size {
            font-size: 15px;
        }
    </style>
</head>
<body>
<h1>New Game is available on Epic Game Store!</h1>
<p>{{ $subject }}</p>
<ul class="font-size">
    @foreach($messageArray as $key =>  $data)

        @if($key == 'game_id')
            @continue
        @endif

            @if($key != 'game_offer_start' || $key != 'game_offer_end')
                @php
                    $dataname = explode('_',$key);
                @endphp
            @endif

        @if($key == 'game_images')
            @php
                $images = json_decode($data);
            @endphp
            <img src="{{ $images->Thumbnail }}" alt="" width="250">
        @else
            @if($key == 'game_effective_date')
                <li>{!!  isset($dataname[1]) ? '<strong>'.Str::ucfirst($dataname[1]) . '</strong>' . ' : ' . \Carbon\Carbon::parse($data)->format('d M Y, h:i A')  : ''  !!}</li>
                @elseif($key == 'game_offer_start' || $key == 'game_offer_end')
                    @php
                        $titleArray = explode('_',$key);
                    @endphp
                    <li>{!!  isset($titleArray[1]) ? '<strong>'.Str::ucfirst($dataname[1] .' '.$dataname[2]) . '</strong>' . ' : ' .\Carbon\Carbon::parse($data)->format('d F, Y, H:i') : ''  !!}</li>
                @else
                <li>{!!  isset($dataname[1]) ? '<strong>'.Str::ucfirst($dataname[1]) . '</strong>' . ' : ' .$data : ''  !!}</li>
            @endif
        @endif

    @endforeach
</ul>
</body>
</html>
