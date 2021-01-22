<?php

use App\FlatIcon\Parser;
use Kuvardin\TelegramBotsApi\Bot;
use Kuvardin\TelegramBotsApi\Types\InlineKeyboardButton;
use Kuvardin\TelegramBotsApi\Types\InlineKeyboardMarkup;
use Kuvardin\TelegramBotsApi\Types\KeyboardButton;
use Kuvardin\TelegramBotsApi\Types\Update;
use GuzzleHttp\Client;

error_reporting(E_ALL | E_STRICT);
ini_set('error_reporting', E_ALL | E_STRICT);
date_default_timezone_set('Asia/Almaty');

define('START_TIME', (float)microtime(true));
define('START_MEMORY', memory_get_usage());
define('ROOT_DIR', __DIR__);
define('IS_LOCAL', !array_key_exists('HTTP_HOST', $_SERVER));
define('IS_CLI', !empty($argv));
define('ACTIONS_DIR', ROOT_DIR . '/system/actions');
define('API_DIR', ROOT_DIR . '/system/api');
define('LOGS_DIR', ROOT_DIR . '/logs');
define('CACHE_DIR', ROOT_DIR . '/system/cache');
define('CLASSES_DIR', ROOT_DIR . '/system/classes');
define('COOKIES_DIR', ROOT_DIR . '/system/cookies');
define('FONTS_DIR', ROOT_DIR . '/fonts');
define('IMAGES_DIR', ROOT_DIR . '/images');
define('INCLFILES_DIR', ROOT_DIR . '/system/inclfiles');
define('LANGUAGES_DIR', ROOT_DIR . '/system/languages');
define('MEDIA_DIR', ROOT_DIR . '/../httpdocs/media');
define('FILES_DIR', ROOT_DIR . '/files');
define('PAGES_DIR', ROOT_DIR . '/system/pages');
define('TELEGRAM_DIR', ROOT_DIR . '/system/telegram');
define('TEMPLATES_DIR', ROOT_DIR . '/system/templates');
define('THEMES_DIR', ROOT_DIR . '/themes');
define('TEMP_DIR', ROOT_DIR . '/system/temp');

try {
    set_error_handler(static function (int $code, string $message, string $file_name, string $line, array $vars) {
        throw new Error("Handled by error handler\nError #$code: $message on $file_name:$line", $code);
    });

    register_shutdown_function(static function () {
        $e = error_get_last();
        if ($e !== null) {
            $error_text = "Handled by shutdown function\n" .
                "Fatal error #{$e['type']}: {$e['message']} on {$e['file']}:{$e['line']}";
            throw new Error($error_text);
        }
    });


    /**
     * @var Composer\Autoload\ClassLoader $loader
     */
    $loader = require ROOT_DIR . '/vendor/autoload.php';
    $loader->addPsr4('App\\', CLASSES_DIR . '/App');

    $bot = new Bot('1262124680:AAF6b3ZZ6mU45DAbNi79gm6UdMfsWvsrLac', 'search_svg_bot');

    $bot->deleteWebhook()->sendRequest();
    $admin_id = 427917307;


    $sleep = (int)($argv[1] ?? 10);
    $offset = null;

    $users = [];

    $offset = (int)file_get_contents('max_update_id.txt');

    while (true) {
        $updates = $bot->getUpdates($offset)->sendRequest();
        if ($updates === []) {
            echo "None received\n";
        } else {
            foreach ($updates as $update) {
                switch ($update->getAction()) {
                    case Update::ACT_MESSAGE:
                        $message = $update->message;
                        if ($message->from !== null && !isset($users[$message->from->id])) {
                            $users[$message->from->id] = $message->from;
                            $bot->sendMessage($admin_id, "{$message->from->getFullName(true)}  started bot")
                                ->sendRequest();
                        }

                        echo "\t{$message->chat->id}. {$message->chat->first_name} [{$message->chat->getType()}]\n";
                        if ($message->text !== null) {
                            echo "\t{$message->text}\n";
                        }
                        

                        if ($message->from !== null && $message->from->id === 884474465) {
                            $bot
                                ->sendPhoto($message->from->id, 'AgACAgQAAxkDAAIJnl_pd-433z-Uxwc4R-xSw-H5u727AAI-qTEb5Wh1U7EYguQyNQLk0GfDGgAEAQADAgADeQADi4wHAAEeBA')
                                ->setReplyToMessageWithId($message->message_id)
                                ->sendRequest();
                            break;
                        }

                        if ($message->text !== null) {
                            $client = new Client();
                            $parser = new Parser($client);
                            $icons = $parser->search($message->text);

                            $bot->sendMessage($admin_id, "{$message->from->getFullName(true)}  searched {$message->text}")
                                ->sendRequest();

                            if ($icons === null) {
                                $bot->sendMessage($message->chat->id, 'Icons not found')
                                    ->setReplyToMessageWithId($message->message_id)
                                    ->sendRequest();
                            } else {
                                $free_exists = false;
                                $sent_number = 0;
                                foreach ($icons as $key => $icon) {
                                    if ($icon['data-premium'] === '1') {
                                        continue;
                                    }

                                    $free_exists = true;
                                    $png_image = str_replace(['www.flaticon.com/svg/static/icons/svg', '.svg'],
                                        ['image.flaticon.com/icons/png/128', '.png'], $icon['data-icon_src']);
                                    $caption = "{$icon['data-name']} - {$icon['data-category_name']}";
                                    $bot->sendDocument($message->chat->id, $png_image, $caption)
                                        ->setInlineKeyboardMarkup(InlineKeyboardMarkup::make([
                                            [
                                                InlineKeyboardButton::makeWithUrl('Download SVG', $icon['data-icon_src'])
                                            ],
                                        ]))
                                        ->sendRequest();
                                    if (++$sent_number === 3) break;
                                }

                                if (!$free_exists) {
                                    $bot->sendMessage($message->chat->id, 'Free icons not found')
                                        ->setReplyToMessageWithId($message->message_id)
                                        ->sendRequest();
                                }
                            }
                        }
                        break;

                    case Update::ACT_CALLBACK_QUERY:
                        $bot->answerCallbackQuery($update->callback_query->id, $update->callback_query->data)->sendRequest();
                        break;
                }

                echo "Received update #{$update->update_id}: {$update->getAction()}\n";
                $offset = $update->update_id + 1;

                file_put_contents('max_update_id.txt', $offset);
            }
        }

        sleep($sleep);
    }
} catch (Throwable $exception) {
    echo $exception, PHP_EOL;
}