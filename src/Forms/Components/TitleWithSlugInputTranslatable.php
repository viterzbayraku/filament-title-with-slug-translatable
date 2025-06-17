<?php

namespace Viterzbayraku\Filament\Forms\Components;

use Viterzbayraku\Filament\Forms\Fields\SlugInput;
use Closure;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Set;
use Filament\Forms\Get;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use function config;
use function collect;
use function filled;
use function class_basename;

class TitleWithSlugInputTranslatable
{
    public static function make(
        // Model fields
        string|null $fieldTitle = null,
        string|null $fieldSlug = null,
        // Multilingual
        array $locales = [], // <--- Додаємо параметр для мов
        // Url
        string|Closure|null $urlPath = '/',
        string|Closure|null $urlHost = null,
        bool $urlHostVisible = true,
        bool|Closure $urlVisitLinkVisible = true,
        null|Closure|string $urlVisitLinkLabel = null,
        null|Closure $urlVisitLinkRoute = null,
        // Title
        string|Closure|null $titleLabel = null,
        string|null $titlePlaceholder = null,
        array|Closure|null $titleExtraInputAttributes = null,
        array $titleRules = [
            'required',
        ],
        array $titleRuleUniqueParameters = [],
        bool|Closure $titleIsReadonly = false,
        bool|Closure $titleAutofocus = true,
        null|Closure $titleAfterStateUpdated = null,
        // Slug
        string|null $slugLabel = null,
        array $slugRules = [
            'required',
        ],
        array $slugRuleUniqueParameters = [],
        bool|Closure $slugIsReadonly = false,
        null|Closure $slugAfterStateUpdated = null,
        null|Closure $slugSlugifier = null,
        string|Closure|null $slugRuleRegex = '/^[a-z0-9\-\_]*$/',
        string|Closure|null $slugLabelPostfix = null,
    ): Group {
        $fieldTitle = $fieldTitle ?? config('filament-title-with-slug.field_title');
        $fieldSlug = $fieldSlug ?? config('filament-title-with-slug.field_slug');
        $urlHost = $urlHost ?? config('filament-title-with-slug.url_host');

        // --- MULTILINGUAL TITLE, SINGLE SLUG SUPPORT ---
        if (!empty($locales)) {
            $titleInputs = [];
            foreach ($locales as $locale) {
                $titleInputs[] = TextInput::make("{$fieldTitle}.{$locale}")
                    ->label($titleLabel ? ($titleLabel . " [{$locale}]") : strtoupper($locale))
                    ->disabled($titleIsReadonly)
                    ->autofocus($titleAutofocus)
                    ->reactive()
                    ->disableAutocomplete()
                    ->rules($titleRules)
                    ->extraInputAttributes(array_merge(['class' => 'text-2xl font-semibold'], (array) ($titleExtraInputAttributes ?? [])))
                    ->beforeStateDehydrated(fn (TextInput $component, $state) => $component->state(trim($state)))
                    ->debounce(500)
                    ->extraInputAttributes([
                        'x-on:blur' => "\$dispatch('title-blur-{$locale}')"
                    ])
                    ->afterStateUpdated(
                        function (
                            $state,
                            Set $set,
                            Get $get,
                            string $context,
                            ?Model $record,
                            TextInput $component
                        ) use (
                            $slugSlugifier,
                            $fieldSlug, // додано у use
                            $titleAfterStateUpdated,
                            $locale,
                            $locales
                        ) {
                            $slug = $get($fieldSlug);
                            $autoUpdate = ($context === 'create' || empty($slug));
                            if ($autoUpdate && $locale === $locales[0]) {
                                // Оновлюємо slug лише при blur (через Alpine event)
                                // AlpineJS emit: $dispatch('title-blur-{$locale}')
                                // Livewire слухає через wire:listen
                                // Тому тут нічого не потрібно, логіка blur буде у JS/Blade
                            }
                            if ($titleAfterStateUpdated) {
                                $component->evaluate($titleAfterStateUpdated);
                            }
                        }
                    );
                if (in_array('required', $titleRules, true)) {
                    $titleInputs[count($titleInputs)-1]->required();
                }
                if ($titlePlaceholder !== '') {
                    $titleInputs[count($titleInputs)-1]->placeholder($titlePlaceholder ?: fn () => Str::of($fieldTitle)->headline());
                }
                if (!$titleLabel) {
                    $titleInputs[count($titleInputs)-1]->disableLabel();
                }
                if ($titleRuleUniqueParameters) {
                    $titleInputs[count($titleInputs)-1]->unique(...$titleRuleUniqueParameters);
                }
            }
            $hiddenInputSlugAutoUpdateDisabled = Hidden::make('slug_auto_update_disabled')->dehydrated(false);
            // Один slug для всіх мов
            $slugInput = SlugInput::make($fieldSlug)
                ->slugInputVisitLinkRoute($urlVisitLinkRoute)
                ->slugInputVisitLinkLabel($urlVisitLinkLabel)
                ->slugInputUrlVisitLinkVisible($urlVisitLinkVisible)
                ->slugInputContext(fn ($context) => $context === 'create' ? 'create' : 'edit')
                ->slugInputRecordSlug(fn (?Model $record) => $record?->{$fieldSlug} ?? null)
                ->slugInputModelName(
                    fn (?Model $record) => $record
                        ? Str::of(class_basename($record))->headline()
                        : ''
                )
                ->slugInputLabelPrefix($slugLabel)
                ->slugInputBasePath($urlPath)
                ->slugInputBaseUrl($urlHost)
                ->slugInputShowUrl($urlHostVisible)
                ->slugInputSlugLabelPostfix($slugLabelPostfix)
                ->readonly($slugIsReadonly)
                ->reactive()
                ->disableAutocomplete()
                ->disableLabel()
                ->regex($slugRuleRegex)
                ->rules($slugRules)
                ->afterStateUpdated(
                    function (
                        $state,
                        Set $set,
                        Get $get,
                        TextInput $component
                    ) use (
                        $slugSlugifier,
                        $fieldTitle,
                        $locales,
                        $slugAfterStateUpdated,
                        $fieldSlug // додано у use
                    ) {
                        // Оновлюємо slug лише з першої мови
                        $text = trim($state) === ''
                            ? $get("{$fieldTitle}.{$locales[0]}")
                            : $get($fieldSlug);
                        $set($fieldSlug, self::slugify($slugSlugifier, $text));
                        $set('slug_auto_update_disabled', true);
                        if ($slugAfterStateUpdated) {
                            $component->evaluate($slugAfterStateUpdated);
                        }
                    }
                )
                ->beforeStateDehydrated(function ($state, Get $get, Set $set) use ($slugSlugifier, $fieldTitle, $locales, $fieldSlug) {
                    if (trim($state) === '') {
                        $title = $get("{$fieldTitle}.{$locales[0]}");
                        $set($fieldSlug, self::slugify($slugSlugifier, $title));
                    }
                });
            if (in_array('required', $slugRules, true)) {
                $slugInput->required();
            }
            $slugRuleUniqueParameters
                ? $slugInput->unique(...$slugRuleUniqueParameters)
                : $slugInput->unique(ignorable: fn (?Model $record) => $record);
            return Group::make()
                ->schema([
                    \Filament\Forms\Components\Placeholder::make('title-with-tabs')
                        ->view('filament-title-with-slug::forms.fields.title-with-tabs', [
                            'titleLabel' => $titleLabel ? ($titleLabel . ' [' . strtoupper($locales[0]) . ']') : strtoupper($locales[0]),
                            'locales' => $locales,
                            'activeLocale' => $locales[0],
                            'titleInputs' => $titleInputs,
                        ]),
                    \Filament\Forms\Components\Tabs::make('locale_tabs')
                        ->tabs(
                            collect($locales)->map(fn($locale, $i) =>
                                \Filament\Forms\Components\Tabs\Tab::make($locale)
                                    ->label(strtoupper($locale))
                                    ->schema([
                                        $titleInputs[$i],
                                    ])
                            )->toArray()
                        )
                        ->extraAttributes(['class' => 'my-custom-tabs text-xs py-0 my-0']),
                    $slugInput,
                    $hiddenInputSlugAutoUpdateDisabled,
                ])
                ->extraAttributes(['class' => 'filament-title-with-slug__group shadow-none border-0 bg-transparent p-0']);
        }

        /** Input: "Title" */
        $textInput = TextInput::make($fieldTitle)
            ->disabled($titleIsReadonly)
            ->autofocus($titleAutofocus)
            ->reactive()
            ->disableAutocomplete()
            ->rules($titleRules)
            ->extraInputAttributes($titleExtraInputAttributes ?? ['class' => 'text-2xl font-semibold'])
            ->beforeStateDehydrated(fn (TextInput $component, $state) => $component->state(trim($state)))
            ->afterStateUpdated(
                function (
                    $state,
                    Set $set,
                    Get $get,
                    string $context,
                    ?Model $record,
                    TextInput $component
                ) use (
                    $slugSlugifier,
                    $fieldSlug,
                    $titleAfterStateUpdated,
                ) {
                    $slugAutoUpdateDisabled = $get('slug_auto_update_disabled');

                    if ($context === 'edit' && filled($record)) {
                        $slugAutoUpdateDisabled = true;
                    }

                    if (! $slugAutoUpdateDisabled && filled($state)) {
                        $set($fieldSlug, self::slugify($slugSlugifier, $state));
                    }

                    if ($titleAfterStateUpdated) {
                        $component->evaluate($titleAfterStateUpdated);
                    }
                }
            );

        if (in_array('required', $titleRules, true)) {
            $textInput->required();
        }

        if ($titlePlaceholder !== '') {
            $textInput->placeholder($titlePlaceholder ?: fn () => Str::of($fieldTitle)->headline());
        }

        if (! $titleLabel) {
            $textInput->disableLabel();
        }

        if ($titleLabel) {
            $textInput->label($titleLabel);
        }

        if ($titleRuleUniqueParameters) {
            $textInput->unique(...$titleRuleUniqueParameters);
        }

        /** Input: "Slug" (+ view) */
        $slugInput = SlugInput::make($fieldSlug)

            // Custom SlugInput methods
            ->slugInputVisitLinkRoute($urlVisitLinkRoute)
            ->slugInputVisitLinkLabel($urlVisitLinkLabel)
            ->slugInputUrlVisitLinkVisible($urlVisitLinkVisible)
            ->slugInputContext(fn ($context) => $context === 'create' ? 'create' : 'edit')
            ->slugInputRecordSlug(fn (?Model $record) => $record?->getAttributeValue($fieldSlug))
            ->slugInputModelName(
                fn (?Model $record) => $record
                    ? Str::of(class_basename($record))->headline()
                    : ''
            )
            ->slugInputLabelPrefix($slugLabel)
            ->slugInputBasePath($urlPath)
            ->slugInputBaseUrl($urlHost)
            ->slugInputShowUrl($urlHostVisible)
            ->slugInputSlugLabelPostfix($slugLabelPostfix)

            // Default TextInput methods
            ->readonly($slugIsReadonly)
            ->reactive()
            ->disableAutocomplete()
            ->disableLabel()
            ->regex($slugRuleRegex)
            ->rules($slugRules)
            ->afterStateUpdated(
                function (
                    $state,
                    Set $set,
                    Get $get,
                    TextInput $component
                ) use (
                    $slugSlugifier,
                    $fieldTitle,
                    $fieldSlug,
                    $slugAfterStateUpdated,
                ) {
                    $text = trim($state) === ''
                        ? $get($fieldTitle)
                        : $get($fieldSlug);
                    $set($fieldSlug, self::slugify($slugSlugifier, $text));
                    $set('slug_auto_update_disabled', true);
                    if ($slugAfterStateUpdated) {
                        $component->evaluate($slugAfterStateUpdated);
                    }
                }
            )
            ->beforeStateDehydrated(function ($state, Get $get, Set $set) use ($slugSlugifier, $fieldTitle, $fieldSlug) {
                if (trim($state) === '') {
                    $title = $get($fieldTitle);
                    $set($fieldSlug, self::slugify($slugSlugifier, $title));
                }
            });

        if (in_array('required', $slugRules, true)) {
            $slugInput->required();
        }

        $slugRuleUniqueParameters
            ? $slugInput->unique(...$slugRuleUniqueParameters)
            : $slugInput->unique(ignorable: fn (?Model $record) => $record);

        /** Input: "Slug Auto Update Disabled" (Hidden) */
        $hiddenInputSlugAutoUpdateDisabled = Hidden::make('slug_auto_update_disabled')
            ->dehydrated(false);

        /** Group */

        return Group::make()
            ->schema([
                $textInput,
                $slugInput,
                $hiddenInputSlugAutoUpdateDisabled,
            ])
            ->extraAttributes(['class' => 'filament-title-with-slug__group shadow-none border-0 bg-transparent p-0']);
    }

