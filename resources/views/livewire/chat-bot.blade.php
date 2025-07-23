<div class="relative w-full" id="chatgpt-agent-window" style="{{ $winWidth }}">
    <div class="fixed z-30 cursor-pointer" style="bottom: 1rem; right: 1rem;">
        <x-filament::button wire:click="togglePanel" id="btn-chat" :icon="$buttonIcon" :color="$panelHidden ? 'primary' : 'gray'">
            {{ $panelHidden ? $buttonText : __('filament-ai-chat-agent::translations.close') }}
        </x-filament::button>
    </div>

    <x-filament::section
        class="flex-1 p-2 sm:p-6 justify-between flex flex-col max-h-screen fixed {{ $winPosition == 'left' ? 'left-0' : 'right-0' }} bottom-0 bg-white shadow z-30 {{ $panelHidden ? 'hidden' : '' }}"
        style="{{ $winWidth }}" id="chat-window">
        <x-slot name="heading" :icon="$buttonIcon" icon-size="md">
            {{ $name }}
        </x-slot>

        <x-slot name="headerEnd">
            <x-filament::icon-button color="gray" icon="heroicon-o-document" wire:click="resetSession()"
                label="{{ __('filament-ai-chat-agent::translations.new_session') }}"
                tooltip="{{ __('filament-ai-chat-agent::translations.new_session') }}" />
            <x-filament::icon-button color="gray" :icon="$winWidth != 'width:100%;' ? 'heroicon-m-arrows-pointing-out' : 'heroicon-m-arrows-pointing-in'" wire:click="changeWinWidth()"
                label="{{ __('filament-ai-chat-agent::translations.toggle_full_screen') }}"
                tooltip="{{ __('filament-ai-chat-agent::translations.toggle_full_screen') }}" />
            @if ($showPositionBtn)
                <x-filament::icon-button color="gray" icon="heroicon-s-arrows-right-left"
                    wire:click="changeWinPosition()" label="{{ __('filament-ai-chat-agent::translations.move_window') }}"
                    tooltip="{{ __('filament-ai-chat-agent::translations.move_window') }}" />
            @endif
            <x-filament::icon-button color="gray" icon="heroicon-s-minus-small" wire:click="togglePanel"
                label="{{ __('filament-ai-chat-agent::translations.hide_chat') }}"
                tooltip="{{ __('filament-ai-chat-agent::translations.hide_chat') }}" />
        </x-slot>

        <div id="messages"
            wire:scroll
            wire:key="chatgpt-agent-messages"
            style="overflow: auto; min-height: max(20rem, 30vh); max-height: calc(100vh - 11rem); padding-bottom: 1rem; margin-bottom: 65px;"
            class="flex flex-col space-y-4 overflow-y-auto scrollbar-thumb-blue scrollbar-thumb-rounded scrollbar-track-blue-lighter scrollbar-w-2 scrolling-touch">
            @foreach ($messages as $message)
                @if ($message['role'] !== 'system')
                    <div wire:key="chatgpt-agent-message-{{ $loop->index }}">
                        @if ($message['role'] == 'assistant')
                            <div class="chat-message">
                                <div class="flex items-end">
                                    <div class="flex flex-col space-y-2 text-xs mx-2 order-2 items-start">
                                        <div>
                                            <div class="px-4 py-2 rounded-lg block rounded-bl-none bg-gray-300 text-gray-600">
                                                @isset($message['content'])
                                                    {!! \Illuminate\Mail\Markdown::parse($message['content']) !!}
                                                @endisset
                                            </div>
                                        </div>
                                    </div>
                                
                                    @if($logoUrl && $logoUrl !== '')
                                        <img src="{{ $logoUrl }}" alt="{{ $name }}" width="41" height="41" class="relative h-7 w-7 p-1 rounded-full" />
                                    @else
                                        <div class="relative h-5 w-5 p-1 rounded-full text-white flex items-center justify-center bg-primary-500">
                                            <x-filament-ai-chat-agent::chatgpt-svg />
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="chat-message">
                                <div class="flex items-end justify-end">
                                    <div class="flex flex-col space-y-2 text-xs max-w-xs mx-2 order-1 items-end">
                                        <div>
                                            <div class="px-4 py-2 rounded-lg block rounded-br-none bg-blue-600 text-white">
                                                {!! \Illuminate\Mail\Markdown::parse($message['content']) !!}
                                            </div>
                                        </div>
                                    </div>
                                    @if(auth()->user() && method_exists(auth()->user(), 'getFilamentAvatarUrl') && auth()->user()->getFilamentAvatarUrl())
                                        <x-filament::avatar size="sm" :src="auth()->user()?->getFilamentAvatarUrl()" />
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            @endforeach
        </div>
        <div class="fi-section-footer border-t border-gray-200 pt-4 dark:border-white/10 absolute bottom-0 left-0 p-2 sm:p-6 bg-white dark:bg-gray-900 w-full">
            <div class="relative">
                <div id="selected-text-indicator" class="hidden dark:text-white p-1 rounded">
                    <span>{{ __('filament-ai-chat-agent::translations.selected_text') }}:</span>
                    <span id="selected-text-characters"></span>
                    <span> {{ __('filament-ai-chat-agent::translations.characters') }}</span>
                    <x-filament::button id="add-quote-button" size="xs" color="gray" class="ml-2 mb-2">
                        {{ __('filament-ai-chat-agent::translations.add_to_message') }}
                    </x-filament::button>
                </div>
                <div class="flex flex-col w-full py-2 flex-grow md:py-3 md:pl-4 relative bg-gray-200 dark:border-gray-900/50 dark:text-white dark:bg-gray-700 rounded-md shadow">
                    <textarea x-data="{ 
                            resize() { 
                                $el.style.height = '48px'; 
                                $el.style.height = `${$el.scrollHeight}px`; 
                            }, 
                            collapse() { 
                                $el.style.height = '48px'; 
                            }
                        }"
                        x-init="resize()"
                        @input="resize()"
                        @blur="collapse()"
                        @focus="resize()"
                        wire:loading.attr="disabled"
                        wire:target="sendMessage"
                        @keydown.enter="!$event.shiftKey && ($event.preventDefault(), $wire.sendMessage())"
                        wire:model="question"
                        tabindex="0"
                        data-id="root"
                        style="max-height: 200px; height: 48px; padding-right:40px;"
                        placeholder="{{ __('filament-ai-chat-agent::translations.send_a_message') }}"
                        autofocus
                        class="m-0 w-full resize-none border-0 bg-transparent p-0 pr-7 focus:ring-0 focus:outline-none focus:placeholder-gray-400 dark:bg-transparent pl-2 md:pl-0"
                        id="chat-input">
                    </textarea>
                    </textarea>
                    <div class="absolute bottom-1.5 md:bottom-2.5 right-1 md:right-2" style="min-width: 25px;">
                        <x-filament::icon-button color="gray" icon="heroicon-o-paper-airplane" wire:loading.remove
                            wire:target="sendMessage" wire:click="sendMessage"
                            label="{{ __('filament-ai-chat-agent::translations.send_message') }}" />
                            <div>
                        <x-filament::loading-indicator wire:target="sendMessage" size="lg"
                            wire:loading
                            wire:target="sendMessage" />
                            </div>
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>

    <style>
        .scrollbar-w-2::-webkit-scrollbar {
            width: 0.5rem;
            height: 0.5rem;
        }

        .scrollbar-track-blue-lighter::-webkit-scrollbar-track {
            --bg-opacity: 1;
            background-color: #f7fafc;
            background-color: rgba(247, 250, 252, var(--bg-opacity));
        }

        .scrollbar-thumb-blue::-webkit-scrollbar-thumb {
            --bg-opacity: 1;
            background-color: #edf2f7;
            background-color: rgba(237, 242, 247, var(--bg-opacity));
        }

        .scrollbar-thumb-rounded::-webkit-scrollbar-thumb {
            border-radius: 0.25rem;
        }

        .bg-blue-600 {
            --tw-bg-opacity: 1;
            background-color: rgb(37 99 235 / var(--tw-bg-opacity));
        }

        .order-2 {
            order: 2;
        }

        .mx-2 {
            margin-left: 0.5rem;
            margin-right: 0.5rem;
        }

        .border-0 {
            border-width: 0px;
        }

        .rounded-br-right {
            border-bottom-right-radius: 0px;
        }

        .rounded-sm {
            border-radius: 0.125rem;
        }

        .p-1 {
            padding: 0.25rem;
        }

        .pl-1 {
            padding-left: 0.25rem;
        }

        .pl-2 {
            padding-left: 0.5rem;
        }

        .pt-4 {
            padding-top: 1rem;
        }

        .h-\[30px\] {
            height: 30px;
        }

        .w-\[30px\] {
            width: 30px;
        }

        .right-0 {
            right: 0;
        }

        .left-0 {
            left: 0;
        }

        .right-1 {
            right: 0.25rem;
        }

        .md\:right-2 {
            right: 0.5rem;
        }

        .max-h-screen {
            max-height: 100vh;
        }

        .chat-message blockquote {
            padding: 0.5rem 1rem;
            margin: 0.5rem 0;
            border-left: 3px solid #ccc;
        }

        .chat-message ul {
            list-style-type: circle;
            padding-left: 1rem;
        }

        .chat-message ol {
            list-style-type: decimal;
            padding-left: 1rem;
        }

        .chat-message strong {
            font-weight: 600;
        }

        .chat-message em {
            font-style: italic;
        }

        .chat-message code {
            background-color: #f4f4f4;
            padding: 0.2rem 0.4rem;
            border-radius: 4px;
        }

        .chat-message pre {
            background-color: #f4f4f4;
            padding: 0.5rem;
            border-radius: 4px;
            overflow-x: auto;
        }

        .chat-message a {
            color: #3182ce;
            text-decoration: underline;
        }

        .chat-message a:hover {
            color: #2c5282;
            text-decoration: none
        }
    </style>
