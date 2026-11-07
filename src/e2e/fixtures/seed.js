// Values TestEnvironmentSeeder (database/seeders/TestEnvironmentSeeder.php)
// writes. Keep the two in step.

export const BROOKLYN = {
  id: 1006,
  name: 'Brooklyn Area Service',
  option: 'Brooklyn Area Service (1006) / Greater New York Region (1)',
  row: 'Brooklyn Area Service (1006)',
};

export const MANHATTAN = {
  id: 1008,
  name: 'Manhattan Area Service',
  row: 'Manhattan Area Service (1008)',
};

// Service-body admin with rights to Brooklyn only.
export const BROOKLYN_ADMIN = { username: 'brooklyn', password: 'brooklyn', name: 'Brooklyn Admin' };

// Call records (from numbers) by service body.
export const CALLS = {
  brooklynVolunteerCall: '+15005550101',
  brooklynSms: '+15005550102',
  manhattanMeetingCall: '+15005550201',
};

export const VOICEMAILS = [
  { callsid: 'CAe2e0000000000000000000000000vm1', recording: 'REe2e00000000000000000000000000001', from: '+15005550111', pin: '1111111' },
  { callsid: 'CAe2e0000000000000000000000000vm2', recording: 'REe2e00000000000000000000000000002', from: '+15005550112', pin: '2222222' },
  { callsid: 'CAe2e0000000000000000000000000vm3', recording: 'REe2e00000000000000000000000000003', from: '+15005550113', pin: '3333333' },
];

export const SCHEDULE_VOLUNTEER = { name: 'E2E Schedule Volunteer', phone: '555-0100' };
