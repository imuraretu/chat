<?php

namespace Musonza\Chat\Conversations;

use Eloquent;

class ConversationProfile extends Eloquent
{
    protected $table = 'conversation_profile';

    public function conversation()
    {
        return $this->belongsTo('Musonza\Chat\Conversations\Conversation', 'conversation_id');
    }
}
