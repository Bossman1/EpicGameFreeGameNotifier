<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class WhatssapCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {


        $phoneNumberId = '687039077817260';
        $token = 'EAAbbXYw3TD0BOz683RNaiomkXRMu5Ml6u4ZCZCrsuv4gMx9KK9oetwk1k8XTIuvExVm0FTb0vg9CgxGgnBIh6p1ouTNlbZBvWO4z6kwR3bbwzFhKzcJ7MA4F3ujZAFWIhncDCzbZA2G68Sx23Rra6vwZCN8b4YuGQhvrYvb41sajcUhuPhC1VfkUmJseXKng279dxl0qxG6ZC4PmelHe2h9RuUwp8I7';
        $recipientPhone = '995551315320'; // Must be in international format
        $templateName = 'activation_code_1'; // Use your approved template name
        $code = rand(10000, 99999);

        $response = Http::withToken($token)->post("https://graph.facebook.com/v22.0/{$phoneNumberId}/messages", [
            'messaging_product' => 'whatsapp',
            'to' => $recipientPhone,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => 'en_US'
                ],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => [
                            [
                                'type' => 'text',
                                'text' => $code
                            ]
                        ]
                    ],
                    [
                        'type' => 'button',
                        'sub_type' => 'url',
                        'index' => 0,
                        'parameters' => [
                            [
                                'type' => 'text',
                                'text' => $code
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $this->info("Status Code: " . $response->status());

        if ($response->successful()) {
            $this->info("Message sent successfully!");
        } else {
            $this->error("Failed to send message:");
            $this->line(json_encode($response->json(), JSON_PRETTY_PRINT));
        }

        return 0; // success
    }
}
