<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Forward
 *
 * @property int $id
 * @property int $from_chat_id
 * @property int $from_user_id
 * @property int $to_chat_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Forward newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Forward newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Forward query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Forward whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Forward whereFromChatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Forward whereFromUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Forward whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Forward whereToChatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Forward whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property int $from_message_id
 * @property int $to_message_id
 * @property int $from_media_group_id
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Forward whereFromMediaGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Forward whereFromMessageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Forward whereToMessageId($value)
 */
class Forward extends Model
{
    //
}
