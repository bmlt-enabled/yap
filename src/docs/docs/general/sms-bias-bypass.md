# SMS Bias Bypass

---

The `sms_bias_bypass` setting (Settings page or `config.php`) disables province/state biasing when Yap resolves location from SMS or speech input.

## Default behavior

Without bypass, Yap may append a province or state hint when geocoding caller input—for example from Twilio's `ToState` metadata, or from [Tollfree Province Bias](./tollfree-province-bias) / `toll_province_bias`. That improves accuracy for toll-free and ambiguous addresses.

## When to enable bypass

Set `sms_bias_bypass` to `true` when:

- Callers text or speak locations that should **not** be forced toward a default state or province.
- Toll-free or toll-number bias is skewing meeting search or helpline routing for your region.

When enabled, geocoding uses the caller's text alone without an automatic province suffix.

## Configuration

In `config.php`:

```php
static $sms_bias_bypass = true;
```

Or set **Sms Bias Bypass** on the admin **Settings** page. Service-body overrides follow the usual [configuration precedence](./configuration-precedence) rules.

## Related topics

- [Tollfree Province Bias](./tollfree-province-bias) — explicit bias settings
- [Location Lookup Bias](./location-lookup-bias) — Google geocoding region hints
