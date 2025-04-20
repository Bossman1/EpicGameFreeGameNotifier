<?php

namespace App\Services;

class MessageService
{

    public static function generateTelegramMessageForJobsge($dataObject)
    {

        $message = "New Job:\n\n";
        foreach ($dataObject as $data) {
            $link = '<a href="' . $data['link'] . '" target="_blank"> Job Page </a>';
            $message .= "<b>Position:</b> " . (isset($data['position']) ? $data['position'] : 'N/A') . "\n" .
                "<b>Company:</b> " . (isset($data['company']) ? $data['company'] : 'N/A') . "\n" .
                "<b>Start Date:</b> " . (isset($data['start_date']) ? $data['start_date'] : 'N/A') . "\n" .
                "<b>End Date:</b> " . (isset($data['end_date']) ? $data['end_date'] : 'N/A') . "\n" .
                "<b>Job Url:</b>  {$link}\n\n";

        }
        return $message;

    }

    public static function generateTelegramMessageForEft($dataObject)
    {

        $message = "EFT Price change is detected:\n\n";
        foreach ($dataObject as $data) {
            foreach ($data as $key =>  $package) {
                $label  = str_replace('_',' ',ucfirst($key));
                $message .=  "$label : $package \n";
            }
            $message .= "\n\n";
        }
        return $message;

    }


    public static function generateSmsMessageForJobsge($dataObject)
    {
        $text = null;
        if (!empty($dataObject) && is_array($dataObject)) {
            $text .= "";
        }
        foreach ($dataObject as $message) {
            $text .= "Position: {$message['position']}\nJob Page: {$message['link']}\n\n";
        }
        return $text;
    }

    public static function generateSmsMessageForEpicGames($messageData): ?string
    {
        $test = null;
        if (!empty($messageData) && is_array($messageData)) {
            foreach ($messageData as $message) {
                $test .= "Title: {$message['game_title']} \n";
            }
        }
        return $test;
    }


}
