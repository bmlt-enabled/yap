INSERT INTO sessions (`callsid`, `pin`)
SELECT callsid, ROUND(RAND() * 10000000) FROM records;
