<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use LINE\LINEBot;
use LINE\LINEBot\Constant\HTTPHeader;
use LINE\LINEBot\Event\FollowEvent;
use LINE\LINEBot\Event\MessageEvent\TextMessage;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use App\Models\Member;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

$httpClient = new CurlHTTPClient($_ENV['LINE_CHANNEL_ACCESS_TOKEN']);
$bot = new LINEBot($httpClient, ['channelSecret' => $_ENV['LINE_CHANNEL_SECRET']]);

Route::post('/webhook', function (Request $request) use ($bot) {
    Log::debug($request);

    $signature = $request->header(HTTPHeader::LINE_SIGNATURE);
    if (empty($signature)) {
        return abort(400);
    }

    $events = $bot->parseEventRequest($request->getContent(), $signature);

    collect($events)->each(function ($event) use ($bot) {
        if ($event instanceof TextMessage) {
            return $bot->replyText($event->getReplyToken(), $event->getText());
        }
        if ($event instanceof FollowEvent) {
            $user_id = $event->getUserId();
            Log::debug($user_id);

            $member = Member::find($user_id);
            Log::debug($member);

            if (empty($member)) {
                $barcode_id = strval(rand(1000000000, 9999999999));
                Log::debug($barcode_id);

                $member = Member::firstOrCreate([
                    'user_id' => $user_id,
                    'barcode_id' => $barcode_id,
                ]);
                Log::debug('Member is created.');
                Log::debug($member);
            }

            return $bot->replyText($event->getReplyToken(), '[bot]友達登録されたよ！');
        }
    });

    return 'ok!';
});

Route::get('/members/{member_id}', function ($member_id) {
    $member = Member::find($member_id);

    if (empty($member)) {
        return abort(404);
    }

    return $member;
});
