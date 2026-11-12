<?php

namespace App\Support;

class YouTubeUrl
{
    public static function videoId(string $url): ?string
    {
        if (preg_match('~[\s\x00-\x1f\x7f<>"\\\\]~', $url)) {
            return null;
        }

        $parts = parse_url($url);
        if ($parts === false
            || ! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])) {
            return null;
        }

        $host = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '';
        $isYouTube = in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true);
        $id = null;

        if ($host === 'youtu.be') {
            $id = substr($path, 1);
        } elseif ($isYouTube && $path === '/watch') {
            foreach (explode('&', $parts['query'] ?? '') as $parameter) {
                [$name, $value] = array_pad(explode('=', $parameter, 2), 2, '');
                if (urldecode($name) !== 'v') {
                    continue;
                }
                if ($id !== null) {
                    return null;
                }
                $id = urldecode($value);
            }
        } elseif ($isYouTube && preg_match('~^/(?:embed|shorts|live)/([^/]+)$~D', $path, $matches)) {
            $id = $matches[1];
        } elseif (in_array($host, ['youtube-nocookie.com', 'www.youtube-nocookie.com'], true)
            && preg_match('~^/embed/([^/]+)$~D', $path, $matches)) {
            $id = $matches[1];
        }

        return $id !== null && preg_match('~^[A-Za-z0-9_-]{11}$~D', $id) ? $id : null;
    }
}
