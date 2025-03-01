<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Parsedown;

class MarketingEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $subject;
    public $content;
    public $name;
    public $user;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($subject, $content, $name, $user)
    {
        $this->subject = $subject;
        $this->content = str_replace('{{name}}', $name, $content);
        $this->name = $name;
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $parsedown = new Parsedown();
        $htmlContent = $parsedown->text($this->content); // Convert Markdown to HTML

        return $this->html($htmlContent)
            ->from($this->user->email)
            ->subject($this->subject)
            ->with([
                'content' => $htmlContent,
                'name' => $this->name
            ]);
    }
}
