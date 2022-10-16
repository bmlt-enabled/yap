# Specialized Routing

---

## Gender Routing

Gender routing allows you to specify volunteers as Male or Female to enable callers to speak with volunteers of their gender selection.

This setting is configured from within each service body call handling.

If you want to add the option to allow callers to choose no preference for gender, add the following setting:

```static
static $gender_no_preference = true;
```

## Language

You can tag volunteers to zero or more languages (English is the default).  Be sure to set the list of languages you want your callers to be prompted with [here](../../general/language-options/).
