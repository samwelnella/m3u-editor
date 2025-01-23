<?php

namespace App\Jobs;

use App\Models\Channel;
use App\Models\Group;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessChannelImport implements ShouldQueue
{
    use Batchable, Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $playlistId,
        public string $batchNo,
        public array $channels
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->batch()->cancelled()) {
            // Determine if the batch has been cancelled...
            return;
        }

        // Get the groups
        $groups = Group::where('playlist_id', $this->playlistId)
            ->where('import_batch_no', $this->batchNo)
            ->select('id', 'name')
            ->get();

        // Link the channel groups to the channels
        foreach ($this->channels as $channel) {
            // Find/create the channel
            $model = Channel::firstOrCreate([
                'name' => $channel['name'],
                'group' => $channel['group'],
                'playlist_id' => $channel['playlist_id'],
                'user_id' => $channel['user_id'],
            ]);

            // Don't overwrite channel the logo if currently set
            if ($model->logo) {
                unset($channel['logo']);
            }

            // Update the channel with the group ID
            $model->update([
                ...$channel,
                'group_id' => $groups->where('name', $channel['group'])->first()?->id ?? null,
            ]);
        }
    }
}
