<?php

namespace Ofey\Logan22\component\plugins\sphere_streams;

use Ofey\Logan22\component\redirect;
use Ofey\Logan22\controller\page\error;
use Ofey\Logan22\model\admin\validation;
use Ofey\Logan22\template\tpl;

class streams {

    public static function show_streams_draw() {
        validation::user_protection();

        $settings = include __DIR__ . "/settings.php";
        if (!$settings['PLUGIN_ENABLE']) {
           redirect::location("/main");
        }

        $streams_data = streams::load_streams();
        if (empty($streams_data)) {
            error::error404("Нет доступных стримов");
        }

        tpl::addVar("streamers_info", $streams_data);
        tpl::addVar("domain", $_SERVER['HTTP_HOST']);
        tpl::displayPlugin("/sphere_streams/tpl/show.html");
    }

    public static function request_streams_list() {
        $settings = include __DIR__ . "/settings.php";
        if ($settings['PLUGIN_ENABLE']) {
            echo json_encode(streams::load_streams());
        }
    }

    public static function request_cache_update() {
        $settings = include __DIR__ . "/settings.php";
        if ($settings['PLUGIN_ENABLE']) {
            streams::update_streams_cache();
        }
    }

    private static function update_streams_cache() {
        $config = include __DIR__ . "/config.php";

        $now = time();

        $streams_config = $config['streams'];
        $show_offline = $streams_config['show_offline'];

        $twitch = $streams_config['twitch'];
        $twitch_cache_filename = $twitch['cache_file_name'];
        $twitch_cache_duration = $twitch['cache_duration'];
        if (!file_exists($twitch_cache_filename) || $now - filemtime($twitch_cache_filename) >= $twitch_cache_duration) {
            $twitch_info = streams::load_twitch_streams($twitch['streamers'], $twitch['client_id'], $twitch['access_token'], $now, $show_offline);
            usort($twitch_info, function($a, $b) { return $b['viewers'] - $a['viewers']; });
            file_put_contents($twitch_cache_filename, json_encode($twitch_info));
        }

        $youtube = $streams_config['youtube'];
        $youtube_cache_filename = $youtube['cache_file_name'];
        $youtube_cache_duration = $youtube['cache_duration'];
        if (!file_exists($youtube_cache_filename) || $now - filemtime($youtube_cache_filename) >= $youtube_cache_duration) {
            $youtube_info = streams::load_youtube_streams($youtube['streamers'], $youtube['api_key'], $now, $show_offline);
            usort($youtube_info, function($a, $b) { return $b['viewers'] - $a['viewers']; });
            file_put_contents($youtube_cache_filename, json_encode($youtube_info));
        }

        $trovo = $streams_config['trovo'];
        $trovo_cache_filename = $trovo['cache_file_name'];
        $trovo_cache_duration = $trovo['cache_duration'];
        if (!file_exists($trovo_cache_filename) || $now - filemtime($trovo_cache_filename) >= $trovo_cache_duration) {
            $trovo_info = streams::load_trovo_streams($trovo['streamers'], $trovo['client_id'], $now, $show_offline);
            usort($trovo_info, function($a, $b) { return $b['viewers'] - $a['viewers']; });
            file_put_contents($trovo_cache_filename, json_encode($trovo_info));
        }
    }

    public static function load_streams() {
        $config = include __DIR__ . "/config.php";
        $streams_config = $config['streams'];

        $twitch = $streams_config['twitch'];
        $twitch_cache_filename = $twitch['cache_file_name'];
        $youtube = $streams_config['youtube'];
        $youtube_cache_filename = $youtube['cache_file_name'];
        $trovo = $streams_config['trovo'];
        $trovo_cache_filename = $trovo['cache_file_name'];

        if (!file_exists($twitch_cache_filename) || !file_exists($youtube_cache_filename) || !file_exists($trovo_cache_filename)) {
            streams::update_streams_cache();
        }

        $twitch_info = json_decode(file_get_contents($twitch_cache_filename), true);
        $youtube_info = json_decode(file_get_contents($youtube_cache_filename), true);
        $trovo_info = json_decode(file_get_contents($trovo_cache_filename), true);

        $info = array_merge($twitch_info, $youtube_info, $trovo_info);
        usort($info, function($a, $b) {
            return $b['viewers'] - $a['viewers'];
        });

        return $info;
    }

    private static function load_twitch_streams($list, $client_id, $access_token, $now, $show_offline) {
        $info = [];
        foreach ($list as $stream) {
            $username = $stream['username'];
            $sphere_user_id = $stream['sphere_user_id'];
            $auth_headers = ['Client-ID: ' . $client_id, 'Authorization: Bearer ' . $access_token];

            $user_url = 'https://api.twitch.tv/helix/users?login=' . $username;
            $user_response = streams::request($user_url, $auth_headers);
            $user_data = json_decode($user_response, true);
            
            $profile_image_url = $user_data['data'][0]['profile_image_url'];

            $stream_url = 'https://api.twitch.tv/helix/streams?user_login=' . $username;
            $stream_response = streams::request($stream_url, $auth_headers);
            $stream_response_data = json_decode($stream_response, true);

            if (isset($stream_response_data['data'][0])) {
                $stream_data = $stream_response_data['data'][0];
        
                $stream_start = strtotime($stream_data['started_at']);
                $stream_duration_sec = $now - $stream_start;
                $stream_duration_formatted = gmdate("H:i:s", $stream_duration_sec);

                $info[] = streams::make_live_info('twitch',
                                                  $username,
                                                  $sphere_user_id,
                                                  $username,
                                                  $stream_data['title'],
                                                  $stream_data['viewer_count'],
                                                  $profile_image_url,
                                                  $stream_data['type'],
                                                  $stream_duration_sec / 60,
                                                  $stream_duration_formatted);
            } else {
                if ($show_offline) {
                    $info[] = streams::make_offline_info('twitch', $username, $sphere_user_id, $profile_image_url);
                }
            }
        }
        return $info;
    }

