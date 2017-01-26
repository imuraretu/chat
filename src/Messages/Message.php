<?php

namespace Musonza\Chat\Messages;

use Eloquent;
use Musonza\Chat\Conversations\Conversation;
use Musonza\Chat\Eventing\EventGenerator;
use Musonza\Chat\Chat;
use Musonza\Chat\Notifications\MessageNotification;
use Illuminate\Database\Eloquent\Relations\Relation;

class Message extends Eloquent
{
    protected $fillable = ['body', 'profile_id', 'type'];

    protected $table = 'messages';

    use EventGenerator;

    public function sender()
    {
        return $this->belongsTo(Chat::profileModel(), 'profile_id');
    }

    public function conversation()
    {
        return $this->belongsTo('Musonza\Chat\Conversations\Conversation', 'conversation_id');
    }
    
    public function attachments()
    {
        Relation::morphMap([
            'message' => Message::class,
        ]);

        return $this->morphMany(Chat::attachmentModel(), 'entity');
    }

    /**
     * Adds a message to a conversation
     *
     * @param      Conversation  $conversation
     * @param      string        $body
     * @param      integer        $userId
     * @param      string        $type
     *
     * @return     Message
     */
    public function send(Conversation $conversation, $body, $profileId, $type = 'text')
    {
        $message = $conversation->messages()->create([
            'body' => $body,
            'profile_id' => $profileId,
            'type' => $type,
        ]);

        $this->raise(new MessageWasSent($message));

        return $this;
    }

    /**
     * Deletes a message
     *
     * @param      integer  $messageId
     * @param      integer  $profileId
     *
     * @return
     */
    public function trash($messageId, $profileId)
    {
        return MessageNotification::where('profile_id', $profileId)
            ->where('message_id', $messageId)
            ->delete();
    }

    /**
     * marks message as read
     *
     * @param      integer  $messageId
     * @param      integer  $userId
     *
     * @return
     */
    public function messageRead($messageId, $profileId)
    {
        return MessageNotification::where('profile_id', $profileId)
            ->where('message_id', $messageId)
            ->update(['is_seen' => 1]);
    }
}
