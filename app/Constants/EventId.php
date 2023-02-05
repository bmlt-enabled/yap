<?php
namespace App\Constants;

class EventId
{
    const VOLUNTEER_SEARCH = 1;
    const MEETING_SEARCH = 2;
    const JFT_LOOKUP = 3;
    const VOICEMAIL = 4;
    const VOLUNTEER_DIALED = 5;
    const VOLUNTEER_ANSWERED = 6;
    const VOLUNTEER_REJECTED = 7;
    const VOLUNTEER_NOANSWER = 8;
    const VOLUNTEER_ANSWERED_BUT_CALLER_HUP = 9;
    const CALLER_IN_CONFERENCE = 10;
    const VOLUNTEER_HUP = 11;
    const VOLUNTEER_IN_CONFERENCE = 12;
    const CALLER_HUP = 13;
    const MEETING_SEARCH_LOCATION_GATHERED = 14;
    const HELPLINE_ROUTE = 15;
    const VOICEMAIL_PLAYBACK = 16; // Dead feature
    const DIALBACK = 17;
    const PROVINCE_LOOKUP_LIST = 18;
    const MEETING_SEARCH_SMS = 19;
    const VOLUNTEER_SEARCH_SMS = 20;
    const JFT_LOOKUP_SMS = 21;
    const SMS_BLACKHOLED = 22;

    const SPAD_LOOKUP = 23;
    const SPAD_LOOKUP_SMS = 24;

    public static function getEventById($id)
    {
        switch ($id) {
            case self::VOLUNTEER_SEARCH:
                return "Volunteer Search";
            case self::MEETING_SEARCH:
                return "Meeting Search";
            case self::JFT_LOOKUP:
                return "JFT Lookup";
            case self::VOICEMAIL:
                return "Voicemail";
            case self::VOLUNTEER_DIALED:
                return "Volunteer Dialed";
            case self::VOLUNTEER_ANSWERED:
                return "Volunteer Answered";
            case self::VOLUNTEER_REJECTED:
                return "Volunteer Rejected Call";
            case self::VOLUNTEER_NOANSWER:
                return "Volunteer No Answer";
            case self::VOLUNTEER_ANSWERED_BUT_CALLER_HUP:
                return "Volunteer Answered but Caller Hungup";
            case self::CALLER_IN_CONFERENCE:
                return "Caller Waiting for Volunteer";
            case self::VOLUNTEER_HUP:
                return "Volunteer Hungup";
            case self::VOLUNTEER_IN_CONFERENCE:
                return "Volunteer Connected To Caller";
            case self::CALLER_HUP:
                return "Caller Hungup";
            case self::MEETING_SEARCH_LOCATION_GATHERED:
                return "Meeting Search Location Gathered";
            case self::HELPLINE_ROUTE:
                return "Helpline Route";
            case self::VOICEMAIL_PLAYBACK:
                return "Voicemail Playback";
            case self::DIALBACK:
                return "Dialback";
            case self::PROVINCE_LOOKUP_LIST:
                return "Province Lookup List";
            case self::MEETING_SEARCH_SMS:
                return "Meeting Search via SMS";
            case self::VOLUNTEER_SEARCH_SMS:
                return "Volunteer Search via SMS";
            case self::JFT_LOOKUP_SMS:
                return "JFT Lookup via SMS";
            case self::SMS_BLACKHOLED:
                return "SMS Blackholed";
            case self::SPAD_LOOKUP:
                return "SPAD Lookup";
            case self::SPAD_LOOKUP_SMS:
                return "SPAD Lookup via SMS";
        }
    }
}
