<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\RequestItems;

class TestEmail extends Command
{
    protected $signature = 'test:email';
    protected $description = 'Test RequestItems email sending';

    public function handle()
    {
        $this->info('Testing email sending...');
        
        try {
            $testItems = [
                [
                    'id' => 1,
                    'date' => now()->format('Y-m-d'),
                    'manufacturer' => 'Apple',
                    'model' => 'iPhone 13',
                    'colour' => 'Blue',
                    'issues' => 'None',
                    'imei' => '123456789012345',
                    'grade' => 'A',
                    'selling_price' => 500
                ]
            ];
            
            Mail::to('will@refreshmobile.ca')->send(new RequestItems(
                'Test User',
                'test@example.com',
                'Test Store',
                'This is a test email',
                $testItems
            ));
            
            $this->info('Email sent successfully!');
        } catch (\Exception $e) {
            $this->error('Failed to send email: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
        }
    }
}