<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RequestItems extends Mailable
{
  use Queueable, SerializesModels;

  /**
   * Create a new message instance.
   *
   * @return void
   */
  public function __construct($name, $email, $store, $notes, $items, $total)
  {
    $this->name   = $name;
    $this->email  = $email;
    $this->store  = $store;
    $this->notes = $notes;
    $this->items  = $items;
    $this->total  = $total;
  }

  /**
   * Build the message.
   *
   * @return $this
   */
  public function build()
  {
    return $this->subject("Devices Requested")
      ->view("requestItems")
      ->with([
        'name'  => $this->name,
        'email' => $this->email,
        'store' => $this->store,
        'notes' => $this->notes,
        'items' => $this->items,
        'total' => $this->total,
      ]);
  }
}
