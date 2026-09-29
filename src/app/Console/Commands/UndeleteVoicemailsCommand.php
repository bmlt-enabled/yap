<?php

namespace App\Console\Commands;

use App\Constants\EventId;
use App\Constants\EventStatusId;
use App\Models\EventStatus;
use Illuminate\Console\Command;

class UndeleteVoicemailsCommand extends Command
{
    protected $signature = 'yap:voicemail-undelete {--force : Restore hidden voicemails without prompting}';

    protected $description = 'Show voicemails that were only hidden in Yap so their Twilio recordings can be deleted';

    public function handle(): int
    {
        $query = EventStatus::query()
            ->where('event_id', EventId::VOICEMAIL)
            ->where('status', EventStatusId::VOICEMAIL_DELETED);

        $count = (clone $query)->count();
        if ($count === 0) {
            $this->info('No hidden voicemails to restore.');
            return self::SUCCESS;
        }

        $prompt = "Restore {$count} hidden voicemail(s) to the admin list?";
        if (!$this->option('force') && !$this->confirm($prompt)) {
            $this->info('Aborted.');
            return self::SUCCESS;
        }

        $restored = $query->delete();
        $this->info("Restored {$restored} voicemail(s). Delete them in the admin list to remove the Twilio recordings.");

        return self::SUCCESS;
    }
}