    /** Fallback slugifier, over-writable with slugSlugifier parameter. */
    protected static function slugify(Closure|null $slugifier, string|null $text, string $locale = null): string
    {
        if (is_null($text) || ! trim($text)) {
            return '';
        }
        // Якщо мова українська або російська, транслітеруємо в латинку
        //if (in_array($locale, ['uk'])) {
            $text = self::cyrillicToLatin($text);
        //}
        return is_callable($slugifier)
            ? $slugifier($text)
            : Str::slug($text);
    }

    /** Транслітерація кирилиці в латинку (uk/ru) */
    protected static function cyrillicToLatin(string $text): string
    {
        $map = [
            // Українська
            'Є'=>'Ye','І'=>'I','Ї'=>'Yi','Ґ'=>'G','є'=>'ie','і'=>'i','ї'=>'i','ґ'=>'g',
            'А'=>'A','Б'=>'B','В'=>'V','Г'=>'H','Д'=>'D','Е'=>'E','Ж'=>'Zh','З'=>'Z','И'=>'Y','Й'=>'Y','К'=>'K','Л'=>'L','М'=>'M','Н'=>'N','О'=>'O','П'=>'P','Р'=>'R','С'=>'S','Т'=>'T','У'=>'U','Ф'=>'F','Х'=>'Kh','Ц'=>'Ts','Ч'=>'Ch','Ш'=>'Sh','Щ'=>'Shch','Ю'=>'Yu','Я'=>'Ya',
            'а'=>'a','б'=>'b','в'=>'v','г'=>'h','д'=>'d','е'=>'e','ж'=>'zh','з'=>'z','и'=>'y','й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'kh','ц'=>'ts','ч'=>'ch','ш'=>'sh','щ'=>'shch','ю'=>'yu','я'=>'ya',
        ];
        return strtr($text, $map);
    }
}
