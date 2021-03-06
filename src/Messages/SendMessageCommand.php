<?php

namespace Musonza\Chat\Messages;

class SendMessageCommand
{
    public $senderId;
    public $body;
    public $conversation;

    public function __construct($conversation, $body, $attachments, $senderId)
    {
        $this->conversation = $conversation;
        $this->body = $body;
		$this->attachments = $attachments;
        $this->senderId = $senderId;
    }
}