<?php

declare(strict_types=1);

namespace Core\Queue;

use Core\Models\Task;
use Core\Queue\Exceptions\NoMessageException;
use Exception;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Monolog\Logger;

class MysqlQueue implements IQueue
{
    private string $queueName;
    private Logger $logger;
    public function __construct(string $queueName, Logger $logger)
    {
        $this->queueName = $queueName;
        $this->logger = $logger;
    }

    public function createIfNotExists(): void
    {
        if(!Capsule::schema()->hasTable($this->queueName)) {
            Capsule::schema()->create($this->queueName, function ($table) {
                $table->string('id')->primary();
                $table->text('data');
                $table->string('status', 20)->default('created');
                $table->integer('tries')->default(0);
            });
        }
    }

    public function reMigrate(): void
    {
        if(Capsule::schema()->hasTable($this->queueName)) {
            Capsule::schema()->drop($this->queueName);
        }
        Capsule::schema()->create($this->queueName, function ($table) {
            $table->string('id')->primary();
            $table->text('data');
            $table->string('status', 20)->default('created');
            $table->integer('tries')->default(0);
        });
    }

    public function sendMessage(string $id, array $data): void
    {
        try {
            Task::create(['id'=>$id, 'data'=>$data]);
        } catch (QueryException $e) {
            $errorCode = $e->errorInfo[1];
            if ($errorCode == 1062) {
                $this->logger->error("Task with id $id already exists");
                return;
            }
            throw $e;
        }
    }
    public function receiveMessage(): Task
    {
        DB::beginTransaction();
        try {
            $msg = Task::whereIn('status', ['created', 'error'])->select()->lockForUpdate()->first();
            if($msg === null) {
                throw new NoMessageException();
            }
            Task::where('id', $msg->id)->update(['status'=>'processing']);
            DB::commit();
            return $msg;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
    public function returnProcessingMessagesToQueue(): void
    {
        Task::where('status', 'processing')->update(['status'=>'created']);
    }
    public function deleteMessage(string $id): void
    {
        Task::where('id', $id)->update(['status'=>'completed']);
    }
    public function isEmpty(): bool
    {
        $res = Capsule::table('queue')->whereNot('status', 'completed')->count();
        if($res > 0) {
            return false;
        }
        return true;
    }
}
