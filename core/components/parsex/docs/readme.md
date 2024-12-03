# ParseX

Fetch XML files from any location on the web and put its contents into placeholders.

- Authors: Guido Gallenkamp <guido.gallenkamp@posteo.de>, Thomas Jakobi <office@treehillstudio.com>
- License: GNU GPLv2

## Features

ParseX is an extra for MODX to fetch XML files from any location on the web and
put its contents into placeholders. It works with rss feeds, product lists, race
results or any nested XML format.

## Usage

```html
[[!ParseX?
&source=`https://modx.com/feeds/latest.rss`
&tpl=`xmlTpl`
&elements=`item`
&wrapper=`wrapX`
]]
```

In real words:

Please get that feed from modx.com and put all elements that are "item" into the
microtemplate "parsexTpl". And please also embed that mass of items into the
microtemplate "parsexWrapTpl". Set &debugmode=`true` to see all available
elements you can use from your specific feed.

The following snippet properties are available:

| Property        | Description                                                    | Default                           |
|-----------------|----------------------------------------------------------------|-----------------------------------|
| source          | Source URL for the XML feed                                    | https://modx.com/feeds/latest.rss |
| elements        | XML elements that should be collected for the output           | item                              |
| filter          | JSON encoded array of filters to filter the elements           |                                   |
| sortby          | JSON encoded array of sortby clauses to sort the elements      |                                   |
| tpl             | Template for one element                                       | parsexTpl                         |
| wrapper         | Wrapper template for all elements                              | parsexWrapTpl                     |
| outputSeparator | Optional string to separate each tpl instance                  | "\n"                              |
| limit           | Limits the number of elements returned (0 means no limit)      | 0                                 |
| debugmode       | If true output some debug informations after the normal output | No                                |
| cacheData       | Amount of time (in seconds) the XML feed will be cached        | Not cached                        |

The filter property could be filled with a JSON encoded array of filters similar
to xPDO where clauses:

```html
&filter=`{"requestparam:operator":"value"}`
```

The following filter operators are available: `!=`, `<>`, `>`, `>=`, `<`, `<=`, `LIKE`.
Multiple filter clauses are joined by `AND`.

The snippet ParseXFilter is available in the package to generate the filter
property on base of the value of request parameters.

The following snippet properties are available in this snippet:

| Property                                               | Description                                              | Default |
|--------------------------------------------------------|----------------------------------------------------------|---------|
| where                                                  | JSON encoded xPDO where clause                           |         |
| params                                                 | Comma separated list of request parameters. Each request |         |
| parameter could contain a query operator and query key |                                                          |         |
| prefix/suffix, appended colon separated, i.e.:         |                                                          |         |
| &params=`requestparam:operator:prefix:suffix`          |                                                          |         |

Example:

```html
&filter=`[[!ParseXFilter? &params=`requestparam:<>:prefix.:.suffix`]]`
```

will be translated to

```html
&filter=`{"prefix.requestparam.suffix:<>":"value"}`
```

## Documentation

https://github.com/Jako/ParseX/README.md

## GitHub Repository

https://github.com/Jako/ParseX
