<?php

namespace Ofey\Logan22\component\plugins\sphere_streams;

use Ofey\Logan22\model\db\sql;
use Ofey\Logan22\model\notification\notification;
use Ofey\Logan22\component\plugins\sphere_streams\streams;

class rewards {

    public static function check_rewards() {
        $settings = include __DIR__ . "/settings.php";
        if (!$settings['PLUGIN_ENABLE']) {
            exit;
        }

        $config = include __DIR__ . "/config.php";
        $reward_config = $config['rewards'];
        $rewarded_filename = $reward_config['rewards_filename'];

        // load last reward state from file
        $rewarded_data = [];
        if (file_exists($rewarded_filename)) {
            $rewarded_data = json_decode(file_get_contents($rewarded_filename), true);
        }

        // get info about current streams
        $streams = streams::load_streams();
        
        $now = time();

        $reward_records = $reward_config['records'];

        $reward_interval = $reward_config['rewards_reset_interval'];
        foreach ($streams as $stream) {
            $username = $stream['user_login'];
            $user_id = $stream['sphere_user_id'];
            $stream_duration_min = $stream['duration_min'];
            $stream_duration_formated = $stream['duration'];
            $viewers_count = $stream['viewers_count'];

            $need_notify = false;
            foreach ($reward_records as $record) {
                $record_id = $record['id'];
                $record_server_id = $record['server_id'];
                $record_items = $record['items'];
                $time_threshold = $record['threshold']['duration_min'];
                $viewers_threshold = $record['threshold']['viewers'];

                // check if pass current record threshold
                if ($stream_duration_min < $time_threshold || $viewers_count < $viewers_threshold) {
                    continue;
                }

                // check if already rewarded
                if (isset($rewarded_data[$user_id]) && isset($rewarded_data[$user_id][$record_id])) {
                    $last_rewarded_timestamp = $rewarded_data[$user_id][$record_id];
                    $time_passed = $now - $last_rewarded_timestamp;
                    if ($time_passed < $reward_interval) {
                        continue;
                    }
                }
                
                // reward streamer for current record
                foreach ($record_items as $item) {
                    rewards::addToInventory($user_id, $record_server_id, $item['id'], $item['count'], $item['enchant'] ?? 0, "Streaming Reward");
                }

                // init user info in rewards data and set timestamp for last reward
                if (!isset($rewarded_data[$user_id])) {
                    $rewarded_data[$user_id] = [];
                }
                $rewarded_data[$user_id][$record_id] = $now;

                $need_notify = true;
            }
        
            if ($need_notify) {
                notification::toAdmin("Streamer \"" . $username . "\" rewarded for " . $stream_duration_formated . " stream duration and " . $viewers_count . " online viewers", "/streams/view");
                notification::add($user_id, "You received a reward for " . $stream_duration_formated . " stream duration and " . $viewers_count . " online viewers", "/streams/view");
            }
        }

        // save current reward state to file
        file_put_contents($rewarded_filename, json_encode($rewarded_data));
    }

    public static function addToInventory($user_id, $server_id, $item_id, $count, $enchant, $phrase): void {
        $ins = sql::run("INSERT INTO `bonus` (`user_id`, `server_id`, `item_id`, `count`, `enchant`, `phrase`) VALUES (?, ?, ?, ?, ?, ?)", [
            $user_id, $server_id, $item_id, $count, $enchant, $phrase,
        ]);
        if (!$ins) {
            error_log("Failed to add to inventory item: user_id=" . $user_id . " server_id=" . $server_id . " item_id=" . $item_id);
        }
    }
}
