<?php

namespace App\Cms;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * Two-factor authentication with an app (Google Authenticator, Microsoft
 * Authenticator, 2FAS…): 6-digit codes renewed every 30 seconds.
 */
class TwoFactor
{
    public function __construct(private Google2FA $engine = new Google2FA) {}

    public function generateSecret(): string
    {
        return $this->engine->generateSecretKey(32);
    }

    public function qrCodeSvg(string $email, string $secret): string
    {
        $issuer = str(app(SiteSettings::class)->get('site_name') ?: config('app.name'))->ascii()->toString();
        $uri = $this->engine->getQRCodeUrl($issuer, $email, $secret);

        $svg = (new Writer(new ImageRenderer(new RendererStyle(220, 1), new SvgImageBackEnd)))->writeString($uri);

        // XML declaration removed for direct insertion in the page
        return trim(preg_replace('/^<\?xml.*?\?>/', '', $svg));
    }

    /** Checks a code; returns false or the timestamp used (anti-replay). */
    public function verify(string $secret, string $code, ?int $lastTimestamp = null): int|false
    {
        $code = preg_replace('/\D/', '', $code);
        if (strlen($code) !== 6) {
            return false;
        }

        $result = $lastTimestamp
            ? $this->engine->verifyKeyNewer($secret, $code, $lastTimestamp, 1)
            : $this->engine->verifyKeyNewer($secret, $code, 0, 1);

        return $result === false ? false : (int) $result;
    }

    /** Validates a user's code and prevents the same code from being reused. */
    public function verifyUser(User $user, string $code): bool
    {
        $timestamp = $this->verify($user->two_factor_secret, $code, $user->two_factor_last_timestamp);
        if ($timestamp === false) {
            return false;
        }
        $user->forceFill(['two_factor_last_timestamp' => $timestamp])->save();

        return true;
    }

    /** @return string[] 8 single-use recovery codes */
    public function recoveryCodes(): array
    {
        return collect(range(1, 8))->map(fn () => strtoupper(Str::random(5).Str::random(5)))->all();
    }

    public static function formatCode(string $code): string
    {
        return substr($code, 0, 5).'-'.substr($code, 5);
    }
}
