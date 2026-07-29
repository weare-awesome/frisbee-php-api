# Frisbee PHP SDK — Guide for AI Agents

> Audience: an AI coding agent that has been asked to build or extend a website using the
> `weare-awesome/frisbee-php-api` SDK. This document is self-contained: read it top to bottom
> before writing any integration code. Every class, method, and field named here exists in the
> SDK source — treat them as the complete public surface. If something you want is not listed,
> it does not exist; do not invent it.

---

## 1. What Frisbee is

Frisbee is a **headless CMS**. Content is authored in Frisbee and served over an HTTP **Read
API**. This SDK is a thin, read-only PHP client for that API. It does **not** write, publish, or
mutate content — it only *reads* pages, lists, menus, and the site map for a single
**distribution** (a distribution ≈ one published site/channel).

The mental model, from largest to smallest:

```
Distribution                     one published site (identified by a numeric distribution_id)
└── Page                         one URL/route, addressed by a string "path" (slug)
    ├── meta / settings / menus  page-level SEO + navigation data
    └── content_version.body.sections[]
        └── Section              a named region of the page (e.g. "Hero", "Gallery")
            └── contents[]       content items inside the section
                └── ContentItem  a single field: text, image, gallery, video, wysiwyg…
```

An agent building a site does two things repeatedly:
1. **Fetch** a `Page` (or list/map) from the API via the SDK.
2. **Read** named sections and content items off that `Page` and render them into a template.

The CMS decides the section names and content-item titles. Your template code addresses them
**by name as strings** (`$page->section('Hero')->content('title')`). Names are defined by whoever
modelled the content in Frisbee — you must know them (from a brief, an existing template, or by
inspecting a live API response) before you can render them. Lookups are forgiving: a missing
section or item returns a safe "null" object, never throws (see §9).

---

## 2. Install & requirements

```bash
composer require weare-awesome/frisbee-php-api
```

- PHP `^8.0.2`
- Pulls in `guzzlehttp/guzzle`, `illuminate/collections`, `nesbot/carbon`.
- PSR-4 root namespace: `WeAreAwesome\FrisbeePHPAPI\`.
- Return types lean on `Illuminate\Support\Collection`, so it feels native inside Laravel but
  works in any PHP app.

### Configuration (three values)

| Purpose            | Example                              |
|--------------------|--------------------------------------|
| Read API token     | `cQOhy...` (secret, Bearer auth)     |
| Distribution ID    | `2` (integer)                        |
| Read API base URL  | `https://read.frisbeecms.com/api`    |

Store these in the environment, never hard-code them:

```dotenv
FRISBEE_READ_API_TOKEN=xxxxxxxxxxxxxxxxxxxxxxxx
FRISBEE_DISTRIBUTION_ID=2
FRISBEE_READ_API_URL=https://read.frisbeecms.com/api
```

The SDK authenticates by sending `Authorization: Bearer <token>` on every request. All requests
are GET.

---

## 3. Bootstrapping the client

The one entry-point class is `Frisbee`. Construct it with a Guzzle client, the token, the
distribution id, and the base URL:

```php
use GuzzleHttp\Client;
use WeAreAwesome\FrisbeePHPAPI\Frisbee;

$frisbee = Frisbee::make(
    new Client(),
    env('FRISBEE_READ_API_TOKEN'),
    (int) env('FRISBEE_DISTRIBUTION_ID'),
    env('FRISBEE_READ_API_URL'),
);
```

### In Laravel (recommended)

Bind it once in a service provider so it can be dependency-injected into controllers:

```php
// app/Providers/AppServiceProvider.php  → boot()
$this->app->bind(Frisbee::class, function () {
    return Frisbee::make(
        new Client(),
        env('FRISBEE_READ_API_TOKEN'),
        (int) env('FRISBEE_DISTRIBUTION_ID'),
        env('FRISBEE_READ_API_URL'),
    );
});
```

Then type-hint `Frisbee` in any controller action and Laravel injects it.

### The read entry point

Everything goes through `->read()`, which returns a `ReadAPI` instance:

```php
$frisbee->read()                       // ReadAPI
$frisbee->read()->inLang('en')         // optional: sets ?lang=en on the request
$frisbee->distributionTagOverride('preview')->read()   // optional: preview/tag override
```

- `inLang(?string)` — request a specific language variant (adds `lang` to the query).
- `distributionTagOverride(string)` — fetch a tagged/preview version instead of the live
  distribution (adds `distribution_tag` to the query). Can be set on `Frisbee` or on `ReadAPI`.

Both are chainable and optional. Omit them for the default live English content.

---

## 4. Fetching content — the ReadAPI methods

`ReadAPI` exposes exactly five read operations. This is the complete list.

| Method                                   | Returns          | HTTP endpoint            | Use for                              |
|------------------------------------------|------------------|--------------------------|--------------------------------------|
| `page(PageRequest\|PageCall)`            | `Page`           | `GET /page`              | One page by path/slug                |
| `pages(PagesRequest)`                    | content resource | (batch of `/page`)       | Several pages by path in one round   |
| `list(ListCall)`                         | `ContentList`    | `GET /content-list`      | A paginated list of pages by type    |
| `map()`                                  | `SiteMap`        | `GET /distribution/map`  | The whole site map (for sitemap.xml) |
| `distributionCall(DistributionCall)`     | `DistributionPage` | `GET /distribution`    | Distribution-wide data               |

### 4.1 Fetch a single page

