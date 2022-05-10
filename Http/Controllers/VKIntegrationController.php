<?php

namespace Modules\VKIntegration\Http\Controllers;

use App\Conversation;
use App\Customer;
use App\Mailbox;
use App\Thread;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use VK\Client\VKApiClient;

class VKIntegrationController extends Controller
{
    public function webhook(Request $request)
    {
        $options = \Option::getOptions([
            'vkintegration.access_token',
            'vkintegration.confirmation_code',
            'vkintegration.default_mailbox',
            'vkintegration.secret',
        ]);

        if ($request->secret != $options['vkintegration.secret']) return 'invalid secret';

        if ($request->type == 'confirmation') return $options['vkintegration.confirmation_code'];

        if ($request->type == 'message_new') {
            $vk_group_id = $request->group_id;
            $vk_key = $options['vkintegration.access_token'];
            $vk_id = $request->object['message']['from_id'];
            $vk = new VKApiClient();

            if (!is_int($vk_id)) return 'invalid vk id';
            $customer = Customer::whereRaw('json_contains(json_extract(social_profiles, \'$[*].value\'), \'"https:\\\\/\\\\/vk.com\\\\/id'.$vk_id.'"\', \'$\')')->first();

            if (!$customer) {
                $vk_user = $vk->users()->get($vk_key, [
                    'user_ids' => $vk_id,
                    'fields' => 'first_name,last_name,photo_100'
                ])[0];

                $customer = Customer::createWithoutEmail([
                    'first_name' => $vk_user['first_name'],
                    'last_name' => $vk_user['last_name']
                ]);

                $customer->setSocialProfiles([[
                    'type' => Customer::SOCIAL_TYPE_VK,
                    'value' => "https://vk.com/id{$vk_id}"
                ]]);

                $customer->setPhotoFromRemoteFile($vk_user['photo_100']);
                $customer->save();
            }

            $thread = Thread::where('customer_id', $customer->id)->first();
            $conversation = null;

            // TODO mark conversation as active

            if ($thread) {
                $conversation = Conversation::where('id', $thread->conversation_id)->first();

                Thread::create(
                    $conversation,
                    Thread::TYPE_CUSTOMER,
                    $request->object['message']['text'],
                    [
                        'source_via' => Thread::PERSON_CUSTOMER,
                        'source_type' => Thread::SOURCE_TYPE_API,
                        'customer_id' => $customer->id,
                        'created_by_customer_id' => $customer->id,
                        'meta' => ['vk_integration' => true]
                    ]
                );
            } else {
                $vk_group = $vk->groups()->getById($vk_key, ['group_id' => $vk_group_id])[0];
                $default_mailbox = Mailbox::where('email', $options['vkintegration.default_mailbox'])->first();

                // TODO it doesn't look like meta is added
                $conversation = Conversation::create([
                    'type' => Conversation::TYPE_CHAT,
                    'subject' => "Диалог в сообществе {$vk_group['name']}",
                    'mailbox_id' => $default_mailbox->id,
                    'source_type' => Conversation::SOURCE_TYPE_API,
                ], [
                    [
                        'body' => $request->object['message']['text'],
                        'type' => Thread::TYPE_CUSTOMER,
                        'meta' => ['vk_integration' => true]
                    ]
                ], $customer);
            }
        }

        return 'ok';
    }
}
