<?php

namespace Ngankt2\FilamentChatAgent\Models;

use Illuminate\Database\Eloquent\Model;

class ChatQuestionTemplate extends Model
{
    protected $table = 'chat_question_templates';
    protected $fillable = ['question', 'embedding', 'function_name'];
    protected $casts = ['embedding' => 'array'];
}