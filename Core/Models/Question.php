<?php

namespace Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Question extends Model
{
    /**
     * The attributes that are mass assignable.
     * @var  array
     */
    protected $fillable = ['id', 'text'];

    /**
     * The table associated with the model.
     * @var  string
     */
    protected $table = 'questions';

    /**
     * Indicates if the model should be timestamped.
     * @var  bool
     */
    public $timestamps = false;

    /**
     * The answers of the question.
     */
    public function answers(): BelongsToMany
    {
        return $this->belongsToMany(Answer::class);
    }

    /**
     * Returns existing question with this text or creates it and returns
     * @param  string  $questionText
     * @return  Question
     */
    public function getOrCreate(string $questionText): Question
    {
        $question = Question::where('text', $questionText)->first();
        if (!$question) {
            $question = Question::create(['text' => $questionText]);
        }
        return $question;
    }

    /**
     * If this question has answer
     * @param  Answer  $answer
     * @return  bool
     */
    public function hasAnswer(Answer $answer): bool
    {
        return Answer::where('id', $answer->id)
            ->whereHas('questions', function ($query) {
                $query->where('question_id', $this->id);
            })
            ->exists();
    }
}
