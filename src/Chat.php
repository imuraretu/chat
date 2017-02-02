<?php

namespace Musonza\Chat;

use Musonza\Chat\Commanding\CommandBus;
use Musonza\Chat\Conversations\Conversation;
use Musonza\Chat\Conversations\ConversationUser;
use Musonza\Chat\Messages\Message;
use Musonza\Chat\Messages\SendMessageCommand;
use Musonza\Chat\Notifications\MessageNotification;

class Chat
{
    public function __construct(
        Conversation $conversation,
        Message $message,
        CommandBus $commandBus
    ) {
        $this->conversation = $conversation;
        $this->message = $message;
        $this->commandBus = $commandBus;
    }

    /**
     * Creates a new conversation
     *
     * @param array $participants
     *
     * @return Conversation
     */
    public function createConversation(array $participants = null)
    {
        return $this->conversation->start($participants);
    }

    /**
     * Returns a new conversation
     *
     * @param int $conversationId
     *
     * @return Conversation
     */
    public function conversation($conversationId)
    {
        return $this->conversation->findOrFail($conversationId);
    }

    /**
     * Add user(s) to a conversation
     *
     * @param int $conversationId
     * @param mixed $profileId / array of user ids or an integer
     *
     * @return Conversation
     */
    public function addParticipants($conversationId, $profileId)
    {
        return $this->conversation($conversationId)->addParticipants($profileId);
    }

    /**
     * Sends a message
     *
     * @param int $conversationId
     * @param string $body
     * @param int $senderId
     *
     * @return
     */
    public function send($conversationId, $body, $senderId)
    {
        $conversation = $this->conversation->findOrFail($conversationId);

        $command = new SendMessageCommand($conversation, $body, $senderId);

        $this->commandBus->execute($command);
    }

    /**
     * Remove user(s) from a conversation
     *
     * @param int $conversationId
     * @param mixed $userId / array of user ids or an integer
     *
     * @return Coonversation
     */
    public function removeParticipants($conversationId, $profileId)
    {
        return $this->conversation($conversationId)->removeParticipants($profileId);
    }

    /**
     * Get recent user messages for each conversation
     *
     * @param int $profileId
     *
     * @return Message
     */
    public function conversations($profileId)
    {
        $c = ConversationUser::join('messages', 'messages.conversation_id', '=', 'conversation_profile.conversation_id')
            ->where('conversation_profile.profile_id', $profileId)
            ->groupBy('messages.conversation_id')
            ->orderBy('messages.id', 'DESC')
            ->get(['messages.*', 'messages.id as message_id', 'conversation_profile.*']);

        $messages = [];

        foreach ($c as $profile) {

            $recent_message = $profile->conversation->messages()->orderBy('id', 'desc')->first()->toArray();

            $notification = MessageNotification::where('profile_id', $profileId)
                ->where('message_id', $profile->id)
                ->get(['message_notification.id',
                    'message_notification.is_seen',
                    'message_notification.is_sender']
                );

            $messages[] = array_merge(
                $recent_message, ['notification' => $notification]
            );

        }

        return $messages;
    }

    /**
     * Get messages in a conversation
     *
     * @param int $profileId
     * @param int $conversationId
     * @param int $perPage
     * @param int $page
     *
     * @return Message
     */
    public function messages($profileId, $conversationId, $perPage = null, $page = null)
    {
        return $this->conversation($conversationId)->getMessages($profileId, $perPage, $page);
    }

    /**
     * Deletes message
     *
     * @param      int  $messageId
     * @param      int  $profileId     profile id
     *
     * @return     void
     */
    public function trash($messageId, $profileId)
    {
        return $this->message->trash($messageId, $profileId);
    }

    /**
     * clears conversation
     *
     * @param      int  $conversationId
     * @param      int  $profileId
     */
    public function clear($conversationId, $profileId)
    {
        return $this->conversation->clear($conversationId, $profileId);
    }

    public function messageRead($messageId, $profileId)
    {
        return $this->message->messageRead($messageId, $profileId);
    }

    public function conversationRead($conversationId, $profileId)
    {
        $this->conversation->conversationRead($conversationId, $profileId);
    }

    public function getConversationBetweenUsers($profileOne, $profileTwo)
    {
        $conversation1 = $this->conversation->userConversations($profileOne)->toArray();

        $conversation2 = $this->conversation->userConversations($profileTwo)->toArray();

        $common_conversations = $this->getConversationsInCommon($conversation1, $conversation2);

        if(!$common_conversations){
            return null;
        }

        return $this->conversation->findOrFail($common_conversations[0]);
    }

    private function getConversationsInCommon($conversation1, $conversation2)
    {
        return array_values(array_intersect($conversation1, $conversation2));
    }

    public static function profileModel()
    {
        return config('chat.profile_model');
    }
    
    public static function attachmentModel()
    {
        return config('chat.attachment_model');
    }

}
