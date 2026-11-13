<?php

declare(strict_types=1);

define('CEGEM360_MH_TESTING', true);

require_once __DIR__.'/../../wordpress-plugin/cegem360-marketinghub/includes/class-payload.php';

it('turns Contact Form 7 posted data into fields', function (): void {
    $payload = Cegem360_MarketingHub_Payload::from_cf7([
        '_wpcf7' => '12',
        '_wpcf7_unit_tag' => 'wpcf7-f12',
        'your-name' => 'Minta Anna',
        'your-email' => 'anna@example.com',
        'menu-312' => ['Ajánlatkérés'],
        'checkbox-1' => ['A', 'B'],
        'acceptance-1' => '1',
    ]);

    expect($payload)->toBe([
        'your-name' => 'Minta Anna',
        'your-email' => 'anna@example.com',
        'menu-312' => 'Ajánlatkérés',
        'checkbox-1' => ['A', 'B'],
        'acceptance-1' => '1',
    ]);
});

it('turns WPForms fields into label keyed fields', function (): void {
    $payload = Cegem360_MarketingHub_Payload::from_wpforms([
        1 => ['id' => 1, 'name' => 'Név', 'value' => 'Minta Anna', 'type' => 'name'],
        2 => ['id' => 2, 'name' => 'E-mail cím', 'value' => 'anna@example.com', 'type' => 'email'],
        3 => ['id' => 3, 'name' => 'Érdeklődés', 'value' => "SEO\nPPC", 'type' => 'checkbox'],
        4 => ['id' => 4, 'name' => '', 'value' => 'x', 'type' => 'text'],
    ]);

    expect($payload)->toBe([
        'nev' => 'Minta Anna',
        'e_mail_cim' => 'anna@example.com',
        'erdeklodes' => ['SEO', 'PPC'],
        'field_4' => 'x',
    ]);
});

it('turns Elementor Pro form fields into id keyed fields', function (): void {
    $payload = Cegem360_MarketingHub_Payload::from_elementor([
        'name' => ['id' => 'name', 'type' => 'text', 'title' => 'Név', 'value' => 'Minta Anna', 'raw_value' => 'Minta Anna'],
        'email' => ['id' => 'email', 'type' => 'email', 'title' => 'Email', 'value' => 'anna@example.com', 'raw_value' => 'anna@example.com'],
        'consent' => ['id' => 'consent', 'type' => 'acceptance', 'title' => 'Hozzájárulok', 'value' => 'on', 'raw_value' => 'on'],
    ]);

    expect($payload)->toBe(['name' => 'Minta Anna', 'email' => 'anna@example.com', 'consent' => 'on']);
});

it('turns a Gravity Forms entry into label keyed fields', function (): void {
    $form = ['fields' => [
        ['id' => 1, 'type' => 'name', 'label' => 'Név', 'inputs' => [['id' => '1.3', 'label' => 'First'], ['id' => '1.6', 'label' => 'Last']]],
        (object) ['id' => 2, 'label' => 'Email', 'inputs' => null],
        ['id' => 3, 'label' => 'Szolgáltatások', 'inputs' => [['id' => '3.1'], ['id' => '3.2']]],
    ]];
    $entry = ['1.3' => 'Anna', '1.6' => 'Minta', '2' => 'anna@example.com', '3.1' => 'SEO', '3.2' => '', 'id' => '99'];

    expect(Cegem360_MarketingHub_Payload::from_gravity($entry, $form))->toBe([
        'nev' => 'Anna Minta',
        'email' => 'anna@example.com',
        'szolgaltatasok' => 'SEO',
    ]);
});

it('adds page meta without overwriting form fields', function (): void {
    $payload = Cegem360_MarketingHub_Payload::with_meta(['email' => 'a@b.hu', 'wp_form' => 'mine'], 'https://capstone.hu/kapcsolat/', 'Kapcsolat', 'Contact Form 7: Kapcsolat');

    expect($payload)->toBe([
        'email' => 'a@b.hu',
        'wp_form' => 'mine',
        'wp_page_url' => 'https://capstone.hu/kapcsolat/',
        'wp_page_title' => 'Kapcsolat',
    ]);
});

it('only accepts https webhook urls of the inbound shape', function (): void {
    expect(Cegem360_MarketingHub_Payload::is_valid_webhook_url('https://marketinghub.cegem360.eu/forms/kapcsolat/inbound/'.str_repeat('a', 64)))->toBeTrue()
        ->and(Cegem360_MarketingHub_Payload::is_valid_webhook_url('http://marketinghub.cegem360.eu/forms/kapcsolat/inbound/'.str_repeat('a', 64)))->toBeFalse()
        ->and(Cegem360_MarketingHub_Payload::is_valid_webhook_url('https://example.com/whatever'))->toBeFalse();
});
