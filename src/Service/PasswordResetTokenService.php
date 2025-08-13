<?php

namespace App\Service;

use App\Entity\Participant;
use App\Repository\ParticipantRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class PasswordResetTokenService
{
    private string $appSecret;

    public function __construct(
        private readonly ParticipantRepository $participantRepository,
        ParameterBagInterface $parameterBag,
    ) {
        $secret = (string) $parameterBag->get('kernel.secret');
        if ($secret === '') {
            throw new \RuntimeException('kernel.secret is not configured.');
        }
        $this->appSecret = $secret;
    }

    public function generateToken(Participant $user, \DateTimeImmutable $expiresAt): string
    {
        $userId = (string) $user->getId();
        $expiry = (string) $expiresAt->getTimestamp();
        $passwordHash = (string) $user->getPassword();

        $payload = $userId . ':' . $expiry;
        $signature = $this->sign($payload . ':' . $passwordHash);

        return $this->base64UrlEncode($payload) . '.' . $signature;
    }

    /**
     * Returns the Participant if the token is valid and not expired; null otherwise.
     */
    public function validateAndGetUser(string $token): ?Participant
    {
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) {
            return null;
        }

        [$payloadB64, $providedSignature] = $parts;
        $payload = $this->base64UrlDecode($payloadB64);
        if ($payload === null) {
            return null;
        }

        $payloadParts = explode(':', $payload, 2);
        if (count($payloadParts) !== 2) {
            return null;
        }

        [$userId, $expiryTs] = $payloadParts;
        if (!ctype_digit($userId) || !ctype_digit($expiryTs)) {
            return null;
        }

        $expiresAt = (int) $expiryTs;
        if ($expiresAt < time()) {
            return null; // expired
        }

        $user = $this->participantRepository->find((int) $userId);
        if (!$user instanceof Participant) {
            return null;
        }

        $passwordHash = (string) $user->getPassword();
        $expectedSignature = $this->sign($userId . ':' . $expiryTs . ':' . $passwordHash);

        if (!hash_equals($expectedSignature, $providedSignature)) {
            return null;
        }

        return $user;
    }

    private function sign(string $data): string
    {
        $raw = hash_hmac('sha256', $data, $this->appSecret, true);
        return $this->base64UrlEncode($raw);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): ?string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        $decoded = base64_decode(strtr($data, '-_', '+/'), true);
        return $decoded === false ? null : $decoded;
    }
}