    private static function load_youtube_streams($list, $api_key, $now, $show_offline) {
        $info = [];
        foreach ($list as $stream) {
            $username = $stream['username'];
            $channel_id = $stream['channel_id'];
            $sphere_user_id = $stream['sphere_user_id'];

            // get live video info
            $live_url = "https://www.googleapis.com/youtube/v3/search?part=snippet&channelId=" . $channel_id . "&eventType=live&type=video&key=" . $api_key;
            $live_response = streams::request($live_url);
            $live_data = json_decode($live_response, true);
            if (!isset($live_data['items']) || !isset($live_data['items'][0])) {
                continue;
            }

            $live_video_id = $live_data['items'][0]['id']['videoId'];
            $live_title = $live_data['items'][0]['snippet']['title'];
            $live_image_url = $live_data['items'][0]['snippet']['thumbnails']['default']['url'];

            // get current live video stats
            $video_url = "https://www.googleapis.com/youtube/v3/videos?part=liveStreamingDetails&id=" . $live_video_id . "&key=" . $api_key;
            $video_response = streams::request($video_url);
            $video_data = json_decode($video_response, true);
            
            if (isset($video_data['items']) && isset($video_data['items'][0])) {
                $stream_live_data = $video_data['items'][0]['liveStreamingDetails'];
                
                $stream_start = strtotime($stream_live_data['actualStartTime']);
                $stream_duration_sec = $now - $stream_start;
                $stream_duration_formatted = gmdate("H:i:s", $stream_duration_sec);

                $info[] = streams::make_live_info('youtube',
                                                  $live_video_id,
                                                  $sphere_user_id,
                                                  $username,
                                                  $live_title,
                                                  $stream_live_data['concurrentViewers'],
                                                  $live_image_url,
                                                  'live',
                                                  $stream_duration_sec / 60,
                                                  $stream_duration_formatted);
            } else {
                if ($show_offline) {
                    $info[] = streams::make_offline_info('youtube', $username, $sphere_user_id, $live_image_url);
                }
            }
        }
        return $info;
    }

    private static function load_trovo_streams($list, $client_id, $now, $show_offline) {
        $info = [];
        foreach ($list as $stream) {
            $username = $stream['username'];
            $sphere_user_id = $stream['sphere_user_id'];

            $auth_headers = ['Client-ID: ' . $client_id];

            $stream_url = "https://open-api.trovo.live/openplatform/channels/id";
            $stream_url_payload = json_encode(['username' => $username]);
            $stream_response = streams::request($stream_url, $auth_headers, $stream_url_payload);
            $stream_response_data = json_decode($stream_response, true);

            if (!isset($stream_response_data['current_viewers'])) {
                continue;
            }

            $is_live = $stream_response_data['is_live'];
            $profile_image_url = $stream_response_data['profile_pic'];
            if ($is_live) {
                $stream_data = $stream_response_data;

                $stream_start = strtotime($stream_data['started_at']);
                $stream_duration_sec = $now - $stream_start;
                $stream_duration_formatted = gmdate("H:i:s", $stream_duration_sec);

                $info[] = streams::make_live_info('trovo',
                                                  $username,
                                                  $sphere_user_id,
                                                  $username,
                                                  $stream_data['live_title'],
                                                  $stream_data['current_viewers'],
                                                  $profile_image_url,
                                                  'live',
                                                  $stream_duration_sec / 60,
                                                  $stream_duration_formatted);
            } else {
                if ($show_offline) {
                    $info[] = streams::make_offline_info('trovo', $username, $sphere_user_id, $profile_image_url);
                }
            }
        }
        return $info;
    }

    private static function make_live_info($platform, $stream_id, $sphere_user_id, $username, $title, $viewers_cout, $image_url, $type, $stream_duration_min, $formated_duration) {
        return [
            'platform' => $platform,
            'stream_id' => $stream_id,
            'sphere_user_id' => $sphere_user_id,
            'user_login' => $username,
            'title' => $title,
            'viewers' => $viewers_cout,
            'thumbnail_url' => $image_url,
            'type' => $type,
            'duration_min' => $stream_duration_min,
            'duration' => $formated_duration
        ];
    }

    private static function make_offline_info($platform, $username, $login, $image_url) {
        return [
            'platform' => $platform,
            'user_login' => $username,
            'sphere_user_id' => $login,
            'title' => $username,
            'viewers' => 0,
            'thumbnail_url' => $image_url,
            'type' => 'offline',
            'duration_min' => 0,
            'duration' => '00:00:00'
        ];
    }

    private static function request($url, $headers = [], $payload = null) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($payload) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Accept: application/json',
                'Content-Type: application/json',
                'Content-Length: ' . strlen($payload)
            ));
        }
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
}