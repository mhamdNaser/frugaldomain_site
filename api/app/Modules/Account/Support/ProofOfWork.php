<?php

namespace App\Modules\Account\Support;

use Illuminate\Support\Facades\Cache;

/**
 * A proof-of-work challenge in the ALTCHA format (altcha.org), implemented
 * here so nothing third-party sits between a person and their password.
 *
 * The server picks a secret number N and publishes SHA-256(salt . N) with an
 * HMAC signature. The browser finds N by trying 0, 1, 2, ... - about a second
 * of work for one visitor, and a real cost for a script sending thousands of
 * requests. The signature proves the challenge came from here; the expiry
 * in the salt and the one-use cache entry stop a solved challenge being
 * reused.
 *
 * This raises the price of automation; it does not prove a human is present.
 * What proves the right person is present is the code sent to their inbox -
 * this sits in front of it so that inbox is not flooded, and so the code
 * cannot be guessed at scale.
 */
class ProofOfWork
{
    /** Upper bound for N. Average work is half of it. */
    private const MAX_NUMBER = 200000;

    /** How long a challenge may be held before it is solved and used. */
    private const TTL_SECONDS = 600;

    public static function create(): array
    {
        $expires = time() + self::TTL_SECONDS;
        $salt = bin2hex(random_bytes(12)) . '?expires=' . $expires;
        $number = random_int(0, self::MAX_NUMBER);
        $challenge = hash('sha256', $salt . $number);

        return [
            'algorithm' => 'SHA-256',
            'challenge' => $challenge,
            'maxnumber' => self::MAX_NUMBER,
            'salt' => $salt,
            'signature' => self::sign($challenge),
        ];
    }

    /**
     * Checks a solution, given as the base64 JSON the browser sends
     * ({algorithm, challenge, number, salt, signature}). A solution is
     * accepted once; a second use of the same one fails.
     */
    public static function verify(?string $payload, int $minAgeSeconds = 0): bool
    {
        if (!$payload || strlen($payload) > 2048) {
            return false;
        }

        $data = json_decode(base64_decode($payload, true) ?: '', true);
        if (!is_array($data)) {
            return false;
        }

        foreach (['algorithm', 'challenge', 'number', 'salt', 'signature'] as $key) {
            if (!array_key_exists($key, $data)) {
                return false;
            }
        }

        if ($data['algorithm'] !== 'SHA-256' || !is_numeric($data['number'])) {
            return false;
        }

        // The expiry is inside the signed salt, so it cannot be extended.
        $expires = preg_match('/[?&]expires=(\d+)/', (string) $data['salt'], $match) ? (int) $match[1] : 0;
        if ($expires < time()) {
            return false;
        }

        // Issued at expires - TTL, and that is signed too: a form sent back
        // within a couple of seconds of being opened was filled in by a
        // script, not typed. Measured here, not trusted from the browser.
        if (time() - ($expires - self::TTL_SECONDS) < $minAgeSeconds) {
            return false;
        }

        if (!hash_equals(self::sign((string) $data['challenge']), (string) $data['signature'])) {
            return false;
        }

        $number = (int) $data['number'];
        if ($number < 0 || $number > self::MAX_NUMBER) {
            return false;
        }

        if (!hash_equals((string) $data['challenge'], hash('sha256', $data['salt'] . $number))) {
            return false;
        }

        // One use per challenge: add() fails when the key already exists.
        return Cache::add('pow-used:' . $data['challenge'], true, $expires - time() + 60);
    }

    private static function sign(string $challenge): string
    {
        return hash_hmac('sha256', $challenge, (string) config('app.key'));
    }
}
