<?php

use App\Utilities\MessagingChannel;

test('detects whatsapp addresses regardless of prefix case', function () {
    expect(MessagingChannel::isWhatsApp('whatsapp:+14075551212'))->toBeTrue()
        ->and(MessagingChannel::isWhatsApp('WHATSAPP:+14075551212'))->toBeTrue()
        ->and(MessagingChannel::isWhatsApp('+14075551212'))->toBeFalse()
        ->and(MessagingChannel::isWhatsApp(null))->toBeFalse()
        ->and(MessagingChannel::isWhatsApp(''))->toBeFalse();
});

test('strips the whatsapp prefix to e164', function () {
    expect(MessagingChannel::e164('whatsapp:+14075551212'))->toBe('+14075551212')
        ->and(MessagingChannel::e164('+14075551212'))->toBe('+14075551212')
        ->and(MessagingChannel::e164(null))->toBeNull()
        ->and(MessagingChannel::e164(''))->toBe('');
});

test('channel detection looks at any supplied address', function () {
    expect(MessagingChannel::channelFromAddresses('+15551111111', 'whatsapp:+14075551212'))
        ->toBe(MessagingChannel::WHATSAPP)
        ->and(MessagingChannel::channelFromAddresses('+15551111111', '+14075551212'))
        ->toBe(MessagingChannel::SMS);
});

test('sameNumber compares e164 values across channels', function () {
    expect(MessagingChannel::sameNumber('whatsapp:+14075551212', '+14075551212'))->toBeTrue()
        ->and(MessagingChannel::sameNumber('+14075551212', '+15551111111'))->toBeFalse();
});

test('click-to-chat urls omit plus signs and can prefill a first message', function () {
    expect(MessagingChannel::clickToChatUrl('whatsapp:+14075551212'))
        ->toBe('https://wa.me/14075551212')
        ->and(MessagingChannel::clickToChatUrl('+14075551212', 'talk Orlando'))
        ->toBe('https://wa.me/14075551212?text=talk%20Orlando')
        ->and(MessagingChannel::clickToChatUrl(''))->toBeNull();
});
