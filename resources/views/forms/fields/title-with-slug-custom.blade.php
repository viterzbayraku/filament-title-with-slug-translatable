@php
    $fieldTitle = $fieldTitle ?? 'name';
    $fieldSlug = $fieldSlug ?? 'slug';
    $defaultTitle = ['uk' => '', 'en' => ''];
@endphp

<script>
    function initTitleWithSlug(locales, defaultTitle, entangledJson) {
        let safeTitle;
        try {
            safeTitle = entangledJson ? JSON.parse(entangledJson) : defaultTitle;
        } catch (e) {
            safeTitle = defaultTitle;
        }
        return {
            locale: '{{ $activeLocale }}',
            title: safeTitle,
            slug: $wire.entangle('state.' + @js($fieldSlug)),
            autoUpdateSlug: true,
            get nameJson() { return JSON.stringify(this.title); },
            set nameJson(val) {
                try { this.title = JSON.parse(val); } catch (e) {}
            },
            watchTitle() {
                this.$watch('title', value => {
                    $wire.set('state.nameJson', JSON.stringify(value));
                }, { deep: true });
            },
        };
    }
</script>

<div
    x-data="initTitleWithSlug(@js($locales), @js($defaultTitle), $wire.entangle('state.nameJson'))"
    x-init="watchTitle()"
    class="filament-title-with-slug-custom w-full"
>
    <!-- Вкладки мов -->
    <div class="flex gap-2 mb-2">
        @foreach($locales as $locale)
            <button
                type="button"
                class="px-2 py-1 rounded text-xs font-medium"
                :class="locale === '{{ $locale }}' ? 'bg-primary-600 text-white' : 'bg-gray-200 text-gray-700'"
                @click="locale = '{{ $locale }}'"
            >
                {{ strtoupper($locale) }}
            </button>
        @endforeach
    </div>

    <!-- Поле Title -->
    <div class="mb-2">
        <label class="block text-sm font-semibold mb-1">{{ $titleLabel }}444</label>
        @foreach($locales as $locale)
            <input
                x-show="locale === '{{ $locale }}'"
                type="text"
                class="w-full text-2xl font-semibold border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                x-model="title['{{ $locale }}']"
                @input="console.log('input title[{{ $locale }}]:', title['{{ $locale }}'])"
                @blur="if (locale === '{{ $locales[0] }}' && autoUpdateSlug && title[locale] !== undefined) { console.log('onBlur: генеруємо slug для', title[locale], locale); slug = generateSlug(title[locale], locale); }"
                :placeholder="`{{ addslashes($titleLabel) }} [${locale.toUpperCase()}]`"
            />
        @endforeach
    </div>

    <!-- Поле Slug -->
    <div class="mb-2 flex items-center gap-2">
        <input
            type="text"
            class="w-full max-w-md text-sm border-gray-300 focus:border-primary-500 focus:ring-primary-500"
            x-model="slug"
            @input="console.log('input slug:', slug); autoUpdateSlug = false"
            placeholder="slug"
        />
        <button type="button" class="text-xs text-gray-500 hover:text-primary-600" @click="console.log('click 🔄: генеруємо slug для', title[locale], locale); slug = generateSlug(title[locale] || '', locale); autoUpdateSlug = true;">🔄</button>
        <button type="button" class="text-xs text-gray-500 hover:text-red-600" @click="console.log('click ×: очищаємо slug'); slug = ''; autoUpdateSlug = true;">×</button>
    </div>
</div>

<script>
    function cyrillicToLatin(text) {
        console.log('cyrillicToLatin input:', text);
        const map = {
            'Є':'Ye','І':'I','Ї':'Yi','Ґ':'G','є':'ie','і':'i','ї':'i','ґ':'g',
            'А':'A','Б':'B','В':'V','Г':'H','Д':'D','Е':'E','Ж':'Zh','З':'Z','И':'Y','Й':'Y','К':'K','Л':'L','М':'M','Н':'N','О':'O','П':'P','Р':'R','С':'S','Т':'T','У':'U','Ф':'F','Х':'Kh','Ц':'Ts','Ч':'Ch','Ш':'Sh','Щ':'Shch','Ю':'Yu','Я':'Ya',
            'а':'a','б':'b','в':'v','г':'h','д':'d','е':'e','ж':'zh','з':'z','и':'y','й':'y','к':'k','л':'l','м':'m','н':'n','о':'o','п':'p','р':'r','с':'s','т':'t','у':'u','ф':'f','х':'kh','ц':'ts','ч':'ch','ш':'sh','щ':'shch','ю':'yu','я':'ya',
        };
        const result = text.split('').map(char => map[char] ?? char).join('');
        console.log('cyrillicToLatin output:', result);
        return result;
    }
    function slugify(text) {
        console.log('slugify input:', text);
        const result = (text || '')
            .toLowerCase()
            .replace(/\s+/g, '-')
            .replace(/[^a-z0-9\-]/g, '')
            .replace(/-+/g, '-')
            .replace(/^-+|-+$/g, '');
        console.log('slugify output:', result);
        return result;
    }
    function generateSlug(text, locale) {
        console.log('generateSlug input:', text, locale);
        let result;
        if (locale === 'uk') {
            result = slugify(cyrillicToLatin(text));
        } else {
            result = slugify(text);
        }
        console.log('generateSlug output:', result);
        return result;
    }
</script>
