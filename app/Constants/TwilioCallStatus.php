<?php

namespace App\Constants;

class TwilioCallStatus
{
    const QUEUED = "queued";
    const RINGING = "ringing";
    const INPROGRESS = "in-progress";
    const CANCELED = "canceled";
    const COMPLETED = "completed";
    const FAILED = "failed";
    const BUSY = "busy";
    const NOANSWER = "no-answer";
}
