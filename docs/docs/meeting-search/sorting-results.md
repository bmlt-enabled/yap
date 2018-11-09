---
layout: default
title: Sorting Results
nav_order: 9
parent: Meeting Search
---

## Sorting Results

---


By default the results will be sorted starting with today and then moving on to the next result.  If latitude and longitude are not used in the meeting query, the first meeting latitude and longitude will be the assumed timezone.

If you wanted to hardcode the sorting to start with another day you could use say for Wednesday use `static $meeting_result_sort = 4;`.

Or you can keep a more natural flow by setting it to 1 which would sort Sunday to Saturday.
