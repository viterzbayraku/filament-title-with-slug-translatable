<div
    x-data="{
        context: '{{ $getContext() }}',
        state: $wire.entangle('{{ $getStatePath() }}'),
        titleUk: '',
        statePersisted: '',
        stateInitial: '',
        editing: false,
        modified: false,
        handleTitleBlurUk() {
            setTimeout(() => {
                // Отримуємо значення напряму з DOM
                let input = document.getElementById('data.name.uk');
                if (input) {
                    this.titleUk = input.value;
                }
                console.log('handleTitleBlurUk (delayed)', {context: this.context, state: this.state, titleUk: this.titleUk});
                if (this.context === 'create' && !this.state && this.titleUk) {
                    this.state = slugify(cyrillicToLatin(this.titleUk));
                    setTimeout(() => {
                        console.log('handleTitleBlurUk: before submitModification', {state: this.state, titleUk: this.titleUk});
                        this.submitModification();
                    }, 100);
                }
                // Форсуємо Livewire refresh для негайної синхронізації state після blur
                if ($wire && $wire.$refresh) {
                    $wire.$refresh();
                }
            }, 100);
        },
        initModification: function() {
            this.stateInitial = this.state;
            if(!this.statePersisted) {
                this.statePersisted = this.state;
            }
            this.editing = true;
            setTimeout(() => $refs.slugInput.focus(), 75);
        },
        submitModification: function() {
            console.log('submitModification called', {state: this.state, titleUk: this.titleUk});
            if (!this.state && this.titleUk) {
                this.state = slugify(cyrillicToLatin(this.titleUk));
                console.log('submitModification generated slug:', this.state);
            }
            this.stateInitial = this.state;
            this.detectModification();
            this.editing = false;
            this.$nextTick(() => {
                console.log('submitModification $wire.set', {state: this.state});
                $wire.set('{{ $getStatePath() }}', this.state);
            });
       },
       cancelModification: function() {
            this.stateInitial = this.state;
            this.detectModification();
            this.editing = false;
       },
       resetModification: function() {
            this.stateInitial = this.statePersisted;
            this.detectModification();
       },
       detectModification: function() {
            this.modified = this.stateInitial !== this.statePersisted;
       },
    }"
    x-init="
        $nextTick(() => {
            if ($wire.__instance && $wire.__instance.serverMemo && $wire.__instance.serverMemo.data && $wire.__instance.serverMemo.data.name && $wire.__instance.serverMemo.data.name.uk) {
                titleUk = $wire.__instance.serverMemo.data.name.uk;
            }
            window.Livewire && window.Livewire.hook && window.Livewire.hook('message.processed', () => {
                if ($wire.__instance && $wire.__instance.serverMemo && $wire.__instance.serverMemo.data && $wire.__instance.serverMemo.data.name && $wire.__instance.serverMemo.data.name.uk) {
                    titleUk = $wire.__instance.serverMemo.data.name.uk;
                    // Генеруємо slug лише якщо він порожній і є titleUk
                    if (context === 'create' && !state && titleUk) {
                        state = slugify(cyrillicToLatin(titleUk));
                        setTimeout(() => {
                            submitModification();
                        }, 100);
                    }
                }
            });
        })"
    x-on:title-blur-uk.window="handleTitleBlurUk()"
    x-on:submit.document="modified = false"