@script
    <script>
        const el = document.getElementById('messages');

        window.addEventListener('sendmessage', event => {
            setTimeout(() => {
                el.scrollTop = el.scrollHeight
            }, 100)
        });

        // Handle text selection
        document.addEventListener('mouseup', function() {
            const selectedText = window.getSelection().toString().trim();
            const selectedTextIndicator = document.getElementById('selected-text-indicator');
            const selectedTextCharacters = document.getElementById('selected-text-characters');

            if (selectedText) {
                selectedTextCharacters.innerText = selectedText.length;
                selectedTextIndicator.classList.remove('hidden');
                selectedTextIndicator.dataset.selectedText = selectedText;
            } else {
                selectedTextIndicator.classList.add('hidden');
                selectedTextIndicator.dataset.selectedText = '';
            }
        });

        // Add quote to textarea
        document.getElementById('add-quote-button').addEventListener('click', function() {
            const selectedTextIndicator = document.getElementById('selected-text-indicator');
            const selectedText = selectedTextIndicator.dataset.selectedText;
            var textarea = document.querySelector('#chat-input');
            if (selectedText) {
                const quotedText = selectedText.split('\n').map(line => `> ${line}`).join('\n');
                @this.set('question', @this.get('question') + `\n${quotedText}\n`).then(() => {
                    textarea.style.height = "inherit";
                    textarea.style.height = `${textarea.scrollHeight}px`;
                    el.style.paddingBottom = `${textarea.scrollHeight}px`;
                    el.scrollTop = el.scrollHeight;
                    textarea.focus();
                    window.getSelection().removeAllRanges();
                });
                selectedTextIndicator.classList.add('hidden');
                selectedTextIndicator.dataset.selectedText = '';
            }
        });

        document.addEventListener('livewire:initialized', function () {
            var textarea = document.querySelector('#chat-input');
            el.scrollTop = el.scrollHeight;
            textarea.focus();
            el.style.paddingBottom = `${textarea.scrollHeight}px`;

            if ({{ $pageWatcherEnabled }}) {
                function updateQuestionContext() {
                    const element = document.querySelector("{{ $pageWatcherSelector }}");
                    if (element) {
                        const context = element.innerText;
                        const value = context + "\nPage URL: " + window.location.href;
                        @this.set('questionContext', value);
                    }
                }

                updateQuestionContext();
                setInterval(updateQuestionContext, 5000);
            }
        });
    </script>
@endscript
</div>