```php
use WeAreAwesome\FrisbeePHPAPI\Requests\Content\PageRequest;

$page = $frisbee->read()->page(PageRequest::make('home'));       // slug "home"
$page = $frisbee->read()->page(PageRequest::make('our-work/some-case-study'));
$page = $frisbee->read()->page(new PageRequest('post/' . $slug)); // nested path
```

`PageRequest::make($path)` and `new PageRequest($path)` are equivalent. The `$path` is the
content path/slug as defined in Frisbee (e.g. `home`, `contact-us`, `our-work/foo`, `post/bar`).

### 4.2 Fetch a page *and* related lists together (additional calls)

A single page request can carry **additional calls** that are executed **concurrently** with the
page fetch (Guzzle async under the hood — one network round-trip's worth of latency, not N). This
is how you load, say, a landing page plus the list of posts that belong on it:

```php
use WeAreAwesome\FrisbeePHPAPI\Requests\Content\PageRequest;
use WeAreAwesome\FrisbeePHPAPI\Requests\Content\ListCall;

$page = $frisbee->read()->page(
    PageRequest::make('blog')->addCall(
        new ListCall(
            'items',              // key: how you retrieve the result later
            [76],                 // content_type_ids to include
            $request->input('page', 1), // page number
            10,                   // per page
            'published',          // order_by
            'desc',               // order_direction
            [],                   // tag_ids to include (whitelist)
            [29, 30],             // tag_ids to exclude
        )
    )
);

$items = $page->getAdditionalContent('items');   // ContentList, keyed by 'items'
```

`addCall()` accepts any `ContentCall` (currently `ListCall`). You may add several; each result is
retrieved by its key via `$page->getAdditionalContent($key)`.

### 4.3 Fetch a standalone list

If you don't need a page wrapper, call `list()` directly. `ListCall` returns a `ContentList`:

```php
use WeAreAwesome\FrisbeePHPAPI\Requests\Content\ListCall;

$list = $frisbee->read()->list(
    new ListCall('work', [66], $page = 1, $perPage = 20, 'published', 'asc')
);

$pages      = $list->content();      // Collection<Page>
$pagination = $list->pagination();   // ['total','per_page','current_page','cdn_url', …]
```

**`ListCall` constructor signature** (all optional, shown with defaults):

```php
new ListCall(
    string $key            = 'content-list',
    array  $contentTypeIds = [],
    int    $page           = 1,
    int    $perPage        = 100,
    string $orderBy        = 'publish_date',
    string $orderDirection = 'asc',
    array  $tags           = [],   // include only these tag ids
    array  $excludeTags    = [],   // exclude these tag ids
);
```

`contentTypeIds` and tag ids are **numeric ids defined in Frisbee**. You must know the ids for
the content types/tags you want (from the CMS or an existing integration). `orderBy` accepts
fields like `publish_date` / `published`.

### 4.4 Fetch the site map

```php
$map = $frisbee->read()->map();          // SiteMap
$items = $map->getData();                // Collection of ['path','content_type_id', …]
```

Each map item includes at least `path` and `content_type_id`. Use it to generate `sitemap.xml`
(map content-type ids to URL bases, priorities, change frequencies — that mapping lives in *your*
app, not the SDK).

---

## 5. The `Page` object — reading content

`page()` returns a `WeAreAwesome\FrisbeePHPAPI\Content\Page`. Its public API:

### Page-level properties (read via magic getter `$page->field`)

Only these keys are populated (anything else returns `null`):

`cached_at`, `cdn_url`, `slug`, `title`, `description`, `published`, `content_type`,
`content_version`, `menus`, `distribution`, `distribution_settings`, `meta`, `tags`.

- `$page->title`, `$page->slug`, `$page->description` — strings.
- `$page->content_type` — array; `$page->content_type['name']` is the type name (e.g. `"General"`,
  `"basic page"`, `"Service landing page"`, `"Redirect"`). Use it to pick a template (see §8).
- `$page->contentTypeName(): ?string` — the type name **lower-cased**, convenient for comparisons.

### Page-level helper methods

| Method | Returns | Notes |
|--------|---------|-------|
| `section(string $name)` | `SectionInterface` | Region lookup. Missing → `NullSection` (safe). |
| `menu(string $name)` | `MenuInterface` | Navigation lookup. Missing → `NullMenu` (safe). |
| `getMeta(string $key, string $default = '')` | string | Page meta (e.g. `seo_title`, `seo_description`). |
| `metaWithCDN(string $key, $default = '')` | string | Meta value resolved to a full CDN image URL. |
| `getSetting(string $key, $default = '')` | string | Distribution-level setting. |
| `hasSetting(string $key)` | bool | |
| `joinSettings(array $keys, string $sep = ',')` | string | Concatenate several settings. |
| `publishedFormatted(string $format = 'd/m/Y')` | string | `published` via Carbon. |
| `availableLanguages()` | array | Language codes configured for the distribution. |
| `contentTypeName()` | ?string | Lower-cased content type name. |
| `sectionDisplayable(string $name)` | bool | Whether a section is flagged displayed. |
| `hasAdditionalContent()` | bool | Any additional-call results attached? |
| `additionalContentExists(string $key)` | bool | |
| `additionalIsContentList(string $key)` | bool | |
| `getAdditionalContent(string $key)` | `ContentResource\|null` | Retrieve an additional-call result. |

### Sections

`$page->section('Hero')` returns a `Section` (or `NullSection` if absent). A section:

| Method | Returns | Notes |
|--------|---------|-------|
| `isDisplayed()` | bool | Author toggled this region on/off. **Always guard rendering with this.** |
| `content(string $title)` | `ContentItemInterface` | One content item by title. Missing → `NullContent`. |
| `all()` | `Collection` | All items, sorted by `order`. Iterate for repeating/unknown content. |
| `name` / `order` (props) | string / int | |

### Content items

`$section->content('title')` returns a `ContentItemInterface`. Concrete types are chosen by the
item's `type`: `text-box` → `Text`, `image` → `Image`, `gallery` → `Gallery`, `video-file` →
`VideoFile`, `text-input-select` → `Select`, `component` → `Component`, everything else → generic
`ContentItem`. Common members:

| Member | Applies to | Returns | Notes |
|--------|-----------|---------|-------|
| `raw()` | all | string | The raw body/value. **Your primary getter for text.** |
| `notEmpty()` / `empty()` | all | bool | Presence check. |
| `type` (prop) | all | string | e.g. `text-box`, `image`, `gallery`, `video`, `video-file`, `wysiwyg`. |
| `title` / `order` (props) | all | string / int | |
| `meta(string $key, $default='')` | all | mixed | Item-level metadata. |
| `render(array $attrs = [])` | all | string | Wraps body in a `<span>` with Frisbee edit tags. |
| `url()` | `Image`, `VideoFile` | string | Full CDN-resolved URL. For `VideoFile`, the canonical source (body, else preferred format). |
| `variant(string $size)` | `Image` | string | Sized variant URL; falls back to `url()`. Sizes: `Image::SMALL`='sm', `Image::MEDIUM`='med', `Image::LARGE`='lg'. |
| `items()` | `Gallery` | `Image[]` | The gallery's images (each a full `Image`). |
| `variant(string $format)` | `VideoFile` | string | URL for one format; falls back to `url()`. Formats: `VideoFile::WEBM`='webm', `VideoFile::MP4`='mp4', `VideoFile::OGG`='ogg'. |
| `sources()` | `VideoFile` | array | Ordered `<source>` list (preferred first): each `['format','mime','file','url']`. |
| `poster()` | `VideoFile` | string | Poster image URL (resolved under `/images`), `''` if none. |
| `fallback()` | `VideoFile` | string | Text shown when no source can play. |
| `preferredFormat()` | `VideoFile` | string | Format key browsers should try first (default `webm`). |
| `value()` | `Select` | string | The chosen option's value (alias of `raw()`). |
| `is(string $value)` | `Select` | bool | Selected value === `$value` (strict; the machine value, not the label). |
| `in(array $values)` | `Select` | bool | Selected value is one of `$values`. |
| `rows()` | `Component` | `Collection<ComponentRow>` | Authored repeater rows; each row's sub-fields hydrate to their real types. |
| `content(string $title)` | `ComponentRow` | `ContentItemInterface` | One sub-field by title (the schema `label`, fallback `name`). Missing → `NullContent`. |
| `all()` | `ComponentRow` | `Collection` | All sub-fields in the row, sorted by order. |

Image URLs are resolved against the page's `cdn_url` (a bare filename becomes
`{cdn_url}/images/{filename}`; an absolute `http…` value is returned as-is). `VideoFile` source
files resolve the same way but under **`/videos/`**; its poster resolves under `/images/`.