>

    <div
        {{ $attributes->merge($getExtraAttributes())->class(['flex mx-1 items-center justify-between group text-sm filament-forms-text-input-component']) }}
    >

        @if($getReadonly())

            <span class="flex">
                <span class="mr-1">{{ $getLabelPrefix() }}</span>
                <span class="text-gray-400">{{ $getFullBaseUrl() }}</span>
                <span class="text-gray-400 font-semibold">{{ $getState() }}</span>
            </span>

            @if($getSlugInputUrlVisitLinkVisible())

                <a
                    href="{{ $getRecordUrl() }}"
                    target="_blank"
                    class="
                        filament-link cursor-pointer text-sm text-primary-600 underline
                        inline-flex items-center justify-center space-x-1
                        hover:text-primary-500
                        dark:text-primary-500 dark:hover:text-primary-400
                    "
                >

                    <span>{{ $getVisitLinkLabel() }}</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h6m0 0v6m0-6L10 19M5 19h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                </a>
            @endif

        @else

            <span
                 class="
                    @if(!$getState()) flex items-center gap-1 @endif
                "
            >

                <span>{{ $getLabelPrefix() }}</span>

                <span
                    x-text="!editing ? '{{ $getFullBaseUrl() }}' : '{{ $getBasePath() }}'"
                    class="text-gray-400"
                ></span>

                <a
                    href="#"
                    role="button"
                    title="{{ trans('filament-title-with-slug::package.permalink_action_edit') }}"
                    x-on:click.prevent="initModification()"
                    x-show="!editing"
                    class="
                        cursor-pointer
                        font-semibold text-gray-400
                        inline-flex items-center justify-center
                        hover:underline hover:text-primary-500
                        dark:hover:text-primary-400
                    "
                    :class="context !== 'create' && modified ? 'text-gray-600 bg-gray-100 dark:text-gray-400 dark:bg-gray-700 px-1 rounded-md' : ''"
                >
                    <span class="mr-1">{{ $getState() }}</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-primary-600 dark:text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-2.828 0L9 13zm-6 6h6"/></svg>
                    <span class="sr-only">{{ trans('filament-title-with-slug::package.permalink_action_edit') }}</span>
                </a>

                @if($getSlugLabelPostfix())
                    <span
                        x-show="!editing"
                        class="ml-0.5 text-gray-400"
                    >{{ $getSlugLabelPostfix() }}</span>
                @endif

                <span x-show="!editing && context !== 'create' && modified"> [{{ __('filament-title-with-slug::package.permalink_status_changed') }}]</span>

            </span>

            <div
                class="flex-1 mx-2"
                x-show="editing"
                style="display: none;"
            >

                <div class="relative flex items-center gap-2">
                    <input
                        type="text"
                        x-ref="slugInput"
                        x-model="state"
                        x-bind:disabled="!editing"
                        x-on:keydown.enter="submitModification()"
                        x-on:keydown.escape="cancelModification()"
                        x-on:blur="
                            console.log('onBlur', {context, state, titleUk});
                            if (context === 'create' && !state && titleUk) {
                                state = slugify(cyrillicToLatin(titleUk));
                                console.log('slug generated:', state);
                                setTimeout(() => {
                                    console.log('before submitModification', {state, titleUk});
                                    submitModification();
                                }, 100);
                            }
                        "
                        {!! ($autocomplete = $getAutocomplete()) ? "autocomplete=\"{$autocomplete}\"" : null !!}
                        id="{{ $getId() }}"
                        {!! ($placeholder = $getPlaceholder()) ? "placeholder=\"{$placeholder}\"" : null !!}
                        {!! $isRequired() ? 'required' : null !!}
                        {{ $getExtraInputAttributeBag()->class([
                            'block',
                            'w-2xl',
                            'max-w-2xl',
                            'text-sm', // Зменшуємо шрифт для slug
                            'transition duration-75 rounded-lg shadow-sm focus:border-primary-600 focus:ring-1 focus:ring-inset focus:ring-primary-600 disabled:opacity-70',
                            'dark:bg-gray-700 dark:text-white' => config('forms.dark_mode'),
                            'border-gray-300' => !$errors->has($getStatePath()),
                            'dark:border-gray-600' => !$errors->has($getStatePath()) && config('forms.dark_mode'),
                            'border-danger-600 ring-danger-600' => $errors->has($getStatePath())
                        ]) }}
                    />
                    <span
                        x-on:click.prevent="submitModification()"
                        class="cursor-pointer text-primary-600 hover:underline text-sm font-medium px-2 py-1 rounded focus:outline-none focus:underline"
                        tabindex="0"
                        role="button"
                    >
                        ОК
                    </span>
                    <span x-on:click.prevent="
    let input = document.getElementById('data.name.uk');
    if (input) {
        titleUk = input.value;
        state = slugify(cyrillicToLatin(titleUk));
        submitModification();
    }
" class="cursor-pointer text-gray-500 hover:underline text-xs font-normal px-2 py-1 rounded focus:outline-none focus:underline ml-1" tabindex="0" role="button" title="Оновити slug з поля Назва">
    <svg xmlns="http://www.w3.org/2000/svg" class="inline h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582M20 20v-5h-.581M5 9A7.003 7.003 0 0112 5c1.657 0 3.156.576 4.354 1.536M19 15a7.003 7.003 0 01-7 4c-1.657 0-3.156-.576-4.354-1.536" /></svg>
    Оновити
</span>
                    <span
                        x-on:click="cancelModification()"
                        class="cursor-pointer text-gray-400 hover:text-danger-600 text-lg px-1"
                        title="{{ trans('filament-title-with-slug::package.permalink_action_cancel') }}"
                        tabindex="0"
                        role="button"
                    >
                        &times;
                    </span>
                </div>

            </div>

            <span
                x-show="context === 'edit'"
                class="flex items-center space-x-2"
            >

                @if($getSlugInputUrlVisitLinkVisible())

                    <a

                        href="{{ $getRecordUrl() }}"
                        target="_blank"
                        class="filament-link inline-flex items-center justify-center space-x-1 hover:underline focus:outline-none focus:underline text-sm text-primary-600 hover:text-primary-500 dark:text-primary-500 dark:hover:text-primary-400 cursor-pointer"
                >

                    <span>{{ $getVisitLinkLabel() }}</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h6m0 0v6m0-6L10 19M5 19h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>

                </a>

                @endif
            </span>
        @endif
    </div>
</div>

<script>
    // Українська транслітерація (як у PHP)
    function cyrillicToLatin(text) {
        const map = {
            'Є':'Ye','І':'I','Ї':'Yi','Ґ':'G','є':'ie','і':'i','ї':'i','ґ':'g',
            'А':'A','Б':'B','В':'V','Г':'H','Д':'D','Е':'E','Ж':'Zh','З':'Z','И':'Y','Й':'Y','К':'K','Л':'L','М':'M','Н':'N','О':'O','П':'P','Р':'R','С':'S','Т':'T','У':'U','Ф':'F','Х':'Kh','Ц':'Ts','Ч':'Ch','Ш':'Sh','Щ':'Shch','Ю':'Yu','Я':'Ya',
            'а':'a','б':'b','в':'v','г':'h','д':'d','е':'e','ж':'zh','з':'z','и':'y','й':'y','к':'k','л':'l','м':'m','н':'n','о':'o','п':'p','р':'r','с':'s','т':'t','у':'u','ф':'f','х':'kh','ц':'ts','ч':'ch','ш':'sh','щ':'shch','ю':'yu','я':'ya',
        };
        return text.split('').map(char => map[char] ?? char).join('');
    }
    // Створення slug з латиниці
    function slugify(text) {
        return (text || '')
            .toLowerCase()
            .replace(/\s+/g, '-')
            .replace(/[^a-z0-9\-]/g, '')
            .replace(/-+/g, '-')
            .replace(/^-+|-+$/g, '');
    }
    // Приклад використання:
    // slugify(cyrillicToLatin(title))
</script>