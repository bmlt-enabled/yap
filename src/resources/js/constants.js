// constants.js
export const VOLUNTEER_ROUTING_OPTIONS = {
    HELPLINE_FIELD: "helpline_field",
    VOLUNTEERS: "volunteers",
    VOLUNTEERS_REDIRECT: "volunteers_redirect",
    VOLUNTEERS_AND_SMS: "volunteers_and_sms",
};

export const CALL_STRATEGY = {
    LINEAR_LOOP_FOREVER: "0",
    LINEAR_CYCLE_ONCE_THEN_VOICEMAIL: "1",
    RANDOM_LOOP_FOREVER: "2",
    BLASTING_THEN_VOICEMAIL: "3",
    RANDOM_LOOP_ONCE_THEN_VOICEMAIL: "4",
};

export const SMS_STRATEGY = {
    RANDOM: "2",
    BLAST: "3",
};
