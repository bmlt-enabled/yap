# Call Strategies

---

Call strategies control how Yap tries volunteers after a caller is routed to **Volunteers** helpline handling. Configure them under **Service Bodies → Configure → Call Handling → Call Strategy**.

These settings apply to voice outdial. SMS volunteer routing uses a separate **SMS Strategy** (Random or Blast) when **Volunteers and SMS** is selected.

For a video walkthrough, see [Call Blasting and other Call Strategies Explained](../videos/call-blasting-and-other-call-strategies-explained).

## Strategy reference

| Call strategy (admin UI) | Behavior |
|---|---|
| **Linear Loop Forever** | Dial volunteers in list order. After the last volunteer, loop back to the first and keep cycling until someone answers or the caller hangs up. |
| **Linear Cycle Once, Then Voicemail** | Dial in order once through the full list. If no one answers, send the caller to voicemail. |
| **Random Loop Forever** | Pick a random volunteer each attempt. Repeat until someone answers or the caller hangs up. |
| **Blasting, Then Voicemail** | Ring **all** active volunteers at the same time. The first person to answer takes the call. If nobody answers within the configured timeout, send the caller to voicemail. |
| **Random Loop Once, Then Voicemail** | Randomize the list once for this call, then dial through that order one time. If no one answers, go to voicemail. |

## Call timeout

**Call Timeout** (seconds) on the same dialog controls how long each outdial leg rings before Yap treats it as no-answer and moves to the next step (next volunteer, blasting completion, or voicemail).

## Blasting details

Blasting is useful when any on-shift volunteer can take the call and you want the shortest wait time.

- All numbers for active volunteers (including members expanded from an enabled [volunteer group](./volunteer-groups)) are dialed simultaneously.
- Twilio connects the caller to the first leg that answers.
- Unanswered blasting legs must complete before voicemail plays; Yap tracks no-answer callbacks so voicemail does not start prematurely.
- Blasting is also used internally for experimental WebChat volunteer SMS notification (see [Experimental Web Widgets](../miscellaneous/experimental-web-widgets)).

## Cycling and dial order

For linear strategies, drag volunteer cards on the **Volunteers** tab to set order. **Include Group** entries expand at dial time; the group card's position in the list matters for linear cycling.

**Random** strategies ignore the visual order when picking the next leg (except **Random Loop Once**, which shuffles once then follows that shuffled order).

## Voicemail fallback

Strategies that include "Then Voicemail" require voicemail to be enabled in call handling for that service body. **Primary Contact Number** receives SMS notification links when configured.

## Related topics

- [Volunteer routing](./volunteer-routing) — enabling routing and test calls
- [Volunteer groups](./volunteer-groups) — group cards on the dial list
- [Voicemail](./voicemail) — recordings and notifications
- [Checking call routing](./checking-call-routing) — troubleshooting
