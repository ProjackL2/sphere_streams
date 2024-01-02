# SphereWeb CMS: Streams Plugin

Online streams plugin for SphereWeb CMS.

# Instalation

Add to `src/component/plugins` dir as `sphere_streams` folder. Plugin will be available by path `/streams/view`

# How to use

Configure `config.php` file.

## Twitch
1. Get access token and client id for your [twich app](https://dev.twitch.tv/docs/authentication/register-app/).
2. Setup credentials to config.php
```
        "twitch" => [
            "access_token" => "YOUR_ACCESS_TOKEN",
            "client_id" => "YOUR_CLIENT_ID",
```
3. Add streamers to list
```
            "streamers" => [
                [
                    "username" => "TWITCH_USERNAME_FROM_URL",
                    "sphere_user_id" => 0 // used to give rewards
                ],
```

## Youtube
1. Get api key for your [youtube project](https://blog.hubspot.com/website/how-to-get-youtube-api-key/).
2. Setup credentials to config.php
```
        "youtube" => [
            "api_key" => "YOUR_API_KEY",
```
3. Add streamers to list. You can get `chnnel_id` using this [instruction](https://stackoverflow.com/a/76285153)
```
            "streamers" => [
                [
                    "username" => "NAME_THAT_WILL_BE_IN_WEB",
                    "channel_id" => "YOUTUBE_CHANNEL_ID",
                    "sphere_user_id" => 6
                ],
```

## Trovo
1. Get client id for your [trovo app](https://developer.trovo.live/docs/APIs.html#_2-register-your-application).
2. Setup credentials to config.php
```
        "trovo" => [
            "client_id" => "YOUR_CLIENT_ID",
```
3. Add streamers to list
```
            "streamers" => [
                [
                    "username" => "TROVO_USERNAME_FROM_URL",
                    "sphere_user_id" => 8
                ],
```

# Rewards

Configure rewards for streaming in `config.php`
```
[
    "id" => 0, // unique id for reward record
    "server_id" => 1, // server id for reward apply
    "threshold" => [
        "duration_min" => 60, // min time to get reward(in mins)
        "viewers" => 5, // min viewvers to get reward
    ],
    // user will get rewards into sphere inventory
    "items" => [
        [
            "id" => 57,
            "count" => 1000,
            "enchant" => 0,
        ],
        [
            "id" => 4357,
            "count" => 1,
            "enchant" => 0,
        ],
    ]
],
```

If streamer pass reward threshold he will receive reward items to SphwereWeb inventory. User and admins will be notified about it throught the `/user/notification` tab.


# Cron job

Streaming service API has many restrictions and coz of that we use caching as main data source.

Cron used as schedule mechanism for cache update and rewards processing. *To make it all works you MUST setup this.*

## Add cron job

1. Type the following command to edit the crontab file:
```
crontab -e
```
2. This will open the crontab file for editing. Add the following line to the file:

```
* * * * * php /path/to/sphere/src/component/plugins/sphere_streams/cronjob.php > /dev/null 2>&1
```
example:
```
* * * * * php /home/root/web/l2.ru/public_html/src/component/plugins/sphere_streams/cronjob.php > /dev/null 2>&1
```
3. Save the file and exit the editor.

After saving the crontab file, the cron job will be scheduled and start running automatically every minute.
