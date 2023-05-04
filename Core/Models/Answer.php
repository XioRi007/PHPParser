<?php

namespace Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Answer extends Model
{
    /**
     * The attributes that are mass assignable.
     * @var  array
     */
    protected $fillable = ['id', 'text', 'length'];

    /**
     * The table associated with the model.
     * @var  string
     */
    protected $table = 'answers';

    /**
     * Indicates if the model should be timestamped.
     * @var  bool
     */
    public $timestamps = false;

    /**
     * The questions of the answer.
     */
    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class);
    }

    /**
     * Returns existing answer with this text or creates it and returns
     * @param  string  $answerText
     * @param  int  $length
     * @return  Answer
     */
    public function getOrCreate(string $answerText, int $length): Answer
    {
        $answer = Answer::where('text', $answerText)->first();
        if (!$answer) {
            $answer = Answer::create(['text' => $answerText, 'length'=>$length]);
        }
        return $answer;
    }
}
