<?php

namespace App\Services;

class MessageService
{

    public static function generateTelegramMessage($dataObject)
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




}
