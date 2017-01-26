<?php

namespace Musonza\Chat\Notifications;

use Eloquent;
use Illuminate\Database\Eloquent\SoftDeletes;
use Musonza\Chat\Chat;
use Musonza\Chat\Conversations\Conversation;
use Musonza\Chat\Messages\Message;

class MessageNotification extends Eloquent
{
    use SoftDeletes;

    protected $fillable = ['profile_id', 'message_id', 'conversation_id'];

    protected $table = 'message_notification';

    protected $dates = ['deleted_at'];

    public function sender()
    {
        return $this->belongsTo(Chat::profileModel(), 'profile_id');
    }

    public function message()
    {
        return $this->belongsTo('Musonza\Chat\Messages\Message', 'message_id');
    }

    /**
     * Creates a new notification
     *
     * @param      Message       $message
     * @param      Conversation  $conversation
     */
    public static function make(Message $message, Conversation $conversation)
    {
        $notification = [];

        foreach ($conversation->profiles as $profile) {

            $is_sender = ($message->user_id == $profile->id) ? 1 : 0;

            $notification[] = [
                'profile_id' => $profile->id,
                'message_id' => $message->id,
                'conversation_id' => $conversation->id,
                'is_seen' => $is_sender,
                'is_sender' => $is_sender,
                'created_at' => $message->created_at,
            ];
        }

        MessageNotification::insert($notification);
    }
}
