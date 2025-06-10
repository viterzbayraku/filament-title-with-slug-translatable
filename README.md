# "Title With Slug" Input - Easy Permalink Slugs for Filament Forms (PHP / Laravel / Livewire)

This package for [FilamentPHP](https://filamentphp.com/plugins/title-with-slug-permalink) adds the form component `TitleWithSlugInput` which allows to
edit titles and slugs easily.

It is inspired by the classic **WordPress title & slug** implementation.

The plugin is fully configurable. You can change all labels, use your own slugifier, use a route() to generate the "
View" link, hide the host name, and many more. Read the [full documentation](#installation)

```php
TitleWithSlugInput::make(
    fieldTitle: 'title', // The name of the field in your model that stores the title.
    fieldSlug: 'slug', // The name of the field in your model that will store the slug.
    locales: ['uk', 'en'],
),
```
