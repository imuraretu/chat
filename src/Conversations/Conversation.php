<?php

namespace Musonza\Chat\Conversations;

use Eloquent;
use Musonza\Chat\Chat;
use Musonza\Chat\Notifications\MessageNotification;

class Conversation extends Eloquent
{
    protected $fillable = [];

    /**
     * Conversation participants
     *
     * @return User
     */
    public function profiles()
    {
        return $this->belongsToMany(Chat::userModel(), 'conversation_profile')->withTimestamps();
    }

    /**
     * Messages in conversation
     *
     * @return Message
     */
    public function messages()
    {
        return $this->hasMany('Musonza\Chat\Messages\Message', 'conversation_id')
            ->with('sender', 'attachments');
    }

    /**
     * Get recent user messages for each conversation
     *
     * @param      integer   $userId
     * @param      integer  $perPage
     * @param      integer  $page
     * @param      string   $sorting
     * @param      array    $columns
     * @param      string   $pageName
     *
     * @return     <type>
     */
    public function getMessages($userId, $perPage = 25, $page = 1, $sorting = 'asc', $columns = ['messages.*', 'message_notification.is_seen'], $pageName = 'page')
    {
        return $this->messages()
            ->join('message_notification', 'message_notification.message_id', '=', 'messages.id')
            ->whereNull('message_notification.deleted_at')
            ->where('message_notification.profile_id', $userId)
            ->orderBy('messages.id', $sorting)
            ->paginate($perPage, $columns, $pageName, $page);
    }

    /**
     * Add user to conversation
     *
     * @param  integer  $userId
     * @return void
     */
    public function addParticipants($userIds)
    {
        if (is_array($userIds)) {
            foreach ($userIds as $id) {
                $this->users()->attach($id);
            }
        } else {
            $this->users()->attach($userIds);
        }

        if ($this->users->count() > 2) {
            $this->private = false;
            $this->save();
        }

        return $this;
    }

    /**
     * Remove user from conversation
     *
     * @param  User  $userId
     * @return void
     */
    public function removeUsers($userId)
    {
        if (is_array($userId)) {
            foreach ($userId as $id) {
                $this->users()->detach($id);
            }

            return $this;
        }

        $this->users()->detach($userId);

        return $this;
    }

    /**
     * Starts a new conversation
     *
     * @param      array  $participants  users
     *
     * @return     Conversation
     */
    public function start($participants)
    {
        $conversation = $this->create();

        if ($participants) {
            $conversation->addParticipants($participants);
        }

        return $conversation;
    }

    /**
     * Get number of users in a conversation
     *
     * @return     integer
     */
    public function userCount()
    {
        return $this->count();
    }

    /**
     * Gets conversations for a specific user
     *
     * @param      integer  $userId
     *
     * @return     array
     */
    public function userConversations($userId)
    {
        return $this->join('conversation_profile', 'conversation_profile.conversation_id', '=', 'conversations.id')
            ->where('conversation_profile.profile_id', $userId)
            ->where('private', true)
            ->pluck('conversations.id');
    }

    /**
     * Clears user conversation
     *
     * @param      integer  $conversationId
     * @param      integer  $profileId
     *
     * @return
     */
    public function clear($conversationId, $profileId)
    {
        return MessageNotification::where('user_id', $profileId)
            ->where('conversation_id', $conversationId)
            ->delete();
    }

    public function conversationRead($conversationId, $profileId)
    {
        return MessageNotification::where('profile_id', $profileId)
            ->where('conversation_id', $conversationId)
            ->update(['is_seen' => 1]);
    }
}