### Menus

`$page->menu('Footer')` returns a `Menu` (or `NullMenu`). `->all()` is a `Collection<MenuItem>`;
each `MenuItem` has `title()`, `url()`, `children()` (`Collection<MenuItem>`), and
`hasChildren()`.

### ContentList

Returned by `list()` and by `getAdditionalContent()` when the call was a `ListCall`:

- `content()` → `Collection<Page>` — each list entry is itself a lightweight `Page`.
- `pagination()` → array with `total`, `per_page`, `current_page`, `cdn_url`.

---

## 6. Rendering patterns (Blade examples)

These mirror the reference site's templates. Adapt to your view layer; the SDK calls are the same.

**Guard every section with `isDisplayed()`, then read named items:**

```blade
@if($page->section('Hero')->isDisplayed())
    <x-hero
        title="{{ $page->section('Hero')->content('title')->raw() }}"
        content="{{ $page->section('Hero')->content('content')->raw() }}"
        buttonText="{{ $page->section('Hero')->content('button_text')->raw() }}"
        buttonLink="{{ $page->section('Hero')->content('button_link')->raw() }}"
    />
@endif
```

**Image with a sized variant:**

```blade
<img src="{{ $page->section('Hero')->content('image')->variant(\WeAreAwesome\FrisbeePHPAPI\Content\Types\Image::LARGE) }}">
```

**Iterate an unknown/repeating section with `all()`** (items sorted by order):

```blade
@foreach($page->section('Gallery')->all() as $item)
    @if($item->type === 'video')
        <x-video :src="$item->raw()" />
    @else
        <img src="{{ $item->url() }}">
    @endif
@endforeach
```

**Gallery items:**

```blade
@foreach($page->section('Gallery')->content('images')->items() as $image)
    <img src="{{ $image->url() }}">
@endforeach
```

**Menu:**

```blade
@foreach($page->menu('Footer')->all() as $item)
    <a href="{{ $item->url() }}">{{ $item->title() }}</a>
@endforeach
```

**SEO meta into the layout:**

```blade
<title>{{ $page->getMeta('seo_title', $page->title) }}</title>
<meta name="description" content="{{ $page->getMeta('seo_description', $page->description) }}">
```

---

## 7. Full recipe — a paginated blog index

Combines a page fetch + concurrent list + framework pagination:

