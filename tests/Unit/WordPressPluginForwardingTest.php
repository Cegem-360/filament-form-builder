<?php

declare(strict_types=1);

/*
 * Runs the plugin's hooks against minimal WordPress stand-ins to prove a
 * Contact Form 7 / Elementor submission is posted to the configured URL.
 */

if (! defined('ABSPATH')) {
    define('ABSPATH', __DIR__);
}

$GLOBALS['cegem360_test_options'] = [];
$GLOBALS['cegem360_test_requests'] = [];

if (! function_exists('get_option')) {
    function get_option($name, $default = false)
    {
        return $GLOBALS['cegem360_test_options'][$name] ?? $default;
    }
    function update_option($name, $value, $autoload = null)
    {
        $GLOBALS['cegem360_test_options'][$name] = $value;

        return true;
    }
    function add_action(...$args) {}
    function add_filter(...$args) {}
    function wp_json_encode($data)
    {
        return json_encode($data);
    }
    function is_wp_error($thing)
    {
        return false;
    }
    function current_time($type)
    {
        return '2026-11-13 15:00:00';
    }
    function get_the_title($id)
    {
        return 'Kapcsolat';
    }
    function wp_get_referer()
    {
        return 'https://capstone.test/ajanlat/';
    }
    function wp_remote_retrieve_response_code($response)
    {
        return $response['response']['code'];
    }
    function wp_remote_retrieve_body($response)
    {
        return $response['body'];
    }
    function wp_remote_post($url, $args)
    {
        $GLOBALS['cegem360_test_requests'][] = ['url' => $url, 'body' => json_decode($args['body'], true), 'timeout' => $args['timeout']];

        return ['response' => ['code' => 201], 'body' => '{"status":"ok","submission_id":1}'];
    }
}

if (! class_exists('WPCF7_Submission')) {
    final class WPCF7_Submission
    {
        public static function get_instance(): self
        {
            return new self;
        }

        public function get_posted_data(): array
        {
            return ['_wpcf7' => '7', 'your-name' => 'Anna', 'your-email' => 'anna@example.com'];
        }

        public function get_meta(string $key)
        {
            return ['url' => 'https://capstone.test/kapcsolat/', 'container_post_id' => 5][$key] ?? null;
        }
    }
}

require_once __DIR__.'/../../wordpress-plugin/cegem360-marketinghub/includes/class-payload.php';
require_once __DIR__.'/../../wordpress-plugin/cegem360-marketinghub/includes/class-sender.php';
require_once __DIR__.'/../../wordpress-plugin/cegem360-marketinghub/includes/class-integrations.php';
require_once __DIR__.'/../../wordpress-plugin/cegem360-marketinghub/includes/class-settings.php';

if (! defined('CEGEM360_MH_VERSION')) {
    define('CEGEM360_MH_VERSION', 'test');
}

beforeEach(function (): void {
    $GLOBALS['cegem360_test_requests'] = [];
    $GLOBALS['cegem360_test_options'] = [
        Cegem360_MarketingHub_Settings::OPTION_CONNECTIONS => [
            ['form' => 'cf7:7', 'url' => 'https://hub.test/forms/kapcsolat/inbound/'.str_repeat('a', 64)],
            ['form' => 'elementor:Ajanlat', 'url' => 'https://hub.test/forms/ajanlat/inbound/'.str_repeat('b', 64)],
        ],
    ];
});

it('posts a Contact Form 7 submission to its webhook with page meta', function (): void {
    $form = new class
    {
        public function id(): int
        {
            return 7;
        }

        public function title(): string
        {
            return 'Kapcsolat';
        }
    };

    Cegem360_MarketingHub_Integrations::contact_form_7($form);

    expect($GLOBALS['cegem360_test_requests'])->toHaveCount(1)
        ->and($GLOBALS['cegem360_test_requests'][0]['url'])->toBe('https://hub.test/forms/kapcsolat/inbound/'.str_repeat('a', 64))
        ->and($GLOBALS['cegem360_test_requests'][0]['timeout'])->toBe(5)
        ->and($GLOBALS['cegem360_test_requests'][0]['body'])->toBe([
            'your-name' => 'Anna',
            'your-email' => 'anna@example.com',
            'wp_form' => 'Contact Form 7: Kapcsolat',
            'wp_page_url' => 'https://capstone.test/kapcsolat/',
            'wp_page_title' => 'Kapcsolat',
        ])
        ->and(Cegem360_MarketingHub_Sender::entries()[0])->toMatchArray(['form' => 'Contact Form 7: Kapcsolat', 'ok' => true, 'status' => 201]);
});

it('posts an Elementor submission by form name and ignores unconfigured forms', function (): void {
    $record = fn (string $name) => new class($name)
    {
        public function __construct(private string $name) {}

        public function get_form_settings(string $key)
        {
            return $this->name;
        }

        public function get(string $key)
        {
            return $key === 'fields'
                ? ['email' => ['id' => 'email', 'raw_value' => 'anna@example.com']]
                : ['page_url' => ['value' => 'https://capstone.test/ajanlat/']];
        }
    };

    Cegem360_MarketingHub_Integrations::elementor($record('Ajanlat'));
    Cegem360_MarketingHub_Integrations::elementor($record('Hirlevel'));

    expect($GLOBALS['cegem360_test_requests'])->toHaveCount(1)
        ->and($GLOBALS['cegem360_test_requests'][0]['body']['email'])->toBe('anna@example.com');
});
