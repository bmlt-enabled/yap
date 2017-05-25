# yap

The purpose of yap is to be able to take the results of a BMLT root server and play it back through the telephone.  

Unlike it's predecessor bmlt-vox, this doesn't require any special infrastructure other than a server capable of delivering PHP over HTTP.

We are taking advantage of using Twilio which essentially handles all the VOIP parts.  You provision a number, set up an application, and point it your PHP server.

More setup information coming soon.


## query types

### by distance

```
?switcher=GetSearchResults&weekdays[]=5&weekdays[]=4&lat_val=35.541797706205&long_val=-78.64243553608&geo_width=-20
```