```php
use WeAreAwesome\FrisbeePHPAPI\Frisbee;
use WeAreAwesome\FrisbeePHPAPI\Requests\Content\PageRequest;
use WeAreAwesome\FrisbeePHPAPI\Requests\Content\ListCall;
use Illuminate\Pagination\LengthAwarePaginator;

public function index(Request $request, Frisbee $frisbee)
{
    $page = $frisbee->read()->page(
        PageRequest::make('blog')->addCall(new ListCall(
            'items', [76], $request->input('page', 1), 10, 'published', 'desc', [], [29, 30]
        ))
    );

    $items = $page->getAdditionalContent('items');   // ContentList

    $meta = $items->pagination();
    $pagination = new LengthAwarePaginator(
        $items,
        $meta['total'],
        $meta['per_page'],
        $meta['current_page'],
        ['path' => '/blog', 'query' => $request->query()],
    );

    return view('pages.blog.index', compact('page', 'items', 'pagination'));
}
```

The individual list entries are `Page`s: `foreach ($items->content() as $post) { $post->title; … }`.

---

## 8. Routing by content type

A common pattern is one catch-all route that fetches by slug and dispatches on the content type:

```php
$page = $frisbee->read()->page(PageRequest::make($slug));

switch ($page->content_type['name']) {
    case 'General':              return view('pages.general', compact('page'));
    case 'basic page':           return view('pages.basic', compact('page'));
    case 'Service landing page': return view('pages.service-landing-page', compact('page'));
    case 'Redirect':             return redirect($page->section('Redirect')->content('url')->raw());
    default:                     abort(404);
}
```

`content_type['name']` values are defined in Frisbee — match them exactly (they are
case-sensitive as returned; use `contentTypeName()` for a lower-cased compare).

---

## 9. Errors & null-safety

Two distinct layers — know which applies:

**Fetch-time exceptions** (thrown by `page()`/`list()`/`map()` when the HTTP call fails):

| Exception | When |
|-----------|------|
| `Requests\Content\Exceptions\FrisbeeContentNotFound` | 404 / 422 — page not found or not distributed |
| `Exceptions\FrisbeeAuthorizationException` | 401 — bad/expired token |
| `Exceptions\FrisbeeException` | any other API error (also premature access) |
| `Content\Exceptions\FrisbeeMalformedContentException` | response body wasn't the expected shape |

Wrap fetches and map `FrisbeeContentNotFound` to a 404:

```php
use WeAreAwesome\FrisbeePHPAPI\Requests\Content\Exceptions\FrisbeeContentNotFound;

try {
    $page = $frisbee->read()->page(PageRequest::make($slug));
} catch (FrisbeeContentNotFound) {
    abort(404);
}
```

**Read-time null-safety** (accessing content on a `Page` that loaded fine): lookups **never
throw**. A missing section returns a `NullSection`, a missing item returns `NullContent`, a
missing menu returns `NullMenu`. Calling `->raw()`, `->url()`, `->isDisplayed()`, `->all()`, etc.
on those null objects returns safe empties (`''`, `false`, empty collection). So you can write
`$page->section('Maybe')->content('maybe')->raw()` without guards — it degrades to `''`. Use
`isDisplayed()` / `notEmpty()` to decide whether to render a block at all.

---

## 10. Caching

The SDK does **not** cache — every call hits the API. In production, cache fetched pages/lists at
the application layer (keyed by slug) and invalidate on publish. The reference site does this with
a `PageCache` around the fetch and a token-protected "bust cache" endpoint. Do the same in any
non-trivial site: without it, every request makes a live API round-trip.

---

## 11. Content types — the schema you author, and the integration that matches it

Everything above assumes a page already has sections and fields. **This section is about
creating them.** In Frisbee a **content type** (e.g. "Service landing page", "General", "News
post") is defined by an ordered list of **section definitions**. That definition is the *schema*:
it declares what an author *can* fill in. The API response for a page of that type then carries
what an author *did* fill in, under `content_version.body.sections[]`.

An agent building a feature produces **two artifacts that must agree field-for-field**:

1. **The content-type schema** — the JSON that defines the sections and fields (authored in
   Frisbee / created via its authoring API).
2. **The integration** — the controller + template that reads those exact section names and field
   names back out via the SDK (`$page->section('X')->content('y')`).

> **The golden rule:** the strings in the schema (`section.name`, `field.name`) *are* the string
> keys your template passes to `section()` and `content()`. Design them together, name them once,
> never let them drift. A typo on either side silently yields an empty value (§9), not an error —
> so mismatches are invisible until someone notices missing content.

### 11.1 Schema anatomy

A content type is a JSON **array of section definitions**. Each section:

```jsonc
{
  "name": "Hero",              // ← becomes $page->section('Hero'); case-sensitive, exact
  "description": null,          // author-facing help text for the whole section
  "displayable": false,         // can the author toggle this section on/off? (see 11.4)
  "fixed_content": [ … ],       // named, predefined fields (the common case)
  "allowed_content": []         // free/repeatable item types (the gallery case)
}
```

Each entry in **`fixed_content`** is a **field definition**:

```jsonc
{
  "name": "cta_text",          // ← becomes ->content('cta_text'); this is the identifier
  "type": "text-input",         // field/input type (table below)
  "label": "CTA text",          // author-facing label in the Frisbee editor UI
  "description": null,          // author-facing help text for this field
  "meta": { … }                 // present only for select fields (see below)
}
```

`label` and `description` are for humans editing in the CMS — they do **not** affect the
integration. Only `name` and `type` matter to your code.

> **Schema vs data — both are in the page response.** A full `/page` response carries the *schema*
> at `content_type.config.sections` (the array of section definitions, exactly the shape shown
> above — reachable as `$page->content_type['config']['sections']`) and the authored *data* at
> `content_version.body.sections`. The SDK reads the data side for you; the schema side is there
> for introspection if you ever need to discover section/field names programmatically. Note that
> **sections with no authored content are omitted from `body.sections`** — so `section('X')` on an
> empty section correctly returns a `NullSection` (§9), it isn't an error.

### 11.2 Field type reference (schema `type` → runtime → how you read it)

This is the crucial mapping. The SDK turns each authored field into a runtime object based on its
`type` string (via `ContentItemFactory`, used by both `Section` and `Component`), and the runtime
object's `->type` equals this same string. Six types get dedicated classes (`Text`, `Image`,
`Gallery`, `VideoFile`, `Select`, `Component`); **everything else becomes a generic `ContentItem`**,
which means media/typed helpers (`->url()` / `->variant()` / `->items()` / `->sources()` /
`->rows()`) return empty on them — match the reader to the `type`.

