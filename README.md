# frisbee-php-api

A thin, **read-only** PHP client for the [Frisbee](https://frisbeecms.com) headless CMS Read API.

Content is authored in Frisbee and served over HTTP. This SDK fetches pages, lists, menus and the
site map for a single **distribution** (one published site/channel). It does not write, publish or
mutate anything.

> Building a site with an AI coding agent? Point it at [`AGENTS.md`](AGENTS.md) — a self-contained
> guide covering the full public surface plus how to design content-type schemas that match your
> templates field-for-field.

---

## Requirements

- PHP `^8.0.2`
- `guzzlehttp/guzzle`, `illuminate/collections`, `nesbot/carbon` (pulled in automatically)

Return types use `Illuminate\Support\Collection`, so it feels native in Laravel but works in any
PHP app.

### Version compatibility

Laravel 13 dropped Carbon 2. Version `2.x` widens the Carbon and Collections constraints to span
both eras, so it installs cleanly on everything from Laravel 9 to 13:

| SDK version | Laravel   | `nesbot/carbon` | Status                     |
|-------------|-----------|-----------------|----------------------------|
| `^2.0`      | 9 – 13    | `^2.62\|^3.0`   | Current                    |
| `^1.0`      | 9 – 12    | `^2.62`         | Maintenance (Carbon 2 only) |

There are no code changes between the lines beyond dependency constraints and `ListCall`'s
publish-date filtering — upgrading from `1.x` to `2.x` requires no changes to your integration.

## Install

```bash
composer require weare-awesome/frisbee-php-api
```

## Configuration

Three values, all from the environment — never hard-code the token:

```dotenv
FRISBEE_READ_API_TOKEN=xxxxxxxxxxxxxxxxxxxxxxxx
FRISBEE_DISTRIBUTION_ID=2
FRISBEE_READ_API_URL=https://read-v2.frisbeecms.com/api
```

Every request is a `GET` authenticated with `Authorization: Bearer <token>`.

## Getting started

```php
use GuzzleHttp\Client;
use WeAreAwesome\FrisbeePHPAPI\Frisbee;
use WeAreAwesome\FrisbeePHPAPI\Requests\Content\PageRequest;

$frisbee = Frisbee::make(
    new Client(),
    env('FRISBEE_READ_API_TOKEN'),
    (int) env('FRISBEE_DISTRIBUTION_ID'),
    env('FRISBEE_READ_API_URL'),
);

$page = $frisbee->read()->page(PageRequest::make('home'));

echo $page->title;
echo $page->section('Hero')->content('title')->raw();
```

### In Laravel

Bind it once in a service provider, then type-hint `Frisbee` anywhere:

```php
// app/Providers/AppServiceProvider.php → boot()
$this->app->bind(Frisbee::class, fn () => Frisbee::make(
    new Client(),
    env('FRISBEE_READ_API_TOKEN'),
    (int) env('FRISBEE_DISTRIBUTION_ID'),
    env('FRISBEE_READ_API_URL'),
));
```

### Optional modifiers

```php
$frisbee->read()->inLang('en');                        // ?lang=en
$frisbee->distributionTagOverride('preview')->read();  // ?distribution_tag=preview
```

Both are chainable and optional — omit them for the default live content.

---

## The read API

`$frisbee->read()` returns a `ReadAPI` with exactly five operations:

| Method                               | Returns            | Endpoint                | Use for                              |
|--------------------------------------|--------------------|-------------------------|--------------------------------------|
| `page(PageRequest\|PageCall)`        | `Page`             | `GET /page`             | One page by path/slug                |
| `pages(PagesRequest)`                | content resource   | (batch of `/page`)      | Several pages by path in one round   |
| `list(ListCall)`                     | `ContentList`      | `GET /content-list`     | A paginated list of pages by type    |
| `map()`                              | `SiteMap`          | `GET /distribution/map` | The whole site map (for sitemap.xml) |
| `distributionCall(DistributionCall)` | `DistributionPage` | `GET /distribution`     | Distribution-wide data               |

### A single page

```php
$page = $frisbee->read()->page(PageRequest::make('home'));
$page = $frisbee->read()->page(PageRequest::make('our-work/some-case-study'));
```

### A page plus related lists, concurrently

Additional calls attached to a `PageRequest` run **in parallel** with the page fetch (Guzzle async),
so a landing page and its posts cost one round-trip's latency, not two:

```php
use WeAreAwesome\FrisbeePHPAPI\Requests\Content\ListCall;

$page = $frisbee->read()->page(
    PageRequest::make('blog')->addCall(new ListCall(
        'items',                    // key used to retrieve the result
        [76],                       // content_type_ids
        $request->input('page', 1), // page number
        10,                         // per page
        'published',                // order by
        'desc',                     // order direction
        [],                         // tag_ids to include
        [29, 30],                   // tag_ids to exclude
    ))
);

$items = $page->getAdditionalContent('items');  // ContentList
```

### A standalone list

```php
$list = $frisbee->read()->list(new ListCall('work', [66], 1, 20, 'published', 'asc'));

$pages      = $list->content();     // Collection<Page>
$pagination = $list->pagination();  // ['total','per_page','current_page','cdn_url', …]
```

`ListCall` defaults:

```php
new ListCall(
    string $key            = 'content-list',
    array  $contentTypeIds = [],
    int    $page           = 1,
    int    $perPage        = 100,
    string $orderBy        = 'publish_date',
    string $orderDirection = 'asc',
    array  $tags           = [],
    array  $excludeTags    = [],
);
```

Content-type and tag ids are numeric ids defined in Frisbee — get them from the CMS, don't guess.

### The site map

```php
$items = $frisbee->read()->map()->getData();  // Collection of ['path','content_type_id', …]
```

---

## Reading a page

Pages expose properties via a magic getter — `cached_at`, `cdn_url`, `slug`, `title`,
`description`, `published`, `content_type`, `content_version`, `menus`, `distribution`,
`distribution_settings`, `meta`, `tags`. Anything else returns `null`.

Helpers:

| Method | Returns | Notes |
|--------|---------|-------|
| `section(string $name)` | `SectionInterface` | Missing → `NullSection` (safe) |
| `menu(string $name)` | `MenuInterface` | Missing → `NullMenu` (safe) |
| `getMeta(string $key, string $default = '')` | string | e.g. `seo_title`, `seo_description` |
| `metaWithCDN(string $key, $default = '')` | string | Meta resolved to a full CDN image URL |
| `getSetting(string $key, $default = '')` / `hasSetting()` / `joinSettings()` | | Distribution settings |
| `publishedFormatted(string $format = 'd/m/Y')` | string | `published` via Carbon |
| `availableLanguages()` | array | Language codes for the distribution |
| `contentTypeName()` | ?string | Content type name, lower-cased |
| `sectionDisplayable(string $name)` | bool | |
| `getAdditionalContent(string $key)` | `ContentResource\|null` | Result of an additional call |

### Sections and content items

The CMS defines section names and content-item titles; your templates address them by string.

```php
$section = $page->section('Hero');
$section->isDisplayed();            // author's on/off toggle — always guard on this
$section->content('title')->raw();  // one item by title
$section->all();                    // Collection of every item, sorted by order
```

Content items resolve to a class based on their `type`: `text-box` → `Text`, `image` → `Image`,
`gallery` → `Gallery`, `video-file` → `VideoFile`, `text-input-select` → `Select`, `component` →
`Component`, everything else → generic `ContentItem`.

| Member | Applies to | Notes |
|--------|-----------|-------|
| `raw()` | all | The raw body/value — the primary getter for text |
| `notEmpty()` / `empty()` | all | Presence check |
| `type`, `title`, `order` | all | Properties |
| `meta(string $key, $default = '')` | all | Item-level metadata |
| `render(array $attrs = [])` | all | Body wrapped in a `<span>` with Frisbee edit tags |
| `url()` | `Image`, `VideoFile` | Full CDN-resolved URL |
| `variant(string $size)` | `Image` | `Image::SMALL\|MEDIUM\|LARGE`; falls back to `url()` |
| `items()` | `Gallery` | `Image[]` |
| `variant(string $format)` | `VideoFile` | URL for `VideoFile::WEBM\|MP4\|OGG`; falls back to `url()` |
| `sources()` | `VideoFile` | Ordered `<source>` list (preferred first): `['format','mime','file','url']` |
| `poster()` / `fallback()` / `preferredFormat()` | `VideoFile` | Poster image URL, fallback text, preferred format key |
| `value()` | `Select` | The chosen option's value (alias of `raw()`) |
| `is(string $value)` / `in(array $values)` | `Select` | Test the selection — branch content on it |
| `rows()` | `Component` | `Collection<ComponentRow>`; each row's sub-fields hydrate to their real types |
| `content(string $title)` / `all()` | `ComponentRow` | Read one sub-field by title / all sub-fields (sorted) |

Image URLs resolve against the page's `cdn_url`: a bare filename becomes
`{cdn_url}/images/{filename}`; an absolute `http…` value is returned as-is.

### Menus

```php
foreach ($page->menu('Footer')->all() as $item) {
    $item->title(); $item->url(); $item->hasChildren(); $item->children();
}
```

### Blade example

```blade
@if($page->section('Hero')->isDisplayed())
    <x-hero
        title="{{ $page->section('Hero')->content('title')->raw() }}"
        image="{{ $page->section('Hero')->content('image')->variant(\WeAreAwesome\FrisbeePHPAPI\Content\Types\Image::LARGE) }}"
    />
@endif
```

**Self-hosted video (`video-file`):** iterate `sources()` (preferred format first) to emit a
standard multi-format `<video>` element with a poster and fallback text:

```blade
@php($video = $page->section('Hero')->content('background_video'))
@if($video->notEmpty())
    <video controls poster="{{ $video->poster() }}">
        @foreach($video->sources() as $source)
            <source src="{{ $source['url'] }}" type="{{ $source['mime'] }}">
        @endforeach
        {{ $video->fallback() }}
    </video>
@endif
```

**Conditional content from a select (`text-input-select`):** branch on the chosen value with
`is()` / `in()` (they compare the option's machine value, not its label):

```blade
@php($layout = $page->section('Features')->content('layout'))
@if($layout->is('grid'))
    <x-features-grid :items="$items" />
@elseif($layout->in(['list', 'compact']))
    <x-features-list :items="$items" :dense="$layout->is('compact')" />
@endif
```

**Repeater (`component`):** iterate `rows()`; each row's sub-fields hydrate to their real types, so
nested images/videos/selects behave exactly like top-level content — no raw-array plumbing. Address
sub-fields by title (which for a component sub-field is the schema **label**, falling back to
`name`):

```blade
@foreach($page->section('Cards')->content('cards')->rows() as $row)
    <x-card
        title="{{ $row->content('Title')->raw() }}"
        image="{{ $row->content('Image')->variant(\WeAreAwesome\FrisbeePHPAPI\Content\Types\Image::LARGE) }}"
        :featured="$row->content('Layout')->is('featured')"
    />
@endforeach
```

---

## Errors and null-safety

**Fetch time** — `page()`, `list()` and `map()` throw when the HTTP call fails:

| Exception | When |
|-----------|------|
| `Requests\Content\Exceptions\FrisbeeContentNotFound` | 404 / 422 — page not found or not distributed |
| `Exceptions\FrisbeeAuthorizationException` | 401 — bad or expired token |
| `Exceptions\FrisbeeException` | any other API error |
| `Content\Exceptions\FrisbeeMalformedContentException` | unexpected response shape |

```php
try {
    $page = $frisbee->read()->page(PageRequest::make($slug));
} catch (FrisbeeContentNotFound) {
    abort(404);
}
```

**Read time** — content lookups **never throw**. Missing sections, items and menus return
`NullSection` / `NullContent` / `NullMenu`, whose methods return safe empties (`''`, `false`, empty
collection). So `$page->section('Maybe')->content('maybe')->raw()` degrades to `''` rather than
erroring. Use `isDisplayed()` and `notEmpty()` to decide whether to render a block at all.

## Caching

The SDK does **not** cache — every call hits the API. In production, cache fetched pages and lists
at the application layer (keyed by slug) and bust on publish.

## Namespace cheat sheet

```
WeAreAwesome\FrisbeePHPAPI\Frisbee                         // entry point
WeAreAwesome\FrisbeePHPAPI\Api\ReadAPI                     // read operations
WeAreAwesome\FrisbeePHPAPI\Requests\Content\PageRequest    // build a page fetch
WeAreAwesome\FrisbeePHPAPI\Requests\Content\PagesRequest   // build a multi-page fetch
WeAreAwesome\FrisbeePHPAPI\Requests\Content\ListCall       // build a list fetch / additional call
WeAreAwesome\FrisbeePHPAPI\Requests\Content\DistributionCall
WeAreAwesome\FrisbeePHPAPI\Content\Page
WeAreAwesome\FrisbeePHPAPI\Content\ContentList             // content() + pagination()
WeAreAwesome\FrisbeePHPAPI\Content\SiteMap                 // getData()
WeAreAwesome\FrisbeePHPAPI\Content\Sections\Section
WeAreAwesome\FrisbeePHPAPI\Content\Types\{Text,Image,Gallery,VideoFile,Select,Component,ComponentRow,ContentItem}
WeAreAwesome\FrisbeePHPAPI\Content\Menus\{Menu,MenuItem}
WeAreAwesome\FrisbeePHPAPI\Exceptions\{FrisbeeException,FrisbeeAuthorizationException}
WeAreAwesome\FrisbeePHPAPI\Requests\Content\Exceptions\FrisbeeContentNotFound
```

## Licence

Apache-2.0
