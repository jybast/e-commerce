<?php

namespace App\Service;

class JWTService
{

    /**
     * On génére un token
     * @param $header
     * @param $payload
     * @param $secret
     * @param $validity (in seconds) 10800 = 3 hours
     * @return string
     */
    public function generate(array $header, array $payload, string $secret, int $validity = 10800)
    {
        if ($validity > 0) {
            $now = new \DatetimeImmutable();

            $expiration = $now->getTimestamp() + $validity;

            $payload['iat'] = $now->getTimestamp();
            $payload['exp'] = $expiration;
        }



        // on encode en base64

        $base64Header = base64_encode(json_encode($header));
        $base64Payload = base64_encode(json_encode($payload));
        $secret = $secret;
        $validity = $validity;

        // on "nettoie" les valeurs encodées
        // retrait des signes +, / et =
        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], $base64Header);
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], $base64Payload);

        // on génère la signature
        // utilise JWT_SECRET dans .env.local

        $secret = base64_encode($secret);
        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $secret, true);

        $base64Signature = base64_encode($signature);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], $base64Signature);

        // on crée le token

        $jwt = $base64Header . '.' . $base64Payload . '.' . $base64Signature;

        return $jwt;
    }

    public function decodeToken($token)
    {
        return $this->jwt->decode($token);
    }

    // on vérifie que le token est bien formé
    public function isValid(string $token): bool
    {
        return preg_match('/^[a-zA-Z0-9\-\_\=]+\.[a-zA-Z0-9\-\_\=]+\.[a-zA-Z0-9\-\_\=]+$/', $token) === 1;
    }

    // on récupère le header du token
    public function getHeader(string $token): array
    {
        // on démonte le token
        $array = explode('.', $token);
        // on décode le header (1ere partie du token)
        $header = json_decode(base64_decode($array[0]), true);

        return $header;
    }
    // on récupère le payload du token
    public function getPayload(string $token): array
    {
        // on démonte le token
        $array = explode('.', $token);
        // on décode le payload (2d partie du token)
        $payload = json_decode(base64_decode($array[1]), true);

        return $payload;
    }

    // on vérifie si le token a expiré
    public function isExpired(string $token): bool
    {
        $payload = $this->getPayload($token);
        $now = new \DatetimeImmutable();
        return $payload['exp'] < $now->getTimestamp();
    }

    // on vérifie la signature du token
    public function isValidSignature(string $token, string $secret): bool
    {
        // récupère le header et le payload
        $header = $this->getHeader($token);
        $payload = $this->getPayload($token);

        // regénère le token avec le secret la validité à 0 pour ne pas regenerer les dates
        $newToken = $this->generate($header, $payload, $secret, 0);

        return $token === $newToken;
    }
}