| Schema `type`        | Frisbee editor input        | Runtime class  | Read it with                          |
|----------------------|-----------------------------|----------------|---------------------------------------|
| `text-input`         | single-line text            | `ContentItem`  | `->raw()` → string                    |
| `text-box`           | multi-line plain text       | `Text`         | `->raw()` → string                    |
| `wysiwyg`            | rich-text HTML editor       | `ContentItem`  | `->raw()` → HTML (sanitise before echo) |
| `image`              | image picker                | `Image`        | `->url()`, `->variant('sm'\|'med'\|'lg')`; `->raw()` = filename (use for presence) |
| `video`              | embed field (YouTube/Vimeo) | `ContentItem`  | `->raw()` → embed id; provider in `->meta('type')` |
| `video-file`         | self-hosted multi-format video | `VideoFile` | `->sources()`, `->poster()`, `->fallback()`; `->url()`/`->variant('webm'\|'mp4'\|'ogg')` |
| `text-input-select`  | dropdown (needs `meta`)     | `Select`       | `->value()`/`->raw()` → chosen `value`; `->is('x')`/`->in([...])` to branch |
| `gallery`            | grouped images              | `Gallery`      | `->items()` → `Image[]`               |
| `component`          | **repeater** (needs `config`) | `Component`   | `->rows()` → `ComponentRow[]`; each `->content('SubField')` hydrated — see §11.3 Shape B |

A **select** field carries its options in `meta`:

```jsonc
{
  "name": "allow_lightbox",
  "type": "text-input-select",
  "label": "Allow lightbox",
  "meta": {
    "uses": "select",
    "options": [
      { "label": "Yes", "value": "yes" },
      { "label": "No",  "value": "no"  }
    ]
  }
}
```

At runtime a `text-input-select` hydrates into a **`Select`**. `->value()` (or `->raw()`) returns
the chosen option's machine `value` — `"yes"` or `"no"` here, **not** the human `label`. To display
content conditionally, branch on it with `->is('yes')` (strict equality) or `->in(['yes','maybe'])`
rather than comparing `raw()` by hand:

```blade
@if($page->section('Gallery')->content('allow_lightbox')->is('yes'))
    <x-lightbox :images="$images" />
@endif
```

The options list (`meta.options`) lives in the content-type **schema**, not in the page data, so it
is not readable from the `Select` item at runtime — you already know the values you defined, so
compare against those.

A **`video-file`** field is a self-hosted, multi-format HTML5 video — distinct from `video`, which
is a YouTube/Vimeo embed id. The author uploads one file per format (WebM/MP4/Ogg) plus an optional
poster and fallback text; the SDK hydrates it into a **`VideoFile`** (not a generic `ContentItem`).
The authored shape:

```jsonc
{
  "title": "background_video",
  "type": "video-file",
  "body": "clip.webm",                 // canonical/preferred source filename
  "meta": {
    "sources":   { "webm": "clip.webm", "mp4": "clip.mp4", "ogg": "clip.ogg" },
    "preferred": "webm",               // which format to try first
    "poster":    "clip-poster.png",     // resolved under /images
    "fallback":  "Your browser cannot play this video."
  }
}
```

Unlike `image`, video source files resolve under **`/videos/`** (`{cdn_url}/videos/{filename}`);
the poster resolves under `/images/`. Don't hand-build these URLs or call `->url()` expecting an
image path — use the `VideoFile` helpers, which resolve everything for you:

```blade
@php($video = $page->section('Hero')->content('background_video'))  {{-- VideoFile --}}
@if($video->notEmpty())
  <video controls poster="{{ $video->poster() }}">
    @foreach($video->sources() as $source)   {{-- preferred format first --}}
      <source src="{{ $source['url'] }}" type="{{ $source['mime'] }}">
    @endforeach
    {{ $video->fallback() }}
  </video>
@endif
```

`sources()` returns only the formats the author actually uploaded, preferred one first, each as
`['format' => 'webm', 'mime' => 'video/webm', 'file' => 'clip.webm', 'url' => '…']`. `url()` gives
the single canonical source (the `body`, or the first available format); `variant('mp4')` gives one
specific format's URL and falls back to `url()` when that format is absent. Presence is
`notEmpty()` / `empty()` — true only when no playable file resolves at all.

### 11.3 The section/field shapes (pick the right one)

