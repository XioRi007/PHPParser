<?php

declare(strict_types=1);

namespace Core\Database;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Facades\DB;

class MyDB
{
    /**
     * @var  string  $answersTable Name of the answers table
     */
    private static string $answersTable = "answers";

    /**
     * @var  string  $questionsTable Name of the questions table
     */
    private static string $questionsTable = "questions";

    /**
     * @var  string  $answerQuestionsTable Name of the answer-question mapping table
     */
    private static string $answerQuestionsTable = "answer_question";

    /**
     * Establishes a connection to the database using the configuration from environment variables.
     * @return  Capsule  The Capsule instance that was created.
     */
    public static function connect(): Capsule
    {
        $capsule = new Capsule();
        $capsule->addConnection([
            'driver' => $_ENV['DB_CONNECTION'],
            'host' => $_ENV['DB_HOST'],
            'database' => $_ENV['DB_DATABASE'],
            'username' => $_ENV['DB_USERNAME'],
            'password' => $_ENV['DB_PASSWORD'],
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        return $capsule;
    }

    /**
     * Closes the connection to the database.
     * @return  void
     */
    public static function close(): void
    {
        DB::disconnect('parser');
    }

    /**
     * Creates the answers table in the database.
     * @return  void
     */
    public static function createAnswersTable(): void
    {
        Capsule::schema()->create(static::$answersTable, function ($table) {
            $table->id();
            $table->string('text', 255)->unique();
            $table->integer('length');
        });
    }

    /**
     * Creates the questions table in the database.
     * @return  void
     */
    public static function createQuestionsTable(): void
    {
        Capsule::schema()->create(static::$questionsTable, function ($table) {
            $table->id();
            $table->string('text', 255)->unique();
        });
    }

    /**
     * Creates the answer-question mapping table in the database.
     * @return  void
     */
    public static function createAnswerQuestionTable(): void
    {
        Capsule::schema()->create(static::$answerQuestionsTable, function ($table) {
            $table->id();
            $table->foreignId('answer_id')->constrained()->onDelete('cascade');
            $table->foreignId('question_id')->constrained()->onDelete('cascade');
        });
    }

    /**
     * Checks if the required database tables exist and creates them if they don't.
     * @return  void
     */
    public static function migrate(): void
    {
        if(!Capsule::schema()->hasTable(static::$answersTable)) {
            static::createAnswersTable();
        }
        if(!Capsule::schema()->hasTable(static::$questionsTable)) {
            static::createQuestionsTable();
        }
        if(!Capsule::schema()->hasTable(static::$answerQuestionsTable)) {
            static::createAnswerQuestionTable();
        }
    }

    /**
     * Drops and recreates all the required database tables.
     * @return  void
     */
    public static function reMigrate(): void
    {
        Capsule::schema()->dropIfExists(static::$answerQuestionsTable);
        Capsule::schema()->dropIfExists(static::$answersTable);
        Capsule::schema()->dropIfExists(static::$questionsTable);
        static::createAnswersTable();
        static::createQuestionsTable();
        static::createAnswerQuestionTable();
    }
}
