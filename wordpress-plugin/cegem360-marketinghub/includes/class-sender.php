<?php

/**
 * Sends a payload to the MarketingHub webhook and keeps a short log.
 */
if (! defined('ABSPATH')) {
    exit;
}

final class Cegem360_MarketingHub_Sender
{
    public const OPTION_LOG = 'cegem360_mh_log';

    private const LOG_SIZE = 20;

    /**
     * Never throws: a failing MarketingHub must not break the visitor's form.
     *
     * @param  array<string, mixed>  $payload
     * @return array{ok: bool, status: int, message: string}
     */
    public static function send(string $url, array $payload, string $form_label): array
    {
        $response = wp_remote_post($url, [
            'timeout' => 5,
            'redirection' => 0,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'User-Agent' => 'Cegem360-MarketingHub-WP/'.CEGEM360_MH_VERSION,
            ],
            'body' => wp_json_encode($payload),
            'data_format' => 'body',
        ]);

        if (is_wp_error($response)) {
            $result = ['ok' => false, 'status' => 0, 'message' => $response->get_error_message()];
        } else {
            $status = (int) wp_remote_retrieve_response_code($response);
            $body = json_decode((string) wp_remote_retrieve_body($response), true);
            $message = is_array($body) && isset($body['message']) ? (string) $body['message'] : self::status_text($status);
            $result = ['ok' => $status === 201, 'status' => $status, 'message' => $message];
        }

        self::log($form_label, $result);

        return $result;
    }

    /**
     * Checks a webhook URL without creating a submission: an empty payload
     * must be rejected as invalid (422), which proves the URL and token work.
     *
     * @return array{ok: bool, status: int, message: string}
     */
    public static function test(string $url): array
    {
        $response = wp_remote_post($url, [
            'timeout' => 5,
            'redirection' => 0,
            'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
            'body' => '{}',
            'data_format' => 'body',
        ]);

        if (is_wp_error($response)) {
            return ['ok' => false, 'status' => 0, 'message' => $response->get_error_message()];
        }

        $status = (int) wp_remote_retrieve_response_code($response);

        return [
            'ok' => in_array($status, [201, 422], true),
            'status' => $status,
            'message' => in_array($status, [201, 422], true) ? 'A kapcsolat rendben: a MarketingHub fogadja az űrlapot.' : self::status_text($status),
        ];
    }

    /**
     * @return list<array{time: string, form: string, ok: bool, status: int, message: string}>
     */
    public static function entries(): array
    {
        $log = get_option(self::OPTION_LOG, []);

        return is_array($log) ? array_values($log) : [];
    }

    /**
     * @param  array{ok: bool, status: int, message: string}  $result
     */
    private static function log(string $form_label, array $result): void
    {
        $log = self::entries();
        array_unshift($log, [
            'time' => current_time('mysql'),
            'form' => $form_label,
            'ok' => $result['ok'],
            'status' => $result['status'],
            'message' => mb_substr($result['message'], 0, 300),
        ]);

        update_option(self::OPTION_LOG, array_slice($log, 0, self::LOG_SIZE), false);
    }

    private static function status_text(int $status): string
    {
        switch ($status) {
            case 201:
                return 'Elküldve.';
            case 404:
                return 'A webhook cím rossz vagy újragenerálták, vagy a webhook ki van kapcsolva az űrlapon.';
            case 413:
                return 'Túl sok mező.';
            case 422:
                return 'Hiányzik egy kötelező mező, vagy nincs párosítva.';
            case 429:
                return 'Túl sok kérés, később újra próbálható.';
            default:
                return 'Váratlan válasz: HTTP '.$status;
        }
    }
}