There are four shapes an agent will produce; match the template idiom to the shape. Two of them
repeat: the **native `component` repeater** (Shape B — preferred for unbounded, structured rows)
and the older **ordinal-field pattern** (Shape C — still common in existing content types).

**Shape A — Fixed-field block** (Hero, Content one, CTA, Campaign CTA). A handful of distinct
named fields. Read each by name.

```jsonc
// schema
{ "name": "CTA", "displayable": true, "allowed_content": [], "fixed_content": [
  { "name": "text",        "type": "text-box",   "label": "Text" },
  { "name": "button_text", "type": "text-input", "label": "Button text" },
  { "name": "button_label","type": "text-input", "label": "Button link" }
]}
```
```blade
{{-- integration --}}
@if($page->section('CTA')->isDisplayed())
  <x-cta
    text="{{ $page->section('CTA')->content('text')->raw() }}"
    href="{{ $page->section('CTA')->content('button_label')->raw() }}">
    {{ $page->section('CTA')->content('button_text')->raw() }}
  </x-cta>
@endif
```

**Shape B — Native repeater (`component`).** A field of `type: "component"` is Frisbee's real
repeater: the author adds **any number of rows**, and each row is itself a little group of core
fields defined in `config.items`. Use this whenever the count is unbounded *and* each entry has
more than one sub-field (an FAQ list, feature cards, logos-with-captions, etc.). The row schema
lives under `config.items` — each item is a **core field** (`text-input`, `image`, `wysiwyg`,
`video`, `text-input-select`, …); you can repeat any of the core types:

```jsonc
// schema — a component (repeater) field inside a section's fixed_content
{
  "name": "cards-component",       // ← the field name you'll read: ->content('cards-component')
  "type": "component",
  "label": "Card",
  "config": {
    "items": [                     // the schema of ONE row (repeated N times by the author)
      { "name": "title",        "type": "text-box", "label": "Title" },
      { "name": "cta one text", "type": "text-box", "label": "CTA 1 Text" },
      { "name": "cta one link", "type": "text-box", "label": "CTA 1 Link" },
      { "name": "video",        "type": "video"  },
      { "name": "image",        "type": "image"  }
    ]
  }
}
```

**Runtime.** A `component` field hydrates to a **`Component`**. Call `->rows()` for a
`Collection<ComponentRow>` (one per authored row); each `ComponentRow` hydrates its sub-fields to
their **real runtime types** via the same factory the SDK uses for top-level content — so a nested
`image` is a real `Image` (`->url()`/`->variant()`), a `video-file` a real `VideoFile`, a
`text-input-select` a real `Select`, and so on. No raw-array plumbing, no manual URL building:

```blade
@php($cards = $page->section('Cards')->content('cards-component'))   {{-- Component --}}
@foreach($cards->rows() as $row)
  <x-card
    title="{{ $row->content('Title')->raw() }}"
    ctaText="{{ $row->content('CTA 1 Text')->raw() }}"
    ctaLink="{{ $row->content('CTA 1 Link')->raw() }}"
    image="{{ $row->content('image')->variant(\WeAreAwesome\FrisbeePHPAPI\Content\Types\Image::LARGE) }}"
    video="{{ $row->content('video')->raw() }}"   {{-- embed id; provider via ->meta('type') --}}
  />
@endforeach
```

Read a row's sub-fields with `->content('Title')` (one by title) or `->all()` (every sub-field,
sorted by order). One thing to keep in mind about the **title**:

- **A component sub-field is addressed by its runtime `title`, which is the schema item's `label`
  (falling back to `name` when the label is null).** So schema `name: "cta one text"` /
  `label: "CTA 1 Text"` is read as `->content('CTA 1 Text')`. This is the **opposite** of top-level
  fixed content (where the runtime title is the *name*). Lookups are case-insensitive, but to keep
  reads predictable **give every `component` sub-field a `label` equal to its `name`**, or always
  read by the label string. A miss returns a safe `NullContent` (§9), same as a section.

The old approach — reading `->meta('items')` and resolving image/video URLs by hand — is no longer
needed; `rows()` does it. `$component->empty()` / `->notEmpty()` report whether any rows exist.

Prefer Shape B over Shape C for new content types with repeating structured entries. Reach for
Shape C only when you deliberately want a **fixed, small number of slots** modelled as flat fields
(and when matching an existing content type that already uses that pattern).

**Shape C — Ordinal-field pseudo-repeater (legacy pattern)** (List One, Grid, service pills). A
fixed-count list modelled as **flat, numbered fields** using an ordinal word — the pattern used
before/instead of a `component` field. Two naming conventions appear in the wild — pick one and be
consistent:

- kebab + `-one`: `list-title`, `icon-one`, `text-one`, `link-one`, … `-five`
- snake + `_one`: `label_one`, `icon_one`, `title_one`, `body_one`, `cta_link_one`, … `_nine`

