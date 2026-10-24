<?php

namespace App\Services;

use RuntimeException;

/**
 * Client RouterOS API minimalis (port 8728) tanpa dependensi eksternal.
 * Mendukung login RouterOS >= 6.43 (plain) dan fallback challenge MD5 untuk versi lama.
 */
class RouterOsApiClient
{
    /** @var resource|null */
    private $socket = null;

    public function connect(string $host, int $port, string $user, string $password, float $timeout = 1.5): void
    {
        $socket = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, $timeout);
        if (!$socket) {
            throw new RuntimeException("Router tidak dapat dihubungi ({$errstr})");
        }
        stream_set_timeout($socket, (int) ceil($timeout) + 1);
        $this->socket = $socket;

        $reply = $this->command('/login', ['name' => $user, 'password' => $password]);

        // RouterOS < 6.43 mengembalikan challenge (=ret=) yang harus dijawab dengan MD5
        $challenge = $reply['done']['ret'] ?? null;
        if ($challenge) {
            $response = '00' . md5(chr(0) . $password . pack('H*', $challenge));
            $this->command('/login', ['name' => $user, 'response' => $response]);
        }
    }

    /**
     * Kirim perintah dan kumpulkan seluruh balasan hingga !done.
     *
     * @return array{rows: array<int, array<string, string>>, done: array<string, string>}
     */
    public function command(string $path, array $params = []): array
    {
        $words = [$path];
        foreach ($params as $key => $value) {
            $words[] = "={$key}={$value}";
        }
        $this->writeSentence($words);

        $rows = [];
        while (true) {
            $sentence = $this->readSentence();
            $type = array_shift($sentence);
            $attrs = $this->parseAttributes($sentence);

            if ($type === '!re') {
                $rows[] = $attrs;
            } elseif ($type === '!done') {
                return ['rows' => $rows, 'done' => $attrs];
            } elseif ($type === '!trap') {
                $this->drainUntilDone();
                throw new RuntimeException('RouterOS: ' . ($attrs['message'] ?? 'perintah ditolak'));
            } elseif ($type === '!fatal') {
                $this->close();
                throw new RuntimeException('RouterOS fatal: ' . implode(' ', $sentence));
            }
        }
    }

    public function close(): void
    {
        if (is_resource($this->socket)) {
            @fclose($this->socket);
        }
        $this->socket = null;
    }

    public function __destruct()
    {
        $this->close();
    }

    private function drainUntilDone(): void
    {
        while (true) {
            $sentence = $this->readSentence();
            if (($sentence[0] ?? null) === '!done') {
                return;
            }
        }
    }

    private function writeSentence(array $words): void
    {
        $payload = '';
        foreach ($words as $word) {
            $payload .= $this->encodeLength(strlen($word)) . $word;
        }
        $payload .= chr(0);

        if (@fwrite($this->socket, $payload) === false) {
            throw new RuntimeException('Gagal mengirim data ke RouterOS.');
        }
    }

    private function readSentence(): array
    {
        $words = [];
        while (true) {
            $length = $this->readLength();
            if ($length === 0) {
                return $words;
            }
            $words[] = $this->readBytes($length);
        }
    }

    private function parseAttributes(array $words): array
    {
        $attrs = [];
        foreach ($words as $word) {
            if (str_starts_with($word, '=')) {
                [$key, $value] = array_pad(explode('=', substr($word, 1), 2), 2, '');
                $attrs[$key] = $value;
            }
        }
        return $attrs;
    }

    private function encodeLength(int $len): string
    {
        if ($len < 0x80) {
            return chr($len);
        }
        if ($len < 0x4000) {
            return pack('n', $len | 0x8000);
        }
        if ($len < 0x200000) {
            $v = $len | 0xC00000;
            return chr(($v >> 16) & 0xFF) . chr(($v >> 8) & 0xFF) . chr($v & 0xFF);
        }
        if ($len < 0x10000000) {
            return pack('N', $len | 0xE0000000);
        }
        return chr(0xF0) . pack('N', $len);
    }

    private function readLength(): int
    {
        $b = ord($this->readBytes(1));

        if (($b & 0x80) === 0x00) {
            return $b;
        }
        if (($b & 0xC0) === 0x80) {
            return (($b & 0x3F) << 8) | ord($this->readBytes(1));
        }
        if (($b & 0xE0) === 0xC0) {
            $r = $this->readBytes(2);
            return (($b & 0x1F) << 16) | (ord($r[0]) << 8) | ord($r[1]);
        }
        if (($b & 0xF0) === 0xE0) {
            $r = $this->readBytes(3);
            return (($b & 0x0F) << 24) | (ord($r[0]) << 16) | (ord($r[1]) << 8) | ord($r[2]);
        }
        return unpack('N', $this->readBytes(4))[1];
    }

    private function readBytes(int $length): string
    {
        $data = '';
        while (strlen($data) < $length) {
            $chunk = @fread($this->socket, $length - strlen($data));
            if ($chunk === false || $chunk === '') {
                $meta = stream_get_meta_data($this->socket);
                throw new RuntimeException(!empty($meta['timed_out'])
                    ? 'Timeout membaca balasan RouterOS.'
                    : 'Koneksi RouterOS terputus.');
            }
            $data .= $chunk;
        }
        return $data;
    }
}
