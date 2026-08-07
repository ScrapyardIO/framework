<?php

use Fabricate\Broadcasting\Channel;
use Fabricate\Broadcasting\InteractsWithSockets;
use Fabricate\Broadcasting\PrivateChannel;
use Fabricate\Contracts\Broadcasting\HasBroadcastChannel;

test('channel is stringable by name', function () {
    $channel = new Channel('orders');

    expect((string) $channel)->toBe('orders')
        ->and($channel->name)->toBe('orders');
});

test('private channel prefixes name', function () {
    expect((string) new PrivateChannel('chat.1'))->toBe('private-chat.1');
});

test('private channel resolves HasBroadcastChannel', function () {
    $model = new class implements HasBroadcastChannel
    {
        public function broadcastChannelRoute(): string
        {
            return 'users.{user}';
        }

        public function broadcastChannel(): string
        {
            return 'users.42';
        }
    };

    expect((string) new PrivateChannel($model))->toBe('private-users.42');
});

test('interacts with sockets clears socket for everyone', function () {
    $event = new class
    {
        use InteractsWithSockets;
    };

    $event->socket = 'abc';

    expect($event->dontBroadcastToCurrentUser())->toBe($event)
        ->and($event->broadcastToEveryone())->toBe($event)
        ->and($event->socket)->toBeNull();
});