```jsonc
// schema (Grid — 9 repeated cells, six fields each)
{ "name": "Grid", "displayable": true, "allowed_content": [], "fixed_content": [
  { "name": "label_one", "type": "text-input", "label": "Label one" },
  { "name": "icon_one",  "type": "image",      "label": "Icon one"  },
  { "name": "title_one", "type": "text-input", "label": "Title one" },
  { "name": "body_one",  "type": "wysiwyg",    "label": "Body one"  },
  { "name": "cta_link_one", "type": "text-input" },
  { "name": "cta_text_one", "type": "text-input" }
  /* …repeat with _two … _nine */
]}
```
The integration **reconstructs the array by looping the ordinal words** and using a presence check
(truthiness of a key field's `->raw()`) to skip empty cells:

```blade
@php
  $keys = ['one','two','three','four','five','six','seven','eight','nine'];
  $section = $page->section('Grid');
  $items = [];
  foreach ($keys as $key) {
      if (!$section->content("title_{$key}")->raw()) continue;   // empty cell → stop including
      $items[] = [
          'label'   => $section->content("label_{$key}")->raw(),
          'icon'    => $section->content("icon_{$key}")->url(),
          'title'   => $section->content("title_{$key}")->raw(),
          'body'    => $section->content("body_{$key}")->raw(),
          'ctaLink' => $section->content("cta_link_{$key}")->raw(),
          'ctaText' => $section->content("cta_text_{$key}")->raw(),
      ];
  }
@endphp
<x-grid :items="$items" />
```

Rules for this shape: decide the **max count** up front (that's how many ordinal groups you emit in
the schema); use **one consistent field prefix per slot** (`title_`, `icon_`, …); pick a single
"required" field per slot (usually `title`) as the presence gate so partially-filled tails drop off.

**Shape D — Free / repeatable content** (Gallery one/two/three; a "Free" content well).
`fixed_content` is empty and `allowed_content` lists the item types an author may add any number
of, in any order. The set isn't limited to media — it can include text types too:

```jsonc
{ "name": "Gallery one", "displayable": true, "fixed_content": [],
  "allowed_content": ["image", "video"] }

// a broader "anything goes" well, from a real content type:
{ "name": "Free", "displayable": false, "fixed_content": [],
  "allowed_content": ["image", "text-input", "html", "text-box", "wysiwyg", "gallery"] }
```
The integration ignores named fields and iterates `->all()`, branching on each item's `->type`
(handle every type you listed in `allowed_content`: `image`, `video`, `wysiwyg`, `html`,
`text-box`, `gallery`, …):

```blade
@if($page->section('Gallery one')->isDisplayed())
  @foreach($page->section('Gallery one')->all() as $item)
    @if($item->type === 'video')
      <x-video :src="$item->raw()" />
    @else
      <img src="{{ $item->url() }}">
    @endif
  @endforeach
@endif
```

Choosing between the shapes:

- **Shape B (`component`)** — unbounded rows, each with several structured sub-fields, all of one
  known type-set. The preferred repeater.
- **Shape C (ordinal fields)** — a small, fixed maximum number of structured slots; or when
  extending a content type that already uses the pattern.
- **Shape D (`allowed_content`)** — a flat, unbounded stream of loose items (images/videos) whose
  order the author controls and which have no per-row sub-fields.

### 11.4 `displayable`

- `displayable: true` — the author gets an on/off toggle for the whole section. **Always guard**
  its render with `@if($page->section('X')->isDisplayed())`.
- `displayable: false` — the section is always present (e.g. `Hero`). You may still guard for
  safety, but it won't be author-hidden.

### 11.5 End-to-end: producing a new section as a (schema, integration) pair

Suppose the brief is "a testimonials strip with up to three quotes, each with an author and a
logo." The count is small and fixed (3), so this example uses the ordinal-field **Shape C** (for
an *unbounded* list you'd instead reach for a `component` repeater, Shape B). Produce both
artifacts in one go, names matching exactly:

```jsonc
// 1) Schema — add this section object to the content type's array
{
  "name": "Testimonials",
  "description": "Up to three customer quotes",
  "displayable": true,
  "allowed_content": [],
  "fixed_content": [
    { "name": "heading",    "type": "text-input", "label": "Heading" },
    { "name": "quote_one",  "type": "text-box",   "label": "Quote one" },
    { "name": "author_one", "type": "text-input", "label": "Author one" },
    { "name": "logo_one",   "type": "image",      "label": "Logo one" },
    { "name": "quote_two",  "type": "text-box",   "label": "Quote two" },
    { "name": "author_two", "type": "text-input", "label": "Author two" },
    { "name": "logo_two",   "type": "image",      "label": "Logo two" },
    { "name": "quote_three","type": "text-box",   "label": "Quote three" },
    { "name": "author_three","type": "text-input","label": "Author three" },
    { "name": "logo_three", "type": "image",      "label": "Logo three" }
  ]
}
```
```blade
{{-- 2) Integration — reads exactly those names --}}
@if($page->section('Testimonials')->isDisplayed())
  @php
    $s = $page->section('Testimonials');
    $quotes = [];
    foreach (['one','two','three'] as $k) {
        if (!$s->content("quote_{$k}")->raw()) continue;
        $quotes[] = [
            'quote'  => $s->content("quote_{$k}")->raw(),
            'author' => $s->content("author_{$k}")->raw(),
            'logo'   => $s->content("logo_{$k}")->url(),
        ];
    }
  @endphp
  <x-testimonials :heading="$s->content('heading')->raw()" :quotes="$quotes" />
@endif
```

That is the whole skill: **choose a shape, name the fields once, emit the schema and the reader
together.**

### 11.6 Conventions & gotchas

- **Names are the contract and are case/character-exact.** `section('Grid')` ≠ `section('grid')`;
  `content('cta_text')` ≠ `content('cta-text')`. Whatever the schema says, the template must echo.
- **Pick one naming style per project.** This codebase mixes kebab (`list-title`, `campaign-id`)
  and snake (`cta_text`, `label_one`). New content types should be internally consistent; snake +
  `_one…_nine` is the more common pattern here for repeaters.
- **`label` may be `null` or duplicated** in the schema (e.g. several fields labelled "Icon one").
  That's a CMS-UI cosmetic only — irrelevant to your code, which keys on `name`.
- **`->url()`/`->variant()` only work on `image` and `video-file` fields** (for `video-file` also
  `->sources()`/`->poster()`/`->fallback()`); **`->items()` only on `gallery`.** On any other type
  they return empty because it's a generic `ContentItem`. Match the reader to the `type`.
- **`video` (embed) vs `video-file` (self-hosted) are different types.** `video` is a generic
  `ContentItem` (`->raw()` = embed id, provider in `->meta('type')`); `video-file` is a `VideoFile`
  with `->sources()`/`->url()`/`->poster()`. Don't call image/gallery helpers on either by mistake.
- **`video` fields return an id/stream string** via `->raw()`, not a URL — pass it to your player
  component.
- **Select fields compare on `value`**, not the human `label`. Use the `Select` helpers
  `->is('x')` / `->in([...])` (strict equality on the value) to branch content on the selection.
- **`component` (repeater) rows invert the title rule.** Read them with `->rows()` →
  `$row->content('SubField')`; sub-fields **are** hydrated to their real types (`Image`,
  `VideoFile`, `Select`, …), so no manual URL/variant plumbing. But a sub-field is keyed by its
  **`label`** (fallback `name`), the opposite of top-level fixed content. Give sub-fields
  `label == name` to keep reads predictable. (See §11.3 Shape B.)
- **Presence is truthiness of `->raw()`.** Use it to gate optional fields and to trim the empty
  tail of a Shape-C ordinal repeater. There is no "field exists" flag beyond content being non-empty.
- **`displayable: false` sections still need data**; they just can't be toggled off by the author.

### 11.7 Procedure for an agent (given a page design, produce the pair)

1. Break the design into **sections**; name each (`Hero`, `Content one`, `Gallery`, …).
2. For each section pick a **shape**: fixed block (A), native `component` repeater (B), fixed-count
   ordinal repeater (C), or unbounded free content (D).
3. List each section's **fields**: choose `name` (final identifier) and `type` (from the §11.2
   table). Add `meta.options` for selects, `config.items` for `component` repeaters. Set
   `displayable` (true unless it must always show).
4. Emit the **schema JSON** (array of section objects) — this is what gets created in Frisbee.
5. Emit the **integration** that reads the *same* names: `section()` + `content()` for A;
   `->content('field')->rows()` then `$row->content('SubField')` for B; an ordinal loop for C;
   `->all()` + `type` switch for D — each guarded by `isDisplayed()`.
6. Cross-check every `content('…')` / `section('…')` string against the schema `name`s. Any string
   with no matching schema field is a bug that will render blank.

---

## 12. Rules for an AI agent building with this SDK

1. **Read-only.** There is no create/update/delete. Never claim you can write to Frisbee.
2. **Fetch through `Frisbee::make(...)->read()`.** In a framework, bind `Frisbee` once and inject it.
3. **Config comes from env**: `FRISBEE_READ_API_TOKEN`, `FRISBEE_DISTRIBUTION_ID`,
   `FRISBEE_READ_API_URL`. Never hard-code the token.
4. **Address content by the exact string names/ids defined in Frisbee** (section names, content
   titles, content-type ids, tag ids). You must obtain these — from a brief, an existing template,
   or by printing a real API response. Don't guess ids.
5. **Guard rendering** with `->isDisplayed()` (sections) and `->notEmpty()`/`->empty()` (items).
6. **Load related lists via `addCall(new ListCall(...))`** on a `PageRequest` so they run
   concurrently, then read them with `getAdditionalContent($key)`.
7. **Catch `FrisbeeContentNotFound`** and return a 404; let real errors surface in dev.
8. **Cache fetched content in production**; the SDK won't do it for you.
9. **Stick to the documented surface.** The five `ReadAPI` methods and the `Page` helpers above
   are the whole API. If you need something else, it isn't here — solve it in application code, not
   by inventing SDK calls.

---

## Appendix — namespace cheat sheet

```
WeAreAwesome\FrisbeePHPAPI\Frisbee                         // entry point
WeAreAwesome\FrisbeePHPAPI\Api\ReadAPI                     // read operations
WeAreAwesome\FrisbeePHPAPI\Requests\Content\PageRequest    // build a page fetch
WeAreAwesome\FrisbeePHPAPI\Requests\Content\PagesRequest   // build a multi-page fetch
WeAreAwesome\FrisbeePHPAPI\Requests\Content\ListCall       // build a list fetch / additional call
WeAreAwesome\FrisbeePHPAPI\Requests\Content\DistributionCall
WeAreAwesome\FrisbeePHPAPI\Content\Page                    // returned page
WeAreAwesome\FrisbeePHPAPI\Content\ContentList             // returned list (content() + pagination())
WeAreAwesome\FrisbeePHPAPI\Content\SiteMap                 // returned map (getData())
WeAreAwesome\FrisbeePHPAPI\Content\Sections\Section        // section (isDisplayed/content/all)
WeAreAwesome\FrisbeePHPAPI\Content\Types\{Text,Image,Gallery,VideoFile,Select,Component,ComponentRow,ContentItem}
WeAreAwesome\FrisbeePHPAPI\Content\Menus\{Menu,MenuItem}
WeAreAwesome\FrisbeePHPAPI\Exceptions\{FrisbeeException,FrisbeeAuthorizationException}
WeAreAwesome\FrisbeePHPAPI\Requests\Content\Exceptions\FrisbeeContentNotFound
```
